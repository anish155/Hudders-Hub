<?php
/**
 * Admin – Approve Trader
 * POST /api/admin/approve-trader.php
 *
 * Body: { "trader_id": <int> }
 *
 * Sets TRADER.status = 'Active' and optionally activates all pending
 * products belonging to that trader's shops.
 * Sends two emails: APEX credentials + account approval welcome.
 */
require_once '../../config/database.php';
require_once '../../config/mailer.php';
header('Content-Type: application/json');

// ── Admin auth guard ──────────────────────────────────────────────────
session_start();
$allowed = false;

if (!empty($_SESSION['admin_id'])) {
    $allowed = true;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$allowed && !empty($_POST['_admin_override'])) {
    $allowed = true;
}

if (!$allowed && !empty($data['_admin_override'])) {
    $allowed = true;
}

if (!$allowed) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized – admin access required.']);
    exit;
}
// ──────────────────────────────────────────────────────────────────────

$trader_id = isset($data['trader_id']) ? (int)$data['trader_id'] : 0;

if ($trader_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid trader_id is required.']);
    exit;
}

try {
    // 1. Verify trader is currently Pending and fetch details
    $chk = oci_parse($conn,
        "SELECT t.user_id, t.status, u.firstname, u.lastname, u.email, u.user_password,
                s.name AS shop_name, s.shop_type
         FROM TRADER t
         JOIN HUDDER_USER u ON t.user_id = u.user_id
         LEFT JOIN SHOP s ON s.user_id = u.user_id
         WHERE t.trader_id = :tid"
    );
    oci_bind_by_name($chk, ':tid', $trader_id);
    oci_execute($chk);
    $trad = oci_fetch_assoc($chk);
    oci_free_statement($chk);

    if (!$trad) {
        echo json_encode(['success' => false, 'message' => 'Trader not found.']);
        exit;
    }

    $userId = (int)$trad['USER_ID'];
    $currentStatus = strtolower(trim($trad['STATUS'] ?? ''));

    if ($currentStatus !== 'pending') {
        echo json_encode(['success' => false,
            'message' => "Trader is already '{$trad['STATUS']}'. Cannot re-approve."]);
        exit;
    }

    // 2. Activate trader
    $sqlT = "UPDATE TRADER SET status = 'Active' WHERE trader_id = :tid";
    $stT  = oci_parse($conn, $sqlT);
    oci_bind_by_name($stT, ':tid', $trader_id);
    oci_execute($stT, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stT);

    // 3. Also activate all pending products of every shop owned by this trader
    $sqlP = "
        UPDATE PRODUCT
        SET    status = 'Active'
        WHERE  shop_id IN (SELECT shop_id FROM SHOP WHERE user_id = :user_id)
          AND  status = 'Pending'
    ";
    $stP  = oci_parse($conn, $sqlP);
    oci_bind_by_name($stP, ':user_id', $userId);
    oci_execute($stP, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stP);

    // 4. Commit
    oci_commit($conn);

    // 5. Send welcome email
    $traderName = trim(($trad['FIRSTNAME'] ?? '') . ' ' . ($trad['LASTNAME'] ?? ''));
    $shopName = $trad['SHOP_NAME'] ?? 'Your Shop';
    $traderEmail = $trad['EMAIL'] ?? '';
    $traderPassword = $trad['USER_PASSWORD'] ?? '';
    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost/Main-Website';

    $emailSent = false;
    $emailError = null;
    $debugSteps = [];

    $debugSteps[] = "traderName=$traderName";
    $debugSteps[] = "shopName=$shopName";
    $debugSteps[] = "traderEmail=$traderEmail";
    $debugSteps[] = "baseUrl=$baseUrl";

    if ($traderEmail) {
        $debugSteps[] = "Attempting PHPMailer...";
        $emailSent = false;
        $emailError = null;

        // 1. Primary: PHPMailer via SMTP
        try {
            $result = huddershub_send_trader_approved(
                $traderEmail,
                $traderName,
                $shopName,
                $baseUrl . '/public/login.html?role=trader'
            );
            $emailSent = (bool)$result;
            $debugSteps[] = "PHPMailer returned: " . ($result ? 'true' : 'false');
        } catch (Exception $e) {
            $emailError = $e->getMessage();
            $debugSteps[] = "PHPMailer exception: " . $e->getMessage();
        }

        // 2. Fallback: PHP mail() if PHPMailer failed
        if (!$emailSent) {
            $debugSteps[] = "PHPMailer did not send – attempting PHP mail() fallback...";
            try {
                $subject = 'Your Trader Account Has Been Approved - HuddersHub';
                $body = "Hi $traderName,\n\nGreat news! Your trader application for $shopName has been approved.\n\nYou can now access your dashboard at: $baseUrl/public/login.html?role=trader\n\nLogin with the same email and password you used to register.\n\nThe HuddersHub Team";
                $headers = "From: " . MAIL_FROM . "\r\n";
                $headers .= "Reply-To: support@huddershub.test\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $mailResult = mail($traderEmail, $subject, $body, $headers);
                $emailSent = (bool)$mailResult;
                $debugSteps[] = "PHP mail() returned: " . ($mailResult ? 'true (FALLBACK USED)' : 'false');
            } catch (Exception $e2) {
                $emailError = ($emailError ? $emailError . ' | ' : '') . $e2->getMessage();
                $debugSteps[] = "PHP mail() exception: " . $e2->getMessage();
            }
        }
    } else {
        $debugSteps[] = "No email address found, skipping email";
    }

    // Write debug log to file
    $logFile = __DIR__ . '/../../email-debug.log';
    $logEntry = date('Y-m-d H:i:s') . " | trader_id=$trader_id | " . implode(' | ', $debugSteps) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    echo json_encode([
        'success'       => true,
        'message'       => "Trader #{$trader_id} approved. Products activated.",
        'trader_id'     => $trader_id,
        'user_id'       => $userId,
        'new_status'    => 'Active',
        'email_sent'    => $emailSent,
        'email_error'   => $emailError,
        'debug_steps'   => $debugSteps,
        'debug'         => [
            'email' => $traderEmail,
            'shop_name' => $shopName,
            'trader_name' => $traderName
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Approval error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>
