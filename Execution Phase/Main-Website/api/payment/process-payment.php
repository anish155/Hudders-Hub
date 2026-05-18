<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

// ── POST: called by payment.html or internal API ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['order_id'])) {
        echo json_encode(['success' => false, 'error' => 'Missing order_id']);
        exit;
    }

    $order_id       = (int)$input['order_id'];
    $payment_method = $input['payment_method'] ?? 'PayPal';
    $conn           = getDB();

    try {
        // Update payment record
        $upd = oci_parse($conn, "UPDATE PAYMENT SET method = :method, status = 'Completed' WHERE order_id = :oid");
        oci_bind_by_name($upd, ':method', $payment_method);
        oci_bind_by_name($upd, ':oid', $order_id);
        oci_execute($upd, OCI_NO_AUTO_COMMIT);

        // Update order status to 'Ready' (valid constraint value)
        $updOrder = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Ready' WHERE order_id = :oid");
        oci_bind_by_name($updOrder, ':oid', $order_id);
        oci_execute($updOrder, OCI_NO_AUTO_COMMIT);

        oci_commit($conn);
        oci_close($conn);

        echo json_encode(['success' => true, 'message' => 'Payment processed']);
    } catch (Exception $e) {
        oci_rollback($conn);
        oci_close($conn);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── GET: PayPal return callback ───────────────────────────────────────────
$order = $_SESSION['pending_order'] ?? null;

if (!$order && isset($_GET['order_id'])) {
    $order = [
        'order_id' => (int)$_GET['order_id'],
        'user_id'  => (int)($_GET['user_id'] ?? 0),
        'amount'   => (float)($_GET['amt']    ?? 0),
    ];
}

if (!$order || empty($order['order_id'])) {
    header('Location: ' . BASE_URL . '/public/index.html');
    exit;
}

$order_id = (int)$order['order_id'];
$user_id  = (int)$order['user_id'];
$amount   = (float)$order['amount'];

$conn = getDB();

try {
    // Update order status to 'Ready'
    $upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Ready' WHERE order_id = :oid");
    oci_bind_by_name($upd, ':oid', $order_id);
    oci_execute($upd, OCI_NO_AUTO_COMMIT);

    // Update payment status to 'Completed'
    $updPay = oci_parse($conn, "UPDATE PAYMENT SET status = 'Completed', method = 'PayPal' WHERE order_id = :oid");
    oci_bind_by_name($updPay, ':oid', $order_id);
    oci_execute($updPay, OCI_NO_AUTO_COMMIT);

    oci_commit($conn);
} catch (Exception $e) {
    oci_rollback($conn);
    error_log('[process-payment] Error: ' . $e->getMessage());
}

oci_close($conn);
unset($_SESSION['pending_order']);

// Redirect to invoice page
header('Location: ' . BASE_URL . '/public/invoice.html?order_id=' . $order_id . '&paid=1');
exit;
?>
