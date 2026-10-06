<?php
/**
 * Sidebar Navigation Component
 * Digital Marketing Campaign Tracker
 */

// Determine active page name for menu highlighting
$current_script = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <!-- Brand Logo -->
    <a href="index.php" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
        <div>
            <div class="fw-bold" style="line-height: 1.1;">AdTracker Pro</div>
            <small style="font-size: 0.7rem; color: #94a3b8; font-weight: 400;">Marketing Analytics</small>
        </div>
    </a>

    <!-- Menu Items -->
    <ul class="sidebar-menu">
        <li class="sidebar-section-title">Core Menu</li>
        
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= ($current_script === 'index.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-section-title">Campaigns</li>
        
        <li class="nav-item">
            <a href="campaigns.php" class="nav-link <?= ($current_script === 'campaigns.php' || $current_script === 'campaign_detail.php' || $current_script === 'edit_campaign.php') ? 'active' : '' ?>">
                <i class="bi bi-megaphone"></i>
                <span>All Campaigns</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="add_campaign.php" class="nav-link <?= ($current_script === 'add_campaign.php') ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i>
                <span>Add Campaign</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="add_metrics.php" class="nav-link <?= ($current_script === 'add_metrics.php') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Log Metrics</span>
            </a>
        </li>

        <li class="sidebar-section-title">Analytics & Export</li>
        
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?= ($current_script === 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Reports & Export</span>
            </a>
        </li>
    </ul>

    <!-- User Profile & Logout -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="user-profile-badge">
                <div class="user-avatar">
                    <?= strtoupper(substr(current_user_name(), 0, 1)) ?>
                </div>
                <div style="line-height: 1.2;">
                    <div class="text-white fw-semibold small text-truncate" style="max-width: 120px;">
                        <?= htmlspecialchars(current_user_name()) ?>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">
                        <?= htmlspecialchars(current_user_role()) ?>
                    </small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-danger border-0 text-white-50 p-1" title="Log Out">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </div>
</nav>
