<?php
$pageTitle = 'Interventions';
require_once 'config/config.php';
require_once 'includes/functions.php';
requireLogin();

$role = getUserRole();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'session_date';
$sortOrder = $_GET['order'] ?? 'DESC';
$schoolFilter = $_GET['school'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Valid sort columns
$validSortColumns = ['session_date', 'counselor_name', 'school_name', 'first_name', 'report_title', 'created_at'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'session_date';
}
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Build query depending on role
$params = [];
$conditions = [];
$sql = "SELECT i.*, r.title as report_title, r.id as report_id, u.first_name, u.last_name, s.name as school_name, s.id as school_id
        FROM interventions i
        JOIN reports r ON i.report_id = r.id
        JOIN users u ON r.student_id = u.id
        JOIN schools s ON r.school_id = s.id
";

if ($role === 'admin') {
    // Admin sees all interventions
} elseif ($role === 'school') {
    $schoolId = $_SESSION['school_id'];
    $conditions[] = 'r.school_id = ?';
    $params[] = $schoolId;
} elseif ($role === 'student') {
    $studentId = $_SESSION['user_id'];
    $conditions[] = 'r.student_id = ?';
    $params[] = $studentId;
}

// Apply filters
if ($search) {
    $conditions[] = "(r.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR i.counselor_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($role === 'admin' && $schoolFilter) {
    $conditions[] = 's.id = ?';
    $params[] = $schoolFilter;
}

if ($dateFrom) {
    $conditions[] = 'i.session_date >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $conditions[] = 'i.session_date <= ?';
    $params[] = $dateTo;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Get total count
$countSql = str_replace('SELECT i.*, r.title as report_title, r.id as report_id, u.first_name, u.last_name, s.name as school_name, s.id as school_id', 'SELECT COUNT(*) as count', $sql);
$totalInterventions = $db->fetchOne($countSql, $params)['count'] ?? 0;

// Get interventions
$sqlWithOrder = $sql . " ORDER BY $sortBy $sortOrder LIMIT $perPage OFFSET $offset";
$interventions = $db->fetchAll($sqlWithOrder, $params);

// Get schools for filter (admin only)
$schools = [];
if ($role === 'admin') {
    $schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");
}

$totalPages = ceil($totalInterventions / $perPage);

require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-notes-medical text-primary me-2"></i>Interventions</h1>
        <p class="text-muted">Recorded counseling and intervention sessions. Scoped to your role.</p>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
                </div>
            </div>
            
            <?php if ($role === 'admin'): ?>
            <div class="col-md-2">
                <select class="form-select" name="school">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $sch): ?>
                        <option value="<?php echo $sch['id']; ?>" <?php echo $schoolFilter == $sch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sch['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From">
            </div>
            
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To">
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="sort">
                    <option value="session_date" <?php echo $sortBy === 'session_date' ? 'selected' : ''; ?>>Sort by Date</option>
                    <option value="counselor_name" <?php echo $sortBy === 'counselor_name' ? 'selected' : ''; ?>>Sort by Counselor</option>
                    <?php if ($role === 'admin'): ?>
                    <option value="school_name" <?php echo $sortBy === 'school_name' ? 'selected' : ''; ?>>Sort by School</option>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                    <option value="first_name" <?php echo $sortBy === 'first_name' ? 'selected' : ''; ?>>Sort by Student</option>
                    <?php endif; ?>
                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Sort by Created</option>
                </select>
            </div>
            
            <div class="col-md-1">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    <a href="interventions.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Interventions <?php if ($totalInterventions > 0): ?><span class="badge bg-secondary ms-2"><?php echo $totalInterventions; ?> total</span><?php endif; ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($interventions)): ?>
            <div class="text-center p-4 text-muted">No interventions found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="interventionsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>
                                <a href="?page=1&sort=report_title&order=<?php echo $sortBy === 'report_title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?><?php if ($search) echo '&search=' . urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>" class="text-decoration-none text-dark">
                                    Report
                                    <?php if ($sortBy === 'report_title'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?page=1&sort=first_name&order=<?php echo $sortBy === 'first_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?><?php if ($search) echo '&search=' . urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>" class="text-decoration-none text-dark">
                                    Student
                                    <?php if ($sortBy === 'first_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <?php if ($role === 'admin'): ?>
                            <th>
                                <a href="?page=1&sort=school_name&order=<?php echo $sortBy === 'school_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?><?php if ($search) echo '&search=' . urlencode($search); ?><?php if ($schoolFilter) echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>" class="text-decoration-none text-dark">
                                    School
                                    <?php if ($sortBy === 'school_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <?php endif; ?>
                            <th>
                                <a href="?page=1&sort=session_date&order=<?php echo $sortBy === 'session_date' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?><?php if ($search) echo '&search=' . urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>" class="text-decoration-none text-dark">
                                    Session Date
                                    <?php if ($sortBy === 'session_date'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?page=1&sort=counselor_name&order=<?php echo $sortBy === 'counselor_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?><?php if ($search) echo '&search=' . urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>" class="text-decoration-none text-dark">
                                    Counselor
                                    <?php if ($sortBy === 'counselor_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Outcome</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $iv): ?>
                        <tr>
                            <td><?php echo $iv['id']; ?></td>
                            <td><a href="<?php echo ($role === 'admin') ? 'admin/all_reports.php?id=' : 'school/manage_report.php?id='; ?><?php echo $iv['report_id']; ?>" class="text-decoration-none">#<?php echo $iv['report_id']; ?> — <?php echo htmlspecialchars($iv['report_title']); ?></a></td>
                            <td><?php echo htmlspecialchars($iv['first_name'] . ' ' . $iv['last_name']); ?></td>
                            <?php if ($role === 'admin'): ?>
                            <td><?php echo htmlspecialchars($iv['school_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($iv['session_date']); ?></td>
                            <td><?php echo htmlspecialchars($iv['counselor_name'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($iv['outcome'] ?: '—'); ?></td>
                            <td style="max-width:250px;white-space:pre-wrap;overflow:hidden;text-overflow:ellipsis;font-size:0.875rem;">
                                <?php echo nl2br(htmlspecialchars($iv['notes'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Interventions pagination">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?><?php if ($schoolFilter && $role === 'admin') echo '&school=' . urlencode($schoolFilter); ?><?php if ($dateFrom) echo '&date_from=' . urlencode($dateFrom); ?><?php if ($dateTo) echo '&date_to=' . urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
