<?php
/**
 * Verify Email API
 * Endpoint: GET /api/auth/verify-email.php?token=xxx&email=xxx
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification link.']);
    exit;
}

try {
    $sql = "SELECT user_id, firstname, verification_token, verified_at FROM HUDDER_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if (!empty($user['VERIFIED_AT'])) {
        echo json_encode(['success' => true, 'message' => 'Email is already verified. You can log in.']);
        exit;
    }

    if ($user['VERIFICATION_TOKEN'] !== $token) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification token.']);
        exit;
    }

    // Clear the token and mark as verified
    $updateSql = "UPDATE HUDDER_USER SET verification_token = NULL, verified_at = SYSDATE WHERE user_id = :user_id";
    $updateStmt = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStmt, ':user_id', $user['USER_ID']);
    
    if (oci_execute($updateStmt)) {
        echo json_encode(['success' => true, 'message' => 'Email verified successfully! You can now log in.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify email.']);
    }
    oci_free_statement($updateStmt);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>