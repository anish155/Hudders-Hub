<?php
// api/trader/update-product.php
// Accepts: FormData
// Fields:  user_id, product_id, name, description, price, stock,
//          min_order, max_order, unit, category (name), allergen_info, dietary_tags,
//          delete_images  (JSON array of image_id),
//          new_image_urls (comma-separated URLs)
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id     = isset($_POST['user_id'])     ? (int)$_POST['user_id']     : 0;
$product_id  = isset($_POST['product_id'])  ? (int)$_POST['product_id']  : 0;

if (!$user_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id or product_id']);
    exit;
}

// ── Verify ownership ─────────────────────────────────────────────────────────
$check = oci_parse($conn, "
    SELECT p.product_id FROM PRODUCT p
    JOIN SHOP s ON p.shop_id = s.shop_id
    WHERE p.product_id = :pid AND s.user_id = :user_id
");
oci_bind_by_name($check, ':pid', $product_id);
oci_bind_by_name($check, ':user_id', $user_id);
oci_execute($check);
if (!oci_fetch_assoc($check)) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit;
}
oci_free_statement($check);

// ── Extract fields ───────────────────────────────────────────────────────────
$name          = trim($_POST['name']          ?? '');
$description   = trim($_POST['description']   ?? '');
$price         = isset($_POST['price'])        ? (float)$_POST['price']      : null;
$stock         = isset($_POST['stock'])        ? (int)$_POST['stock']        : null;
$min_order     = isset($_POST['min_order'])    ? (int)$_POST['min_order']    : null;
$max_order     = isset($_POST['max_order'])    ? (int)$_POST['max_order']    : null;
$unit          = trim($_POST['unit']           ?? '');
$category_name = trim($_POST['category']       ?? '');
$allergen_info = trim($_POST['allergen_info']  ?? 'None');
$dietary_tags  = trim($_POST['dietary_tags']   ?? '');

$details = json_encode([
    'appearance_aroma'       => trim($_POST['appearance_aroma']       ?? ''),
    'texture_flavour'        => trim($_POST['texture_flavour']        ?? ''),
    'culinary_versatility'   => trim($_POST['culinary_versatility']   ?? ''),
    'nutritional_highlights' => trim($_POST['nutritional_highlights'] ?? ''),
    'growing_sourcing'       => trim($_POST['growing_sourcing']       ?? ''),
    'allergenic_info_detail' => trim($_POST['allergenic_info_detail'] ?? ''),
]);

if (!$name || $price === null || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Name and valid price are required']);
    exit;
}

// ── Resolve category_id ──────────────────────────────────────────────────────
$cat_id = 1;
if ($category_name) {
    $cs   = oci_parse($conn, "SELECT category_id FROM PRODUCT_CATEGORY WHERE category_name = :b_cat_id");
    oci_bind_by_name($cs, ':b_cat_id', $category_name);
    oci_execute($cs);
    $crow = oci_fetch_assoc($cs);
    if ($crow) $cat_id = (int)$crow['CATEGORY_ID'];
    oci_free_statement($cs);
}

// ── Update PRODUCT row ────────────────────────────────────────────────────────
$sql = "UPDATE PRODUCT SET
            name            = :b_name,
            description     = :b_desc,
            price           = :b_price,
            stock           = :b_stock,
            min_order       = :b_min_ord,
            max_order       = :b_max_ord,
            unit            = :b_unit,
            allergen_info   = :b_allergen,
            dietary_tags    = :b_diet,
            category_id     = :b_cat_id,
            status          = 'Active',
            product_details = EMPTY_CLOB()
        WHERE product_id = :b_pid
        RETURNING product_details INTO :b_clob";
$stmt = oci_parse($conn, $sql);
$clob = oci_new_descriptor($conn, OCI_D_LOB);
oci_bind_by_name($stmt, ':b_name',     $name);
oci_bind_by_name($stmt, ':b_desc',     $description);
oci_bind_by_name($stmt, ':b_price',    $price);
oci_bind_by_name($stmt, ':b_stock',    $stock);
oci_bind_by_name($stmt, ':b_min_ord',  $min_order);
oci_bind_by_name($stmt, ':b_max_ord',  $max_order);
oci_bind_by_name($stmt, ':b_unit',     $unit);
oci_bind_by_name($stmt, ':b_allergen', $allergen_info);
oci_bind_by_name($stmt, ':b_diet',     $dietary_tags);
oci_bind_by_name($stmt, ':b_cat_id',   $cat_id);
oci_bind_by_name($stmt, ':b_pid',      $product_id);
oci_bind_by_name($stmt, ':b_clob',     $clob, -1, OCI_B_CLOB);
oci_execute($stmt, OCI_NO_AUTO_COMMIT);
$clob->save($details);
oci_free_statement($stmt);

// ── Delete requested images ──────────────────────────────────────────────────
if (isset($_POST['delete_images'])) {
    $delete_ids = json_decode($_POST['delete_images'], true);
    if (is_array($delete_ids) && count($delete_ids) > 0) {
        $ds   = oci_parse($conn, "DELETE FROM PRODUCT_IMAGE WHERE image_id = :iid AND product_id = :pid");
        foreach ($delete_ids as $img_id) {
            oci_bind_by_name($ds, ':iid', $img_id);
            oci_bind_by_name($ds, ':pid', $product_id);
            oci_execute($ds, OCI_NO_AUTO_COMMIT);
        }
        oci_free_statement($ds);
    }
}

// ── Insert new image URLs (max 5 total) ──────────────────────────────────
if (isset($_POST['new_image_urls']) && trim($_POST['new_image_urls']) !== '') {
    $cnt_s = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM PRODUCT_IMAGE WHERE product_id = :pid");
    oci_bind_by_name($cnt_s, ':pid', $product_id);
    oci_execute($cnt_s);
    $crow   = oci_fetch_assoc($cnt_s);
    $curCnt = (int)($crow['CNT'] ?? 0);
    oci_free_statement($cnt_s);

    $urls = array_filter(array_map('trim', explode(',', $_POST['new_image_urls'])));
    $uploaded = 0;
    foreach ($urls as $url) {
        if ($uploaded >= 5 || ($curCnt + $uploaded) >= 5 || empty($url)) break;
        $sql_img = "INSERT INTO PRODUCT_IMAGE
                       (product_id, image_url, mime_type, file_name, display_order)
                    VALUES (:pid, :url, 'image/jpeg', :fname, :ord)";
        $si   = oci_parse($conn, $sql_img);
        $fname = basename(parse_url($url, PHP_URL_PATH) ?: 'image-' . ($curCnt + $uploaded));
        oci_bind_by_name($si, ':pid',   $product_id);
        oci_bind_by_name($si, ':url',   $url);
        oci_bind_by_name($si, ':fname', $fname);
        oci_bind_by_name($si, ':ord',   $curCnt + $uploaded);
        oci_execute($si, OCI_NO_AUTO_COMMIT);
        oci_free_statement($si);
        $uploaded++;
    }
}

// Handle discount
$discount_pct = isset($_POST['discount_percent']) ? (int)$_POST['discount_percent'] : 0;
if ($discount_pct > 0 && $discount_pct <= 90) {
    $expiry = trim($_POST['discount_expiry'] ?? '');
    $expiry_date = $expiry ?: date('Y-m-d', strtotime('+30 days'));

    // Delete existing discounts for this product by this user
    $del_disc = oci_parse($conn, "DELETE FROM DISCOUNT WHERE product_id = :pid AND user_id = :uid");
    oci_bind_by_name($del_disc, ':pid', $product_id);
    oci_bind_by_name($del_disc, ':uid', $user_id);
    oci_execute($del_disc, OCI_NO_AUTO_COMMIT);
    oci_free_statement($del_disc);

    // Insert new discount
    $ins_disc = oci_parse($conn, "INSERT INTO DISCOUNT (discount_percent, discount_type, valid_until, user_id, product_id)
                                  VALUES (:pct, 'Percentage', TO_DATE(:exp,'YYYY-MM-DD'), :uid, :pid)");
    oci_bind_by_name($ins_disc, ':pct', $discount_pct);
    oci_bind_by_name($ins_disc, ':exp', $expiry_date);
    oci_bind_by_name($ins_disc, ':uid', $user_id);
    oci_bind_by_name($ins_disc, ':pid', $product_id);
    oci_execute($ins_disc, OCI_NO_AUTO_COMMIT);
    oci_free_statement($ins_disc);
} else if ($discount_pct === 0) {
    // Remove any existing discount if set to 0
    $del_disc = oci_parse($conn, "DELETE FROM DISCOUNT WHERE product_id = :pid AND user_id = :uid");
    oci_bind_by_name($del_disc, ':pid', $product_id);
    oci_bind_by_name($del_disc, ':uid', $user_id);
    oci_execute($del_disc, OCI_NO_AUTO_COMMIT);
    oci_free_statement($del_disc);
}

oci_commit($conn);
oci_close($conn);

echo json_encode(['success' => true, 'message' => 'Product updated (pending admin approval)']);
?>
