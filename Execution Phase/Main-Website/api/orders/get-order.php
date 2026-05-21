<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$sql = "SELECT o.order_id, o.order_date, o.order_time, o.status,
               cs.slot_date, cs.slot_time, cs.location,
               p.payment_id, p.amount, p.method AS payment_method, p.status AS payment_status
        FROM HUDDER_ORDER o
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        LEFT JOIN PAYMENT p ON o.order_id = p.order_id
        WHERE o.order_id = :order_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':order_id', $order_id);
oci_execute($stmt);

$order = oci_fetch_assoc($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$productsSql = "SELECT op.product_id, p.name, op.quantity, op.unit_price,
                       (op.quantity * op.unit_price) AS subtotal,
                       s.name AS shop_name, s.shop_type
                FROM ORDER_PRODUCT op
                JOIN PRODUCT p ON op.product_id = p.product_id
                JOIN SHOP s ON p.shop_id = s.shop_id
                WHERE op.order_id = :order_id";
$productsStmt = oci_parse($conn, $productsSql);
oci_bind_by_name($productsStmt, ':order_id', $order_id);
oci_execute($productsStmt);

$products = [];
while ($product = oci_fetch_assoc($productsStmt)) {
    $products[] = $product;
}
oci_free_statement($productsStmt);

$order['PRODUCTS'] = $products;

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $order]);
