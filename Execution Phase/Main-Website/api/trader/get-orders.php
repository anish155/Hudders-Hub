<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Get shop
$sql_shop = "SELECT shop_id, name AS shop_name FROM SHOP WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$shop = oci_fetch_assoc($stmt);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}

$shop_id = (int)$shop['SHOP_ID'];

// Get all orders for this shop
$sql = "
    SELECT
        o.order_id,
        o.order_date,
        o.order_time,
        o.status,
        u.firstname || ' ' || u.lastname AS customer_name,
        COUNT(op.product_id)             AS item_count,
        SUM(op.quantity * op.unit_price) AS order_total
    FROM HUDDER_ORDER o
    JOIN HUDDER_USER u    ON u.user_id    = o.user_id
    JOIN ORDER_PRODUCT op ON op.order_id  = o.order_id
    JOIN PRODUCT p        ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
    GROUP BY o.order_id, o.order_date, o.order_time, o.status,
             u.firstname, u.lastname
    ORDER BY o.order_date DESC, o.order_id DESC
";
$stmt_o = oci_parse($conn, $sql);
oci_bind_by_name($stmt_o, ':shop_id', $shop_id);
oci_execute($stmt_o);

$orders = [];
while ($row = oci_fetch_assoc($stmt_o)) {
    $orders[] = [
        'id'       => '#ORD-' . str_pad($row['ORDER_ID'], 3, '0', STR_PAD_LEFT),
        'date'     => date('Y-m-d', strtotime($row['ORDER_DATE'])),
        'time'     => $row['ORDER_TIME'],
        'status'   => $row['STATUS'],
        'customer' => $row['CUSTOMER_NAME'],
        'items'    => (int)$row['ITEM_COUNT'],
        'total'    => (float)$row['ORDER_TOTAL'],
    ];
}

echo json_encode([
    'success'   => true,
    'shop_name' => $shop['SHOP_NAME'],
    'orders'    => $orders,
]);

oci_close($conn);
?>