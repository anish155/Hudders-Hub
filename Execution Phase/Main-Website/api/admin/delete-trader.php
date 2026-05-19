<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

requireRole('Admin');

$conn = getDB();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['trader_id'])) {
    echo json_encode(['success' => false, 'message' => 'trader_id is required']);
    exit;
}

$trader_id = (int)$data['trader_id'];

try {
    $getStmt = oci_parse($conn, "SELECT user_id FROM TRADER WHERE trader_id = :trader_id");
    oci_bind_by_name($getStmt, ':trader_id', $trader_id);
    oci_execute($getStmt);
    $trader = oci_fetch_assoc($getStmt);
    oci_free_statement($getStmt);

    if (!$trader) {
        throw new Exception('Trader not found');
    }

    $user_id = (int)$trader['USER_ID'];

    $shopStmt = oci_parse($conn, "DELETE FROM SHOP WHERE user_id = :user_id");
    oci_bind_by_name($shopStmt, ':user_id', $user_id);
    oci_execute($shopStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($shopStmt);

    $prodStmt = oci_parse($conn, "DELETE FROM PRODUCT WHERE user_id = :user_id");
    oci_bind_by_name($prodStmt, ':user_id', $user_id);
    oci_execute($prodStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($prodStmt);

    $traderStmt = oci_parse($conn, "DELETE FROM TRADER WHERE trader_id = :trader_id");
    oci_bind_by_name($traderStmt, ':trader_id', $trader_id);
    
    if (!oci_execute($traderStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($traderStmt);
        throw new Exception('Failed to delete trader: ' . $e['message']);
    }
    oci_free_statement($traderStmt);

    $userStmt = oci_parse($conn, "DELETE FROM HUDDER_USER WHERE user_id = :user_id");
    oci_bind_by_name($userStmt, ':user_id', $user_id);
    oci_execute($userStmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($userStmt);

    oci_commit($conn);

    echo json_encode(['success' => true, 'message' => 'Trader deleted successfully']);

} catch (Exception $e) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>