<?php
/**
 * Trader Registration API
 * POST /api/auth/register-trader.php
 * Table: HUDDER_USER (varchar2(20) password — no bcrypt, plain stored)
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Only POST allowed.';
    echo json_encode($response); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response); exit;
}

$required = ['firstname', 'lastname', 'email', 'password', 'shop_name', 'shop_type'];
foreach ($required as $f) {
    if (empty($data[$f])) {
        $response['message'] = ucfirst(str_replace('_', ' ', $f)) . ' is required.';
        echo json_encode($response); exit;
    }
}

$firstname        = trim($data['firstname']);
$lastname         = trim($data['lastname']);
$email            = trim($data['email']);
$password         = $data['password'];   // stored plain (VARCHAR2(20) limit)
$phone            = trim($data['phone_number'] ?? '');
$address          = trim($data['address'] ?? '');
$shop_name        = trim($data['shop_name']);
$shop_type        = trim($data['shop_type'] ?? '');
$shop_description = trim($data['shop_description'] ?? '');
$shop_location    = trim($data['shop_location'] ?? $address);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format.';
    echo json_encode($response); exit;
}

if (strlen($password) < 4) {
    $response['message'] = 'Password must be at least 4 characters.';
    echo json_encode($response); exit;
}

// VARCHAR2(20) limit – truncate silently if needed
$password = substr($password, 0, 20);

try {
    // Check duplicate email  (re-enabled after testing)
    $chk = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE email = :email");
    oci_bind_by_name($chk, ':email', $email);
    oci_execute($chk);
    $row = oci_fetch_assoc($chk);
    oci_free_statement($chk);

    if ($row && (int)$row['CNT'] > 0) {
        $response['message'] = 'An account with this email already exists.';
        echo json_encode($response); exit;
    }

    // Insert user — trg_Hudder_user auto-assigns user_id via seq_Hudder_user
    // dob/gender optional, NULL if missing
    $dob    = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
    $gender = !empty($data['gender'])        ? trim($data['gender'])  : null;

    if ($dob) {
        $sql_user = "INSERT INTO HUDDER_USER (user_id, firstname, lastname, email, user_password, user_role, phone_number, address, date_of_birth, gender)
                     VALUES (seq_Hudder_user.NEXTVAL, :firstname, :lastname, :email, :password, 'trader', :phone, :address, TO_DATE(:dob,'YYYY-MM-DD'), :gender)";
    } else {
        $sql_user = "INSERT INTO HUDDER_USER (user_id, firstname, lastname, email, user_password, user_role, phone_number, address)
                     VALUES (seq_Hudder_user.NEXTVAL, :firstname, :lastname, :email, :password, 'trader', :phone, :address)";
    }

    $ins = oci_parse($conn, $sql_user);
    oci_bind_by_name($ins, ':firstname', $firstname);
    oci_bind_by_name($ins, ':lastname',  $lastname);
    oci_bind_by_name($ins, ':email',     $email);
    oci_bind_by_name($ins, ':password',  $password);
    oci_bind_by_name($ins, ':phone',     $phone);
    oci_bind_by_name($ins, ':address',   $address);
    if ($dob) {
        oci_bind_by_name($ins, ':dob',    $dob);
        oci_bind_by_name($ins, ':gender', $gender);
    }
    $r = @oci_execute($ins, OCI_NO_AUTO_COMMIT);

    if (!$r) {
        $e = oci_error($ins);
        oci_rollback($conn);
        $response['message'] = 'Failed to create user: ' . ($e['message'] ?? 'Unknown error');
        echo json_encode($response); exit;
    }
    oci_free_statement($ins);

    // Fetch the auto-generated user_id by email
    $getUserId = oci_parse($conn, "SELECT user_id FROM HUDDER_USER WHERE email = :email");
    oci_bind_by_name($getUserId, ':email', $email);
    oci_execute($getUserId);
    $userRow = oci_fetch_assoc($getUserId);
    $user_id = $userRow ? (int)$userRow['USER_ID'] : 0;
    oci_free_statement($getUserId);

    if (!$user_id) {
        oci_rollback($conn);
        $response['message'] = 'Failed to retrieve user ID after registration.';
        echo json_encode($response); exit;
    }

    // Insert TRADER — uses seq_Trader directly (bypasses broken trigger)
    $sqlTrader = "INSERT INTO TRADER (trader_id, user_id, status) VALUES (seq_Trader.NEXTVAL, :user_id, 'Pending')";
    $stmtTrader = oci_parse($conn, $sqlTrader);
    oci_bind_by_name($stmtTrader, ':user_id', $user_id);
    $r2 = @oci_execute($stmtTrader, OCI_NO_AUTO_COMMIT);

    if (!$r2) {
        $e = oci_error($stmtTrader);
        oci_rollback($conn);
        $response['message'] = 'Failed to create trader record: ' . ($e['message'] ?? 'Unknown error');
        echo json_encode($response); exit;
    }
    oci_free_statement($stmtTrader);

    // Insert SHOP — uses seq_Shop directly (bypasses broken trigger)
    $ss = oci_parse($conn, "INSERT INTO SHOP (shop_id, name, description, location, contact_number, user_id, shop_type)
                             VALUES (seq_Shop.NEXTVAL, :shop_name, :shop_desc, :shop_loc, :contact_num, :user_id, :shop_type)");
    oci_bind_by_name($ss, ':shop_name',    $shop_name);
    oci_bind_by_name($ss, ':shop_desc',    $shop_description);
    oci_bind_by_name($ss, ':shop_loc',     $shop_location);
    oci_bind_by_name($ss, ':contact_num',  $phone);
    oci_bind_by_name($ss, ':user_id',      $user_id);
    oci_bind_by_name($ss, ':shop_type',    $shop_type);
    $r3 = @oci_execute($ss, OCI_NO_AUTO_COMMIT);

    if (!$r3) {
        $e = oci_error($ss);
        oci_rollback($conn);
        $response['message'] = 'Failed to create shop: ' . ($e['message'] ?? 'Unknown error');
        echo json_encode($response); exit;
    }
    oci_free_statement($ss);

    oci_commit($conn);
    $response['success'] = true;
    $response['message'] = 'Registration successful! Your account is pending admin approval.';

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    if (isset($conn)) oci_close($conn);
}

echo json_encode($response);
?>