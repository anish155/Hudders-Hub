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
require_once '../../config/config.php';
require_once '../../config/mailer.php';

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
    $sql  = "SELECT user_id, firstname, email FROM HUDDER_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    // Always return same message for security
    $response['success'] = true;
    $response['message'] = 'If an account with that email exists, a password reset link has been sent.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiresSql = "UPDATE HUDDER_USER
                       SET password_reset_token = :token,
                           password_reset_expires = (SYSDATE + (1/24))
                       WHERE user_id = :user_id";
        $expStmt = oci_parse($conn, $expiresSql);
        oci_bind_by_name($expStmt, ':token', $token);
        oci_bind_by_name($expStmt, ':user_id', $user['USER_ID']);
        if (!oci_execute($expStmt, OCI_COMMIT_ON_SUCCESS)) {
            $e = oci_error($expStmt);
            echo json_encode(['success' => false, 'message' => $e['message'] ?? 'Could not store reset token']);
            exit;
        }
        oci_free_statement($expStmt);

        $resetLink = huddershub_base_url() . '/public/reset-password.html?token=' . urlencode($token) . '&email=' . urlencode($email);
        try {
            huddershub_send_reset_email($email, $user['FIRSTNAME'], $resetLink);
        } catch (Exception $mailError) {
            error_log('Reset email failed: ' . $mailError->getMessage());
        }
        $response['reset_link'] = $resetLink;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'An error occurred. Please try again.';
} finally {
    if (isset($conn)) oci_close($conn);
}

echo json_encode($response);
?>
