<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

$logged_in = isLoggedIn();
$user_id = $logged_in ? $_SESSION['user_id'] : 0;

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Product ID required']);
    exit;
}

$conn = getDB();

$can_review = false;
$already_reviewed = false;
$has_purchased = false;

if ($logged_in) {
    $purchaseSql = "SELECT 1 FROM ORDER_PRODUCT op
                    JOIN HUDDER_ORDER o ON op.order_id = o.order_id
                    WHERE op.product_id = :p_pid
                      AND o.user_id = :p_uid
                      AND o.status IN ('Completed', 'Delivered', 'Collected', 'Ready')";
    $purchaseStmt = oci_parse($conn, $purchaseSql);
    oci_bind_by_name($purchaseStmt, ':p_pid', $product_id);
    oci_bind_by_name($purchaseStmt, ':p_uid', $user_id);
    oci_execute($purchaseStmt);
    $has_purchased = (bool)oci_fetch_assoc($purchaseStmt);
    oci_free_statement($purchaseStmt);

    if ($has_purchased) {
        $existingSql = "SELECT review_id FROM REVIEW WHERE product_id = :p_pid2 AND user_id = :p_uid2";
        $existingStmt = oci_parse($conn, $existingSql);
        oci_bind_by_name($existingStmt, ':p_pid2', $product_id);
        oci_bind_by_name($existingStmt, ':p_uid2', $user_id);
        oci_execute($existingStmt);
        $already_reviewed = (bool)oci_fetch_assoc($existingStmt);
        oci_free_statement($existingStmt);

        if (!$already_reviewed) {
            $can_review = true;
        }
    }
}

oci_close($conn);

echo json_encode([
    'success' => true,
    'can_review' => $can_review,
    'has_purchased' => $has_purchased,
    'already_reviewed' => $already_reviewed,
    'logged_in' => $logged_in
]);
