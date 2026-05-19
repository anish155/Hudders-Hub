<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$sql = "SELECT f.favourite_id, f.created_at,
               p.product_id, p.name, p.description, p.price, p.stock,
               p.allergen_info, p.status AS product_status,
               s.shop_id, s.name AS shop_name, s.location AS shop_location
        FROM FAVOURITE f
        JOIN PRODUCT p ON f.product_id = p.product_id
        JOIN SHOP s ON p.shop_id = s.shop_id
        WHERE f.user_id = :user_id
        ORDER BY f.created_at DESC";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);

$wishlist = [];
while ($row = oci_fetch_assoc($stmt)) {
    $wishlist[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $wishlist]);
