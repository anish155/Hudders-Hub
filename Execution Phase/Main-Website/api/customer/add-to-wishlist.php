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

$checkSql = "SELECT COUNT(*) AS cnt FROM FAVOURITE
              WHERE user_id = :user_id AND product_id = :product_id";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':user_id', $user_id);
oci_bind_by_name($checkStmt, ':product_id', $product_id);
oci_execute($checkStmt);
$checkRow = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($checkRow['CNT'] > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product already in wishlist']);
    oci_close($conn);
    exit;
}

$sql = "INSERT INTO FAVOURITE (created_at, user_id, product_id)
        VALUES (SYSDATE, :user_id, :product_id)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_bind_by_name($stmt, ':product_id', $product_id);

$result = oci_execute($stmt);
if ($result) {
    echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
} else {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
}

oci_free_statement($stmt);
oci_close($conn);
