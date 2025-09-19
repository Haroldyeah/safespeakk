<?php
$pageTitle = 'Forgot Password';
require_once '../config/config.php';
require_once __DIR__ . '/../config/mail.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Find user by email
        $user = $db->fetchOne("SELECT id, first_name, email FROM users WHERE email = ? AND status = 'active'", [$email]);
        if (!$user) {
            // Do not reveal whether the email exists
            $message = 'If an account with that email exists, a reset code will be sent.';
        } else {
            // Ensure password_resets table exists
            $db->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(256) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Generate 6-digit code
            try {
                $code = random_int(100000, 999999);
            } catch (Exception $e) {
                $code = mt_rand(100000, 999999);
            }

            // Hash the code before storing
            $hashedCode = password_hash((string)$code, PASSWORD_DEFAULT);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Insert code (hashed) into password_resets
            $inserted = $db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => $hashedCode,
                'expires_at' => $expiresAt
            ]);

        // Prepare a professional email with the numeric code only (no website link)
        $subject = APP_NAME . ' - Password Reset Code';
        $body = "<div style='font-family:Arial,Helvetica,sans-serif;color:#333;'>"
            . "<h3 style='color:#0d6efd;margin-bottom:8px;'>Password Reset Code</h3>"
            . "<p>Hi " . htmlspecialchars($user['first_name']) . ",</p>"
            . "<p>We received a request to reset the password for your account at " . htmlspecialchars(APP_NAME) . ". Use the 6-digit code below to securely reset your password. This code will expire in 1 hour.</p>"
            . "<div style='text-align:center;margin:18px 0;'>"
            . "<span style='display:inline-block;padding:12px 18px;background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;font-size:22px;letter-spacing:6px;font-weight:700;color:#111;'>" . $code . "</span>"
            . "</div>"
            . "<p>If you did not request this, you can safely ignore this email or contact your school administrator.</p>"
            . "<p style='font-size:12px;color:#666;margin-top:12px;'>Regards,<br/>" . htmlspecialchars(APP_NAME) . " Team</p>"
            . "</div>";

            sendMail($user['email'], $subject, $body);

            // Redirect user to verify_code page (prefill email). Show generic message via flash so we don't reveal account existence.
            redirect('verify_code.php?email=' . urlencode($email), 'If an account with that email exists, a 6-digit reset code has been sent. Enter the code to reset your password.', 'info');
        }
        // If user was not found we still redirect to the code entry page for UX consistency
        if (!$user) {
            redirect('verify_code.php?email=' . urlencode($email), 'If an account with that email exists, a 6-digit reset code has been sent. Enter the code to reset your password.', 'info');
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
                    <h3 class="mb-3">Forgot Password</h3>
                    <p class="text-muted">Enter the email address associated with your account and we'll send a 6-digit code to your email that you can use to reset your password.</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label small">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Send Reset Code</button>
                            <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


