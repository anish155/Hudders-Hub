<?php
session_start();
require_once '../../config/database.php';

$order = $_SESSION['pending_order'] ?? null;
if ($order) {
    $upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Cancelled' WHERE order_id = :oid");
    oci_bind_by_name($upd, ':oid', $order['order_id']);
    oci_execute($upd);
    unset($_SESSION['pending_order']);
}
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
    <br>
    <a href="/Hudders-Hub/Execution Phase/Main-Website/public/index.html" class="btn">Back to Home</a>
    <a href="javascript:history.back()" class="btn btn-orange">Try Again</a>
</div>
</body>
</html>