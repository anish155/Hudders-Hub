<?php
/**
 * Reset Password API
 * POST /api/auth/reset-password.php
 * Resets password using a valid session token
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

$data        = json_decode(file_get_contents('php://input'), true);
$email       = trim($data['email']        ?? '');
$token       = trim($data['token']        ?? '');
$newPassword = $data['new_password']      ?? '';

if (!$email || !$token || !$newPassword) {
    $response['message'] = 'Email, token, and new password are required.';
    echo json_encode($response); exit;
}

// VARCHAR2(20) max
if (strlen($newPassword) < 4) {
    $response['message'] = 'Password must be at least 4 characters.';
    echo json_encode($response); exit;
}
$newPassword = substr($newPassword, 0, 20);

if (session_status() === PHP_SESSION_NONE) session_start();

$resets = $_SESSION['pwd_reset'] ?? [];

if (!isset($resets[$email])) {
    $response['message'] = 'Invalid or expired reset token.';
    echo json_encode($response); exit;
}

$entry = $resets[$email];

if ($entry['token'] !== $token) {
    $response['message'] = 'Invalid reset token.';
    echo json_encode($response); exit;
}

if (time() > $entry['expires']) {
    unset($_SESSION['pwd_reset'][$email]);
    $response['message'] = 'Reset token has expired. Please request a new one.';
    echo json_encode($response); exit;
}

$userId = $entry['user_id'];

try {
    $sql  = "UPDATE HUDDER_USER SET user_password = :pwd WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':pwd', $newPassword);
    oci_bind_by_name($stmt, ':user_id', $userId);
    $ok   = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($stmt);

    if ($ok) {
        unset($_SESSION['pwd_reset'][$email]);
        $response['success'] = true;
        $response['message'] = 'Password reset successfully. You can now log in.';
    } else {
        $e = oci_error($conn);
        $response['message'] = 'Failed to update password: ' . ($e['message'] ?? 'Unknown error');
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
} finally {
    if (isset($conn)) oci_close($conn);
}

echo json_encode($response);
?>
