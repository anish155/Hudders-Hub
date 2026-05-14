<?php
// Suppress PHP warnings so they don't break JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST is allowed.';
    echo json_encode($response);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Email, password and role are required']);
    exit;
}

$email        = trim($data['email']);
$password     = $data['password'];
$requested_role = strtolower(trim($data['role'])); // 'customer' or 'trader'

// Basic role whitelist
if (!in_array($requested_role, ['customer', 'trader'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
    exit;
}

// Fetch the user
$sql = "SELECT user_id, firstname, lastname, email, user_role 
        FROM HUDDER_USER 
        WHERE email = :email AND user_password = :password";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':email',    $email);
oci_bind_by_name($stmt, ':password', $password);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    oci_close($conn);
    exit;
}

$actual_role = strtolower($row['USER_ROLE']);

// Role mismatch — tell the frontend which tab to use
if ($actual_role !== $requested_role) {
    echo json_encode([
        'success'       => false,
        'role_mismatch' => true,
        'actual_role'   => $actual_role,
        'message'       => 'This account is registered as a ' . $actual_role . '. Please use the ' . ucfirst($actual_role) . ' tab.'
    ]);
    oci_close($conn);
    exit;
}

// For traders: check approval status
$trader_status = null;
if ($actual_role === 'trader') {
    $sql2  = "SELECT status FROM TRADER WHERE user_id = :trader_user_id";
    $stmt2 = oci_parse($conn, $sql2);
    $trader_user_id = $row['USER_ID'];
    oci_bind_by_name($stmt2, ':trader_user_id', $trader_user_id);
    oci_execute($stmt2);
    $trow  = oci_fetch_assoc($stmt2);
    oci_free_statement($stmt2);
    $trader_status = $trow ? strtolower($trow['STATUS']) : 'pending';

    if ($trader_status !== 'active') {
        echo json_encode([
            'success'        => false,
            'trader_pending' => true,
            'message'        => 'Your trader account is awaiting admin approval.'
        ]);
        oci_close($conn);
        exit;
    }
}

// All good — create session
$_SESSION['user_id']   = $row['USER_ID'];
$_SESSION['firstname'] = $row['FIRSTNAME'];
$_SESSION['email']     = $row['EMAIL'];
$_SESSION['role']      = $actual_role;

echo json_encode([
    'success' => true,
    'user_id' => $row['USER_ID'],
    'name'    => $row['FIRSTNAME'],
    'email'   => $row['EMAIL'],
    'role'    => $actual_role
]);

oci_close($conn);
?>