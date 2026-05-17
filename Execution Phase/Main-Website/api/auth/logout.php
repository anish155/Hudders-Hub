<?php
/**
 * Logout API
 * Endpoint: GET or POST /api/auth/logout.php
 * Destroys the session and clears all session data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Destroy the session
session_unset();
session_destroy();

// Return success response
header('Location: ../../public/login.html');
exit;
?>
