<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('hudders_hub');
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']));
    }
}

function requireRole($role) {
    requireLogin();
    if (strtolower($_SESSION['user_role'] ?? '') !== strtolower($role)) {
        header('Content-Type: application/json');
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Forbidden. Insufficient permissions.']));
    }
}
?>
