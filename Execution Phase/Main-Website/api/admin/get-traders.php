<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $conditions = [];
    $params = [];
    
    if ($status) {
        $conditions[] = "t.status = :status";
        $params[':status'] = $status;
    }
    if ($search) {
        $conditions[] = "(u.firstname LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search OR s.name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(*) AS cnt FROM TRADER t 
                 JOIN HUDDER_USER u ON t.user_id = u.user_id 
                 LEFT JOIN SHOP s ON s.user_id = u.user_id 
                 $whereClause";
    $countStmt = oci_parse($conn, $countSql);
    foreach ($params as $key => $val) {
        oci_bind_by_name($countStmt, $key, $val);
    }
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $total = (int)($countRow['CNT'] ?? 0);
    oci_free_statement($countStmt);

    $sql = "
        SELECT t.trader_id, t.user_id, t.status AS trader_status, t.created_at AS trader_created,
               u.firstname, u.lastname, u.email, u.phone_number,
               s.shop_id, s.name AS shop_name, s.location, s.contact_number
        FROM TRADER t
        JOIN HUDDER_USER u ON t.user_id = u.user_id
        LEFT JOIN SHOP s ON s.user_id = u.user_id
        $whereClause
        ORDER BY t.created_at DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':offset', $offset);
    oci_bind_by_name($stmt, ':limit', $limit);
    foreach ($params as $key => $val) {
        oci_bind_by_name($stmt, $key, $val);
    }
    oci_execute($stmt);

    $traders = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $traders[] = [
            'trader_id' => (int)$row['TRADER_ID'],
            'user_id' => (int)$row['USER_ID'],
            'firstname' => $row['FIRSTNAME'],
            'lastname' => $row['LASTNAME'],
            'email' => $row['EMAIL'],
            'phone' => $row['PHONE_NUMBER'] ?? '',
            'status' => $row['TRADER_STATUS'],
            'shop_id' => $row['SHOP_ID'] ? (int)$row['SHOP_ID'] : null,
            'shop_name' => $row['SHOP_NAME'] ?? '',
            'shop_location' => $row['LOCATION'] ?? '',
            'created_at' => $row['TRADER_CREATED']
        ];
    }
    oci_free_statement($stmt);

    echo json_encode([
        'success' => true,
        'traders' => $traders,
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