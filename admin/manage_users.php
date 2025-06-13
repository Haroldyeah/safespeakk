<?php
$pageTitle = 'Manage Users';
require_once '../config/config.php';
requireRole('admin');

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $role = $_POST['role'];
        $studentId = sanitizeInput($_POST['student_id'] ?? '');
        $schoolId = (int)($_POST['school_id'] ?? 0);
        
        if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        } elseif ($role === 'student' && (empty($studentId) || empty($schoolId))) {
            $message = 'Student ID and School are required for student accounts.';
            $messageType = 'error';
        } elseif (!in_array($role, ['student', 'admin'])) {
            $message = 'Invalid role selected.';
            $messageType = 'error';
        } else {
            // Check if username or email already exists
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$username, $email]
            );
            
            if ($existing) {
                $message = 'Username or email already exists.';
                $messageType = 'error';
            } else {
                // Verify school exists if student
                if ($role === 'student') {
                    $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$schoolId]);
                    if (!$school) {
                        $message = 'Selected school is not valid.';
                        $messageType = 'error';
                    }
                }
                
                if ($messageType !== 'error') {
                    $userData = [
                        'username' => $username,
                        'email' => $email,
                        'password' => hashPassword($password),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'role' => $role,
                        'student_id' => $role === 'student' ? $studentId : null,
                        'school_id' => $role === 'student' ? $schoolId : null
                    ];
                    
                    $userId = $db->insert('users', $userData);
                    
                    if ($userId) {
                        logActivity($db, $_SESSION['user_id'], 'admin', 'add_user', "Added $role user: $username");
                        $message = 'User added successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to add user.';
                        $messageType = 'error';
                    }
                }
            }
        }
    } elseif ($action === 'update_user') {
        $userId = (int)$_POST['user_id'];
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $role = $_POST['role'];
        $studentId = sanitizeInput($_POST['student_id'] ?? '');
        $schoolId = (int)($_POST['school_id'] ?? 0);
        $status = $_POST['status'];
        
        if (empty($username) || empty($email) || empty($firstName) || empty($lastName) || empty($role)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        } elseif ($role === 'student' && (empty($studentId) || empty($schoolId))) {
            $message = 'Student ID and School are required for student accounts.';
            $messageType = 'error';
        } else {
            // Check if username or email already exists (excluding current user)
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?",
                [$username, $email, $userId]
            );
            
            if ($existing) {
                $message = 'Username or email already exists.';
                $messageType = 'error';
            } else {
                $updateData = [
                    'username' => $username,
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $role,
                    'student_id' => $role === 'student' ? $studentId : null,
                    'school_id' => $role === 'student' ? $schoolId : null,
                    'status' => $status
                ];
                
                // If password is provided, hash and include it
                if (!empty($_POST['password'])) {
                    $updateData['password'] = hashPassword($_POST['password']);
                }
                
                $success = $db->update('users', $updateData, 'id = ?', [$userId]);
                
                if ($success) {
                    logActivity($db, $_SESSION['user_id'], 'admin', 'update_user', "Updated user ID: $userId");
                    $message = 'User updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update user.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        
        // Check if user has reports
        $reportCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM reports WHERE student_id = ?",
            [$userId]
        )['count'];
        
        if ($reportCount > 0) {
            $message = 'Cannot delete user with existing reports. Set status to inactive instead.';
            $messageType = 'error';
        } elseif ($userId === $_SESSION['user_id']) {
            $message = 'You cannot delete your own account.';
            $messageType = 'error';
        } else {
            $success = $db->delete('users', 'id = ?', [$userId]);
            
            if ($success) {
                logActivity($db, $_SESSION['user_id'], 'admin', 'delete_user', "Deleted user ID: $userId");
                $message = 'User deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'error';
            }
        }
    }
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$schoolFilter = $_GET['school'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($search) {
    $conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.student_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($roleFilter) {
    $conditions[] = "u.role = ?";
    $params[] = $roleFilter;
}

if ($schoolFilter) {
    $conditions[] = "u.school_id = ?";
    $params[] = $schoolFilter;
}

if ($statusFilter) {
    $conditions[] = "u.status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $conditions);

// Get total count
$totalUsers = $db->fetchOne(
    "SELECT COUNT(*) as count FROM users u WHERE $whereClause",
    $params
)['count'];

// Get users with statistics
$users = $db->fetchAll(
    "SELECT u.*, s.name as school_name, 
            COUNT(r.id) as report_count,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count
     FROM users u 
     LEFT JOIN schools s ON u.school_id = s.id 
     LEFT JOIN reports r ON u.id = r.student_id 
     WHERE $whereClause 
     GROUP BY u.id 
     ORDER BY u.created_at DESC 
     LIMIT $perPage OFFSET $offset",
    $params
);

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

// Calculate pagination
$totalPages = ceil($totalUsers / $perPage);

// Get user statistics
$userStats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'students' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'")['count'],
    'admins' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'")['count'],
    'inactive' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")['count']
];

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-users text-primary me-2"></i>
            Manage Users
        </h1>
        <p class="text-muted mb-0">Add, edit, and manage system users</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i>Add User
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- User Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $userStats['total_users']; ?></h3>
                <p class="mb-0 text-muted">Total Active Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success"><?php echo $userStats['students']; ?></h3>
                <p class="mb-0 text-muted">Students</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info"><?php echo $userStats['admins']; ?></h3>
                <p class="mb-0 text-muted">Administrators</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-secondary"><?php echo $userStats['inactive']; ?></h3>
                <p class="mb-0 text-muted">Inactive Users</p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search users...">
                </div>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="role">
                    <option value="">All Roles</option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="school">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" 
                                <?php echo $schoolFilter == $school['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="manage_users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Users (<?php echo $totalUsers; ?>)
            <?php if ($search || $roleFilter || $schoolFilter || $statusFilter): ?>
                <small class="text-muted">
                    - filtered results
                </small>
            <?php endif; ?>
        </h5>
        
        <?php if (!empty($users)): ?>
            <button class="btn btn-sm btn-outline-primary" onclick="exportTable('usersTable', 'users')">
                <i class="fas fa-download me-1"></i>Export
            </button>
        <?php endif; ?>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No users found</h6>
                <?php if ($search || $roleFilter || $schoolFilter || $statusFilter): ?>
                    <p class="text-muted mb-3">Try adjusting your search criteria</p>
                    <a href="manage_users.php" class="btn btn-outline-primary">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-3">Add the first user to get started</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-1"></i>Add User
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>School</th>
                            <th>Statistics</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['username']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                        </small>
                                        <?php if ($user['student_id']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-id-badge me-1"></i><?php echo htmlspecialchars($user['student_id']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'warning' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['school_name']): ?>
                                        <span class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($user['school_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <small class="text-muted">N/A</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['role'] === 'student'): ?>
                                        <div class="d-flex gap-1">
                                            <span class="badge bg-secondary"><?php echo $user['report_count']; ?> Reports</span>
                                            <?php if ($user['approved_count'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $user['approved_count']; ?> Approved</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Administrator</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo formatDate($user['created_at']); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button class="btn btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Users pagination">
                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&school=<?php echo urlencode($schoolFilter); ?>&status=<?php echo urlencode($statusFilter); ?>">
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                <div class="invalid-feedback">Please enter first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                <div class="invalid-feedback">Please enter last name.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">Please enter username.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter valid email.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required onchange="toggleStudentFields()">
                                    <option value="">Select Role</option>
                                    <option value="student">Student</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <div class="invalid-feedback">Please select a role.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="studentFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id" class="form-label">Student ID *</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id">
                                    <div class="invalid-feedback">Please enter student ID.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_id" class="form-label">School *</label>
                                    <select class="form-select" id="school_id" name="school_id">
                                        <option value="">Select School</option>
                                        <?php foreach ($schools as $school): ?>
                                            <option value="<?php echo $school['id']; ?>">
                                                <?php echo htmlspecialchars($school['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a school.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                <div class="invalid-feedback">Please enter first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                <div class="invalid-feedback">Please enter last name.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                                <div class="invalid-feedback">Please enter username.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                                <div class="invalid-feedback">Please enter valid email.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                                <small class="text-muted">Leave blank to keep current password</small>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_role" class="form-label">Role *</label>
                                <select class="form-select" id="edit_role" name="role" required onchange="toggleEditStudentFields()">
                                    <option value="student">Student</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <div class="invalid-feedback">Please select a role.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="editStudentFields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_student_id" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="edit_student_id" name="student_id">
                                    <div class="invalid-feedback">Please enter student ID.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_school_id" class="form-label">School</label>
                                    <select class="form-select" id="edit_school_id" name="school_id">
                                        <option value="">Select School</option>
                                        <?php foreach ($schools as $school): ?>
                                            <option value="<?php echo $school['id']; ?>">
                                                <?php echo htmlspecialchars($school['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a school.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStudentFields() {
    const role = document.getElementById('role').value;
    const studentFields = document.getElementById('studentFields');
    const studentIdField = document.getElementById('student_id');
    const schoolIdField = document.getElementById('school_id');
    
    if (role === 'student') {
        studentFields.style.display = 'block';
        studentIdField.required = true;
        schoolIdField.required = true;
    } else {
        studentFields.style.display = 'none';
        studentIdField.required = false;
        schoolIdField.required = false;
        studentIdField.value = '';
        schoolIdField.value = '';
    }
}

function toggleEditStudentFields() {
    const role = document.getElementById('edit_role').value;
    const studentFields = document.getElementById('editStudentFields');
    const studentIdField = document.getElementById('edit_student_id');
    const schoolIdField = document.getElementById('edit_school_id');
    
    if (role === 'student') {
        studentFields.style.display = 'block';
        studentIdField.required = true;
        schoolIdField.required = true;
    } else {
        studentFields.style.display = 'none';
        studentIdField.required = false;
        schoolIdField.required = false;
    }
}

function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_first_name').value = user.first_name;
    document.getElementById('edit_last_name').value = user.last_name;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_student_id').value = user.student_id || '';
    document.getElementById('edit_school_id').value = user.school_id || '';
    document.getElementById('edit_status').value = user.status;
    
    toggleEditStudentFields();
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete "${userName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize form validation
document.addEventListener('DOMContentLoaded', function() {
    toggleStudentFields();
    toggleEditStudentFields();
});
</script>

<?php require_once '../includes/footer.php'; ?>
