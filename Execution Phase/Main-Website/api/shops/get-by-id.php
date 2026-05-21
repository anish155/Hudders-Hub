<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/database.php';

$shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$limit  = isset($_GET['limit'])   ? min((int)$_GET['limit'], 40) : 20;
$offset = isset($_GET['offset'])  ? (int)$_GET['offset'] : 0;

if (!$shopId) {
    echo json_encode(['success' => false, 'error' => 'shop_id required']);
    exit;
}

$conn = getDB();

// ── SHOP + TRADER info ──────────────────────────────────────────────────────
$sql = "
    SELECT s.shop_id, s.name AS shop_name, s.description AS shop_description,
           s.location, s.contact_number, s.shop_type,
           s.shop_logo, s.mimetype, s.filename,
           NVL(s.collection_wed, 1) AS collection_wed,
           NVL(s.collection_thu, 1) AS collection_thu,
           NVL(s.collection_fri, 1) AS collection_fri,
           u.user_id, u.firstname AS trader_firstname,
           u.lastname  AS trader_lastname,
           u.email     AS trader_email,
           u.phone_number AS trader_phone,
           t.status AS trader_status,
           (SELECT COUNT(*) FROM PRODUCT p WHERE p.shop_id = s.shop_id AND p.status = 'Active') AS product_count,
           (SELECT ROUND(AVG(r.rating), 1) FROM REVIEW r JOIN PRODUCT p ON r.product_id = p.product_id WHERE p.shop_id = s.shop_id) AS avg_rating
    FROM SHOP  s
    JOIN HUDDER_USER u ON s.user_id = u.user_id
    JOIN TRADER     t ON t.user_id = u.user_id
    WHERE s.shop_id = :shop_id
    AND ROWNUM = 1
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':shop_id', $shopId);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    oci_close($conn);
    echo json_encode(['success' => false, 'error' => 'Shop not found']);
    exit;
}

// ── Product images helper ─────────────────────────────────────────────────────
$imgStmt = oci_parse($conn,
    "SELECT product_id, image_url FROM PRODUCT_IMAGE pi
     WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :shop_id AND status = 'Active')
       AND display_order = 0"
);
oci_bind_by_name($imgStmt, ':shop_id', $shopId);
oci_execute($imgStmt);
$imgMap = [];
while ($ir = oci_fetch_assoc($imgStmt)) {
    $imgMap[(int)$ir['PRODUCT_ID']] = $ir['IMAGE_URL'];
}
oci_free_statement($imgStmt);

// ── Products ─────────────────────────────────────────────────────────────────
$pSql = "
    SELECT p.product_id, p.name, p.description, p.price, p.stock, p.unit,
           p.min_order, p.max_order, p.allergen_info, c.category_name,
           NVL(d.discount_percent, 0) AS discount_percent
    FROM PRODUCT p
    LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
    LEFT JOIN (
        SELECT product_id, MAX(discount_percent) AS discount_percent
        FROM DISCOUNT WHERE valid_until >= TRUNC(SYSDATE)
        GROUP BY product_id
    ) d ON d.product_id = p.product_id
    WHERE p.shop_id = :shop_id AND p.status = 'Active'
    ORDER BY p.product_id DESC
    OFFSET :off ROWS FETCH NEXT :lim ROWS ONLY
";

$pStmt = oci_parse($conn, $pSql);
oci_bind_by_name($pStmt, ':shop_id', $shopId);
oci_bind_by_name($pStmt, ':off',     $offset);
oci_bind_by_name($pStmt, ':lim',     $limit);
oci_execute($pStmt);

$products = [];
$countStmt = oci_parse($conn,
    "SELECT COUNT(*) AS cnt FROM PRODUCT WHERE shop_id = :shop_id AND status = 'Active'"
);
oci_bind_by_name($countStmt, ':shop_id', $shopId);
oci_execute($countStmt);
$totalCount = (int)(oci_fetch_assoc($countStmt)['CNT'] ?? 0);
oci_free_statement($countStmt);

while ($pr = oci_fetch_assoc($pStmt)) {
    $pid = (int)$pr['PRODUCT_ID'];
    $pr['IMAGE_URL']         = $imgMap[$pid] ?? '';
    $pr['PRICE']             = (float)$pr['PRICE'];
    $pr['STOCK']             = (int)$pr['STOCK'];
    $pr['DISCOUNT_PERCENT']  = (float)$pr['DISCOUNT_PERCENT'];
    $pr['MIN_ORDER']         = (int)($pr['MIN_ORDER'] ?? 0);
    $pr['MAX_ORDER']         = (int)($pr['MAX_ORDER'] ?? 0);
    $products[] = $pr;
}
oci_free_statement($pStmt);
oci_close($conn);

echo json_encode([
    'success' => true,
    'shop' => [
        'shop_id'           => (int)$row['SHOP_ID'],
        'shop_name'         => $row['SHOP_NAME'],
        'shop_description'  => $row['SHOP_DESCRIPTION'],
        'shop_type'         => $row['SHOP_TYPE'],
        'location'          => $row['LOCATION'],
        'contact_number'    => $row['CONTACT_NUMBER'],
        'collection_wed'    => (int)($row['COLLECTION_WED'] ?? 1),
        'collection_thu'    => (int)($row['COLLECTION_THU'] ?? 1),
        'collection_fri'    => (int)($row['COLLECTION_FRI'] ?? 1),
        'logo_data'         => base64_encode($row['SHOP_LOGO'] ?? ''),
        'logo_mime'         => $row['MIMETYPE'] ?? 'image/png',
        'trader_firstname'  => $row['TRADER_FIRSTNAME'],
        'trader_lastname'   => $row['TRADER_LASTNAME'],
        'trader_email'      => $row['TRADER_EMAIL'],
        'trader_phone'      => $row['TRADER_PHONE'],
        'trader_status'     => $row['TRADER_STATUS'],
        'product_count'     => (int)($row['PRODUCT_COUNT'] ?? $totalCount),
        'avg_rating'        => (float)($row['AVG_RATING'] ?? 0),
    ],
    'products'     => $products,
    'total'        => $totalCount,
    'offset'       => $offset,
    'limit'        => $limit,
]);
