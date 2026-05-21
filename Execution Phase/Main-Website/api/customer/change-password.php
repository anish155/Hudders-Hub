<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$current_password = $data['current_password'] ?? null;
$new_password = $data['new_password'] ?? null;

if (!$current_password || !$new_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current and new password are required']);
    exit;
}

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

// Get current password from DB
$checkSql = "SELECT user_password FROM HUDDER_USER WHERE user_id = :user_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_execute($checkStmt);
$user = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($user['USER_PASSWORD'] !== $current_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    oci_close($conn);
    exit;
}

// Check if new password matches any old password in history
$historySql = "SELECT old_password FROM PASSWORD_HISTORY WHERE user_id = :user_id ORDER BY changed_at DESC";
$historyStmt = oci_parse($conn, $historySql);
oci_bind_by_name($historyStmt, ':user_id', $user_id);
oci_execute($historyStmt);

$usedBefore = false;
while ($row = oci_fetch_assoc($historyStmt)) {
    if ($row['OLD_PASSWORD'] === $new_password) {
        $usedBefore = true;
        break;
    }
}
oci_free_statement($historyStmt);

if ($usedBefore) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'You have used this password before. Please choose a different one.']);
    oci_close($conn);
    exit;
}

// Begin transaction
oci_execute(oci_parse($conn, "BEGIN NULL; END;"), OCI_NO_AUTO_COMMIT);

// Save current password to history before changing
$insertHistorySql = "INSERT INTO PASSWORD_HISTORY (user_id, old_password) VALUES (:user_id, :old_password)";
$insertHistoryStmt = oci_parse($conn, $insertHistorySql);
oci_bind_by_name($insertHistoryStmt, ':user_id', $user_id);
oci_bind_by_name($insertHistoryStmt, ':old_password', $current_password);
$historyOk = @oci_execute($insertHistoryStmt, OCI_NO_AUTO_COMMIT);
oci_free_statement($insertHistoryStmt);

if (!$historyOk) {
    oci_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update password history']);
    oci_close($conn);
    exit;
}

// Update password
$sql = "UPDATE HUDDER_USER SET user_password = :new_password WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':new_password', $new_password);
oci_bind_by_name($stmt, ':user_id', $user_id);

$result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
if ($result) {
    oci_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} else {
    oci_rollback($conn);
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
