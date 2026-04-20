<?php

// Base URL
define('BASE_URL', '/vehcile_-management_system');

// Include DB connection
require_once __DIR__ . '/includes/connection.php';

/**
 * Check if admin or super admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && 
           in_array($_SESSION['role'], ['admin', 'super_admin']);
}

/**
 * Get logged-in user
 */
function getCurrentUser() {
    global $conn;

    if (!isLoggedIn()) {
        return null;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check POST request
 */
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Require admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}

/**
 * Flash messages
 */
function setFlash($key, $message) {
    $_SESSION[$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
?>