<?php
$pageTitle = 'All Reports';
require_once '../config/config.php';
requireRole('admin');

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$schoolFilter = $_GET['school'] ?? '';
$sortBy = $_GET['sort'] ?? 'submission_date';
$sortOrder = $_GET['order'] ?? 'DESC';

// View specific report
$viewReportId = (int)($_GET['id'] ?? 0);

// Valid sort columns
$validSortColumns = ['submission_date', 'title', 'status', 'first_name', 'school_name'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'submission_date';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($search) {
    $conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.student_id LIKE ? OR s.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($statusFilter) {
    $conditions[] = "r.status = ?";
    $params[] = $statusFilter;
}

if ($schoolFilter) {
    $conditions[] = "r.school_id = ?";
    $params[] = $schoolFilter;
}

$whereClause = implode(' AND ', $conditions);

// Get total count
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s ON r.school_id = s.id 
     WHERE $whereClause",
    $params
)['count'];

// Get reports
$reports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s.name as school_name
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s ON r.school_id = s.id 
     WHERE $whereClause 
     ORDER BY $sortBy $sortOrder 
     LIMIT $perPage OFFSET $offset",
    $params
);

// Get schools for filter dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

// Get specific report if viewing
$viewReport = null;
if ($viewReportId) {
    $viewReport = $db->fetchOne(
        "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s.name as school_name
         FROM reports r 
         JOIN users u ON r.student_id = u.id 
         JOIN schools s ON r.school_id = s.id 
         WHERE r.id = ?",
        [$viewReportId]
    );
}

// Handle AJAX requests for status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input['action'] === 'update_status') {
        $reportId = (int)$input['report_id'];
        $newStatus = $input['status'];
        $comments = $input['comments'] ?? '';
        $grade = $input['grade'] ?? null;
        
        // Valid status transitions
        $validStatuses = ['submitted', 'under_review', 'approved', 'rejected', 'revision_required'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        // Update report
        $updateData = [
            'status' => $newStatus,
            'reviewed_by_admin' => $_SESSION['user_id'],
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($comments) {
            $updateData['admin_comments'] = $comments;
        }
        
        if ($grade && in_array($newStatus, ['approved'])) {
            $updateData['grade'] = $grade;
        }
        
        $success = $db->update('reports', $updateData, 'id = ?', [$reportId]);
        
        if ($success) {
            logActivity($db, $_SESSION['user_id'], 'admin', 'update_report_status', "Updated report #$reportId status to $newStatus");
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
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
            All Reports
        </h1>
        <p class="text-muted mb-0">
            System-wide report management and oversight
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
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search reports, students, schools...">
                </div>
            </div>
            
            <div class="col-md-2">
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
                <select class="form-select" name="school">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" 
                                <?php echo $schoolFilter == $school['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="sort">
                    <option value="submission_date" <?php echo $sortBy === 'submission_date' ? 'selected' : ''; ?>>Sort by Date</option>
                    <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Sort by Title</option>
                    <option value="first_name" <?php echo $sortBy === 'first_name' ? 'selected' : ''; ?>>Sort by Student</option>
                    <option value="school_name" <?php echo $sortBy === 'school_name' ? 'selected' : ''; ?>>Sort by School</option>
                    <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Sort by Status</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="all_reports.php" class="btn btn-outline-secondary">
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
            <?php if ($search || $statusFilter || $schoolFilter): ?>
                <small class="text-muted">
                    (<?php echo $totalReports; ?> results found)
                </small>
            <?php endif; ?>
        </h5>
        
        <?php if (!empty($reports)): ?>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" onclick="exportTable('reportsTable', 'all_reports')">
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
                <?php if ($search || $statusFilter || $schoolFilter): ?>
                    <p class="text-muted mb-3">Try adjusting your search criteria</p>
                    <a href="all_reports.php" class="btn btn-outline-primary">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0">System reports will appear here once students start submitting</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="reportsTable">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=title&order=<?php echo $sortBy === 'title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Title 
                                    <?php if ($sortBy === 'title'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=first_name&order=<?php echo $sortBy === 'first_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Student 
                                    <?php if ($sortBy === 'first_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=school_name&order=<?php echo $sortBy === 'school_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    School 
                                    <?php if ($sortBy === 'school_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Status 
                                    <?php if ($sortBy === 'status'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=submission_date&order=<?php echo $sortBy === 'submission_date' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Submitted 
                                    <?php if ($sortBy === 'submission_date'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
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
                                                <?php echo htmlspecialchars(substr($report['description'], 0, 60)) . (strlen($report['description']) > 60 ? '...' : ''); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($report['student_id']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($report['school_name']); ?>
                                    </span>
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
                                        <a href="?id=<?php echo $report['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'under_review')">
                                                        <i class="fas fa-eye text-info me-1"></i>Mark Under Review
                                                    </button>
                                                </li>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
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

<!-- Report Details Modal -->
<?php if ($viewReport): ?>
<div class="modal fade show" id="reportModal" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Details - <?php echo htmlspecialchars($viewReport['title']); ?></h5>
                <a href="all_reports.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="academic-section">
                            <h6>Report Information</h6>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Title:</div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($viewReport['title']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Student:</div>
                                <div class="col-sm-9">
                                    <?php echo htmlspecialchars($viewReport['first_name'] . ' ' . $viewReport['last_name']); ?>
                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($viewReport['student_id']); ?></small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">School:</div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($viewReport['school_name']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Description:</div>
                                <div class="col-sm-9"><?php echo nl2br(htmlspecialchars($viewReport['description'])); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Status:</div>
                                <div class="col-sm-9">
                                    <span class="status-badge status-<?php echo $viewReport['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $viewReport['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Submitted:</div>
                                <div class="col-sm-9"><?php echo formatDate($viewReport['submission_date']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Admin Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-info btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'under_review')">
                                        <i class="fas fa-eye me-1"></i>Mark Under Review
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'approved')">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'revision_required')">
                                        <i class="fas fa-edit me-1"></i>Request Revision
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'rejected')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="all_reports.php" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<script>
function updateStatus(reportId, newStatus) {
    let confirmMessage = `Are you sure you want to ${newStatus.replace('_', ' ')} this report?`;
    
    if (confirm(confirmMessage)) {
        fetch('all_reports.php', {
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
