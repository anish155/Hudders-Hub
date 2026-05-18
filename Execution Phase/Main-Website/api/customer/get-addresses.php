<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$sql = "SELECT user_id, address
        FROM HUDDER_USER
        WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);

$user = oci_fetch_assoc($stmt);
oci_free_statement($stmt);
oci_close($conn);

if ($user) {
    echo json_encode(['success' => true, 'data' => $user]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
}
