<?php
require_once '../../config/database.php';

$conn = getDB();
$product_id = $_GET['product_id'] ?? null;
$sort = $_GET['sort'] ?? 'newest';

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

$orderBy = $sort === 'oldest' ? 'ASC' : 'DESC';

$sql = "SELECT r.review_id, r.review_text, r.rating, r.created_at,
               u.firstname, u.lastname
        FROM REVIEW r
        JOIN HUDDER_USER u ON r.user_id = u.user_id
        WHERE r.product_id = :product_id
        ORDER BY r.created_at $orderBy";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':product_id', $product_id);
oci_execute($stmt);

$reviews = [];
while ($row = oci_fetch_assoc($stmt)) {
    // Mask the name - show first letter only
    $row['REVIEWER'] = substr($row['FIRSTNAME'], 0, 1) . '. ' . substr($row['LASTNAME'], 0, 1) . '.';
    unset($row['FIRSTNAME'], $row['LASTNAME']);
    $reviews[] = $row;
}

// Get average rating
$avgSql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
           FROM REVIEW WHERE product_id = :product_id";
$avgStmt = oci_parse($conn, $avgSql);
oci_bind_by_name($avgStmt, ':product_id', $product_id);
oci_execute($avgStmt);
$stats = oci_fetch_assoc($avgStmt);

oci_free_statement($stmt);
oci_free_statement($avgStmt);
oci_close($conn);

echo json_encode([
    'success' => true, 
    'data' => $reviews,
    'average_rating' => round($stats['AVG_RATING'] ?? 0, 1),
    'total_reviews' => $stats['TOTAL_REVIEWS'] ?? 0
]);