<?php
$pageTitle = 'Login';
require_once '../config/config.php';
require_once '../includes/security.php';

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
    
    // Check brute force protection
    if (isAccountLocked($username)) {
        $timeRemaining = getLockoutTimeRemaining($username);
        $minutes = ceil($timeRemaining / 60);
        $error = "Account temporarily locked due to too many failed attempts. Please try again in approximately $minutes minute(s).";
    } elseif (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        if ($loginType === 'school') {
            // School login
            $school = $db->fetchOne(
                "SELECT * FROM schools WHERE (email = ? OR code = ?) AND status = 'active'",
                [$username, $username]
            );
            
            if ($school && verifyPassword($password, $school['password'])) {
                // Record successful login
                recordSuccessfulLogin($username);
                
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp_code'] = $otp;
                $_SESSION['otp_expiry'] = time() + (5 * 60); // OTP valid for 5 minutes

                // Store temporary MFA data
                $_SESSION['mfa_id'] = $school['id'];
                $_SESSION['mfa_type'] = 'school';
                $_SESSION['mfa_email'] = $school['email'];
                $_SESSION['mfa_redirect_url'] = '../school/dashboard.php'; // Intended final redirect

                // Send OTP email
                require_once __DIR__ . '/../config/mail.php'; // Ensure mail.php is included
                $subject = "Your One-Time Password (OTP) for SafeSpeak";
                $body = "<p>Hello,</p><p>Your One-Time Password (OTP) for SafeSpeak login is: <strong>{$otp}</strong></p><p>This OTP is valid for 5 minutes. Please enter it on the verification page to complete your login.</p><p>If you did not attempt to log in, please ignore this email.</p>";
                sendMail($school['email'], $subject, $body);

                logActivity($db, $school['id'], 'school', 'otp_sent', 'OTP sent for school login');
                
                redirect('verify_otp.php', 'Please check your email for the OTP.', 'info');
            } else {
                // Record failed attempt
                recordFailedAttempt($username);
                $attempts = getFailedAttempts($username);
                $error = 'Invalid school credentials.';
                if ($attempts >= 3) {
                    $timeRemaining = getLockoutTimeRemaining($username);
                    $minutes = ceil($timeRemaining / 60);
                    $error .= " Account will be locked after 5 failed attempts. ($attempts/5 attempts)";
                }
            }
        } else {
            // Student/Admin login
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
                [$username, $username]
            );
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Record successful login
                recordSuccessfulLogin($username);
                
                if ($user['is_verified'] == 0) {
                    $error = 'Your account is not verified. Please check your email for the verification link.';
                    // Optionally, add a resend verification email link here.
                } else {
                    // Check if login type matches user role
                    if (($loginType === 'admin' && $user['role'] !== 'admin') ||
                        ($loginType === 'student' && $user['role'] !== 'student'))  {
                        recordFailedAttempt($username);
                        $error = 'Invalid credentials for this login type.';
                    } else {
                        // Generate OTP
                        $otp = rand(100000, 999999);
                        $_SESSION['otp_code'] = $otp;
                        $_SESSION['otp_expiry'] = time() + (5 * 60); // OTP valid for 5 minutes

                        // Store temporary MFA data
                        $_SESSION['mfa_id'] = $user['id'];
                        $_SESSION['mfa_type'] = $user['role'];
                        $_SESSION['mfa_email'] = $user['email'];
                        $_SESSION['mfa_redirect_url'] = ($user['role'] === 'admin' ? '../admin/dashboard.php' : '../student/dashboard.php'); // Intended final redirect

                        // Send OTP email
                        require_once __DIR__ . '/../config/mail.php'; // Ensure mail.php is included
                        $subject = "Your One-Time Password (OTP) for SafeSpeak";
                        $body = "<p>Hello,</p><p>Your One-Time Password (OTP) for SafeSpeak login is: <strong>{$otp}</strong></p><p>This OTP is valid for 5 minutes. Please enter it on the verification page to complete your login.</p><p>If you did not attempt to log in, please ignore this email.</p>";
                        sendMail($user['email'], $subject, $body);

                        logActivity($db, $user['id'], $user['role'], 'otp_sent', 'OTP sent for user login');
                        
                        redirect('verify_otp.php', 'Please check your email for the OTP.', 'info');
                    }
                }
            } else {
                // Record failed attempt
                recordFailedAttempt($username);
                $attempts = getFailedAttempts($username);
                $error = 'Invalid credentials.';
                if ($attempts >= 3) {
                    $timeRemaining = getLockoutTimeRemaining($username);
                    $minutes = ceil($timeRemaining / 60);
                    $error .= " Account will be locked after 5 failed attempts. ($attempts/5 attempts)";
                }
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
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            <label for="username"><?php echo $loginType === 'school' ? 'School Email or Code' : 'Username or Email'; ?></label>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                            <span class="position-absolute top-50 end-0 translate-middle-y pe-3 toggle-password" style="cursor: pointer;" data-target="#password">
                                <i class="fas fa-eye"></i>
                            </span>
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
document.addEventListener('DOMContentLoaded', function () {
    const passwordToggles = document.querySelectorAll('.toggle-password');

    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const passwordField = document.querySelector(this.getAttribute('data-target'));
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye-slash');
            this.querySelector('i').classList.toggle('fa-eye');
        });
    });
});
</script>




