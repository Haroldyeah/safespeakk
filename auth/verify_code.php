<?php
$pageTitle = 'Verify Reset Code';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';

// Prefill email from query if present
$error = '';
$message = '';
$prefillEmail = '';
if (!empty($_GET['email'])) {
    $prefillEmail = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prefer POST email, but fallback to prefilled
    $email = sanitizeInput($_POST['email'] ?? $prefillEmail);
    $code = sanitizeInput($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Please enter the 6-digit code sent to your email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Find user
        $user = $db->fetchOne('SELECT id, first_name, email FROM users WHERE email = ? AND status = "active"', [$email]);
        if (!$user) {
            $error = 'Invalid email or code.';
        } else {
            // Get latest valid reset entry for this user
            $reset = $db->fetchOne('SELECT id, token, expires_at FROM password_resets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1', [$user['id']]);
            if (!$reset) {
                $error = 'No reset request found for this account.';
            } elseif (strtotime($reset['expires_at']) < time()) {
                // delete expired token
                $db->delete('password_resets', 'id = ?', [$reset['id']]);
                $error = 'The reset code has expired. Please request a new one.';
            } else {
                // Verify code
                if (!password_verify($code, $reset['token'])) {
                    $error = 'Invalid reset code.';
                } else {
                    // Update password using project's helper
                    $hashed = hashPassword($password);
                    // Use named parameter for WHERE to match Database::update handling
                    $updated = $db->update('users', ['password' => $hashed], 'id = :id', ['id' => $user['id']]);
                    // Delete used reset entries for this user
                    $db->delete('password_resets', 'user_id = ?', [$user['id']]);

                    // Send confirmation email
                    $subject = APP_NAME . ' - Password Changed';
                    $body = "<div style='font-family:Arial,Helvetica,sans-serif;color:#333;'>"
                          . "<p>Hello " . htmlspecialchars($user['first_name']) . ",</p>"
                          . "<p>Your password was successfully changed. If you did not perform this action, please contact your school administrator immediately.</p>"
                          . "<p style='font-size:12px;color:#666;'>Regards,<br/>" . APP_NAME . " Team</p></div>";
                    sendMail($user['email'], $subject, $body);

                    $message = 'Your password has been updated. You can now log in.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Enter Reset Code & Set New Password</h3>
                    <p class="text-muted">Enter the 6-digit code we sent to your email, then choose a new password.</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small">Email address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($prefillEmail ?? ''); ?>" required readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">6-digit code</label>
                                <input type="text" name="code" class="form-control" pattern="\d{6}" maxlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">New Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary">Set Password</button>
                                <a href="login.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


