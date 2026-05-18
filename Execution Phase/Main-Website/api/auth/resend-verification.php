<?php
/**
 * Resend Verification Email API
 * Endpoint: POST /api/auth/resend-verification.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$email = trim($data['email']);

try {
    $sql = "SELECT user_id, firstname, verification_token FROM HUDDER_USER WHERE email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;
    }

    if (empty($user['VERIFICATION_TOKEN'])) {
        echo json_encode(['success' => true, 'message' => 'Email is already verified. You can log in.']);
        exit;
    }

    $token = $user['VERIFICATION_TOKEN'];
    $verify_link = BASE_URL . '/public/verify-email.html?token=' . $token . '&email=' . urlencode($email);

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