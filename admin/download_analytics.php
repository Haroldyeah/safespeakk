<?php
require_once '../config/config.php';
requireRole('admin');

// Database connection is already initialized in config.php
global $db;

// Get filters from POST
$dateFrom = $_POST['date_from'] ?? date('Y-01-01');
$dateTo = $_POST['date_to'] ?? date('Y-12-31');
$schoolFilter = $_POST['school_id'] ?? 'all';

// Build WHERE clause for school filter
$schoolWhere = '';
$params = [$dateFrom, $dateTo];
if ($schoolFilter !== 'all') {
    $schoolWhere = ' AND school_id = ?';
    $params[] = $schoolFilter;
}

// Get statistics
$totalReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$approvedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'approved' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$rejectedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'rejected' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$underReviewReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_review' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$submittedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'submitted' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

// Calculate rates
$approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

// Get active students
$activeStudents = $db->fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

// Top performing schools
$topSchools = $db->fetchAll(
    "SELECT s.name, COUNT(r.id) as total_reports, SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reports FROM schools s LEFT JOIN reports r ON s.id = r.school_id AND r.submission_date BETWEEN ? AND ? GROUP BY s.id ORDER BY total_reports DESC LIMIT 10",
    [$dateFrom, $dateTo]
);

// Monthly data
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, COUNT(*) as total_submissions, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere . " GROUP BY DATE_FORMAT(submission_date, '%Y-%m') ORDER BY month",
    $params
);

// Recent reports for inclusion in exported analytics
$recentReports = $db->fetchAll(
    "SELECT r.title, r.bully_name, u.first_name, u.last_name, s.name as school_name, r.status, r.submission_date
     FROM reports r
     JOIN users u ON r.student_id = u.id
     JOIN schools s ON r.school_id = s.id
     WHERE r.submission_date BETWEEN ? AND ? AND r.deleted_at IS NULL
     ORDER BY r.submission_date DESC
     LIMIT 25",
    $params
);

// Build HTML output into variable so we can optionally render to PDF via Dompdf
$html = '';
$html .= '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>System Analytics Report</title>';
$html .= '<style>body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; } .header { text-align: center; border-bottom: 2px solid #1E3A8A; padding-bottom: 20px; margin-bottom: 30px; } .report-title { color: #1E3A8A; font-size: 24px; font-weight: bold; } .report-subtitle { color: #666; font-size: 18px; margin: 10px 0; } .report-period { color: #888; font-size: 14px; } .statistics { display: flex; flex-wrap: wrap; gap: 20px; margin: 30px 0; } .stat-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; flex: 1; min-width: 150px; } .stat-number { font-size: 24px; font-weight: bold; color: #1E3A8A; } .stat-label { color: #666; font-size: 12px; } .section { margin: 30px 0; } .section-title { color: #1E3A8A; font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; } table { width: 100%; border-collapse: collapse; margin: 15px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f8f9fa; font-weight: bold; } .status-approved { background-color: #d1fae5; color: #047857; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-rejected { background-color: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-under_review { background-color: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-submitted { background-color: #ebf8ff; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }</style>';
$html .= '</head><body>';
$html .= '<div class="header"><div class="report-title">System-Wide Analytics Report</div><div class="report-subtitle">Capstone Report Management System</div><div class="report-period">Report Period: ' . date('F j, Y', strtotime($dateFrom)) . ' - ' . date('F j, Y', strtotime($dateTo)) . '<br>Generated on: ' . date('F j, Y g:i A') . '</div></div>';

$html .= '<div class="section"><div class="section-title">Summary Statistics</div><div class="statistics">';
$html .= '<div class="stat-card"><div class="stat-number">' . $totalReports . '</div><div class="stat-label">Total Reports</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #059669;">' . $approvedReports . '</div><div class="stat-label">Approved Reports</div><div style="color: #059669; font-size: 12px;">' . $approvalRate . '%</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #F59E0B;">' . $underReviewReports . '</div><div class="stat-label">Under Review</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #3B82F6;">' . $submittedReports . '</div><div class="stat-label">Submitted</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #EF4444;">' . $rejectedReports . '</div><div class="stat-label">Rejected</div><div style="color: #EF4444; font-size: 12px;">' . $rejectionRate . '%</div></div>';
$html .= '<div class="stat-card"><div class="stat-number">' . $activeStudents . '</div><div class="stat-label">Active Students</div></div>';
$html .= '</div></div>';

if (!empty($monthlyData)) {
    $html .= '<div class="section"><div class="section-title">Monthly Submission Trends</div><table><thead><tr><th>Month</th><th>Total Submissions</th><th>Approved</th><th>Rejected</th><th>Approval Rate</th></tr></thead><tbody>';
    foreach ($monthlyData as $month) {
        $monthlyApprovalRate = $month['total_submissions'] > 0 ? round(($month['approved_count'] / $month['total_submissions']) * 100, 1) : 0;
        $html .= '<tr><td>' . date('F Y', strtotime($month['month'] . '-01')) . '</td><td>' . $month['total_submissions'] . '</td><td>' . $month['approved_count'] . '</td><td>' . $month['rejected_count'] . '</td><td>' . $monthlyApprovalRate . '%</td></tr>';
    }
    $html .= '</tbody></table></div>';
}

if (!empty($topSchools)) {
    $html .= '<div class="section"><div class="section-title">Top Performing Schools</div><table><thead><tr><th>School Name</th><th>Total Reports</th><th>Approved Reports</th><th>Success Rate</th></tr></thead><tbody>';
    foreach ($topSchools as $school) {
        $successRate = $school['total_reports'] > 0 ? round(($school['approved_reports'] / $school['total_reports']) * 100, 1) : 0;
        $html .= '<tr><td>' . htmlspecialchars($school['name']) . '</td><td>' . $school['total_reports'] . '</td><td>' . $school['approved_reports'] . '</td><td>' . $successRate . '%</td></tr>';
    }
    $html .= '</tbody></table></div>';
}

if (!empty($recentReports)) {
    $html .= '<div class="section"><div class="section-title">Recent Reports</div><table><thead><tr><th>Report Title</th><th>Involved</th><th>Student</th><th>School</th><th>Status</th><th>Submission Date</th></tr></thead><tbody>';
    foreach ($recentReports as $r) {
        $html .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars($r['bully_name'] ?? '—') . '</td><td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td><td>' . htmlspecialchars($r['school_name']) . '</td><td>' . htmlspecialchars(ucfirst(str_replace('_',' ',$r['status']))) . '</td><td>' . formatDate($r['submission_date']) . '</td></tr>';
    }
    $html .= '</tbody></table></div>';
}

$html .= '<div class="footer"><p>This report was generated by the Capstone Report Management System</p><p>Report contains data from ' . date('F j, Y', strtotime($dateFrom)) . ' to ' . date('F j, Y', strtotime($dateTo)) . '</p><p>© ' . date('Y') . ' Capstone Management System. All rights reserved.</p></div>';

$html .= '</body></html>';

// Try to render a PDF using Dompdf if available
$pdfFilename = 'System_Analytics_Report_' . date('Y-m-d') . '.pdf';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dompdf\\Dompdf')) {
        try {
            $dompdf = new Dompdf\\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream($pdfFilename, ['Attachment' => 1]);
            exit;
        } catch (Exception $e) {
            error_log('PDF generation error (admin): ' . $e->getMessage());
        }
    }
}

// Fallback: return HTML download and include a note about PDF support
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="System_Analytics_Report_' . date('Y-m-d') . '.html"');
echo $html;
echo '<!-- NOTE: To enable direct PDF exports, install Dompdf via Composer: composer require dompdf/dompdf and ensure vendor/autoload.php is present. -->';