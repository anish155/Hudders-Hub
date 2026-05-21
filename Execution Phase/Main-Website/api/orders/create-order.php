<?php
ob_start(); // Buffer output to prevent accidental text output
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    require_once '../../config/session.php';
    require_once '../../config/mailer.php';

    requireLogin();

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    $slot_id = $data['slot_id'] ?? null;
    $payment_method = (strtolower($data['payment_method'] ?? 'paypal') === 'paypal') ? 'PayPal' : $data['payment_method'];

    if (!$slot_id) {
        throw new Exception('Collection slot is required.');
    }

    // 1. Get cart items to verify and calculate total
    $cartSql = "SELECT ci.product_id, p.name AS product_name, p.price, ci.quantity 
                FROM CART c 
                JOIN CART_ITEM ci ON c.cart_id = ci.cart_id
                JOIN PRODUCT p ON ci.product_id = p.product_id 
                WHERE c.user_id = :user_id";
    $cartStmt = oci_parse($conn, $cartSql);
    oci_bind_by_name($cartStmt, ':user_id', $user_id);
    oci_execute($cartStmt);

    $cartItems = [];
    $total = 0;
    while ($row = oci_fetch_assoc($cartStmt)) {
        $cartItems[] = $row;
        $total += $row['PRICE'] * $row['QUANTITY'];
    }
    oci_free_statement($cartStmt);

    if (empty($cartItems)) {
        throw new Exception('Your cart is empty.');
    }

    // 2. Get next order ID from sequence
    $idStmt = oci_parse($conn, "SELECT seq_Order.NEXTVAL AS next_id FROM DUAL");
    if (!oci_execute($idStmt)) {
        $e = oci_error($idStmt);
        throw new Exception('Failed to generate Order ID: ' . $e['message']);
    }
    $idRow = oci_fetch_assoc($idStmt);
    $order_id = $idRow['NEXT_ID'];
    oci_free_statement($idStmt);

    // 3. Create order
    $orderSql = "INSERT INTO HUDDER_ORDER (order_id, order_date, order_time, status, user_id, slot_id)
                 VALUES (:order_id, SYSDATE, TO_CHAR(SYSDATE, 'HH:MI AM'), 'Pending', :user_id, :slot_id)";
    $orderStmt = oci_parse($conn, $orderSql);
    oci_bind_by_name($orderStmt, ':order_id', $order_id);
    oci_bind_by_name($orderStmt, ':user_id', $user_id);
    oci_bind_by_name($orderStmt, ':slot_id', $slot_id);

    if (!oci_execute($orderStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($orderStmt);
        throw new Exception('Order creation failed: ' . $e['message']);
    }
    oci_free_statement($orderStmt);

    // 4. Insert order products
    foreach ($cartItems as $item) {
        $opSql = "INSERT INTO ORDER_PRODUCT (order_id, product_id, quantity, unit_price)
                  VALUES (:order_id, :product_id, :quantity, :price)";
        $opStmt = oci_parse($conn, $opSql);
        oci_bind_by_name($opStmt, ':order_id', $order_id);
        oci_bind_by_name($opStmt, ':product_id', $item['PRODUCT_ID']);
        oci_bind_by_name($opStmt, ':quantity', $item['QUANTITY']);
        oci_bind_by_name($opStmt, ':price', $item['PRICE']);
        if (!oci_execute($opStmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($opStmt);
            throw new Exception('Failed to add product ' . $item['PRODUCT_NAME'] . ': ' . $e['message']);
        }
        oci_free_statement($opStmt);
    }

    // Calculate total with fee
    $service_fee = 2.40;
    $grand_total = $total + $service_fee;

    // 5. Create payment record
    $paySql = "INSERT INTO PAYMENT (payment_id, amount, method, status, payment_date, order_id, user_id)
               VALUES (seq_Payment.NEXTVAL, :amount, :method, 'Pending', SYSDATE, :order_id, :user_id)";
    $payStmt = oci_parse($conn, $paySql);
    oci_bind_by_name($payStmt, ':amount', $grand_total);
    oci_bind_by_name($payStmt, ':method', $payment_method);
    oci_bind_by_name($payStmt, ':order_id', $order_id);
    oci_bind_by_name($payStmt, ':user_id', $user_id);
    if (!oci_execute($payStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($payStmt);
        throw new Exception('Payment record creation failed: ' . $e['message']);
    }
    oci_free_statement($payStmt);

    oci_commit($conn);
    
    ob_end_clean(); // Clear buffer
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    error_log("Order creation error: " . $e->getMessage());
    ob_end_clean(); // Clear buffer
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) { // Catch fatal errors in PHP 7+
    if (isset($conn)) oci_rollback($conn);
    error_log("System error in create-order: " . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
