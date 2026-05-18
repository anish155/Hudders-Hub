<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Coupon code required']);
    exit;
}

$sql = "SELECT discount_id, discount_percent, discount_type, valid_until
        FROM DISCOUNT
        WHERE UPPER(discount_code) = :code AND user_id IS NULL";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':code', $code);
oci_execute($stmt);
$coupon = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$coupon) {
    echo json_encode(['success' => false, 'error' => 'Invalid coupon code']);
    exit;
}

if (!empty($coupon['VALID_UNTIL'])) {
    $expires = strtotime($coupon['VALID_UNTIL']);
    if ($expires < time()) {
        echo json_encode(['success' => false, 'error' => 'Coupon has expired']);
        exit;
    }
}

$_SESSION['applied_coupon'] = [
    'discount_id' => $coupon['DISCOUNT_ID'],
    'percent' => $coupon['DISCOUNT_PERCENT'],
    'type' => $coupon['DISCOUNT_TYPE']
];

oci_close($conn);

echo json_encode([
    'success' => true,
    'message' => 'Coupon applied!',
    'discount_percent' => $coupon['DISCOUNT_PERCENT']
]);
