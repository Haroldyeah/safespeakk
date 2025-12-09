<?php
$pageTitle = 'School Dashboard';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];
$schoolName = $_SESSION['school_name'];

// Get school statistics
$stats = [
    'total_reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ?", [$schoolId])['count'],
    'pending_review' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'submitted'", [$schoolId])['count'],
    'under_investigation' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'under_investigation'", [$schoolId])['count'],
    'verified' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'verified'", [$schoolId])['count'],
    'rejected' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'rejected'", [$schoolId])['count'],
    'referred_to_mswd' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'referred_to_mswd'", [$schoolId])['count']
];
// Count students registered to this school
$stats['students'] = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE school_id = ? AND role = 'student'", [$schoolId])['count'];

// Get recent reports
$recentReports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id 
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     WHERE r.school_id = ? 
     ORDER BY r.submission_date DESC 
     LIMIT 10",
    [$schoolId]
);

// Get monthly submission statistics for chart
$monthlyStats = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as count
     FROM reports 
     WHERE school_id = ? AND submission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month DESC",
    [$schoolId]
);

require_once '../includes/header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
</style>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-school text-primary me-2"></i>
            School Dashboard
        </h1>
        <p class="text-muted mb-0">
            Welcome to <?php echo htmlspecialchars($schoolName); ?> reporting dashboard
        </p>
    </div>
    <div class="col-auto">
        <div class="btn-group">
            <a href="view_reports.php" class="btn btn-primary">
                <i class="fas fa-file-alt me-1"></i>View All Reports
            </a>
            <a href="analytics.php" class="btn btn-success">
                <i class="fas fa-chart-bar me-1"></i>Analytics
            </a>
            <button class="btn btn-outline-secondary" type="button" disabled>
                <i class="fas fa-users me-1"></i>Students
                <span class="badge bg-primary ms-2"><?php echo $stats['students']; ?></span>
            </button>
        </div>
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
    
    <div class="dashboard-card">                                                                          
        <div class="icon">
            <i class="fas fa-clock"></i>
        </div>
        <h3><?php echo $stats['pending_review']; ?></h3>
        <p>Pending Review</p>
    </div>
    
    <div class="dashboard-card" >
        <div class="icon">
            <i class="fas fa-eye"></i>
        </div>
        <h3><?php echo $stats['under_investigation']; ?></h3>
        <p>Under Investigation</p>
    </div>
    
    <div class="dashboard-card"">
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3><?php echo $stats['verified']; ?></h3>
        <p>Verified</p>
    </div>
</div>

<div class="row">
    <!-- Recent Reports -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Submissions
                </h5>
                <a href="view_reports.php" class="btn btn-sm btn-outline-primary">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentReports)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No reports submitted yet</h6>
                        <p class="text-muted mb-0">Reports from students will appear here once submitted</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($recentReports, 0, 5) as $report): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                        <span class="badge bg-light text-dark ms-2"><?php echo htmlspecialchars($report['student_id']); ?></span>
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
                                    <div class="mt-2">
                                        <a href="manage_report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Review
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats & Actions -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid">
                    <a href="analytics.php" class="btn btn-primary">
                        <i class="fas fa-chart-bar me-1"></i>View Analytics
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Report Status Distribution -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Report Distribution
                </h6>
            </div>
            <div class="card-body">
                <?php
                $total = $stats['total_reports'] > 0 ? $stats['total_reports'] : 1;
                $pending_percent = ($stats['pending_review'] / $total) * 100;
                $investigation_percent = ($stats['under_investigation'] / $total) * 100;
                $verified_percent = ($stats['verified'] / $total) * 100;
                $referred_to_mswd_percent = ($stats['referred_to_mswd'] / $total) * 100;
                $rejected_percent = ($stats['rejected'] / $total) * 100;
                ?>
                <!-- Status distribution progress bars -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-warning">Pending Review</small>
                        <small class="text-muted"><?php echo $stats['pending_review']; ?> reports</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo $pending_percent; ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-info">Under Investigation</small>
                        <small class="text-muted"><?php echo $stats['under_investigation']; ?> reports</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: <?php echo $investigation_percent; ?>%"></div>
                    </div>
                </div>
           <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-black">Referred to MSWD</small>
                        <small class="text-muted"><?php echo $stats['referred_to_mswd']; ?> reports</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-black" style="width: <?php echo $referred_to_mswd_percent; ?>%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-success">Verified</small>
                        <small class="text-muted"><?php echo $stats['verified']; ?> reports</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $verified_percent; ?>%"></div>
                    </div>
                </div>
           <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-danger">Rejected</small>
                        <small class="text-muted"><?php echo $stats['rejected']; ?> reports</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?php echo $rejected_percent; ?>%"></div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Total Reports: <?php echo $stats['total_reports']; ?></small>
                </div>
            </div>
        </div>
        <!-- Monthly Submissions -->
        <?php if (!empty($monthlyStats)): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Recent Activity
                </h6>
            </div>
            <div class="card-body">
                <?php foreach (array_slice($monthlyStats, 0, 6) as $stat): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></small>
                        <span class="badge bg-primary"><?php echo $stat['count']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
