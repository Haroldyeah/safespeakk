<?php
$pageTitle = 'Manage Interventions';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';
requireRole('admin');

$schoolId = $_SESSION['school_id'] ?? null;
$reportId = (int)($_GET['report_id'] ?? 0);

// Handle adding intervention via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input['action'] === 'add_intervention') {
        $reportId = (int)$input['report_id'];
        $sessionDate = $input['session_date'] ?? null;
        $counselorName = $input['counselor_name'] ?? '';
        $notes = $input['notes'] ?? '';
        $outcome = $input['outcome'] ?? '';
        
        header('Content-Type: application/json');
        
        if (!$reportId || !$sessionDate) {
            echo json_encode(['success' => false, 'message' => 'Report ID and session date are required']);
            exit;
        }
        
        try {
            $insertData = [
                'report_id' => $reportId,
                'added_by_user_id' => $_SESSION['user_id'],
                'added_by_type' => 'admin',
                'session_date' => $sessionDate,
                'counselor_name' => $counselorName,
                'notes' => $notes,
                'outcome' => $outcome,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $success = $db->insert('interventions', $insertData);
            
            if ($success) {
                // Get report details for email
                $reportDetails = $db->fetchOne(
                    "SELECT r.title, u.first_name, u.last_name, u.email FROM reports r JOIN users u ON r.student_id = u.id WHERE r.id = ?",
                    [$reportId]
                );
                
                // Get school SMTP settings
                $report = $db->fetchOne("SELECT school_id FROM reports WHERE id = ?", [$reportId]);
                $schoolSmtp = $db->fetchOne(
                    "SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM schools WHERE id = ?",
                    [$report['school_id']]
                );
                
                // Send notification email
                if ($reportDetails && $reportDetails['email'] && $schoolSmtp) {
                    require_once __DIR__ . '/../templates/email/load_template.php';
                    $body = load_email_template('intervention_added.php', [
                        'studentName' => $reportDetails['first_name'] . ' ' . $reportDetails['last_name'],
                        'reportTitle' => $reportDetails['title'],
                        'sessionDate' => $sessionDate,
                        'counselorName' => $counselorName,
                        'notes' => $notes,
                        'outcome' => $outcome,
                        'appName' => APP_NAME,
                        'baseUrl' => BASE_URL
                    ]);
                    
                    try {
                        sendMail(
                            $reportDetails['email'],
                            'Intervention Session Added to Your Report',
                            $body,
                            $schoolSmtp['from_email'],
                            $schoolSmtp['from_name']
                        );
                    } catch (Exception $e) {
                        error_log('Mail Error: ' . $e->getMessage());
                    }
                }
                
                logActivity($db, $_SESSION['user_id'], 'admin', 'add_intervention', "Added intervention to report #$reportId");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Intervention added successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add intervention']);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Get interventions with report details
$interventions = $db->fetchAll(
    "SELECT i.*, r.title as report_title, r.id as report_id, u.first_name, u.last_name, s.name as school_name
     FROM interventions i
     JOIN reports r ON i.report_id = r.id
     JOIN users u ON r.student_id = u.id
     JOIN schools s ON r.school_id = s.id
     ORDER BY i.session_date DESC",
    []
);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-notes-medical text-primary me-2"></i>Manage Interventions</h1>
        <p class="text-muted">View and manage all counseling and intervention sessions across the system.</p>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">All Interventions</h5>
        <span class="badge bg-secondary"><?php echo count($interventions); ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($interventions)): ?>
            <div class="text-center p-4 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                <p>No interventions recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Report</th>
                            <th>Student</th>
                            <th>School</th>
                            <th>Session Date</th>
                            <th>Counselor</th>
                            <th>Outcome</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $iv): ?>
                        <tr>
                            <td><?php echo $iv['id']; ?></td>
                            <td>
                                <a href="all_reports.php?id=<?php echo $iv['report_id']; ?>" class="text-decoration-none">
                                    #<?php echo $iv['report_id']; ?> — <?php echo htmlspecialchars($iv['report_title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($iv['first_name'] . ' ' . $iv['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($iv['school_name']); ?></td>
                            <td><?php echo htmlspecialchars($iv['session_date']); ?></td>
                            <td><?php echo htmlspecialchars($iv['counselor_name'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($iv['outcome'] ?: '—'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $iv['added_by_type'] === 'admin' ? 'primary' : 'info'; ?>">
                                    <?php echo ucfirst($iv['added_by_type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="all_reports.php?id=<?php echo $iv['report_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Report
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
