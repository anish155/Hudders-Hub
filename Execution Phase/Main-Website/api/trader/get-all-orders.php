<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$sql_shop = "SELECT shop_id FROM SHOP WHERE user_id = :user_id";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':user_id', $user_id);
oci_execute($stmt_shop);
$shop = oci_fetch_assoc($stmt_shop);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}

$shop_id = (int)$shop['SHOP_ID'];

$sql = "
    SELECT o.order_id, o.order_date, o.order_time, o.status,
           u.firstname || ' ' || u.lastname AS customer_name,
           u.phone_number, u.address,
           SUM(op.quantity * op.unit_price) AS total,
           cs.slot_date, cs.slot_time, cs.location
    FROM HUDDER_ORDER o
    JOIN HUDDER_USER u ON o.user_id = u.user_id
    JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
    JOIN PRODUCT p ON op.product_id = p.product_id
    LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
    WHERE p.shop_id = :shop_id
";

if ($status && $status !== 'all') {
    $sql .= " AND o.status = :status";
}

$sql .= " GROUP BY o.order_id, o.order_date, o.order_time, o.status,
          u.firstname, u.lastname, u.phone_number, u.address,
          cs.slot_date, cs.slot_time, cs.location
          ORDER BY o.order_date DESC, o.order_time DESC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':shop_id', $shop_id);

if ($status && $status !== 'all') {
    oci_bind_by_name($stmt, ':status', $status);
}

oci_execute($stmt);

$orders = [];
while ($row = oci_fetch_assoc($stmt)) {
    $orders[] = [
        'id' => '#ORD-' . str_pad($row['ORDER_ID'], 3, '0', STR_PAD_LEFT),
        'order_id' => (int)$row['ORDER_ID'],
        'date' => $row['ORDER_DATE'],
        'time' => $row['ORDER_TIME'],
        'status' => $row['STATUS'],
        'customer' => $row['CUSTOMER_NAME'],
        'phone' => $row['PHONE_NUMBER'],
        'address' => $row['ADDRESS'],
        'total' => (float)$row['TOTAL'],
        'collection' => [
            'date' => $row['SLOT_DATE'],
            'time' => $row['SLOT_TIME'],
            'location' => $row['LOCATION']
        ]
    ];
}

echo json_encode(['success' => true, 'orders' => $orders]);

oci_close($conn);