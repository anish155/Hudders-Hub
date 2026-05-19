<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();
$conn = getDB();
$userId = (int) $_SESSION['user_id'];

try {
	$stmt = oci_parse($conn, 'DELETE FROM CART_ITEM WHERE cart_id IN (SELECT cart_id FROM CART WHERE user_id = :user_id)');
	oci_bind_by_name($stmt, ':user_id', $userId);

	if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
		$e = oci_error($stmt);
		throw new Exception($e['message']);
	}

	oci_commit($conn);
	oci_free_statement($stmt);
	oci_close($conn);

	echo json_encode(['success' => true, 'message' => 'Cart cleared']);
} catch (Exception $e) {
	oci_rollback($conn);
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
