<?php
function getDB() {
    static $conn = null;

    if ($conn) {
        return $conn;
    }

    if (!function_exists('oci_connect')) {
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'message' => 'PHP OCI8 extension is not enabled. Load OCI8 in XAMPP before testing database connections.'
        ]));
    }

    $username = 'HUDDERSHUB';
    $password = 'StrongPassword11';
    $connection_string = 'localhost:1521/XEPDB1';

    $conn = oci_connect($username, $password, $connection_string);

    if (!$conn) {
        $e = oci_error();
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'Oracle Connection Error: ' . $e['message']]));
    }

    return $conn;
}

$conn = getDB();