<?php
$pageTitle = 'Manage Report';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];
$reportId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input['action'] === 'update_status') {
        $newStatus = $input['status'];
        $reportId = (int)$input['report_id'];
        $comments = $input['comments'] ?? '';
        $grade = $input['grade'] ?? null;
        
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
        $validStatuses = ['submitted', 'under_review', 'approved', 'rejected', 'revision_required'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        // Update report
        $updateData = [
            'status' => $newStatus,
            'reviewed_by_school' => true,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($comments) {
            $updateData['school_comments'] = $comments;
        }
        
        if ($grade && in_array($newStatus, ['approved'])) {
            $updateData['grade'] = $grade;
        }
        
        $success = $db->update(
            'reports',
            $updateData,
            'id = ?',
            [$reportId]
        );
        
        if ($success) {
            logActivity($db, $schoolId, 'school', 'update_report_status', "Updated report #$reportId status to $newStatus");
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }
}

// Get report details
$report = $db->fetchOne(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s.name as school_name
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s ON r.school_id = s.id
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
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Title:</div>
                    <div class="col-sm-9"><?php echo htmlspecialchars($report['title']); ?></div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Student:</div>
                    <div class="col-sm-9">
                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                        <br>
                        <small class="text-muted">
                            ID: <?php echo htmlspecialchars($report['student_id']); ?> | 
                            Email: <?php echo htmlspecialchars($report['email']); ?>
                        </small>
                    </div>
                </div>
                
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
                
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">File:</div>
                    <div class="col-sm-9">
                        <?php echo htmlspecialchars($report['file_name']); ?>
                        <small class="text-muted ms-2">(<?php echo formatFileSize($report['file_size']); ?>)</small>
                        
                        <?php if ($report['file_path'] && file_exists($report['file_path'])): ?>
                            <br>
                            <a href="../student/download_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="fas fa-download me-1"></i>Download File
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($report['grade']): ?>
                <div class="row mb-3">
                    <div class="col-sm-3 fw-bold">Grade:</div>
                    <div class="col-sm-9">
                        <span class="badge bg-success fs-6"><?php echo htmlspecialchars($report['grade']); ?></span>
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
                            <option value="revision_required" <?php echo $report['status'] === 'revision_required' ? 'selected' : ''; ?>>Revision Required</option>
                            <option value="rejected" <?php echo $report['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="gradeGroup" style="display: none;">
                        <label for="grade" class="form-label">Grade (Optional)</label>
                        <input type="text" class="form-control" id="grade" name="grade" 
                               value="<?php echo htmlspecialchars($report['grade'] ?? ''); ?>"
                               placeholder="e.g., A, B+, 85, Pass">
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
                        <button class="btn btn-warning btn-sm" onclick="quickUpdateStatus('revision_required')">
                            <i class="fas fa-edit me-1"></i>Request Revision
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="quickUpdateStatus('rejected')">
                            <i class="fas fa-times me-1"></i>Reject
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($report['file_path'] && file_exists($report['file_path'])): ?>
                        <a href="../student/download_report.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Download File
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
                    <strong>School:</strong> <?php echo htmlspecialchars($report['school_name']); ?><br>
                    <strong>File Size:</strong> <?php echo formatFileSize($report['file_size']); ?><br>
                    <strong>Submitted:</strong> <?php echo formatDate($report['submission_date']); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const gradeGroup = document.getElementById('gradeGroup');
    
    function toggleGradeField() {
        if (statusSelect.value === 'approved') {
            gradeGroup.style.display = 'block';
        } else {
            gradeGroup.style.display = 'none';
        }
    }
    
    statusSelect.addEventListener('change', toggleGradeField);
    toggleGradeField(); // Initialize on load
    
    document.getElementById('statusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_status',
            report_id: parseInt(document.getElementById('reportId').value),
            status: document.getElementById('status').value,
            comments: document.getElementById('comments').value,
            grade: document.getElementById('grade').value
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
