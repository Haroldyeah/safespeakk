<?php
/**
 * Analytics PDF Export with Charts
 * 
 * This page:
 * 1. Loads the analytics view with special export mode
 * 2. Captures Chart.js graphs as images
 * 3. Submits them to download_analytics.php with chart data embedded
 */

require_once '../config/config.php';
requireRole('admin');

// Get filters from URL/POST
$dateFrom = $_GET['date_from'] ?? $_POST['date_from'] ?? date('Y-01-01');
$dateTo = $_GET['date_to'] ?? $_POST['date_to'] ?? date('Y-12-31');
$schoolFilter = $_GET['school_id'] ?? $_POST['school_id'] ?? 'all';

// Fetch analytics data needed for charts
global $db;
$schoolWhere = '';
$params = [$dateFrom, $dateTo];
if ($schoolFilter !== 'all') {
    $schoolWhere = ' AND school_id = ?';
    $params[] = $schoolFilter;
}

// Fetch all needed statistics
$totalReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$submittedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'submitted' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$underInvestigationReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_investigation' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$referredToMswdReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'referred_to_mswd' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$verifiedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'verified' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$rejectedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'rejected' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

// Fetch monthly data
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'submitted' AND deleted_at IS NULL THEN 1 ELSE 0 END) as submitted_count,
        SUM(CASE WHEN status = 'under_investigation' AND deleted_at IS NULL THEN 1 ELSE 0 END) as under_investigation_count,
        SUM(CASE WHEN status = 'referred_to_mswd' AND deleted_at IS NULL THEN 1 ELSE 0 END) as referred_to_mswd_count,
        SUM(CASE WHEN status = 'verified' AND deleted_at IS NULL THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN status = 'rejected' AND deleted_at IS NULL THEN 1 ELSE 0 END) as rejected_count
     FROM reports
     WHERE submission_date BETWEEN ? AND ? AND deleted_at IS NULL" . $schoolWhere . " 
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m') 
     ORDER BY month",
    $params
);

$pageTitle = 'Export Analytics as PDF';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="mb-4">Exporting Analytics Report...</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-spinner fa-spin me-2"></i>
        <span id="status">Generating report with charts...</span>
    </div>
    
    <!-- Hidden container for analytics charts -->
    <div id="chartsContainer" style="display: none; position: fixed; top: -10000px; left: 0;"></div>
    
    <!-- Hidden form to submit to download_analytics.php -->
    <form id="downloadForm" method="POST" action="download_analytics.php" style="display: none;">
        <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
        <input type="hidden" name="school_id" value="<?php echo htmlspecialchars($schoolFilter); ?>">
        <input type="hidden" name="status_chart" id="statusChartInput" value="">
        <input type="hidden" name="trends_chart" id="trendsChartInput" value="">
    </form>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
const analyticsData = {
    submitted: <?php echo $submittedReports; ?>,
    underInvestigation: <?php echo $underInvestigationReports; ?>,
    referredToMswd: <?php echo $referredToMswdReports; ?>,
    verified: <?php echo $verifiedReports; ?>,
    rejected: <?php echo $rejectedReports; ?>,
    total: <?php echo $totalReports; ?>,
    monthlyData: <?php echo json_encode($monthlyData); ?>
};

document.addEventListener('DOMContentLoaded', function() {
    captureCharts();
});

function updateStatus(message) {
    document.getElementById('status').textContent = message;
    console.log(message);
}

async function captureCharts() {
    try {
        updateStatus('Creating Status Distribution chart...');
        
        // Create Status Distribution Chart
        const statusCanvas = document.createElement('canvas');
        statusCanvas.id = 'exportStatusChart';
        statusCanvas.width = 1000;
        statusCanvas.height = 500;
        
        const chartsContainer = document.getElementById('chartsContainer');
        chartsContainer.appendChild(statusCanvas);
        
        const statusCtx = statusCanvas.getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Submitted', 'Under Investigation', 'Referred to MSWD', 'Verified', 'Rejected'],
                datasets: [{
                    data: [
                        analyticsData.submitted,
                        analyticsData.underInvestigation,
                        analyticsData.referredToMswd,
                        analyticsData.verified,
                        analyticsData.rejected
                    ],
                    backgroundColor: ['#3B82F6', '#8B5CF6', '#1F2937', '#10B981', '#EF4444']
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 14 }
                        }
                    }
                }
            }
        });
        
        // Wait for chart to render
        await sleep(1500);
        
        updateStatus('Capturing Status Distribution chart...');
        const statusImage = statusCanvas.toDataURL('image/png');
        const statusInput = document.getElementById('statusChartInput');
        statusInput.value = statusImage;
        console.log('Status chart captured, length:', statusImage.length);
        
        // Create Monthly Trends Chart if data exists
        if (analyticsData.monthlyData && analyticsData.monthlyData.length > 0) {
            updateStatus('Creating Monthly Trends chart...');
            
            const trendsCanvas = document.createElement('canvas');
            trendsCanvas.id = 'exportTrendsChart';
            trendsCanvas.width = 1000;
            trendsCanvas.height = 500;
            chartsContainer.appendChild(trendsCanvas);
            
            const trendsCtx = trendsCanvas.getContext('2d');
            const monthLabels = analyticsData.monthlyData.map(d => {
                const [year, month] = d.month.split('-');
                const date = new Date(year, month - 1, 1);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
            });
            
            const trendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Total Submissions',
                        data: analyticsData.monthlyData.map(d => parseInt(d.total_submissions)),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        borderWidth: 3
                    }, {
                        label: 'Verified',
                        data: analyticsData.monthlyData.map(d => parseInt(d.verified_count)),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        borderWidth: 2
                    }, {
                        label: 'Rejected',
                        data: analyticsData.monthlyData.map(d => parseInt(d.rejected_count)),
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#EF4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 14 }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Math.floor(value) === value ? value : '';
                                }
                            }
                        }
                    }
                }
            });
            
            // Wait for trends chart to render
            await sleep(1500);
            
            updateStatus('Capturing Monthly Trends chart...');
            const trendsImage = trendsCanvas.toDataURL('image/png');
            const trendsInput = document.getElementById('trendsChartInput');
            trendsInput.value = trendsImage;
            console.log('Trends chart captured, length:', trendsImage.length);
        }
        
        updateStatus('Generating PDF download...');
        await sleep(500);
        
        // Verify data before submitting
        const statusData = document.getElementById('statusChartInput').value;
        const trendsData = document.getElementById('trendsChartInput').value;
        
        console.log('Before submit - Status chart:', statusData.substring(0, 50));
        console.log('Before submit - Trends chart:', trendsData.substring(0, 50));
        
        if (!statusData || statusData.length < 100) {
            throw new Error('Status chart data is empty or invalid');
        }
        
        document.getElementById('downloadForm').submit();
        
    } catch (error) {
        console.error('Chart capture error:', error);
        updateStatus('❌ Error: ' + error.message);
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>

<?php require_once '../includes/footer.php'; ?>
