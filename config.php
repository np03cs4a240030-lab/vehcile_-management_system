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

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
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

/**
 * Auto-complete bookings whose end_date has passed
 * Call this on any page load
 */
function autoCompleteBookings() {
    global $conn;
    $today = date('Y-m-d');

    // Find approved/ongoing bookings whose end_date < today
    $result = $conn->query("
        SELECT id, vehicle_id FROM bookings
        WHERE status IN ('approved', 'ongoing')
        AND end_date != '0000-00-00'
        AND end_date < '$today'
    ");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bid = (int)$row['id'];
            $vid = (int)$row['vehicle_id'];

            // Mark booking as completed
            $conn->query("UPDATE bookings SET status = 'completed' WHERE id = $bid");

            // Restore vehicle availability
            $conn->query("UPDATE vehicles SET availability = 1 WHERE id = $vid");
        }
    }
}

// Run auto-complete on every page load
autoCompleteBookings();

?>