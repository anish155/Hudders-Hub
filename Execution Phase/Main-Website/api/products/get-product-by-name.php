<?php
ini_set('display_errors', 0);
error_reporting(0);
// api/products/get-product-by-name.php
// Public endpoint: looks up a product by its name across ALL active products.
// Returns the first matching product_id so that cart/wishlist fallback lookup works
// without requiring a shop_id.
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$conn = getDB();

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'name parameter is required']);
    oci_close($conn);
    exit;
}

// Fetch first active match (case-insensitive) — we only need the product_id
$sql = "SELECT p.product_id
          FROM PRODUCT p
         WHERE LOWER(p.name) = LOWER(:name)
           AND p.status = 'Active'
         ORDER BY p.product_id DESC
         FETCH FIRST 1 ROWS ONLY";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':name', $name);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);

if ($row) {
    echo json_encode([
        'success'    => true,
        'product_id' => (int)$row['PRODUCT_ID']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => 'Product not found'
    ]);
}

oci_free_statement($stmt);
oci_close($conn);
