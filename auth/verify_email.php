<?php
$pageTitle = 'Email Verification';
require_once '../config/config.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'danger';

if (empty($token)) {
    $message = 'Verification token not provided.';
} else {
    $user = $db->fetchOne("SELECT * FROM users WHERE verification_token = ?", [$token]);

    if (!$user) {
        $message = 'Invalid verification token.';
    } elseif (strtotime($user['verification_token_expires_at']) < time()) {
        $message = 'Verification token has expired. Please request a new one.';
        // Here you could add logic to resend the verification email
    } else {
        // Token is valid and not expired, verify the user
        $db->update('users', 
            [
                'is_verified' => 1,
                'verification_token' => null,
                'verification_token_expires_at' => null
            ],
            $user['id']
        );
        
        logActivity($db, $user['id'], $user['role'], 'email_verified', 'Email address verified successfully.');

        redirect('login.php', 'Email verified successfully! You can now log in.', 'success');
    }
}

require_once '../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4">Email Verification</h3>
                    <div class="alert alert-<?php echo $message_type; ?> text-center">
                        <?php echo $message; ?>
                    </div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
?>