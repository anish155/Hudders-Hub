<?php
// Disable error reporting to prevent warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

/**
 * Quick Stock Update API
 * POST /api/trader/update-product-stock.php
 * PRODUCT column: stock (not stock_quantity)
 */
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id    = (int)($data['user_id']    ?? 0);
    $product_id = (int)($data['product_id'] ?? 0);
    $stock      = (int)($data['stock']      ?? -1);

    if (!$user_id || !$product_id) {
        throw new Exception('Missing required fields');
    }

    if ($stock < 0) {
        throw new Exception('Stock cannot be negative');
    }

    // Verify ownership
    $check = oci_parse($conn, "
        SELECT p.product_id FROM PRODUCT p
        JOIN SHOP s ON p.shop_id = s.shop_id
        WHERE p.product_id = :pid AND s.user_id = :user_id
    ");
    oci_bind_by_name($check, ':pid', $product_id);
    oci_bind_by_name($check, ':user_id', $user_id);
    oci_execute($check);
    if (!oci_fetch_assoc($check)) {
        throw new Exception('Product not found or access denied');
    }
    oci_free_statement($check);

    // Update 'stock' (correct column name from schema)
    $su = oci_parse($conn, "UPDATE PRODUCT SET stock = :stock WHERE product_id = :pid");
oci_bind_by_name($su, ':stock', $stock);
oci_bind_by_name($su, ':pid',   $product_id);

    if (oci_execute($su, OCI_COMMIT_ON_SUCCESS)) {
        echo json_encode(['success' => true, 'message' => 'Stock updated to ' . $stock]);
    } else {
        $e = oci_error($su);
        throw new Exception($e['message'] ?? 'Update failed');
    }
    oci_free_statement($su);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}