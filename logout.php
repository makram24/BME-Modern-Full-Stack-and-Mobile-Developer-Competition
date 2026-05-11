<?php
// Prevent output buffering issues
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear output buffer before redirect
ob_end_clean();

// Redirect to login page
header("Location: index.php?logout=1");
exit();
?>

