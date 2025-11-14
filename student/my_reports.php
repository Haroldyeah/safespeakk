<?php
$pageTitle = 'My Reports';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$schoolFilter = $_GET['school'] ?? '';

// Build query conditions
$conditions = ["r.student_id = ?"];
$params = [$studentId];

if ($search) {
    $conditions[] = "(r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
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
    "SELECT COUNT(*) as count FROM reports r WHERE $whereClause",
    $params
)['count'];

// Get reports
$reports = $db->fetchAll(
    "SELECT r.*, s.name as school_name,
        (SELECT COUNT(*) FROM report_evidence re WHERE re.report_id = r.id) as evidence_count,
        (SELECT file_name FROM report_evidence re WHERE re.report_id = r.id ORDER BY id ASC LIMIT 1) as evidence_sample_name,
        (SELECT file_path FROM report_evidence re WHERE re.report_id = r.id ORDER BY id ASC LIMIT 1) as evidence_sample_path,
        (SELECT file_size FROM report_evidence re WHERE re.report_id = r.id ORDER BY id ASC LIMIT 1) as evidence_sample_size
     FROM reports r 
     JOIN schools s ON r.school_id = s.id 
     WHERE $whereClause 
     ORDER BY r.submission_date DESC 
     LIMIT $perPage OFFSET $offset",
    $params
);

// Get schools for filter dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

// Calculate pagination
$totalPages = ceil($totalReports / $perPage);

require_once '../includes/header.php';

// Helper to convert local file path to web path
function getWebPath($filePath) {
    // If already a web path, return as absolute
    if (strpos($filePath, 'uploads/') === 0) return '/CapstoneTracker/' . $filePath;
    // Extract filename and prepend uploads/
    $filename = basename($filePath);
    return '/CapstoneTracker/uploads/' . $filename;
}
?>



<div class="row mb-4 align-items-center">
    <div class="col-12 col-md">
        <h1 class="h3 mb-3">
            <i class="fas fa-file-alt text-primary me-2"></i>
            My Reports
        </h1>
        <p class="text-muted mb-0">
            View and manage your submitted capstone reports
            <span class="badge bg-secondary ms-2"><?php echo $totalReports; ?> total</span>
        </p>
    </div>
    <div class="col-12 col-md-auto mt-3 mt-md-0">
        <a href="submit_report.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Submit New Report
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
                           placeholder="Search reports...">
                </div>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="under_investigation" <?php echo $statusFilter === 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                    <option value="referred_to_mswd" <?php echo $statusFilter === 'referred_to_mswd' ? 'selected' : ''; ?>>Referred to MSWD</option>
                    <option value="verified" <?php echo $statusFilter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="my_reports.php" class="btn btn-outline-secondary">
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
            <button class="btn btn-sm btn-outline-primary" onclick="exportTable('reportsTable', 'my_reports')">
                <i class="fas fa-download me-1"></i>Export
            </button>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No reports found</h6>
                <?php if ($search || $statusFilter || $schoolFilter): ?>
                    <p class="text-muted mb-3">Try adjusting your search criteria</p>
                    <a href="my_reports.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-3">Start by submitting your first capstone report</p>
                <?php endif; ?>
                <a href="submit_report.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Submit Report
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Type of Bullying</th>
                            <th>Description</th>
                            <th>School</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Date of Incident</th>
                            <th>Submitted</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr class="searchable-row">
                                <td>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($report['school_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $severityLabel = $report['severity'] ?? '';
                                    if (empty($severityLabel)) {
                                        try {
                                            $evidenceCount = (int)($report['evidence_count'] ?? 0);
                                            $analysis = analyze_report($report, $evidenceCount);
                                            $severityLabel = $analysis['severity'] ?? '';
                                        } catch (Throwable $t) {
                                            $severityLabel = '';
                                        }
                                    }
                                    if (!empty($severityLabel)) {
                                        $badgeClass = getSeverityBadgeClass($severityLabel);
                                        echo '<span class="badge ' . $badgeClass . '">' . htmlspecialchars(ucfirst($severityLabel)) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">N/A</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php 
                                        $statusDisplay = [
                                            'submitted' => 'Submitted',
                                            'under_investigation' => 'Under Investigation',
                                            'referred_to_mswd' => 'Referred to MSWD',
                                            'verified' => 'Verified',
                                            'rejected' => 'Rejected'
                                        ];
                                        echo $statusDisplay[$report['status']] ?? $report['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo $report['date_of_incident'] ? formatDate($report['date_of_incident']) : 'N/A'; ?></small>
                                </td>
                                <td>
                                    <small><?php echo formatDate($report['submission_date']); ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php if (in_array($report['status'], ['submitted', 'revision_required'])): ?>
                                            <a href="edit_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Report">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="view_evidence.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Evidence">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>">
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
<div class="modal fade" id="reportDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Report Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportDetailsContent">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function viewReportDetails(reportId) {
    const modal = new bootstrap.Modal(document.getElementById('reportDetailsModal'));
    const content = document.getElementById('reportDetailsContent');
    
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    modal.show();
    
    fetch(`get_report_details.php?id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load report details.</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">An error occurred while loading report details.</div>';
        });
}

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        fetch('delete_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({report_id: reportId})
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
            showAlert('An error occurred while deleting the report', 'danger');
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

<script>
function showEvidenceModal(imgSrc) {
    // Use global viewer helper
    showGlobalImage(imgSrc, 'Evidence Photo');
}
</script>