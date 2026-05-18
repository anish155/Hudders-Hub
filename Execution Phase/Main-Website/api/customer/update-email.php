<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$new_email = $data['email'] ?? null;

if (!$new_email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

$checkSql = "SELECT COUNT(*) AS cnt FROM HUDDER_USER WHERE email = :email AND user_id != :user_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':email', $new_email);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_execute($checkStmt);
$checkRow = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($checkRow['CNT'] > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email already in use']);
    oci_close($conn);
    exit;
}

$sql = "UPDATE HUDDER_USER SET email = :email WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':email', $new_email);
oci_bind_by_name($stmt, ':user_id', $user_id);

$result = oci_execute($stmt);
if ($result) {
    $_SESSION['email'] = $new_email;
    echo json_encode(['success' => true, 'message' => 'Email updated successfully']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
