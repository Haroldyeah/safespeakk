<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Only allow logged-in users (school admin, system admin, or student)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['school_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Access denied.');
}

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reportId) {
    http_response_code(400);
    exit('Invalid report ID.');
}

$db = new Database();

// Check if specific evidence file is requested
$evidenceId = isset($_GET['evidence_id']) ? (int)$_GET['evidence_id'] : 0;

if ($evidenceId) {
    // Get specific evidence file
    $evidence = $db->fetchOne(
        "SELECT file_path, file_name, file_size FROM report_evidence WHERE id = :evidence_id AND report_id = :report_id", 
        ['evidence_id' => $evidenceId, 'report_id' => $reportId]
    );
    
    if (!$evidence) {
        http_response_code(404);
        exit('Evidence file not found.');
    }
    
    $filePath = $evidence['file_path'];
    $fileName = $evidence['file_name'];
} else {
    // For backward compatibility - check if there's evidence in the report_evidence table
    $evidence = $db->fetchOne(
        "SELECT file_path, file_name, file_size FROM report_evidence WHERE report_id = :report_id LIMIT 1", 
        ['report_id' => $reportId]
    );
    
    if ($evidence) {
        $filePath = $evidence['file_path'];
        $fileName = $evidence['file_name'];
    } else {
        // Fall back to the old method (for legacy reports)
        $report = $db->fetchOne("SELECT file_path, file_name FROM reports WHERE id = :id", ['id' => $reportId]);
        
        if (!$report || empty($report['file_path'])) {
            http_response_code(404);
            exit('File not found.');
        }
        
        $filePath = $report['file_path'];
        $fileName = $report['file_name'];
    }
}

// Convert web path to local path
$localPath = '';
if (!empty($filePath)) {
    // Remove leading slash if present
    $relativePath = ltrim($filePath, '/');
    // Handle both formats: with or without 'uploads/' prefix
    if (strpos($relativePath, 'uploads/') !== 0) {
        $relativePath = 'uploads/' . basename($relativePath);
    }
    $localPath = realpath(__DIR__ . '/../' . $relativePath);
}

if (empty($localPath) || !file_exists($localPath)) {
    http_response_code(404);
    exit('File not found or inaccessible. Path: ' . $filePath);
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($localPath));

// Output the file
readfile($localPath);
exit;
