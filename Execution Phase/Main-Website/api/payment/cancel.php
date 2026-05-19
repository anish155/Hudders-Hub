<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = $_SESSION['pending_order'] ?? null;
if (!$order_id && $order) {
    $order_id = (int)($order['order_id'] ?? 0);
}

if ($order_id) {
    try {
        $conn = getDB();
        $upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Cancelled' WHERE order_id = :oid");
        oci_bind_by_name($upd, ':oid', $order_id);
        oci_execute($upd);
        oci_close($conn);
    } catch (Exception $e) {
        error_log('[cancel] ' . $e->getMessage());
    }
}
unset($_SESSION['pending_order']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Cancelled - HuddersHub</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
<style>
    body { font-family: sans-serif; background: #F7F6F3; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .card { background: #fff; border: 1px solid #DCE3DA; padding: 48px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 18px 36px rgba(15,38,11,0.12); }
    .icon { width: 80px; height: 80px; background: #FEF2F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .icon span { font-size: 40px; color: #EF4444; }
    h2 { color: #991B1B; font-size: 26px; margin-bottom: 10px; }
    p { color: #5E6A63; font-size: 15px; margin-bottom: 8px; }
    .btn { display: inline-block; padding: 12px 28px; background: #0F260B; color: #fff; text-decoration: none; font-weight: 700; margin-top: 20px; }
    .btn-orange { background: #FF5E3A; margin-left: 10px; }
</style>
</head>
<body>
<div class="card">
    <div class="icon"><span class="material-icons-outlined">cancel</span></div>
    <h2>Payment Cancelled</h2>
    <p>Your payment was cancelled. No charges have been made.</p>
    <p>Your order has been marked as cancelled.</p>
    <br>
    <a href="<?php echo BASE_URL; ?>/public/index.html" class="btn">Back to Home</a>
    <a href="<?php echo BASE_URL; ?>/public/cart.html" class="btn btn-orange">Back to Cart</a>
</div>
</body>
</html>
