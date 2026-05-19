<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();

$conn = getDB();
$user_id = $_SESSION['user_id'];

$sql = "SELECT email_notifications FROM HUDDER_USER WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);

$user = oci_fetch_assoc($stmt);
oci_free_statement($stmt);
oci_close($conn);

$settings = ['order_updates' => true, 'promotions' => true, 'newsletter' => false, 'security_alerts' => true];

if ($user && !empty($user['EMAIL_NOTIFICATIONS'])) {
    $decoded = json_decode($user['EMAIL_NOTIFICATIONS'], true);
    if ($decoded) $settings = array_merge($settings, $decoded);
}

echo json_encode(['success' => true, 'data' => $settings]);
