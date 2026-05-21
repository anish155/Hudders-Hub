<?php
/**
 * PayPal Sandbox - Create Order
 * POST /api/paypal/create-order.php
 *
 * Creates a PayPal order in the sandbox environment.
 * Uses PayPal REST API v2.
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

if (!$data || empty($data['order_id']) || empty($data['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id or amount']);
    exit;
}

$orderId = (int)($data['order_id'] ?? 0);
$passedAmount = (float)($data['amount'] ?? 0);
$currency = $data['currency'] ?? 'GBP';

if (!$orderId || !$passedAmount) {
    echo json_encode(['success' => false, 'message' => 'Invalid order_id or amount']);
    exit;
}

try {
    // Fetch order items from database for better PayPal breakdown
    $items = [];
    $itemTotal = 0;
    $sql = "SELECT op.quantity, op.unit_price, p.name 
            FROM ORDER_PRODUCT op 
            JOIN PRODUCT p ON op.product_id = p.product_id 
            WHERE op.order_id = :order_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':order_id', $orderId);
    oci_execute($stmt);
    
    while ($row = oci_fetch_assoc($stmt)) {
        $price = (float)$row['UNIT_PRICE'];
        $qty = (int)$row['QUANTITY'];
        $items[] = [
            'name' => substr($row['NAME'], 0, 120),
            'unit_amount' => [
                'currency_code' => $currency,
                'value' => number_format($price, 2, '.', '')
            ],
            'quantity' => (string)$qty
        ];
        $itemTotal += ($price * $qty);
    }
    oci_free_statement($stmt);

    $serviceFee = 2.40;
    
    // Calculate totals carefully
    $itemTotalStr = number_format($itemTotal, 2, '.', '');
    $feeStr = number_format($serviceFee, 2, '.', '');
    $finalAmountStr = number_format($itemTotal + $serviceFee, 2, '.', '');

    // Log the calculation for debugging
    $logFile = __DIR__ . '/../../paypal-error.log';
    $calcLog = date('Y-m-d H:i:s') . " | Create | Order: $orderId | Items: $itemTotalStr | Fee: $feeStr | Total: $finalAmountStr | Passed: " . number_format($passedAmount, 2, '.', '') . "\n";
    @file_put_contents($logFile, $calcLog, FILE_APPEND);

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

    $tokenData = json_decode($tokenResponse, true);

    if (!$tokenData || !isset($tokenData['access_token'])) {
        $logFile = __DIR__ . '/../../paypal-error.log';
        $logEntry = date('Y-m-d H:i:s') . " | Token Error | HTTP: $tokenHttpCode | Response: $tokenResponse\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Failed to authenticate with PayPal (check credentials)']);
        exit;
    }

    $accessToken = $tokenData['access_token'];

    // 2. Create PayPal order (Simplified payload to avoid common sandbox validation errors)
    $orderPayload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'HUDDERS-' . $orderId,
            'description'  => 'HuddersHub Order #' . $orderId,
            'amount' => [
                'currency_code' => $currency,
                'value' => $finalAmountStr
            ]
        ]],
        'application_context' => [
            'brand_name' => 'HuddersHub',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
            'return_url' => RETURN_URL . '?order_id=' . $orderId,
            'cancel_url' => CANCEL_URL . '&order_id=' . $orderId,
        ],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
        'Prefer: return=representation',
    ]);
    $orderResponse = curl_exec($ch);
    $orderHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $orderData = json_decode($orderResponse, true);

    if ($orderHttpCode >= 200 && $orderHttpCode < 300 && isset($orderData['id'])) {
        echo json_encode([
            'success' => true,
            'paypal_order_id' => $orderData['id'],
            'status' => $orderData['status'],
        ]);
    } else {
        $logFile = __DIR__ . '/../../paypal-error.log';
        $logEntry = date('Y-m-d H:i:s') . " | Order Creation Error | HTTP: $orderHttpCode | Response: $orderResponse\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        $errorMsg = isset($orderData['message']) ? $orderData['message'] : 'Failed to create PayPal order';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
