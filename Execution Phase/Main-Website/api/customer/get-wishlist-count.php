<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$conn = oci_connect('HUDDERSHUB', 'StrongPassword11', 'localhost:1521/XEPDB1');
if (!$conn) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$sql = "SELECT COUNT(*) as cnt FROM FAVOURITE WHERE user_id = :user_id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $user_id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);

echo json_encode(['success' => true, 'count' => (int)$row['CNT']]);

oci_close($conn);
