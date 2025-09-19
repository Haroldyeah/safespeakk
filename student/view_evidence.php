<?php
$pageTitle = 'View Evidence';
require_once '../config/config.php';
requireRole('student');

// Get report ID
$reportId = (int)($_GET['id'] ?? 0);
if (!$reportId) {
    setFlashMessage('error', 'Invalid report ID');
    redirect('my_reports.php');
}

// Verify the report belongs to the current user
$report = $db->fetchOne(
    "SELECT r.* FROM reports r WHERE r.id = ? AND r.student_id = ?", 
    [$reportId, $_SESSION['user_id']]
);

if (!$report) {
    setFlashMessage('error', 'Report not found or you do not have permission to view it');
    redirect('my_reports.php');
}

// Get evidence files
$evidence = $db->fetchAll(
    "SELECT * FROM report_evidence WHERE report_id = ? ORDER BY id ASC", 
    [$reportId]
);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-file-image text-primary me-2"></i>
            Evidence Files
        </h1>
        <p class="text-muted mb-0">
            Viewing evidence for report: <strong><?php echo htmlspecialchars($report['title']); ?></strong>
        </p>
    </div>
    <div class="col-auto">
        <a href="my_reports.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Reports
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Evidence Files (<?php echo count($evidence); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($evidence)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-image fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No evidence files found</h6>
                <p class="text-muted">This report does not have any evidence files attached.</p>
                <a href="my_reports.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Reports
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($evidence as $index => $file): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0 text-truncate" title="<?php echo htmlspecialchars($file['file_name']); ?>">
                                    <i class="fas fa-file me-1"></i>
                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <?php
                                $fileExt = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                                $videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'm4v', '3gp', 'avi', 'wmv', 'flv', 'mkv']; // Added mkv
                                $pdfTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                                
                                // Ensure the file path is correctly formatted
                                $webPath = $file['file_path'];
                                if (strpos($webPath, 'uploads/') !== 0) {
                                    $webPath = 'uploads/' . basename($webPath);
                                }

                                // Map file extensions to common MIME types for video
                                $videoMimeTypes = [
                                    'mp4' => 'video/mp4',
                                    'webm' => 'video/webm',
                                    'ogg' => 'video/ogg',
                                    'mov' => 'video/quicktime',
                                    'm4v' => 'video/x-m4v',
                                    '3gp' => 'video/3gpp',
                                    'avi' => 'video/x-msvideo',
                                    'wmv' => 'video/x-ms-wmv',
                                    'flv' => 'video/x-flv',
                                    'mkv' => 'video/x-matroska',
                                ];
                                $videoMimeType = $videoMimeTypes[$fileExt] ?? 'video/' . $fileExt; // Fallback to generic
                                ?>
                                
                                <?php if (in_array($fileExt, $imageTypes)): ?>
                                    <a href="javascript:void(0);" onclick="showGlobalImage('../<?php echo htmlspecialchars($webPath); ?>', '<?php echo htmlspecialchars($file['file_name']); ?>')">
                                        <img src="../<?php echo htmlspecialchars($webPath); ?>" alt="Evidence Photo" 
                                             class="img-fluid mb-3" style="max-height: 200px; border-radius: 8px; cursor: pointer;">
                                    </a>
                                <?php elseif (in_array($fileExt, $videoTypes)): ?>
                                    <video controls class="img-fluid mb-3" style="max-height: 200px; border-radius: 8px;">
                                        <source src="../<?php echo htmlspecialchars($webPath); ?>" type="<?php echo htmlspecialchars($videoMimeType); ?>">
                                        Your browser does not support the video tag or the video format is not supported.
                                    </video>
                                    <?php if ($fileExt === 'mov'): ?>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Note: .mov files may not play in all browsers. Please download to view.</small>
                                    <?php endif; ?>
                                <?php elseif (in_array($fileExt, $pdfTypes)): ?>
                                    <div class="d-flex justify-content-center align-items-center mb-3" style="height: 200px;">
                                        <i class="fas fa-file-pdf fa-5x text-danger"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-center align-items-center mb-3" style="height: 200px;">
                                        <i class="fas fa-file fa-5x text-primary"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="text-muted mb-3">
                                    <small>Size: <?php echo formatFileSize($file['file_size']); ?></small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <?php if (in_array($fileExt, $imageTypes)): ?>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="showGlobalImage('../<?php echo htmlspecialchars($webPath); ?>', '<?php echo htmlspecialchars($file['file_name']); ?>')">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                    <?php elseif (in_array($fileExt, $videoTypes) || in_array($fileExt, $pdfTypes)): ?>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="toggleEvidence('evidencePreview_<?php echo $file['id']; ?>')">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                    <?php endif; ?>
                                    <a href="download_report.php?id=<?php echo $report['id']; ?>&evidence_id=<?php echo $file['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                </div>
                                
                                <?php if (in_array($fileExt, $videoTypes) || in_array($fileExt, $pdfTypes)): ?>
                                <div id="evidencePreview_<?php echo $file['id']; ?>" style="display:none;" class="mt-2">
                                    <?php if (in_array($fileExt, $videoTypes)): ?>
                                    <video controls style="max-width:100%;max-height:400px;border-radius:12px;margin-bottom:10px;">
                                        <source src="../<?php echo htmlspecialchars($webPath); ?>" type="<?php echo htmlspecialchars($videoMimeType); ?>">
                                        Your browser does not support the video tag or the video format is not supported.
                                    </video>
                                    <?php if ($fileExt === 'mov'): ?>
                                        <small class="text-muted d-block mt-1">Note: .mov files may not play in all browsers. Please use the download button to view the file.</small>
                                    <?php endif; ?>
                                    <?php elseif (in_array($fileExt, $pdfTypes)): ?>
                                    <iframe src="../<?php echo htmlspecialchars($webPath); ?>" 
                                            style="width:100%;height:400px;border-radius:12px;border:1px solid #007bff;margin-bottom:10px;" 
                                            frameborder="0"></iframe>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
function toggleEvidence(elementId) {
    var preview = document.getElementById(elementId);
    if (preview.style.display === 'none') {
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}
</script>