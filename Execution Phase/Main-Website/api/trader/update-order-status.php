<?php
// Disable error reporting to prevent warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

/**
 * Update Order Status API
 * POST /api/trader/update-order-status.php
 *
 *  Valid statuses: Pending, Preparing, Ready, Collected, Cancelled
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true);
$user_id   = isset($data['user_id'])  ? (int)$data['user_id']  : 0;
$order_id  = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$new_status = trim($data['status'] ?? '');

if (!$user_id || !$order_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Must match DB CHECK constraint exactly
// Traders are restricted from marking orders as 'Collected' (Done via Staff/Arduino bridge)
$valid_statuses = ['Pending', 'Preparing', 'Ready', 'Cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false,
        'message' => 'Invalid status or permission denied. Allowed: ' . implode(', ', $valid_statuses)]);
    exit;
}

// Get trader's shop
$ss   = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
oci_bind_by_name($ss, ':user_id', $user_id);
oci_execute($ss);
$shop = oci_fetch_assoc($ss);
oci_free_statement($ss);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found for this trader']);
    exit;
}

$shop_id = (int)$shop['SHOP_ID'];

// Verify this order belongs to trader's shop
$sv = oci_parse($conn, "
    SELECT o.order_id FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
    JOIN PRODUCT p ON op.product_id = p.product_id
    WHERE o.order_id = :oid AND p.shop_id = :sid
    FETCH FIRST 1 ROWS ONLY
");
oci_bind_by_name($sv, ':oid', $order_id);
oci_bind_by_name($sv, ':sid', $shop_id);
oci_execute($sv);
$found = oci_fetch_assoc($sv);
oci_free_statement($sv);

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Order not found for your shop']);
    exit;
}

// Update status
$su = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = :status WHERE order_id = :oid");
oci_bind_by_name($su, ':status', $new_status);
oci_bind_by_name($su, ':oid',    $order_id);

if (oci_execute($su, OCI_COMMIT_ON_SUCCESS)) {
    echo json_encode(['success' => true,
        'message' => 'Order status updated to ' . $new_status]);
} else {
    $e = oci_error($su);
    echo json_encode(['success' => false,
        'message' => $e['message'] ?? 'Update failed']);
}

oci_free_statement($su);
oci_close($conn);