<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id         = isset($data['user_id'])          ? (int)$data['user_id']              : 0;
$firstname       = isset($data['firstname'])         ? trim($data['firstname'])           : '';
$lastname        = isset($data['lastname'])          ? trim($data['lastname'])            : '';
$phone           = isset($data['phone'])             ? trim($data['phone'])               : '';
$address         = isset($data['address'])           ? trim($data['address'])             : '';
$shop_name       = isset($data['shop_name'])         ? trim($data['shop_name'])           : '';
$shop_location   = isset($data['shop_location'])     ? trim($data['shop_location'])       : '';
$shop_description= isset($data['shop_description'])  ? trim($data['shop_description'])    : '';
$shop_contact    = isset($data['shop_contact'])      ? trim($data['shop_contact'])        : '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// Update user
$sql_user = "
    UPDATE HUDDER_USER
    SET firstname = :firstname, lastname = :lastname,
        phone_number = :phone, address = :address
    WHERE user_id = :user_id
";
$stmt_user = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt_user, ':firstname', $firstname);
oci_bind_by_name($stmt_user, ':lastname',  $lastname);
oci_bind_by_name($stmt_user, ':phone',     $phone);
oci_bind_by_name($stmt_user, ':address',   $address);
oci_bind_by_name($stmt_user, ':user_id',   $user_id);
oci_execute($stmt_user, OCI_NO_AUTO_COMMIT);

// Update shop
$sql_shop = "
    UPDATE SHOP
    SET name = :shop_name, description = :shop_description,
        location = :shop_location, contact_number = :shop_contact
    WHERE user_id = :user_id
";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':shop_name',        $shop_name);
oci_bind_by_name($stmt_shop, ':shop_description',  $shop_description);
oci_bind_by_name($stmt_shop, ':shop_location',     $shop_location);
oci_bind_by_name($stmt_shop, ':shop_contact',      $shop_contact);
oci_bind_by_name($stmt_shop, ':user_id',           $user_id);
oci_execute($stmt_shop, OCI_NO_AUTO_COMMIT);

oci_commit($conn);

echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);

oci_close($conn);
?>