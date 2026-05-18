<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'User ID required']); exit; }

try {
    $shopStmt = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
    oci_bind_by_name($shopStmt, ':user_id', $user_id);
    oci_execute($shopStmt);
    $shopRow = oci_fetch_assoc($shopStmt);
    $shop_id = $shopRow['SHOP_ID'] ?? 0;
    if (!$shop_id) { throw new Exception("Shop not found"); }

    // Summary
    $stmt = oci_parse($conn, "
        SELECT COUNT(DISTINCT o.order_id) AS total_orders, 
               NVL(SUM(op.quantity * op.unit_price), 0) AS total_revenue
        FROM HUDDER_ORDER o 
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id 
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid AND TRUNC(o.order_date, 'MM') = TRUNC(SYSDATE, 'MM') AND o.status != 'Cancelled'
    ");
    oci_bind_by_name($stmt, ':sid', $shop_id);
    oci_execute($stmt);
    $sum = oci_fetch_assoc($stmt);

    // Orders List (This is what was missing)
    $ordersStmt = oci_parse($conn, "
        SELECT DISTINCT o.order_id, u.firstname || ' ' || u.lastname AS customer_name
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        JOIN ORDER_PRODUCT op ON o.order_id = op.order_id
        JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid AND TRUNC(o.order_date, 'MM') = TRUNC(SYSDATE, 'MM')
        ORDER BY o.order_id DESC
    ");
    oci_bind_by_name($ordersStmt, ':sid', $shop_id);
    oci_execute($ordersStmt);
    $orders = [];
    while ($row = oci_fetch_assoc($ordersStmt)) {
        $itemStmt = oci_parse($conn, "SELECT SUM(quantity) AS items, SUM(quantity * unit_price) AS total FROM ORDER_PRODUCT op2 JOIN PRODUCT p2 ON op2.product_id = p2.product_id WHERE op2.order_id = :oid AND p2.shop_id = :sid");
        oci_bind_by_name($itemStmt, ':oid', $row['ORDER_ID']);
        oci_bind_by_name($itemStmt, ':sid', $shop_id);
        oci_execute($itemStmt);
        $iRow = oci_fetch_assoc($itemStmt);
        $orders[] = ['customer' => $row['CUSTOMER_NAME'], 'items' => $iRow['ITEMS'], 'total' => $iRow['TOTAL']];
    }

    // Chart
    $chartStmt = oci_parse($conn, "
        SELECT 'Week ' || CEIL(TO_NUMBER(TO_CHAR(o.order_date, 'DD')) / 7) AS label, SUM(op.quantity * op.unit_price) AS value
        FROM HUDDER_ORDER o JOIN ORDER_PRODUCT op ON o.order_id = op.order_id JOIN PRODUCT p ON op.product_id = p.product_id
        WHERE p.shop_id = :sid AND TRUNC(o.order_date, 'MM') = TRUNC(SYSDATE, 'MM') AND o.status != 'Cancelled'
        GROUP BY CEIL(TO_NUMBER(TO_CHAR(o.order_date, 'DD')) / 7) ORDER BY label
    ");
    oci_bind_by_name($chartStmt, ':sid', $shop_id);
    oci_execute($chartStmt);
    $labels = []; $values = [];
    while ($r = oci_fetch_assoc($chartStmt)) { $labels[] = $r['LABEL']; $values[] = (float)$r['VALUE']; }

    echo json_encode(['success' => true, 'summary' => ['total_orders' => $sum['TOTAL_ORDERS'], 'total_revenue' => $sum['TOTAL_REVENUE'], 'avg_order_value' => $sum['TOTAL_ORDERS'] > 0 ? $sum['TOTAL_REVENUE']/$sum['TOTAL_ORDERS'] : 0], 'orders' => $orders, 'chart' => ['labels' => $labels, 'values' => $values]]);
} catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?>