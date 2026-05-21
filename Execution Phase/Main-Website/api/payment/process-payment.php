<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/mailer.php';

// Check if payment was cancelled
if (isset($_GET['cancel']) || isset($_GET['cancelled'])) {
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    if ($order_id) {
        header('Location: ' . BASE_URL . '/public/payment.html?order_id=' . $order_id . '&error=cancelled');
        exit;
    }
    header('Location: ' . BASE_URL . '/public/cart.html');
    exit;
}

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

        // Update order status to 'Preparing' (matches database check constraint)
        $updOrder = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Preparing' WHERE order_id = :oid");
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
// When returning from PayPal, we treat it as successful payment (simulated)
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

$conn = getDB();

// Update order status to 'Preparing' (matches database check constraint)
$upd = oci_parse($conn, "UPDATE HUDDER_ORDER SET status = 'Preparing' WHERE order_id = :oid");
oci_bind_by_name($upd, ':oid', $order_id);
oci_execute($upd, OCI_NO_AUTO_COMMIT);

// Update payment status to 'Completed'
$updPay = oci_parse($conn, "UPDATE PAYMENT SET status = 'Completed', method = 'PayPal' WHERE order_id = :oid");
oci_bind_by_name($updPay, ':oid', $order_id);
oci_execute($updPay, OCI_NO_AUTO_COMMIT);

oci_commit($conn);
oci_close($conn);

unset($_SESSION['pending_order']);

// Redirect to invoice page with success
header('Location: ' . BASE_URL . '/public/invoice.html?order_id=' . $order_id . '&paid=1');
exit;
?>
