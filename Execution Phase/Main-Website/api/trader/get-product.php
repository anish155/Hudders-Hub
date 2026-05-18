<?php
/**
 * Get Single Product API
 * GET /api/trader/get-product.php?product_id=N&user_id=N
 *
 * Returns: product fields + dietary_tags + unit + product_images[]
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$user_id    = isset($_GET['user_id'])    ? (int)$_GET['user_id']    : 0;

if (!$product_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing product_id or user_id']);
    exit;
}

// ── Product row ──────────────────────────────────────────────────────────────
$sql = "
    SELECT p.product_id, p.name, p.description, p.price,
           p.stock, p.min_order, p.max_order,
           p.unit, p.allergen_info, p.dietary_tags,
           p.status, p.category_id,
           c.category_name,
           NVL(d.discount_percent, 0) AS discount_percent
    FROM PRODUCT p
    JOIN SHOP s ON p.shop_id = s.shop_id
    LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
    LEFT JOIN (
        SELECT product_id, MAX(discount_percent) AS discount_percent
        FROM DISCOUNT
        WHERE valid_until >= TRUNC(SYSDATE)
        GROUP BY product_id
    ) d ON d.product_id = p.product_id
    WHERE p.product_id = :pid AND s.user_id = :user_id
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':pid', $product_id);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit;
}

// ── Images for this product ──────────────────────────────────────────────────
$img_sql = "
    SELECT image_id, mime_type, file_name, display_order
    FROM PRODUCT_IMAGE
    WHERE product_id = :pid
    ORDER BY display_order, image_id
";
$img_stmt = oci_parse($conn, $img_sql);
oci_bind_by_name($img_stmt, ':pid', $product_id);
oci_execute($img_stmt);

$images = [];
while ($img = oci_fetch_assoc($img_stmt)) {
    // Fetch BLOB content
    $blob_sql = "SELECT image FROM PRODUCT_IMAGE WHERE image_id = :iid";
    $blob_stmt = oci_parse($conn, $blob_sql);
    oci_bind_by_name($blob_stmt, ':iid', $img['IMAGE_ID']);
    oci_execute($blob_stmt);
    $blob_row = oci_fetch_array($blob_stmt, OCI_ASSOC);
    $blob_data = '';
    if ($blob_row && isset($blob_row['IMAGE'])) {
        $lob   = $blob_row['IMAGE'];
        $blob_data = $lob->load();
        $lob->free();
    }
    oci_free_statement($blob_stmt);

    $images[] = [
        'image_id'   => (int)$img['IMAGE_ID'],
        'mime_type'  => $img['MIME_TYPE']  ?? 'image/jpeg',
        'file_name'  => $img['FILE_NAME']  ?? '',
        'image_data' => base64_encode($blob_data),
    ];
}
oci_free_statement($img_stmt);

oci_close($conn);

echo json_encode([
    'success' => true,
    'product' => [
        'id'            => (int)$row['PRODUCT_ID'],
        'name'          => $row['NAME'],
        'description'   => $row['DESCRIPTION'],
        'price'         => (float)$row['PRICE'],
        'stock'         => (int)$row['STOCK'],
        'min_order'     => (int)$row['MIN_ORDER'],
        'max_order'     => (int)$row['MAX_ORDER'],
        'unit'          => $row['UNIT']          ?? '',
        'allergen_info' => $row['ALLERGEN_INFO']  ?? 'None',
        'dietary_tags'  => $row['DIETARY_TAGS']   ?? '',
        'status'        => $row['STATUS'],
        'category_id'   => (int)$row['CATEGORY_ID'],
        'category'         => $row['CATEGORY_NAME']   ?? '',
        'discount_percent' => (float)($row['DISCOUNT_PERCENT'] ?? 0),
        'images'           => $images,
    ]
]);
?>
