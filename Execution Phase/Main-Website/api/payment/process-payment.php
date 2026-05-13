<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../config/mail_config.php'; // Load the secret credentials

// Load PHPMailer
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 1. Resolve order data ---
$order = $_SESSION['pending_order'] ?? null;

// Fallback for PayPal return URL params
if (!$order && isset($_GET['order_id'])) {
    $order = [
        'order_id' => $_GET['order_id'],
        'user_id'  => $_GET['user_id']  ?? 0,
        'amount'   => $_GET['amt']      ?? 0
    ];
}

if (!$order) {
    die("<div style='text-align:center; padding:50px;'><h2>Error: Order data lost.</h2><p>Please check your session or URL parameters.</p></div>");
}

$order_id = (int)$order['order_id'];
$user_id  = (int)$order['user_id'];
$amount   = (float)$order['amount'];

$db_success = false;
$mail_error = null;

try {
    // 2. Update Order Status to 'Completed'
    $upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Completed' WHERE order_id = :oid");
    oci_bind_by_name($upd, ':oid', $order_id);
    oci_execute($upd, OCI_NO_AUTO_COMMIT);

    // 3. Insert Payment Record (Using :u_id to avoid Oracle reserved keyword conflict)
    $ins = oci_parse($conn, "
        INSERT INTO PAYMENT (PAYMENT_ID, ORDER_ID, PAYMENT_DATE, AMOUNT, METHOD, STATUS, USER_ID)
        VALUES (seq_Payment.NEXTVAL, :oid, SYSDATE, :amt, 'PayPal', 'Completed', :u_id)
    ");
    oci_bind_by_name($ins, ':oid', $order_id);
    oci_bind_by_name($ins, ':amt', $amount);
    oci_bind_by_name($ins, ':u_id', $user_id);
    
    $success = oci_execute($ins, OCI_NO_AUTO_COMMIT);
    
    if (!$success) {
        $e = oci_error($ins);
        throw new Exception($e['message']);
    }

    oci_commit($conn);
    $db_success = true;

} catch (Exception $e) {
    oci_rollback($conn);
    die("<div style='color:red; text-align:center; padding:50px;'><h2>Database Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>");
}

// --- 4. Send Confirmation Email ---
if ($db_success) {
    // Fetch User Details
    $user_q = oci_parse($conn, "SELECT email, firstname FROM HUDDER_USER WHERE user_id = :u_id");
    oci_bind_by_name($user_q, ':u_id', $user_id);
    oci_execute($user_q);
    $user_data = oci_fetch_assoc($user_q);

    if ($user_data && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            // SMTP Settings using the constants from mail_config.php
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER; 
            $mail->Password   = MAIL_PASS; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom(MAIL_USER, 'HuddersHub Support');
            $mail->addAddress($user_data['EMAIL'], $user_data['FIRSTNAME']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmed - HuddersHub #' . $order_id;
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                    <h2 style='color: #28a745;'>Payment Received! 🎉</h2>
                    <p>Hi " . htmlspecialchars($user_data['FIRSTNAME']) . ",</p>
                    <p>Your order <strong>#$order_id</strong> has been successfully placed.</p>
                    <p><strong>Total Paid:</strong> &pound;" . number_format($amount, 2) . "</p>
                    <p>Thank you for shopping at HuddersHub.</p>
                </div>";

            $mail->send();
        } catch (Exception $e) {
            $mail_error = $mail->ErrorInfo;
            error_log("Email failed: " . $mail_error);
        }
    }
    unset($_SESSION['pending_order']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Complete - HuddersHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-align: center; padding: 60px; background: #f7f6f3; color: #333; }
        .card { background: white; padding: 40px; display: inline-block; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 500px; width: 100%; }
        h1 { color: #28a745; margin-bottom: 10px; }
        .order-id { font-size: 1.4em; font-weight: bold; background: #f1f1f1; padding: 10px; margin: 20px 0; border-radius: 4px; }
        .btn { display: inline-block; padding: 14px 30px; background: #0F260B; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
        .btn:hover { background: #1c3c17; }
        .error-msg { background: #fff5f5; border: 1px solid #feb2b2; padding: 10px; color: #c53030; font-size: 0.9em; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Payment Successful!</h1>
        <p>Your order has been confirmed and recorded in our system.</p>
        
        <div class="order-id">Order #<?php echo $order_id; ?></div>
        
        <p>Amount Paid: <strong>£<?php echo number_format($amount, 2); ?></strong></p>
        
        <?php if ($mail_error): ?>
            <div class="error-msg">
                <strong>Notice:</strong> Order saved, but the confirmation email could not be sent.<br>
                <em>(Error: <?php echo htmlspecialchars($mail_error); ?>)</em>
            </div>
        <?php else: ?>
            <p style="color: #666;">A confirmation email was sent to <strong><?php echo htmlspecialchars($user_data['EMAIL'] ?? 'your email'); ?></strong></p>
        <?php endif; ?>

        <br>
        <a href="/Hudders-Hub/Execution%20Phase/Main-Website/public/index.html" class="btn">Back to Home</a>
    </div>
</body>
</html>