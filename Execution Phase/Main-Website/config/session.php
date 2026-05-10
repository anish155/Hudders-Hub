<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Hudders-Hub/Execution Phase/Main-Website/public/login.html');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (strtolower($_SESSION['role']) !== strtolower($role)) {
        header('Location: /Hudders-Hub/Execution Phase/Main-Website/public/index.html');
        exit;
    }
}
?>