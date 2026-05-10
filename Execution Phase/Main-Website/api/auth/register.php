<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$firstname = $data['firstname'];
$lastname = $data['lastname'];
$email = $data['email'];
$password = $data['password'];
$phone = $data['phone_number'] ?? '';
$address = $data['address'] ?? '';
$dob = $data['date_of_birth'] ?? '';
$gender = $data['gender'] ?? '';

// Check if email exists
$check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE email = :email");
oci_bind_by_name($check, ':email', $email);
oci_execute($check);
$row = oci_fetch_assoc($check);
if ($row['CNT'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

// Get next user_id
$idq = oci_parse($conn, "SELECT NVL(MAX(user_id),0)+1 AS new_id FROM HUDDER_USER");
oci_execute($idq);
$idrow = oci_fetch_assoc($idq);
$user_id = $idrow['NEW_ID'];

// Insert user
$sql = "INSERT INTO HUDDER_USER VALUES (:user_id, :firstname, :lastname, :email, :password, 'customer', :phone, :address, TO_DATE(:dob,'YYYY-MM-DD'), :gender)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_bind_by_name($stmt, ':firstname', $firstname);
oci_bind_by_name($stmt, ':lastname', $lastname);
oci_bind_by_name($stmt, ':email', $email);
oci_bind_by_name($stmt, ':password', $password);
oci_bind_by_name($stmt, ':phone', $phone);
oci_bind_by_name($stmt, ':address', $address);
oci_bind_by_name($stmt, ':dob', $dob);
oci_bind_by_name($stmt, ':gender', $gender);
oci_execute($stmt);

// Get next customer_id
$cidq = oci_parse($conn, "SELECT NVL(MAX(customer_id),0)+1 AS new_id FROM CUSTOMER");
oci_execute($cidq);
$cidrow = oci_fetch_assoc($cidq);
$customer_id = $cidrow['NEW_ID'];

// Insert customer
$cstmt = oci_parse($conn, "INSERT INTO CUSTOMER VALUES (:cid, :uid)");
oci_bind_by_name($cstmt, ':cid', $customer_id);
oci_bind_by_name($cstmt, ':uid', $user_id);
oci_execute($cstmt);

oci_commit($conn);
echo json_encode(['success' => true, 'message' => 'Registration successful']);
?>