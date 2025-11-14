<?php
require_once '../config/config.php';
requireRole('student');

$pageTitle = 'Edit Profile';
$userId = $_SESSION['user_id'];

// fetch current user (use id_photo_path column if present)
$user = $db->fetchOne("SELECT id, first_name, last_name, email, id_photo_path FROM users WHERE id = ?", [$userId]);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($first === '') $errors[] = 'First name is required.';
    if ($last === '') $errors[] = 'Last name is required.';
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    // handle file upload
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

        // update first/last/email
        $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?", [$first, $last, $email, $userId]);
        $success = true;
        // refresh user record
        $user = $db->fetchOne("SELECT id, first_name, last_name, email, id_photo_path FROM users WHERE id = ?", [$userId]);
        // update session values
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['id_photo'] = $user['id_photo_path'] ? basename($user['id_photo_path']) : '';
        $_SESSION['email'] = $user['email'];

        // Notify admins and school
        require_once '../config/mail.php';
        require_once '../templates/email/load_template.php';

        $studentUser = $db->fetchOne("SELECT u.*, s.name as school_name, s.email as school_email FROM users u JOIN schools s ON u.school_id = s.id WHERE u.id = ?", [$userId]);
        $admins = $db->fetchAll("SELECT * FROM users WHERE role = 'admin'");
        
        $emailData = [
            'user' => $studentUser,
            'updatedBy' => $studentUser,
            'userRole' => 'Student',
            'changes' => $changes,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $emailBody = load_email_template('profile_updated.php', $emailData);
        $subject = 'Student Profile Updated: ' . $studentUser['first_name'] . ' ' . $studentUser['last_name'];

        foreach ($admins as $admin) {
            sendMail($admin['email'], $subject, $emailBody);
        }

        if (!empty($studentUser['school_email'])) {
            sendMail($studentUser['school_email'], $subject, $emailBody);
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
                            <input type="file" name="id_photo" accept="image/*" class="form-control">
                            <?php $displayPhoto = $_SESSION['id_photo'] ?? $user['id_photo'] ?? ''; ?>
                            <?php if (!empty($displayPhoto) && file_exists(__DIR__ . '/../uploads/id_photos/' . $displayPhoto)): ?>
                                <div class="mt-2">
                                    <img src="<?php echo BASE_URL . '/uploads/id_photos/' . htmlspecialchars($displayPhoto); ?>" alt="ID Photo" style="max-width:120px;border-radius:8px;" />
                                </div>
                            <?php endif; ?>
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
