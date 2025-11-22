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
$underInvestigationReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_investigation' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$referredToMswd = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'referred_to_mswd' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$verifiedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'verified' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];
$rejectedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'rejected' AND submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere, $params)['count'];

// Define old variables for compatibility and calculate rates
$approvedReports = $verifiedReports; // 'verified' is the new 'approved'
$closedReports = $verifiedReports + $rejectedReports; // 'closed' are reports that are finalized

$approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;
$closureRate = $totalReports > 0 ? round(($closedReports / $totalReports) * 100, 1) : 0;

// These statuses no longer exist, initialize to 0 to avoid errors
$underReviewReports = 0;
$revisionRequiredReports = 0;

// Monthly submission trends with all statuses
// Monthly data aggregated over canonical statuses
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'submitted' AND deleted_at IS NULL THEN 1 ELSE 0 END) as submitted_count,
        SUM(CASE WHEN status = 'under_investigation' AND deleted_at IS NULL THEN 1 ELSE 0 END) as under_investigation_count,
        SUM(CASE WHEN status = 'referred_to_mswd' AND deleted_at IS NULL THEN 1 ELSE 0 END) as referred_to_mswd_count,
        SUM(CASE WHEN status = 'verified' AND deleted_at IS NULL THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN status = 'rejected' AND deleted_at IS NULL THEN 1 ELSE 0 END) as rejected_count
     FROM reports
     WHERE submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere . " GROUP BY DATE_FORMAT(submission_date, '%Y-%m') ORDER BY month",
    $params
);

// Top performing schools
$topSchools = $db->fetchAll(
    "SELECT s.name, COUNT(CASE WHEN r.deleted_at IS NULL THEN r.id END) as total_reports, SUM(CASE WHEN r.status = 'verified' AND r.deleted_at IS NULL THEN 1 ELSE 0 END) as verified_reports FROM schools s LEFT JOIN reports r ON s.id = r.school_id AND r.submission_date BETWEEN ? AND ? GROUP BY s.id ORDER BY total_reports DESC LIMIT 10",
    [$dateFrom, $dateTo]
);

// Recent activity
$recentActivity = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, s.name as school_name FROM reports r JOIN users u ON r.student_id = u.id JOIN schools s ON r.school_id = s.id WHERE r.submission_date BETWEEN ? AND ? AND r.deleted_at IS NULL" . $schoolWhere . " ORDER BY r.submission_date DESC LIMIT 15",
    $params
);

// Calculate percentages
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
                    <h3 class="text-info"><?php echo $underInvestigationReports; ?></h3>
                    <p class="mb-0 text-muted small">Under Investigation</p>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $verifiedReports; ?></h3>
                    <p class="mb-0 text-muted small">Verified</p>
                    <small class="text-success"><?php echo $approvalRate; ?>%</small>
                </div>
            </div>
        </div>
        <div class="col-md-1-5">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-danger"><?php echo $rejectedReports; ?></h3>
                    <p class="mb-0 text-muted small">Rejected</p>
                    <small class="text-danger"><?php echo $rejectionRate; ?>%</small>
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
                            <div class="progress-bar bg-dark" style="width: <?php echo $totalReports > 0 ? ($referredToMswd / $totalReports) * 100 : 0; ?>%"></div>
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
                            <small class="fw-bold">Verified</small>
                            <small class="text-muted"><?php echo $verifiedReports; ?></small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" style="width: <?php echo $totalReports > 0 ? ($verifiedReports / $totalReports) * 100 : 0; ?>%"></div>
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
                                        <th>Verified Reports</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topSchools as $school): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($school['name']); ?></strong></td>
                                            <td><span class="badge bg-primary"><?php echo $school['total_reports']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $school['verified_reports']; ?></span></td>
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
                                <th>Involved</th>
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
                                    <td><?php echo htmlspecialchars($report['bully_name'] ?? '—'); ?></td>
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
// Status Distribution Chart (All 9 Statuses)
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
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#3B82F6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 3
        }, {
            label: 'Submitted',
            data: [<?php echo implode(',', array_column($monthlyData, 'submitted_count')); ?>],
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#3B82F6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2
        }, {
            label: 'Verified',
            data: [<?php echo implode(',', array_column($monthlyData, 'verified_count')); ?>],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10B981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2
        }, {
            label: 'Under Investigation',
            data: [<?php echo implode(',', array_column($monthlyData, 'under_investigation_count')); ?>],
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#8B5CF6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2
        }, {
            label: 'Referred to MSWD',
            data: [<?php echo implode(',', array_column($monthlyData, 'referred_to_mswd_count')); ?>],
            borderColor: '#1F2937',
            backgroundColor: 'rgba(31, 41, 55, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1F2937',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2
        }, {
            label: 'Rejected',
            data: [<?php echo implode(',', array_column($monthlyData, 'rejected_count')); ?>],
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.05)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#EF4444',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2
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