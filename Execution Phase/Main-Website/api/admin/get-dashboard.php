<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();

try {
    $totalCustomers = 0;
    $totalTraders = 0;
    $totalOrders = 0;
    $totalRevenue = 0;
    $pendingTraders = 0;
    $recentOrders = [];

    $custStmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM HUDDER_USER WHERE user_role = 'Customer'");
    oci_execute($custStmt);
    $custRow = oci_fetch_assoc($custStmt);
    $totalCustomers = (int)($custRow['CNT'] ?? 0);
    oci_free_statement($custStmt);

    $traderStmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM TRADER");
    oci_execute($traderStmt);
    $traderRow = oci_fetch_assoc($traderStmt);
    $totalTraders = (int)($traderRow['CNT'] ?? 0);
    oci_free_statement($traderStmt);

    $pendingStmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM TRADER WHERE status = 'Pending'");
    oci_execute($pendingStmt);
    $pendingRow = oci_fetch_assoc($pendingStmt);
    $pendingTraders = (int)($pendingRow['CNT'] ?? 0);
    oci_free_statement($pendingStmt);

    $orderStmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM HUDDER_ORDER");
    oci_execute($orderStmt);
    $orderRow = oci_fetch_assoc($orderStmt);
    $totalOrders = (int)($orderRow['CNT'] ?? 0);
    oci_free_statement($orderStmt);

    $revStmt = oci_parse($conn, "SELECT NVL(SUM(amount), 0) AS total FROM PAYMENT WHERE status = 'Completed'");
    oci_execute($revStmt);
    $revRow = oci_fetch_assoc($revStmt);
    $totalRevenue = (float)($revRow['TOTAL'] ?? 0);
    oci_free_statement($revStmt);

    $recentSql = "
        SELECT o.order_id, o.order_date, o.status, o.order_time,
               u.firstname, u.lastname,
               p.amount as total
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        LEFT JOIN PAYMENT p ON p.order_id = o.order_id
        ORDER BY o.order_date DESC, o.order_id DESC
        FETCH FIRST 10 ROWS ONLY
    ";
    $recentStmt = oci_parse($conn, $recentSql);
    oci_execute($recentStmt);
    while ($row = oci_fetch_assoc($recentStmt)) {
        $recentOrders[] = [
            'order_id' => (int)$row['ORDER_ID'],
            'date' => $row['ORDER_DATE'],
            'time' => $row['ORDER_TIME'],
            'status' => $row['STATUS'],
            'customer' => $row['FIRSTNAME'] . ' ' . $row['LASTNAME'],
            'total' => (float)($row['TOTAL'] ?? 0)
        ];
    }
    oci_free_statement($recentStmt);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_customers' => $totalCustomers,
            'total_traders' => $totalTraders,
            'pending_traders' => $pendingTraders,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue
        ],
        'recent_orders' => $recentOrders
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>