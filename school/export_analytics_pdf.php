<?php
/**
 * School Analytics PDF Export with Charts
 */

require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];

// Get filters from URL/POST
$dateFrom = $_GET['date_from'] ?? $_POST['date_from'] ?? date('Y-01-01');
$dateTo = $_GET['date_to'] ?? $_POST['date_to'] ?? date('Y-12-31');

$pageTitle = 'Export Analytics as PDF';
require_once '../includes/header.php';

// Fetch school info
global $db;
$school = $db->fetchOne("SELECT * FROM schools WHERE id = ?", [$schoolId]);

// Fetch monthly data for chart
$monthlyData = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
     FROM reports 
     WHERE school_id = ? AND submission_date BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month",
    [$schoolId, $dateFrom, $dateTo]
);
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
        <input type="hidden" name="monthly_chart" id="monthlyChartInput" value="">
    </form>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
const monthlyData = <?php echo json_encode($monthlyData); ?>;

document.addEventListener('DOMContentLoaded', function() {
    captureCharts();
});

function updateStatus(message) {
    document.getElementById('status').textContent = message;
    console.log(message);
}

async function captureCharts() {
    try {
        if (monthlyData && monthlyData.length > 0) {
            updateStatus('Creating Monthly Trends chart...');
            
            const monthlyCanvas = document.createElement('canvas');
            monthlyCanvas.id = 'exportMonthlyChart';
            monthlyCanvas.width = 1000;
            monthlyCanvas.height = 500;
            
            const chartsContainer = document.getElementById('chartsContainer');
            chartsContainer.appendChild(monthlyCanvas);
            
            const monthlyCtx = monthlyCanvas.getContext('2d');
            const monthLabels = monthlyData.map(d => {
                const [year, month] = d.month.split('-');
                const date = new Date(year, month - 1, 1);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
            });
            
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Total Submissions',
                        data: monthlyData.map(d => parseInt(d.total_submissions)),
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
                        data: monthlyData.map(d => parseInt(d.verified_count)),
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
                        data: monthlyData.map(d => parseInt(d.rejected_count)),
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
            
            // Wait for chart to render
            await sleep(1500);
            
            updateStatus('Capturing Monthly Trends chart...');
            const monthlyImage = monthlyCanvas.toDataURL('image/png');
            const monthlyInput = document.getElementById('monthlyChartInput');
            monthlyInput.value = monthlyImage;
            console.log('Monthly chart captured, length:', monthlyImage.length);
            
            if (!monthlyImage || monthlyImage.length < 100) {
                throw new Error('Monthly chart data is empty or invalid');
            }
        } else {
            updateStatus('No data available for chart');
        }
        
        updateStatus('Generating PDF download...');
        await sleep(500);
        
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
