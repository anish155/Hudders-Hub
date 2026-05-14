<?php
$username = 'MYWORKSPACE1';
$password = '9815085801@#aA';
$connection_string = 'localhost:1521/XEPDB1';

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'DB Error: ' . $e['message']]));
}
?>