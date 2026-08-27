<?php
/**
 * Panda Realty - Admin Common Header & Navigation
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../../config/settings.php';
require_admin();

$user = get_current_user_data();
$counts = get_system_counts();
$current_admin_page = basename($_SERVER['PHP_SELF']);
$current_role = get_current_user_role();
$dashboard_title = get_dashboard_title_for_role($current_role);
$role_label = get_role_label($current_role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($admin_page_title ?? 'Admin Dashboard') ?> | Panda Realty</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <?php $avatar_src = !empty($user['avatar']) ? normalize_media_url($user['avatar']) : (!empty($realtor_image) ? normalize_media_url($realtor_image) : '../assets/images/perpetuah.jpg'); ?>
                <img src="<?= htmlspecialchars($avatar_src) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120'" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--admin-accent); object-fit: cover;">
                <div>
                    <?php if (!empty($site_logo)): ?>
                        <img src="<?= htmlspecialchars(normalize_media_url($site_logo)) ?>" style="max-height: 26px; object-fit: contain;">
                    <?php else: ?>
                        <div class="sidebar-brand">PANDA <span>REALTY</span></div>
                    <?php endif; ?>
                    <span class="sidebar-role-badge <?= get_role_badge_class($current_role) ?>">
                        <?= htmlspecialchars($role_label) ?>
                    </span>
                </div>
            </a>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-heading">Core Overview</li>
            <li>
                <a href="index.php" class="<?= $current_admin_page === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-pie"></i> <?= htmlspecialchars($dashboard_title) ?>
                </a>
            </li>

            <li class="sidebar-heading">CRM Workspace</li>
            <?php if (user_can('manage_properties')): ?>
                <li>
                    <a href="properties.php" class="<?= in_array($current_admin_page, ['properties.php', 'property-add.php', 'property-edit.php']) ? 'active' : '' ?>">
                        <i class="fas fa-building"></i> Properties
                        <span class="menu-badge"><?= $counts['total_properties'] ?></span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_bookings')): ?>
                <li>
                    <a href="bookings.php" class="<?= $current_admin_page === 'bookings.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Bookings
                        <?php if ($counts['pending_visits'] > 0): ?>
                            <span class="menu-badge" style="background: var(--admin-warning); color: #111827;"><?= $counts['pending_visits'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_inquiries')): ?>
                <li>
                    <a href="inquiries.php" class="<?= $current_admin_page === 'inquiries.php' ? 'active' : '' ?>">
                        <i class="fas fa-inbox"></i> Inquiries
                        <?php if ($counts['new_inquiries'] > 0): ?>
                            <span class="menu-badge" style="background: var(--admin-danger); color: #fff;"><?= $counts['new_inquiries'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_sales_pipeline')): ?>
                <li>
                    <a href="crm.php" class="<?= in_array($current_admin_page, ['crm.php', 'sales-pipeline.php']) ? 'active' : '' ?>">
                        <i class="fas fa-funnel-dollar"></i> CRM &amp; Deal Pipeline
                        <?php if ($counts['active_pipeline_deals'] > 0): ?>
                            <span class="menu-badge" style="background: var(--admin-accent); color: #fff;"><?= $counts['active_pipeline_deals'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_invoices')): ?>
                <li>
                    <a href="invoices.php" class="<?= in_array($current_admin_page, ['invoices.php', 'invoice-create.php']) ? 'active' : '' ?>">
                        <i class="fas fa-file-invoice-dollar"></i> Invoices & Receipts
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_tasks')): ?>
                <li>
                    <a href="tasks.php" class="<?= $current_admin_page === 'tasks.php' ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i> Tasks
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_users')): ?>
                <li>
                    <a href="users.php" class="<?= $current_admin_page === 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i> People & Roles
                        <span class="menu-badge" style="background: var(--admin-info); color: #fff;"><?= $counts['total_internal_users'] ?></span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_cms')): ?>
                <li>
                    <a href="cms-editor.php" class="<?= in_array($current_admin_page, ['cms-editor.php', 'testimonials.php', 'videos-manager.php', 'property-tips.php']) ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> CMS & Media
                    </a>
                </li>
            <?php endif; ?>

            <?php if (user_can('view_security_logs') || user_can('view_online_users') || user_can('manage_maintenance') || user_can('manage_system_settings')): ?>
                <li class="sidebar-heading" style="color: var(--admin-accent);">Technical Controls</li>
            <?php endif; ?>
            <?php if (user_can('view_online_users')): ?>
                <li>
                    <a href="online-users.php" class="<?= $current_admin_page === 'online-users.php' ? 'active' : '' ?>">
                        <i class="fas fa-satellite-dish"></i> Live Visitors
                        <span class="menu-badge" style="background: var(--admin-success); color: #fff;"><?= $counts['online_visitors'] ?></span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('view_security_logs')): ?>
                <li>
                    <a href="security-logs.php" class="<?= $current_admin_page === 'security-logs.php' ? 'active' : '' ?>">
                        <i class="fas fa-shield-alt"></i> Security Logs
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_maintenance')): ?>
                <li>
                    <a href="maintenance-toggle.php" class="<?= $current_admin_page === 'maintenance-toggle.php' ? 'active' : '' ?>">
                        <i class="fas fa-toggle-on"></i> Maintenance
                    </a>
                </li>
            <?php endif; ?>
            <?php if (user_can('manage_system_settings')): ?>
                <li>
                    <a href="settings.php" class="<?= $current_admin_page === 'settings.php' ? 'active' : '' ?>">
                        <i class="fas fa-sliders-h"></i> System Settings
                    </a>
                </li>
            <?php endif; ?>

            <li class="sidebar-heading">Account</li>
            <li>
                <a href="profile.php" class="<?= $current_admin_page === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Profile & Google 2FA
                </a>
            </li>
            <li>
                <a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Public Website
                </a>
            </li>
            <li>
                <a href="../logout.php" style="color: #f87171;">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <img src="../assets/images/default-avatar.png" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=80'" class="user-avatar-mini">
            <div class="user-info-mini">
                <h5><?= htmlspecialchars($user['name'] ?? 'Administrator') ?></h5>
                <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" id="sidebarToggle" class="btn-icon" style="display: none;">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?= htmlspecialchars($admin_page_title ?? 'Panda Realty Management') ?></h2>
            </div>

            <div class="topbar-right">
                <div class="role-chip <?= get_role_badge_class($current_role) ?>">
                    <?= htmlspecialchars($role_label) ?>
                </div>
                <div class="online-indicator">
                    <span class="pulse-dot"></span>
                    <span><strong><?= $counts['online_visitors'] ?></strong> Live Online</span>
                </div>

                <a href="bookings.php" class="btn-icon" title="Site Visit Schedule">
                    <i class="fas fa-calendar-day"></i>
                </a>

                <a href="inquiries.php" class="btn-icon" title="New Inquiries">
                    <i class="fas fa-bell"></i>
                </a>

                <?php if (user_can('manage_sales_pipeline')): ?>
                    <a href="sales-pipeline.php" class="btn-icon" title="Sales Pipeline">
                        <i class="fas fa-funnel-dollar"></i>
                    </a>
                <?php endif; ?>

                <a href="profile.php" class="btn-icon" title="Security Profile">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </header>

        <div class="admin-content">
