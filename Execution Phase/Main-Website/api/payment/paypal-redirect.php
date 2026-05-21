<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: ' . BASE_URL . '/public/cart.html');
    exit;
}

$conn = getDB();

// Fetch the order + payment record
$sql = "SELECT o.order_id, o.user_id, p.amount
        FROM HUDDER_ORDER o
        JOIN PAYMENT p ON o.order_id = p.order_id
        WHERE o.order_id = :oid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':oid', $order_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);
oci_close($conn);

if (!$row) {
    header('Location: ' . BASE_URL . '/public/cart.html?error=order_not_found');
    exit;
}

$amount   = (float)$row['AMOUNT'];
$user_id  = (int)$row['USER_ID'];

// Store in session for process-payment.php callback
$_SESSION['pending_order'] = [
    'order_id' => $order_id,
    'user_id'  => $user_id,
    'amount'   => $amount,
];

$paypalURL  = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
$paypalID   = 'sb-ynhsr50703047@business.example.com';
$base       = BASE_URL . '/api/payment';
$successURL = $base . '/process-payment.php?order_id=' . $order_id . '&user_id=' . $user_id . '&amt=' . $amount;
$cancelURL  = $base . '/cancel.php?order_id=' . $order_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayPal…</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; background: #F7F6F3; flex-direction: column; gap: 16px; }
        p { color: #5E6A63; font-size: 15px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #DCE3DA; border-top-color: #FF5E3A; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <p>Redirecting to PayPal, please wait…</p>

    <form id="paypalForm" action="<?php echo htmlspecialchars($paypalURL); ?>" method="post">
        <input type="hidden" name="business"      value="<?php echo htmlspecialchars($paypalID); ?>">
        <input type="hidden" name="cmd"           value="_xclick">
        <input type="hidden" name="item_name"     value="HuddersHub Order #<?php echo $order_id; ?>">
        <input type="hidden" name="item_number"   value="<?php echo $order_id; ?>">
        <input type="hidden" name="amount"        value="<?php echo number_format($amount, 2, '.', ''); ?>">
        <input type="hidden" name="currency_code" value="GBP">
        <input type="hidden" name="quantity"      value="1">
        <input type="hidden" name="custom"        value="<?php echo $order_id . '|' . $user_id; ?>">
        <input type="hidden" name="return"        value="<?php echo htmlspecialchars($successURL); ?>">
        <input type="hidden" name="cancel_return" value="<?php echo htmlspecialchars($cancelURL); ?>">
        <input type="hidden" name="rm"            value="2">
        <input type="hidden" name="no_shipping"   value="1">
    </form>
    <script>document.getElementById('paypalForm').submit();</script>
</body>
</html>
