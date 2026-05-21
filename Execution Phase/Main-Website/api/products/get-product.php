<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();

$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

if ($productId <= 0) {
	http_response_code(400);
	echo json_encode(['success' => false, 'error' => 'product_id is required']);
	exit;
}

// CLOBs cannot be in GROUP BY — fetch product_details separately
$sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.min_order, p.max_order,
			   p.reorder_label, p.allergen_info, p.shop_id, p.category_id, p.status,
			   p.unit, p.dietary_tags,
			   s.name AS shop_name, s.shop_type, s.location AS shop_location,
			   c.category_name,
			   NVL(ROUND(AVG(r.rating), 2), 0) AS avg_rating,
			   COUNT(r.review_id) AS review_count,
			   NVL(d.discount_percent, 0) AS discount_percent
		FROM PRODUCT p
		JOIN SHOP s ON p.shop_id = s.shop_id
		LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
		LEFT JOIN REVIEW r ON r.product_id = p.product_id
		LEFT JOIN (
			SELECT product_id, MAX(discount_percent) AS discount_percent
			FROM DISCOUNT WHERE valid_until >= TRUNC(SYSDATE)
			GROUP BY product_id
		) d ON d.product_id = p.product_id
		WHERE p.product_id = :product_id
		GROUP BY p.product_id, p.name, p.description, p.price, p.stock, p.min_order,
				 p.max_order, p.reorder_label, p.allergen_info, p.shop_id, p.category_id,
				 p.status, p.unit, p.dietary_tags,
				 s.name, s.shop_type, s.location, c.category_name, d.discount_percent";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':product_id', $productId);

if (!oci_execute($stmt)) {
	$e = oci_error($stmt);
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e['message']]);
	oci_free_statement($stmt);
	oci_close($conn);
	exit;
}

$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
	http_response_code(404);
	echo json_encode(['success' => false, 'error' => 'Product not found']);
	exit;
}

// Fetch CLOB product_details separately (can't be in GROUP BY)
$row['PRODUCT_DETAILS'] = '';
$clobStmt = oci_parse($conn, 'SELECT product_details FROM PRODUCT WHERE product_id = :pid');
oci_bind_by_name($clobStmt, ':pid', $productId);
oci_execute($clobStmt);
$clobRow = oci_fetch_assoc($clobStmt);
if ($clobRow && isset($clobRow['PRODUCT_DETAILS'])) {
	$clob = $clobRow['PRODUCT_DETAILS'];
	$row['PRODUCT_DETAILS'] = is_object($clob) ? $clob->load() : (string)($clob ?? '');
}
oci_free_statement($clobStmt);

// Fetch image URLs
$imgStmt = oci_parse($conn, 'SELECT image_url FROM PRODUCT_IMAGE WHERE product_id = :pid ORDER BY display_order');
oci_bind_by_name($imgStmt, ':pid', $productId);
oci_execute($imgStmt);
$images = [];
while ($imgRow = oci_fetch_assoc($imgStmt)) {
	if ($imgRow['IMAGE_URL']) {
		$images[] = $imgRow['IMAGE_URL'];
	}
}
oci_free_statement($imgStmt);
$row['IMAGES'] = $images;

oci_close($conn);

echo json_encode(['success' => true, 'data' => $row]);
?>
