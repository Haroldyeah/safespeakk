<?php
$pageTitle = 'Student Dashboard';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];

// Get student statistics
$stats = [
    'total_reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ?", [$studentId])['count'],
    'submitted' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status = 'submitted'", [$studentId])['count'],
    'verified' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status = 'verified'", [$studentId])['count'],
    'under_investigation' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE student_id = ? AND status = 'under_investigation'", [$studentId])['count']
];

// Get recent reports
$recentReports = $db->fetchAll(
    "SELECT r.*, s.name as school_name 
     FROM reports r 
     JOIN schools s ON r.school_id = s.id 
     WHERE r.student_id = ? 
     ORDER BY r.submission_date DESC 
     LIMIT 3",
    [$studentId]
);

// Get school information
$school = $db->fetchOne("SELECT name FROM schools WHERE id = ?", [$schoolId]);

// Fetch full user record (including id_photo_path and student_id)
$userRecord = $db->fetchOne("SELECT id, first_name, last_name, email, student_id, id_photo_path, status, created_at FROM users WHERE id = ?", [$studentId]);

require_once '../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-12">
        <h1 class="h3 mb-3">
            <i class="fas fa-tachometer-alt text-primary me-2"></i>
            Student Dashboard
        </h1>
        <p class="text-muted mb-0">
            Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 
            <?php if ($school): ?>
                <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($school['name']); ?></span>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid mb-4">
    <div class="dashboard-card">
        <div class="icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h3><?php echo $stats['total_reports']; ?></h3>
        <p>Total Reports</p>
    </div>
    
    <div class="dashboard-card" ">
        <div class="icon">
            <i class="fas fa-clock"></i>
        </div>
        <h3><?php echo $stats['submitted']; ?></h3>
        <p>Awaiting Review</p>
    </div>
    
    <div class="dashboard-card">
        <div class="icon">
            <i class="fas fa-eye"></i>
        </div>
        <h3><?php echo $stats['under_investigation']; ?></h3>
        <p>Under Investigation</p>
    </div>
    
    <div class="dashboard-card">
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3><?php echo $stats['verified']; ?></h3>
        <p>Verified</p>
    </div>
</div>

<div class="row">
    <!-- Recent Reports -->
    <div class="col-lg-8 col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Reports
                </h5>
                <a href="my_reports.php" class="btn btn-sm btn-outline-primary">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentReports)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No reports submitted yet</h6>
                        <p class="text-muted mb-3">Start by submitting your first report using the Report Actions panel</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentReports as $report): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-school me-1"></i>
                                        <?php echo htmlspecialchars($report['school_name']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($report['submission_date']); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-<?php echo $report['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                   
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Profile Information -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>Profile Information
                </h5>
                <a href="edit_profile.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <div class="d-flex align-items-center mb-3">
                            <?php
                            // Get photo path from database
                            $photoPath = $userRecord['id_photo_path'] ?? '';
                            if (!empty($photoPath) && file_exists(__DIR__ . '/../' . $photoPath)):
                            ?>
                                <div class="me-3">
                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($photoPath); ?>" alt="ID Photo" class="rounded-circle" style="width:64px;height:64px;object-fit:cover;" />
                                </div>
                            <?php else: ?>
                                <div class="avatar-lg me-3">
                                    <?php 
                                    $firstName = $userRecord['first_name'] ?? ($_SESSION['first_name'] ?? 'U');
                                    echo htmlspecialchars(strtoupper(substr($firstName, 0, 1))); 
                                    ?>
                                </div>
                            <?php endif; ?>
                                <div>
                                    <h6 class="mb-1">
                                        <?php 
                                        $fullName = trim(($userRecord['first_name'] ?? $_SESSION['first_name'] ?? '') . ' ' . ($userRecord['last_name'] ?? $_SESSION['last_name'] ?? ''));
                                        echo htmlspecialchars($fullName ?: 'Student User'); 
                                        ?>
                                    </h6>
                                    <div class="small text-muted"><?php echo htmlspecialchars($userRecord['email'] ?? ''); ?></div>
                                    <span class="badge bg-primary">Student</span>
                                </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <p class="mb-1"><strong><i class="fas fa-envelope me-2"></i>Contact Email:</strong></p>
                        <p class="text-muted"><?php echo htmlspecialchars($userRecord['email'] ?? 'Not Available'); ?></p>
                    </div>
                    <div class="col-md-6 col-12">
                        <p class="mb-1"><strong><i class="fas fa-id-card me-2"></i>Student ID:</strong></p>
                        <p class="text-muted"><?php echo htmlspecialchars($userRecord['student_id'] ?? 'Not Available'); ?></p>
                    </div>
                    <div class="col-md-6 col-12">
                        <p class="mb-1"><strong><i class="fas fa-school me-2"></i>School:</strong></p>
                        <p class="text-muted"><?php echo htmlspecialchars($school['name'] ?? 'Not Available'); ?></p>
                    </div>
                    <div class="col-md-6 col-12">
                        <p class="mb-1"><strong><i class="fas fa-calendar me-2"></i>Joined:</strong></p>
                        <p class="text-muted"><?php echo formatDate($userRecord['created_at'] ?? date('Y-m-d')); ?></p>
                    </div>
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0"><strong>Account Status:</strong></p>
                                <?php 
                                $status = $userRecord['status'] ?? 'inactive';
                                $statusClass = $status === 'active' ? 'bg-success' : 'bg-danger';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $status === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <p class="mb-0"><strong>Submitted Reports:</strong></p>
                                <small class="text-muted"><?php echo $stats['total_reports']; ?> reports</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Tips -->
    <div class="col-lg-4 col-12">
        <!-- Report Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Report Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid">
                    <a href="submit_report.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Submit New Report
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Submission Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Submission Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, MP4, MOV, AVI, WMV, MKV files allowed</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Maximum file size: 50MB</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Include a descriptive title</small>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Provide a brief description</small>
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success me-2"></i>
                        <small>Select the correct school</small>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Status Meanings -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>Status Meanings
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="status-badge status-submitted me-2">Submitted</span>
                    <small>Awaiting initial review</small>
                </div>
                <div class="mb-2">
                    <span class="status-badge status-under_investigation me-2">Under Investigation</span>
                    <small>Being evaluated</small>
                </div>
                <div class="mb-2">
                    <span class="status-badge status-verified me-2">Verified</span>
                    <small>Successfully accepted</small>
                </div>
                <div class="mb-2">
                    <span class="status-badge status-referred_to_mswd me-2">Referred to MSWD</span>
                    <small>Referred to MSWD for further action</small>
                </div>
                <div class="mb-2">
                    <span class="status-badge status-rejected me-2">Rejected</span>
                    <small>Needs major revision</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
