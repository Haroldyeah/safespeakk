<?php
$pageTitle = 'Student Registration';
require_once '../config/config.php';

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

$error = '';
$success = '';

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $studentId = sanitizeInput($_POST['student_id']);
    $schoolId = (int)$_POST['school_id'];
    
    // Handle ID photo upload
    $idPhotoPath = null;
    if (isset($_FILES['id_photo']) && $_FILES['id_photo']['error'] == 0) {
        $uploadDir = '../uploads/id_photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['id_photo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = $username . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['id_photo']['tmp_name'], $targetPath)) {
                $idPhotoPath = 'uploads/id_photos/' . $fileName;
            } else {
                $error = 'Failed to upload ID photo.';
            }
        } else {
            $error = 'ID photo must be a JPG, JPEG, or PNG file.';
        }
    }
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($studentId) || empty($schoolId)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isset($_FILES['id_photo']) || $_FILES['id_photo']['error'] != 0) {
        $error = 'Please upload your student ID photo.';
    } else {
        // Check if username or email already exists
        $existingUser = $db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existingUser) {
            $error = 'Username or email already exists.';
        } else {
            // Check if school exists
            $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$schoolId]);
            
            if (!$school) {
                $error = 'Selected school is not valid.';
            } else {
                $verificationToken = bin2hex(random_bytes(32));
                $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                // Create user account
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'password' => hashPassword($password),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => 'student',
                    'student_id' => $studentId,
                    'school_id' => $schoolId,
                    'id_photo_path' => $idPhotoPath,
                    'is_verified' => 0,
                    'verification_token' => $verificationToken,
                    'verification_token_expires_at' => $tokenExpires
                ];
                
                $userId = $db->insert('users', $userData);
                
                if ($userId) {
                    logActivity($db, $userId, 'student', 'register', 'New student account created, verification pending.');

                    // Send verification email
                    require_once __DIR__ . '/../config/mail.php';
                    require_once __DIR__ . '/../templates/email/load_template.php';
                    $verificationLink = BASE_URL . 'auth/verify_email.php?token=' . $verificationToken;
                    
                    $subject = 'Verify Your Email for ' . APP_NAME;
                    $body = "<p>Hello " . htmlspecialchars($firstName) . ",</p>"
                          . "<p>Thank you for registering. Please click the link below to verify your email address:</p>"
                          . "<p><a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>"
                          . "<p>This link will expire in 24 hours.</p>"
                          . "<p>If you did not create this account, please ignore this email.</p>";

                    sendMail($email, $subject, $body);

                    redirect('login.php', 'Account created successfully! Please check your email to complete your registration.', 'success');
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3 display-6 text-primary"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <h3 class="mb-0">Student Registration</h3>
                            <small class="text-muted">Create your academic account</small>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label small fw-semibold">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label small fw-semibold">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="student_id" class="form-label small fw-semibold">Student ID *</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($studentId ?? ''); ?>" placeholder="e.g., 2024-001234" required>
                            </div>
                            <div class="col-md-6">
                                <label for="school_id" class="form-label small fw-semibold">School *</label>
                                <select class="form-select" id="school_id" name="school_id" required>
                                    <option value="">Select your school</option>
                                    <?php foreach ($schools as $school): ?>
                                        <option value="<?php echo $school['id']; ?>" <?php echo (isset($schoolId) && $schoolId == $school['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($school['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12"><hr></div>

                            <div class="col-md-6">
                                <label for="username" class="form-label small fw-semibold">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-semibold">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6 position-relative">
                                <label for="password" class="form-label small fw-semibold">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                                <span class="position-absolute top-50 end-0 translate-middle-y pe-3 toggle-password" style="cursor: pointer; margin-top: 12px;" data-target="#password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for="confirm_password" class="form-label small fw-semibold">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                <span class="position-absolute top-50 end-0 translate-middle-y pe-3 toggle-password" style="cursor: pointer; margin-top: 12px;" data-target="#confirm_password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>

                            <div class="col-md-6">
                                <label for="id_photo" class="form-label small fw-semibold">Student ID Photo *</label>
                                <input type="file" class="form-control" id="id_photo" name="id_photo" accept="image/jpeg,image/jpg,image/png" required>
                                <div class="form-text">Upload a clear photo of your student ID (JPG, JPEG, or PNG)</div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="w-100 text-center">
                                    <img id="idPreview" src="../uploads/6890110659703_pexels-panditwiguna-3401403.jpg" alt="ID preview" class="img-fluid rounded border" style="max-height:140px; object-fit:cover;">
                                    <div class="small text-muted mt-2">ID preview</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label small" for="terms">I agree to the <a href="#" class="text-primary">Terms</a> and <a href="#" class="text-primary">Privacy Policy</a> *</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-user-plus me-2"></i>Create Account</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2 small">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-sign-in-alt me-1"></i>Sign In</a>
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
// Password confirmation validation and ID preview
const confirmInput = document.getElementById('confirm_password');
const passwordInput = document.getElementById('password');
confirmInput && confirmInput.addEventListener('input', function() {
    if (passwordInput.value !== this.value) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

const idPhoto = document.getElementById('id_photo');
const preview = document.getElementById('idPreview');
if (idPhoto) {
    idPhoto.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            preview.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
}
</script>
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


