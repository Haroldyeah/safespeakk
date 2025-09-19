<?php
$pageTitle = 'System Analytics';
require_once '../config/config.php';
requireRole('admin');

// Database connection is already initialized in config.php
global $db;

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-01-01');
$dateTo = $_GET['date_to'] ?? date('Y-12-31');
$schoolFilter = $_GET['school_id'] ?? 'all';

// Build WHERE clause for school filter
$schoolWhere = '';
$params = [$dateFrom, $dateTo];
if ($schoolFilter !== 'all') {
    $schoolWhere = ' AND school_id = ?';
    $params[] = $schoolFilter;
}

// Overall statistics
$totalReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$submittedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'submitted' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$underReviewReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_review' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$approvedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'approved' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$rejectedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'rejected' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];

// Monthly submission trends
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, COUNT(*) as total_submissions, SUM(CASE WHEN status = 'approved' AND deleted_at IS NULL THEN 1 ELSE 0 END) as approved_count, SUM(CASE WHEN status = 'rejected' AND deleted_at IS NULL THEN 1 ELSE 0 END) as rejected_count FROM reports WHERE submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere . " GROUP BY DATE_FORMAT(submission_date, '%Y-%m') ORDER BY month",
    $params
);

// Top performing schools
$topSchools = $db->fetchAll(
    "SELECT s.name, COUNT(CASE WHEN r.deleted_at IS NULL THEN r.id END) as total_reports, SUM(CASE WHEN r.status = 'approved' AND r.deleted_at IS NULL THEN 1 ELSE 0 END) as approved_reports FROM schools s LEFT JOIN reports r ON s.id = r.school_id AND r.submission_date BETWEEN ? AND ? GROUP BY s.id ORDER BY total_reports DESC LIMIT 10",
    [$dateFrom, $dateTo]
);

// Recent activity
$recentActivity = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, s.name as school_name FROM reports r JOIN users u ON r.student_id = u.id JOIN schools s ON r.school_id = s.id WHERE r.submission_date BETWEEN ? AND ? AND r.deleted_at IS NULL" . $schoolWhere . " ORDER BY r.submission_date DESC LIMIT 15",
    $params
);

// Calculate percentages
$approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

// Get active students count
$activeStudents = $db->fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

// Get all schools for the filter
$schools = $db->fetchAll("SELECT id, name FROM schools ORDER BY name");

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-chart-line text-primary me-2"></i>System Analytics</h1>
        <p class="text-muted mb-0">Overall statistics and analysis of the capstone reporting system.</p>
    </div>
    <div class="col-auto">
        <div class="btn-group">
            <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print me-1"></i>Print Report</button>
            <a href="download_analytics.php" class="btn btn-success"><i class="fas fa-download me-1"></i>Download PDF</a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">School</label>
                <select class="form-select" name="school_id">
                    <option value="all">All Schools</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" <?php echo $schoolFilter == $school['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($school['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12">
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
                    <h5 class="mb-0">Report Status Distribution</h5>
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

    <!-- Top Performing Schools -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top Performing Schools</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topSchools)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>School Name</th>
                                        <th>Reports Submitted</th>
                                        <th>Approved Reports</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topSchools as $school): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($school['name']); ?></strong></td>
                                            <td><span class="badge bg-primary"><?php echo $school['total_reports']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $school['approved_reports']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-school fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No school data available for this period</p>
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
                                <th>School</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $report): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($report['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['school_name']); ?></td>
                                    <td><span class="status-badge status-<?php echo $report['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?></span></td>
                                    <td><small><?php echo formatDate($report['submission_date']); ?></small></td>
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
</script>

<?php require_once '../includes/footer.php'; ?>