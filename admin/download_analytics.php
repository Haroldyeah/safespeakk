<?php
require_once '../config/config.php';
requireRole('admin');

    use Dompdf\Dompdf;
    use Dompdf\Options;

// Database connection is already initialized in config.php
global $db;


// Filters
$dateFrom = $_POST['date_from'] ?? date('Y-01-01');
$dateTo = $_POST['date_to'] ?? date('Y-12-31');
$schoolFilter = $_POST['school_id'] ?? 'all';

// Build WHERE clause
$schoolWhere = '';
$params = [$dateFrom, $dateTo];

if ($schoolFilter !== 'all') {
    $schoolWhere = ' AND school_id = ?';
    $params[] = $schoolFilter;
}

// Fetch analytics data
$totalReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$verifiedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'verified' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$rejectedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'rejected' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$referredToMswdReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'referred_to_mswd' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$underInvestigationReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'under_investigation' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];
$submittedReports = $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'submitted' AND submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

$approvalRate = $totalReports > 0 ? round(($verifiedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

$activeStudents = $db->fetchOne("SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE submission_date BETWEEN ? AND ?" . $schoolWhere, $params)['count'];

$topSchools = $db->fetchAll(
    "SELECT s.name, COUNT(r.id) as total_reports, 
    SUM(CASE WHEN r.status = 'verified' THEN 1 ELSE 0 END) as verified_reports
     FROM schools s 
     LEFT JOIN reports r 
     ON s.id = r.school_id 
     AND r.submission_date BETWEEN ? AND ? 
     GROUP BY s.id 
     ORDER BY total_reports DESC LIMIT 10",
    [$dateFrom, $dateTo]
);

$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, 
    COUNT(*) as total_submissions, 
    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count, 
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    SUM(CASE WHEN status = 'referred_to_mswd' THEN 1 ELSE 0 END) as referred_to_mswd_count,
    SUM(CASE WHEN status = 'under_investigation' THEN 1 ELSE 0 END) as under_investigation_count
     FROM reports 
     WHERE submission_date BETWEEN ? AND ?" . $schoolWhere . "
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m') 
     ORDER BY month",
    $params
);

$recentReports = $db->fetchAll(
    "SELECT r.title, r.bully_name, u.first_name, u.last_name, s.name as school_name, r.status, r.submission_date
     FROM reports r
     JOIN users u ON r.student_id = u.id
     JOIN schools s ON r.school_id = s.id
     WHERE r.submission_date BETWEEN ? AND ? 
     AND r.deleted_at IS NULL
     ORDER BY r.submission_date DESC
     LIMIT 25",
    $params
);

// BUILD HTML
$html = '';
$html .= '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>System Analytics Report</title>';
$html .= '<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.header { text-align: center; border-bottom: 2px solid #1E3A8A; padding-bottom: 20px; margin-bottom: 30px; }
.report-title { color: #1E3A8A; font-size: 24px; font-weight: bold; }
.report-subtitle { color: #666; font-size: 18px; margin: 10px 0; }
.report-period { color: #888; font-size: 14px; }
.statistics { display: flex; flex-wrap: wrap; gap: 20px; margin: 30px 0; }
.stat-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; flex: 1; min-width: 150px; }
.stat-number { font-size: 24px; font-weight: bold; color: #1E3A8A; }
.stat-label { color: #666; font-size: 12px; }
.section { margin: 30px 0; }
.section-title { color: #1E3A8A; font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f8f9fa; font-weight: bold; }
.footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
</style>';
$html .= '</head><body>';

$html .= '<div class="header">
<div class="report-title">System-Wide Analytics Report</div>
<div class="report-subtitle">Capstone Report Management System</div>
<div class="report-period">Report Period: ' . date('F j, Y', strtotime($dateFrom)) . ' - ' . date('F j, Y', strtotime($dateTo)) . '<br>Generated on: ' . date('F j, Y g:i A') . '</div></div>';

$html .= '<div class="section"><div class="section-title">Summary Statistics</div><div class="statistics">';
$html .= '<div class="stat-card"><div class="stat-number">' . $totalReports . '</div><div class="stat-label">Total Reports</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #059669;">' . $verifiedReports . '</div><div class="stat-label">Verified Reports</div><div style="color: #059669; font-size: 12px;">' . $approvalRate . '%</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #970bf5ff;">' . $underInvestigationReports . '</div><div class="stat-label">Under Inventgation</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #000000ff;">' . $referredToMswdReports . '</div><div class="stat-label">Referred to MSWD</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #447acfff;">' . $submittedReports . '</div><div class="stat-label">Submitted</div></div>';
$html .= '<div class="stat-card"><div class="stat-number" style="color: #EF4444;">' . $rejectedReports . '</div><div class="stat-label">Rejected</div><div style="color: #EF4444; font-size: 12px;">' . $rejectionRate . '%</div></div>';
$html .= '<div class="stat-card"><div class="stat-number">' . $activeStudents . '</div><div class="stat-label">Active Students</div></div>';
$html .= '</div></div>';
// Ensure months with zero data (e.g., October) are shown
if (!empty($monthlyData)) {
    $monthlyMap = [];
    foreach ($monthlyData as $m) {
        $monthlyMap[$m['month']] = $m;
    }

    $html .= '<div class="section"><div class="section-title">Monthly Submission Trends</div><table><thead><tr><th>Month</th><th>Total</th><th>Verified</th><th>Rejected</th><th>Referred to MSWD</th><th>Under Investigation</th><th>Rate</th></tr></thead><tbody>';

    $start = new DateTime(date('Y-m-01', strtotime($dateFrom)));
    $end = new DateTime(date('Y-m-01', strtotime($dateTo)));
    // iterate inclusive months
    for ($period = clone $start; $period <= $end; $period->modify('+1 month')) {
        $key = $period->format('Y-m');
        $m = $monthlyMap[$key] ?? [
            'total_submissions' => 0,
            'verified_count' => 0,
            'rejected_count' => 0,
            'referred_to_mswd_count' => 0,
            'under_investigation_count' => 0
        ];

        $total = (int)$m['total_submissions'];
        $verified = (int)$m['verified_count'];
        $rejected = (int)$m['rejected_count'];
        $referred = (int)$m['referred_to_mswd_count'];
        $under = (int)$m['under_investigation_count'];

        $rate = $total > 0 ? round(($verified / $total) * 100, 1) : 0;
        $html .= '<tr><td>' . $period->format('F Y') . '</td><td>' . $total . '</td><td>' . $verified . '</td><td>' . $rejected . '</td><td>' . $referred . '</td><td>' . $under . '</td><td>' . $rate . '%</td></tr>';
    }

    $html .= '</tbody></table></div>';
}

if (!empty($topSchools)) {
    $html .= '<div class="section"><div class="section-title">Top Performing Schools</div><table><thead><tr>
        <th>School Name</th>
        <th>Total</th>
        <th>Verified</th>
        <th>Rejected</th>
        <th>Referred to MSWD</th>
        <th>Under Investigation</th>
        <th>Success Rate</th>
    </tr></thead><tbody>';
    foreach ($topSchools as $s) {
        $total = (int)($s['total_reports'] ?? $s['total_submissions'] ?? 0);
        $verified = (int)($s['verified_reports'] ?? $s['verified_count'] ?? 0);
        $rejectedReports = (int)($s['rejected_reports'] ?? $s['rejected_count'] ?? 0);
        $underInvestigationReports = (int)($s['under_investigation_reports'] ?? $s['under_investigation_count'] ?? 0);
        $referredReports = (int)($s['referred_to_mswd_reports'] ?? $s['referred_to_mswd_count'] ?? 0);
        $success = $total > 0 ? round(($verified / $total) * 100, 1) : 0;

        $html .= '<tr>
            <td>' . htmlspecialchars($s['name'] ?? '—') . '</td>
            <td>' . $total . '</td>
            <td>' . $verified . '</td>
            <td>' . $rejectedReports . '</td>
            <td>' . $referredReports . '</td>
            <td>' . $underInvestigationReports . '</td>
            <td>' . $success . '%</td>
        </tr>';
    }
    $html .= '</tbody></table></div>';
}

if (!empty($recentReports)) {
    $html .= '<div class="section"><div class="section-title">Recent Reports</div><table><thead><tr><th>Title</th><th>Involved</th><th>Student</th><th>School</th><th>Status</th><th>Submitted</th></tr></thead><tbody>';
    foreach ($recentReports as $r) {
        $html .= '<tr>
            <td>' . htmlspecialchars($r['title']) . '</td>
            <td>' . htmlspecialchars($r['bully_name'] ?? '—') . '</td>
            <td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>
            <td>' . htmlspecialchars($r['school_name']) . '</td>
            <td>' . htmlspecialchars(ucfirst(str_replace("_", " ", $r['status']))) . '</td>
            <td>' . formatDate($r['submission_date']) . '</td>
        </tr>';
    }
    $html .= '</tbody></table></div>';
}

$html .= '<div class="footer"><p>This report was generated by the Capstone Management System</p><p>© ' . date('Y') . ' All rights reserved.</p></div>';
$html .= '</body></html>';

// ===============================
//         DOMPDF EXPORT FIXED
// ===============================
$pdfFilename = 'System_Analytics_Report_' . date('Y-m-d') . '.pdf';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {

    require_once __DIR__ . '/../vendor/autoload.php';


    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream($pdfFilename, ['Attachment' => true]);
    exit;
}

// Fallback download as HTML if DomPDF not installed
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="System_Analytics_Report_' . date('Y-m-d') . '.html"');
echo $html;
exit;
?>
