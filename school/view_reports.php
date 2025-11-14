<?php
$pageTitle = 'View Reports';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];
$schoolName = $_SESSION['school_name'];

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$severityFilter = $_GET['severity'] ?? '';
$sortBy = $_GET['sort'] ?? 'submission_date';
$sortOrder = $_GET['order'] ?? 'DESC';

// Valid sort columns
$validSortColumns = ['submission_date', 'title', 'status', 'first_name', 'student_id', 'date_of_incident', 'severity'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'submission_date';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Build query conditions
$conditions = ["r.school_id = ?", "r.deleted_at IS NULL"];
$params = [$schoolId];

if ($search) {
    $conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.student_id LIKE ? OR r.bully_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($statusFilter) {
    $conditions[] = "r.status = ?";
    $params[] = $statusFilter;
}

// Apply severity filter only if column exists
if (!empty($severityFilter)) {
    try {
        $col = $db->fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'severity'", [DB_NAME, 'reports']);
        if ($col) {
            $conditions[] = "r.severity = ?";
            $params[] = $severityFilter;
        }
    } catch (Exception $e) {
        // ignore
    }
}

$whereClause = implode(' AND ', $conditions);

// Get total count
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports r JOIN users u ON r.student_id = u.id WHERE $whereClause",
    $params
)['count'];

// Get reports
$reports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s_user.name as registered_school_name, u.school_id as registered_school_id
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     LEFT JOIN schools s_user ON u.school_id = s_user.id 
     WHERE $whereClause 
     ORDER BY $sortBy $sortOrder 
     LIMIT $perPage OFFSET $offset",
    $params
);

// Handle soft delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'soft_delete') {
        $reportIds = $input['report_ids'] ?? [];
        header('Content-Type: application/json');
        
        if (empty($reportIds)) {
            echo json_encode(['success' => false, 'message' => 'No reports selected']);
            exit;
        }
        
        $deletedCount = 0;
        foreach ($reportIds as $reportId) {
            $reportId = (int)$reportId;
            // Check if the report belongs to the school before deleting
            $reportSchool = $db->fetchOne("SELECT school_id FROM reports WHERE id = ?", [$reportId]);
            
            if ($reportSchool && $reportSchool['school_id'] == $schoolId) {
                $updateData = [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    // We can set deleted_by_admin with a negative school_id to distinguish
                    'deleted_by_admin' => -1 * (int)$_SESSION['school_id'] 
                ];
                
                $success = $db->update('reports', $updateData, 'id = :id', ['id' => $reportId]);
                if ($success instanceof PDOStatement) {
                    $deletedCount++;
                    logActivity($db, $_SESSION['school_id'], 'school', 'soft_delete_report', "Soft deleted report #$reportId");
                }
            }
        }
        
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "$deletedCount report(s) deleted successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete reports or no reports found for this school']);
        }
        exit;
    }
}

// Calculate pagination
$totalPages = ceil($totalReports / $perPage);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-file-alt text-primary me-2"></i>
            Report Management
        </h1>
        <p class="text-muted mb-0">
            Manage capstone reports for <?php echo htmlspecialchars($schoolName); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalReports; ?> total</span>
        </p>
    </div>
    <div class="col-auto">
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search reports, students...">
                </div>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="under_review" <?php echo $statusFilter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="severity">
                    <option value="">All Severities</option>
                    <option value="low" <?php echo $severityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $severityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $severityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $severityFilter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="sort">
                    <option value="submission_date" <?php echo $sortBy === 'submission_date' ? 'selected' : ''; ?>>Sort by Submission Date</option>
                    <option value="date_of_incident" <?php echo $sortBy === 'date_of_incident' ? 'selected' : ''; ?>>Sort by Incident Date</option>
                    <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Sort by Type</option>
                    <option value="first_name" <?php echo $sortBy === 'first_name' ? 'selected' : ''; ?>>Sort by Student</option>
                    <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Sort by Status</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="view_reports.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reports List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">
                Report List
                <?php if ($search || $statusFilter): ?>
                    <small class="text-muted">
                        (<?php echo $totalReports; ?> results found)
                    </small>
                <?php endif; ?>
            </h5>
            <?php if (!empty($reports)): ?>
                <button class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                    <i class="fas fa-check-square me-1"></i>Select All
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reports)): ?>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-danger" id="deleteSelectedBtn" disabled>
                    <i class="fas fa-trash-alt me-1"></i>Delete Selected
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="exportTable('reportsTable', 'school_reports')">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No reports found</h6>
                <?php if ($search || $statusFilter): ?>
                    <p class="text-muted mb-3">Try adjusting your search criteria</p>
                    <a href="view_reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0">Reports from students will appear here once submitted</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="reportsTable">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                                </div>
                            </th>
                            <th>
                                <a href="?sort=title&order=<?php echo $sortBy === 'title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Type of Bullying 
                                    <?php if ($sortBy === 'title'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Involved</th>
                            <th>
                                <a href="?sort=first_name&order=<?php echo $sortBy === 'first_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Student 
                                    <?php if ($sortBy === 'first_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Registered School</th>
                            <th>
                                <a href="?sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Status 
                                    <?php if ($sortBy === 'status'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=severity&order=<?php echo $sortBy === 'severity' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    <i class="fas fa-exclamation-circle me-1"></i>Severity 
                                    <?php if ($sortBy === 'severity'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=date_of_incident&order=<?php echo $sortBy === 'date_of_incident' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Date of Incident
                                    <?php if ($sortBy === 'date_of_incident'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=submission_date&order=<?php echo $sortBy === 'submission_date' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Submitted 
                                    <?php if ($sortBy === 'submission_date'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>File Size</th>
                            <th>Evidence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr class="searchable-row">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input report-checkbox" type="checkbox" value="<?php echo $report['id']; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                        <?php if ($report['description']): ?>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($report['description'], 0, 80)) . (strlen($report['description']) > 80 ? '...' : ''); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($report['bully_name'] ?? '—'); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($report['student_id']); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($report['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($report['registered_school_name'] ?? 'N/A'); ?>
                                    <?php if ($report['school_id'] !== $report['registered_school_id']): ?>
                                        <span class="badge bg-warning text-dark ms-2">Different School</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $severity = strtolower($report['severity'] ?? 'low');
                                    ?>
                                    <span class="severity-badge severity-<?php echo $severity; ?>" style="font-size: 0.9em; padding: 0.5em 0.75em;">
                                        <?php 
                                        // Add icon based on severity
                                        switch($severity) {
                                            case 'critical':
                                                echo '<i class="fas fa-skull-crossbones me-1"></i>';
                                                break;
                                            case 'high':
                                                echo '<i class="fas fa-exclamation-triangle me-1"></i>';
                                                break;
                                            case 'medium':
                                                echo '<i class="fas fa-exclamation me-1"></i>';
                                                break;
                                            case 'low':
                                            default:
                                                echo '<i class="fas fa-info-circle me-1"></i>';
                                        }
                                        echo ucfirst($severity);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo $report['date_of_incident'] ? formatDate($report['date_of_incident']) : 'N/A'; ?></small>
                                </td>
                                <td>
                                    <small><?php echo formatDate($report['submission_date']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo !empty($report['file_size']) ? formatFileSize($report['file_size']) : 'N/A'; ?></small>
                                </td>
                                <td>
                                    <?php
                                    // Check if there are evidence files for this report
                                    $evidenceCount = $db->fetchOne(
                                        "SELECT COUNT(*) as count FROM report_evidence WHERE report_id = ?", 
                                        [$report['id']]
                                    )['count'];
                                    
                                    if ($evidenceCount > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="window.location.href='manage_report.php?id=<?php echo $report['id']; ?>#evidence'">
                                            <i class="fas fa-image me-1"></i>View Evidence (<?php echo $evidenceCount; ?>)
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-ban me-1"></i>No evidence</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="manage_report.php?id=<?php echo $report['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-1"></i>Review
                                        </a>
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($report['status'] === 'submitted'): ?>
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'under_review')">
                                                            <i class="fas fa-eye text-info me-1"></i>Mark Under Review
                                                        </button>
                                                    </li>
                                                <?php elseif ($report['status'] === 'under_review'): ?>
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'approved')">
                                                            <i class="fas fa-check text-success me-1"></i>Approve
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'rejected')">
                                                            <i class="fas fa-times text-danger me-1"></i>Reject
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger" onclick="deleteReport(<?php echo $report['id']; ?>)">
                                                        <i class="fas fa-trash text-danger me-1"></i>Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Reports pagination">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function updateStatus(reportId, newStatus) {
    let confirmMessage = 'Are you sure you want to update the status of this report?';
    
    if (newStatus === 'approved') {
        confirmMessage = 'Are you sure you want to approve this report?';
    } else if (newStatus === 'rejected') {
        confirmMessage = 'Are you sure you want to reject this report? The student will be notified.';
    } else if (newStatus === 'revision_required') {
        confirmMessage = 'Are you sure you want to request revision for this report?';
    }
    
    if (confirm(confirmMessage)) {
        fetch('manage_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update_status',
                report_id: reportId,
                status: newStatus
            })
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

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        fetch('view_reports.php', { // Note: Changed to view_reports.php for school context
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'soft_delete',
                report_ids: [reportId]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Report deleted successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message || 'Failed to delete report', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while deleting report', 'danger');
        });
    }
}

// Handle checkbox selection
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const reportCheckboxes = document.querySelectorAll('.report-checkbox');

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            reportCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    // Select all button functionality
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            const allChecked = Array.from(reportCheckboxes).every(cb => cb.checked);
            reportCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            selectAllCheckbox.checked = !allChecked;
            updateDeleteButtonState();
        });
    }

    // Individual checkbox change
    reportCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateDeleteButtonState();
            
            // Update select all checkbox state
            const checkedCount = document.querySelectorAll('.report-checkbox:checked').length;
            if (checkedCount === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedCount === reportCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
        });
    });

    // Delete selected functionality
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selectedReports = Array.from(document.querySelectorAll('.report-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedReports.length === 0) {
                showAlert('Please select reports to delete', 'warning');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedReports.length} selected report(s)? This action cannot be undone.`)) {
                fetch('view_reports.php', { // Note: Changed to view_reports.php for school context
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'soft_delete',
                        report_ids: selectedReports
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Failed to delete reports', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting reports', 'danger');
                });
            }
        });
    }

    function updateDeleteButtonState() {
        const checkedCount = document.querySelectorAll('.report-checkbox:checked').length;
        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = checkedCount === 0;
            deleteSelectedBtn.innerHTML = checkedCount > 0 
                ? `<i class="fas fa-trash-alt me-1"></i>Delete Selected (${checkedCount})`
                : '<i class="fas fa-trash-alt me-1"></i>Delete Selected';
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
