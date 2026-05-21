<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$review_id = $data['review_id'] ?? null;

if (!$review_id) {
    echo json_encode(['success' => false, 'error' => 'Review ID required']);
    exit;
}

// Verify the review belongs to the user
$checkSql = "SELECT review_id FROM REVIEW WHERE review_id = :p_review_id AND user_id = :p_user_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':p_review_id', $review_id);
oci_bind_by_name($checkStmt, ':p_user_id', $user_id);
oci_execute($checkStmt);
$review = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if (!$review) {
    echo json_encode(['success' => false, 'error' => 'Review not found or you do not have permission to delete it']);
    exit;
}

$deleteSql = "DELETE FROM REVIEW WHERE review_id = :p_review_id";
$deleteStmt = oci_parse($conn, $deleteSql);
oci_bind_by_name($deleteStmt, ':p_review_id', $review_id);

if (oci_execute($deleteStmt)) {
    echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
} else {
    $e = oci_error($deleteStmt);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($deleteStmt);
oci_close($conn);
