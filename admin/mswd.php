<?php
$pageTitle = 'MSWD Cases';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';
requireRole('admin');

// List reports referred to MSWD or verified
$cases = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, s_report.name as report_school_name
     FROM reports r
     JOIN users u ON r.student_id = u.id
     JOIN schools s_report ON r.school_id = s_report.id
     WHERE r.status IN ('referred_to_mswd','verified')
     ORDER BY r.submission_date DESC"
);

// Handle adding intervention (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_intervention') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $sessionDate = $_POST['session_date'] ?? null;
        $counselor = trim($_POST['counselor_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $outcome = trim($_POST['outcome'] ?? '');

        if (!$reportId || !$sessionDate) {
            $_SESSION['flash_message'] = 'Report and session date are required.';
            $_SESSION['flash_type'] = 'error';
            header('Location: mswd.php');
            exit;
        }

        try {
            $insertData = [
                'report_id' => $reportId,
                'added_by_user_id' => $_SESSION['user_id'] ?? null,
                'added_by_type' => 'admin',
                'session_date' => $sessionDate,
                'counselor_name' => $counselor,
                'notes' => $notes,
                'outcome' => $outcome
            ];
            $db->insert('interventions', $insertData);

            // Optionally update report status to 'verified' if provided
            if (!empty($_POST['mark_verified'])) {
                $db->update('reports', ['status' => 'verified'], 'id = :id', ['id' => $reportId]);
            }

            // Notify student and reporting school about intervention
            $reportDetails = $db->fetchOne(
                "SELECT r.*, u.first_name, u.last_name, u.email, s.name as school_email FROM reports r JOIN users u ON r.student_id = u.id JOIN schools s ON r.school_id = s.id WHERE r.id = ?",
                [$reportId]
            );

            if ($reportDetails && !empty($reportDetails['email'])) {
                require_once __DIR__ . '/../templates/email/load_template.php';
                $subject = 'Intervention Recorded for Report #' . $reportId;
                $body = "<p>Dear " . htmlspecialchars($reportDetails['first_name']) . ",</p>";
                $body .= "<p>An intervention/counseling session has been recorded for the report you are involved in (Report ID #$reportId). Details:</p>";
                $body .= "<ul>";
                $body .= "<li>Session date: " . htmlspecialchars($sessionDate) . "</li>";
                if ($counselor) $body .= "<li>Counselor: " . htmlspecialchars($counselor) . "</li>";
                if ($outcome) $body .= "<li>Outcome: " . htmlspecialchars($outcome) . "</li>";
                $body .= "</ul>";
                $body .= "<p>If you have questions, please contact your school administration.</p>";

                try {
                    sendMail($reportDetails['email'], $subject, $body, $reportDetails['email'] ?? null, APP_NAME);
                } catch (Exception $e) {
                    error_log('MSWD mail error to student: ' . $e->getMessage());
                }
            }

            // Notify school contact if has email (use school's configured from_email)
            $schoolSmtp = $db->fetchOne("SELECT from_email, from_name FROM schools WHERE id = ?", [$reportDetails['school_id'] ?? 0]);
            if ($schoolSmtp && !empty($schoolSmtp['from_email'])) {
                try {
                    $subject = 'Intervention Recorded for Report #' . $reportId;
                    $body = "<p>Dear School Administrator,</p>";
                    $body .= "<p>An intervention has been recorded for report ID #$reportId submitted to your school.</p>";
                    $body .= "<p>Session date: " . htmlspecialchars($sessionDate) . "</p>";
                    sendMail($schoolSmtp['from_email'], $subject, $body, $schoolSmtp['from_email'], $schoolSmtp['from_name'] ?? APP_NAME);
                } catch (Exception $e) {
                    error_log('MSWD mail error to school: ' . $e->getMessage());
                }
            }

            $_SESSION['flash_message'] = 'Intervention recorded successfully.';
            $_SESSION['flash_type'] = 'success';
            header('Location: mswd.php');
            exit;
        } catch (Throwable $t) {
            error_log('Failed to add intervention: ' . $t->getMessage());
            $_SESSION['flash_message'] = 'Failed to record intervention.';
            $_SESSION['flash_type'] = 'error';
            header('Location: mswd.php');
            exit;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-hand-holding-heart text-primary me-2"></i>MSWD Cases</h1>
        <p class="text-muted mb-0">Cases referred to MSWD or verified by the system. Add interventions and counseling records here.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Referred / Verified Cases</h6>
            </div>
            <div class="card-body">
                <?php if (empty($cases)): ?>
                    <div class="text-center py-4 text-muted">No MSWD cases found.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($cases as $case): ?>
                            <a href="all_reports.php?id=<?php echo $case['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($case['title']); ?></h6>
                                    <small><?php echo formatDate($case['submission_date']); ?></small>
                                </div>
                                <p class="mb-1 small text-muted">Student: <?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?> | School: <?php echo htmlspecialchars($case['report_school_name']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Record Intervention</h6></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_intervention">
                    <div class="mb-2">
                        <label class="form-label">Report</label>
                        <select name="report_id" class="form-select" required>
                            <option value="">Select a report</option>
                            <?php foreach ($cases as $c): ?>
                                <option value="<?php echo $c['id']; ?>">#<?php echo $c['id']; ?> — <?php echo htmlspecialchars($c['title'] . ' — ' . $c['first_name'] . ' ' . $c['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Session Date</label>
                        <input type="date" name="session_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Counselor</label>
                        <input type="text" name="counselor_name" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Outcome</label>
                        <input type="text" name="outcome" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="mark_verified" id="mark_verified">
                        <label class="form-check-label" for="mark_verified">Mark report as Verified</label>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary">Record Intervention</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mt-3">
            <a href="all_reports.php" class="btn btn-outline-secondary w-100">Back to Reports</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
