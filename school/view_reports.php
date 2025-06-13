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
$sortBy = $_GET['sort'] ?? 'submission_date';
$sortOrder = $_GET['order'] ?? 'DESC';

// Valid sort columns
$validSortColumns = ['submission_date', 'title', 'status', 'first_name', 'student_id'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'submission_date';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Build query conditions
$conditions = ["r.school_id = ?"];
$params = [$schoolId];

if ($search) {
    $conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.student_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($statusFilter) {
    $conditions[] = "r.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $conditions);

// Get total count
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports r JOIN users u ON r.student_id = u.id WHERE $whereClause",
    $params
)['count'];

// Get reports
$reports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     WHERE $whereClause 
     ORDER BY $sortBy $sortOrder 
     LIMIT $perPage OFFSET $offset",
    $params
);

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
                    <option value="revision_required" <?php echo $statusFilter === 'revision_required' ? 'selected' : ''; ?>>Revision Required</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="sort">
                    <option value="submission_date" <?php echo $sortBy === 'submission_date' ? 'selected' : ''; ?>>Sort by Date</option>
                    <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Sort by Title</option>
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
        <h5 class="mb-0">
            Report List
            <?php if ($search || $statusFilter): ?>
                <small class="text-muted">
                    (<?php echo $totalReports; ?> results found)
                </small>
            <?php endif; ?>
        </h5>
        
        <?php if (!empty($reports)): ?>
            <div class="btn-group">
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
                                <a href="?sort=title&order=<?php echo $sortBy === 'title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Title 
                                    <?php if ($sortBy === 'title'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=first_name&order=<?php echo $sortBy === 'first_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Student 
                                    <?php if ($sortBy === 'first_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" class="text-decoration-none text-white">
                                    Status 
                                    <?php if ($sortBy === 'status'): ?>
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
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr class="searchable-row">
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
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo formatDate($report['submission_date']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo formatFileSize($report['file_size']); ?></small>
                                </td>
                                <td>
                                    <?php if ($report['grade']): ?>
                                        <span class="badge bg-success">
                                            <?php echo htmlspecialchars($report['grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <small class="text-muted">Not graded</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="manage_report.php?id=<?php echo $report['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-1"></i>Review
                                        </a>
                                        
                                        <?php if ($report['status'] === 'submitted'): ?>
                                            <button type="button" class="btn btn-success" onclick="updateStatus(<?php echo $report['id']; ?>, 'under_review')">
                                                <i class="fas fa-play me-1"></i>Start Review
                                            </button>
                                        <?php elseif ($report['status'] === 'under_review'): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'approved')">
                                                            <i class="fas fa-check text-success me-1"></i>Approve
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'revision_required')">
                                                            <i class="fas fa-edit text-warning me-1"></i>Request Revision
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'rejected')">
                                                            <i class="fas fa-times text-danger me-1"></i>Reject
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
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
</script>

<?php require_once '../includes/footer.php'; ?>
