<?php
// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['school_id']);
}

function getUserRole() {
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['school_id'])) {
        return 'school';
    }
    return null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function requireRole($requiredRole) {
    requireLogin();
    $userRole = getUserRole();
    if ($userRole !== $requiredRole) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Utility functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatDate($date) {
    return date('M d, Y g:i A', strtotime($date));
}

function getStatusBadgeClass($status) {
    $classes = [
        'submitted' => 'bg-primary',
        'under_review' => 'bg-warning',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'revision_required' => 'bg-secondary'
    ];
    
    return isset($classes[$status]) ? $classes[$status] : 'bg-secondary';
}

function logActivity($db, $userId, $userType, $action, $description = '') {
    $data = [
        'user_id' => $userId,
        'user_type' => $userType,
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ];
    
    $db->insert('system_logs', $data);
}

function uploadFile($file, $uploadDir = UPLOAD_DIR) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file was uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'File is too large'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File is too large (max ' . formatFileSize(MAX_FILE_SIZE) . ')'];
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES)];
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . $file['name'];
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return [
        'success' => true,
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_size' => $file['size']
    ];
}

function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

// Redirect with message
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $class = $alertClass[$type] ?? 'alert-info';
        
        echo "<div class='alert $class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}
?>
