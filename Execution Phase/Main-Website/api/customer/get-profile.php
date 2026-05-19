<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'No user_id provided']);
    exit;
}

// Direct connection without includes
$conn = oci_connect('HUDDERSHUB', 'StrongPassword11', 'localhost:1521/XEPDB1');
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$sql = "SELECT user_id, firstname, lastname, email, phone_number, address,
           TO_CHAR(NVL(created_at, SYSDATE), 'YYYY-MM-DD') AS created_at
    FROM HUDDER_USER
    WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);

$user = oci_fetch_assoc($stmt);

if ($user) {
    echo json_encode([
        'success' => true,
        'data' => [
            'FIRSTNAME' => $user['FIRSTNAME'],
            'LASTNAME' => $user['LASTNAME'],
            'EMAIL' => $user['EMAIL'],
            'PHONE_NUMBER' => $user['PHONE_NUMBER'] ?? '',
            'ADDRESS' => $user['ADDRESS'] ?? '',
            'CREATED_AT' => $user['CREATED_AT'] ?? ''
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'User not found']);
}

oci_close($conn);
