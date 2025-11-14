<?php
// Authentication functions
function isLoggedIn() {
    return (isset($_SESSION['user_id']) || isset($_SESSION['school_id'])) && isset($_SESSION['mfa_verified']) && $_SESSION['mfa_verified'] === true;
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
    try {
        // Create a DateTime object, assuming the input string is from the database in UTC+1 timezone.
        $dateObj = new DateTime($date, new DateTimeZone('Etc/GMT-1')); // Etc/GMT-1 is UTC+1

        // Convert it to the correct timezone for display.
        $dateObj->setTimezone(new DateTimeZone('Asia/Manila'));

        // Return the formatted date.
        return $dateObj->format('M d, Y g:i A');
    } catch (Exception $e) {
        // Fallback to the old method in case of an error.
        return date('M d, Y g:i A', strtotime($date));
    }
}

function getStatusBadgeClass($status) {
    $classes = [
        'submitted' => 'bg-primary',
        'under_investigation' => 'bg-info',
        'referred_to_mswd' => 'bg-dark',
        'verified' => 'bg-success',
        'rejected' => 'bg-danger'
    ];
    return isset($classes[$status]) ? $classes[$status] : 'bg-secondary';
}

/**
 * Analyze a report and return severity and suggested actions.
 * Returns array: ['severity' => 'low|medium|high|critical', 'suggested_actions' => string]
 * This is a heuristic analyzer intended to provide guidance. It's deterministic and runs locally.
 */
function analyze_report(array $report, $evidenceInfo = 0) {
    // $evidenceInfo can be:
    // - int: a simple count of evidence files
    // - array: ['count' => int, 'samples' => [ ['file_name'=>..., 'file_path'=>..., 'file_size'=>...], ... ]]
    $title = strtolower($report['title'] ?? '');
    $description = strtolower($report['description'] ?? '');
    $studentName = trim(($report['first_name'] ?? '') . ' ' . ($report['last_name'] ?? '')) ?: null;
    $schoolName = $report['report_school_name'] ?? $report['school_name'] ?? ($report['school_id'] ?? null);

    $score = 0;

    // Title-based weighting
    if (strpos($title, 'physical') !== false) $score += 3;
    if (strpos($title, 'sexual') !== false) $score += 4;
    if (strpos($title, 'cyber') !== false) $score += 2;
    if (strpos($title, 'prejudicial') !== false) $score += 2;
    if (strpos($title, 'verbal') !== false) $score += 1;

    // Keyword lists
    $criticalKeywords = ['weapon','gun','knife','stab','rape','sexual assault','serious injury','blood','suicide','self-harm'];
    foreach ($criticalKeywords as $kw) {
        if (strpos($description, $kw) !== false) $score += 5;
    }

    $highKeywords = ['threat','assault','attack','hurt','beat','beaten','punch','kick','choke','stalk','harass','threaten'];
    foreach ($highKeywords as $kw) {
        if (strpos($description, $kw) !== false) $score += 3;
    }

    // Description detail
    $wordCount = str_word_count($description);
    if ($wordCount > 100) $score += 2;
    elseif ($wordCount > 50) $score += 1;

    // Evidence analysis: accept either int or a richer array
    $evidenceCount = 0;
    $evidenceAnalysis = [
        'images' => 0,
        'videos' => 0,
        'documents' => 0,
        'possible_fraud' => false,
        'samples' => []
    ];

    if (is_array($evidenceInfo)) {
        $evidenceCount = $evidenceInfo['count'] ?? 0;
        $samples = $evidenceInfo['samples'] ?? [];
        foreach ($samples as $s) {
            $name = strtolower($s['file_name'] ?? '');
            $path = strtolower($s['file_path'] ?? '');
            $size = (int)($s['file_size'] ?? 0);
            $ext = strtolower(pathinfo($s['file_name'] ?? $s['file_path'] ?? '', PATHINFO_EXTENSION));

            $evidenceAnalysis['samples'][] = ['file_name' => $name, 'file_path' => $path, 'file_size' => $size, 'ext' => $ext];

            // Increase score based on type
            if (in_array($ext, ['mp4','mov','webm','mkv','avi','wmv','3gp'])) {
                $score += 3; // video is strong evidence
                $evidenceAnalysis['videos']++;
            } elseif (in_array($ext, ['jpg','jpeg','png','gif','heic','heif'])) {
                $score += 2; // image evidence
                $evidenceAnalysis['images']++;
            } elseif (in_array($ext, ['pdf','doc','docx','txt'])) {
                $score += 1; // document evidence
                $evidenceAnalysis['documents']++;
            }

            // Heuristic checks for possibly unrelated or fraudulent evidence
            $suspiciousWords = ['meme','joke','not related','not related.jpg','fake','scam','whatsapp','facebook','tiktok','instagram'];
            foreach ($suspiciousWords as $w) {
                if (strpos($name, $w) !== false || strpos($path, $w) !== false) {
                    // mark possible fraud and slightly reduce score
                    $evidenceAnalysis['possible_fraud'] = true;
                    $score = max(0, $score - 2);
                }
            }

            // Tiny files may indicate placeholders or irrelevant files
            if ($size > 0 && $size < 1024) {
                $evidenceAnalysis['possible_fraud'] = true;
                $score = max(0, $score - 1);
            }
        }
        // Cap added evidence score influence
        $score += min(3, $evidenceCount);
    } else {
        // simple count
        $evidenceCount = (int)$evidenceInfo;
        if ($evidenceCount > 0) $score += min(3, $evidenceCount);
    }

    // Recent incident increases urgency
    if (!empty($report['date_of_incident'])) {
        try {
            $incidentTs = strtotime($report['date_of_incident']);
            if ($incidentTs !== false) {
                $daysAgo = (time() - $incidentTs) / 86400;
                if ($daysAgo <= 7) $score += 2;
                elseif ($daysAgo <= 30) $score += 1;
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    // Map score to severity (tuned)
    if ($score >= 11) {
        $severity = 'critical';
    } elseif ($score >= 8) {
        $severity = 'high';
    } elseif ($score >= 4) {
        $severity = 'medium';
    } else {
        $severity = 'low';
    }

    // Build detailed suggested actions (kept mostly the same)
    $actions = [];
    $actions[] = 'Acknowledge receipt of this report to the reporter and ensure they are safe.';
    $actions[] = 'Preserve and secure all evidence attached to this report.';

    if ($severity === 'critical') {
        $actions[] = 'Immediate safety action: separate the involved students and notify school leadership and security right away.';
        $actions[] = 'Contact local authorities if there is an imminent danger or if a weapon/serious injury is alleged.';
        $actions[] = 'Arrange urgent medical and counseling support for affected students.';
        $actions[] = 'Initiate a formal investigation and record all witness statements within 48 hours.';
    } elseif ($severity === 'high') {
        $actions[] = 'Initiate an expedited investigation and schedule a meeting with parents/guardians.';
        $actions[] = 'Provide counseling referrals and implement a short-term safety/monitoring plan.';
        $actions[] = 'Secure any available evidence and interview witnesses as soon as possible.';
    } elseif ($severity === 'medium') {
        $actions[] = 'Arrange counseling sessions and mediation where appropriate.';
        $actions[] = 'Monitor interactions between the involved students and document follow-ups.';
        $actions[] = 'Consider targeted restorative practices or disciplinary measures based on findings.';
    } else {
        $actions[] = 'Provide supportive check-ins and education on appropriate behavior.';
        $actions[] = 'Document the report and offer mediation or counseling if both parties agree.';
    }

    if (strpos($title, 'cyber') !== false) {
        array_unshift($actions, 'Document and archive all digital evidence (screenshots, URLs, timestamps).');
        $actions[] = 'Consider contacting platform administrators and advise on adjusting privacy settings.';
    }

    if (strpos($title, 'sexual') !== false) {
        array_unshift($actions, 'Prioritize the privacy and safety of the reporter; follow mandated reporting procedures.');
        $actions[] = 'Engage specialized counselors and consider immediate protective measures.';
    }

    $contextLines = [];
    if ($studentName) $contextLines[] = "Reported student: {$studentName}.";
    if ($schoolName) $contextLines[] = "Submitted to: {$schoolName}.";
    if (!empty($report['date_of_incident'])) $contextLines[] = 'Incident date: ' . date('M d, Y', strtotime($report['date_of_incident']));

    // Build actions-only suggested text (no context or severity header) to avoid UI redundancy
    $actionsText = '';
    $i = 1;
    foreach ($actions as $act) {
        $actionsText .= "{$i}. {$act}\n";
        $i++;
    }

    $result = [
        'severity' => $severity,
        // suggested_actions now contains only the numbered actions (no context/severity header)
        'suggested_actions' => trim($actionsText),
        // separate context for display if needed
        'context' => implode(' ', $contextLines),
        'score' => $score,
        'evidence_analysis' => $evidenceAnalysis,
        'evidence_count' => $evidenceCount
    ];

    return $result;
}

function getSeverityBadgeClass($severity) {
    $map = [
        'low' => 'bg-secondary text-white',
        'medium' => 'bg-warning text-dark',
        'high' => 'bg-danger text-white',
        'critical' => 'bg-dark text-white'
    ];
    return $map[$severity] ?? 'bg-secondary';
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