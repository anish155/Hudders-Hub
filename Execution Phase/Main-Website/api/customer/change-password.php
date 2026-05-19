<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$current_password = $data['current_password'] ?? null;
$new_password = $data['new_password'] ?? null;

if (!$current_password || !$new_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current and new password are required']);
    exit;
}

$checkSql = "SELECT user_password FROM HUDDER_USER WHERE user_id = :user_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_execute($checkStmt);
$user = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($user['USER_PASSWORD'] !== $current_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    oci_close($conn);
    exit;
}

$sql = "UPDATE HUDDER_USER SET user_password = :new_password WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':new_password', $new_password);
oci_bind_by_name($stmt, ':user_id', $user_id);

$result = oci_execute($stmt);
if ($result) {
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
