<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();
$conn = getDB();
$userId = (int) $_SESSION['user_id'];

$sql = "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.name, p.price, p.allergen_info,
			   (ci.quantity * p.price) AS subtotal
		FROM CART c
		JOIN CART_ITEM ci ON c.cart_id = ci.cart_id
		JOIN PRODUCT p ON ci.product_id = p.product_id
		WHERE c.user_id = :user_id
		ORDER BY ci.cart_item_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $userId);

if (!oci_execute($stmt)) {
	$e = oci_error($stmt);
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e['message']]);
	oci_free_statement($stmt);
	oci_close($conn);
	exit;
}

$items = [];
$total = 0;

while ($row = oci_fetch_assoc($stmt)) {
	$items[] = $row;
	$total += (float) ($row['SUBTOTAL'] ?? 0);
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $items, 'total' => $total]);
?>
