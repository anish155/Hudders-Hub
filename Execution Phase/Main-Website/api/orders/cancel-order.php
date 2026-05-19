<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check order exists and belongs to user
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

// Update order status to Cancelled
$updateSql = "UPDATE HUDDER_ORDER SET status = 'Cancelled' WHERE order_id = :oid";
$updateStmt = oci_parse($conn, $updateSql);
oci_bind_by_name($updateStmt, ':oid', $order_id);

if (oci_execute($updateStmt, OCI_NO_AUTO_COMMIT)) {
    // Update payment status
    $paySql = "UPDATE PAYMENT SET status = 'Refunded' WHERE order_id = :oid";
    $payStmt = oci_parse($conn, $paySql);
    oci_bind_by_name($payStmt, ':oid', $order_id);
    oci_execute($payStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($payStmt);

    oci_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    $e = oci_error($updateStmt);
    oci_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($updateStmt);
oci_close($conn);
