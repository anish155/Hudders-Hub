<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$address = $data['address'] ?? null;

$sql = "UPDATE HUDDER_USER SET address = :address WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':address', $address);
oci_bind_by_name($stmt, ':user_id', $user_id);

$result = oci_execute($stmt);
if ($result) {
    echo json_encode(['success' => true, 'message' => 'Address saved successfully']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
