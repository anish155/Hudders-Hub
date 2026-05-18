<?php
/**
 * Forgot Password API
 * POST /api/auth/forgot-password.php
 * Sends a password reset token (stored in session for demo)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Only POST allowed.';
    echo json_encode($response); exit;
}

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Valid email address is required.';
    echo json_encode($response); exit;
}

try {
    $sql  = "SELECT user_id, firstname FROM HUDDER_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    // Always return same message for security
    $response['success'] = true;
    $response['message'] = 'If an account with that email exists, a password reset link has been sent.';

    if ($user) {
        // Store token in session (demo — no email server)
        if (session_status() === PHP_SESSION_NONE) session_start();
        $token = bin2hex(random_bytes(32));
        $_SESSION['pwd_reset'][$email] = [
            'token'   => $token,
            'user_id' => (int)$user['USER_ID'],
            'expires' => time() + 3600
        ];
        // In production you'd email the link. For demo, expose token in response.
        $response['dev_token'] = $token;
        $response['dev_email'] = $email;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'An error occurred. Please try again.';
} finally {
    if (isset($conn)) oci_close($conn);
}

echo json_encode($response);
?>
