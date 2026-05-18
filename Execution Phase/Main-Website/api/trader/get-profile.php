<?php
/**
 * Get Trader Profile API
 * GET /api/trader/get-profile.php?user_id=N
 *
 * SHOP columns: shop_id, name, description, location, contact_number, user_id
 * NO: logo_url, shop_type, collection_wed/thu/fri
 * HUDDER_USER: no notify_* columns
 */
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$sql = "
    SELECT u.firstname, u.lastname, u.email, u.phone_number, u.address,
           u.notify_new_order, u.notify_daily_report, u.notify_weekly_finance, u.notify_monthly_report,
           s.shop_id, s.name AS shop_name, s.description AS shop_description,
           s.location, s.contact_number, s.shop_logo, s.mimetype, s.filename,
           s.shop_type, s.collection_wed, s.collection_thu, s.collection_fri,
           t.status AS trader_status
    FROM HUDDER_USER u
    JOIN TRADER t ON t.user_id = u.user_id
    JOIN SHOP   s ON s.user_id = u.user_id
    WHERE u.user_id = :user_id
    AND ROWNUM = 1
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$p = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$p) {
    echo json_encode(['success' => false, 'message' => 'Profile not found']);
    exit;
}

oci_close($conn);

echo json_encode([
    'success' => true,
    'data' => [
        'firstname'         => $p['FIRSTNAME'],
        'lastname'          => $p['LASTNAME'],
        'email'             => $p['EMAIL'],
        'phone'             => $p['PHONE_NUMBER'],
        'address'           => $p['ADDRESS'],
        'shop_id'           => (int)$p['SHOP_ID'],
        'shop_name'         => $p['SHOP_NAME'],
        'shop_type'         => $p['SHOP_TYPE'] ?? 'Butcher',
        'shop_description'  => $p['SHOP_DESCRIPTION'],
        'location'          => $p['LOCATION'],
        'contact_number'    => $p['CONTACT_NUMBER'],
        'trader_status'     => $p['TRADER_STATUS'],
        'collection_wed'    => (int)($p['COLLECTION_WED'] ?? 1),
        'collection_thu'    => (int)($p['COLLECTION_THU'] ?? 1),
        'collection_fri'    => (int)($p['COLLECTION_FRI'] ?? 1),
        'notify_new_order'  => (int)($p['NOTIFY_NEW_ORDER'] ?? 1),
        'notify_daily_report' => (int)($p['NOTIFY_DAILY_REPORT'] ?? 0),
        'notify_weekly_finance' => (int)($p['NOTIFY_WEEKLY_FINANCE'] ?? 1),
        'notify_monthly_report' => (int)($p['NOTIFY_MONTHLY_REPORT'] ?? 1),
        'logo_data'  => base64_encode($p['SHOP_LOGO'] ?? ''),
        'logo_mime'  => $p['MIMETYPE'] ?? 'image/png'
    ]
]);
?>