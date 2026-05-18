<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$exclude     = isset($_GET['exclude'])      ? (int)$_GET['exclude']      : 0;
$limit       = isset($_GET['limit'])        ? min((int)$_GET['limit'], 20) : 8;

if (!$category_id) {
    echo json_encode(['success' => false, 'error' => 'category_id required']);
    exit;
}

$conn = getDB();

$sql = "SELECT p.product_id, p.name, p.price, p.stock, p.unit,
               s.name AS shop_name,
               NVL(d.discount_percent, 0) AS discount_percent
        FROM PRODUCT p
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN (
            SELECT product_id, MAX(discount_percent) AS discount_percent
            FROM DISCOUNT WHERE valid_until >= TRUNC(SYSDATE)
            GROUP BY product_id
        ) d ON d.product_id = p.product_id
        WHERE p.category_id = :cat_id
          AND p.product_id  != :exclude
          AND p.status = 'Active'
          AND ROWNUM <= :lim
        ORDER BY p.product_id DESC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':cat_id',  $category_id);
oci_bind_by_name($stmt, ':exclude', $exclude);
oci_bind_by_name($stmt, ':lim',     $limit);
oci_execute($stmt);

$products = [];
while ($row = oci_fetch_assoc($stmt)) {
    $row['PRICE']            = (float)$row['PRICE'];
    $row['STOCK']            = (int)$row['STOCK'];
    $row['DISCOUNT_PERCENT'] = (float)$row['DISCOUNT_PERCENT'];
    $products[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $products]);
