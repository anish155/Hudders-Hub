<?php
session_start();

$order = $_SESSION['pending_order'] ?? null;
if (!$order) {
    header('Location: /Hudders-Hub/Execution Phase/Main-Website/public/index.html');
    exit;
}

$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
$paypalID  = 'sb-ynhsr50703047@business.example.com';

// Build custom value with all 4 fields
$custom_val = $order['order_id'] . '|' . $order['user_id'] . '|' . $order['product_id'] . '|' . $order['quantity'];

$successURL = 'http://localhost/Hudders-Hub/Execution%20Phase/Main-Website/api/payment/process-payment.php?custom=' . urlencode($custom_val) . '&amt=' . $order['amount'];
$cancelURL  = 'http://localhost/Hudders-Hub/Execution%20Phase/Main-Website/api/payment/cancel.php';
?>
<form id="paypalForm" action="<?php echo $paypalURL; ?>" method="post">
    <input type="hidden" name="business" value="<?php echo $paypalID; ?>">
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="item_name" value="HuddersHub Order #<?php echo $order['order_id']; ?>">
    <input type="hidden" name="item_number" value="<?php echo $order['product_id']; ?>">
    <input type="hidden" name="amount" value="<?php echo $order['amount']; ?>">
    <input type="hidden" name="currency_code" value="GBP">
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="custom" value="<?php echo $custom_val; ?>">
    <input type="hidden" name="return" value="<?php echo $successURL; ?>">
    <input type="hidden" name="cancel_return" value="<?php echo $cancelURL; ?>">
</form>
<script>document.getElementById('paypalForm').submit();</script>