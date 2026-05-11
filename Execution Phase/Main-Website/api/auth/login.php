<?php
/**
 * Login API
 * Endpoint: POST /api/auth/login.php
 * Validates credentials and returns role-based user data
 */

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

// Validate JSON decoding
if (!$data) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

// Check required fields
if (empty($data['email']) || empty($data['password'])) {
    $response['message'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

try {
    // Fetch user by email
    $sql = "SELECT user_id, firstname, lastname, email, user_password, user_role,
                   phone_number, address, date_of_birth, gender
            FROM HUDDERS_USER
            WHERE email = :email";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);

    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        $response['message'] = 'Invalid email or password.';
        echo json_encode($response);
        exit;
    }

    // Verify password against hashed password
    $storedHash = $user['USER_PASSWORD'];
    // If password is plaintext (legacy), direct compare; otherwise use password_verify
    $passwordValid = false;
    if (strlen($storedHash) === 60 && strpos($storedHash, '$2') === 0) {
        // bcrypt hash
        $passwordValid = password_verify($password, $storedHash);
    } else {
        // plaintext fallback for already existing users
        $passwordValid = ($password === $storedHash);
    }

    if (!$passwordValid) {
        $response['message'] = 'Invalid email or password.';
        echo json_encode($response);
        exit;
    }

    // Check role/approval for traders
    $role = strtolower($user['USER_ROLE']);
    $roleId = null;

    if ($role === 'trader') {
        // Check if trader is approved
        $tSql = "SELECT trader_id, status FROM TRADER WHERE user_id = :uid";
        $tStmt = oci_parse($conn, $tSql);
        oci_bind_by_name($tStmt, ':uid', $user['USER_ID']);
        oci_execute($tStmt);
        $tRow = oci_fetch_assoc($tStmt);
        oci_free_statement($tStmt);

        if (!$tRow) {
            $response['message'] = 'Trader profile not found. Please contact admin.';
            echo json_encode($response);
            exit;
        }

        if (strtolower($tRow['STATUS']) === 'pending') {
            $response['message'] = 'Your trader account is pending approval. Please wait for admin approval.';
            echo json_encode($response);
            exit;
        }

        $roleId = $tRow['TRADER_ID'];
    } elseif ($role === 'customer') {
        // Get customer_id for customer role
        $cSql = "SELECT customer_id FROM CUSTOMER WHERE user_id = :uid";
        $cStmt = oci_parse($conn, $cSql);
        oci_bind_by_name($cStmt, ':uid', $user['USER_ID']);
        oci_execute($cStmt);
        $cRow = oci_fetch_assoc($cStmt);
        oci_free_statement($cStmt);

        if ($cRow) {
            $roleId = $cRow['CUSTOMER_ID'];
        }
    } elseif ($role === 'admin') {
        // Get admin_id for admin role
        $aSql = "SELECT admin_id FROM HUDDERS_ADMIN WHERE user_id = :uid";
        $aStmt = oci_parse($conn, $aSql);
        oci_bind_by_name($aStmt, ':uid', $user['USER_ID']);
        oci_execute($aStmt);
        $aRow = oci_fetch_assoc($aStmt);
        oci_free_statement($aStmt);

        if ($aRow) {
            $roleId = $aRow['ADMIN_ID'];
        }
    }

    // Set session variables
    $_SESSION['user_id'] = $user['USER_ID'];
    $_SESSION['firstname'] = $user['FIRSTNAME'];
    $_SESSION['lastname'] = $user['LASTNAME'];
    $_SESSION['email'] = $user['EMAIL'];
    $_SESSION['role'] = $role;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');

    // Build success response
    $response['success'] = true;
    $response['message'] = 'Login successful!';
    $response['user'] = [
        'user_id'    => $user['USER_ID'],
        'firstname'  => $user['FIRSTNAME'],
        'lastname'   => $user['LASTNAME'],
        'name'       => $user['FIRSTNAME'] . ' ' . $user['LASTNAME'],
        'email'      => $user['EMAIL'],
        'role'       => $role,
        'role_id'    => $roleId,
        'phone'      => $user['PHONE_NUMBER'] ?? '',
        'address'    => $user['ADDRESS'] ?? ''
    ];
    $response['redirect'] = getRedirectUrl($role);

} catch (Exception $e) {
    $response['message'] = 'An error occurred during login: ' . $e->getMessage();
} finally {
    if (isset($conn)) {
        oci_close($conn);
    }
}

echo json_encode($response);
exit;

/**
 * Determine redirect URL based on user role
 */
function getRedirectUrl($role) {
    $role = strtolower($role);
    switch ($role) {
        case 'admin':
            return '../admin/dashboard.html';
        case 'trader':
            return '../trader/dashboard.html';
        case 'customer':
        default:
            return '../customer/profile.html';
    }
}
?>
