<?php
// Disable error reporting to prevent warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Get shop - create if not exists
$stmtShop = oci_parse($conn, "SELECT shop_id, name AS shop_name FROM SHOP WHERE user_id = :user_id");
oci_bind_by_name($stmtShop, ':user_id', $user_id);
oci_execute($stmtShop);
$shop = oci_fetch_assoc($stmtShop);
oci_free_statement($stmtShop);

if (!$shop) {
    // Attempt safe insert so that trg_Shop auto-assigns shop_id via seq_Shop
    try {
        $stmtIns = oci_parse($conn,
            "INSERT INTO SHOP (shop_id, name, description, location, contact_number, user_id)
             VALUES (seq_Shop.NEXTVAL, 'My Shop', 'Default shop', 'Huddersfield', '0000000000', :user_id)"
        );
        oci_bind_by_name($stmtIns, ':user_id', $user_id);
        @oci_execute($stmtIns, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmtIns);

        $stmtRefetch = oci_parse($conn, "SELECT shop_id, name AS shop_name FROM SHOP WHERE user_id = :user_id");
        oci_bind_by_name($stmtRefetch, ':user_id', $user_id);
        oci_execute($stmtRefetch);
        $shop = oci_fetch_assoc($stmtRefetch);
        oci_free_statement($stmtRefetch);

        oci_commit($conn);
    } catch (Exception $e) {
        if (isset($conn)) oci_rollback($conn);
    }

    if (!$shop) {
        echo json_encode(['success' => false, 'message' => 'Could not create a shop for this trader.']);
        exit;
    }
}
$shop_id    = (int)$shop['SHOP_ID'];
$shop_name  = $shop['SHOP_NAME'];

// Get products
$stmt = oci_parse($conn, "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.min_order, p.max_order, p.allergen_info, p.status, p.category_id, c.category_name FROM PRODUCT p LEFT JOIN PRODUCT_CATEGORY c ON p.category_id = c.category_id WHERE p.shop_id = :sid ORDER BY p.name");
oci_bind_by_name($stmt, ':sid', $shop_id);
oci_execute($stmt);

$products = [];
while ($row = oci_fetch_assoc($stmt)) {
    $products[] = [
        'id'           => (int)$row['PRODUCT_ID'],
        'name'         => $row['NAME'],
        'description'  => $row['DESCRIPTION'],
        'price'        => (float)$row['PRICE'],
        'stock'        => (int)$row['STOCK'],
        'min_order'    => (int)$row['MIN_ORDER'],
        'max_order'    => (int)$row['MAX_ORDER'],
        'allergen_info'=> $row['ALLERGEN_INFO'],
        'status'       => $row['STATUS'],
        'category_id'  => $row['CATEGORY_ID'] ? (int)$row['CATEGORY_ID'] : 0,
        'category'     => $row['CATEGORY_NAME'] ?? 'Uncategorised'
    ];
}
oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'shop_id' => $shop_id, 'shop_name' => $shop_name, 'products' => $products]);
