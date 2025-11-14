<?php
$pageTitle = 'View Student';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];
$studentId = (int)($_GET['id'] ?? 0);

$student = $db->fetchOne("SELECT id, first_name, last_name, email, student_id, id_photo_path FROM users WHERE id = ? AND school_id = ? AND role = 'student'", [$studentId, $schoolId]);
if (!$student) {
    redirect('students.php', 'Student not found or access denied', 'error');
}

$reports = $db->fetchAll("SELECT id, title, status, submission_date FROM reports WHERE student_id = ? AND deleted_at IS NULL ORDER BY submission_date DESC", [$studentId]);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-user-graduate text-primary me-2"></i>Student Details</h1>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
    </div>
    <div class="col-auto">
        <a href="students.php" class="btn btn-outline-secondary">Back to Students</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($student['id_photo_path'] && file_exists(__DIR__ . '/../' . $student['id_photo_path'])): ?>
                    <img src="<?php echo '../' . htmlspecialchars($student['id_photo_path']); ?>" alt="ID" class="img-fluid rounded mb-3" style="max-height:220px;object-fit:cover;">
                <?php else: ?>
                    <div class="bg-light rounded mb-3" style="width:100%;height:220px;display:flex;align-items:center;justify-content:center;font-size:48px;color:#6c757d;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                <?php endif; ?>

                <h5 class="mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                <p class="text-muted small mb-2">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                <p class="text-muted small">Email: <?php echo htmlspecialchars($student['email']); ?></p>
                <hr>
                <p class="mb-0">Total Reports: <strong><?php echo count($reports); ?></strong></p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Reports</h6>
            </div>
            <div class="card-body">
                <?php if (empty($reports)): ?>
                    <div class="text-center text-muted py-4">No reports found for this student.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($reports as $r): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                                    <br><small class="text-muted"><?php echo formatDate($r['submission_date']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-<?php echo $r['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$r['status'])); ?></span>
                                    <div class="mt-2">
                                        <a href="../school/manage_report.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
