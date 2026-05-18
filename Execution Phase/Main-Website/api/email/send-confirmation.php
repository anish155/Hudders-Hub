<?php
require_once '../../config/database.php';
require_once '../../config/config.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;
}

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$conn = getDB();

// Get order details
$sql = "SELECT o.order_id, o.order_date, o.status, o.slot_id,
               u.firstname, u.email,
               cs.slot_date, cs.slot_time, cs.location,
               p.amount, p.method, p.status AS payment_status
        FROM HUDDER_ORDER o
        JOIN HUDDER_USER u ON o.user_id = u.user_id
        LEFT JOIN COLLECTION_SLOT cs ON o.slot_id = cs.slot_id
        LEFT JOIN PAYMENT p ON o.order_id = p.order_id
        WHERE o.order_id = :order_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':order_id', $order_id);
oci_execute($stmt);
$order = oci_fetch_assoc($stmt);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Get products
$prodSql = "SELECT op.product_id, p.name, op.quantity, op.unit_price
             FROM ORDER_PRODUCT op
             JOIN PRODUCT p ON op.product_id = p.product_id
             WHERE op.order_id = :order_id";
$prodStmt = oci_parse($conn, $prodSql);
oci_bind_by_name($prodStmt, ':order_id', $order_id);
oci_execute($prodStmt);

$products = [];
while ($prod = oci_fetch_assoc($prodStmt)) {
    $products[] = $prod;
}
oci_free_statement($prodStmt);
oci_close($conn);

// Build email content
$itemsHtml = '';
foreach ($products as $p) {
    $itemsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . $p['NAME'] . ' x' . $p['QUANTITY'] . '</td><td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">£' . number_format($p['QUANTITY'] * $p['UNIT_PRICE'], 2) . '</td></tr>';
}

$slotDate = $order['SLOT_DATE'] ? date('l, jS F Y', strtotime($order['SLOT_DATE'])) : 'TBD';
$slotTime = $order['SLOT_TIME'] ?: '10:00 - 13:00';

$emailBody = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation - HuddersHub</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #0F260B; color: white; padding: 20px; text-align: center;">
        <h1 style="margin:0;font-style:italic;">HuddersHub</h1>
        <p>Order Confirmation</p>
    </div>
    
    <h2 style="color: #10B981;">✓ Payment Successful!</h2>
    <p>Dear ' . htmlspecialchars($order['FIRSTNAME']) . ',</p>
    <p>Thank you for your order! Here are your order details:</p>
    
    <h3>Order #' . $order['ORDER_ID'] . '</h3>
    <p><strong>Total Paid:</strong> £' . number_format($order['AMOUNT'], 2) . '</p>
    <p><strong>Payment Method:</strong> ' . htmlspecialchars($order['METHOD']) . '</p>
    
    <h3>Collection Details</h3>
    <p><strong>Date:</strong> ' . $slotDate . '</p>
    <p><strong>Time:</strong> ' . $slotTime . '</p>
    <p><strong>Location:</strong> ' . htmlspecialchars($order['LOCATION'] ?: 'Queensgate Market Hall, Huddersfield') . '</p>
    
    <h3>Order Items</h3>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f5f5f5;">
                <th style="padding:8px;text-align:left;">Item</th>
                <th style="padding:8px;text-align:right;">Price</th>
            </tr>
        </thead>
        <tbody>
            ' . $itemsHtml . '
        </tbody>
    </table>
    
    <p style="margin-top:20px;">Please bring your order confirmation when collecting your items.</p>
    <p>Thank you for shopping local with HuddersHub!</p>
    
    <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
    <p style="color:#666;font-size:12px;">HuddersHub - Local marketplace for Huddersfield</p>
</body>
</html>';

// For now, just return success (email sending requires SMTP configuration)
echo json_encode([
    'success' => true,
    'message' => 'Confirmation email prepared',
    'email' => $order['EMAIL'],
    'order_id' => $order_id
]);

// In production, integrate with PHPMailer to actually send the email
// The email content is in $emailBody and recipient is $order['EMAIL']
?>