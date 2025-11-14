<?php
// ---------------------------
// Session Handling
// ---------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------
// App Information
// ---------------------------
define('APP_NAME', 'SafeSpeak');
define('APP_VERSION', '1.0.0');

// ---------------------------
// Base URL Configuration
// ---------------------------
define('BASE_URL', 'http://localhost/CapstoneTracker/');

// ---------------------------
// Timezone Configuration
// ---------------------------
// Set your default timezone here. For a list of supported timezones, see: https://www.php.net/manual/en/timezones.php
define('TIMEZONE', 'Asia/Manila'); // Example: 'America/New_York', 'Europe/London', 'Asia/Tokyo'
date_default_timezone_set(TIMEZONE);

// ---------------------------
// File Upload Configuration
// ---------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('MAX_EVIDENCE_FILES', 10); // Maximum number of evidence files per report
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'wmv', 'mkv', 'heic', 'heif']);

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ---------------------------
// Email Configuration
// ---------------------------
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'haroldarreglado121618@gmail.com'); // Your SMTP username
define('SMTP_PASSWORD', 'aqqtzxdmeousvgmb'); // Your SMTP password or app password
define('FROM_EMAIL', 'haroldarreglado121618@gmail.com');
define('FROM_NAME', 'SafeSpeak');

// ---------------------------
// Include Core Files
// ---------------------------
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ---------------------------
// Initialize Database
// ---------------------------
$db = new Database();

// ---------------------------
// Fallback Helper Functions
// ---------------------------
if (!function_exists('verifyPassword')) {
    function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
}

// Set default charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
?>
