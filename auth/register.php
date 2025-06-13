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
                    'id_photo_path' => $idPhotoPath
                ];
                
                $userId = $db->insert('users', $userData);
                
                if ($userId) {
                    logActivity($db, $userId, 'student', 'register', 'New student account created');
                    
                    $success = 'Account created successfully! You can now log in.';
                    
                    // Auto-login the user
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name'] = $lastName;
                    $_SESSION['role'] = 'student';
                    $_SESSION['school_id'] = $schoolId;
                    
                    redirect('../student/dashboard.php', 'Welcome to the Capstone System, ' . $firstName . '!', 'success');
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card" style="max-width: 550px;">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h2>Student Registration</h2>
            <p>Create your academic account</p>
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
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter your first name.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter your last name.</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="student_id" class="form-label">Student ID *</label>
                <input type="text" class="form-control" id="student_id" name="student_id" 
                       value="<?php echo htmlspecialchars($studentId ?? ''); ?>" 
                       placeholder="e.g., 2024-001234" required>
                <div class="invalid-feedback">Please enter your student ID.</div>
            </div>
            
            <div class="form-group">
                <label for="school_id" class="form-label">School *</label>
                <select class="form-select" id="school_id" name="school_id" required>
                    <option value="">Select your school</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" 
                                <?php echo (isset($schoolId) && $schoolId == $school['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select your school.</div>
            </div>
            
            <hr class="my-3">
            
            <div class="form-group">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                       pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores" required>
                <div class="invalid-feedback">Please enter a valid username (letters, numbers, and underscores only).</div>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="6" required>
                        <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="6" required>
                        <div class="invalid-feedback">Please confirm your password.</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="id_photo" class="form-label">Student ID Photo *</label>
                <input type="file" class="form-control" id="id_photo" name="id_photo" 
                       accept="image/jpeg,image/jpg,image/png" required>
                <div class="form-text">Upload a clear photo of your student ID (JPG, JPEG, or PNG format, max 5MB)</div>
                <div class="invalid-feedback">Please upload your student ID photo.</div>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a> *
                </label>
                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="mb-2">Already have an account?</p>
            <a href="login.php" class="btn btn-outline-primary">
                <i class="fas fa-sign-in-alt me-1"></i>Sign In
            </a>
        </div>
        
        <div class="text-center mt-3">
            <a href="../index.php" class="text-muted">
                <i class="fas fa-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
