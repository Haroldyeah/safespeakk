<?php
$pageTitle = 'School Analytics';
require_once '../config/config.php';
requireRole('school');

// Database connection is already initialized in config.php
global $db;

$schoolId = $_SESSION['school_id'];

// Get date range from filters
$dateFrom = $_GET['date_from'] ?? date('Y-01-01');
$dateTo = $_GET['date_to'] ?? date('Y-12-31');
$academicYear = $_GET['academic_year'] ?? date('Y');

// Get school information
$school = $db->fetchOne(
    "SELECT * FROM schools WHERE id = ?",
    [$schoolId]
);

// Overall statistics
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$submittedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'submitted' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$underInvestigationReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'under_investigation' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$referredToMswd = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'referred_to_mswd' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$verifiedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'verified' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];


$rejectedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'rejected' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];





// Monthly submission trends with all statuses
$monthlyData = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as under_investigation_count,
        SUM(CASE WHEN status = 'referred_to_mswd' THEN 1 ELSE 0 END) as referred_to_mswd_count
     FROM reports 
     WHERE school_id = ? AND submission_date BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month",
    [$schoolId, $dateFrom, $dateTo]
);

// Top performing students
$topStudents = $db->fetchAll(
    "SELECT 
        u.first_name, u.last_name, u.student_id,
        COUNT(r.id) as total_reports,
        SUM(CASE WHEN r.status = 'verified' THEN 1 ELSE 0 END) as verified_reports
     FROM users u
     LEFT JOIN reports r ON u.id = r.student_id AND r.submission_date BETWEEN ? AND ?
     WHERE u.school_id = ? AND u.role = 'student'
     GROUP BY u.id
     HAVING total_reports > 0
     ORDER BY verified_reports DESC
     LIMIT 10",
    [$dateFrom, $dateTo, $schoolId]
);

// Recent activity
$recentActivity = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id
     FROM reports r
     JOIN users u ON r.student_id = u.id
     WHERE r.school_id = ? AND r.submission_date BETWEEN ? AND ?
     ORDER BY r.submission_date DESC
     LIMIT 15",
    [$schoolId, $dateFrom, $dateTo]
);

// Calculate percentages
$approvalRate = $totalReports > 0 ? round(($verifiedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;


// Get active students count
$activeStudents = $db->fetchOne(
    "SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-chart-bar text-primary me-2"></i>
            School Analytics Dashboard
        </h1>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($school['name']); ?> - Report Statistics & Analysis</p>
    </div>
    <div class="col-auto">
        <div class="btn-group">
            <button class="btn btn-primary" onclick="printAnalytics()">
                <i class="fas fa-print me-1"></i>Print Report
            </button>
            <button class="btn btn-success" onclick="downloadAnalytics()">
                <i class="fas fa-download me-1"></i>Download PDF
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select class="form-select" name="academic_year">
                    <option value="2024" <?php echo $academicYear == '2024' ? 'selected' : ''; ?>>2024</option>
                    <option value="2023" <?php echo $academicYear == '2023' ? 'selected' : ''; ?>>2023</option>
                    <option value="2022" <?php echo $academicYear == '2022' ? 'selected' : ''; ?>>2022</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="analytics-content">
    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $totalReports; ?></h3>
                    <p class="mb-0 text-muted small">Total Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $submittedReports; ?></h3>
                    <p class="mb-0 text-muted small">Submitted</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $verifiedReports; ?></h3>
                    <p class="mb-0 text-muted small">Verified</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $referredToMswd; ?></h3>
                    <p class="mb-0 text-muted small">Referred to MSWD</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-danger"><?php echo $rejectedReports; ?></h3>
                    <p class="mb-0 text-muted small">Rejected</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $activeStudents; ?></h3>
                    <p class="mb-0 text-muted small">Active Students</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Report Processing Pipeline</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">Submitted</small>
                            <small class="text-muted"><?php echo $submittedReports; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: <?php echo $totalReports > 0 ? ($submittedReports / $totalReports) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
           
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">Under Investigation</small>
                            <small class="text-muted"><?php echo $underInvestigationReports; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $totalReports > 0 ? ($underInvestigationReports / $totalReports) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">Referred to MSWD</small>
                            <small class="text-muted"><?php echo $referredToMswd; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-black" style="width: <?php echo $totalReports > 0 ? ($referredToMswd / $totalReports) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">Verified</small>
                            <small class="text-muted"><?php echo $verifiedReports; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $totalReports > 0 ? ($verifiedReports / $totalReports) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                     <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">Rejected</small>
                            <small class="text-muted"><?php echo $rejectedReports; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" style="width: <?php echo $totalReports > 0 ? ($rejectedReports / $totalReports) * 100 : 0; ?>%"></div>
         
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Report Submissions & Status Trends</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($monthlyData)): ?>
                        <canvas id="trendsChart" height="300"></canvas>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No data available for the selected period to display the chart.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Reports Summary -->
    <div class="row mb-4">
        <!-- Students with Reports -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Students with Submitted Reports</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topStudents)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Student ID</th>
                                        <th>Reports Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topStudents as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($student['student_id']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $student['total_reports']; ?></span>
                                            </td>
                                          
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No students have submitted reports in this period</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Report Activity</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($recentActivity)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Report Title</th>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $report): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                        <?php if ($report['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($report['description'], 0, 50)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($report['student_id']); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $report['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo formatDate($report['submission_date']); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No recent activity in selected period</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Status Distribution Chart 
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Submitted', 'Under Investigation', 'Referred to MSWD', 'Verified', 'Rejected'],
        datasets: [{
            data: [<?php echo $submittedReports; ?>, <?php echo $underInvestigationReports; ?>, <?php echo $referredToMswd; ?>, <?php echo $verifiedReports; ?>, <?php echo $rejectedReports; ?>],
            backgroundColor: ['#3B82F6', '#8B5CF6', '#1F2937', '#10B981', '#EF4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});

// Monthly Trends Chart
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
const trendsChart = new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_column($monthlyData, 'month')) . "'"; ?>],
        datasets: [{
            label: 'Total Submissions',
            data: [<?php echo implode(',', array_column($monthlyData, 'total_submissions')); ?>],
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#3B82F6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }, {
            label: 'Verified',
            data: [<?php echo implode(',', array_column($monthlyData, 'verified_count')); ?>],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#10B981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }, {
            label: 'Under Investigation',
            data: [<?php echo implode(',', array_column($monthlyData, 'under_investigation_count')); ?>],
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#8B5CF6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }, {
            label: 'Referred to MSWD',
            data: [<?php echo implode(',', array_column($monthlyData, 'referred_to_mswd_count')); ?>],
            borderColor: '#1F2937',
            backgroundColor: 'rgba(31, 41, 55, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#1F2937',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }, {
            label: 'Rejected',
            data: [<?php echo implode(',', array_column($monthlyData, 'rejected_count')); ?>],
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#EF4444',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});



function printAnalytics() {
    window.print();
}

function downloadAnalytics() {
    // Create form for PDF download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'download_analytics.php';
    
    // Add current filter parameters
    const params = new URLSearchParams(window.location.search);
    for (const [key, value] of params) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Print styles
const printStyles = `
<style media="print">
    @page { margin: 1in; }
    .btn, .card-header { display: none !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    canvas { max-height: 300px !important; }
    .no-print { display: none !important; }
</style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>



<?php require_once '../includes/footer.php'; ?>