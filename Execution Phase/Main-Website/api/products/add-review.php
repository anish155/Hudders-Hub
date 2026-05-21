<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;
$review_text = trim($data['review_text'] ?? '');

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit;
}

if (empty($review_text)) {
    echo json_encode(['success' => false, 'error' => 'Review text is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDB();

$checkSql = "SELECT product_id FROM PRODUCT WHERE product_id = :p_pid";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':p_pid', $product_id);
oci_execute($checkStmt);
$product = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$purchaseSql = "SELECT 1 FROM ORDER_PRODUCT op
                JOIN HUDDER_ORDER o ON op.order_id = o.order_id
                WHERE op.product_id = :p_pid2
                  AND o.user_id = :p_uid
                  AND o.status IN ('Completed', 'Delivered', 'Collected', 'Ready')";
$purchaseStmt = oci_parse($conn, $purchaseSql);
oci_bind_by_name($purchaseStmt, ':p_pid2', $product_id);
oci_bind_by_name($purchaseStmt, ':p_uid', $user_id);
oci_execute($purchaseStmt);
$hasPurchase = oci_fetch_assoc($purchaseStmt);
oci_free_statement($purchaseStmt);

if (!$hasPurchase) {
    echo json_encode(['success' => false, 'error' => 'You can only review products you have purchased and collected']);
    exit;
}

$existingSql = "SELECT review_id FROM REVIEW WHERE product_id = :p_pid3 AND user_id = :p_uid2";
$existingStmt = oci_parse($conn, $existingSql);
oci_bind_by_name($existingStmt, ':p_pid3', $product_id);
oci_bind_by_name($existingStmt, ':p_uid2', $user_id);
oci_execute($existingStmt);
$existing = oci_fetch_assoc($existingStmt);
oci_free_statement($existingStmt);

if ($existing) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this product']);
    exit;
}

$insertSql = "INSERT INTO REVIEW (review_id, review_text, rating, user_id, product_id)
              VALUES (seq_Review.NEXTVAL, :p_text, :p_rating, :p_uid3, :p_pid4)";
$insertStmt = oci_parse($conn, $insertSql);
oci_bind_by_name($insertStmt, ':p_text', $review_text);
oci_bind_by_name($insertStmt, ':p_rating', $rating);
oci_bind_by_name($insertStmt, ':p_uid3', $user_id);
oci_bind_by_name($insertStmt, ':p_pid4', $product_id);

if (oci_execute($insertStmt, OCI_NO_AUTO_COMMIT)) {
    oci_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    $e = oci_error($insertStmt);
    oci_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($insertStmt);
oci_close($conn);
