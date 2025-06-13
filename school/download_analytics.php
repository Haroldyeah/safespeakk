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

$revisionRequiredReports = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports WHERE school_id = ? AND status = 'revision_required' AND submission_date BETWEEN ? AND ?",
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

// Set headers for download
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $school['name'] . '_Analytics_Report_' . date('Y-m-d') . '.html"');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($school['name']); ?> - Analytics Report</title>
    <style>
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
        .status-approved { background-color: #d1fae5; color: #047857; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .status-rejected { background-color: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .status-under_review { background-color: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .status-submitted { background-color: #ebf8ff; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .status-revision_required { background-color: #ede9fe; color: #6b21a8; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>

<div class="header">
    <div class="school-name"><?php echo htmlspecialchars($school['name']); ?></div>
    <div class="report-title">Capstone Reports Analytics</div>
    <div class="report-period">
        Report Period: <?php echo date('F j, Y', strtotime($dateFrom)); ?> - <?php echo date('F j, Y', strtotime($dateTo)); ?>
        <br>Generated on: <?php echo date('F j, Y g:i A'); ?>
    </div>
</div>

<div class="section">
    <div class="section-title">Summary Statistics</div>
    <div class="statistics">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalReports; ?></div>
            <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #059669;"><?php echo $approvedReports; ?></div>
            <div class="stat-label">Approved Reports</div>
            <div style="color: #059669; font-size: 12px;"><?php echo $approvalRate; ?>%</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #F59E0B;"><?php echo $underReviewReports; ?></div>
            <div class="stat-label">Under Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #3B82F6;"><?php echo $submittedReports; ?></div>
            <div class="stat-label">Submitted</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #EF4444;"><?php echo $rejectedReports; ?></div>
            <div class="stat-label">Rejected</div>
            <div style="color: #EF4444; font-size: 12px;"><?php echo $rejectionRate; ?>%</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #8B5CF6;"><?php echo $revisionRequiredReports; ?></div>
            <div class="stat-label">Revision Required</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $activeStudents; ?></div>
            <div class="stat-label">Active Students</div>
        </div>
    </div>
</div>

<?php if (!empty($monthlyData)): ?>
<div class="section">
    <div class="section-title">Monthly Submission Trends</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Total Submissions</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th>Approval Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthlyData as $month): ?>
                <?php $monthlyApprovalRate = $month['total_submissions'] > 0 ? round(($month['approved_count'] / $month['total_submissions']) * 100, 1) : 0; ?>
                <tr>
                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                    <td><?php echo $month['total_submissions']; ?></td>
                    <td><span class="status-approved"><?php echo $month['approved_count']; ?></span></td>
                    <td><span class="status-rejected"><?php echo $month['rejected_count']; ?></span></td>
                    <td><?php echo $monthlyApprovalRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($topStudents)): ?>
<div class="section">
    <div class="section-title">Top Performing Students</div>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Total Reports</th>
                <th>Approved Reports</th>
                <th>Average Grade</th>
                <th>Success Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topStudents as $student): ?>
                <?php $successRate = $student['total_reports'] > 0 ? round(($student['approved_reports'] / $student['total_reports']) * 100, 1) : 0; ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo $student['total_reports']; ?></td>
                    <td><span class="status-approved"><?php echo $student['approved_reports']; ?></span></td>
                    <td>
                        <?php if ($student['avg_grade']): ?>
                            <?php echo number_format($student['avg_grade'], 1); ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo $successRate; ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="section">
    <div class="section-title">School Information</div>
    <table>
        <tr>
            <th style="width: 25%;">School Name</th>
            <td><?php echo htmlspecialchars($school['name']); ?></td>
        </tr>
        <tr>
            <th>School Code</th>
            <td><?php echo htmlspecialchars($school['code']); ?></td>
        </tr>
        <tr>
            <th>Contact Person</th>
            <td><?php echo htmlspecialchars($school['contact_person'] ?? 'Not specified'); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($school['email']); ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars($school['phone'] ?? 'Not specified'); ?></td>
        </tr>
        <tr>
            <th>Address</th>
            <td><?php echo htmlspecialchars($school['address'] ?? 'Not specified'); ?></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Performance Analysis</div>
    <table>
        <tr>
            <th style="width: 30%;">Metric</th>
            <th>Value</th>
            <th>Analysis</th>
        </tr>
        <tr>
            <td>Overall Approval Rate</td>
            <td><?php echo $approvalRate; ?>%</td>
            <td>
                <?php if ($approvalRate >= 80): ?>
                    Excellent performance - well above average
                <?php elseif ($approvalRate >= 60): ?>
                    Good performance - meeting expectations
                <?php elseif ($approvalRate >= 40): ?>
                    Average performance - room for improvement
                <?php else: ?>
                    Below average - requires attention
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Student Participation</td>
            <td><?php echo $activeStudents; ?> students</td>
            <td>
                <?php if ($activeStudents > 0): ?>
                    Active student engagement in capstone submissions
                <?php else: ?>
                    No student activity in selected period
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Rejection Rate</td>
            <td><?php echo $rejectionRate; ?>%</td>
            <td>
                <?php if ($rejectionRate <= 10): ?>
                    Low rejection rate - quality submissions
                <?php elseif ($rejectionRate <= 25): ?>
                    Moderate rejection rate - acceptable range
                <?php else: ?>
                    High rejection rate - may need quality improvement
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <p>This report was generated by the Capstone Report Management System</p>
    <p>Report contains data from <?php echo date('F j, Y', strtotime($dateFrom)); ?> to <?php echo date('F j, Y', strtotime($dateTo)); ?></p>
    <p>© <?php echo date('Y'); ?> Capstone Management System. All rights reserved.</p>
</div>

</body>
</html>