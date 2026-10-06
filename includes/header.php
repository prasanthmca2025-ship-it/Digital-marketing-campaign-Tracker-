<?php
/**
 * Global Header Component
 * Digital Marketing Campaign Tracker
 */

// Ensure authentication check runs for all standard pages
if (!isset($skip_auth) || !$skip_auth) {
    require_once __DIR__ . '/auth_check.php';
} else {
    require_once __DIR__ . '/db_connect.php';
}

$page_title = $page_title ?? 'Dashboard | Digital Marketing Campaign Tracker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Navigation Component -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Navigation Bar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none p-1 px-2" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="top-navbar-title"><?= htmlspecialchars($header_title ?? 'Dashboard') ?></h1>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="add_campaign.php" class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Campaign</span>
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle text-primary"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars(current_user_name()) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">Signed in as <strong><?= htmlspecialchars(current_user_name()) ?></strong></h6></li>
                        <li><span class="dropdown-item-text text-muted small"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars(current_user_role()) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <main class="content-body">
            <!-- Flash Message Alerts -->
            <?php 
            $flash = get_flash_message();
            if ($flash): 
                $alertType = htmlspecialchars($flash['type'] ?? 'info');
                // Map common types to Bootstrap alert classes
                $bsClass = ($alertType === 'error') ? 'danger' : $alertType;
            ?>
                <div class="alert alert-<?= $bsClass ?> alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <?php if ($bsClass === 'success'): ?>
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <?php elseif ($bsClass === 'danger'): ?>
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <?php elseif ($bsClass === 'warning'): ?>
                            <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <?php endif; ?>
                        <div><?= htmlspecialchars($flash['text'] ?? '') ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
