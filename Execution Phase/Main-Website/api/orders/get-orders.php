<?php
// api/orders/get-orders.php
// Dual-mode: customer (session-scoped orders) or trader (shop-scoped orders)
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn    = getDB();
$user_id = getUserId();
$role    = getUserRole();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($role === 'trader') {
    // ── Trader mode: return all orders containing this trader's products ──────
    $sql = "
        SELECT DISTINCT o.order_id, o.order_date, o.order_time, o.status,
               u.firstname || ' ' || u.lastname AS customer_name,
               u.phone_number, u.address,
               SUM(op.quantity * op.unit_price) AS total,
               cs.slot_date, cs.slot_time, cs.location
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        WHERE s.user_id = :user_id
        GROUP BY o.order_id, o.order_date, o.order_time, o.status,
                 u.firstname, u.lastname, u.phone_number, u.address,
                 cs.slot_date, cs.slot_time, cs.location
        ORDER BY o.order_date DESC, o.order_time DESC
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':user_id', $user_id);
} else {
    // ── Customer mode: own orders only ───────────────────────────────────────
    $sql = "
        SELECT o.order_id, o.order_date, o.order_time, o.status,
               cs.slot_date, cs.slot_time, cs.location,
               p.amount, p.method AS payment_method, p.status AS payment_status
        FROM HUDDER_ORDER o
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        LEFT JOIN PAYMENT p ON o.order_id = p.order_id
        WHERE o.user_id = :user_id
        ORDER BY o.order_date DESC, o.order_time DESC
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':user_id', $user_id);
}
oci_execute($stmt);

$orders = [];
while ($row = oci_fetch_assoc($stmt)) {
    $order_id = $row['ORDER_ID'];

    // Fetch line items
    $productsSql = "SELECT op.product_id, p.name, op.quantity, op.unit_price,
                           (op.quantity * op.unit_price) AS subtotal
                    FROM ORDER_PRODUCT op
                    JOIN PRODUCT p ON op.product_id = p.product_id
                    WHERE op.order_id = :order_id";
    $productsStmt = oci_parse($conn, $productsSql);
    oci_bind_by_name($productsStmt, ':order_id', $order_id);
    oci_execute($productsStmt);

    $products = [];
    while ($product = oci_fetch_assoc($productsStmt)) {
        $products[] = $product;
    }
    oci_free_statement($productsStmt);

    $row['PRODUCTS'] = $products;
    $orders[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $orders]);