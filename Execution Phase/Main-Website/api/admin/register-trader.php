<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/mail_config.php'; // ✅ Secure Credentials
require_once '../../vendor/autoload.php';    // ✅ PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$firstname = $data['firstname'];
$lastname  = $data['lastname'];
$email     = $data['email'];
$password  = $data['password'];
$phone     = $data['phone_number']  ?? '';
$address   = $data['address']       ?? '';
$dob       = $data['date_of_birth'] ?? '1990-01-01';
$gender    = $data['gender']        ?? 'Prefer not to say';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// 1. Check if email already exists
$check = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE email = :email");
oci_bind_by_name($check, ':email', $email);
oci_execute($check);
$row = oci_fetch_assoc($check);
if ($row['CNT'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

try {
    // 2. Get next user_id
    $idq = oci_parse($conn, "SELECT NVL(MAX(user_id),0)+1 AS new_id FROM HUDDER_USER");
    oci_execute($idq);
    $idrow   = oci_fetch_assoc($idq);
    $user_id = $idrow['NEW_ID'];

    // 3. Insert user
    $sql  = "INSERT INTO HUDDER_USER (user_id, firstname, lastname, email, user_password, user_role, phone_number, address, date_of_birth, gender) 
             VALUES (:u_id, :fn, :ln, :em, :pw, 'trader', :ph, :adr, TO_DATE(:dob,'YYYY-MM-DD'), :gen)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':u_id', $user_id);
    oci_bind_by_name($stmt, ':fn',   $firstname);
    oci_bind_by_name($stmt, ':ln',   $lastname);
    oci_bind_by_name($stmt, ':em',   $email);
    oci_bind_by_name($stmt, ':pw',   $password);
    oci_bind_by_name($stmt, ':ph',   $phone);
    oci_bind_by_name($stmt, ':adr',  $address);
    oci_bind_by_name($stmt, ':dob',  $dob);
    oci_bind_by_name($stmt, ':gen',  $gender);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception("User Table: " . $e['message']);
    }

    // 4. Get next trader_id
    $tidq   = oci_parse($conn, "SELECT NVL(MAX(trader_id),0)+1 AS new_id FROM TRADER");
    oci_execute($tidq);
    $tidrow    = oci_fetch_assoc($tidq);
    $trader_id = $tidrow['NEW_ID'];

    // 5. Insert trader
    $tstmt = oci_parse($conn, "INSERT INTO TRADER (trader_id, user_id, status) VALUES (:tid, :u_id, 'Pending')");
    oci_bind_by_name($tstmt, ':tid',  $trader_id);
    oci_bind_by_name($tstmt, ':u_id', $user_id);

    if (!oci_execute($tstmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($tstmt);
        throw new Exception("Trader Table: " . $e['message']);
    }

    // 6. COMMIT ALL
    oci_commit($conn);

    // ✅ 7. SEND EMAIL CONFIRMATION
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER; 
            $mail->Password   = MAIL_PASS; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom(MAIL_USER, 'HuddersHub');
            $mail->addAddress($email, $firstname);

            $mail->isHTML(true);
            $mail->Subject = 'Application Received - HuddersHub';
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee;'>
                    <h2 style='color: #0F260B;'>Hi $firstname,</h2>
                    <p>Thanks for applying to be a trader on HuddersHub!</p>
                    <p>Your application is currently <strong>Pending Review</strong>. We will notify you once you are approved.</p>
                </div>";

            $mail->send();
        } catch (Exception $e) {
            // Log error but don't stop the success response
            error_log("Email failed: " . $mail->ErrorInfo);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Trader registration submitted successfully!']);

} catch (Exception $e) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Registration Error: ' . $e->getMessage()]);
}
?>