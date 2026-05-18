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

    if (isset($data['firstname']) || isset($data['lastname']) || isset($data['phone'])) {
        $fields = [];
        if (isset($data['firstname'])) {
            $fields[] = "firstname = :firstname";
        }
        if (isset($data['lastname'])) {
            $fields[] = "lastname = :lastname";
        }
        if (isset($data['phone'])) {
            $fields[] = "phone_number = :phone";
        }

        $sql = "UPDATE HUDDER_USER SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $user_id);
        
        if (isset($data['firstname'])) {
            oci_bind_by_name($stmt, ':firstname', $data['firstname']);
        }
        if (isset($data['lastname'])) {
            oci_bind_by_name($stmt, ':lastname', $data['lastname']);
        }
        if (isset($data['phone'])) {
            oci_bind_by_name($stmt, ':phone', $data['phone']);
        }

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception('Failed to update user: ' . $e['message']);
        }
        oci_free_statement($stmt);
    }

    if (isset($data['status'])) {
        $stmt = oci_parse($conn, "UPDATE TRADER SET status = :status WHERE trader_id = :trader_id");
        oci_bind_by_name($stmt, ':status', $data['status']);
        oci_bind_by_name($stmt, ':trader_id', $trader_id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception('Failed to update trader status: ' . $e['message']);
        }
        oci_free_statement($stmt);
    }

    if (isset($data['shop_name']) || isset($data['shop_location'])) {
        $shopFields = [];
        if (isset($data['shop_name'])) {
            $shopFields[] = "name = :shop_name";
        }
        if (isset($data['shop_location'])) {
            $shopFields[] = "location = :shop_location";
        }

        if (!empty($shopFields)) {
            $shopSql = "UPDATE SHOP SET " . implode(', ', $shopFields) . " WHERE user_id = :user_id";
            $shopStmt = oci_parse($conn, $shopSql);
            oci_bind_by_name($shopStmt, ':user_id', $user_id);
            
            if (isset($data['shop_name'])) {
                oci_bind_by_name($shopStmt, ':shop_name', $data['shop_name']);
            }
            if (isset($data['shop_location'])) {
                oci_bind_by_name($shopStmt, ':shop_location', $data['shop_location']);
            }

            if (!oci_execute($shopStmt)) {
                $e = oci_error($shopStmt);
                throw new Exception('Failed to update shop: ' . $e['message']);
            }
            oci_free_statement($shopStmt);
        }
    }

    oci_commit($conn);

    echo json_encode(['success' => true, 'message' => 'Trader updated successfully']);

} catch (Exception $e) {
    oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
?>