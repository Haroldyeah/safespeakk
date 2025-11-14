<?php
$pageTitle = 'Verify OTP';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php'; // For resending OTP

// Check if user is supposed to be here (i.e., came from login.php)
if (!isset($_SESSION['otp_code']) || !isset($_SESSION['mfa_id']) || !isset($_SESSION['mfa_type']) || !isset($_SESSION['mfa_email']) || !isset($_SESSION['mfa_redirect_url'])) {
    redirect('login.php', 'Please log in first.', 'error');
}

// Check OTP expiry
if (time() > $_SESSION['otp_expiry']) {
    // Clear expired OTP data
    unset($_SESSION['otp_code'], $_SESSION['otp_expiry'], $_SESSION['mfa_id'], $_SESSION['mfa_type'], $_SESSION['mfa_email'], $_SESSION['mfa_redirect_url']);
    redirect('login.php', 'Your OTP has expired. Please try logging in again.', 'error');
}

$error = '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify_otp') {
        $enteredOtp = sanitizeInput($_POST['otp']);

        if (empty($enteredOtp)) {
            $error = 'Please enter the OTP.';
        } elseif ($enteredOtp != $_SESSION['otp_code']) {
            $error = 'Invalid OTP. Please try again.';
            logActivity($db, $_SESSION['mfa_id'], $_SESSION['mfa_type'], 'otp_failed', 'Invalid OTP entered');
        } else {
            // OTP is valid and not expired
            // Retrieve user/school details from DB using mfa_id and mfa_type
            $entity = null;
            if ($_SESSION['mfa_type'] === 'school') {
                $entity = $db->fetchOne("SELECT * FROM schools WHERE id = ?", [$_SESSION['mfa_id']]);
            } else { // student or admin
                $entity = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['mfa_id']]);
            }

            if ($entity) {
                // Set actual session variables based on user type
                if ($_SESSION['mfa_type'] === 'school') {
                    $_SESSION['school_id'] = $entity['id'];
                    $_SESSION['school_name'] = $entity['name'];
                    $_SESSION['school_code'] = $entity['code'];
                } else { // student or admin
                    $_SESSION['user_id'] = $entity['id'];
                    $_SESSION['username'] = $entity['username'];
                    $_SESSION['first_name'] = $entity['first_name'];
                    $_SESSION['last_name'] = $entity['last_name'];
                    $_SESSION['role'] = $entity['role'];
                    $_SESSION['school_id'] = $entity['school_id'] ?? null; // Students/Admins might have school_id
                    $_SESSION['email'] = $entity['email'] ?? '';
                    $photoVal = $entity['id_photo_path'] ?? $entity['id_photo'] ?? '';
                    $_SESSION['id_photo'] = $photoVal ? basename($photoVal) : '';
                }
                
                logActivity($db, $entity['id'], $_SESSION['mfa_type'], 'login_success_mfa', 'MFA successful');
                
                $redirectUrl = $_SESSION['mfa_redirect_url'];

                // Clear MFA session data
                unset($_SESSION['otp_code'], $_SESSION['otp_expiry'], $_SESSION['mfa_id'], $_SESSION['mfa_type'], $_SESSION['mfa_email'], $_SESSION['mfa_redirect_url']);

                // Set MFA verified flag
                $_SESSION['mfa_verified'] = true;

                redirect($redirectUrl, 'Login successful!', 'success');

            } else {
                $error = 'User data not found. Please try logging in again.';
                logActivity($db, $_SESSION['mfa_id'], $_SESSION['mfa_type'], 'otp_failed', 'User data not found after OTP verification');
            }
        }
    } elseif ($action === 'resend_otp') {
        // Generate new OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_expiry'] = time() + (5 * 60); // New OTP valid for 5 minutes

        // Send new OTP email
        $subject = "Your New One-Time Password (OTP) for SafeSpeak";
        $body = "<p>Hello,</p><p>Your new One-Time Password (OTP) for SafeSpeak login is: <strong>{$otp}</strong></p><p>This OTP is valid for 5 minutes. Please enter it on the verification page to complete your login.</p><p>If you did not attempt to log in, please ignore this email.</p>";
        sendMail($_SESSION['mfa_email'], $subject, $body);

        logActivity($db, $_SESSION['mfa_id'], $_SESSION['mfa_type'], 'otp_resend', 'OTP resent');
        $message = 'A new OTP has been sent to your email.';
        $messageType = 'info';
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
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="mb-0">Verify Your Login</h3>
                        <p class="text-muted small mb-0">A One-Time Password (OTP) has been sent to your email.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="verify_otp">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" required maxlength="6">
                            <label for="otp">Enter OTP</label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Verify OTP
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <form method="POST">
                            <input type="hidden" name="action" value="resend_otp">
                            <button type="submit" class="btn btn-link text-muted small">Resend OTP</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>