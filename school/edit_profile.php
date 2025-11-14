<?php
$pageTitle = 'Edit School Profile';
require_once '../config/config.php';
requireRole('school');

$schoolId = $_SESSION['school_id'];

// fetch current school info
$school = $db->fetchOne("SELECT * FROM schools WHERE id = ?", [$schoolId]);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // Basic validation
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if ($contact_person === '') $errors[] = 'Contact person is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($address === '') $errors[] = 'Address is required.';

    // Check if email already exists (excluding current school)
    if ($email !== $school['email']) {
        $exists = $db->fetchOne("SELECT id FROM schools WHERE email = ? AND id != ?", [$email, $schoolId]);
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
            if (!password_verify($currentPassword, $school['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }
    }

    if (empty($errors)) {
        // Prepare update data
        $updateData = [
            'email' => $email,
            'contact_person' => $contact_person,
            'phone' => $phone,
            'address' => $address
        ];

        // Add password to update if changing
        if ($newPassword !== '') {
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Build the SQL query
        $sql = "UPDATE schools SET ";
        $params = [];
        foreach ($updateData as $key => $value) {
            $sql .= "$key = ?, ";
            $params[] = $value;
        }
        $sql = rtrim($sql, ", ") . " WHERE id = ?";
        $params[] = $schoolId;

        // Track old values before update
        $oldSchool = $db->fetchOne("SELECT * FROM schools WHERE id = ?", [$schoolId]);

        // Execute update
        $result = $db->query($sql, $params);

        if ($result) {
            $success = true;
            // Update session values
            $_SESSION['email'] = $email;
            $_SESSION['contact_person'] = $contact_person;

            // Refresh school data
            $school = $db->fetchOne("SELECT * FROM schools WHERE id = ?", [$schoolId]);

            // Track changes for before/after comparison
            $changes = [];
            if ($oldSchool['email'] !== $email) {
                $changes['email'] = ['old' => $oldSchool['email'], 'new' => $email];
            }
            if ($oldSchool['contact_person'] !== $contact_person) {
                $changes['contact_person'] = ['old' => $oldSchool['contact_person'], 'new' => $contact_person];
            }
            if ($oldSchool['phone'] !== $phone) {
                $changes['phone'] = ['old' => $oldSchool['phone'], 'new' => $phone];
            }
            if ($oldSchool['address'] !== $address) {
                $changes['address'] = ['old' => $oldSchool['address'], 'new' => $address];
            }

            // Notify admins
            require_once '../config/mail.php';
            require_once '../templates/email/load_template.php';

            $admins = $db->fetchAll("SELECT * FROM users WHERE role = 'admin'");
            $updatedBy = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            
            $emailData = [
                'user' => $school,
                'updatedBy' => $updatedBy,
                'userRole' => 'School',
                'changes' => $changes,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $emailBody = load_email_template('profile_updated.php', $emailData);
            $subject = 'School Profile Updated: ' . $school['name'];

            foreach ($admins as $admin) {
                sendMail($admin['email'], $subject, $emailBody);
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
                    <h5 class="mb-0">Edit School Profile</h5>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-1"></i>School profile updated successfully.
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
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                <hr>
                            </div>

                            <div class="col-12">
                                <label class="form-label">School Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($school['name']); ?>" disabled>
                                <small class="text-muted">Contact system administrator to change school name</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">School Code</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($school['code']); ?>" disabled>
                                <small class="text-muted">School code cannot be changed</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($school['email']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($school['contact_person']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($school['phone']); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($school['address']); ?></textarea>
                            </div>



                            <!-- Password Change -->
                            <div class="col-12 mt-4">
                                <h6><i class="fas fa-lock me-2"></i>Change Password (Optional)</h6>
                                <hr>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                                <small class="text-muted">Required to change password</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control">
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
