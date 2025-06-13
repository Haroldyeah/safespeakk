<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application configuration
define('APP_NAME', 'Capstone Report Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);

// Email configuration (if needed)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@capstone-system.com');
define('FROM_NAME', 'Capstone System');

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Include database and functions
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize database connection
$db = new Database();
?>
