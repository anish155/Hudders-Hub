<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = getDB();

$firstname = trim($data['firstname'] ?? '');
$lastname = trim($data['lastname'] ?? '');
$phone_number = trim($data['phone_number'] ?? '');
$address = trim($data['address'] ?? '');

$sql = "UPDATE HUDDER_USER 
        SET firstname = :firstname, 
            lastname = :lastname, 
            phone_number = :phone_number, 
            address = :address 
        WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':firstname', $firstname);
oci_bind_by_name($stmt, ':lastname', $lastname);
oci_bind_by_name($stmt, ':phone_number', $phone_number);
oci_bind_by_name($stmt, ':address', $address);
oci_bind_by_name($stmt, ':user_id', $user_id);

$result = oci_execute($stmt);
if ($result) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);