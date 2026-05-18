<?php
// Suppress warnings to keep JSON output clean
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

try {
    $fname = trim($data['firstname']);
    $lname = trim($data['lastname']);
    $email = trim($data['email']);
    $pass  = $data['password'];
    $role  = strtolower(trim($data['user_role'] ?? 'customer'));
    
    $phone   = trim($data['phone_number'] ?? '');
    $address = trim($data['address'] ?? '');
    $dob     = $data['date_of_birth'] ?? ''; 
    $gender  = trim($data['gender'] ?? '');

    // 1. CHECK IF EMAIL ALREADY EXISTS
    $checkSql = "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE email = :em";
    $checkStmt = oci_parse($conn, $checkSql);
    oci_bind_by_name($checkStmt, ':em', $email);
    oci_execute($checkStmt);
    $checkRow = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if ($checkRow['CNT'] > 0) {
        throw new Exception("The email '$email' is already registered. Please login.");
    }

    // 2. Get max user_id and sync sequence to avoid ORA-00001
    $maxQ = oci_parse($conn, "SELECT NVL(MAX(user_id),0) AS max_id FROM HUDDER_USER");
    oci_execute($maxQ);
    $maxRow = oci_fetch_assoc($maxQ);
    $maxId = (int)$maxRow['MAX_ID'];
    oci_free_statement($maxQ);
    $syncQ = oci_parse($conn, "SELECT seq_Hudder_user.NEXTVAL FROM DUAL");
    oci_execute($syncQ);
    $seqRow = oci_fetch_assoc($syncQ);
    $seqVal = (int)$seqRow['NEXTVAL'];
    oci_free_statement($syncQ);
    if ($seqVal <= $maxId) {
        $diff = $maxId - $seqVal + 100;
        $alterSql = "ALTER SEQUENCE seq_Hudder_user INCREMENT BY " . $diff;
        $alterStmt = oci_parse($conn, $alterSql);
        @oci_execute($alterStmt);
        oci_free_statement($alterStmt);
        $advStmt = oci_parse($conn, "SELECT seq_Hudder_user.NEXTVAL FROM DUAL");
        @oci_execute($advStmt);
        oci_free_statement($advStmt);
        $resetStmt = oci_parse($conn, "ALTER SEQUENCE seq_Hudder_user INCREMENT BY 1");
        @oci_execute($resetStmt);
        oci_free_statement($resetStmt);
    }

    // 3. INSERT INTO HUDDER_USER with sequence
    $sql = "INSERT INTO HUDDER_USER (
                user_id, firstname, lastname, email, user_password, 
                user_role, phone_number, address, date_of_birth, gender
            ) VALUES (
                seq_Hudder_user.NEXTVAL, :f_name, :l_name, :e_mail, :p_word, 
                :role, :phone, :addr, TO_DATE(:dob, 'YYYY-MM-DD'), :gender
            )";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':f_name', $fname);
    oci_bind_by_name($stmt, ':l_name', $lname);
    oci_bind_by_name($stmt, ':e_mail', $email);
    oci_bind_by_name($stmt, ':p_word', $pass);
    oci_bind_by_name($stmt, ':role', $role);
    oci_bind_by_name($stmt, ':phone', $phone);
    oci_bind_by_name($stmt, ':addr', $address);
    oci_bind_by_name($stmt, ':gender', $gender);
    
    // Bind DOB - if empty, Oracle handles it better as a null variable
    $dob_val = !empty($dob) ? $dob : null;
    oci_bind_by_name($stmt, ':dob', $dob_val);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception("Hudder_User Error: " . $e['message']);
    }
    oci_free_statement($stmt);

    // Get the new user_id from sequence AFTER insert
    $id_stmt = oci_parse($conn, "SELECT seq_Hudder_user.CURRVAL AS NEWID FROM DUAL");
    oci_execute($id_stmt);
    $row = oci_fetch_assoc($id_stmt);
    $new_uid = $row['NEWID'];
    oci_free_statement($id_stmt);

    // 4. INSERT INTO CUSTOMER with customer_id via max+1
    $maxCq = oci_parse($conn, "SELECT NVL(MAX(customer_id),0)+1 AS new_id FROM CUSTOMER");
    oci_execute($maxCq);
    $maxCRow = oci_fetch_assoc($maxCq);
    $newCid = (int)$maxCRow['NEW_ID'];
    oci_free_statement($maxCq);
    $c_sql = "INSERT INTO CUSTOMER (customer_id, user_id) VALUES (:cid, :u_id_cust)";
    $c_stmt = oci_parse($conn, $c_sql);
    oci_bind_by_name($c_stmt, ':cid', $newCid);
    oci_bind_by_name($c_stmt, ':u_id_cust', $new_uid);

    if (!oci_execute($c_stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($c_stmt);
        throw new Exception("Customer Table Error: " . $e['message']);
    }
    oci_free_statement($c_stmt);

    // 5. Generate verification token
    $token = bin2hex(random_bytes(32));
    $token_stmt = oci_parse($conn, "UPDATE HUDDER_USER SET verification_token = :token WHERE user_id = :user_id");
    oci_bind_by_name($token_stmt, ':token', $token);
    oci_bind_by_name($token_stmt, ':user_id', $new_uid);
    oci_execute($token_stmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($token_stmt);

    // 6. COMMIT TRANSACTION
    oci_commit($conn);
    
    // 7. Send verification email
    $verify_link = BASE_URL . '/public/verify-email.html?token=' . $token . '&email=' . urlencode($email);
    
    // Include email sending (mock for now - returns link in response)
    echo json_encode([
        'success' => true, 
        'message' => "Welcome $fname! Account created. Please check your email to verify your account.",
        'verification_link' => $verify_link // Remove in production - for testing only
    ]);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>