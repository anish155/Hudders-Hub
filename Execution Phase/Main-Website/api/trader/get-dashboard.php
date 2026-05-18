<?php
/**
 * Trader Dashboard API
 * GET /api/trader/get-dashboard.php?user_id=N
 * Returns: stats, today's orders, low stock alerts, recent reviews
 *
 * Schema facts:
 *  - PRODUCT.stock (not stock_quantity)
 *  - REVIEW.review_text (not comment), no review_date column
 *  - HUDDER_ORDER status: Pending/Preparing/Ready/Collected/Cancelled/Delivered
 *  - HUDDER_ORDER status_updated_at (DATE, set by trg_order_status_update)
 *  - SHOP columns: shop_id, name, description, location, contact_number, user_id
 */

require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// 1. Trader info + shop
$sql_user = "
    SELECT u.firstname, u.lastname, u.email, s.name AS shop_name, t.status AS trader_status, s.shop_id
    FROM HUDDER_USER u
    JOIN TRADER t ON t.user_id = u.user_id
    JOIN SHOP   s ON s.user_id = u.user_id
    WHERE u.user_id = :user_id
";
$stmt = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$user = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Trader or shop not found']);
    exit;
}

$shop_id = (int)$user['SHOP_ID'];

// 2. Total orders (all time)
$st = oci_parse($conn, "
    SELECT COUNT(DISTINCT o.order_id) AS total_orders
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
");
oci_bind_by_name($st, ':shop_id', $shop_id);
oci_execute($st);
$rt = oci_fetch_assoc($st);
$total_orders = (int)($rt['TOTAL_ORDERS'] ?? 0);

// 3. Orders today
$st2 = oci_parse($conn, "
    SELECT COUNT(DISTINCT o.order_id) AS orders_today
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
      AND TRUNC(o.order_date) = TRUNC(SYSDATE)
");
oci_bind_by_name($st2, ':shop_id', $shop_id);
oci_execute($st2);
$rt2 = oci_fetch_assoc($st2);
$orders_today = (int)($rt2['ORDERS_TODAY'] ?? 0);

// 4. Weekly revenue (Mon–now, Completed orders only)
$st3 = oci_parse($conn, "
    SELECT NVL(SUM(op.quantity * op.unit_price), 0) AS weekly_revenue
    FROM HUDDER_ORDER o
    JOIN ORDER_PRODUCT op ON op.order_id = o.order_id
    JOIN PRODUCT p ON p.product_id = op.product_id
    WHERE p.shop_id = :shop_id
      AND o.order_date >= TRUNC(SYSDATE, 'IW')
      AND o.status IN ('Ready','Collected','Delivered','Completed')
");
oci_bind_by_name($st3, ':shop_id', $shop_id);
oci_execute($st3);
$rt3 = oci_fetch_assoc($st3);
$weekly_revenue = (float)($rt3['WEEKLY_REVENUE'] ?? 0);

// 5. Active products count
$st4 = oci_parse($conn, "
    SELECT COUNT(*) AS total_products
    FROM PRODUCT
    WHERE shop_id = :shop_id AND status = 'Active'
");
oci_bind_by_name($st4, ':shop_id', $shop_id);
oci_execute($st4);
$rt4 = oci_fetch_assoc($st4);
$total_products = (int)($rt4['TOTAL_PRODUCTS'] ?? 0);

// 6. Today's orders detail
$sql_orders = "
    SELECT o.order_id, o.order_time, o.status, o.status_updated_at,
           u.firstname || ' ' || u.lastname AS customer_name,
           cs.slot_date, cs.slot_time, cs.location
    FROM HUDDER_ORDER o
    JOIN HUDDER_USER u ON u.user_id = o.user_id
    LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
    WHERE o.order_id IN (
        SELECT DISTINCT o2.order_id
        FROM HUDDER_ORDER o2
        JOIN ORDER_PRODUCT op ON op.order_id = o2.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :shop_id AND TRUNC(o2.order_date) = TRUNC(SYSDATE)
    )
    ORDER BY o.order_id
";
$stmt_orders = oci_parse($conn, $sql_orders);
oci_bind_by_name($stmt_orders, ':shop_id', $shop_id);
oci_execute($stmt_orders);

$orders = [];
while ($row = oci_fetch_assoc($stmt_orders)) {
    $order_id = (int)$row['ORDER_ID'];

    $sp = oci_parse($conn, "
        SELECT p.name, op.quantity, op.unit_price
        FROM ORDER_PRODUCT op
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE op.order_id = :oid AND p.shop_id = :sid
    ");
    oci_bind_by_name($sp, ':oid', $order_id);
    oci_bind_by_name($sp, ':sid', $shop_id);
    oci_execute($sp);

    $products = [];
    $total    = 0;
    while ($p = oci_fetch_assoc($sp)) {
        $qty   = (int)$p['QUANTITY'];
        $price = (float)$p['UNIT_PRICE'];
        $products[] = ['name' => $p['NAME'], 'quantity' => $qty, 'price' => $price];
        $total      += $qty * $price;
    }
    oci_free_statement($sp);

    $slot_date = $row['SLOT_DATE'];
    if ($slot_date && is_object($slot_date)) {
        $slot_date = $slot_date->format('d M Y');
    }

    $status_updated_at = $row['STATUS_UPDATED_AT'];
    if ($status_updated_at && is_object($status_updated_at)) {
        $status_updated_at = $status_updated_at->format('Y-m-d');
    }

    $orders[] = [
        'order_id'   => $order_id,
        'id'         => '#ORD-' . str_pad($order_id, 3, '0', STR_PAD_LEFT),
        'time'       => $row['ORDER_TIME'],
        'status'     => $row['STATUS'],
        'status_updated_at' => $status_updated_at,
        'customer'   => $row['CUSTOMER_NAME'],
        'items'      => count($products),
        'products'   => $products,
        'total'      => $total,
        'collection' => [
            'date'     => $slot_date,
            'time'     => $row['SLOT_TIME'],
            'location' => $row['LOCATION']
        ],
        'status_updated_at' => $status_updated_at
    ];
}
oci_free_statement($stmt_orders);

// 7. Low stock (stock <= 5) — column is 'stock' in schema
$sql_low = "
    SELECT product_id, name, stock
    FROM PRODUCT
    WHERE shop_id = :shop_id AND stock <= 5
    ORDER BY stock ASC
";
$stmt_low = oci_parse($conn, $sql_low);
oci_bind_by_name($stmt_low, ':shop_id', $shop_id);
oci_execute($stmt_low);

$low_stock = [];
while ($row = oci_fetch_assoc($stmt_low)) {
    $low_stock[] = [
        'id'    => (int)$row['PRODUCT_ID'],
        'name'  => $row['NAME'],
        'stock' => (int)$row['STOCK']
    ];
}
oci_free_statement($stmt_low);

// 8. Recent reviews — REVIEW has: review_id, review_text, rating, user_id, product_id (no review_date)
$sql_rev = "
    SELECT r.rating, r.review_text, p.name AS product_name,
           u.firstname || ' ' || SUBSTR(u.lastname,1,1) || '.' AS customer_name
    FROM REVIEW r
    JOIN PRODUCT p ON r.product_id = p.product_id
    JOIN HUDDER_USER u ON r.user_id = u.user_id
    WHERE p.shop_id = :shop_id
    FETCH FIRST 5 ROWS ONLY
";
$stmt_rev = oci_parse($conn, $sql_rev);
oci_bind_by_name($stmt_rev, ':shop_id', $shop_id);
oci_execute($stmt_rev);

$reviews = [];
while ($row = oci_fetch_assoc($stmt_rev)) {
    $reviews[] = [
        'rating'   => (float)$row['RATING'],
        'comment'  => $row['REVIEW_TEXT'],
        'product'  => $row['PRODUCT_NAME'],
        'customer' => $row['CUSTOMER_NAME']
    ];
}
oci_free_statement($stmt_rev);

echo json_encode([
    'success'       => true,
    'email'         => $user['EMAIL'],
    'firstname'     => $user['FIRSTNAME'],
    'lastname'      => $user['LASTNAME'],
    'shop_name'     => $user['SHOP_NAME'],
    'trader_status' => $user['TRADER_STATUS'],
    'stats' => [
        'total_orders'   => $total_orders,
        'orders_today'   => $orders_today,
        'weekly_revenue' => $weekly_revenue,
        'total_products' => $total_products
    ],
    'orders'    => $orders,
    'low_stock' => $low_stock,
    'reviews'   => $reviews
]);

oci_close($conn);
?>
