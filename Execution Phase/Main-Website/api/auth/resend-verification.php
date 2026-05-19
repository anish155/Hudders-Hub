<?php
/**
 * Resend Verification Email API
 * Endpoint: POST /api/auth/resend-verification.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../config/mailer.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$email = trim($data['email']);

try {
    $sql = "SELECT user_id, firstname, email, verification_token, verified_at FROM HUDDER_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;
    }

    if (!empty($user['VERIFIED_AT'])) {
        echo json_encode(['success' => true, 'message' => 'Email is already verified. You can log in.']);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $updateSql = "UPDATE HUDDER_USER SET verification_token = :token, verified_at = NULL WHERE user_id = :user_id";
    $updateStmt = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStmt, ':token', $token);
    oci_bind_by_name($updateStmt, ':user_id', $user['USER_ID']);
    if (!oci_execute($updateStmt, OCI_COMMIT_ON_SUCCESS)) {
        $e = oci_error($updateStmt);
        echo json_encode(['success' => false, 'message' => $e['message'] ?? 'Failed to update verification token']);
        exit;
    }
    oci_free_statement($updateStmt);

    $verify_link = huddershub_base_url() . '/public/verify-email.html?token=' . urlencode($token) . '&email=' . urlencode($email);
    try {
        huddershub_send_verification_email($email, $user['FIRSTNAME'], $verify_link);
    } catch (Exception $mailError) {
        error_log('Verification resend failed: ' . $mailError->getMessage());
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Verification email sent.',
        'verification_link' => $verify_link
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>