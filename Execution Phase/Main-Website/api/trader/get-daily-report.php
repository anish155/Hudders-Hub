<?php
/**
 * Daily Report API
 * GET /api/trader/get-daily-report.php?user_id=N&date=YYYY-MM-DD
 *
 * COLLECTION_SLOT.slot_time values: '10:00-13:00','13:00-16:00','16:00-19:00'
 * HUDDER_ORDER status: Pending/Completed/Cancelled/Delivered
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date    = trim($_GET['date'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

if (!$date) {
    $date = date('Y-m-d');
}

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
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
$shop_id = (int)$shop['SHOP_ID'];

// Get orders for this date that include this shop's products
$sql = "
    SELECT DISTINCT o.order_id, o.order_time, o.status,
           u.firstname || ' ' || u.lastname AS customer_name,
           u.user_id AS customer_id,
           cs.slot_time
    FROM HUDDER_ORDER o
    JOIN HUDDER_USER u ON u.user_id = o.user_id
    LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
    WHERE TRUNC(o.order_date) = TO_DATE(:dt, 'YYYY-MM-DD')
      AND o.order_id IN (
          SELECT op.order_id FROM ORDER_PRODUCT op
          JOIN PRODUCT p ON op.product_id = p.product_id
          WHERE p.shop_id = :shop_id
      )
    ORDER BY cs.slot_time, o.order_id
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':dt',      $date);
oci_bind_by_name($stmt, ':shop_id', $shop_id);
oci_execute($stmt);

$slots        = ['10:00-13:00' => [], '13:00-16:00' => [], '16:00-19:00' => []];
$total_orders  = 0;
$total_revenue = 0;
$order_num     = 1;

while ($row = oci_fetch_assoc($stmt)) {
    $order_id = (int)$row['ORDER_ID'];

    // Products for this shop in this order
    $sp = oci_parse($conn, "
        SELECT p.name, op.quantity, op.unit_price
        FROM ORDER_PRODUCT op
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE op.order_id = :oid AND p.shop_id = :sid
    ");
    oci_bind_by_name($sp, ':oid', $order_id);
    oci_bind_by_name($sp, ':sid', $shop_id);
    oci_execute($sp);

    $products     = [];
    $order_total  = 0;
    while ($p = oci_fetch_assoc($sp)) {
        $products[]  = ['name' => $p['NAME'], 'quantity' => (int)$p['QUANTITY']];
        $order_total += (int)$p['QUANTITY'] * (float)$p['UNIT_PRICE'];
    }
    oci_free_statement($sp);

    $slot_key = $row['SLOT_TIME'] ?? '10:00-13:00';
    if (!isset($slots[$slot_key])) {
        $slot_key = '10:00-13:00'; // default bucket
    }

    $order_entry = [
        'order_num'       => '#ORD-' . str_pad($order_num, 3, '0', STR_PAD_LEFT),
        'customer_name'   => $row['CUSTOMER_NAME'],
        'customer_id'     => (int)$row['CUSTOMER_ID'],
        'products'        => $products,
        'total'           => $order_total,
        'collection_slot' => $slot_key,
        'status'          => $row['STATUS']
    ];

    $slots[$slot_key][] = $order_entry;
    $total_orders++;
    $total_revenue += $order_total;
    $order_num++;
}
oci_free_statement($stmt);
oci_close($conn);

echo json_encode([
    'success'       => true,
    'date'          => $date,
    'total_orders'  => $total_orders,
    'total_revenue' => $total_revenue,
    'slots'         => $slots
]);
?>