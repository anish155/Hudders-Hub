<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Get shop_id for this trader
$sql_shop = "SELECT s.shop_id, s.name AS shop_name FROM SHOP s WHERE s.user_id = :user_id";
$stmt = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$shop = oci_fetch_assoc($stmt);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}

$shop_id = (int)$shop['SHOP_ID'];

// Get all products for this shop
$sql = "
    SELECT p.product_id, p.name, p.price, p.stock, p.status,
           pc.category_name
    FROM PRODUCT p
    LEFT JOIN PRODUCT_CATEGORY pc ON pc.category_id = p.category_id
    WHERE p.shop_id = :shop_id
    ORDER BY p.product_id
";
$stmt_p = oci_parse($conn, $sql);
oci_bind_by_name($stmt_p, ':shop_id', $shop_id);
oci_execute($stmt_p);

$products = [];
while ($row = oci_fetch_assoc($stmt_p)) {
    $products[] = [
        'id'       => (int)$row['PRODUCT_ID'],
        'name'     => $row['NAME'],
        'category' => $row['CATEGORY_NAME'] ?? 'Uncategorised',
        'price'    => (float)$row['PRICE'],
        'stock'    => (int)$row['STOCK'],
        'status'   => $row['STATUS'],
    ];
}

echo json_encode([
    'success'   => true,
    'shop_name' => $shop['SHOP_NAME'],
    'products'  => $products,
]);

oci_close($conn);