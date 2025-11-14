<?php
$pageTitle = 'System Logs';
require_once '../config/config.php';
requireRole('admin');

global $db;

// Pagination setup
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Fetch total count for pagination
$totalLogs = $db->fetchOne("SELECT COUNT(*) as count FROM system_logs")['count'];

// Fetch system logs
$systemLogs = $db->fetchAll(
    "SELECT sl.*, u.first_name, u.last_name, s.name as school_name
     FROM system_logs sl
     LEFT JOIN users u ON sl.user_id = u.id AND (sl.user_type = 'admin' OR sl.user_type = 'student')
     LEFT JOIN schools s ON sl.user_id = s.id AND sl.user_type = 'school'
     ORDER BY sl.created_at DESC
     LIMIT ? OFFSET ?",
    [$perPage, $offset]
);

// Calculate total pages
$totalPages = ceil($totalLogs / $perPage);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-clipboard-list text-primary me-2"></i>
            System Logs
        </h1>
        <p class="text-muted mb-0">
            View all system activities and user actions.
            <span class="badge bg-secondary ms-2"><?php echo $totalLogs; ?> total</span>
        </p>
    </div>
    <div class="col-auto">
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Log Entries</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($systemLogs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No system logs found.</h6>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>User Type</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($systemLogs as $log): ?>
                            <tr>
                                <td><small><?php echo formatDate($log['created_at']); ?></small></td>
                                <td>
                                    <?php
                                    if ($log['user_type'] === 'admin' || $log['user_type'] === 'student') {
                                        echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']);
                                    } elseif ($log['user_type'] === 'school') {
                                        echo htmlspecialchars($log['school_name']);
                                    } else {
                                        echo 'System';
                                    }
                                    ?>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($log['user_type'])); ?></span></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><small><?php echo htmlspecialchars($log['description']); ?></small></td>
                                <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Log pagination">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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

<?php require_once '../includes/footer.php'; ?>