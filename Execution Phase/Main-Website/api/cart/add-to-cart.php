<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();
$conn = getDB();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['product_id']) || empty($input['quantity'])) {
	http_response_code(400);
	echo json_encode(['success' => false, 'error' => 'product_id and quantity are required']);
	exit;
}

$userId = (int) $_SESSION['user_id'];
$productId = (int) $input['product_id'];
$quantity = max(1, (int) $input['quantity']);

try {
	$cartId = null;
	$cartStmt = oci_parse($conn, 'SELECT cart_id FROM CART WHERE user_id = :user_id');
	oci_bind_by_name($cartStmt, ':user_id', $userId);
	oci_execute($cartStmt);
	$cartRow = oci_fetch_assoc($cartStmt);
	oci_free_statement($cartStmt);

	if ($cartRow) {
		$cartId = (int) $cartRow['CART_ID'];
	} else {
		$insertCart = oci_parse($conn, 'INSERT INTO CART (created_at, user_id) VALUES (SYSDATE, :user_id)');
		oci_bind_by_name($insertCart, ':user_id', $userId);
		if (!oci_execute($insertCart, OCI_NO_AUTO_COMMIT)) {
			$e = oci_error($insertCart);
			throw new Exception($e['message']);
		}
		oci_free_statement($insertCart);

		$cartLookup = oci_parse($conn, 'SELECT cart_id FROM CART WHERE user_id = :user_id');
		oci_bind_by_name($cartLookup, ':user_id', $userId);
		oci_execute($cartLookup);
		$cartRow = oci_fetch_assoc($cartLookup);
		oci_free_statement($cartLookup);

		if (!$cartRow) {
			throw new Exception('Unable to create cart');
		}

		$cartId = (int) $cartRow['CART_ID'];
	}

	$itemStmt = oci_parse($conn, 'SELECT cart_item_id, quantity FROM CART_ITEM WHERE cart_id = :cart_id AND product_id = :product_id');
	oci_bind_by_name($itemStmt, ':cart_id', $cartId);
	oci_bind_by_name($itemStmt, ':product_id', $productId);
	oci_execute($itemStmt);
	$itemRow = oci_fetch_assoc($itemStmt);
	oci_free_statement($itemStmt);

	if ($itemRow) {
		$newQty = (int) $itemRow['QUANTITY'] + $quantity;
		$updateStmt = oci_parse($conn, 'UPDATE CART_ITEM SET quantity = :quantity WHERE cart_item_id = :cart_item_id');
		oci_bind_by_name($updateStmt, ':quantity', $newQty);
		oci_bind_by_name($updateStmt, ':cart_item_id', $itemRow['CART_ITEM_ID']);
		if (!oci_execute($updateStmt, OCI_NO_AUTO_COMMIT)) {
			$e = oci_error($updateStmt);
			throw new Exception($e['message']);
		}
		oci_free_statement($updateStmt);
	} else {
		$insertItem = oci_parse($conn, 'INSERT INTO CART_ITEM (quantity, cart_id, product_id) VALUES (:quantity, :cart_id, :product_id)');
		oci_bind_by_name($insertItem, ':quantity', $quantity);
		oci_bind_by_name($insertItem, ':cart_id', $cartId);
		oci_bind_by_name($insertItem, ':product_id', $productId);
		if (!oci_execute($insertItem, OCI_NO_AUTO_COMMIT)) {
			$e = oci_error($insertItem);
			throw new Exception($e['message']);
		}
		oci_free_statement($insertItem);
	}

	oci_commit($conn);
	// Return the new total quantity across all cart items for the user
	$sumStmt = oci_parse($conn, 'SELECT SUM(ci.quantity) AS total_qty FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :user_id');
	oci_bind_by_name($sumStmt, ':user_id', $userId);
	oci_execute($sumStmt);
	$sumRow = oci_fetch_assoc($sumStmt);
	$newCount = (int)($sumRow['TOTAL_QTY'] ?? 0);
	oci_free_statement($sumStmt);
	echo json_encode(['success' => true, 'message' => 'Cart updated', 'new_count' => $newCount]);
} catch (Exception $e) {
	oci_rollback($conn);
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

oci_close($conn);
?>
