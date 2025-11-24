<?php
ob_start();
require_once '../config/config.php';
requireRole('school');

// Database connection already initialized
global $db;

// Enable DomPDF
use Dompdf\Dompdf;
use Dompdf\Options;

$schoolId = $_SESSION['school_id'];

// Check if we're receiving captured chart via POST
$monthlyChartBase64 = $_POST['monthly_chart'] ?? null;

// Filters
$dateFrom = $_POST['date_from'] ?? $_GET['date_from'] ?? date('Y-01-01');
$dateTo = $_POST['date_to'] ?? $_GET['date_to'] ?? date('Y-12-31');
$academicYear = $_POST['academic_year'] ?? $_GET['academic_year'] ?? date('Y');

// ==============================
//  SCHOOL INFORMATION
// ==============================

$school = $db->fetchOne(
    "SELECT * FROM schools WHERE id = ?",
    [$schoolId]
);

// ==============================
//  SUMMARY STATISTICS
// ==============================

$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ?",
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
$referredtoMswdReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'referred_to_mswd' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];
$underInvestigationReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'under_investigation' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$submittedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'submitted' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

// Rates
$approvalRate = $totalReports > 0 ? round(($verifiedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

// Active students
$activeStudents = $db->fetchOne(
    "SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

// ==============================
//  TOP STUDENTS
// ==============================

$topStudents = $db->fetchAll(
    "SELECT 
        u.first_name, u.last_name, u.student_id,
        COUNT(r.id) as total_reports,
        SUM(CASE WHEN r.status = 'verified' THEN 1 ELSE 0 END) as verified_reports,
        AVG(CASE WHEN r.grade IS NOT NULL AND r.grade != '' THEN CAST(r.grade as DECIMAL(4,2)) ELSE NULL END) as avg_grade
     FROM users u
     LEFT JOIN reports r ON u.id = r.student_id AND r.submission_date BETWEEN ? AND ?
     WHERE u.school_id = ? AND u.role = 'student'
     GROUP BY u.id
     HAVING total_reports > 0
     ORDER BY verified_reports DESC, avg_grade DESC
     LIMIT 10",
    [$dateFrom, $dateTo, $schoolId]
);

// ==============================
//  MONTHLY DATA
// ==============================

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

// ==============================
//  RECENT REPORTS
// ==============================

$recentReports = $db->fetchAll(
    "SELECT r.id, r.title, r.bully_name, r.severity, r.recommended_actions, 
        u.first_name, u.last_name, 
        r.date_of_incident, r.submission_date, r.status,
        (SELECT COUNT(*) FROM report_evidence re WHERE re.report_id = r.id) as evidence_count
     FROM reports r
     JOIN users u ON r.student_id = u.id
     WHERE r.school_id = ? 
       AND r.submission_date BETWEEN ? AND ? 
       AND r.status IN ('submitted', 'verified', 'rejected', 'referred_to_mswd', 'under_investigation')
       AND r.deleted_at IS NULL
     ORDER BY r.submission_date DESC
     LIMIT 50",
    [$schoolId, $dateFrom, $dateTo]
);

// ==============================
//       BUILD HTML
// ==============================

$html = '';
$html .= '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($school['name']) . ' - Analytics Report</title>';

$html .= '<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.header { text-align: center; border-bottom: 2px solid #1E3A8A; padding-bottom: 20px; margin-bottom: 30px; }
.school-name { color: #1E3A8A; font-size: 24px; font-weight: bold; }
.report-title { color: #666; font-size: 18px; margin: 10px 0; }
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
.status-verified { background-color: #d1fae5; color: #047857; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.status-rejected { background-color: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.status-under_investigation { background-color: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.status-submitted { background-color: #ebf8ff; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
</style>';

$html .= '</head><body>';

$html .= '<div class="header">
            <div class="school-name">' . htmlspecialchars($school['name']) . '</div>
            <div class="report-title">Capstone Reports Analytics</div>
            <div class="report-period">
                Report Period: ' . date('F j, Y', strtotime($dateFrom)) . ' - ' . date('F j, Y', strtotime($dateTo)) . '<br>
                Generated on: ' . date('F j, Y g:i A') . '
            </div>
          </div>';

// ==============================
// Summary Statistics
// ==============================
$html .= '<div class="section">
            <div class="section-title">Summary Statistics</div>
            <div class="statistics">';

$html .= '<div class="stat-card"><div class="stat-number">' . $totalReports . '</div><div class="stat-label">Total Reports</div></div>';

$html .= '<div class="stat-card"><div class="stat-number" style="color:#059669;">' . $verifiedReports . '</div>
          <div class="stat-label">Verified</div><div style="font-size:12px;color:#059669;">' . $approvalRate . '%</div></div>';

$html .= '<div class="stat-card"><div class="stat-number" style="color:#F59E0B;">' . $underInvestigationReports . '</div><div class="stat-label">Under Investigation</div></div>';

$html .= '<div class="stat-card"><div class="stat-number" style="color:#3B82F6;">' . $submittedReports . '</div><div class="stat-label">Submitted</div></div>';

$html .= '<div class="stat-card"><div class="stat-number" style="color:#EF4444;">' . $rejectedReports . '</div>
          <div class="stat-label">Rejected</div><div style="font-size:12px;color:#EF4444;">' . $rejectionRate . '%</div></div>';

$html .= '<div class="stat-card"><div class="stat-number">' . $activeStudents . '</div><div class="stat-label">Active Students</div></div>';

$html .= '</div></div>';

// Add Monthly Chart if captured and valid
if ($monthlyChartBase64 && strpos($monthlyChartBase64, 'data:image') === 0 && strlen($monthlyChartBase64) > 100) {
    $html .= '<div class="section"><div class="section-title">Monthly Submission Trends - Chart</div>';
    $html .= '<img src="' . htmlspecialchars($monthlyChartBase64) . '" style="width: 100%; max-height: 400px; object-fit: contain;" />';
    $html .= '</div>';
}

// ==============================
// Monthly Trends
// ==============================

if (!empty($monthlyData)) {
    $html .= '<div class="section"><div class="section-title">Monthly Submission Trends</div>
              <table><thead><tr>
              <th>Month</th><th>Total</th><th>Verified</th><th>Rejected</th><th>Approval Rate</th>
              </tr></thead><tbody>';

    foreach ($monthlyData as $m) {
        $rate = $m['total_submissions'] > 0 ? round($m['verified_count'] / $m['total_submissions'] * 100, 1) : 0;
        $html .= '<tr>
                    <td>' . date('F Y', strtotime($m['month'] . '-01')) . '</td>
                    <td>' . $m['total_submissions'] . '</td>
                    <td>' . $m['verified_count'] . '</td>
                    <td>' . $m['rejected_count'] . '</td>
                    <td>' . $rate . '%</td>
                  </tr>';
    }

    $html .= '</tbody></table></div>';
}

// ==============================
// Top Students
// ==============================

if (!empty($topStudents)) {
    $html .= '<div class="section"><div class="section-title">Top Performing Students</div>
                <table><thead><tr>
                  <th>Name</th><th>ID</th><th>Total</th><th>Verified</th><th>Average Grade</th><th>Success %</th>
                </tr></thead><tbody>';

    foreach ($topStudents as $s) {
        $success = $s['total_reports'] > 0 ? round($s['verified_reports'] / $s['total_reports'] * 100, 1) : 0;

        $html .= '<tr>
                    <td>' . htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) . '</td>
                    <td>' . htmlspecialchars($s['student_id']) . '</td>
                    <td>' . $s['total_reports'] . '</td>
                    <td>' . $s['verified_reports'] . '</td>
                    <td>' . ($s['avg_grade'] ? number_format($s['avg_grade'], 1) : 'N/A') . '</td>
                    <td>' . $success . '%</td>
                  </tr>';
    }

    $html .= '</tbody></table></div>';
}

// ==============================
// Recent Reports
// ==============================

if (!empty($recentReports)) {
    $html .= '<div class="section">
                <div class="section-title">Recent Reports</div>
                <table><thead><tr>
                    <th>ID</th><th>Title</th><th>Involved</th><th>Student</th>
                    <th>Incident</th><th>Submitted</th><th>Status</th><th>Severity</th><th>Evidence</th><th>Actions</th>
                </tr></thead><tbody>';

    foreach ($recentReports as $r) {
        $html .= '<tr>
                    <td>' . $r['id'] . '</td>
                    <td>' . htmlspecialchars($r['title']) . '</td>
                    <td>' . htmlspecialchars($r['bully_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>
                    <td>' . ($r['date_of_incident'] ? date('Y-m-d', strtotime($r['date_of_incident'])) : 'N/A') . '</td>
                    <td>' . date('Y-m-d', strtotime($r['submission_date'])) . '</td>
                    <td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $r['status']))) . '</td>
                    <td>' . htmlspecialchars($r['severity']) . '</td>
                    <td>' . $r['evidence_count'] . '</td>
                    <td>' . htmlspecialchars(substr($r['recommended_actions'] ?? '', 0, 150)) . '</td>
                  </tr>';
    }

    $html .= '</tbody></table></div>';
}

// ==============================
// School Details
// ==============================

$html .= '<div class="section"><div class="section-title">School Information</div><table>';

$html .= '<tr><th style="width:25%;">School Name</th><td>' . htmlspecialchars($school['name']) . '</td></tr>';
$html .= '<tr><th>School Code</th><td>' . htmlspecialchars($school['code']) . '</td></tr>';
$html .= '<tr><th>Contact Person</th><td>' . htmlspecialchars($school['contact_person'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><th>Email</th><td>' . htmlspecialchars($school['email']) . '</td></tr>';
$html .= '<tr><th>Phone</th><td>' . htmlspecialchars($school['phone'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><th>Address</th><td>' . htmlspecialchars($school['address'] ?? 'Not specified') . '</td></tr>';

$html .= '</table></div>';

// ==============================
// Footer
// ==============================

$html .= '<div class="footer">
            <p>This report was generated by the Capstone Report Management System</p>
            <p>Report contains data from ' . date('F j, Y', strtotime($dateFrom)) . ' to ' . date('F j, Y', strtotime($dateTo)) . '</p>
            <p>© ' . date('Y') . ' Capstone Management System. All rights reserved.</p>
          </div>';

$html .= '</body></html>';

// ==============================
//  PDF FILE NAME
// ==============================

$pdfFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $school['name'])
    . '_Analytics_Report_' . date('Y-m-d') . '.pdf';

// ==============================
//  DOMPDF
// ==============================

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {

    require_once __DIR__ . '/../vendor/autoload.php';

    if (class_exists('\Dompdf\Dompdf')) {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            ob_clean();
            header("Content-type: application/pdf");
            header("Content-Disposition: attachment; filename=\"$pdfFilename\"");
            echo $dompdf->output();
            exit;

        } catch (Exception $e) {
            error_log("PDF generation failed: " . $e->getMessage());
        }
    }
}

// ==============================
// FALLBACK (HTML DOWNLOAD)
// ==============================
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' .
    preg_replace('/[^A-Za-z0-9_\-]/', '_', $school['name']) .
    '_Analytics_Report_' . date('Y-m-d') . '.html"');

echo $html;
echo "\n<!-- PDF NOT AVAILABLE. Install DomPDF: composer require dompdf/dompdf -->";
exit;
