<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $whereClause = '';
    if ($status) {
        $whereClause = "WHERE o.status = :status";
    }

    $countSql = "SELECT COUNT(*) AS cnt FROM HUDDER_ORDER o $whereClause";
    $countStmt = oci_parse($conn, $countSql);
    if ($status) {
        oci_bind_by_name($countStmt, ':status', $status);
    }
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $total = (int)($countRow['CNT'] ?? 0);
    oci_free_statement($countStmt);

    $sql = "
        SELECT o.order_id, o.order_date, o.order_time, o.status,
               u.user_id, u.firstname, u.lastname, u.email,
               cs.slot_date, cs.slot_time, cs.location AS slot_location,
               p.amount AS total, p.method AS payment_method, p.status AS payment_status
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        LEFT JOIN PAYMENT p ON p.order_id = o.order_id
        $whereClause
        ORDER BY o.order_date DESC, o.order_id DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':offset', $offset);
    oci_bind_by_name($stmt, ':limit', $limit);
    if ($status) {
        oci_bind_by_name($stmt, ':status', $status);
    }
    oci_execute($stmt);

    $orders = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $orderId = (int)$row['ORDER_ID'];
        
        $itemsStmt = oci_parse($conn, "
            SELECT op.product_id, op.quantity, op.unit_price, p.name, p.image_url
            FROM ORDER_PRODUCT op
            JOIN PRODUCT p ON op.product_id = p.product_id
            WHERE op.order_id = :order_id
        ");
        oci_bind_by_name($itemsStmt, ':order_id', $orderId);
        oci_execute($itemsStmt);
        
        $items = [];
        while ($item = oci_fetch_assoc($itemsStmt)) {
            $items[] = [
                'product_id' => (int)$item['PRODUCT_ID'],
                'name' => $item['NAME'],
                'image' => $item['IMAGE_URL'] ?? '',
                'quantity' => (int)$item['QUANTITY'],
                'unit_price' => (float)$item['UNIT_PRICE']
            ];
        }
        oci_free_statement($itemsStmt);

        $orders[] = [
            'order_id' => $orderId,
            'order_date' => $row['ORDER_DATE'],
            'order_time' => $row['ORDER_TIME'],
            'status' => $row['STATUS'],
            'customer' => [
                'user_id' => (int)$row['USER_ID'],
                'firstname' => $row['FIRSTNAME'],
                'lastname' => $row['LASTNAME'],
                'email' => $row['EMAIL']
            ],
            'collection' => [
                'date' => $row['SLOT_DATE'],
                'time' => $row['SLOT_TIME'],
                'location' => $row['SLOT_LOCATION']
            ],
            'items' => $items,
            'total' => (float)($row['TOTAL'] ?? 0),
            'payment_method' => $row['PAYMENT_METHOD'] ?? '',
            'payment_status' => $row['PAYMENT_STATUS'] ?? ''
        ];
    }
    oci_free_statement($stmt);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>