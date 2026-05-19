<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

$sql = "DELETE FROM FAVOURITE WHERE user_id = :user_id AND product_id = :product_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_bind_by_name($stmt, ':product_id', $product_id);

$result = oci_execute($stmt);

// In Oracle OCI8, oci_execute returns true even when DELETE touches 0 rows;
// we must check oci_num_rows so we don't silently return success for a non-existent entry.
$rowsRemoved = oci_num_rows($stmt) ?? 0;

if ($result && $rowsRemoved > 0) {
    echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
} elseif ($rowsRemoved === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Item was not in your wishlist']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
