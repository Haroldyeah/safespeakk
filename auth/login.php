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
                    // ensure email and id photo are in session for header/profile display
                    $_SESSION['email'] = $user['email'] ?? '';
                    // DB stores full path in `id_photo_path` (e.g. 'uploads/id_photos/xxx.jpg')
                    $photoVal = $user['id_photo_path'] ?? $user['id_photo'] ?? '';
                    $_SESSION['id_photo'] = $photoVal ? basename($photoVal) : '';
                    
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="display-6 text-primary mb-2">
                            <i class="fas fa-<?php echo $loginType === 'school' ? 'school' : ($loginType === 'admin' ? 'user-shield' : 'user-graduate'); ?>"></i>
                        </div>
                        <h3 class="mb-0">
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
                        </h3>
                        <p class="text-muted small mb-0">Access your academic dashboard securely</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-semibold">
                                <?php echo $loginType === 'school' ? 'School Email or Code' : 'Username or Email'; ?>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Password</label>
                            <div class="input-group input-group-lg">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                            <?php if ($loginType === 'student'): ?>
                                <a href="forgot_password.php" class="small">Forgot password?</a>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mb-3">
                        <?php if ($loginType === 'student'): ?>
                            <p class="mb-2 small">Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i>Register Now
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="text-center">
                        <div class="btn-group" role="group" aria-label="Login types">
                            <a href="login.php?type=student" class="btn btn-sm <?php echo $loginType === 'student' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Student</a>
                            <a href="login.php?type=school" class="btn btn-sm <?php echo $loginType === 'school' ? 'btn-primary' : 'btn-outline-secondary'; ?>">School</a>
                            <a href="login.php?type=admin" class="btn btn-sm <?php echo $loginType === 'admin' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Admin</a>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="../index.php" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password toggle
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
