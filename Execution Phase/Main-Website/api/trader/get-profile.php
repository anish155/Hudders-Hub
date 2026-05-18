<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$sql = "
    SELECT u.firstname, u.lastname, u.email, u.phone_number, u.address,
           s.name AS shop_name, s.description AS shop_description,
           s.location AS shop_location, s.contact_number AS shop_contact,
           t.status AS trader_status
    FROM HUDDER_USER u
    JOIN TRADER t ON t.user_id = u.user_id
    JOIN SHOP s   ON s.user_id = u.user_id
    WHERE u.user_id = :user_id
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Profile not found']);
    exit;
}

echo json_encode([
    'success'          => true,
    'firstname'        => $row['FIRSTNAME'],
    'lastname'         => $row['LASTNAME'],
    'email'            => $row['EMAIL'],
    'phone'            => $row['PHONE_NUMBER'],
    'address'          => $row['ADDRESS'],
    'shop_name'        => $row['SHOP_NAME'],
    'shop_description' => $row['SHOP_DESCRIPTION'],
    'shop_location'    => $row['SHOP_LOCATION'],
    'shop_contact'     => $row['SHOP_CONTACT'],
    'trader_status'    => $row['TRADER_STATUS'],
]);

oci_close($conn);
?>