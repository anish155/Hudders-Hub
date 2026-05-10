<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$firstname = $data['firstname'];
$lastname = $data['lastname'];
$email = $data['email'];
$password = $data['password'];
$phone = $data['phone_number'] ?? '';
$address = $data['address'] ?? '';
$dob = $data['date_of_birth'] ?? '1990-01-01';
$gender = $data['gender'] ?? 'Prefer not to say';

if (!$conn) {
    $e = oci_error();
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e['message']]);
    exit;
}

// Check email
$check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE email = :email");
oci_bind_by_name($check, ':email', $email);
oci_execute($check);
$row = oci_fetch_assoc($check);
if ($row['CNT'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

// Insert user
$sql = "INSERT INTO HUDDER_USER (firstname, lastname, email, user_password, user_role, phone_number, address, date_of_birth, gender)
        VALUES (:firstname, :lastname, :email, :password, 'trader', :phone, :address, TO_DATE(:dob,'YYYY-MM-DD'), :gender)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':firstname', $firstname);
oci_bind_by_name($stmt, ':lastname', $lastname);
oci_bind_by_name($stmt, ':email', $email);
oci_bind_by_name($stmt, ':password', $password);
oci_bind_by_name($stmt, ':phone', $phone);
oci_bind_by_name($stmt, ':address', $address);
oci_bind_by_name($stmt, ':dob', $dob);
oci_bind_by_name($stmt, ':gender', $gender);

if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stmt);
    echo json_encode(['success' => false, 'message' => 'User insert failed: ' . $e['message']]);
    exit;
}

// Get the user_id just inserted
$idq = oci_parse($conn, "SELECT user_id FROM HUDDER_USER WHERE email = :email");
oci_bind_by_name($idq, ':email', $email);
oci_execute($idq, OCI_NO_AUTO_COMMIT);
$idrow = oci_fetch_assoc($idq);
$user_id = $idrow['USER_ID'];

if (!$user_id) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Could not retrieve user_id after insert']);
    exit;
}

// Insert trader
$tstmt = oci_parse($conn, "INSERT INTO TRADER (user_id, status) VALUES (:user_id, 'Pending')");
oci_bind_by_name($tstmt, ':user_id', $user_id);

if (!oci_execute($tstmt, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($tstmt);
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Trader insert failed: ' . $e['message']]);
    exit;
}

oci_commit($conn);
echo json_encode(['success' => true, 'message' => 'Trader registration submitted. Awaiting admin approval.']);
?>