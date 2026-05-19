<?php
/**
 * Quick Stock Update API
 * POST /api/trader/update-product-stock.php
 * PRODUCT column: stock (not stock_quantity)
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$data       = json_decode(file_get_contents('php://input'), true);
$user_id    = isset($data['user_id'])    ? (int)$data['user_id']    : 0;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$stock      = isset($data['stock'])      ? (int)$data['stock']      : -1;

if (!$user_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if ($stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock cannot be negative']);
    exit;
}

// Verify ownership
$sv = oci_parse($conn, "
    SELECT p.product_id FROM PRODUCT p
    JOIN SHOP s ON p.shop_id = s.shop_id
    WHERE p.product_id = :pid AND s.user_id = :user_id
");
oci_bind_by_name($sv, ':pid', $product_id);
oci_bind_by_name($sv, ':user_id', $user_id);
oci_execute($sv);
$found = oci_fetch_assoc($sv);
oci_free_statement($sv);

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit;
}

// Update 'stock' (correct column name from schema)
$su = oci_parse($conn, "UPDATE PRODUCT SET stock = :stock WHERE product_id = :pid");
oci_bind_by_name($su, ':stock', $stock);
oci_bind_by_name($su, ':pid',   $product_id);

if (oci_execute($su, OCI_COMMIT_ON_SUCCESS)) {
    echo json_encode(['success' => true, 'message' => 'Stock updated to ' . $stock]);
} else {
    $e = oci_error($su);
    echo json_encode(['success' => false, 'message' => $e['message'] ?? 'Update failed']);
}

oci_free_statement($su);
oci_close($conn);
?>