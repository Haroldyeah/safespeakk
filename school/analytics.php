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
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$submittedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'submitted' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$underReviewReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'under_review' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$approvedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'approved' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$rejectedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'rejected' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

// Monthly submission trends
$monthlyData = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
     FROM reports 
     WHERE school_id = ? AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month",
    [$schoolId, $dateFrom, $dateTo]
);

// Top performing students - FIXED to properly exclude soft-deleted reports
$topStudents = $db->fetchAll(
    "SELECT 
        u.first_name, u.last_name, u.student_id,
        COUNT(r.id) as total_reports,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reports
     FROM users u
     INNER JOIN reports r ON u.id = r.student_id 
     WHERE u.school_id = ? 
       AND u.role = 'student'
       AND r.submission_date BETWEEN ? AND ?
       AND r.deleted_at IS NULL
     GROUP BY u.id, u.first_name, u.last_name, u.student_id
     HAVING total_reports > 0
     ORDER BY approved_reports DESC, total_reports DESC
     LIMIT 10",
    [$schoolId, $dateFrom, $dateTo]
);

// Recent activity
$recentActivity = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.student_id
     FROM reports r
     JOIN users u ON r.student_id = u.id
     WHERE r.school_id = ? AND r.submission_date BETWEEN ? AND ? AND r.deleted_at IS NULL
     ORDER BY r.submission_date DESC
     LIMIT 15",
    [$schoolId, $dateFrom, $dateTo]
);

// Calculate percentages
$approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

// Get active students count (students who have submitted non-deleted reports)
$activeStudents = $db->fetchOne(
    "SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL",
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
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $totalReports; ?></h3>
                    <p class="mb-0 text-muted small">Total Reports</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $approvedReports; ?></h3>
                    <p class="mb-0 text-muted small">Approved</p>
                    <small class="text-success"><?php echo $approvalRate; ?>%</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $underReviewReports; ?></h3>
                    <p class="mb-0 text-muted small">Under Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $submittedReports; ?></h3>
                    <p class="mb-0 text-muted small">Submitted</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-danger"><?php echo $rejectedReports; ?></h3>
                    <p class="mb-0 text-muted small">Rejected</p>
                    <small class="text-danger"><?php echo $rejectionRate; ?>%</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-secondary"><?php echo $activeStudents; ?></h3>
                    <p class="mb-0 text-muted small">Active Students</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Status Distribution Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Student Report Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Submission Trends Chart -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Report Submissions</h5>
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
                    <small class="text-muted">Only showing students with non-deleted reports</small>
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
                                        <th>Approved</th>
                                        <th>Status</th>
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
                                            <td>
                                                <span class="badge bg-success"><?php echo $student['approved_reports']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($student['approved_reports'] > 0): ?>
                                                    <span class="badge bg-success">Has Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">All Pending</span>
                                                <?php endif; ?>
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

        <!-- Performance Summary -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Performance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-success"><?php echo $approvalRate; ?>%</h4>
                                <small class="text-muted">Approval Rate</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-danger"><?php echo $rejectionRate; ?>%</h4>
                            <small class="text-muted">Rejection Rate</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h5 class="text-primary"><?php echo count($topStudents); ?></h5>
                                <small class="text-muted">Students with Reports</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h5 class="text-info"><?php echo $activeStudents; ?></h5>
                            <small class="text-muted">Unique Students</small>
                        </div>
                    </div>
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
        labels: ['Approved', 'Under Review', 'Submitted', 'Rejected'],
        datasets: [{
            data: [<?php echo $approvedReports; ?>, <?php echo $underReviewReports; ?>, <?php echo $submittedReports; ?>, <?php echo $rejectedReports; ?>],
            backgroundColor: ['#059669', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

<?php if (!empty($monthlyData)): ?>
// Monthly Trends Chart with Progressive Animation
const trendsCtx = document.getElementById('trendsChart').getContext('2d');
const monthlyDataLabels = [<?php echo "'" . implode("','", array_column($monthlyData, 'month')) . "'"; ?>];

// Animation configuration for progressive line drawing
const totalDuration = 2000;
const delayBetweenPoints = totalDuration / monthlyDataLabels.length;
const previousY = (ctx) => ctx.index === 0 ? ctx.chart.scales.y.getPixelForValue(100) : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;

const animation = {
    x: {
        type: 'number',
        easing: 'easeOutCubic',
        duration: delayBetweenPoints,
        from: NaN, // the point is initially skipped
        delay(ctx) {
            if (ctx.type !== 'data' || ctx.xStarted) {
                return 0;
            }
            ctx.xStarted = true;
            return ctx.index * delayBetweenPoints;
        }
    },
    y: {
        type: 'number',
        easing: 'easeOutCubic',
        duration: delayBetweenPoints,
        from: previousY,
        delay(ctx) {
            if (ctx.type !== 'data' || ctx.yStarted) {
                return 0;
            }
            ctx.yStarted = true;
            return ctx.index * delayBetweenPoints;
        }
    }
};

const trendsChart = new Chart(trendsCtx, {
    type: 'line',
    data: {
        labels: monthlyDataLabels,
        datasets: [{
            label: 'Total Submissions',
            data: [<?php echo implode(',', array_column($monthlyData, 'total_submissions')); ?>],
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4, // Smooth curves
            pointBackgroundColor: '#3B82F6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
        }, {
            label: 'Approved',
            data: [<?php echo implode(',', array_column($monthlyData, 'approved_count')); ?>],
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#059669',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
        }, {
            label: 'Rejected',
            data: [<?php echo implode(',', array_column($monthlyData, 'rejected_count')); ?>],
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#EF4444',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation,
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    title: function(context) {
                        return 'Month: ' + context[0].label;
                    },
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + ' reports';
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Month',
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                },
                grid: {
                    display: true,
                    color: 'rgba(0, 0, 0, 0.05)',
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                display: true,
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Reports',
                    color: '#6b7280',
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                },
                grid: {
                    display: true,
                    color: 'rgba(0, 0, 0, 0.05)',
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    },
                    stepSize: 1,
                    callback: function(value) {
                        return Math.floor(value) === value ? value : '';
                    }
                }
            }
        },
        elements: {
            line: {
                borderWidth: 3,
            },
            point: {
                hoverBackgroundColor: '#ffffff',
                hoverBorderWidth: 3,
            }
        }
    }
});

// Optional: Add a refresh animation function
function refreshChart() {
    trendsChart.reset();
    trendsChart.update();
}

// Optional: Add chart refresh button functionality
// You can call refreshChart() to replay the animation
<?php endif; ?>

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

<style>
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-submitted { background-color: #EBF8FF; color: #1e38afff; }
.status-under_review { background-color: #FEF3C7; color: #B45309; }
.status-approved { background-color: #D1FAE5; color: #047857; }
.status-rejected { background-color: #FEE2E2; color: #B91C1C; }
.status-revision_required { background-color: #EDE9FE; color: #6B21A8; }
</style>

<?php require_once '../includes/footer.php'; ?>