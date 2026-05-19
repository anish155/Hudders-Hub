<?php
/**
 * Weekly Finance Report API
 * GET /api/trader/get-weekly-report.php?user_id=N&week_start=YYYY-MM-DD
 * Revenue counted only for 'Completed' orders (matching DB status constraint)
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id    = isset($_GET['user_id'])    ? (int)$_GET['user_id']      : 0;
$week_start = trim($_GET['week_start'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

if (!$week_start || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $week_start)) {
    $week_start = date('Y-m-d', strtotime('last wednesday'));
}

// Get shop
$ss   = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
oci_bind_by_name($ss, ':user_id', $user_id);
oci_execute($ss);
$shop = oci_fetch_assoc($ss);
oci_free_statement($ss);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}
$shop_id  = (int)$shop['SHOP_ID'];
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Daily breakdown
$sql_daily = "
    SELECT TO_CHAR(o.order_date, 'YYYY-MM-DD') AS day_date,
           COUNT(DISTINCT o.order_id) AS total_orders,
           SUM(CASE WHEN o.status = 'Completed' THEN op.quantity * op.unit_price ELSE 0 END) AS revenue,
           COUNT(DISTINCT CASE WHEN o.status = 'Completed' THEN o.order_id END) AS collected
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
    JOIN PRODUCT p ON op.product_id = p.product_id
    WHERE p.shop_id = :shop_id
      AND TRUNC(o.order_date) >= TO_DATE(:ws, 'YYYY-MM-DD')
      AND TRUNC(o.order_date) <= TO_DATE(:we, 'YYYY-MM-DD')
    GROUP BY TO_CHAR(o.order_date, 'YYYY-MM-DD')
    ORDER BY day_date
";
$stmt = oci_parse($conn, $sql_daily);
oci_bind_by_name($stmt, ':shop_id', $shop_id);
oci_bind_by_name($stmt, ':ws',      $week_start);
oci_bind_by_name($stmt, ':we',      $week_end);
oci_execute($stmt);

$daily          = [];
$total_orders   = 0;
$total_revenue  = 0;
$total_collected= 0;

while ($row = oci_fetch_assoc($stmt)) {
    $orders    = (int)$row['TOTAL_ORDERS'];
    $rev       = (float)$row['REVENUE'];
    $collected = (int)$row['COLLECTED'];

    $daily[]          = ['date' => $row['DAY_DATE'], 'orders' => $orders, 'revenue' => $rev, 'collected' => $collected];
    $total_orders    += $orders;
    $total_revenue   += $rev;
    $total_collected += $collected;
}
oci_free_statement($stmt);

// Product revenue breakdown (Completed orders only)
$sql_prod = "
    SELECT p.name,
           SUM(op.quantity) AS quantity,
           SUM(op.quantity * op.unit_price) AS income
    FROM ORDER_PRODUCT op
    JOIN PRODUCT p ON op.product_id = p.product_id
    JOIN HUDDER_ORDER o ON o.order_id = op.order_id
    WHERE p.shop_id = :shop_id
      AND TRUNC(o.order_date) >= TO_DATE(:ws, 'YYYY-MM-DD')
      AND TRUNC(o.order_date) <= TO_DATE(:we, 'YYYY-MM-DD')
      AND o.status = 'Completed'
    GROUP BY p.name
    ORDER BY income DESC
";
$stmt2 = oci_parse($conn, $sql_prod);
oci_bind_by_name($stmt2, ':shop_id', $shop_id);
oci_bind_by_name($stmt2, ':ws',      $week_start);
oci_bind_by_name($stmt2, ':we',      $week_end);
oci_execute($stmt2);

$products = [];
while ($row = oci_fetch_assoc($stmt2)) {
    $products[] = [
        'name'     => $row['NAME'],
        'quantity' => (int)$row['QUANTITY'],
        'income'   => (float)$row['INCOME']
    ];
}
oci_free_statement($stmt2);
oci_close($conn);

echo json_encode([
    'success'  => true,
    'week_start'=> $week_start,
    'week_end'  => $week_end,
    'summary'  => [
        'total_revenue'  => $total_revenue,
        'total_orders'   => $total_orders,
        'total_collected'=> $total_collected
    ],
    'daily'    => $daily,
    'products' => $products
]);
?>