<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-graduation-cap me-2"></i>
                Capstone System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php 
                    $role = getUserRole();
                    $baseUrl = BASE_URL;
                    ?>
                    
                    <?php if ($role === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/student/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/student/submit_report.php">
                                <i class="fas fa-plus me-1"></i>Submit Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/student/my_reports.php">
                                <i class="fas fa-file-alt me-1"></i>My Reports
                            </a>
                        </li>
                    <?php elseif ($role === 'school'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/school/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/school/view_reports.php">
                                <i class="fas fa-file-alt me-1"></i>Reports
                            </a>
                        </li>
                    <?php elseif ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/admin/all_reports.php">
                                <i class="fas fa-file-alt me-1"></i>All Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/admin/manage_schools.php">
                                <i class="fas fa-school me-1"></i>Schools
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/admin/manage_users.php">
                                <i class="fas fa-users me-1"></i>Users
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php 
                            if ($role === 'school') {
                                echo 'School Admin';
                            } else {
                                echo $_SESSION['first_name'] ?? 'User';
                            }
                            ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">
        <div class="container-fluid py-4">
            <?php displayFlashMessage(); ?>
