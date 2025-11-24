<?php
require_once '../config/config.php';
require_once '../includes/security.php';
requireRole('student');

$pageTitle = 'Edit Profile';
$userId = $_SESSION['user_id'];

// fetch current user (use id_photo_path column if present)
$user = $db->fetchOne("SELECT id, first_name, last_name, email, password, id_photo_path FROM users WHERE id = ?", [$userId]);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($first === '') $errors[] = 'First name is required.';
    if ($last === '') $errors[] = 'Last name is required.';
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    // Validate password change if provided
    if ($newPassword !== '' || $confirmPassword !== '') {
        if ($currentPassword === '') {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($currentPassword, $user['password'] ?? '')) {
            $errors[] = 'Current password is incorrect.';
        } elseif ($newPassword === '') {
            $errors[] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    // handle file upload
    $photoChanged = false;
    $oldPhotoPath = $user['id_photo_path'] ?? '';
    
    if (!empty($_FILES['id_photo']) && $_FILES['id_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['id_photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error.';
        } elseif (!in_array($file['type'], $allowed)) {
            $errors[] = 'Only JPG and PNG image types are allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'File too large. Max 10MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = 'id_' . $userId . '_' . time() . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/id_photos';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $dest = $destDir . DIRECTORY_SEPARATOR . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Failed to move uploaded file.';
            } else {
                $photoChanged = true;
                // Delete old photo file if it exists
                if (!empty($user['id_photo_path'])) {
                    $oldBase = basename($user['id_photo_path']);
                    $oldFile = $destDir . DIRECTORY_SEPARATOR . $oldBase;
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                // store DB path and session basename
                $dbPath = 'uploads/id_photos/' . $safeName;
                $db->query("UPDATE users SET id_photo_path = ? WHERE id = ?", [$dbPath, $userId]);
                $_SESSION['id_photo'] = $safeName;
            }
        }
    }

    if (empty($errors)) {
        // Track changes for before/after comparison
        $changes = [];
        if ($user['first_name'] !== $first) {
            $changes['first_name'] = ['old' => $user['first_name'], 'new' => $first];
        }
        if ($user['last_name'] !== $last) {
            $changes['last_name'] = ['old' => $user['last_name'], 'new' => $last];
        }
        if ($user['email'] !== $email) {
            $changes['email'] = ['old' => $user['email'], 'new' => $email];
        }
        if ($newPassword !== '') {
            $changes['password'] = ['old' => '***', 'new' => '***'];
        }
        if ($photoChanged) {
            $changes['id_photo'] = ['old' => $oldPhotoPath, 'new' => 'uploads/id_photos/' . basename($_SESSION['id_photo'] ?? '')];
        }

        // Prepare update data
        $updateData = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email
        ];

        // Add password to update if changing
        if ($newPassword !== '') {
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Build the SQL query
        $sql = "UPDATE users SET ";
        $params = [];
        foreach ($updateData as $key => $value) {
            $sql .= "$key = ?, ";
            $params[] = $value;
        }
        $sql = rtrim($sql, ", ") . " WHERE id = ?";
        $params[] = $userId;

        // Execute update
        $db->query($sql, $params);
        $success = true;
        
        // refresh user record
        $user = $db->fetchOne("SELECT id, first_name, last_name, email, id_photo_path, password FROM users WHERE id = ?", [$userId]);
        // update session values
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['id_photo'] = $user['id_photo_path'] ? basename($user['id_photo_path']) : '';
        $_SESSION['email'] = $user['email'];

        // Notify student, admins, and school
        require_once '../config/mail.php';
        require_once '../templates/email/load_template.php';

        $studentUser = $db->fetchOne("SELECT u.*, s.name as school_name, s.email as school_email FROM users u JOIN schools s ON u.school_id = s.id WHERE u.id = ?", [$userId]);
        $admins = $db->fetchAll("SELECT * FROM users WHERE role = 'admin'");
        
        // Prepare attachments for emails with photo changes
        $attachments = [];
        if ($photoChanged) {
            // Add old photo if it exists
            if (!empty($oldPhotoPath) && file_exists(__DIR__ . '/../' . $oldPhotoPath)) {
                $attachments[] = [
                    'path' => __DIR__ . '/../' . $oldPhotoPath,
                    'name' => 'old_photo_' . $userId . '.jpg'
                ];
            }
            // Add new photo if it exists
            if (!empty($studentUser['id_photo_path']) && file_exists(__DIR__ . '/../' . $studentUser['id_photo_path'])) {
                $attachments[] = [
                    'path' => __DIR__ . '/../' . $studentUser['id_photo_path'],
                    'name' => 'new_photo_' . $userId . '.jpg'
                ];
            }
        }
        
        $emailData = [
            'user' => $studentUser,
            'updatedBy' => $studentUser,
            'userRole' => 'Student',
            'changes' => $changes,
            'timestamp' => date('Y-m-d H:i:s'),
            'oldPhotoPath' => $oldPhotoPath,
            'newPhotoPath' => $studentUser['id_photo_path'] ?? '',
            'photoChanged' => $photoChanged
        ];

        // Send email to the student
        $studentEmailBody = load_email_template('profile_updated.php', $emailData);
        $subject = 'Your Profile Has Been Updated';
        sendMail($studentUser['email'], $subject, $studentEmailBody, null, null, $attachments);

        // Send email to admins
        $adminEmailBody = load_email_template('profile_updated_admin.php', $emailData);
        $adminSubject = 'Student Profile Updated: ' . $studentUser['first_name'] . ' ' . $studentUser['last_name'];
        foreach ($admins as $admin) {
            sendMail($admin['email'], $adminSubject, $adminEmailBody, null, null, $attachments);
        }

        // Send email to school
        if (!empty($studentUser['school_email'])) {
            $schoolEmailBody = load_email_template('profile_updated_admin.php', $emailData);
            $schoolSubject = 'Student Profile Updated: ' . $studentUser['first_name'] . ' ' . $studentUser['last_name'];
            sendMail($studentUser['school_email'], $schoolSubject, $schoolEmailBody, null, null, $attachments);
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Profile</div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">Profile updated successfully.</div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul>
                            <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                        </ul></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">First name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? $_SESSION['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID Photo (JPG/PNG, max 10MB)</label>
                            <input type="file" name="id_photo" accept="image/*" class="form-control" id="idPhotoInput">
                            
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Current Photo</h6>
                                        <?php $displayPhoto = $_SESSION['id_photo'] ?? $user['id_photo_path'] ?? ''; ?>
                                        <?php if (!empty($displayPhoto) && file_exists(__DIR__ . '/../uploads/id_photos/' . basename($displayPhoto))): ?>
                                            <img id="currentPhoto" src="<?php echo BASE_URL . '/uploads/id_photos/' . htmlspecialchars(basename($displayPhoto)); ?>" alt="Current ID Photo" style="max-width:150px;border-radius:8px;border:2px solid #ddd;padding:5px;" />
                                        <?php else: ?>
                                            <p class="text-muted">No photo uploaded yet</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Preview of New Photo</h6>
                                        <img id="newPhotoPreview" src="#" alt="New ID Photo Preview" style="max-width:150px;border-radius:8px;border:2px solid #28a745;padding:5px;display:none;" />
                                        <p id="noPhotoSelected" class="text-muted">No new photo selected</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.getElementById('idPhotoInput').addEventListener('change', function(e) {
                                const file = e.target.files[0];
                                const preview = document.getElementById('newPhotoPreview');
                                const noPhoto = document.getElementById('noPhotoSelected');
                                
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = function(event) {
                                        preview.src = event.target.result;
                                        preview.style.display = 'block';
                                        noPhoto.style.display = 'none';
                                    };
                                    reader.readAsDataURL(file);
                                } else {
                                    preview.style.display = 'none';
                                    noPhoto.style.display = 'block';
                                }
                            });
                        </script>

                        <hr>
                        <h6>Change Password (Optional)</h6>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password" data-strength="true" minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Save</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
