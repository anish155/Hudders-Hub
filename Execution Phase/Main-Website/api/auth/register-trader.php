<?php
/**
 * Trader Registration API
 * Endpoint: POST /api/auth/register-trader.php
 * Registers a new trader (requires admin approval)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST is allowed.';
    echo json_encode($response);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

$required = ['firstname', 'lastname', 'email', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $response['message'] = ucfirst($field) . ' is required.';
        echo json_encode($response);
        exit;
    }
}

$firstname = trim($data['firstname']);
$lastname  = trim($data['lastname']);
$email     = trim($data['email']);
$password  = $data['password'];
$phone     = !empty($data['phone_number']) ? trim($data['phone_number']) : '';
$address   = !empty($data['address']) ? trim($data['address']) : '';
$dob       = !empty($data['date_of_birth']) ? $data['date_of_birth'] : '';
$gender    = !empty($data['gender']) ? trim($data['gender']) : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) {
    $response['message'] = 'Password must be at least 6 characters long.';
    echo json_encode($response);
    exit;
}

try {
    $checkSql = "SELECT COUNT(*) AS CNT FROM HUDDERS_USER WHERE email = :email";
    $checkStmt = oci_parse($conn, $checkSql);
    oci_bind_by_name($checkStmt, ':email', $email);
    oci_execute($checkStmt);
    $checkRow = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if ($checkRow && $checkRow['CNT'] > 0) {
        $response['message'] = 'An account with this email already exists.';
        echo json_encode($response);
        exit;
    }

    $idq = oci_parse($conn, "SELECT NVL(MAX(user_id),0)+1 AS new_id FROM HUDDERS_USER");
    oci_execute($idq);
    $idrow = oci_fetch_assoc($idq);
    $user_id = $idrow['NEW_ID'];
    oci_free_statement($idq);

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $insertSql = "INSERT INTO HUDDERS_USER (
        user_id, firstname, lastname, email, user_password,
        user_role, phone_number, address, date_of_birth, gender
    ) VALUES (
        :user_id, :firstname, :lastname, :email, :password,
        'trader', :phone, :address, TO_DATE(:dob, 'YYYY-MM-DD'), :gender
    )";

    $insertStmt = oci_parse($conn, $insertSql);
    oci_bind_by_name($insertStmt, ':user_id', $user_id);
    oci_bind_by_name($insertStmt, ':firstname', $firstname);
    oci_bind_by_name($insertStmt, ':lastname', $lastname);
    oci_bind_by_name($insertStmt, ':email', $email);
    oci_bind_by_name($insertStmt, ':password', $hashedPassword);
    oci_bind_by_name($insertStmt, ':phone', $phone);
    oci_bind_by_name($insertStmt, ':address', $address);
    oci_bind_by_name($insertStmt, ':dob', $dob);
    oci_bind_by_name($insertStmt, ':gender', $gender);

    $insertResult = oci_execute($insertStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($insertStmt);

    if (!$insertResult) {
        $e = oci_error($insertStmt);
        oci_rollback($conn);
        $response['message'] = 'Failed to create user account.';
        if ($e) {
            $response['message'] .= ' Error: ' . $e['message'];
        }
        echo json_encode($response);
        exit;
    }

    $traderIdQ = oci_parse($conn, "SELECT NVL(MAX(trader_id),0)+1 AS new_id FROM TRADER");
    oci_execute($traderIdQ);
    $traderIdRow = oci_fetch_assoc($traderIdQ);
    $trader_id = $traderIdRow['NEW_ID'];
    oci_free_statement($traderIdQ);

    $traderStmt = oci_parse($conn, "INSERT INTO TRADER (trader_id, user_id, status) VALUES (:tid, :uid, 'Pending')");
    oci_bind_by_name($traderStmt, ':tid', $trader_id);
    oci_bind_by_name($traderStmt, ':uid', $user_id);
    $traderResult = oci_execute($traderStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($traderStmt);

    if (!$traderResult) {
        oci_rollback($conn);
        $response['message'] = 'Failed to create trader profile.';
        echo json_encode($response);
        exit;
    }

    oci_commit($conn);

    $response['success'] = true;
    $response['message'] = 'Registration successful! Your trader account is pending approval. You will be notified once an admin reviews your application.';

} catch (Exception $e) {
    oci_rollback($conn);
    $response['message'] = 'An error occurred during registration: ' . $e->getMessage();
} finally {
    if (isset($conn)) {
        oci_close($conn);
    }
}

echo json_encode($response);
?>