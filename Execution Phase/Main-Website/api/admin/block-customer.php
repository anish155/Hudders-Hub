<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'user_id and action are required']);
    exit;
}

$user_id = (int)$data['user_id'];
$action = $data['action'];

try {
    $checkStmt = oci_parse($conn, "SELECT user_role FROM HUDDER_USER WHERE user_id = :user_id");
    oci_bind_by_name($checkStmt, ':user_id', $user_id);
    oci_execute($checkStmt);
    $user = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if (!$user) {
        throw new Exception('User not found');
    }

    if (strtolower($user['USER_ROLE']) !== 'customer') {
        throw new Exception('User is not a customer');
    }

    $blocked = ($action === 'block') ? 1 : 0;
    $stmt = oci_parse($conn, "UPDATE HUDDER_USER SET blocked = :blocked WHERE user_id = :user_id");
    oci_bind_by_name($stmt, ':blocked', $blocked);
    oci_bind_by_name($stmt, ':user_id', $user_id);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        throw new Exception('Update failed: ' . $e['message']);
    }
    oci_commit($stmt);
    oci_free_statement($stmt);

    $message = $action === 'block' ? 'Customer blocked successfully' : 'Customer unblocked successfully';
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>