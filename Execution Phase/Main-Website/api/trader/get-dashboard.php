<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// 1. Get trader's name, shop name, trader status
$sql_user = "
    SELECT u.firstname, u.lastname, s.name AS shop_name, t.status AS trader_status, s.shop_id
    FROM HUDDER_USER u
    JOIN TRADER t ON t.user_id = u.user_id
    JOIN SHOP s ON s.user_id = u.user_id
    WHERE u.user_id = :user_id
";
$stmt = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$user = oci_fetch_assoc($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Trader or shop not found']);
    exit;
}

$shop_id = (int)$user['SHOP_ID'];

// 2. Total orders for this trader's shop (all time)
$sql_total = "
    SELECT COUNT(DISTINCT o.order_id) AS total_orders
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
";
$stmt_total = oci_parse($conn, $sql_total);
oci_bind_by_name($stmt_total, ':shop_id', $shop_id);
oci_execute($stmt_total);
$row_total = oci_fetch_assoc($stmt_total);
$total_orders = (int)($row_total['TOTAL_ORDERS'] ?? 0);

// 3. Orders today
$sql_today = "
    SELECT COUNT(DISTINCT o.order_id) AS orders_today
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
      AND TRUNC(o.order_date) = TRUNC(SYSDATE)
";
$stmt_today = oci_parse($conn, $sql_today);
oci_bind_by_name($stmt_today, ':shop_id', $shop_id);
oci_execute($stmt_today);
$row_today = oci_fetch_assoc($stmt_today);
$orders_today = (int)($row_today['ORDERS_TODAY'] ?? 0);

// 4. Weekly revenue (Monday to now)
$sql_week = "
    SELECT NVL(SUM(op.quantity * op.unit_price), 0) AS weekly_revenue
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
      AND o.order_date >= TRUNC(SYSDATE, 'IW')
";
$stmt_week = oci_parse($conn, $sql_week);
oci_bind_by_name($stmt_week, ':shop_id', $shop_id);
oci_execute($stmt_week);
$row_week = oci_fetch_assoc($stmt_week);
$weekly_revenue = (float)($row_week['WEEKLY_REVENUE'] ?? 0);

// 5. Total active products
$sql_products = "
    SELECT COUNT(*) AS total_products
    FROM PRODUCT
    WHERE shop_id = :shop_id
      AND status = 'Active'
";
$stmt_products = oci_parse($conn, $sql_products);
oci_bind_by_name($stmt_products, ':shop_id', $shop_id);
oci_execute($stmt_products);
$row_products = oci_fetch_assoc($stmt_products);
$total_products = (int)($row_products['TOTAL_PRODUCTS'] ?? 0);

// 6. Today's orders detail
$sql_orders = "
    SELECT
        o.order_id,
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
      AND TRUNC(o.order_date) = TRUNC(SYSDATE)
    GROUP BY o.order_id, o.order_time, o.status, u.firstname, u.lastname
    ORDER BY o.order_id
";
$stmt_orders = oci_parse($conn, $sql_orders);
oci_bind_by_name($stmt_orders, ':shop_id', $shop_id);
oci_execute($stmt_orders);

$orders = [];
while ($row = oci_fetch_assoc($stmt_orders)) {
    $orders[] = [
        'id'       => '#ORD-' . str_pad($row['ORDER_ID'], 3, '0', STR_PAD_LEFT),
        'time'     => $row['ORDER_TIME'],
        'status'   => $row['STATUS'],
        'customer' => $row['CUSTOMER_NAME'],
        'items'    => (int)$row['ITEM_COUNT'],
        'total'    => (float)$row['ORDER_TOTAL'],
    ];
}

echo json_encode([
    'success'       => true,
    'firstname'     => $user['FIRSTNAME'],
    'lastname'      => $user['LASTNAME'],
    'shop_name'     => $user['SHOP_NAME'],
    'trader_status' => $user['TRADER_STATUS'],
    'stats' => [
        'total_orders'   => $total_orders,
        'orders_today'   => $orders_today,
        'weekly_revenue' => $weekly_revenue,
        'total_products' => $total_products,
    ],
    'orders' => $orders,
]);

oci_close($conn);