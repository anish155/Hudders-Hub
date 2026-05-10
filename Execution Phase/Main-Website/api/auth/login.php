<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

$sql = "SELECT user_id, firstname, lastname, email, user_role 
        FROM HUDDER_USER 
        WHERE email = :email AND user_password = :password";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':email', $email);
oci_bind_by_name($stmt, ':password', $password);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);

if ($row) {
    $_SESSION['user_id'] = $row['USER_ID'];
    $_SESSION['firstname'] = $row['FIRSTNAME'];
    $_SESSION['email'] = $row['EMAIL'];
    $_SESSION['role'] = $row['USER_ROLE'];

    echo json_encode([
        'success' => true,
        'user_id' => $row['USER_ID'],
        'name' => $row['FIRSTNAME'],
        'email' => $row['EMAIL'],
        'role' => strtolower($row['USER_ROLE'])
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password'
    ]);
}

oci_free_statement($stmt);
oci_close($conn);
?>