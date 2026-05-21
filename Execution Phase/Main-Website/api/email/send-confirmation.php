<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../config/mailer.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
}

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$conn = getDB();

$sql = "SELECT o.order_id, o.order_date, o.status, o.slot_id,
               u.firstname, u.email,
               cs.slot_date, cs.slot_time, cs.location,
               p.amount, p.method, p.status AS payment_status
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        LEFT JOIN PAYMENT p ON o.order_id = p.order_id
        WHERE o.order_id = :order_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':order_id', $order_id);
oci_execute($stmt);
$order = oci_fetch_assoc($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$prodSql = "SELECT op.product_id, p.name, op.quantity, op.unit_price
             FROM ORDER_PRODUCT op
             JOIN PRODUCT p ON op.product_id = p.product_id
             WHERE op.order_id = :order_id";
$prodStmt = oci_parse($conn, $prodSql);
oci_bind_by_name($prodStmt, ':order_id', $order_id);
oci_execute($prodStmt);

$products = [];
while ($prod = oci_fetch_assoc($prodStmt)) {
    $products[] = $prod;
}
oci_free_statement($prodStmt);
oci_close($conn);

$slotDate = $order['SLOT_DATE'] ? date('l, jS F Y', strtotime($order['SLOT_DATE'])) : 'TBD';
$slotTime = $order['SLOT_TIME'] ?: '10:00 - 13:00';

try {
    huddershub_send_order_confirmation(
        $order['EMAIL'],
        $order['FIRSTNAME'],
        $order_id,
        $products,
        $order['AMOUNT'],
        $slotDate,
        $slotTime
    );

    echo json_encode([
        'success' => true,
        'message' => 'Confirmation email sent successfully',
        'email' => $order['EMAIL'],
        'order_id' => $order_id
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send email: ' . $e->getMessage()
    ]);
}
?>
