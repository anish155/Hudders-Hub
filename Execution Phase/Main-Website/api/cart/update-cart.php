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

try {
    // 1. Check product stock
    $stockStmt = oci_parse($conn, 'SELECT p.stock, p.name FROM PRODUCT p JOIN CART_ITEM ci ON p.product_id = ci.product_id WHERE ci.cart_item_id = :cart_item_id');
    oci_bind_by_name($stockStmt, ':cart_item_id', $cartItemId);
    oci_execute($stockStmt);
    $stockRow = oci_fetch_assoc($stockStmt);
    oci_free_statement($stockStmt);

    if ($stockRow && (int)$stockRow['STOCK'] < $quantity) {
        echo json_encode(['success' => false, 'error' => 'Only ' . $stockRow['STOCK'] . ' units of ' . $stockRow['NAME'] . ' are available.']);
        exit;
    }

    // 2. Check total cart quantity
    $countStmt = oci_parse($conn, 'SELECT SUM(quantity) as total_qty FROM CART_ITEM WHERE cart_id IN (SELECT cart_id FROM CART WHERE user_id = :user_id) AND cart_item_id != :cart_item_id');
    oci_bind_by_name($countStmt, ':user_id', $userId);
    oci_bind_by_name($countStmt, ':cart_item_id', $cartItemId);
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $otherItemsQty = (int)($countRow['TOTAL_QTY'] ?? 0);
    oci_free_statement($countStmt);

    if ($otherItemsQty + $quantity > 20) {
        echo json_encode(['success' => false, 'error' => 'Cart limit reached. You can only have up to 20 products in your cart.']);
        exit;
    }

    $stmt = oci_parse($conn, 'UPDATE CART_ITEM SET quantity = :quantity WHERE cart_item_id = :cart_item_id AND cart_id IN (SELECT cart_id FROM CART WHERE user_id = :user_id)');
    oci_bind_by_name($stmt, ':quantity', $quantity);
    oci_bind_by_name($stmt, ':cart_item_id', $cartItemId);
    oci_bind_by_name($stmt, ':user_id', $userId);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception($e['message']);
    }

    $rowsUpdated = oci_num_rows($stmt);
    if ($rowsUpdated === 0) {
        throw new Exception('Cart item not found');
    }

    oci_commit($conn);
    oci_free_statement($stmt);
    echo json_encode(['success' => true, 'message' => 'Cart item updated']);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

oci_close($conn);
exit;
?>
