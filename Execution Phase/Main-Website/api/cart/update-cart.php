<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();
$conn = getDB();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cart_item_id']) || !isset($input['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'cart_item_id and quantity are required']);
    exit;
}

$cartItemId = (int) $input['cart_item_id'];
$quantity = (int) $input['quantity'];
$userId = (int) $_SESSION['user_id'];

if ($quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Quantity must be at least 1']);
    exit;
}

$stmt = oci_parse($conn, 'UPDATE CART_ITEM SET quantity = :quantity WHERE cart_item_id = :cart_item_id AND cart_id IN (SELECT cart_id FROM CART WHERE user_id = :user_id)');
oci_bind_by_name($stmt, ':quantity', $quantity);
oci_bind_by_name($stmt, ':cart_item_id', $cartItemId);
oci_bind_by_name($stmt, ':user_id', $userId);

if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

$rowsUpdated = oci_num_rows($stmt);

if ($rowsUpdated === 0) {
    oci_rollback($conn);
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Cart item not found']);
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

oci_commit($conn);
oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'message' => 'Cart item updated']);
?>
