<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['email']) || empty($data['firstname'])) {
    echo json_encode(['success' => false, 'message' => 'Email and firstname are required']);
    exit;
}

$email = trim($data['email']);
$firstname = trim($data['firstname']);
$lastname = trim($data['lastname'] ?? '');
$phone = trim($data['phone'] ?? '');
$shopName = trim($data['shop_name'] ?? '');
$shopLocation = trim($data['shop_location'] ?? '');
$status = $data['status'] ?? 'Active';

try {
    $checkStmt = oci_parse($conn, "SELECT user_id FROM HUDDER_USER WHERE email = :email");
    oci_bind_by_name($checkStmt, ':email', $email);
    oci_execute($checkStmt);
    $existing = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    $userId;
    if ($existing) {
        $userId = (int)$existing['USER_ID'];
        
        $roleStmt = oci_parse($conn, "UPDATE HUDDER_USER SET user_role = 'Trader' WHERE user_id = :user_id");
        oci_bind_by_name($roleStmt, ':user_id', $userId);
        oci_execute($roleStmt);
        oci_free_statement($roleStmt);
    } else {
        $password = password_hash('trader123', PASSWORD_DEFAULT);
        
        $insUser = oci_parse($conn, "
            INSERT INTO HUDDER_USER (user_id, firstname, lastname, email, user_password, user_role, phone_number)
            VALUES (seq_User.NEXTVAL, :firstname, :lastname, :email, :password, 'Trader', :phone)
        ");
        oci_bind_by_name($insUser, ':firstname', $firstname);
        oci_bind_by_name($insUser, ':lastname', $lastname);
        oci_bind_by_name($insUser, ':email', $email);
        oci_bind_by_name($insUser, ':password', $password);
        oci_bind_by_name($insUser, ':phone', $phone);
        
        if (!oci_execute($insUser, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($insUser);
            throw new Exception('Failed to create user: ' . $e['message']);
        }
        oci_free_statement($insUser);

        $idStmt = oci_parse($conn, "SELECT seq_User.CURRVAL AS user_id FROM DUAL");
        oci_execute($idStmt);
        $idRow = oci_fetch_assoc($idStmt);
        $userId = (int)$idRow['USER_ID'];
        oci_free_statement($idStmt);
    }

    $insTrader = oci_parse($conn, "
        INSERT INTO TRADER (trader_id, user_id, status, created_at)
        VALUES (seq_Trader.NEXTVAL, :user_id, :status, SYSDATE)
    ");
    oci_bind_by_name($insTrader, ':user_id', $userId);
    oci_bind_by_name($insTrader, ':status', $status);
    
    if (!oci_execute($insTrader, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($insTrader);
        throw new Exception('Failed to create trader: ' . $e['message']);
    }
    oci_free_statement($insTrader);

    if ($shopName) {
        $insShop = oci_parse($conn, "
            INSERT INTO SHOP (shop_id, name, location, user_id)
            VALUES (seq_Shop.NEXTVAL, :name, :location, :user_id)
        ");
        oci_bind_by_name($insShop, ':name', $shopName);
        oci_bind_by_name($insShop, ':location', $shopLocation);
        oci_bind_by_name($insShop, ':user_id', $userId);
        oci_execute($insShop, OCI_NO_AUTO_COMMIT);
        oci_free_statement($insShop);
    }

    oci_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Trader created successfully',
        'user_id' => $userId
    ]);

} catch (Exception $e) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>