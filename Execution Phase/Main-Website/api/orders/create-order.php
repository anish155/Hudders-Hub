<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'];
$slot_id = $data['slot_id'] ?? null;
$payment_method = $data['payment_method'] ?? 'PayPal';

if (!$slot_id) {
    echo json_encode(['success' => false, 'error' => 'Please select a collection slot']);
    exit;
}

try {
    // Check slot availability
    $checkSql = "SELECT cs.capacity - COUNT(o.order_id) AS available
                 FROM COLLECTION_SLOT cs
                 LEFT JOIN HUDDER_ORDER o ON cs.slot_id = o.slot_id
                 WHERE cs.slot_id = :slot_id
                 GROUP BY cs.capacity";
    $checkStmt = oci_parse($conn, $checkSql);
    oci_bind_by_name($checkStmt, ':slot_id', $slot_id);
    oci_execute($checkStmt);
    $slotRow = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if (!$slotRow || $slotRow['AVAILABLE'] <= 0) {
        throw new Exception('This slot is no longer available');
    }

    // Get cart items
    $cartSql = "SELECT ci.product_id, ci.quantity, p.price, p.name
                FROM CART c
                JOIN CART_ITEM ci ON c.cart_id = ci.cart_id
                JOIN PRODUCT p ON ci.product_id = p.product_id
                WHERE c.user_id = :user_id";
    $cartStmt = oci_parse($conn, $cartSql);
    oci_bind_by_name($cartStmt, ':user_id', $user_id);
    oci_execute($cartStmt);

    $cartItems = [];
    $total = 0;
    while ($item = oci_fetch_assoc($cartStmt)) {
        $cartItems[] = $item;
        $total += $item['QUANTITY'] * $item['PRICE'];
    }
    oci_free_statement($cartStmt);

    if (empty($cartItems)) {
        throw new Exception('Your cart is empty');
    }

    // Create order
    $orderSql = "INSERT INTO HUDDER_ORDER (order_date, order_time, status, user_id, slot_id)
                 VALUES (SYSDATE, TO_CHAR(SYSDATE, 'HH:MI AM'), 'Pending', :user_id, :slot_id)";
    $orderStmt = oci_parse($conn, $orderSql);
    oci_bind_by_name($orderStmt, ':user_id', $user_id);
    oci_bind_by_name($orderStmt, ':slot_id', $slot_id);

    if (!oci_execute($orderStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($orderStmt);
        throw new Exception('Order creation failed: ' . $e['message']);
    }
    oci_free_statement($orderStmt);

    // Get order_id
    $idStmt = oci_parse($conn, "SELECT seq_Order.CURRVAL AS order_id FROM DUAL");
    oci_execute($idStmt);
    $orderRow = oci_fetch_assoc($idStmt);
    $order_id = $orderRow['ORDER_ID'];
    oci_free_statement($idStmt);

    // Insert order products
    foreach ($cartItems as $item) {
        $opSql = "INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price)
                  VALUES (:order_id, :product_id, :quantity, :price)";
        $opStmt = oci_parse($conn, $opSql);
        oci_bind_by_name($opStmt, ':order_id', $order_id);
        oci_bind_by_name($opStmt, ':product_id', $item['PRODUCT_ID']);
        oci_bind_by_name($opStmt, ':quantity', $item['QUANTITY']);
        oci_bind_by_name($opStmt, ':price', $item['PRICE']);
        oci_execute($opStmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($opStmt);
    }

    // Calculate total with fee
    $service_fee = 2.40;
    $grand_total = $total + $service_fee;

    // Create payment record
    $paySql = "INSERT INTO PAYMENT (payment_id, amount, method, status, payment_date, order_id, user_id)
               VALUES (seq_Payment.NEXTVAL, :amount, :method, 'Pending', SYSDATE, :order_id, :user_id)";
    $payStmt = oci_parse($conn, $paySql);
    oci_bind_by_name($payStmt, ':amount', $grand_total);
    oci_bind_by_name($payStmt, ':method', $payment_method);
    oci_bind_by_name($payStmt, ':order_id', $order_id);
    oci_bind_by_name($payStmt, ':user_id', $user_id);
    oci_execute($payStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($payStmt);

    // Clear cart
    $clearSql = "DELETE FROM CART_ITEM WHERE cart_id IN (SELECT cart_id FROM CART WHERE user_id = :user_id)";
    $clearStmt = oci_parse($conn, $clearSql);
    oci_bind_by_name($clearStmt, ':user_id', $user_id);
    oci_execute($clearStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($clearStmt);

    oci_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'total' => $grand_total
    ]);

} catch (Exception $e) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
