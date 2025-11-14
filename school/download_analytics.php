<?php
require_once '../config/config.php';
requireRole('school');

// Database connection is already initialized in config.php
global $db;

// Include TCPDF library (you may need to download and include it)
// For now, we'll create a simple HTML-to-PDF solution using DomPDF or similar
// This is a basic implementation - in production, you'd want to use a proper PDF library

$schoolId = $_SESSION['school_id'];

// Get filters from POST
$dateFrom = $_POST['date_from'] ?? date('Y-01-01');
$dateTo = $_POST['date_to'] ?? date('Y-12-31');
$academicYear = $_POST['academic_year'] ?? date('Y');

// Get school information
$school = $db->fetchOne(
    "SELECT * FROM schools WHERE id = ?",
    [$schoolId]
);

// Get statistics
$totalReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$approvedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'approved' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$rejectedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'rejected' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$underReviewReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'under_review' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

$submittedReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'submitted' AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

// Calculate rates
$approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;
$rejectionRate = $totalReports > 0 ? round(($rejectedReports / $totalReports) * 100, 1) : 0;

// Get active students
$activeStudents = $db->fetchOne(
    "SELECT COUNT(DISTINCT student_id) as count FROM reports WHERE school_id = ? AND submission_date BETWEEN ? AND ?",
    [$schoolId, $dateFrom, $dateTo]
)['count'];

// Get top students
$topStudents = $db->fetchAll(
    "SELECT 
        u.first_name, u.last_name, u.student_id,
        COUNT(r.id) as total_reports,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reports,
        AVG(CASE WHEN r.grade IS NOT NULL AND r.grade != '' THEN CAST(r.grade as DECIMAL(4,2)) ELSE NULL END) as avg_grade
     FROM users u
     LEFT JOIN reports r ON u.id = r.student_id AND r.submission_date BETWEEN ? AND ?
     WHERE u.school_id = ? AND u.role = 'student'
     GROUP BY u.id
     HAVING total_reports > 0
     ORDER BY approved_reports DESC, avg_grade DESC
     LIMIT 10",
    [$dateFrom, $dateTo, $schoolId]
);

// Monthly data
$monthlyData = $db->fetchAll(
    "SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as total_submissions,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
     FROM reports 
     WHERE school_id = ? AND submission_date BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
     ORDER BY month",
    [$schoolId, $dateFrom, $dateTo]
);

// Build the full HTML content into a variable so we can optionally render to PDF
$html = '';
$html .= '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($school['name']) . ' - Analytics Report</title>';
$html .= '<style>body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; } .header { text-align: center; border-bottom: 2px solid #1E3A8A; padding-bottom: 20px; margin-bottom: 30px; } .school-name { color: #1E3A8A; font-size: 24px; font-weight: bold; } .report-title { color: #666; font-size: 18px; margin: 10px 0; } .report-period { color: #888; font-size: 14px; } .statistics { display: flex; flex-wrap: wrap; gap: 20px; margin: 30px 0; } .stat-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; flex: 1; min-width: 150px; } .stat-number { font-size: 24px; font-weight: bold; color: #1E3A8A; } .stat-label { color: #666; font-size: 12px; } .section { margin: 30px 0; } .section-title { color: #1E3A8A; font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; } table { width: 100%; border-collapse: collapse; margin: 15px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f8f9fa; font-weight: bold; } .status-approved { background-color: #d1fae5; color: #047857; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-rejected { background-color: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-under_review { background-color: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .status-submitted { background-color: #ebf8ff; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 12px; } .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }</style>';
$html .= '</head><body>';
$html .= '<div class="header">';
$html .= '<div class="school-name">' . htmlspecialchars($school['name']) . '</div>';
$html .= '<div class="report-title">Capstone Reports Analytics</div>';
$html .= '<div class="report-period">Report Period: ' . date('F j, Y', strtotime($dateFrom)) . ' - ' . date('F j, Y', strtotime($dateTo)) . '<br>Generated on: ' . date('F j, Y g:i A') . '</div>';
$html .= '</div>';

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

if (!empty($topStudents)) {
    $html .= '<div class="section"><div class="section-title">Top Performing Students</div><table><thead><tr><th>Student Name</th><th>Student ID</th><th>Total Reports</th><th>Approved Reports</th><th>Average Grade</th><th>Success Rate</th></tr></thead><tbody>';
    foreach ($topStudents as $student) {
        $successRate = $student['total_reports'] > 0 ? round(($student['approved_reports'] / $student['total_reports']) * 100, 1) : 0;
        $html .= '<tr><td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td><td>' . htmlspecialchars($student['student_id']) . '</td><td>' . $student['total_reports'] . '</td><td>' . $student['approved_reports'] . '</td><td>' . ($student['avg_grade'] ? number_format($student['avg_grade'],1) : 'N/A') . '</td><td>' . $successRate . '%</td></tr>';
    }
    $html .= '</tbody></table></div>';
}

// Fetch recent reports with bully_name, severity, evidence count, and recommended actions
$recentReports = $db->fetchAll(
    "SELECT r.id, r.title, r.bully_name, r.severity, r.recommended_actions, u.first_name, u.last_name, r.date_of_incident, r.submission_date, r.status,
        (SELECT COUNT(*) FROM report_evidence re WHERE re.report_id = r.id) as evidence_count
     FROM reports r
     JOIN users u ON r.student_id = u.id
     WHERE r.school_id = ? AND r.submission_date BETWEEN ? AND ? AND r.deleted_at IS NULL
     ORDER BY r.submission_date DESC
     LIMIT 50",
    [$schoolId, $dateFrom, $dateTo]
);

if (!empty($recentReports)) {
    $html .= '<div class="section"><div class="section-title">Recent Reports</div><table><thead><tr><th>ID</th><th>Title</th><th>Involved</th><th>Student</th><th>Incident Date</th><th>Submitted</th><th>Status</th><th>Severity</th><th>Evidence</th><th>Recommended Actions</th></tr></thead><tbody>';
    foreach ($recentReports as $r) {
        $html .= '<tr>';
        $html .= '<td>' . $r['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($r['title']) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['bully_name'] ?? '—') . '</td>';
        $html .= '<td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>';
        $html .= '<td>' . ($r['date_of_incident'] ? date('Y-m-d', strtotime($r['date_of_incident'])) : 'N/A') . '</td>';
        $html .= '<td>' . date('Y-m-d', strtotime($r['submission_date'])) . '</td>';
        $html .= '<td>' . htmlspecialchars(ucfirst(str_replace('_',' ',$r['status']))) . '</td>';
        $html .= '<td>' . htmlspecialchars(ucfirst($r['severity'] ?? 'N/A')) . '</td>';
        $html .= '<td>' . (int)$r['evidence_count'] . '</td>';
        $html .= '<td>' . htmlspecialchars(substr($r['recommended_actions'] ?? '', 0, 150)) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
}

$html .= '<div class="section"><div class="section-title">School Information</div><table>';
$html .= '<tr><th style="width:25%;">School Name</th><td>' . htmlspecialchars($school['name']) . '</td></tr>';
$html .= '<tr><th>School Code</th><td>' . htmlspecialchars($school['code']) . '</td></tr>';
$html .= '<tr><th>Contact Person</th><td>' . htmlspecialchars($school['contact_person'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><th>Email</th><td>' . htmlspecialchars($school['email']) . '</td></tr>';
$html .= '<tr><th>Phone</th><td>' . htmlspecialchars($school['phone'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><th>Address</th><td>' . htmlspecialchars($school['address'] ?? 'Not specified') . '</td></tr>';
$html .= '</table></div>';

$html .= '<div class="section"><div class="section-title">Performance Analysis</div><table><tr><th style="width:30%;">Metric</th><th>Value</th><th>Analysis</th></tr>';
$html .= '<tr><td>Overall Approval Rate</td><td>' . $approvalRate . '%</td><td>';
if ($approvalRate >= 80) {
    $html .= 'Excellent performance - well above average';
} elseif ($approvalRate >= 60) {
    $html .= 'Good performance - meeting expectations';
} elseif ($approvalRate >= 40) {
    $html .= 'Average performance - room for improvement';
} else {
    $html .= 'Below average - requires attention';
}
$html .= '</td></tr>';
$html .= '<tr><td>Student Participation</td><td>' . $activeStudents . ' students</td><td>';
if ($activeStudents > 0) {
    $html .= 'Active student engagement in capstone submissions';
} else {
    $html .= 'No student activity in selected period';
}
$html .= '</td></tr>';
$html .= '<tr><td>Rejection Rate</td><td>' . $rejectionRate . '%</td><td>';
if ($rejectionRate <= 10) {
    $html .= 'Low rejection rate - quality submissions';
} elseif ($rejectionRate <= 25) {
    $html .= 'Moderate rejection rate - acceptable range';
} else {
    $html .= 'High rejection rate - may need quality improvement';
}
$html .= '</td></tr></table></div>';

$html .= '<div class="footer"><p>This report was generated by the Capstone Report Management System</p><p>Report contains data from ' . date('F j, Y', strtotime($dateFrom)) . ' to ' . date('F j, Y', strtotime($dateTo)) . '</p><p>© ' . date('Y') . ' Capstone Management System. All rights reserved.</p></div>';

$html .= '</body></html>';

// Try to render a real PDF using Dompdf if available via Composer
$pdfFilename = preg_replace('/[^A-Za-z0-9_\\-]/', '_', $school['name']) . '_Analytics_Report_' . date('Y-m-d') . '.pdf';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dompdf\\Dompdf')) {
        try {
            $dompdf = new Dompdf\\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            // Stream the PDF to the browser as a download
            $dompdf->stream($pdfFilename, ['Attachment' => 1]);
            exit;
        } catch (Exception $e) {
            // Fallthrough to HTML output below if PDF generation fails
            error_log('PDF generation error: ' . $e->getMessage());
        }
    }
}

// Fallback: output HTML with attachment headers and a note about PDF support
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_\\-]/', '_', $school['name']) . '_Analytics_Report_' . date('Y-m-d') . '.html"');
echo $html;
echo '<!-- NOTE: To enable direct PDF exports, install Dompdf via Composer: composer require dompdf/dompdf and ensure vendor/autoload.php is present. -->';