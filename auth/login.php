<?php
$pageTitle = 'Login';
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'student':
            header('Location: ../student/dashboard.php');
            exit;
        case 'school':
            header('Location: ../school/dashboard.php');
            exit;
        case 'admin':
            header('Location: ../admin/dashboard.php');
            exit;
    }
}

$loginType = $_GET['type'] ?? 'student';
$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        if ($loginType === 'school') {
            // School login
            $school = $db->fetchOne(
                "SELECT * FROM schools WHERE (email = ? OR code = ?) AND status = 'active'",
                [$username, $username]
            );
            
            if ($school && verifyPassword($password, $school['password'])) {
                $_SESSION['school_id'] = $school['id'];
                $_SESSION['school_name'] = $school['name'];
                $_SESSION['school_code'] = $school['code'];
                
                logActivity($db, $school['id'], 'school', 'login', 'School login successful');
                
                redirect('../school/dashboard.php', 'Welcome back!', 'success');
            } else {
                $error = 'Invalid school credentials.';
            }
        } else {
            // Student/Admin login
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
                [$username, $username]
            );
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Check if login type matches user role
                if (($loginType === 'admin' && $user['role'] !== 'admin') ||
                    ($loginType === 'student' && $user['role'] !== 'student')) {
                    $error = 'Invalid credentials for this login type.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['school_id'] = $user['school_id'];
                    
                    logActivity($db, $user['id'], $user['role'], 'login', 'User login successful');
                    
                    $redirectUrl = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../student/dashboard.php';
                    redirect($redirectUrl, 'Welcome back, ' . $user['first_name'] . '!', 'success');
                }
            } else {
                $error = 'Invalid credentials.';
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-<?php echo $loginType === 'school' ? 'school' : ($loginType === 'admin' ? 'user-shield' : 'user-graduate'); ?>"></i>
            </div>
            <h2>
                <?php 
                switch ($loginType) {
                    case 'school':
                        echo 'School Login';
                        break;
                    case 'admin':
                        echo 'Administrator Login';
                        break;
                    default:
                        echo 'Student Login';
                }
                ?>
            </h2>
            <p>Access your academic dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="username" class="form-label">
                    <?php echo $loginType === 'school' ? 'School Email or Code' : 'Username or Email'; ?>
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                <div class="invalid-feedback">
                    Please enter your <?php echo $loginType === 'school' ? 'school email or code' : 'username or email'; ?>.
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback">
                    Please enter your password.
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <?php if ($loginType === 'student'): ?>
                <p class="mb-2">Don't have an account?</p>
                <a href="register.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-1"></i>Register Now
                </a>
            <?php endif; ?>
            
            <hr class="my-3">
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="login.php?type=student" class="btn btn-sm <?php echo $loginType === 'student' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    Student
                </a>
                <a href="login.php?type=school" class="btn btn-sm <?php echo $loginType === 'school' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    School
                </a>
                <a href="login.php?type=admin" class="btn btn-sm <?php echo $loginType === 'admin' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    Admin
                </a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="../index.php" class="text-muted">
                <i class="fas fa-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
