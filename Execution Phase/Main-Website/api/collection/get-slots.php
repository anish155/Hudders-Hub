<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();

$sql = "SELECT cs.*,
               cs.capacity - COUNT(o.order_id) AS available_spaces
        FROM COLLECTION_SLOT cs
        LEFT JOIN HUDDER_ORDER o ON cs.slot_id = o.slot_id
        WHERE cs.slot_date >= TRUNC(SYSDATE)
        GROUP BY cs.slot_id, cs.slot_date, cs.slot_time, cs.capacity, cs.location
        HAVING cs.capacity - COUNT(o.order_id) > 0
        ORDER BY cs.slot_date, cs.slot_time";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$slots = [];
while ($row = oci_fetch_assoc($stmt)) {
    $slots[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $slots]);