<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();
$product_id = $_GET['product_id'] ?? null;
$sort = $_GET['sort'] ?? 'newest';
$star = isset($_GET['star']) && $_GET['star'] !== '' ? intval($_GET['star']) : null;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

// Determine ORDER BY
switch (strtolower($sort)) {
    case 'highest':
        $orderBy = 'r.rating DESC, r.review_id DESC';
        break;
    case 'oldest':
        $orderBy = 'r.review_id ASC';
        break;
    default:
        $orderBy = 'r.review_id DESC';
}

// Base query
$where = "r.product_id = :product_id";
if ($star && $star >=1 && $star <=5) {
    $where .= " AND r.rating = :star";
}

// Build SQL with pagination (Oracle OFFSET/FETCH)
 $sql = "SELECT r.review_id, r.review_text, r.rating, NULL AS created_at_iso,
               u.firstname, u.lastname,
               CASE WHEN EXISTS (
                   SELECT 1 FROM ORDER_PRODUCT op
                   JOIN HUDDER_ORDER o ON op.order_id = o.order_id
                   WHERE op.product_id = r.product_id
                     AND o.user_id = r.user_id
                     AND (LOWER(o.status) LIKE '%complete%' OR LOWER(o.status) LIKE '%collect%')
               ) THEN 1 ELSE 0 END AS VERIFIED,
               r.user_id
        FROM REVIEW r
        JOIN HUDDER_USER u ON r.user_id = u.user_id
        WHERE $where
        ORDER BY $orderBy
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

 $stmt = oci_parse($conn, $sql);
 if (!$stmt) {
     $e = oci_error($conn);
     echo json_encode(['success' => false, 'error' => $e['message'] ?? 'Failed to prepare query']);
     exit;
 }
 oci_bind_by_name($stmt, ':product_id', $product_id);
 oci_bind_by_name($stmt, ':offset', $offset);
 oci_bind_by_name($stmt, ':limit', $limit);
 if ($star && $star >=1 && $star <=5) oci_bind_by_name($stmt, ':star', $star);
 if (!@oci_execute($stmt)) {
     $e = oci_error($stmt) ?: oci_error($conn);
     echo json_encode(['success' => false, 'error' => $e['message'] ?? 'Failed to execute query']);
     exit;
 }

$reviews = [];
while ($row = oci_fetch_assoc($stmt)) {
    // Mask the name - show first letter only
    $reviewer = (isset($row['FIRSTNAME']) ? substr($row['FIRSTNAME'], 0, 1) : '?') . '. ' . (isset($row['LASTNAME']) ? substr($row['LASTNAME'], 0, 1) . '.' : '');
    $row['REVIEWER'] = $reviewer;
    $row['CREATED_AT'] = $row['CREATED_AT_ISO'] ?? null;
    unset($row['FIRSTNAME'], $row['LASTNAME'], $row['CREATED_AT_ISO']);
    // is_own flag if logged in
    $row['IS_OWN'] = false;
    if (isLoggedIn() && getUserId() && $row['USER_ID'] == getUserId()) $row['IS_OWN'] = true;
    $reviews[] = $row;
}

// Get aggregate stats (no limit)
$avgSql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
           FROM REVIEW WHERE product_id = :product_id";
 $avgStmt = oci_parse($conn, $avgSql);
 if (!$avgStmt) {
     $e = oci_error($conn);
     echo json_encode(['success' => false, 'error' => $e['message'] ?? 'Failed to prepare aggregate query']);
     exit;
 }
 oci_bind_by_name($avgStmt, ':product_id', $product_id);
 if (!@oci_execute($avgStmt)) {
     $e = oci_error($avgStmt) ?: oci_error($conn);
     echo json_encode(['success' => false, 'error' => $e['message'] ?? 'Failed to execute aggregate query']);
     exit;
 }
 $stats = oci_fetch_assoc($avgStmt);

oci_free_statement($stmt);
oci_free_statement($avgStmt);
oci_close($conn);

echo json_encode([
    'success' => true,
    'data' => $reviews,
    'average_rating' => round($stats['AVG_RATING'] ?? 0, 1),
    'total_reviews' => $stats['TOTAL_REVIEWS'] ?? 0,
    'offset' => $offset,
    'limit' => $limit
]);