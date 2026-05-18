<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$product_id = $data['product_id'] ?? null;
$rating = $data['rating'] ?? null;
$review_text = $data['review_text'] ?? null;

if (!$product_id || !$rating || !$review_text) {
    echo json_encode(['success' => false, 'error' => 'Product ID, rating, and review text are required']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit;
}

// Check if user has purchased this product (only after collecting order)
$checkSql = "SELECT COUNT(*) as cnt FROM ORDER_PRODUCT op
             JOIN HUDDER_ORDER o ON op.order_id = o.order_id
             WHERE op.product_id = :product_id AND o.user_id = :user_id
             AND o.status = 'Completed'";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':product_id', $product_id);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_execute($checkStmt);
$purchased = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($purchased['CNT'] == 0) {
    echo json_encode(['success' => false, 'error' => 'You can only review products you have purchased and collected']);
    exit;
}

// Check if user already reviewed this product
$existingSql = "SELECT review_id FROM REVIEW WHERE product_id = :product_id AND user_id = :user_id";
$existingStmt = oci_parse($conn, $existingSql);
oci_bind_by_name($existingStmt, ':product_id', $product_id);
oci_bind_by_name($existingStmt, ':user_id', $user_id);
oci_execute($existingStmt);
$existing = oci_fetch_assoc($existingStmt);

if ($existing) {
    // Update existing review
    $updateSql = "UPDATE REVIEW SET review_text = :text, rating = :rating WHERE review_id = :id";
    $updateStmt = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStmt, ':text', $review_text);
    oci_bind_by_name($updateStmt, ':rating', $rating);
    oci_bind_by_name($updateStmt, ':id', $existing['REVIEW_ID']);

    if (oci_execute($updateStmt)) {
        echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
    } else {
        $e = oci_error($updateStmt);
        echo json_encode(['success' => false, 'error' => $e['message']]);
    }
    oci_free_statement($updateStmt);
} else {
    // Insert new review
    $insertSql = "INSERT INTO REVIEW (review_text, rating, user_id, product_id) VALUES (:text, :rating, :user_id, :product_id)";
    $insertStmt = oci_parse($conn, $insertSql);
    oci_bind_by_name($insertStmt, ':text', $review_text);
    oci_bind_by_name($insertStmt, ':rating', $rating);
    oci_bind_by_name($insertStmt, ':user_id', $user_id);
    oci_bind_by_name($insertStmt, ':product_id', $product_id);

    if (oci_execute($insertStmt)) {
        echo json_encode(['success' => true, 'message' => 'Review added successfully']);
    } else {
        $e = oci_error($insertStmt);
        echo json_encode(['success' => false, 'error' => $e['message']]);
    }
    oci_free_statement($insertStmt);
}

oci_close($conn);
