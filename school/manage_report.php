

<?php
$pageTitle = 'Manage Report';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];
$reportId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input['action'] === 'update_status') {
        $newStatus = $input['status'];
        $reportId = (int)$input['report_id'];
        $comments = $input['comments'] ?? '';

        // Verify report belongs to this school
        $report = $db->fetchOne(
            "SELECT id FROM reports WHERE id = ? AND school_id = ?",
            [$reportId, $schoolId]
        );
    
        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found or access denied']);
            exit;
        }
    
        // Valid status transitions
        $validStatuses = ['submitted', 'under_review', 'approved', 'rejected'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
    
        // Prepare update data
        $updateData = [
            'status' => $newStatus,
            'reviewed_by_school' => 1,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($comments) {
            $updateData['school_comments'] = $comments;
        }

        $success = $db->update(
            'reports',
            $updateData,
            'id = :id',
            ['id' => $reportId]
        );

        if ($success instanceof PDOStatement) {
            // Send notif email to student
            $reportDetails = $db->fetchOne(
                "SELECT r.title, r.description, u.first_name, u.last_name, u.email FROM reports r JOIN users u ON r.student_id = u.id WHERE r.id = ?",
                [$reportId]
            );
            $mailError = '';
            if ($reportDetails && $reportDetails['email']) {
                $schoolSmtp = $db->fetchOne("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM schools WHERE id = ?", [$schoolId]);
                require_once __DIR__ . '/../templates/email/load_template.php';
                $statusLabel = ucfirst(str_replace('_', ' ', $newStatus));
                $reportUrl = rtrim(BASE_URL, '/') . '/student/view_report.php?id=' . $reportId;
                $body = load_email_template('report_status_updated.php', [
                    'studentName' => $reportDetails['first_name'] . ' ' . $reportDetails['last_name'],
                    'statusLabel' => $statusLabel,
                    'comments' => $comments,
                    'title' => $reportDetails['title'],
                    'reportUrl' => $reportUrl,
                    'appName' => APP_NAME,
                    'baseUrl' => BASE_URL
                ]);
                $subject = "Report Status Updated: {$statusLabel}";
                try {
                    sendMail(
                        $reportDetails['email'],
                        $subject,
                        $body,
                        $schoolSmtp['from_email'],
                        $schoolSmtp['from_name']
                    );
                } catch (Exception $e) {
                    $mailError = $e->getMessage();
                    error_log('Mail Error: ' . $mailError);
                }
            }
            logActivity($db, $schoolId, 'school', 'update_report_status', "Updated report #$reportId status to $newStatus");
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'mailError' => $mailError
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            exit;
        }
    }
    
}

// Get report details

$report = $db->fetchOne(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, u.id_photo_path, s_report.name as report_school_name, s_user.name as registered_school_name, u.school_id as registered_school_id
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s_report ON r.school_id = s_report.id
     LEFT JOIN schools s_user ON u.school_id = s_user.id
     WHERE r.id = ? AND r.school_id = ?",
    [$reportId, $schoolId]
);

if (!$report) {
    redirect('view_reports.php', 'Report not found or access denied.', 'error');
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="view_reports.php">Reports</a></li>
                <li class="breadcrumb-item active">Manage Report</li>
            </ol>
        </nav>
        <h1 class="h3 mb-3">
            <i class="fas fa-file-alt text-primary me-2"></i>
            Manage Report
        </h1>
    </div>
    <div class="col-auto">
        <a href="view_reports.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Reports
        </a>
    </div>
</div>

<div class="row">
    <!-- Report Details -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Report Details
                </h5>
            </div>
            <div class="card-body">
                // Student ID Photo Above Title (Clickable for Zoom)
                <?php
                $photoPath = $report['id_photo_path'] ?? '';
                $absolutePhotoPath = '';
                if ($photoPath) {
                    $absolutePhotoPath = __DIR__ . '/../' . $photoPath;
                    echo '<div class="text-center mb-3">';
                    echo '<a href="javascript:void(0);" onclick="showGlobalImage(\'../' . htmlspecialchars($photoPath) . '\', \'Student ID Photo\')">';
                    echo '<img src="../' . htmlspecialchars($photoPath) . '" alt="ID Photo" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #007bff;box-shadow:0 2px 16px rgba(0,0,0,0.12);margin-bottom:10px;cursor:pointer;" />';
                    echo '</a>';
                    if (!file_exists($absolutePhotoPath)) {
                        echo '<div class="text-danger small">ID photo file not found: ' . htmlspecialchars($photoPath) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Type of Bullying:</div>
                    <div class="col-sm-9"><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($report['title']); ?></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Student:</div>
                    <div class="col-sm-9 d-flex align-items-center gap-3">
                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                        <br>
                        <small class="text-muted">
                            ID: <?php echo htmlspecialchars($report['student_id']); ?> | 
                            Email: <?php echo htmlspecialchars($report['email']); ?>
                        </small>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Registered School:</div>
                    <div class="col-sm-9">
                        <?php echo htmlspecialchars($report['registered_school_name'] ?? 'N/A'); ?>
                        <?php if ($report['school_id'] !== $report['registered_school_id']): ?>
                            <span class="badge bg-warning text-dark ms-2">Student is registered at a different school</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($report['date_of_incident']): ?>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Date of Incident:</div>
                    <div class="col-sm-9"><?php echo formatDate($report['date_of_incident']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Description:</div>
                    <div class="col-sm-9">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Status:</div>
                    <div class="col-sm-9">
                        <span class="status-badge status-<?php echo $report['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Submitted:</div>
                    <div class="col-sm-9"><?php echo formatDate($report['submission_date']); ?></div>
                </div>
                
                <?php if ($report['reviewed_at']): ?>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Reviewed:</div>
                    <div class="col-sm-9"><?php echo formatDate($report['reviewed_at']); ?></div>
                </div>
                <?php endif; ?>
                

                <?php 
                // Get evidence files from report_evidence table
                $evidence = $db->fetchAll("SELECT * FROM report_evidence WHERE report_id = ?", [$reportId]);
                if (!empty($evidence)): ?>
                <div id="evidence" class="row mb-3">
                    <div class="col-sm-3 fw-bold">Evidence Files:</div>
                    <div class="col-sm-9">
                        <?php foreach ($evidence as $index => $file): ?>
                        <div class="mb-3 p-3 border rounded">
                            <h6 class="mb-2">File #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($file['file_name']); ?></h6>
                            <small class="text-muted d-block mb-2">Size: <?php echo formatFileSize($file['file_size']); ?></small>
                            <?php
                            $fileExt = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                            $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                            $videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'm4v', '3gp', 'avi', 'wmv', 'flv'];
                            $pdfTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                            
                            // Ensure the file path is correctly formatted
                            $webPath = $file['file_path'];
                            if (strpos($webPath, 'uploads/') !== 0) {
                                $webPath = 'uploads/' . basename($webPath);
                            }
                            ?>
                            
                            <div class="btn-group mb-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="toggleEvidence('evidencePreview_<?php echo $index; ?>')">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                <a href="../student/download_report.php?id=<?php echo $report['id']; ?>&evidence_id=<?php echo $file['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </div>
                            
                            <div id="evidencePreview_<?php echo $index; ?>" style="display:none;" class="mt-2">
                                <?php if (in_array($fileExt, $imageTypes)): ?>
                                    <a href="javascript:void(0);" onclick="showGlobalImage('../<?php echo htmlspecialchars($webPath); ?>', 'Evidence Photo')">
                                        <img src="../<?php echo htmlspecialchars($webPath); ?>" alt="Evidence Photo" 
                                             style="max-width:100%;max-height:400px;border-radius:12px;border:1px solid #007bff;box-shadow:0 2px 16px rgba(0,0,0,0.12);margin-bottom:10px;cursor:pointer;" />
                                    </a>
                                <?php elseif (in_array($fileExt, $videoTypes)): ?>
                                    <video controls style="max-width:100%;max-height:400px;border-radius:12px;margin-bottom:10px;">
                                        <source src="../<?php echo htmlspecialchars($webPath); ?>" type="<?php echo ($fileExt === 'mov') ? 'video/quicktime' : 'video/' . htmlspecialchars($fileExt); ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                    <?php if ($fileExt === 'mov'): ?>
                                        <small class="text-muted d-block mt-1">Note: .mov files may not play in all browsers. Please <a href="../student/download_report.php?id=<?php echo $report['id']; ?>&evidence_id=<?php echo $file['id']; ?>">download the file</a> to view it.</small>
                                    <?php endif; ?>
                                <?php elseif (in_array($fileExt, $pdfTypes)): ?>
                                    <iframe src="../<?php echo htmlspecialchars($webPath); ?>" 
                                            style="width:100%;height:400px;border-radius:12px;border:1px solid #007bff;margin-bottom:10px;" 
                                            frameborder="0"></iframe>
                                <?php else: ?>
                                    <div class="alert alert-info">File type not supported for preview.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
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
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($report['school_comments']): ?>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">School Comments:</div>
                    <div class="col-sm-9">
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($report['school_comments'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($report['admin_comments']): ?>
                <div class="row mb-0">
                    <div class="col-sm-3 fw-bold">Admin Comments:</div>
                    <div class="col-sm-9">
                        <div class="alert alert-warning">
                            <?php echo nl2br(htmlspecialchars($report['admin_comments'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actions Panel -->
    <div class="col-lg-4">
        <!-- Status Management -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>Report Management
                </h6>
            </div>
            <div class="card-body">
                <form id="statusForm">
                    <input type="hidden" id="reportId" value="<?php echo $report['id']; ?>">
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Update Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="submitted" <?php echo $report['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                            <option value="under_review" <?php echo $report['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $report['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $report['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea class="form-control" id="comments" name="comments" rows="4" 
                                  placeholder="Add your review comments here..."><?php echo htmlspecialchars($report['school_comments'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($report['status'] === 'submitted'): ?>
                        <button class="btn btn-success btn-sm" onclick="quickUpdateStatus('under_review')">
                            <i class="fas fa-play me-1"></i>Start Review
                        </button>
                    <?php elseif ($report['status'] === 'under_review'): ?>
                        <button class="btn btn-success btn-sm" onclick="quickUpdateStatus('approved')">
                            <i class="fas fa-check me-1"></i>Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="quickUpdateStatus('rejected')">
                            <i class="fas fa-times me-1"></i>Reject
                        </button>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if there are any evidence files for this report
                    $hasEvidence = $db->fetchOne("SELECT COUNT(*) FROM report_evidence WHERE report_id = ?", [$reportId]);
                    if ($hasEvidence > 0): ?>
                        <a href="../student/download_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Download All Files
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Report Info -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info me-2"></i>Report Information
                </h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>Report ID:</strong> #<?php echo $report['id']; ?><br>
                    <strong>Submitted to:</strong> <?php echo htmlspecialchars($report['report_school_name']); ?><br>
                    <strong>File Size:</strong> <?php echo !empty($report['file_size']) ? formatFileSize($report['file_size']) : 'N/A'; ?><br>
                    <strong>Submitted:</strong> <?php echo formatDate($report['submission_date']); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('statusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_status',
            report_id: parseInt(document.getElementById('reportId').value),
            status: document.getElementById('status').value,
            comments: document.getElementById('comments').value
        };
        
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
        submitBtn.disabled = true;
        
        fetch('manage_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Report status updated successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message || 'Failed to update status', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while updating status', 'danger');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

function quickUpdateStatus(newStatus) {
    const confirmMessage = `Are you sure you want to ${newStatus.replace('_', ' ')} this report?`;
    
    if (confirm(confirmMessage)) {
        const formData = {
            action: 'update_status',
            report_id: parseInt(document.getElementById('reportId').value),
            status: newStatus
        };
        
        fetch('manage_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Report status updated successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message || 'Failed to update status', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while updating status', 'danger');
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
