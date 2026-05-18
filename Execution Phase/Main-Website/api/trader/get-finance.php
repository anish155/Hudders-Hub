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

// Get payments for orders containing this trader's products
$sql = "
    SELECT DISTINCT
        p.payment_id,
        p.payment_date,
        p.method,
        p.amount,
        p.status,
        o.order_id
    FROM PAYMENT p
    JOIN HUDDER_ORDER o      ON o.order_id   = p.order_id
    JOIN ORDER_PRODUCT op    ON op.order_id  = o.order_id
    JOIN PRODUCT pr          ON pr.product_id = op.product_id
    WHERE pr.shop_id = :shop_id
    ORDER BY p.payment_date DESC, p.payment_id DESC
";
$stmt_p = oci_parse($conn, $sql);
oci_bind_by_name($stmt_p, ':shop_id', $shop_id);
oci_execute($stmt_p);

$payouts = [];
while ($row = oci_fetch_assoc($stmt_p)) {
    $payouts[] = [
        'id'        => (int)$row['PAYMENT_ID'],
        'date'      => date('Y-m-d', strtotime($row['PAYMENT_DATE'])),
        'type'      => 'Payout',
        'reference' => 'PAY-' . str_pad($row['PAYMENT_ID'], 3, '0', STR_PAD_LEFT),
        'order_id'  => '#ORD-' . str_pad($row['ORDER_ID'], 3, '0', STR_PAD_LEFT),
        'method'    => $row['METHOD'],
        'amount'    => (float)$row['AMOUNT'],
        'status'    => $row['STATUS'],
    ];
}

// Summary stats
$total_revenue  = array_sum(array_column(array_filter($payouts, fn($p) => $p['status'] === 'Completed'), 'amount'));
$total_pending  = array_sum(array_column(array_filter($payouts, fn($p) => $p['status'] === 'Pending'), 'amount'));

echo json_encode([
    'success'       => true,
    'shop_name'     => $shop['SHOP_NAME'],
    'payouts'       => $payouts,
    'total_revenue' => $total_revenue,
    'total_pending' => $total_pending,
]);

oci_close($conn);
?>