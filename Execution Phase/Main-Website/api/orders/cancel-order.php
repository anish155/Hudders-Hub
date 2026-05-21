<?php
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../config/session.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server configuration error']);
    exit;
}

requireLogin();

$conn = getDB();
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$order_id = $data['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];

$checkSql = "SELECT status FROM HUDDER_ORDER WHERE order_id = :oid AND user_id = :user_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':oid', $order_id);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_execute($checkStmt);
$order = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

if ($order['STATUS'] !== 'Pending') {
    echo json_encode(['success' => false, 'error' => 'Only pending orders can be cancelled']);
    exit;
}

$orderSql = "SELECT o.order_id, o.order_date, o.status, o.slot_id,
                    u.firstname, u.email,
                    cs.slot_date, cs.slot_time, cs.location,
                    p.amount, p.method, p.status AS payment_status
             FROM HUDDER_ORDER o
             JOIN HUDDER_USER u ON o.user_id = u.user_id
             LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
             LEFT JOIN PAYMENT p ON o.order_id = p.order_id
             WHERE o.order_id = :order_id";
$orderStmt = oci_parse($conn, $orderSql);
oci_bind_by_name($orderStmt, ':order_id', $order_id);
oci_execute($orderStmt);
$orderDetails = oci_fetch_assoc($orderStmt);
oci_free_statement($orderStmt);

$prodSql = "SELECT op.product_id, p.name, op.quantity, op.unit_price
             FROM ORDER_PRODUCT op
             JOIN PRODUCT p ON op.product_id = p.product_id
             WHERE op.order_id = :order_id";
$prodStmt = oci_parse($conn, $prodSql);
oci_bind_by_name($prodStmt, ':order_id', $order_id);
oci_execute($prodStmt);
$items = [];
while ($prod = oci_fetch_assoc($prodStmt)) {
    $items[] = $prod;
}
oci_free_statement($prodStmt);

$updateSql = "UPDATE HUDDER_ORDER SET status = 'Cancelled' WHERE order_id = :oid";
$updateStmt = oci_parse($conn, $updateSql);
oci_bind_by_name($updateStmt, ':oid', $order_id);

if (oci_execute($updateStmt, OCI_NO_AUTO_COMMIT)) {
    $paySql = "UPDATE PAYMENT SET status = 'Refunded' WHERE order_id = :oid";
    $payStmt = oci_parse($conn, $paySql);
    oci_bind_by_name($payStmt, ':oid', $order_id);
    oci_execute($payStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($payStmt);

    oci_commit($conn);

    $emailSent = false;
    try {
        require_once '../../config/mailer.php';

        if (function_exists('huddershub_send_order_cancellation')) {
            $slotDate = $orderDetails['SLOT_DATE'] ? date('l, jS F Y', strtotime($orderDetails['SLOT_DATE'])) : 'TBD';
            $slotTime = $orderDetails['SLOT_TIME'] ?: '10:00 - 13:00';

            huddershub_send_order_cancellation(
                $orderDetails['EMAIL'],
                $orderDetails['FIRSTNAME'],
                $order_id,
                $items,
                $orderDetails['AMOUNT'],
                $slotDate,
                $slotTime
            );
            $emailSent = true;
        }
    } catch (Exception $e) {
        error_log('Cancellation email failed for order #' . $order_id . ': ' . $e->getMessage());
    }

    $response = ['success' => true, 'message' => 'Order cancelled successfully'];
    if ($emailSent) {
        $response['email_sent'] = true;
    }
    echo json_encode($response);
} else {
    $e = oci_error($updateStmt);
    oci_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($updateStmt);
oci_close($conn);
