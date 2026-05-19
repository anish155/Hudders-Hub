<?php
ini_set('display_errors', 0);
error_reporting(0);
// api/products/get-products.php
// Trader-aware: if caller is a trader, auto-detects their shop_id.
// Public / customer callers can still supply ?shop_id= explicitly.
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn    = getDB();
$user_id = getUserId();
$role    = getUserRole();

$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
$shopId     = isset($_GET['shop_id'])     && $_GET['shop_id']     !== '' ? (int) $_GET['shop_id']     : null;
$status     = isset($_GET['status'])      && $_GET['status']      !== '' ? trim($_GET['status'])      : 'Active';
$search     = isset($_GET['search'])      && $_GET['search']      !== '' ? trim($_GET['search'])      : '';

// Trader auto-detection of shop_id
if ($role === 'trader' && $user_id && !$shopId) {
    $ss = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
    oci_bind_by_name($ss, ':user_id', $user_id);
    oci_execute($ss);
    $row = oci_fetch_assoc($ss);
    if ($row) $shopId = (int)$row['SHOP_ID'];
    oci_free_statement($ss);
}

if (!$shopId) {
    echo json_encode(['success' => false, 'message' => 'Shop ID required']);
    oci_close($conn);
    exit;
}

// For traders, show all their own products (including Pending) unless ?status= restricts it
$sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.min_order, p.max_order,
               p.reorder_label, p.allergen_info, p.shop_id, p.category_id, p.status,
               s.name AS shop_name, s.shop_type,
               c.category_name
        FROM PRODUCT p
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
        WHERE p.shop_id = :sid";

if ($status !== '') {
    $sql .= " AND p.status = :status";
}

if ($categoryId !== null) {
    $sql .= " AND p.category_id = :category_id";
}

if ($search !== '') {
    $sql .= " AND LOWER(p.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY p.name";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shopId);

if ($status !== '') {
    oci_bind_by_name($stmt, ':status', $status);
}
if ($categoryId !== null) {
    oci_bind_by_name($stmt, ':category_id', $categoryId);
}
if ($search !== '') {
    oci_bind_by_name($stmt, ':search', '%' . $search . '%');
}
oci_execute($stmt);

$results = [];
while ($row = oci_fetch_assoc($stmt)) {
    $results[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $results]);
