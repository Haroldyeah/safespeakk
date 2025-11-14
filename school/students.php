<?php
$pageTitle = 'Students';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Get students for this school with report counts
$students = $db->fetchAll(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.student_id, u.id_photo_path, 
            (SELECT COUNT(*) FROM reports r WHERE r.student_id = u.id AND r.deleted_at IS NULL) as active_report_count,
            (SELECT COUNT(*) FROM reports r WHERE r.student_id = u.id AND r.status = 'approved' AND r.deleted_at IS NULL) as approved_report_count,
            (SELECT COUNT(*) FROM reports r WHERE r.student_id = u.id AND r.deleted_at IS NOT NULL) as deleted_report_count
     FROM users u
     WHERE u.school_id = ? AND u.role = 'student'
     ORDER BY u.last_name, u.first_name
     LIMIT ? OFFSET ?",
    [$schoolId, $perPage, $offset]
);

// Get total count for pagination
$total = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE school_id = ? AND role = 'student'", [$schoolId])['count'];
$totalPages = ceil($total / $perPage);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-users text-primary me-2"></i>Students</h1>
        <p class="text-muted mb-0">Students registered to <?php echo htmlspecialchars($_SESSION['school_name']); ?></p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($students)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                <h6>No students found</h6>
                <p class="mb-0">Students registered to your school will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Student ID</th>
                            <th>Reports</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($s['id_photo_path'] && file_exists(__DIR__ . '/../' . $s['id_photo_path'])): ?>
                                            <img src="<?php echo '../' . htmlspecialchars($s['id_photo_path']); ?>" alt="ID" style="width:48px;height:48px;border-radius:6px;object-fit:cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded" style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#6c757d;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong>
                                            <br><small class="text-muted">Registered student</small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                <td>
                                    <span class="badge bg-primary me-1" title="Active Reports"><?php echo $s['active_report_count']; ?> Active</span>
                                    <?php if ($s['approved_report_count'] > 0): ?>
                                        <span class="badge bg-success me-1" title="Approved Reports"><?php echo $s['approved_report_count']; ?> Approved</span>
                                    <?php endif; ?>
                                    <?php if ($s['deleted_report_count'] > 0): ?>
                                        <span class="badge bg-danger" title="Deleted Reports"><?php echo $s['deleted_report_count']; ?> Deleted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_student.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
