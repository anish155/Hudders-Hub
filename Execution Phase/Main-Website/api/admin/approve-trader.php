<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/mail_config.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['trader_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$trader_id = $data['trader_id'];
$action    = $data['action']; // 'approve', 'reject', or 'block'

// Determine new status
if ($action === 'approve') {
    $status = 'Active';
} elseif ($action === 'block') {
    $status = 'Blocked';
} else {
    $status = 'Rejected';
}

// Update trader status
$stmt = oci_parse($conn, "UPDATE TRADER SET status = :status WHERE trader_id = :trader_id");
oci_bind_by_name($stmt, ':status',    $status);
oci_bind_by_name($stmt, ':trader_id', $trader_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e['message']]);
    exit;
}
oci_commit($conn);

// Get trader email and name
$q = oci_parse($conn, "
    SELECT u.email, u.firstname 
    FROM HUDDER_USER u 
    JOIN TRADER t ON u.user_id = t.user_id 
    WHERE t.trader_id = :trader_id
");
oci_bind_by_name($q, ':trader_id', $trader_id);
oci_execute($q);
$trader = oci_fetch_assoc($q);

// Send approval email
if ($trader && $action === 'approve') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0;

        $mail->setFrom(MAIL_USER, 'HuddersHub');
        $mail->addAddress($trader['EMAIL'], $trader['FIRSTNAME']);
        $mail->Subject = 'Your HuddersHub Trader Account is Approved!';
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family:sans-serif;max-width:500px;margin:0 auto;padding:24px;'>
                <h2 style='color:#0F260B;'>Welcome to HuddersHub, {$trader['FIRSTNAME']}! 🎉</h2>
                <p>Great news! Your trader application has been <strong style='color:#16a34a;'>approved</strong>.</p>
                <p>You can now log in and start setting up your shop:</p>
                <br>
                <a href='http://localhost/Hudders-Hub/Execution%20Phase/Main-Website/public/login.html'
                   style='display:inline-block;padding:12px 24px;background:#FF5E3A;color:#fff;text-decoration:none;font-weight:bold;border-radius:4px;'>
                   Log In Now
                </a>
                <br><br>
                <p style='color:#666;font-size:13px;'>If you have any questions, reply to this email.</p>
                <p style='color:#666;font-size:13px;'>Regards,<br>HuddersHub Team</p>
            </div>
        ";
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Trader approved and email sent!']);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'Trader approved but email failed: ' . $e->getMessage()]);
    }

// Send rejection email
} elseif ($trader && $action === 'reject') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->SMTPDebug  = 0;

        $mail->setFrom(MAIL_USER, 'HuddersHub');
        $mail->addAddress($trader['EMAIL'], $trader['FIRSTNAME']);
        $mail->Subject = 'HuddersHub Trader Application Update';
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family:sans-serif;max-width:500px;margin:0 auto;padding:24px;'>
                <h2 style='color:#991B1B;'>Application Not Approved</h2>
                <p>Dear {$trader['FIRSTNAME']},</p>
                <p>Unfortunately, your trader application has not been approved at this time.</p>
                <p>If you believe this is a mistake or would like more information, please contact us.</p>
                <p style='color:#666;font-size:13px;'>Regards,<br>HuddersHub Team</p>
            </div>
        ";
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Trader rejected and email sent!']);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'Trader rejected but email failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => true, 'message' => "Trader status updated to $status successfully"]);
}
?>