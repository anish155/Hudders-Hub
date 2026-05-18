<?php
session_start();

$order = $_SESSION['pending_order'] ?? null;

if (!$order) {
    // No pending order — send back to home
    header('Location: /Hudders-Hub/Execution%20Phase/Main-Website/public/index.html');
    exit;
}

$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
$paypalID  = 'sb-ynhsr50703047@business.example.com';

// Pipe-delimited fallback
$custom_val = $order['order_id'] . '|' . $order['user_id'] . '|' . $order['product_id'] . '|' . $order['quantity'];

// ✅ FIXED: Changed "Execution-Phase" to "Execution%20Phase"
$base       = 'http://localhost/Hudders-Hub/Execution%20Phase/Main-Website/api/payment/process-payment.php';
$successURL = $base
    . '?order_id='   . urlencode($order['order_id'])
    . '&user_id='    . urlencode($order['user_id'])
    . '&product_id=' . urlencode($order['product_id'])
    . '&quantity='   . urlencode($order['quantity'])
    . '&amt='        . urlencode($order['amount']);

// ✅ FIXED: Changed "Execution-Phase" to "Execution%20Phase"
$cancelURL = 'http://localhost/Hudders-Hub/Execution%20Phase/Main-Website/api/payment/cancel.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayPal...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; background: #F7F6F3; }
        p    { color: #5E6A63; font-size: 15px; }
    </style>
</head>
<body>
    <p>Redirecting to PayPal, please wait...</p>

    <form id="paypalForm" action="<?php echo htmlspecialchars($paypalURL); ?>" method="post">
        <input type="hidden" name="business"       value="<?php echo htmlspecialchars($paypalID); ?>">
        <input type="hidden" name="cmd"            value="_xclick">
        <input type="hidden" name="item_name"      value="HuddersHub Order #<?php echo htmlspecialchars($order['order_id']); ?>">
        <input type="hidden" name="item_number"    value="<?php echo htmlspecialchars($order['product_id']); ?>">
        <input type="hidden" name="amount"         value="<?php echo htmlspecialchars($order['amount']); ?>">
        <input type="hidden" name="currency_code"  value="GBP">
        <input type="hidden" name="quantity"       value="1">
        <input type="hidden" name="custom"         value="<?php echo htmlspecialchars($custom_val); ?>">
        <input type="hidden" name="return"         value="<?php echo htmlspecialchars($successURL); ?>">
        <input type="hidden" name="cancel_return"  value="<?php echo htmlspecialchars($cancelURL); ?>">
        <input type="hidden" name="rm"             value="2">
        <input type="hidden" name="no_shipping"    value="1">
    </form>
    <script>document.getElementById('paypalForm').submit();</script>
</body>
</html>