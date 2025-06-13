<?php
require_once '../config/config.php';

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    logActivity($db, $_SESSION['user_id'], $_SESSION['role'], 'logout', 'User logged out');
} elseif (isset($_SESSION['school_id'])) {
    logActivity($db, $_SESSION['school_id'], 'school', 'logout', 'School logged out');
}

// Clear all session data
session_unset();
session_destroy();

// Start a new session for the flash message
session_start();
redirect('../index.php', 'You have been logged out successfully.', 'info');
?>
