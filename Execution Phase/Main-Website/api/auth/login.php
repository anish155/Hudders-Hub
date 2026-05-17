<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $email = trim($data['email']);
    $password = $data['password'];
    $requested_role = strtolower($data['role']);

    $sql = "SELECT user_id, firstname, lastname, user_password, user_role FROM HUDDER_USER WHERE email = :em";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':em', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);

    if (!$user) throw new Exception("Invalid email or password.");

    // Verify Password
    $db_pass = $user['USER_PASSWORD'];
    $valid = (strpos($db_pass, '$2y$') === 0) ? password_verify($password, $db_pass) : ($password === $db_pass);
    if (!$valid) throw new Exception("Invalid email or password.");

    // Check Role
    if (strtolower($user['USER_ROLE']) !== $requested_role) {
        throw new Exception("Account type mismatch. Please use the " . $user['USER_ROLE'] . " tab.");
    }

    // Trader check
    if ($requested_role === 'trader') {
        $t_sql = "SELECT status FROM TRADER WHERE user_id = :uid";
        $t_stmt = oci_parse($conn, $t_sql);
        oci_bind_by_name($t_stmt, ':uid', $user['USER_ID']);
        oci_execute($t_stmt);
        $trow = oci_fetch_assoc($t_stmt);
        if ($trow && strtolower($trow['STATUS']) === 'pending') {
            echo json_encode(['success' => false, 'trader_pending' => true]);
            exit;
        }
    }

echo json_encode([
    'success'   => true, 
    'name'      => $user['FIRSTNAME'], 
    'role'      => $user['USER_ROLE'],
    'user_id'   => $user['USER_ID'],
    'firstname' => $user['FIRSTNAME'],  
    'lastname'  => $user['LASTNAME'],   
]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>