<?php
// api/products/search-products.php
// Trader-scoped: only returns products belonging to the logged-in trader's shop
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn   = getDB();
$user_id = getUserId();
$role    = getUserRole();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Only traders use this endpoint. Customers should use the public search.
if ($role !== 'trader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Resolve trader's shop_id
$shop_id = 0;
$ss = oci_parse($conn, "SELECT shop_id FROM SHOP WHERE user_id = :user_id");
oci_bind_by_name($ss, ':user_id', $user_id);
oci_execute($ss);
$shop_row = oci_fetch_assoc($ss);
if ($shop_row) $shop_id = (int)$shop_row['SHOP_ID'];
oci_free_statement($ss);

if (!$shop_id) {
    echo json_encode(['success' => true, 'data' => []]);
    oci_close($conn);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query === '') {
    oci_close($conn);
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

if (strlen($query) < 2) {
    oci_close($conn);
    echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters', 'data' => []]);
    exit;
}

$search = '%' . strtoupper($query) . '%';

$sql = "
    SELECT p.product_id, p.name, p.description, p.price, p.stock, p.min_order, p.max_order,
           p.reorder_label, p.allergen_info, p.shop_id, p.category_id, p.status,
           s.name AS shop_name, s.shop_type,
           c.category_name
    FROM PRODUCT p
    JOIN SHOP s ON p.shop_id = s.shop_id
    LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id
    WHERE p.shop_id = :sid
      AND p.status = 'Active'
      AND (UPPER(p.name) LIKE :kw OR UPPER(p.description) LIKE :kw)
    ORDER BY p.name
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shop_id);
oci_bind_by_name($stmt, ':kw', $search);
oci_execute($stmt);

$results = [];
while ($row = oci_fetch_assoc($stmt)) {
    $results[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $results]);
