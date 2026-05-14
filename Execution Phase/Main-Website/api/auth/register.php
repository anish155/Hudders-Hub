<?php
// Suppress warnings to keep JSON output clean
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
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

    // 2. GET NEXT USER_ID
    $id_stmt = oci_parse($conn, "SELECT (NVL(MAX(user_id), 0) + 1) AS NEWID FROM HUDDER_USER");
    oci_execute($id_stmt);
    $row = oci_fetch_assoc($id_stmt);
    $new_uid = $row['NEWID'];
    oci_free_statement($id_stmt);

    $hashed_pw = password_hash($pass, PASSWORD_BCRYPT);

    // 3. INSERT INTO HUDDER_USER
    // We use a safe way to handle the optional Date of Birth
    $sql = "INSERT INTO HUDDER_USER (
                user_id, firstname, lastname, email, user_password, 
                user_role, phone_number, address, date_of_birth, gender
            ) VALUES (
                :u_id, :f_name, :l_name, :e_mail, :p_word, 
                'customer', :phone, :addr, TO_DATE(:dob, 'YYYY-MM-DD'), :gender
            )";
    
    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ':u_id', $new_uid);
    oci_bind_by_name($stmt, ':f_name', $fname);
    oci_bind_by_name($stmt, ':l_name', $lname);
    oci_bind_by_name($stmt, ':e_mail', $email);
    oci_bind_by_name($stmt, ':p_word', $hashed_pw);
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

    // 4. INSERT INTO CUSTOMER
    $c_sql = "INSERT INTO CUSTOMER (customer_id, user_id) 
              VALUES ((SELECT NVL(MAX(customer_id), 0) + 1 FROM CUSTOMER), :u_id_cust)";
    $c_stmt = oci_parse($conn, $c_sql);
    oci_bind_by_name($c_stmt, ':u_id_cust', $new_uid);

    if (!oci_execute($c_stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($c_stmt);
        throw new Exception("Customer Table Error: " . $e['message']);
    }
    oci_free_statement($c_stmt);

    // 5. COMMIT TRANSACTION
    oci_commit($conn);
    echo json_encode(['success' => true, 'message' => "Welcome $fname! Account created successfully."]);

} catch (Exception $e) {
    if (isset($conn)) oci_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>