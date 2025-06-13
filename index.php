<?php
$pageTitle = 'Welcome';
require_once 'config/config.php';

// Redirect based on user status
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'student':
            header('Location: student/dashboard.php');
            exit;
        case 'school':
            header('Location: school/dashboard.php');
            exit;
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>Welcome to Capstone System</h2>
            <p>Managing academic excellence through digital innovation</p>
        </div>
        
        <div class="d-grid gap-3">
            <a href="auth/login.php" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            <a href="auth/register.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-user-plus me-2"></i>Register as Student
            </a>
        </div>
        
        <div class="text-center mt-4">
            <hr class="my-4">
            <p class="text-muted mb-2">For Schools and Administrators</p>
            <a href="auth/login.php?type=school" class="btn btn-outline-secondary me-2">
                <i class="fas fa-school me-1"></i>School Login
            </a>
            <a href="auth/login.php?type=admin" class="btn btn-outline-secondary">
                <i class="fas fa-user-shield me-1"></i>Admin Login
            </a>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Secure • Reliable • Academic-Focused
            </small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
