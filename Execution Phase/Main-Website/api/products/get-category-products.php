<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/database.php';

$conn = getDB();

$shop = isset($_GET['shop']) ? strtolower(trim($_GET['shop'])) : '';
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$sub  = isset($_GET['sub'])  ? strtolower(trim($_GET['sub']))  : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$inStock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';

$valid_shops = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];
$valid_types = ['veg', 'non-veg', 'vegan', 'gluten-free', 'fresh-today'];
$valid_sorts = ['name', 'price-low', 'price-high', 'rating', 'newest'];

if ($shop && !in_array($shop, $valid_shops)) $shop = '';
if ($type && !in_array($type, $valid_types)) $type = '';
if (!in_array($sort, $valid_sorts)) $sort = 'name';

$perPage = 15;
$offset  = ($page - 1) * $perPage;

$base = "SELECT p.product_id, p.name, p.description, p.price, p.stock,
                p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                p.unit, p.status,
                s.shop_id, s.name AS shop_name, s.shop_type,
                pc.category_name,
                NVL(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                COUNT(r.review_id) AS review_count,
                NVL(d.discount_percent, 0) AS discount_percent,
                pi.image_url
         FROM PRODUCT p
         JOIN SHOP s ON p.shop_id = s.shop_id
         LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
         LEFT JOIN REVIEW r ON r.product_id = p.product_id
         LEFT JOIN DISCOUNT d ON d.product_id = p.product_id AND SYSDATE <= d.valid_until
         LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
         WHERE p.status = 'Active'";

$w    = "";
$wCt  = "";
$params = [];

if ($shop) {
    $shopUpper = strtoupper($shop);
    $w   .= " AND UPPER(s.shop_type) = :shop_type";
    $wCt .= " AND UPPER(s.shop_type) = :shop_type";
    $params[':shop_type'] = $shopUpper;
}
if ($type === 'veg') {
    $w   .= " AND (INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', 'VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN') > 0)";
    $wCt .= " AND (INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', 'VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN') > 0)";
} elseif ($type === 'vegan') {
    $w   .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
    $wCt .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
} elseif ($type === 'gluten-free') {
    $w   .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
    $wCt .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
} elseif ($type === 'non-veg') {
    // Non-veg is anything that is NOT explicitly Vegetarian (but could be Non-Vegetarian) and NOT Vegan
    $w   .= " AND (p.dietary_tags IS NULL 
                OR (
                    (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 OR INSTR(UPPER(p.dietary_tags), 'NON-VEGETARIAN') > 0)
                    AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0
                )
            )";
    $wCt .= " AND (p.dietary_tags IS NULL 
                OR (
                    (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 OR INSTR(UPPER(p.dietary_tags), 'NON-VEGETARIAN') > 0)
                    AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0
                )
            )";
}
if ($sub) {
    $subUpper = strtoupper($sub);
    $w   .= " AND UPPER(pc.category_name) = :subcategory";
    $wCt .= " AND UPPER(pc.category_name) = :subcategory";
    $params[':subcategory'] = $subUpper;
}
if ($inStock) {
    $w   .= " AND p.stock > 0";
    $wCt .= " AND p.stock > 0";
}

$groupBy = " GROUP BY p.product_id, p.name, p.description, p.price, p.stock,
              p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
              p.unit, p.status, s.shop_id, s.name, s.shop_type, pc.category_name,
              d.discount_percent, pi.image_url";

$countSql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM PRODUCT p"
           . " JOIN SHOP s ON p.shop_id = s.shop_id"
           . " LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id"
           . " LEFT JOIN REVIEW r ON r.product_id = p.product_id"
           . " WHERE p.status = 'Active'" . $wCt;

$countStmt = oci_parse($conn, $countSql);
foreach ($params as $key => $value) {
    oci_bind_by_name($countStmt, $key, $value);
}
oci_execute($countStmt);
$countRow = oci_fetch_assoc($countStmt);
$totalCount = $countRow ? intval($countRow['TOTAL']) : 0;
oci_free_statement($countStmt);

$sql = $base . $w . $groupBy;

switch ($sort) {
    case 'price-low':
        $sql .= " ORDER BY p.price ASC, p.name ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY p.price DESC, p.name ASC";
        break;
    case 'rating':
        $sql .= " ORDER BY avg_rating DESC, p.name ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.product_id DESC";
        break;
    default:
        $sql .= " ORDER BY p.name ASC";
}

$sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

$stmt = oci_parse($conn, $sql);
foreach ($params as $key => $value) {
    oci_bind_by_name($stmt, $key, $value);
}
oci_bind_by_name($stmt, ':offset', $offset, -1, SQLT_INT);
oci_bind_by_name($stmt, ':limit', $perPage, -1, SQLT_INT);
oci_execute($stmt);

$products = [];
while ($row = oci_fetch_assoc($stmt)) {
    $products[] = $row;
}
oci_free_statement($stmt);

oci_close($conn);

echo json_encode([
    'success' => true,
    'data' => $products,
    'total' => $totalCount,
    'page' => $page,
    'per_page' => $perPage,
    'has_more' => ($offset + count($products)) < $totalCount
]);
