<?php
ini_set('display_errors', 0);
error_reporting(0);
// api/products/get-homepage-products.php
// Returns all Active products with shop + category info for the homepage.
// Returns s.filename and s.mimetype so the frontend can pick the best available product image.
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 12;

$sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock,
                p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                p.unit, p.status,
                s.name AS shop_name, s.shop_type,
                pi.image_url,
                c.category_name,
                NVL(d.discount_percent, 0) AS discount_percent
         FROM PRODUCT p
         JOIN SHOP s ON p.shop_id = s.shop_id
         LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
         LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
         LEFT JOIN (
             SELECT product_id, MAX(discount_percent) AS discount_percent
             FROM DISCOUNT
             WHERE valid_until >= TRUNC(SYSDATE)
             GROUP BY product_id
         ) d ON d.product_id = p.product_id
          WHERE p.status = 'Active'
          ORDER BY DBMS_RANDOM.VALUE
          FETCH FIRST :limit ROWS ONLY";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':limit', $limit);
oci_execute($stmt);

$results = [];
while ($row = oci_fetch_assoc($stmt)) {
    $row['DISCOUNT_PERCENT'] = (float)$row['DISCOUNT_PERCENT'];
    $results[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $results]);
