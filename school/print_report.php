<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireRole('school');

$reportId = (int)($_GET['id'] ?? 0);
$schoolId = $_SESSION['school_id'];

if (!$reportId) {
    die('Invalid report ID.');
}

$db = new Database();

$report = $db->fetchOne(
    "SELECT r.*, u.first_name, u.last_name, u.student_id, s.name as school_name 
     FROM reports r 
     JOIN users u ON r.student_id = u.id 
     JOIN schools s ON r.school_id = s.id
     WHERE r.id = ? AND r.school_id = ?",
    [$reportId, $schoolId]
);

if (!$report) {
    die('Report not found or access denied.');
}

$evidence = $db->fetchAll("SELECT * FROM report_evidence WHERE report_id = ?", [$reportId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Report #<?php echo $report['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
        body {
            font-family: sans-serif;
        }
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .report-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 0.5rem;
        }
        .details-grid > div:nth-child(odd) {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">Print or Save as PDF</button>
            <a href="manage_report.php?id=<?php echo $reportId; ?>" class="btn btn-secondary">Back to Report</a>
        </div>

        <div class="report-header">
            <h2>Capstone Report</h2>
            <p>Report #<?php echo $report['id']; ?></p>
        </div>

        <div class="section-title">Report Details</div>
        <div class="details-grid">
            <div>Report Title:</div>
            <div><?php echo htmlspecialchars($report['title']); ?></div>

            <div>Student:</div>
            <div><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?> (ID: <?php echo htmlspecialchars($report['student_id']); ?>)</div>

            <div>School:</div>
            <div><?php echo htmlspecialchars($report['school_name']); ?></div>

            <div>Date of Incident:</div>
            <div><?php echo formatDate($report['date_of_incident']); ?></div>

            <div>Submission Date:</div>
            <div><?php echo formatDate($report['submission_date']); ?></div>

            <div>Status:</div>
            <div><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $report['status']))); ?></div>
        </div>

        <div class="section-title">Report Content</div>
        <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>

        <?php if (!empty($evidence)): ?>
            <div class="section-title">Evidence</div>
            <ul>
                <?php foreach ($evidence as $file): ?>
                    <li><?php echo htmlspecialchars($file['file_name']); ?> (<?php echo formatFileSize($file['file_size']); ?>)</li>
                <?php endforeach; ?>
            </ul>
            <p class="text-muted">Note: Evidence files are not included in this printout.</p>
        <?php endif; ?>

    </div>
</body>
</html>
