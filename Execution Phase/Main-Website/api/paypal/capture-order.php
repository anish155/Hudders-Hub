<?php
/**
 * PayPal Sandbox - Capture Order
 * POST /api/paypal/capture-order.php
 *
 * Finalizes the payment after the user approves it in the PayPal popup.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/paypal.php';
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Log incoming request
$logFile = __DIR__ . '/../../paypal-error.log';
@file_put_contents($logFile, date('Y-m-d H:i:s') . " | Capture Start | Data: " . json_encode($data) . "\n", FILE_APPEND);

if (!$data || empty($data['order_id']) || empty($data['paypal_order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id or paypal_order_id']);
    exit;
}

$localOrderId = (int)$data['order_id'];
$paypalOrderId = $data['paypal_order_id'];

try {
    // 1. Get PayPal access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $tokenResponse = curl_exec($ch);
    $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenHttpCode !== 200) {
        echo json_encode(['success' => false, 'message' => 'Failed to authenticate with PayPal']);
        exit;
    }

    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'];

    // Log capture attempt
    $capLog = date('Y-m-d H:i:s') . " | Capture Attempt | Order: $localOrderId | PayPal: $paypalOrderId\n";
    @file_put_contents($logFile, $capLog, FILE_APPEND);

    // 2. Capture the PayPal order
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders/$paypalOrderId/capture");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ]);

    $captureResponse = curl_exec($ch);
    $captureHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $captureData = json_decode($captureResponse, true);

    // Log capture response
    $resLog = date('Y-m-d H:i:s') . " | Capture Result | HTTP: $captureHttpCode | Status: " . ($captureData['status'] ?? 'N/A') . "\n";
    @file_put_contents($logFile, $resLog, FILE_APPEND);

    if ($captureHttpCode >= 200 && $captureHttpCode < 300 && isset($captureData['status'])) {
        $paymentStatus = $captureData['status']; // COMPLETED

        if (strtoupper($paymentStatus) === 'COMPLETED') {
            // 3. Update order status to 'Preparing' (matches trader dashboard expectations)
            // Note: 'Completed' might violate DB check constraints in some schemas
            $sql = "UPDATE HUDDER_ORDER SET status = 'Preparing' WHERE order_id = :order_id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':order_id', $localOrderId);
            $execOk = @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            
            if (!$execOk) {
                $e = oci_error($stmt);
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " | SQL Error (HUDDER_ORDER) | " . $e['message'] . "\n", FILE_APPEND);
                oci_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Database Error (Order): ' . $e['message']]);
                exit;
            }
            oci_free_statement($stmt);

            // 4. Update payment record
            $paySql = "UPDATE PAYMENT SET status = 'Completed', method = 'PayPal' WHERE order_id = :order_id";
            $payStmt = oci_parse($conn, $paySql);
            oci_bind_by_name($payStmt, ':order_id', $localOrderId);
            $payOk = @oci_execute($payStmt, OCI_NO_AUTO_COMMIT);
            
            if (!$payOk) {
                $e = oci_error($payStmt);
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " | SQL Error (PAYMENT) | " . $e['message'] . "\n", FILE_APPEND);
                oci_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Database Error (Payment): ' . $e['message']]);
                exit;
            }
            oci_free_statement($payStmt);

            // 5. Clear cart for this user
            // We use subqueries to find the right cart based on the order
            $clearItems = oci_parse($conn, "DELETE FROM CART_ITEM WHERE cart_id IN (SELECT cart_id FROM CART WHERE user_id = (SELECT user_id FROM HUDDER_ORDER WHERE order_id = :order_id))");
            oci_bind_by_name($clearItems, ':order_id', $localOrderId);
            @oci_execute($clearItems, OCI_NO_AUTO_COMMIT);
            oci_free_statement($clearItems);

            $clearCart = oci_parse($conn, "DELETE FROM CART WHERE user_id = (SELECT user_id FROM HUDDER_ORDER WHERE order_id = :order_id)");
            oci_bind_by_name($clearCart, ':order_id', $localOrderId);
            @oci_execute($clearCart, OCI_NO_AUTO_COMMIT);
            oci_free_statement($clearCart);

            // Final Commit
            oci_commit($conn);

            // 6. Send individual trader receipts if multi-trader order
            $traderSql = "SELECT u.email, u.firstname AS trader_name, p.name AS product_name, op.quantity, op.unit_price,
                                 cs.slot_date, cs.slot_time
                          FROM ORDER_PRODUCT op
                          JOIN PRODUCT p ON op.product_id = p.product_id
                          JOIN SHOP s ON p.shop_id = s.shop_id
                          JOIN HUDDER_USER u ON s.user_id = u.user_id
                          JOIN HUDDER_ORDER o ON op.order_id = o.order_id
                          LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
                          WHERE op.order_id = :order_id";
            $traderStmt = oci_parse($conn, $traderSql);
            oci_bind_by_name($traderStmt, ':order_id', $localOrderId);
            oci_execute($traderStmt);

            $traderItems = [];
            while ($tRow = oci_fetch_assoc($traderStmt)) {
                $email = $tRow['EMAIL'];
                if (!isset($traderItems[$email])) {
                    $traderItems[$email] = [
                        'name' => $tRow['TRADER_NAME'],
                        'slot_date' => $tRow['SLOT_DATE'] ? date('l, jS F Y', strtotime($tRow['SLOT_DATE'])) : 'TBD',
                        'slot_time' => $tRow['SLOT_TIME'] ?: '10:00 - 13:00',
                        'items' => []
                    ];
                }
                $traderItems[$email]['items'][] = [
                    'NAME' => $tRow['PRODUCT_NAME'],
                    'QUANTITY' => $tRow['QUANTITY'],
                    'UNIT_PRICE' => $tRow['UNIT_PRICE']
                ];
            }
            oci_free_statement($traderStmt);

            require_once '../../config/mailer.php';
            foreach ($traderItems as $email => $data) {
                huddershub_send_trader_order_notification(
                    $email,
                    $data['name'],
                    $localOrderId,
                    $data['items'],
                    $data['slot_date'],
                    $data['slot_time']
                );
            }

            // 7. Send customer confirmation
            @file_get_contents(huddershub_base_url() . "/api/email/send-confirmation.php?order_id=" . $localOrderId);

            echo json_encode([
                'success' => true,
                'message' => 'Payment completed successfully',
                'paypal_id' => $captureData['id'] ?? '',
                'status' => $paymentStatus,
                'order_id' => $localOrderId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'PayPal payment status is ' . $paymentStatus]);
        }
    } else {
        $errorMsg = $captureData['message'] ?? 'Failed to capture PayPal payment';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
