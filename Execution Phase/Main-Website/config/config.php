<?php
define('BASE_URL', 'http://localhost/Main-Website');
define('SESSION_NAME', 'hudders_hub');
define('JWT_SECRET', 'hudders_hub_secret_key_2026');

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_name(SESSION_NAME);
	session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}
?>
