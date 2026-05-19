<?php
// api/trader/add-product.php
// Supports: unit, dietary_tags, multi-image BLOB upload, FormData body
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/Main-Website/php_api_errors.log');
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$name        = trim($_POST['name']        ?? '');
$description = trim($_POST['description'] ?? '');
$price       = isset($_POST['price'])       ? (float)$_POST['price']       : 0;
$stock       = isset($_POST['stock'])       ? (int)$_POST['stock']         : 0;
$min_order   = isset($_POST['min_order'])   ? (int)$_POST['min_order']     : 1;
$max_order   = isset($_POST['max_order'])   ? (int)$_POST['max_order']     : 50;
$unit        = trim($_POST['unit']         ?? '');
$category_name = trim($_POST['category']   ?? '');
$allergen_info = trim($_POST['allergen_info'] ?? 'None');
$dietary_tags  = trim($_POST['dietary_tags']  ?? '');
$images = $_FILES['images'] ?? null;

$details = json_encode([
    'appearance_aroma'       => trim($_POST['appearance_aroma']       ?? ''),
    'texture_flavour'        => trim($_POST['texture_flavour']        ?? ''),
    'culinary_versatility'   => trim($_POST['culinary_versatility']   ?? ''),
    'nutritional_highlights' => trim($_POST['nutritional_highlights'] ?? ''),
    'growing_sourcing'       => trim($_POST['growing_sourcing']       ?? ''),
    'allergenic_info_detail' => trim($_POST['allergenic_info_detail'] ?? ''),
]);

if (!$user_id || !$name || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (user_id, name, price)']);
    exit;
}

$log = [];
function add_log($msg) {
    global $log;
    $log[] = $msg;
}

add_log("user_id=$user_id name=$name price=$price");

// ── Resolve shop_id ──────────────────────────────────────────────────────────
$sql_shop = "SELECT shop_id, shop_type FROM SHOP WHERE user_id = :user_id";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':user_id', $user_id);
oci_execute($stmt_shop);
$shop = oci_fetch_assoc($stmt_shop);
oci_free_statement($stmt_shop);

if (!$shop) {
    add_log("No shop found for user_id=$user_id");
    echo json_encode(['success' => false, 'message' => 'Shop not found']);
    exit;
}
$shop_id   = (int)$shop['SHOP_ID'];
$shop_type = $shop['SHOP_TYPE'] ?? 'Butcher';
add_log("shop_id=$shop_id shop_type=$shop_type");

// ── Resolve category_id from PRODUCT_CATEGORY ─────────────────────────────────
$cat_sql = "SELECT category_id FROM PRODUCT_CATEGORY WHERE category_name = :cat_id";
$stmt_cat = oci_parse($conn, $cat_sql);
$catVar   = $category_name;
oci_bind_by_name($stmt_cat, ':cat_id', $catVar);
oci_execute($stmt_cat);
$cat_row      = oci_fetch_assoc($stmt_cat);
$category_id  = $cat_row ? (int)$cat_row['CATEGORY_ID'] : 1;
oci_free_statement($stmt_cat);
add_log("category=$category_name -> category_id=$category_id");

// ── Insert product ────────────────────────────────────────────────────────────
$sql_prod = "INSERT INTO PRODUCT"
          . " (product_id, name, description, price, stock, min_order, max_order,"
          . "  unit, allergen_info, dietary_tags, shop_id, category_id, status, product_details)"
          . " VALUES"
          . " (seq_Product.NEXTVAL, :b_name, :b_desc, :b_price, :b_stock, :b_min_ord, :b_max_ord,"
          . "  :b_unit, :b_allergen, :b_diet, :b_sid, :b_cat_id, 'Active', EMPTY_CLOB())"
          . " RETURNING product_details INTO :b_clob";
add_log("SQL len: " . strlen($sql_prod));

// Parse first, report result
$stmt = oci_parse($conn, $sql_prod);
error_log("add-product: parse=" . ($stmt !== false ? "OK" : "FAIL"));
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
oci_bind_by_name($stmt, ':b_sid',      $shop_id);
oci_bind_by_name($stmt, ':b_cat_id',   $category_id);
oci_bind_by_name($stmt, ':b_clob',     $clob, -1, OCI_B_CLOB);
error_log("add-product: all binds done");

preg_match_all('/:(\w+)/', $sql_prod, $bm);
error_log("add-product: bind_names=" . implode(',', $bm[1]));

$exec_ok = @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
error_log(">>>EXEC_CHECK: exec_ok=" . ($exec_ok ? 'true' : 'false') . " err_msg=" . (@oci_error($stmt)['message'] ?? 'no_err'));
$err_msg = '';
if (!$exec_ok) {
    $e = @oci_error($stmt);
    $err_msg = ($e['code'] ?? 'n/a') . ':' . ($e['message'] ?? 'no err');
}
error_log(">>>FINAL: exec=" . ($exec_ok ? "OK" : "FAIL $err_msg"));

if (!$exec_ok) {
    oci_rollback($conn);
    error_log("add-product: rolled back");
    echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $err_msg]);
    oci_close($conn);
    exit;
}
$clob->save($details);

// Uses Oracle sequence CURRVAL (NEXTVAL was called in the INSERT)
$rid = oci_parse($conn, "SELECT seq_Product.CURRVAL FROM DUAL");
oci_execute($rid);
$cur = oci_fetch_array($rid, OCI_NUM);
$product_id = $cur ? (int)$cur[0] : 0;
oci_free_statement($rid);
add_log("product_id=$product_id");

// ── Handle image uploads ──────────────────────────────────────────────────
$uploaded = 0;
if ($images && isset($images['tmp_name']) && is_array($images['tmp_name'])) {
    foreach ($images['tmp_name'] as $key => $tmp_name) {
        if ($uploaded >= 5 || $images['error'][$key] !== UPLOAD_ERR_OK || !is_uploaded_file($tmp_name)) {
            continue;
        }
        $file_content = file_get_contents($tmp_name);
        $mime   = $images['type'][$key];
        $fname  = $images['name'][$key];

        $sql_img = "INSERT INTO PRODUCT_IMAGE"
                 . " (product_id, image, mime_type, file_name, display_order)"
          . " VALUES (:b_pid, EMPTY_BLOB(), :b_mime, :b_fname, :b_ord)"
          . " RETURNING image INTO :b_blob";
        $stmt_img = oci_parse($conn, $sql_img);
        $blob = oci_new_descriptor($conn, OCI_D_LOB);

        $_prod_id = (int)$product_id;
        $_ord = (int)$uploaded;
        oci_bind_by_name($stmt_img, ':b_pid',  $_prod_id);
        oci_bind_by_name($stmt_img, ':b_mime', $mime);
        oci_bind_by_name($stmt_img, ':b_fname',$fname);
        oci_bind_by_name($stmt_img, ':b_ord',  $_ord);
        oci_bind_by_name($stmt_img, ':b_blob', $blob, -1, OCI_B_BLOB);

        $img_ok = @oci_execute($stmt_img, OCI_NO_AUTO_COMMIT);
        add_log("img#$key: " . ($img_ok ? "OK" : "FAIL " . (oci_error($stmt_img)['message'] ?? '')));
        if ($img_ok) $blob->save($file_content);
        oci_free_statement($stmt_img);
        $uploaded++;
    }
}

oci_commit($conn);
add_log("committed");
echo json_encode(['success' => true, 'message' => 'Product added and pending admin approval', 'product_id' => (int)$product_id]);
