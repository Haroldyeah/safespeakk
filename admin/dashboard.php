<?php
$pageTitle = 'Admin Dashboard';
require_once '../config/config.php';
requireRole('admin');

// Get system-wide statistics
$stats = [
    'total_reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports")['count'],
    'total_schools' => $db->fetchOne("SELECT COUNT(*) as count FROM schools WHERE status = 'active'")['count'],
    'total_students' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'")['count'],
    'pending_review' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'submitted'")['count'],
    'under_review' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_review'")['count'],
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'approved'")['count']
];

// Get recent activity
$recentReports = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, s.name as school_name 
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s ON r.school_id = s.id 
     ORDER BY r.submission_date DESC 
     LIMIT 8",
    []
);

// Get school statistics
$schoolStats = $db->fetchAll(
    "SELECT s.name, s.id, COUNT(r.id) as report_count,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN r.status = 'submitted' THEN 1 ELSE 0 END) as pending_count
     FROM schools s 
     LEFT JOIN reports r ON s.id = r.school_id 
     WHERE s.status = 'active'
     GROUP BY s.id, s.name 
     ORDER BY report_count DESC 
     LIMIT 6",
    []
);

// Get monthly statistics for chart
$monthlyStats = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as total_reports
     FROM reports 
     WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month DESC 
     LIMIT 12",
    []
);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-user-shield text-primary me-2"></i>
            System Administration
        </h1>
        <p class="text-muted mb-0">
            Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 
            Manage the entire capstone reporting system
        </p>
    </div>
    <div class="col-auto">
        <div class="btn-group">
            <a href="all_reports.php" class="btn btn-primary">
                <i class="fas fa-file-alt me-1"></i>All Reports
            </a>
            <a href="manage_schools.php" class="btn btn-outline-primary">
                <i class="fas fa-school me-1"></i>Schools
            </a>
        </div>
    </div>
</div>

<!-- System Statistics -->
<div class="stats-grid mb-4">
    <div class="dashboard-card">
        <div class="icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h3><?php echo $stats['total_reports']; ?></h3>
        <p>Total Reports</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--secondary-color) 0%, #10B981 100%);">
        <div class="icon">
            <i class="fas fa-school"></i>
        </div>
        <h3><?php echo $stats['total_schools']; ?></h3>
        <p>Active Schools</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--accent-color) 0%, #8B5CF6 100%);">
        <div class="icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <h3><?php echo $stats['total_students']; ?></h3>
        <p>Registered Students</p>
    </div>
    
    <div class="dashboard-card" style="background: linear-gradient(135deg, var(--warning-color) 0%, #F97316 100%);">
        <div class="icon">
            <i class="fas fa-clock"></i>
        </div>
        <h3><?php echo $stats['pending_review']; ?></h3>
        <p>Pending Review</p>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Report Activity
                </h5>
                <a href="all_reports.php" class="btn btn-sm btn-outline-primary">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentReports)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No reports submitted yet</h6>
                        <p class="text-muted mb-0">System activity will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentReports as $report): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                        <span class="mx-2">•</span>
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
                                    <div class="mt-2">
                                        <a href="all_reports.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
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
    
    <!-- System Overview -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="all_reports.php?status=submitted" class="btn btn-warning btn-sm">
                        <i class="fas fa-clock me-1"></i>Pending Reviews (<?php echo $stats['pending_review']; ?>)
                    </a>
                    <a href="all_reports.php?status=under_review" class="btn btn-info btn-sm">
                        <i class="fas fa-eye me-1"></i>Under Review (<?php echo $stats['under_review']; ?>)
                    </a>
                    <a href="manage_schools.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-school me-1"></i>Manage Schools
                    </a>
                    <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-users me-1"></i>Manage Users
                    </a>
                </div>
            </div>
        </div>
        
        <!-- School Performance -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>School Performance
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($schoolStats)): ?>
                    <p class="text-muted mb-0">No school data available</p>
                <?php else: ?>
                    <?php foreach ($schoolStats as $school): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="fw-bold"><?php echo htmlspecialchars($school['name']); ?></small>
                                <small class="text-muted"><?php echo $school['report_count']; ?> reports</small>
                            </div>
                            <div class="progress mb-1" style="height: 6px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo $school['report_count'] > 0 ? ($school['approved_count'] / $school['report_count']) * 100 : 0; ?>%"></div>
                            </div>
                            <small class="text-muted">
                                <?php echo $school['approved_count']; ?> approved, <?php echo $school['pending_count']; ?> pending
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>System Status
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-success mb-1"><?php echo $stats['approved']; ?></h4>
                            <small class="text-muted">Approved</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info mb-1"><?php echo $stats['under_review']; ?></h4>
                        <small class="text-muted">In Review</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <?php if (!empty($monthlyStats)): ?>
                <div>
                    <small class="text-muted fw-bold">Recent Activity:</small>
                    <?php foreach (array_slice($monthlyStats, 0, 3) as $stat): ?>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small><?php echo date('M Y', strtotime($stat['month'] . '-01')); ?></small>
                            <span class="badge bg-primary"><?php echo $stat['total_reports']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
