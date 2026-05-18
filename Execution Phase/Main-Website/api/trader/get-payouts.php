<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$sql_shop = "SELECT shop_id FROM SHOP WHERE user_id = :user_id";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':user_id', $user_id);
oci_execute($stmt_shop);
$shop = oci_fetch_assoc($stmt_shop);
oci_free_statement($stmt_shop);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}
$shop_id = (int)$shop['SHOP_ID'];

$sql = "
    SELECT 
        TO_CHAR(TRUNC(o.order_date, 'IW'), 'YYYY-MM-DD') AS week_start,
        SUM(op.quantity * op.unit_price) AS amount,
        COUNT(DISTINCT o.order_id) AS order_count
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
    JOIN PRODUCT p ON op.product_id = p.product_id
    WHERE p.shop_id = :shop_id AND o.status = 'Completed'
    GROUP BY TRUNC(o.order_date, 'IW')
    ORDER BY week_start DESC
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':shop_id', $shop_id);
oci_execute($stmt);

$payouts = [];
while ($row = oci_fetch_assoc($stmt)) {
    $payouts[] = [
        'reference' => 'PAY-' . str_replace('-', '', $row['WEEK_START']),
        'date'      => $row['WEEK_START'],
        'orders'    => (int)$row['ORDER_COUNT'],
        'amount'    => (float)$row['AMOUNT'],
        'status'    => 'Completed'
    ];
}
oci_free_statement($stmt);
oci_close($conn);

echo json_encode([
    'success' => true,
    'payouts' => $payouts,
    'total_revenue' => array_sum(array_column($payouts, 'amount'))
]);
?>