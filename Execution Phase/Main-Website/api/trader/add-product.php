<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id     = isset($data['user_id'])     ? (int)$data['user_id']     : 0;
$name        = isset($data['name'])        ? trim($data['name'])        : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$price       = isset($data['price'])       ? (float)$data['price']      : 0;
$stock       = isset($data['stock'])       ? (int)$data['stock']        : 0;
$category_id = isset($data['category_id']) ? (int)$data['category_id']  : 0;
$min_order   = isset($data['min_order'])   ? (int)$data['min_order']    : 1;
$max_order   = isset($data['max_order'])   ? (int)$data['max_order']    : 50;
$allergens   = isset($data['allergens'])   ? trim($data['allergens'])   : 'None';

if (!$user_id || !$name || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get shop_id for this trader
$sql_shop = "SELECT shop_id FROM SHOP WHERE user_id = :user_id";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':user_id', $user_id);
oci_execute($stmt_shop, OCI_COMMIT_ON_SUCCESS);
$shop = oci_fetch_assoc($stmt_shop);

if (!$shop) {
    echo json_encode(['success' => false, 'message' => 'Shop not found for this trader']);
    exit;
}

$shop_id = (int)$shop['SHOP_ID'];

// Insert product — product_id handled by trigger (trg_Product + seq_Product)
$sql_insert = "
    INSERT INTO PRODUCT 
        (name, description, price, stock, min_order, max_order, 
         reorder_label, allergen_info, shop_id, category_id, status)
    VALUES 
        (:name, :description, :price, :stock, :min_order, :max_order,
         5, :allergens, :shop_id, :category_id, 'Pending')
";

$stmt_ins = oci_parse($conn, $sql_insert);
oci_bind_by_name($stmt_ins, ':name',        $name);
oci_bind_by_name($stmt_ins, ':description', $description);
oci_bind_by_name($stmt_ins, ':price',       $price);
oci_bind_by_name($stmt_ins, ':stock',       $stock);
oci_bind_by_name($stmt_ins, ':min_order',   $min_order);
oci_bind_by_name($stmt_ins, ':max_order',   $max_order);
oci_bind_by_name($stmt_ins, ':allergens',   $allergens);
oci_bind_by_name($stmt_ins, ':shop_id',     $shop_id);
oci_bind_by_name($stmt_ins, ':category_id', $category_id);

if (oci_execute($stmt_ins, OCI_COMMIT_ON_SUCCESS)) {
    echo json_encode([
        'success' => true,
        'message' => 'Product submitted for admin approval'
    ]);
} else {
    $e = oci_error($stmt_ins);
    echo json_encode([
        'success' => false,
        'message' => $e['message']
    ]);
}

oci_close($conn);
?>