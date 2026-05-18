<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'User ID required']); exit; }

try {
    // 1. Get Shop ID for the logged-in user
    $shopStmt = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
    oci_bind_by_name($shopStmt, ':user_id', $user_id);
    oci_execute($shopStmt);
    $shopRow = oci_fetch_assoc($shopStmt);
    oci_free_statement($shopStmt);
    $shop_id = $shopRow['SHOP_ID'] ?? 0;

    if (!$shop_id) { throw new Exception("Shop not found"); }

    // 2. Summary: Last 7 Days
    $stmt = oci_parse($conn, "
        SELECT COUNT(DISTINCT o.order_id) AS total_orders, 
               NVL(SUM(op.quantity * op.unit_price), 0) AS total_revenue
        FROM HUDDER_ORDER o 
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id 
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND o.order_date >= TRUNC(SYSDATE) - 7 
        AND o.status != 'Cancelled'
    ");
    oci_bind_by_name($stmt, ':sid', $shop_id);
    oci_execute($stmt);
    $sum = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    $total_orders = (int)$sum['TOTAL_ORDERS'];
    $total_revenue = (float)$sum['TOTAL_REVENUE'];

    // 3. Orders List (Populating the middle section)
    $ordersStmt = oci_parse($conn, "
        SELECT DISTINCT o.order_id, u.firstname || ' ' || u.lastname AS customer_name
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND o.order_date >= TRUNC(SYSDATE) - 7
        ORDER BY o.order_id DESC
    ");
    oci_bind_by_name($ordersStmt, ':sid', $shop_id);
    oci_execute($ordersStmt);
    
    $orders = [];
    while ($row = oci_fetch_assoc($ordersStmt)) {
        // Calculate items and total specifically for this shop's products in the order
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
            'items'    => (int)$iRow['ITEMS'], 
            'total'    => (float)$iRow['TOTAL']
        ];
        oci_free_statement($itemStmt);
    }
    oci_free_statement($ordersStmt);

    // 4. Chart Data (Revenue per day for the last 7 days)
    $chartStmt = oci_parse($conn, "
        SELECT TO_CHAR(o.order_date, 'Dy') AS label, 
               SUM(op.quantity * op.unit_price) AS value
        FROM HUDDER_ORDER o 
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id 
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid 
        AND o.order_date >= TRUNC(SYSDATE) - 6 
        AND o.status != 'Cancelled'
        GROUP BY TO_CHAR(o.order_date, 'Dy'), TRUNC(o.order_date) 
        ORDER BY TRUNC(o.order_date)
    ");
    oci_bind_by_name($chartStmt, ':sid', $shop_id);
    oci_execute($chartStmt);
    $labels = []; $values = [];
    while ($r = oci_fetch_assoc($chartStmt)) { 
        $labels[] = $r['LABEL']; 
        $values[] = (float)$r['VALUE']; 
    }
    oci_free_statement($chartStmt);

    // Fallback labels if no data exists
    if (empty($labels)) {
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $values = [0, 0, 0, 0, 0, 0, 0];
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