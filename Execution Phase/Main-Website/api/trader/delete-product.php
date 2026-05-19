<?php
// api/trader/delete-product.php
// POST body: { user_id: int, product_id: int }
// Owned-product check, explicit image cleanup, FK-safe
require_once '../../config/database.php';
header('Content-Type: application/json');

$data       = json_decode(file_get_contents('php://input'), true);
$user_id    = isset($data['user_id'])    ? (int)$data['user_id']    : 0;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

if (!$user_id || !$product_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id or product_id']);
    exit;
}

// ── Verify product belongs to this trader's shop ────────────────────────────
$sql_verify = "
    SELECT p.product_id
    FROM   PRODUCT p
    JOIN   SHOP     s ON s.shop_id = p.shop_id
    WHERE  p.product_id  = :product_id
    AND    s.user_id     = :user_id
";
$stmt_verify = oci_parse($conn, $sql_verify);
oci_bind_by_name($stmt_verify, ':product_id', $product_id);
oci_bind_by_name($stmt_verify, ':user_id',    $user_id);
oci_execute($stmt_verify);

if (!oci_fetch_assoc($stmt_verify)) {
    oci_free_statement($stmt_verify);
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit;
}
oci_free_statement($stmt_verify);

// ── Delete product images first (explicit, even though FK ON DELETE CASCADE exists) ─
$del_img = oci_parse($conn, "DELETE FROM PRODUCT_IMAGE WHERE product_id = :product_id");
oci_bind_by_name($del_img, ':product_id', $product_id);
oci_execute($del_img, OCI_NO_AUTO_COMMIT);
oci_free_statement($del_img);
oci_commit($conn);   // persist image deletions before product deletion

// ── Delete product row ───────────────────────────────────────────────────────
$del_prod = oci_parse($conn, "DELETE FROM PRODUCT WHERE product_id = :product_id");
oci_bind_by_name($del_prod, ':product_id', $product_id);

if (oci_execute($del_prod, OCI_COMMIT_ON_SUCCESS)) {
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    $e = oci_error($del_prod);
    echo json_encode(['success' => false, 'message' => $e['message'] ?? 'Delete failed']);
}

oci_free_statement($del_prod);
oci_close($conn);
?>
