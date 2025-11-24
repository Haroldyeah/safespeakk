<?php
require_once __DIR__ . '/../config/config.php';
$is_landing_page = basename($_SERVER['PHP_SELF']) == 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F172A" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛡️</text></svg>">
    <script>
        (function() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/dashboard-theme.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/header-profile.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/password-strength.css" rel="stylesheet">
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $exclude_dark_mode_pages = ['index.php', 'login.php', 'register.php'];
    if (!in_array($current_page, $exclude_dark_mode_pages)):
    ?>
    <link href="<?php echo BASE_URL; ?>/assets/css/dark-mode.css" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Hide Jotform branding from chatbot */
        div.bg-navy-25 {
            display: none !important;
        }
    </style>
</head>
<body <?php if (!$is_landing_page) echo ''; ?>>
    <!-- Dark Mode Script -->
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $exclude_dark_mode_pages = ['index.php', 'login.php', 'register.php'];
    if (!in_array($current_page, $exclude_dark_mode_pages)):
    ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/dark-mode.js" defer></script>
    <?php endif; ?>
    <?php if (isLoggedIn() && !$is_landing_page): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <button class="navbar-toggler d-lg-none" type="button" id="sidebar-toggle" aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon" style="color: #2563EB !important;"></span>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="color: #2563EB !important;"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php 
                    $role = getUserRole();
                    $baseUrl = BASE_URL;
                    ?>
                    
                    <?php if ($role === 'student'): ?>
                        <!-- Student navigation moved entirely to sidebar -->
                    <?php else: ?>
                        <!-- Role navigation moved to sidebar for a consistent layout -->
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" id="theme-toggle">
                            <i id="theme-icon" class="fas fa-moon"></i>
                            <span class="ms-2 d-none d-lg-inline"></span>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle header-profile" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="header-profile-photo-container">
                                <?php if ($role === 'student' && !empty($_SESSION['id_photo'])): ?>
                                    <img src="<?php echo $baseUrl; ?>/uploads/id_photos/<?php echo htmlspecialchars($_SESSION['id_photo']); ?>" 
                                         alt="ID Photo" class="header-profile-photo">
                                <?php else: ?>
                                    <div class="header-profile-photo-fallback">
                                        <?php 
                                            if ($role === 'school') {
                                                echo '<i class="fas fa-school"></i>';
                                            } else {
                                                echo strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1));
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span>
                                <?php 
                                if ($role === 'school') {
                                    echo 'School Admin';
                                } else {
                                    echo $_SESSION['first_name'] ?? 'User';
                                }
                                ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header px-3">
                                <strong><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (in_array($role, ['student', 'admin', 'school'])): ?>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/<?php echo $role; ?>/edit_profile.php">
                                    <i class="fas fa-user-edit me-1"></i>Edit Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
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
        <div class="container-fluid py-2">
            <?php if (isLoggedIn() && !$is_landing_page): ?>
                <div class="app-layout with-sidebar">
                    <!-- Mobile sidebar overlay -->
                    <div class="sidebar-overlay d-lg-none" id="sidebar-overlay"></div>
                    <aside class="app-sidebar" id="app-sidebar">
                        <div class="brand">
                            <div class="logo">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <h4>SafeSpeak</h4>
                                <div class="brand-subtitle">Speak Up, Stay Safe</div>
                            </div>
                            <button class="sidebar-close d-lg-none" id="sidebar-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <nav class="sidebar-nav">
                    <?php if ($role === 'student'): ?>
                        <a href="<?php echo $baseUrl; ?>/student/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='dashboard.php')? 'active':''; ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?php echo $baseUrl; ?>/student/submit_report.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='submit_report.php')? 'active':''; ?>"><i class="fa fa-plus"></i> Submit Report</a>
                        <a href="<?php echo $baseUrl; ?>/student/my_reports.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='my_reports.php')? 'active':''; ?>"><i class="fa fa-file-alt"></i> My Reports</a>
                        <a href="<?php echo $baseUrl; ?>/interventions.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='interventions.php')? 'active':''; ?>"><i class="fas fa-notes-medical"></i> Interventions</a>
                        <a href="<?php echo $baseUrl; ?>/info_center.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='info_center.php')? 'active':''; ?>"><i class="fas fa-book"></i> Info Center</a>
                    <?php elseif ($role === 'school'): ?>
                        <a href="<?php echo $baseUrl; ?>/school/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='dashboard.php')? 'active':''; ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?php echo $baseUrl; ?>/school/students.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='students.php')? 'active':''; ?>"><i class="fa fa-users"></i> Students</a>
                        <a href="<?php echo $baseUrl; ?>/school/view_reports.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='view_reports.php')? 'active':''; ?>"><i class="fa fa-file-alt"></i> Reports</a>
                        <a href="<?php echo $baseUrl; ?>/school/analytics.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='analytics.php')? 'active':''; ?>"><i class="fa fa-chart-bar"></i> Analytics</a>
                        <a href="<?php echo $baseUrl; ?>/interventions.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='interventions.php')? 'active':''; ?>"><i class="fas fa-notes-medical"></i> Interventions</a>
                        <a href="<?php echo $baseUrl; ?>/info_center.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='info_center.php')? 'active':''; ?>"><i class="fas fa-book"></i> Info Center</a>
                    <?php elseif ($role === 'admin'): ?>
                        <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='dashboard.php')? 'active':''; ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?php echo $baseUrl; ?>/admin/all_reports.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='all_reports.php')? 'active':''; ?>"><i class="fa fa-file-alt"></i> All Reports</a>
                        <a href="<?php echo $baseUrl; ?>/admin/manage_schools.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='manage_schools.php')? 'active':''; ?>"><i class="fa fa-school"></i> Schools</a>
                        <a href="<?php echo $baseUrl; ?>/admin/manage_users.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='manage_users.php')? 'active':''; ?>"><i class="fa fa-users"></i> Users</a>
                        <a href="<?php echo $baseUrl; ?>/admin/analytics.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='analytics.php')? 'active':''; ?>"><i class="fa fa-chart-bar"></i> Analytics</a>
                        <a href="<?php echo $baseUrl; ?>/interventions.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='interventions.php')? 'active':''; ?>"><i class="fas fa-notes-medical"></i> Interventions</a>
                        <a href="<?php echo $baseUrl; ?>/admin/mswd.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='mswd.php')? 'active':''; ?>"><i class="fas fa-hands-helping"></i> MSWD Cases</a>
                        <a href="<?php echo $baseUrl; ?>/info_center.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='info_center.php')? 'active':''; ?>"><i class="fas fa-book"></i> Info Center</a>
                        <a href="<?php echo $baseUrl; ?>/admin/system_logs.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='system_logs.php')? 'active':''; ?>"><i class="fas fa-clipboard-list"></i> System Logs</a>
                    <?php endif; ?>
                        </nav>
                    </aside>

                    <div class="content-wrapper">
            <?php endif; ?>
            <?php displayFlashMessage(); ?>
            
            <!-- Mobile Sidebar JavaScript -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarToggle = document.getElementById('sidebar-toggle');
                const sidebar = document.getElementById('app-sidebar');
                const sidebarOverlay = document.getElementById('sidebar-overlay');
                const sidebarClose = document.getElementById('sidebar-close');
                
                if (sidebarToggle && sidebar && sidebarOverlay) {
                    // Toggle sidebar
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.add('show');
                        sidebarOverlay.classList.add('show');
                        document.body.classList.add('sidebar-open');
                    });
                    
                    // Close sidebar
                    function closeSidebar() {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                        document.body.classList.remove('sidebar-open');
                    }
                    
                    // Close on overlay click
                    sidebarOverlay.addEventListener('click', closeSidebar);
                    
                    // Close on close button click
                    if (sidebarClose) {
                        sidebarClose.addEventListener('click', closeSidebar);
                    }
                    
                    // Close on window resize to desktop
                    window.addEventListener('resize', function() {
                        if (window.innerWidth >= 992) {
                            closeSidebar();
                        }
                    });
                }
            });
            </script>
