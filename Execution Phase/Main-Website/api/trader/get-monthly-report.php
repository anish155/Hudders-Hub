<?php
/**
 * Monthly Sales Report API
 * GET /api/trader/get-monthly-report.php?user_id=N&month=YYYY-MM
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$month   = trim($_GET['month'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

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
$shop_id = (int)$shop['SHOP_ID'];

// Product sales (Completed orders only — revenue)
$sql = "
    SELECT p.name,
           COUNT(DISTINCT o.order_id) AS orders,
           SUM(op.quantity) AS quantity,
           SUM(op.quantity * op.unit_price) AS income
    FROM ORDER_PRODUCT op
    JOIN PRODUCT p ON op.product_id = p.product_id
    JOIN HUDDER_ORDER o ON o.order_id = op.order_id
    WHERE p.shop_id = :shop_id
      AND TRUNC(o.order_date) >= TO_DATE(:ms, 'YYYY-MM-DD')
      AND TRUNC(o.order_date) <= TO_DATE(:me, 'YYYY-MM-DD')
      AND o.status = 'Completed'
    GROUP BY p.name
    ORDER BY income DESC
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':shop_id', $shop_id);
oci_bind_by_name($stmt, ':ms',      $month_start);
oci_bind_by_name($stmt, ':me',      $month_end);
oci_execute($stmt);

$products     = [];
$total_income = 0;
$total_orders = 0;
$total_qty    = 0;

while ($row = oci_fetch_assoc($stmt)) {
    $income       = (float)$row['INCOME'];
    $orders       = (int)$row['ORDERS'];
    $qty          = (int)$row['QUANTITY'];
    $products[]   = ['name' => $row['NAME'], 'orders' => $orders, 'quantity' => $qty, 'income' => $income];
    $total_income += $income;
    $total_orders += $orders;
    $total_qty    += $qty;
}
oci_free_statement($stmt);
oci_close($conn);

echo json_encode([
    'success' => true,
    'month'   => $month,
    'summary' => [
        'total_income'  => $total_income,
        'total_orders'  => $total_orders,
        'products_sold' => $total_qty
    ],
    'products' => $products
]);
?>