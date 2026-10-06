<?php
/**
 * User Logout Script
 * Digital Marketing Campaign Tracker
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a clean session to pass the logout flash message
session_start();
require_once __DIR__ . '/includes/db_connect.php';
set_flash_message('info', 'You have been successfully logged out.');

// Redirect to login page
header('Location: login.php');
exit();
