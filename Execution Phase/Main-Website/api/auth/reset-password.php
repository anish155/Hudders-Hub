<?php
/**
 * Reset Password API
 * Endpoint: POST /api/auth/reset-password.php
 * Resets the user's password using a valid token
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

// Required fields
if (empty($data['email']) || empty($data['token']) || empty($data['new_password'])) {
    $response['message'] = 'Email, token, and new password are required.';
    echo json_encode($response);
    exit;
}

$email      = trim($data['email']);
$token      = trim($data['token']);
$newPassword = $data['new_password'];

// Validate password
if (strlen($newPassword) < 6) {
    $response['message'] = 'Password must be at least 6 characters long.';
    echo json_encode($response);
    exit;
}

try {
    // Validate token (session-based)
    session_start();
    $tokens = $_SESSION['password_reset_tokens'] ?? [];

    if (!isset($tokens[$email])) {
        $response['message'] = 'Invalid or expired token.';
        echo json_encode($response);
        exit;
    }

    $storedToken = $tokens[$email];

    if ($storedToken['token'] !== $token) {
        $response['message'] = 'Invalid token provided.';
        echo json_encode($response);
        exit;
    }

    if (strtotime($storedToken['expires']) < time()) {
        $response['message'] = 'Token has expired. Please request a new reset link.';
        // Remove expired token
        unset($_SESSION['password_reset_tokens'][$email]);
        echo json_encode($response);
        exit;
    }

    $userId = $storedToken['user_id'];

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update password in database
    $updateSql = "UPDATE HUDDERS_USER SET user_password = :password WHERE user_id = :user_id";
    $updateStmt = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStmt, ':password', $hashedPassword);
    oci_bind_by_name($updateStmt, ':user_id', $userId);

    $result = oci_execute($updateStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($updateStmt);

    if ($result) {
        oci_commit($conn);
        // Remove used token
        unset($_SESSION['password_reset_tokens'][$email]);

        $response['success'] = true;
        $response['message'] = 'Your password has been reset successfully. You can now log in with your new password.';
    } else {
        oci_rollback($conn);
        $e = oci_error($updateStmt);
        $response['message'] = 'Failed to reset password. Please try again.';
        if ($e) {
            $response['message'] .= ' Error: ' . $e['message'];
        }
    }

} catch (Exception $e) {
    if (isset($conn)) {
        oci_rollback($conn);
    }
    $response['message'] = 'An error occurred. Please try again later.';
} finally {
    if (isset($conn)) {
        oci_close($conn);
    }
}

echo json_encode($response);
?>
