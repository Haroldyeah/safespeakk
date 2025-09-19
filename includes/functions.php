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
            return ['success' => false, 'error' => 'No file was uploaded.'];
        case UPLOAD_ERR_INI_SIZE:
            return ['success' => false, 'error' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.'];
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'];
        case UPLOAD_ERR_PARTIAL:
            return ['success' => false, 'error' => 'The uploaded file was only partially uploaded.'];
        case UPLOAD_ERR_NO_TMP_DIR:
            return ['success' => false, 'error' => 'Missing a temporary folder.'];
        case UPLOAD_ERR_CANT_WRITE:
            return ['success' => false, 'error' => 'Failed to write file to disk.'];
        case UPLOAD_ERR_EXTENSION:
            return ['success' => false, 'error' => 'A PHP extension stopped the file upload.'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error.'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File is too large (max ' . formatFileSize(MAX_FILE_SIZE) . ')'];
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type using MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Extended MIME type mapping with more comprehensive HEIC/HEIF support
    $allowedMimeTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'mp4' => ['video/mp4'],
        'mov' => ['video/quicktime'],
        'avi' => ['video/x-msvideo'],
        'wmv' => ['video/x-ms-wmv'],
        'mkv' => ['video/x-matroska'],
        // Enhanced HEIC/HEIF support with all possible MIME types
        'heic' => [
            'image/heic', 
            'image/heic-sequence', 
            'image/heif',
            'image/heif-sequence',
            'application/octet-stream' // Fallback for systems that don't recognize HEIC
        ],
        'heif' => [
            'image/heif', 
            'image/heif-sequence', 
            'image/heic',
            'image/heic-sequence',
            'application/octet-stream' // Fallback for systems that don't recognize HEIF
        ],
    ];

    // Special handling for HEIC/HEIF files
    if (in_array($extension, ['heic', 'heif'])) {
        // For HEIC/HEIF, also check file signature (magic bytes) as additional validation
        $fileHandle = fopen($file['tmp_name'], 'rb');
        if ($fileHandle) {
            // Read first 12 bytes to check for HEIC/HEIF signature
            $header = fread($fileHandle, 12);
            fclose($fileHandle);
            
            // HEIC/HEIF files typically start with specific signatures
            $isHeicHeif = false;
            if (strlen($header) >= 12) {
                // Check for common HEIC/HEIF signatures
                $signatures = [
                    'ftypheic', // HEIC signature at offset 4
                    'ftypmif1', // HEIF signature at offset 4
                    'ftypheix', // HEIC sequence
                    'ftyphevc', // HEVC-based HEIC
                    'ftypheim', // HEIF sequence
                ];
                
                $fileSignature = substr($header, 4, 8);
                foreach ($signatures as $signature) {
                    if (strpos($fileSignature, substr($signature, 0, 4)) === 0) {
                        $isHeicHeif = true;
                        break;
                    }
                }
            }
            
            // If MIME type is not recognized but file signature indicates HEIC/HEIF, allow it
            if ($isHeicHeif) {
                // File is valid HEIC/HEIF based on signature
            } elseif (!isset($allowedMimeTypes[$extension]) || !in_array($mimeType, $allowedMimeTypes[$extension])) {
                return ['success' => false, 'error' => 'Invalid HEIC/HEIF file format. File signature check failed.'];
            }
        }
    } else {
        // Regular file type validation for non-HEIC/HEIF files
        if (!isset($allowedMimeTypes[$extension]) || !in_array($mimeType, $allowedMimeTypes[$extension])) {
            $expectedTypes = isset($allowedMimeTypes[$extension]) ? implode(' or ', $allowedMimeTypes[$extension]) : 'N/A';
            return ['success' => false, 'error' => 'Invalid file type. Detected MIME type: ' . $mimeType . '. Expected MIME type for ' . $extension . ': ' . $expectedTypes];
        }
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . $file['name'];
    $localPath = $uploadDir . $fileName;
    // Compute web path (relative to project root, e.g. uploads/filename.jpg)
    $webPath = 'uploads/' . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $localPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }

    return [
        'success' => true,
        'file_name' => $fileName,
        'file_path' => $webPath, // Use web path for DB and display
        'file_size' => $file['size'],
        'local_path' => $localPath // For backend use if needed
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