<?php
/**
 * Reset Password API
 * POST /api/auth/reset-password.php
 * Resets password using a valid session token
 * Checks password history to prevent reuse
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

$sql = "SELECT user_id FROM HUDDER_USER
    WHERE email = :email
          AND password_reset_token = :token
          AND (password_reset_expires IS NULL OR password_reset_expires >= SYSDATE)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':email', $email);
oci_bind_by_name($stmt, ':token', $token);
oci_execute($stmt);
$user = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$user) {
    $response['message'] = 'Invalid or expired reset token.';
    echo json_encode($response); exit;
}

$userId = $user['USER_ID'];

try {
    // Get current password
    $currentSql = "SELECT user_password FROM HUDDER_USER WHERE user_id = :user_id";
    $currentStmt = oci_parse($conn, $currentSql);
    oci_bind_by_name($currentStmt, ':user_id', $userId);
    oci_execute($currentStmt);
    $currentUser = oci_fetch_assoc($currentStmt);
    oci_free_statement($currentStmt);

    $currentPassword = $currentUser['USER_PASSWORD'] ?? '';

    // Check if new password matches current password
    if ($currentPassword === $newPassword) {
        $response['message'] = 'You cannot use your current password.';
        echo json_encode($response);
        exit;
    }

    // Check password history
    $historySql = "SELECT old_password FROM PASSWORD_HISTORY WHERE user_id = :user_id ORDER BY changed_at DESC";
    $historyStmt = oci_parse($conn, $historySql);
    oci_bind_by_name($historyStmt, ':user_id', $userId);
    oci_execute($historyStmt);

    $usedBefore = false;
    while ($row = oci_fetch_assoc($historyStmt)) {
        if ($row['OLD_PASSWORD'] === $newPassword) {
            $usedBefore = true;
            break;
        }
    }
    oci_free_statement($historyStmt);

    if ($usedBefore) {
        $response['message'] = 'You have used this password before. Please choose a different one.';
        echo json_encode($response);
        exit;
    }

    // Save current password to history
    $insertHistorySql = "INSERT INTO PASSWORD_HISTORY (user_id, old_password) VALUES (:user_id, :old_password)";
    $insertHistoryStmt = oci_parse($conn, $insertHistorySql);
    oci_bind_by_name($insertHistoryStmt, ':user_id', $userId);
    oci_bind_by_name($insertHistoryStmt, ':old_password', $currentPassword);
    oci_execute($insertHistoryStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($insertHistoryStmt);

    // Update password
    $sql  = "UPDATE HUDDER_USER SET user_password = :pwd, password_reset_token = NULL, password_reset_expires = NULL WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':pwd', $newPassword);
    oci_bind_by_name($stmt, ':user_id', $userId);
    $ok   = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stmt);

    if ($ok) {
        oci_commit($conn);
        $response['success'] = true;
        $response['message'] = 'Password reset successfully. You can now log in.';
    } else {
        oci_rollback($conn);
        $e = oci_error($conn);
        $response['message'] = 'Failed to update password: ' . ($e['message'] ?? 'Unknown error');
    }

} catch (Exception $e) {
    oci_rollback($conn);
    $response['message'] = 'An error occurred: ' . $e->getMessage();
} finally {
    if (isset($conn)) oci_close($conn);
}

echo json_encode($response);
?>
