<?php
/**
 * Forgot Password API
 * Endpoint: POST /api/auth/forgot-password.php
 * Sends a password reset email (mock implementation without email server)
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

if (!$data || empty($data['email'])) {
    $response['message'] = 'Email is required.';
    echo json_encode($response);
    exit;
}

$email = trim($data['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

try {
    // Check if email exists in HUDDERS_USER
    $sql = "SELECT user_id, firstname, lastname, email FROM HUDDERS_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        // For security, do NOT reveal if email exists or not.
        // Return same success message regardless.
        $response['success'] = true;
        $response['message'] = 'If an account with that email exists, a password reset link has been sent.';
        echo json_encode($response);
        exit;
    }

    // Generate a secure random token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store the token (using a simple table or session-based approach)
    // For this implementation, we use a session-based temporary storage
    // In production, create a PASSWORD_RESET_TOKENS table
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['password_reset_tokens'] = $_SESSION['password_reset_tokens'] ?? [];
    $_SESSION['password_reset_tokens'][$email] = [
        'token' => $token,
        'expires' => $expires,
        'user_id' => $user['USER_ID']
    ];

    $response['success'] = true;
    $response['message'] = 'If an account with that email exists, a password reset link has been sent.';
    // Debug: optionally expose token for testing (remove in production)
    // $response['token'] = $token;

} catch (Exception $e) {
    $response['message'] = 'An error occurred. Please try again later.';
} finally {
    if (isset($conn)) {
        oci_close($conn);
    }
}

echo json_encode($response);
?>
