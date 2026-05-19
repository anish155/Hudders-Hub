<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $whereClause = "WHERE u.user_role = 'Customer'";
    $countWhere = "WHERE user_role = 'Customer'";
    
    if ($search) {
        $whereClause .= " AND (u.firstname LIKE :search OR u.lastname LIKE :search OR u.email LIKE :search)";
        $countWhere .= " AND (firstname LIKE :search OR lastname LIKE :search OR email LIKE :search)";
    }

    $countSql = "SELECT COUNT(*) AS cnt FROM HUDDER_USER $countWhere";
    $countStmt = oci_parse($conn, $countSql);
    if ($search) {
        $searchParam = '%' . $search . '%';
        oci_bind_by_name($countStmt, ':search', $searchParam);
    }
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $total = (int)($countRow['CNT'] ?? 0);
    oci_free_statement($countStmt);

    $sql = "
        SELECT u.user_id, u.firstname, u.lastname, u.email, u.phone_number, u.address, u.created_at,
               (SELECT COUNT(*) FROM HUDDER_ORDER WHERE user_id = u.user_id) AS order_count
        FROM HUDDER_USER u
        $whereClause
        ORDER BY u.user_id DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
    ";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':offset', $offset);
    oci_bind_by_name($stmt, ':limit', $limit);
    if ($search) {
        $searchParam = '%' . $search . '%';
        oci_bind_by_name($stmt, ':search', $searchParam);
    }
    oci_execute($stmt);

    $customers = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $customers[] = [
            'user_id' => (int)$row['USER_ID'],
            'firstname' => $row['FIRSTNAME'],
            'lastname' => $row['LASTNAME'],
            'email' => $row['EMAIL'],
            'phone' => $row['PHONE_NUMBER'] ?? '',
            'address' => $row['ADDRESS'] ?? '',
            'created_at' => $row['CREATED_AT'],
            'order_count' => (int)$row['ORDER_COUNT']
        ];
    }
    oci_free_statement($stmt);

    echo json_encode([
        'success' => true,
        'customers' => $customers,
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