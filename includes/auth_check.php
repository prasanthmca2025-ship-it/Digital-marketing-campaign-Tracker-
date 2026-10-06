<?php
/**
 * Authentication Check Middleware
 * Digital Marketing Campaign Tracker
 * 
 * Verifies that the user has an active session before accessing protected pages.
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and helper functions if not already loaded
require_once __DIR__ . '/db_connect.php';

/**
 * Check if the user is currently logged in.
 *
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Helper to get current authenticated user ID.
 *
 * @return int|null
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Helper to get current authenticated user name.
 *
 * @return string
 */
function current_user_name() {
    return $_SESSION['user_name'] ?? 'Marketer';
}

/**
 * Helper to get current authenticated user role.
 *
 * @return string
 */
function current_user_role() {
    return $_SESSION['user_role'] ?? 'Marketer';
}

// Enforce route protection
if (!is_logged_in()) {
    set_flash_message('warning', 'Please log in to access this page.');
    // Redirect to login page
    header('Location: login.php');
    exit();
}
