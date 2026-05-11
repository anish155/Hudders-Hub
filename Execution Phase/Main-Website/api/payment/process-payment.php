<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$custom  = $_REQUEST['custom'] ?? null;
$order   = $_SESSION['pending_order'] ?? null;

if ($custom) {
    $parts = explode('|', $custom);
    if (count($parts) >= 2) {
        if ($order) {
            $order['order_id'] = $parts[0];
            $order['user_id']  = $parts[1];
        } else {
            $order = [
                'order_id'   => $parts[0],
                'user_id'    => $parts[1],
                'amount'     => $_REQUEST['amt'] ?? 0,
                'product_id' => $parts[2] ?? null,
                'quantity'   => $parts[3] ?? 1
            ];
        }
    }
}

$txn_id   = $_REQUEST['txn_id'] ?? 'TXN_' . time();
$mc_gross = $_REQUEST['mc_gross'] ?? 0;
$order_id = null;
$amount   = 0;

if ($order) {
    $order_id   = $order['order_id'];
    $user_id    = $order['user_id'];
    $amount     = $order['amount'] ?: $mc_gross;
    $product_id = $order['product_id'];
    $quantity   = $order['quantity'];

    // 1. Update order status
    $upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Completed' WHERE order_id = :oid");
    oci_bind_by_name($upd, ':oid', $order_id);
    oci_execute($upd, OCI_NO_AUTO_COMMIT);

    // 2. INSERT payment
    $pins = oci_parse($conn, "
        INSERT INTO PAYMENT (PAYMENT_ID, ORDER_ID, PAYMENT_DATE, AMOUNT, METHOD, STATUS, USER_ID)
        VALUES (
            NVL((SELECT MAX(payment_id) FROM PAYMENT), 0) + 1,
            :oid, SYSDATE, :amt, 'PayPal', 'Completed', :user_id
        )
    ");
    oci_bind_by_name($pins, ':oid',     $order_id);
    oci_bind_by_name($pins, ':amt',     $amount);
    oci_bind_by_name($pins, ':user_id', $user_id);
    oci_execute($pins, OCI_NO_AUTO_COMMIT);

    // 3. Reduce stock
    if ($product_id && $quantity) {
        $stk = oci_parse($conn, "UPDATE PRODUCT SET stock = stock - :qty WHERE product_id = :pid");
        oci_bind_by_name($stk, ':qty', $quantity);
        oci_bind_by_name($stk, ':pid', $product_id);
        oci_execute($stk, OCI_NO_AUTO_COMMIT);
    }

    oci_commit($conn);

    // 4. Send confirmation email
    $eq = oci_parse($conn, "SELECT email, firstname FROM HUDDER_USER WHERE user_id = :user_id");
    oci_bind_by_name($eq, ':user_id', $user_id);
    oci_execute($eq);
    $user = oci_fetch_assoc($eq);

    if ($user) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'anishtandukar3@gmail.com';
            $mail->Password   = 'kpephxqowoztvlgk';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->setFrom('anishtandukar3@gmail.com', 'HuddersHub');
            $mail->addAddress($user['EMAIL'], $user['FIRSTNAME']);
            $mail->Subject = 'Order Confirmed - HuddersHub #' . $order_id;
            $mail->isHTML(true);
            $mail->Body = "
                <div style='font-family:sans-serif;max-width:500px;margin:0 auto;'>
                    <h2 style='color:#0F260B;'>Order Confirmed! 🎉</h2>
                    <p>Hi {$user['FIRSTNAME']},</p>
                    <p>Your order <strong>#$order_id</strong> has been confirmed.</p>
                    <p><strong>Amount paid:</strong> £$amount</p>
                    <p><strong>Transaction ID:</strong> $txn_id</p>
                    <p>Thank you for shopping at HuddersHub!</p>
                    <p style='color:#666;font-size:13px;'>Regards,<br>HuddersHub Team</p>
                </div>
            ";
            $mail->send();
        } catch (Exception $e) {}
    }

    unset($_SESSION['pending_order']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful - HuddersHub</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
<style>
    body { font-family: sans-serif; background: #F7F6F3; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .card { background: #fff; border: 1px solid #DCE3DA; padding: 48px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 18px 36px rgba(15,38,11,0.12); }
    .icon { width: 80px; height: 80px; background: #ECFDF5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .icon span { font-size: 40px; color: #059669; }
    h2 { color: #0F260B; font-size: 26px; margin-bottom: 10px; }
    p { color: #5E6A63; font-size: 15px; margin-bottom: 8px; }
    .order-id { font-size: 18px; font-weight: 700; color: #0F260B; background: #F7F6F3; padding: 10px 20px; display: inline-block; margin: 16px 0; }
    .btn { display: inline-block; padding: 12px 28px; background: #0F260B; color: #fff; text-decoration: none; font-weight: 700; margin-top: 20px; }
</style>
</head>
<body>
<div class="card">
    <div class="icon"><span class="material-icons-outlined">check_circle</span></div>
    <h2>Payment Successful!</h2>
    <p>Thank you for your order. Your payment has been confirmed.</p>
    <?php if ($order_id): ?>
    <div class="order-id">Order #<?php echo htmlspecialchars($order_id); ?></div>
    <p><strong>Amount:</strong> £<?php echo number_format($amount, 2); ?></p>
    <?php endif; ?>
    <p style="font-size:13px;color:#9CA3AF;">A confirmation email has been sent to you.</p>
    <br>
    <a href="/Hudders-Hub/Execution Phase/Main-Website/public/index.html" class="btn">Back to Home</a>
</div>
</body>
</html>