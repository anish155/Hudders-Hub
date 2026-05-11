<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id    = $data['user_id'];
$product_id = $data['product_id'];
$quantity   = $data['quantity'];
$unit_price = $data['unit_price'];
$slot_id    = $data['slot_id'] ?? null;
$amount     = round($quantity * $unit_price, 2);

// Get next order_id
$idq = oci_parse($conn, "SELECT NVL(MAX(order_id),0)+1 AS new_id FROM HUDDER_ORDER");
oci_execute($idq);
$idrow    = oci_fetch_assoc($idq);
$order_id = $idrow['NEW_ID'];

// Insert pending order (with slot_id and fixed time format)
$sql  = "INSERT INTO HUDDER_ORDER (ORDER_ID, ORDER_DATE, ORDER_TIME, STATUS, USER_ID, SLOT_ID)
         VALUES (:order_id, SYSDATE, TO_CHAR(SYSDATE,'HH12:MI AM'), 'Pending', :user_id, :slot_id)";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':order_id', $order_id);
oci_bind_by_name($stmt, ':user_id',  $user_id);
oci_bind_by_name($stmt, ':slot_id',  $slot_id);
oci_execute($stmt, OCI_NO_AUTO_COMMIT);

// Insert order product
$sql2  = "INSERT INTO ORDER_PRODUCT VALUES (:order_id, :product_id, :quantity, :unit_price)";
$stmt2 = oci_parse($conn, $sql2);
oci_bind_by_name($stmt2, ':order_id',   $order_id);
oci_bind_by_name($stmt2, ':product_id', $product_id);
oci_bind_by_name($stmt2, ':quantity',   $quantity);
oci_bind_by_name($stmt2, ':unit_price', $unit_price);
oci_execute($stmt2, OCI_NO_AUTO_COMMIT);

oci_commit($conn);

$_SESSION['pending_order'] = [
    'order_id'   => $order_id,
    'user_id'    => $user_id,
    'product_id' => $product_id,
    'quantity'   => $quantity,
    'slot_id'    => $slot_id,
    'amount'     => $amount
];

echo json_encode(['success' => true, 'order_id' => $order_id, 'amount' => $amount]);
?>