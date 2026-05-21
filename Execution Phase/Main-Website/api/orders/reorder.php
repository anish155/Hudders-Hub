<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = getDB();

try {
    $orderSql = "SELECT product_id, quantity FROM ORDER_PRODUCT WHERE order_id = :oid";
    $orderStmt = oci_parse($conn, $orderSql);
    oci_bind_by_name($orderStmt, ':oid', $order_id);
    oci_execute($orderStmt);

    $items = [];
    while ($row = oci_fetch_assoc($orderStmt)) {
        $items[] = [
            'product_id' => (int)$row['PRODUCT_ID'],
            'quantity' => (int)$row['QUANTITY']
        ];
    }
    oci_free_statement($orderStmt);

    if (empty($items)) {
        oci_close($conn);
        echo json_encode(['success' => false, 'error' => 'No items found in order']);
        exit;
    }

    $cartSql = "SELECT cart_id FROM CART WHERE user_id = :p_user_id";
    $cartStmt = oci_parse($conn, $cartSql);
    oci_bind_by_name($cartStmt, ':p_user_id', $user_id);
    oci_execute($cartStmt);
    $cartRow = oci_fetch_assoc($cartStmt);

    if (!$cartRow) {
        $nextValSql = oci_parse($conn, "SELECT seq_Cart.NEXTVAL AS cart_id FROM DUAL");
        oci_execute($nextValSql);
        $nextRow = oci_fetch_assoc($nextValSql);
        $cart_id = $nextRow['CART_ID'];
        oci_free_statement($nextValSql);

        $createCart = oci_parse($conn, "INSERT INTO CART (cart_id, user_id, created_at) VALUES (:p_cart_id, :p_user_id2, SYSDATE)");
        oci_bind_by_name($createCart, ':p_cart_id', $cart_id);
        oci_bind_by_name($createCart, ':p_user_id2', $user_id);
        oci_execute($createCart, OCI_NO_AUTO_COMMIT);
        oci_free_statement($createCart);
    } else {
        $cart_id = $cartRow['CART_ID'];
    }
    oci_free_statement($cartStmt);

    $addedItems = 0;
    foreach ($items as $item) {
        $checkSql = "SELECT quantity FROM CART_ITEM WHERE cart_id = :p_cart_id AND product_id = :p_product_id";
        $checkStmt = oci_parse($conn, $checkSql);
        oci_bind_by_name($checkStmt, ':p_cart_id', $cart_id);
        oci_bind_by_name($checkStmt, ':p_product_id', $item['product_id']);
        oci_execute($checkStmt);
        $existing = oci_fetch_assoc($checkStmt);
        oci_free_statement($checkStmt);

        if ($existing) {
            $newQty = $existing['QUANTITY'] + $item['quantity'];
            $updSql = "UPDATE CART_ITEM SET quantity = :p_qty WHERE cart_id = :p_cart_id AND product_id = :p_product_id";
            $updStmt = oci_parse($conn, $updSql);
            oci_bind_by_name($updStmt, ':p_qty', $newQty);
            oci_bind_by_name($updStmt, ':p_cart_id', $cart_id);
            oci_bind_by_name($updStmt, ':p_product_id', $item['product_id']);
            oci_execute($updStmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($updStmt);
        } else {
            $insSql = "INSERT INTO CART_ITEM (cart_item_id, cart_id, product_id, quantity) VALUES (seq_Cart_Item.NEXTVAL, :p_cart_id, :p_product_id, :p_qty)";
            $insStmt = oci_parse($conn, $insSql);
            oci_bind_by_name($insStmt, ':p_cart_id', $cart_id);
            oci_bind_by_name($insStmt, ':p_product_id', $item['product_id']);
            oci_bind_by_name($insStmt, ':p_qty', $item['quantity']);
            oci_execute($insStmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($insStmt);
        }
        $addedItems++;
    }

    oci_commit($conn);

    $countSql = "SELECT SUM(ci.quantity) AS total_qty FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :p_user_id3";
    $countStmt = oci_parse($conn, $countSql);
    oci_bind_by_name($countStmt, ':p_user_id3', $user_id);
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $cartCount = (int)($countRow['TOTAL_QTY'] ?? 0);
    oci_free_statement($countStmt);

    oci_close($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Items added to cart',
        'count' => $addedItems,
        'cart_count' => $cartCount
    ]);

} catch (Exception $e) {
    oci_rollback($conn);
    oci_close($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
