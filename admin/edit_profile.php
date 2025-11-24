<?php
$pageTitle = 'Edit Profile';
require_once '../config/config.php';
require_once '../includes/security.php';
requireRole('admin');

$userId = $_SESSION['user_id'];

// fetch current user info
$user = $db->fetchOne("SELECT id, first_name, last_name, email, username FROM users WHERE id = ? AND role = 'admin'", [$userId]);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // Basic validation
    if ($first === '') $errors[] = 'First name is required.';
    if ($last === '') $errors[] = 'Last name is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    // Check if username or email already exists (excluding current user)
    if ($username !== $user['username']) {
        $exists = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
        if ($exists) {
            $errors[] = 'Username already taken.';
        }
    }

    if ($email !== $user['email']) {
        $exists = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($exists) {
            $errors[] = 'Email already registered.';
        }
    }

    // Handle password change if requested
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        } elseif ($currentPassword === '') {
            $errors[] = 'Current password is required to set a new password.';
        } else {
            // Verify current password
            $currentUser = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
            if (!password_verify($currentPassword, $currentUser['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
    }

    if (empty($errors)) {
        // Prepare update data
        $updateData = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'username' => $username
        ];

        // Add password to update if changing
        if ($newPassword !== '') {
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Get old values before update
        $oldUser = $db->fetchOne("SELECT id, first_name, last_name, email, username FROM users WHERE id = ?", [$userId]);

        // Update user record
        $result = $db->query(
            "UPDATE users SET " . implode(" = ?, ", array_keys($updateData)) . " = ? WHERE id = ?",
            [...array_values($updateData), $userId]
        );

        if ($result) {
            $success = true;
            // Update session values
            $_SESSION['first_name'] = $first;
            $_SESSION['last_name'] = $last;
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;

            // Refresh user data
            $user = $db->fetchOne("SELECT id, first_name, last_name, email, username FROM users WHERE id = ?", [$userId]);

            // Track changes for before/after comparison
            $changes = [];
            if ($oldUser['first_name'] !== $first) {
                $changes['first_name'] = ['old' => $oldUser['first_name'], 'new' => $first];
            }
            if ($oldUser['last_name'] !== $last) {
                $changes['last_name'] = ['old' => $oldUser['last_name'], 'new' => $last];
            }
            if ($oldUser['email'] !== $email) {
                $changes['email'] = ['old' => $oldUser['email'], 'new' => $email];
            }
            if ($oldUser['username'] !== $username) {
                $changes['username'] = ['old' => $oldUser['username'], 'new' => $username];
            }
            if ($newPassword !== '') {
                $changes['password'] = ['old' => '***', 'new' => '***'];
            }

            // Notify admin and other system administrators about this profile update
            require_once __DIR__ . '/../config/mail.php';
            require_once __DIR__ . '/../templates/email/load_template.php';

            $otherAdmins = $db->fetchAll("SELECT * FROM users WHERE role = 'admin' AND id != ?", [$userId]);
            $updatedBy = $db->fetchOne("SELECT id, first_name, last_name, email FROM users WHERE id = ?", [$userId]);

            $emailData = [
                'user' => $user,
                'updatedBy' => $updatedBy,
                'userRole' => 'Administrator',
                'changes' => $changes,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Send email to the admin who made changes
            $adminEmailBody = load_email_template('profile_updated.php', $emailData);
            $subject = 'Your Profile Has Been Updated';
            sendMail($user['email'], $subject, $adminEmailBody);

            // Send email to other administrators
            $otherAdminsEmailBody = load_email_template('profile_updated_admin.php', $emailData);
            $otherAdminsSubject = 'Administrator Profile Updated: ' . $user['first_name'] . ' ' . $user['last_name'];

            foreach ($otherAdmins as $admin) {
                sendMail($admin['email'], $otherAdminsSubject, $otherAdminsEmailBody);
            }
        } else {
            $errors[] = 'Failed to update profile.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Profile</h5>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-1"></i>Profile updated successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="col-12">
                                <hr>
                                <h6>Change Password (optional)</h6>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                                <small class="text-muted">Required to change password</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" data-strength="true" minlength="8">
                                <small class="text-muted">Min. 8 characters</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>

                            <div class="col-12">
                                <hr>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Save Changes
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
