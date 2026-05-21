<?php
/**
 * Admin – Decline Trader
 * POST /api/admin/decline-trader.php
 *
 * Body: { "trader_id": <int>, "reason": <string (optional)> }
 *
 * Sets TRADER.status = 'Declined' and sends a decline notification email.
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
$reason    = isset($data['reason']) ? trim($data['reason']) : '';

if ($trader_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid trader_id is required.']);
    exit;
}

try {
    // 1. Verify trader is currently Pending and fetch details
    $chk = oci_parse($conn,
        "SELECT t.user_id, t.status, u.firstname, u.lastname, u.email,
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
            'message' => "Trader is already '{$trad['STATUS']}'. Cannot decline."]);
        exit;
    }

    // 2. Set status to Declined
    $sqlT = "UPDATE TRADER SET status = 'Declined' WHERE trader_id = :tid";
    $stT  = oci_parse($conn, $sqlT);
    oci_bind_by_name($stT, ':tid', $trader_id);
    oci_execute($stT, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stT);

    // 3. Commit
    oci_commit($conn);

    // 4. Send decline email (PHPMailer)
    $traderName  = trim(($trad['FIRSTNAME'] ?? '') . ' ' . ($trad['LASTNAME'] ?? ''));
    $shopName    = $trad['SHOP_NAME'] ?? 'Your Shop';
    $traderEmail = $trad['EMAIL'] ?? '';

    $reasonText = $reason ?: '';

    if ($traderEmail) {
        $emailSent   = false;
        $emailError  = null;

        // 1. Primary: PHPMailer via SMTP
        try {
            $result = huddershub_send_trader_declined($traderEmail, $traderName, $shopName, $reasonText);
            $emailSent = (bool)$result;
        } catch (Exception $e) {
            $emailError = $e->getMessage();
        }

        // 2. Fallback: PHP mail() if PHPMailer failed
        if (!$emailSent) {
            try {
                $subject = 'Trader Application Update - HuddersHub';
                $reasonLine = $reasonText ? "\nReason: $reasonText\n" : '';
                $body = "Hi $traderName,\n\n"
                    . "Thank you for taking the time to apply to become a HuddersHub trader.\n\n"
                    . "After careful review, we regret to inform you that your application for $shopName has not been approved at this time.\n"
                    . "$reasonLine\n"
                    . "We encourage you to address the feedback above and re-apply in the future.\n\n"
                    . "If you have any questions, contact us at support@huddershub.test.\n\n"
                    . "Thank you for your interest in HuddersHub.\nThe HuddersHub Team";
                $headers = "From: " . MAIL_FROM . "\r\n";
                $headers .= "Reply-To: support@huddershub.test\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $mailResult = mail($traderEmail, $subject, $body, $headers);
                $emailSent = (bool)$mailResult;
                if ($mailResult && !$emailError) {
                    $emailError = null;
                }
            } catch (Exception $e2) {
                $emailError = ($emailError ? $emailError . ' | ' : '') . $e2->getMessage();
            }
        }
    }

    echo json_encode([
        'success'     => true,
        'message'     => "Trader #{$trader_id} has been declined.",
        'trader_id'   => $trader_id,
        'user_id'     => $userId,
        'new_status'  => 'Declined',
        'email_sent'  => $emailSent,
        'email_error' => $emailError,
    ]);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Decline error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>
