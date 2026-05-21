<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

$conn = getDB();

// Get tomorrow's date
$sql = "SELECT TO_CHAR(TRUNC(SYSDATE) + 1, 'DD-MON-YY') AS tomorrow FROM DUAL";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$tomorrow = $row['TOMORROW'];
oci_free_statement($stmt);

// Check if slot already exists for 10:00-13:00 tomorrow
$checkSql = "SELECT slot_id FROM COLLECTION_SLOT WHERE slot_date = :date AND slot_time = '10:00-13:00'";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ':date', $tomorrow);
oci_execute($checkStmt);
$existingSlot = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ($existingSlot) {
    // Update existing slot to have 0 capacity (full)
    $updateSql = "UPDATE COLLECTION_SLOT SET capacity = 0 WHERE slot_id = :slot_id";
    $updateStmt = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStmt, ':slot_id', $existingSlot['SLOT_ID']);
    oci_execute($updateStmt);
    oci_free_statement($updateStmt);
    echo json_encode(['success' => true, 'message' => 'Slot set to 0 capacity (FULL)', 'slot_id' => $existingSlot['SLOT_ID']]);
} else {
    // Create new full slot
    $insertSql = "INSERT INTO COLLECTION_SLOT (slot_id, slot_date, slot_time, capacity, location) 
                  VALUES (seq_Collection_Slot.NEXTVAL, :date, '10:00-13:00', 0, 'Queensgate Market Hall')";
    $insertStmt = oci_parse($conn, $insertSql);
    oci_bind_by_name($insertStmt, ':date', $tomorrow);
    
    if (oci_execute($insertStmt)) {
        // Get the new slot_id
        $idSql = "SELECT seq_Collection_Slot.CURRVAL AS slot_id FROM DUAL";
        $idStmt = oci_parse($conn, $idSql);
        oci_execute($idStmt);
        $idRow = oci_fetch_assoc($idStmt);
        oci_free_statement($idStmt);
        
        echo json_encode(['success' => true, 'message' => 'Full slot created for tomorrow', 'slot_id' => $idRow['SLOT_ID']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create slot']);
    }
    oci_free_statement($insertStmt);
}

oci_close($conn);