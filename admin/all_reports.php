<?php
$pageTitle = 'All Reports';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';
requireRole('admin');

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$schoolFilter = $_GET['school'] ?? '';
$severityFilter = $_GET['severity'] ?? '';
$sortBy = $_GET['sort'] ?? 'submission_date';
$sortOrder = $_GET['order'] ?? 'DESC';

// View specific report
$viewReportId = (int)($_GET['id'] ?? 0);

// Valid sort columns
$validSortColumns = ['submission_date', 'title', 'status', 'first_name', 'report_school_name', 'severity'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'submission_date';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Build query conditions
$conditions = ["r.deleted_at IS NULL"];
$params = [];

if ($search) {
    $conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR s_report.name LIKE ? OR  r.bully_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
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

// Apply severity filter only if the column exists (safe for systems that haven't run migration)
if (!empty($severityFilter)) {
    try {
        $col = $db->fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'severity'", [DB_NAME, 'reports']);
        if ($col) {
            $conditions[] = "r.severity = ?";
            $params[] = $severityFilter;
        }
    } catch (Exception $e) {
        // ignore - severity column may not exist
    }
}

$whereClause = implode(' AND ', $conditions);

// Get total count (join users and schools because search may reference them)
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports r JOIN users u ON r.student_id = u.id JOIN schools s_report ON r.school_id = s_report.id WHERE $whereClause",
    $params
)["count"];

// Get reports
$reports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s_report.name as report_school_name, s_user.name as registered_school_name, u.school_id as registered_school_id
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s_report ON r.school_id = s_report.id 
     LEFT JOIN schools s_user ON u.school_id = s_user.id 
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
        "SELECT r.*, u.first_name, u.last_name, u.student_id, u.email, s_report.name as report_school_name, s_user.name as registered_school_name, u.school_id as registered_school_id
         FROM reports r 
         JOIN users u ON r.student_id = u.id 
         JOIN schools s_report ON r.school_id = s_report.id 
         LEFT JOIN schools s_user ON u.school_id = s_user.id 
         WHERE r.id = ?",
        [$viewReportId]
    );

    if ($viewReport) {
        $viewReport['evidence'] = $db->fetchAll(
            "SELECT * FROM report_evidence WHERE report_id = ?",
            [$viewReportId]
        );
    }
}

// Handle AJAX requests for status updates and soft delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    // Debug log
    file_put_contents(__DIR__ . '/../debug_admin_status.log', date('Y-m-d H:i:s') . "\nINPUT: " . var_export($input, true) . "\n", FILE_APPEND);
    
        if ($input['action'] === 'update_status') {
        $reportId = (int)$input['report_id'];
        $newStatus = $input['status'];
        $comments = $input['comments'] ?? '';
        $validStatuses = ['submitted', 'under_investigation', 'referred_to_mswd', 'verified', 'rejected'];
        header('Content-Type: application/json');
        if (!in_array($newStatus, $validStatuses)) {
            file_put_contents(__DIR__ . '/../debug_admin_status.log', "Invalid status: $newStatus\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        $updateData = [
            'status' => $newStatus,
            'reviewed_by_admin' => $_SESSION['user_id'],
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        if ($comments) {
            $updateData['admin_comments'] = $comments;
        }
        // Use named parameter for consistency
        $success = $db->update('reports', $updateData, 'id = :id', ['id' => $reportId]);
        file_put_contents(__DIR__ . '/../debug_admin_status.log', "UpdateData: " . var_export($updateData, true) . "\nUpdateResult: " . var_export($success, true) . "\n", FILE_APPEND);
        if ($success instanceof PDOStatement) {
            // Fetch student info for email
            $reportDetails = $db->fetchOne(
                "SELECT r.title, r.description, u.first_name, u.last_name, u.email FROM reports r JOIN users u ON r.student_id = u.id WHERE r.id = ?",
                [$reportId]
            );
            if ($reportDetails && $reportDetails['email']) {
                // Fetch SMTP settings for the school associated with the report
                $schoolIdForReport = $db->fetchOne("SELECT school_id FROM reports WHERE id = ?", [$reportId])['school_id'];
                $schoolSmtp = $db->fetchOne("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM schools WHERE id = ?", [$schoolIdForReport]);

                require_once __DIR__ . '/../templates/email/load_template.php';
                $statusLabel = ucfirst(str_replace('_', ' ', $newStatus));
                $reportUrl = rtrim(BASE_URL, '/') . '/student/edit_report.php?id=' . $reportId;
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
                    error_log('Mail Error: ' . $e->getMessage());
                }
            }
            logActivity($db, $_SESSION['user_id'], 'admin', 'update_report_status', "Updated report #$reportId status to $newStatus");
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            exit;
        }
    }
    
    // Handle soft delete
    if ($input['action'] === 'soft_delete') {
        $reportIds = $input['report_ids'] ?? [];
        header('Content-Type: application/json');
        
        if (empty($reportIds)) {
            echo json_encode(['success' => false, 'message' => 'No reports selected']);
            exit;
        }
        
        $deletedCount = 0;
        foreach ($reportIds as $reportId) {
            $reportId = (int)$reportId;
            $updateData = [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by_admin' => $_SESSION['user_id']
            ];
            
            $success = $db->update('reports', $updateData, 'id = :id', ['id' => $reportId]);
            if ($success instanceof PDOStatement) {
                $deletedCount++;
                logActivity($db, $_SESSION['user_id'], 'admin', 'soft_delete_report', "Soft deleted report #$reportId");
            }
        }
        
        if ($deletedCount > 0) {
            echo json_encode(['success' => true, 'message' => "$deletedCount report(s) deleted successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete reports']);
        }
        exit;
    }

    // Handle add intervention (regular form POST, not JSON)
    if (!empty($_POST['action']) && $_POST['action'] === 'add_intervention') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $sessionDate = $_POST['session_date'] ?? '';
        $counselorName = $_POST['counselor_name'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $outcome = $_POST['outcome'] ?? '';
        
        if (!$reportId || !$sessionDate) {
            $_SESSION['error'] = 'Report ID and Session Date are required.';
            header("Location: all_reports.php?id=$reportId");
            exit;
        }
        
        // Verify report exists
        $report = $db->fetchOne("SELECT r.*, u.email, u.first_name, u.last_name FROM reports r JOIN users u ON r.student_id = u.id WHERE r.id = ?", [$reportId]);
        if (!$report) {
            $_SESSION['error'] = 'Report not found.';
            header("Location: all_reports.php");
            exit;
        }
        
        // Insert intervention
        $interventionData = [
            'report_id' => $reportId,
            'added_by_user_id' => $_SESSION['user_id'],
            'added_by_type' => 'admin',
            'session_date' => $sessionDate,
            'counselor_name' => $counselorName,
            'notes' => $notes,
            'outcome' => $outcome,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $success = $db->insert('interventions', $interventionData);
            if ($success) {
                // Send email notification to student
                $schoolIdForReport = $report['school_id'] ?? 0;
                $schoolSmtp = $db->fetchOne("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM schools WHERE id = ?", [$schoolIdForReport]);
                
                if ($report['email'] && $schoolSmtp) {
                    require_once __DIR__ . '/../templates/email/load_template.php';
                    $body = load_email_template('intervention_added.php', [
                        'studentName' => $report['first_name'] . ' ' . $report['last_name'],
                        'reportTitle' => $report['title'],
                        'sessionDate' => formatDate($sessionDate),
                        'counselorName' => $counselorName,
                        'notes' => $notes,
                        'outcome' => $outcome,
                        'appName' => APP_NAME,
                        'baseUrl' => BASE_URL
                    ]);
                    $subject = "Intervention Recorded for Report: {$report['title']}";
                    
                    try {
                        sendMail(
                            $report['email'],
                            $subject,
                            $body,
                            $schoolSmtp['from_email'],
                            $schoolSmtp['from_name']
                        );
                    } catch (Exception $e) {
                        error_log('Mail Error: ' . $e->getMessage());
                    }
                }
                
                // Log activity
                logActivity($db, $_SESSION['user_id'], 'admin', 'add_intervention', "Added intervention to report #$reportId");
                
                $_SESSION['success'] = 'Intervention recorded successfully and student notified.';
            } else {
                $_SESSION['error'] = 'Failed to record intervention.';
            }
        } catch (Exception $e) {
            error_log('Intervention Error: ' . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while recording the intervention.';
        }
        
        header("Location: all_reports.php?id=$reportId");
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
                    <option value="under_investigation" <?php echo $statusFilter === 'under_investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                    <option value="referred_to_mswd" <?php echo $statusFilter === 'referred_to_mswd' ? 'selected' : ''; ?>>Referred to MSWD</option>
                    <option value="verified" <?php echo $statusFilter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="severity">
                    <option value="">All Severities</option>
                    <option value="low" <?php echo $severityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $severityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $severityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $severityFilter === 'critical' ? 'selected' : ''; ?>>Critical</option>
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
                    <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Sort by Type</option>
                    <option value="first_name" <?php echo $sortBy === 'first_name' ? 'selected' : ''; ?>>Sort by Student</option>
                    <option value="report_school_name" <?php echo $sortBy === 'report_school_name' ? 'selected' : ''; ?>>Sort by School</option>
                    <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Sort by Status</option>
                    <option value="severity" <?php echo $sortBy === 'severity' ? 'selected' : ''; ?>>Sort by Severity</option>
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
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-3">
                Report List
                <?php if ($search || $statusFilter || $schoolFilter): ?>
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
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                                </div>
                            </th>
                            <th>
                                <a href="?sort=title&order=<?php echo $sortBy === 'title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Type of Bullying 
                                    <?php if ($sortBy === 'title'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Involved</th>
                            <th>
                                <a href="?sort=first_name&order=<?php echo $sortBy === 'first_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    Student 
                                    <?php if ($sortBy === 'first_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=report_school_name&order=<?php echo $sortBy === 'report_school_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>" class="text-decoration-none text-white">
                                    School 
                                    <?php if ($sortBy === 'report_school_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&severity=<?php echo urlencode($severityFilter); ?>" class="text-decoration-none text-white">
                                    Status 
                                    <?php if ($sortBy === 'status'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=severity&order=<?php echo $sortBy === 'severity' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&severity=<?php echo urlencode($severityFilter); ?>" class="text-decoration-none text-white">
                                    Severity
                                    <?php if ($sortBy === 'severity'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=submission_date&order=<?php echo $sortBy === 'submission_date' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&severity=<?php echo urlencode($severityFilter); ?>" class="text-decoration-none text-white">
                                    Submitted 
                                    <?php if ($sortBy === 'submission_date'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
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
                                                <?php echo htmlspecialchars(substr($report['description'], 0, 60)) . (strlen($report['description']) > 60 ? '...' : ''); ?>
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
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($report['report_school_name']); ?>
                                    </span>
                                    <?php if ($report['school_id'] !== $report['registered_school_id']): ?>
                                        <br><span class="badge bg-warning text-dark mt-1">Registered: <?php echo htmlspecialchars($report['registered_school_name'] ?? 'N/A'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php echo str_replace('_', ' ', $report['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $severity = $report['severity'] ?? 'N/A';
                                    $severityClass = ($severity === 'N/A') ? 'n-a' : strtolower($severity);
                                    ?>
                                    <span class="severity-badge severity-<?php echo $severityClass; ?>">
                                        <?php 
                                        // Add icon based on severity
                                        if ($severity === 'N/A') {
                                            echo '<i class="fas fa-question-circle me-1"></i>';
                                        } else {
                                            switch(strtolower($severity)) {
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
                                        }
                                        echo htmlspecialchars($severity);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo formatDate($report['submission_date']); ?></small>
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
                                                    <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'under_investigation')">
                                                        <i class="fas fa-search text-primary me-1"></i>Mark Under Investigation
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'referred_to_mswd')">
                                                        <i class="fas fa-share-square text-dark me-1"></i>Refer to MSWD
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'verified')">
                                                        <i class="fas fa-check-circle text-success me-1"></i>Mark Verified
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" onclick="updateStatus(<?php echo $report['id']; ?>, 'rejected')">
                                                        <i class="fas fa-times text-danger me-1"></i>Reject
                                                    </button>
                                                </li>
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
                <h5 class="modal-title">Bullying Report Details - <?php echo htmlspecialchars($viewReport['title']); ?></h5>
                <a href="all_reports.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="academic-section">
                            <!-- Student ID Photo Above Title (Clickable for Zoom) -->
                            <?php
                            $photoPath = $viewReport['id_photo_path'] ?? '';
                            $absolutePhotoPath = '';
                            if ($photoPath) {
                                $absolutePhotoPath = __DIR__ . '/../' . $photoPath;
                            }
                            if ($photoPath) {
                                echo '<div class="text-center mb-3">'
                                    . '<a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#idPhotoModal">'
                                    . '<img src="../' . htmlspecialchars($photoPath) . '" alt="ID Photo" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #007bff;box-shadow:0 2px 16px rgba(0,0,0,0.12);margin-bottom:10px;cursor:pointer;" />'
                                    . '</a>';
                                if (!file_exists($absolutePhotoPath)) {
                                    echo '<div class="text-danger small">ID photo file not found: ' . htmlspecialchars($photoPath) . '</div>';
                                }
                                echo '</div>';
                                // Modal for zoomed photo
                                echo '<div class="modal fade" id="idPhotoModal" tabindex="-1" aria-labelledby="idPhotoModalLabel" aria-hidden="true">'
                                    . '<div class="modal-dialog modal-dialog-centered">'
                                    . '<div class="modal-content">'
                                    . '<div class="modal-header">'
                                    . '<h5 class="modal-title" id="idPhotoModalLabel">Student ID Photo</h5>'
                                    . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
                                    . '</div>'
                                    . '<div class="modal-body text-center">'
                                    . '<img src="../' . htmlspecialchars($photoPath) . '" alt="ID Photo" style="max-width:100%;max-height:400px;border-radius:12px;border:3px solid #007bff;box-shadow:0 2px 16px rgba(0,0,0,0.12);" />'
                                    . '</div>'
                                    . '</div>'
                                    . '</div>'
                                    . '</div>';
                            }
                            ?>
                            <h6>Report Information</h6>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Type of Bullying:</div>
                                <div class="col-sm-9"><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($viewReport['title']); ?></span></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Student:</div>
                                <div class="col-sm-9">
                                    <?php echo htmlspecialchars($viewReport['first_name'] . ' ' . $viewReport['last_name']); ?>
                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($viewReport['student_id']); ?></small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">School (Submitted To):</div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($viewReport['report_school_name']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">School (Registered):</div>
                                <div class="col-sm-9">
                                    <?php echo htmlspecialchars($viewReport['registered_school_name'] ?? 'N/A'); ?>
                                    <?php if ($viewReport['school_id'] !== $viewReport['registered_school_id']): ?>
                                        <span class="badge bg-warning text-dark ms-2">Student is registered at a different school</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($viewReport['date_of_incident']): ?>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Date of Incident:</div>
                                <div class="col-sm-9"><?php echo formatDate($viewReport['date_of_incident']); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Name of Bully:</div>
                                <div class="col-sm-9"><?php echo htmlspecialchars($viewReport['bully_name'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Description:</div>
                                <div class="col-sm-9"><?php echo nl2br(htmlspecialchars($viewReport['description'])); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Recommended Actions:</div>
                                <div class="col-sm-9">
                                    <?php
                                    // Prefer stored recommended actions (if DB has them). Otherwise run analyzer for detailed actions and severity.
                                    $recommendedActions = $viewReport['recommended_actions'] ?? '';
                                    $severityLabel = $viewReport['severity'] ?? '';

                                    if (empty($recommendedActions) || empty($severityLabel)) {
                                        try {
                                            $analysis = analyze_report($viewReport, !empty($viewReport['evidence']) ? count($viewReport['evidence']) : 0);
                                            if (empty($recommendedActions) && !empty($analysis['suggested_actions'])) {
                                                $recommendedActions = $analysis['suggested_actions'];
                                            }
                                            if (empty($severityLabel) && !empty($analysis['severity'])) {
                                                $severityLabel = $analysis['severity'];
                                            }
                                        } catch (Throwable $t) {
                                            // fallback: leave as-is
                                        }
                                    }

                                    if (!empty($severityLabel)) {
                                        echo '<div class="mb-2"><strong>Severity:</strong> <span class="badge bg-danger text-white">' . htmlspecialchars(ucfirst($severityLabel)) . '</span></div>';
                                    }

                                    echo nl2br(htmlspecialchars($recommendedActions ?: 'No recommended actions available.'));
                                    ?>
                                </div>
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
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Evidence:</div>
                                <div class="col-sm-9"><?php echo !empty($viewReport['evidence']) ? count($viewReport['evidence']) . ' file(s)' : 'None'; ?></div>
                            </div>
                            <?php if (!empty($viewReport['evidence'])): ?>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Evidence:</div>
                                <div class="col-sm-9">
                                    <?php foreach ($viewReport['evidence'] as $index => $evidence): ?>
                                        <div class="mb-2">
                                            <?php
                                            echo htmlspecialchars($evidence['file_name']);
                                            echo '<small class="text-muted ms-2">(' . formatFileSize($evidence['file_size']) . ')</small>';

                                            $fileExt = strtolower(pathinfo($evidence['file_path'], PATHINFO_EXTENSION));
                                            $imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
                                            $videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'm4v', '3gp', 'avi', 'wmv', 'flv'];
                                            $pdfTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
                                            $webPath = $evidence['file_path'];
                                            if (strpos($webPath, 'uploads/') !== 0) {
                                                $webPath = 'uploads/' . basename($webPath);
                                            }
                                            ?>
                                            <br>
                                            <div class="btn-group mb-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleEvidence(<?php echo $index; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                                <a href="../student/download_report.php?id=<?php echo $viewReport['id']; ?>&evidence_id=<?php echo $evidence['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            </div>
                                            <div id="evidencePreview-<?php echo $index; ?>" style="display:none;" class="mt-2">
                                                <?php if (in_array($fileExt, $imageTypes)): ?>
                                                    <a href="javascript:void(0);" onclick="showGlobalImage('../<?php echo htmlspecialchars($webPath); ?>', 'Evidence Photo')">
                                                        <img src="../<?php echo htmlspecialchars($webPath); ?>" alt="Evidence Photo" style="max-width:100%;max-height:400px;border-radius:12px;border:1px solid #007bff;box-shadow:0 2px 16px rgba(0,0,0,0.12);margin-bottom:10px;cursor:pointer;" />
                                                    </a>
                                                <?php elseif (in_array($fileExt, $videoTypes)): ?>
                                                    <video controls style="max-width:100%;max-height:400px;border-radius:12px;margin-bottom:10px;">
                                                        <source src="../<?php echo htmlspecialchars($webPath); ?>" type="<?php echo ($fileExt === 'mov') ? 'video/quicktime' : 'video/' . htmlspecialchars($fileExt); ?>">Your browser does not support the video tag.
                                                    </video>
                                                    <?php if ($fileExt === 'mov'): ?>
                                                        <small class="text-muted d-block mt-1">Note: .mov files may not play in all browsers. Please <a href="../student/download_report.php?id=<?php echo $viewReport['id']; ?>&evidence_id=<?php echo $evidence['id']; ?>">download the file</a> to view it.</small>
                                                    <?php endif; ?>
                                                <?php elseif (in_array($fileExt, $pdfTypes)): ?>
                                                    <iframe src="../<?php echo htmlspecialchars($webPath); ?>" style="width:100%;height:400px;border-radius:12px;border:1px solid #007bff;margin-bottom:10px;" frameborder="0"></iframe>
                                                <?php else: ?>
                                                    <div class="alert alert-info">File type not supported for preview.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <script>
                                    function toggleEvidence(index) {
                                        var elementId = 'evidencePreview-' + index;
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
                        </div>

                        <!-- Interventions Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Interventions</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                // Fetch existing interventions for this report
                                try {
                                    $interventions = $db->fetchAll(
                                        "SELECT i.*, u.first_name, u.last_name, s.name as school_name 
                                         FROM interventions i 
                                         LEFT JOIN users u ON i.added_by_user_id = u.id 
                                         LEFT JOIN schools s ON u.school_id = s.id 
                                         WHERE i.report_id = ? 
                                         ORDER BY i.session_date DESC",
                                        [$viewReport['id']]
                                    );
                                } catch (Exception $e) {
                                    $interventions = [];
                                }
                                ?>
                                
                                <?php if (!empty($interventions)): ?>
                                    <div class="mb-3">
                                        <h6>Existing Interventions</h6>
                                        <div style="max-height: 300px; overflow-y: auto;">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Counselor</th>
                                                        <th>Outcome</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($interventions as $intervention): ?>
                                                        <tr>
                                                            <td>
                                                                <small><?php echo formatDate($intervention['session_date']); ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($intervention['counselor_name'] ?? 'N/A'); ?></small>
                                                            </td>
                                                            <td>
                                                                <small><?php echo htmlspecialchars($intervention['outcome'] ?? 'Pending'); ?></small>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small">No interventions recorded yet.</p>
                                <?php endif; ?>

                                <hr>
                                <h6>Add New Intervention</h6>
                                <form id="addInterventionForm" method="POST" action="all_reports.php">
                                    <input type="hidden" name="action" value="add_intervention">
                                    <input type="hidden" name="report_id" value="<?php echo $viewReport['id']; ?>">
                                    
                                    <div class="mb-2">
                                        <label for="sessionDate" class="form-label form-label-sm">Session Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="sessionDate" name="session_date" required>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label for="counselorName" class="form-label form-label-sm">Counselor Name</label>
                                        <input type="text" class="form-control form-control-sm" id="counselorName" name="counselor_name" placeholder="e.g., Dr. John Smith">
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label for="interventionNotes" class="form-label form-label-sm">Notes</label>
                                        <textarea class="form-control form-control-sm" id="interventionNotes" name="notes" rows="3" placeholder="Intervention details..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="interventionOutcome" class="form-label form-label-sm">Outcome</label>
                                        <select class="form-select form-select-sm" id="interventionOutcome" name="outcome">
                                            <option value="">Select outcome...</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Completed">Completed</option>
                                            <option value="Referral">Referral</option>
                                            <option value="Follow-up Needed">Follow-up Needed</option>
                                            <option value="Resolved">Resolved</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fas fa-save me-1"></i>Save Intervention
                                    </button>
                                </form>
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
                                    <button class="btn btn-primary btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'under_investigation')">
                                        <i class="fas fa-search me-1"></i>Mark Under Investigation
                                    </button>
                                    <button class="btn btn-dark btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'referred_to_mswd')">
                                        <i class="fas fa-share-square me-1"></i>Refer to MSWD
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'verified')">
                                        <i class="fas fa-check-circle me-1"></i>Mark Verified
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $viewReport['id']; ?>, 'rejected')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                    <hr>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteReport(<?php echo $viewReport['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i>Delete Report
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

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        fetch('all_reports.php', {
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
                fetch('all_reports.php', {
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

<style>
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-submitted { background-color: #EBF8FF; color: #1e38afff; }
.status-rejected { background-color: #FEE2E2; color: #B91C1C; }
.status-under_investigation { background-color: #E6F7FF; color: #065f9f; }
.status-referred_to_mswd { background-color: #F3F4F6; color: #111827; }
.status-verified { background-color: #D1FAE5; color: #065f46; }

.form-check-input:indeterminate {
    background-color: #6c757d;
    border-color: #6c757d;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}
</style>

<?php require_once '../includes/footer.php'; ?>