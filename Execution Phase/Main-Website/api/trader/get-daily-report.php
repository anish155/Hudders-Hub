<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// 1. Get User ID from the URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'User ID required']); exit; }

try {
    // 2. Get the Shop ID for the logged-in user
    $shopStmt = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
    oci_bind_by_name($shopStmt, ':user_id', $user_id);
    oci_execute($shopStmt);
    $shopRow = oci_fetch_assoc($shopStmt);
    oci_free_statement($shopStmt);
    $shop_id = $shopRow['SHOP_ID'] ?? 0;

    if (!$shop_id) { throw new Exception("Shop not found for user ID: " . $user_id); }

    // 3. Summary Stats for TODAY
    $stmt = oci_parse($conn, "
        SELECT COUNT(DISTINCT o.order_id) AS total_orders,
               NVL(SUM(op.quantity * op.unit_price), 0) AS total_revenue
        FROM HUDDER_ORDER o
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND TRUNC(o.order_date) = TRUNC(SYSDATE) 
        AND o.status != 'Cancelled'
    ");
    oci_bind_by_name($stmt, ':sid', $shop_id);
    oci_execute($stmt);
    $sum = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    $total_orders  = (int)($sum['TOTAL_ORDERS'] ?? 0);
    $total_revenue = (float)($sum['TOTAL_REVENUE'] ?? 0);

    // 4. List of Today's Orders
    $ordersStmt = oci_parse($conn, "
        SELECT DISTINCT o.order_id, u.firstname || ' ' || u.lastname AS customer_name
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND TRUNC(o.order_date) = TRUNC(SYSDATE)
        ORDER BY o.order_id DESC
    ");
    oci_bind_by_name($ordersStmt, ':sid', $shop_id);
    oci_execute($ordersStmt);

    $orders = [];
    while ($row = oci_fetch_assoc($ordersStmt)) {
        // Calculate items and total for this specific shop within the order
        $itemStmt = oci_parse($conn, "
            SELECT SUM(quantity) AS items, SUM(quantity * unit_price) AS total 
            FROM ORDER_PRODUCT op2
            JOIN PRODUCT p2 ON op2.product_id = p2.product_id
            WHERE op2.order_id = :oid AND p2.shop_id = :sid
        ");
        oci_bind_by_name($itemStmt, ':oid', $row['ORDER_ID']);
        oci_bind_by_name($itemStmt, ':sid', $shop_id);
        oci_execute($itemStmt);
        $iRow = oci_fetch_assoc($itemStmt);
        
        $orders[] = [
            'customer' => $row['CUSTOMER_NAME'],
            'items'    => (int)($iRow['ITEMS'] ?? 0),
            'total'    => (float)($iRow['TOTAL'] ?? 0)
        ];
        oci_free_statement($itemStmt);
    }
    oci_free_statement($ordersStmt);

    // 5. Chart Data (Revenue by Time Slot for today)
    $chartStmt = oci_parse($conn, "
        SELECT o.order_time AS label, 
               NVL(SUM(op.quantity * op.unit_price), 0) AS value
        FROM HUDDER_ORDER o
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND TRUNC(o.order_date) = TRUNC(SYSDATE)
        AND o.status != 'Cancelled'
        GROUP BY o.order_time
        ORDER BY o.order_time
    ");
    oci_bind_by_name($chartStmt, ':sid', $shop_id);
    oci_execute($chartStmt);
    $labels = []; $values = [];
    while ($r = oci_fetch_assoc($chartStmt)) {
        $labels[] = $r['LABEL'];
        $values[] = (float)$r['VALUE'];
    }
    oci_free_statement($chartStmt);

    // If no data for today, send empty labels so the chart doesn't break
    if (empty($labels)) {
        $labels = ['Morning', 'Afternoon', 'Evening'];
        $values = [0, 0, 0];
    }

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_orders'    => $total_orders,
            'total_revenue'   => $total_revenue,
            'avg_order_value' => $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0
        ],
        'orders' => $orders,
        'chart'  => ['labels' => $labels, 'values' => $values]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>