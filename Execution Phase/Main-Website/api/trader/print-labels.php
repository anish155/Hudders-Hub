<?php
// api/trader/print-labels.php
// Prints order labels ONLY for orders that contain products from the
// logged-in trader's shop.  Ownership is enforced in the primary query.
require_once '../../config/database.php';
header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id  = isset($_GET['user_id'])  ? (int)$_GET['user_id']  : 0;

if (!$order_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id or user_id']);
    exit;
}

// ── Single ownership-enforced query ──────────────────────────────────────────
$sql = "
    SELECT o.order_id, o.order_date, o.order_time, o.status,
           cs.slot_date, cs.slot_time, cs.location,
           u.firstname, u.lastname, u.phone_number
    FROM HUDDER_ORDER o
    JOIN HUDDER_USER u ON o.user_id = u.user_id
    LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
    WHERE o.order_id = :oid
      AND EXISTS (
          SELECT 1
          FROM ORDER_PRODUCT op
          JOIN PRODUCT p ON op.product_id = p.product_id
          JOIN SHOP s ON p.shop_id = s.shop_id
          WHERE op.order_id = o.order_id
            AND s.user_id = :user_id
      )
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':oid', $order_id);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$order = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
    exit;
}

// ── Products (already confirmed to belong to trader's shop) ─────────────────
$sql_products = "
    SELECT p.name, op.quantity
    FROM ORDER_PRODUCT op
    JOIN PRODUCT p ON op.product_id = p.product_id
    JOIN SHOP s ON p.shop_id = s.shop_id
    WHERE op.order_id = :oid
      AND s.user_id = :user_id
    ORDER BY p.name
";
$stmt_prod = oci_parse($conn, $sql_products);
oci_bind_by_name($stmt_prod, ':oid', $order_id);
oci_bind_by_name($stmt_prod, ':user_id', $user_id);
oci_execute($stmt_prod);

$products = [];
while ($row = oci_fetch_assoc($stmt_prod)) {
    $products[] = [
        'name'     => $row['NAME'],
        'quantity' => (int)$row['QUANTITY']
    ];
}
oci_free_statement($stmt_prod);
oci_close($conn);

echo json_encode([
    'success'  => true,
    'order'    => [
        'id'    => '#ORD-' . str_pad($order['ORDER_ID'], 3, '0', STR_PAD_LEFT),
        'date'  => $order['ORDER_DATE'],
        'time'  => $order['ORDER_TIME'],
        'status'=> $order['STATUS'],
        'customer' => $order['FIRSTNAME'] . ' ' . $order['LASTNAME'],
        'phone'    => $order['PHONE_NUMBER'],
        'collection' => [
            'date'     => $order['SLOT_DATE'],
            'time'     => $order['SLOT_TIME'],
            'location' => $order['LOCATION']
        ]
    ],
    'products' => $products
]);
