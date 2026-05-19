<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();
$slot_id = $_GET['slot_id'] ?? null;

if (!$slot_id) {
    echo json_encode(['success' => false, 'error' => 'Slot ID required']);
    exit;
}

$sql = "SELECT cs.capacity - COUNT(o.order_id) AS available
        FROM COLLECTION_SLOT cs
        LEFT JOIN HUDDER_ORDER o ON cs.slot_id = o.slot_id
        WHERE cs.slot_id = :slot_id
        GROUP BY cs.capacity";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':slot_id', $slot_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

oci_free_statement($stmt);
oci_close($conn);

if ($row && $row['AVAILABLE'] > 0) {
    echo json_encode(['success' => true, 'available' => $row['AVAILABLE']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Slot is full']);
}