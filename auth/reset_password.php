<?php
$pageTitle = 'Reset Password';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = '';
$message = '';

if (!$token) {
    redirect('login.php', 'Invalid or missing reset token', 'error');
}

// Find token
$reset = $db->fetchOne("SELECT pr.id, pr.user_id, pr.expires_at, u.email, u.first_name FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ?", [$token]);
if (!$reset) {
    redirect('login.php', 'Invalid or expired reset token', 'error');
}

// Check expiry
if (strtotime($reset['expires_at']) < time()) {
    // delete expired token
    $db->delete('password_resets', 'id = ?', [$reset['id']]);
    redirect('login.php', 'Reset link has expired', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db->update('users', ['password' => $hashed], 'id = ?', ['id' => $reset['user_id']]);
        // Delete token
        $db->delete('password_resets', 'id = ?', [$reset['id']]);
        // Send confirmation email
        $subject = APP_NAME . ' - Password Changed';
        $body = "<div style='font-family:Arial,Helvetica,sans-serif;color:#333;'>"
              . "<p>Hello " . htmlspecialchars($reset['first_name']) . ",</p>"
              . "<p>Your password was successfully changed. If you did not perform this action, please contact your school administrator immediately.</p>"
              . "<p style='font-size:12px;color:#666;'>Regards,<br/>" . APP_NAME . " Team</p></div>";
        sendMail($reset['email'], $subject, $body);
        $message = 'Your password has been updated. You can now log in.';
    }
}

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3">Set a New Password</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
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

<?php require_once '../includes/footer.php'; ?>
