<?php
/**
 * Panda Realty - Admin Central Dashboard
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
$admin_page_title = get_dashboard_title_for_role(get_current_user_role());
require_once __DIR__ . '/includes/admin-header.php';

$conn = get_db_connection();
$counts = get_system_counts();
$current_role = get_current_user_role();
$ceo_snapshot = user_can('view_ceo_dashboard') ? get_ceo_dashboard_snapshot() : null;

// Recent Inquiries
$recent_inquiries = [];
$res_inq = mysqli_query($conn, "SELECT * FROM inquiries ORDER BY id DESC LIMIT 5");
if ($res_inq) {
    while ($row = mysqli_fetch_assoc($res_inq)) {
        $recent_inquiries[] = $row;
    }
}

// Upcoming Site Visits
$upcoming_visits = [];
$res_vis = mysqli_query($conn, "SELECT sv.*, p.title as property_title FROM site_visits sv LEFT JOIN properties p ON sv.property_id = p.id WHERE sv.visit_date >= CURDATE() ORDER BY sv.visit_date ASC, sv.visit_time ASC LIMIT 5");
if ($res_vis) {
    while ($row = mysqli_fetch_assoc($res_vis)) {
        $upcoming_visits[] = $row;
    }
}

// User Tasks
$uid = (int)($_SESSION['user_id'] ?? 0);
$my_tasks = [];
$res_tsk = mysqli_query($conn, "SELECT * FROM tasks WHERE user_id = $uid ORDER BY due_date ASC, id DESC LIMIT 5");
if ($res_tsk) {
    while ($row = mysqli_fetch_assoc($res_tsk)) {
        $my_tasks[] = $row;
    }
}
?>

<div class="admin-card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; gap: 20px; align-items: center; flex-wrap: wrap;">
        <div>
            <h3 style="font-size: 22px; font-weight: 700; color: var(--admin-text); margin-bottom: 6px;"><?= htmlspecialchars(get_dashboard_title_for_role($current_role)) ?></h3>
            <p style="color: var(--admin-muted); font-size: 14px;"><?= htmlspecialchars(get_dashboard_subtitle_for_role($current_role)) ?></p>
        </div>
        <div class="role-chip <?= get_role_badge_class($current_role) ?>"><?= htmlspecialchars(get_role_label($current_role)) ?></div>
    </div>
</div>

<?php if ($ceo_snapshot && $current_role === 'ceo'): ?>
    <div class="admin-card" style="margin-bottom: 20px;">
        <div class="admin-card-header">
            <h3><i class="fas fa-crown"></i> CEO Business Snapshot</h3>
        </div>
        <div class="stat-grid" style="margin-top: 0;">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(197, 160, 89, 0.12); color: var(--admin-accent);"><i class="fas fa-funnel-dollar"></i></div>
                <div class="stat-details">
                    <h4><?= format_price($ceo_snapshot['pipeline_value']) ?></h4>
                    <p>Weighted Pipeline Value</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-details">
                    <h4><?= format_price($ceo_snapshot['open_invoices_value']) ?></h4>
                    <p>Open Invoice Balance</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fas fa-user-clock"></i></div>
                <div class="stat-details">
                    <h4><?= (int)$ceo_snapshot['due_followups'] ?></h4>
                    <p>Follow-Ups Due</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-comments"></i></div>
                <div class="stat-details">
                    <h4><?= (int)$ceo_snapshot['consultations_scheduled'] ?></h4>
                    <p>Open Consultations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-details">
                    <h4><?= (int)$ceo_snapshot['site_visits_confirmed'] ?></h4>
                    <p>Confirmed Site Visits</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;"><i class="fas fa-fire"></i></div>
                <div class="stat-details">
                    <h4><?= (int)$ceo_snapshot['top_property_views'] ?></h4>
                    <p><?= htmlspecialchars($ceo_snapshot['top_property_title'] ?: 'Top Property Views') ?></p>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($current_role === 'admin' || $current_role === 'staff'): ?>
    <div class="admin-card" style="margin-bottom: 20px;">
        <div class="admin-card-header">
            <h3><i class="fas fa-briefcase"></i> Role Summary</h3>
        </div>
        <p style="color: var(--admin-muted); font-size: 14px; margin: 0;">
            <?= $current_role === 'admin'
                ? 'Admin view focuses on daily operations across leads, bookings, invoices, and content handoff.'
                : 'Staff view focuses on frontline follow-up, site tours, inquiry response, and task completion.' ?>
        </p>
    </div>
<?php endif; ?>

<!-- Statistics Overview Grid -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-details">
            <h4><?= $counts['total_properties'] ?></h4>
            <p>Total Inventory</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-double"></i></div>
        <div class="stat-details">
            <h4><?= $counts['sold_properties'] ?></h4>
            <p>Properties Sold</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hard-hat"></i></div>
        <div class="stat-details">
            <h4><?= $counts['under_construction'] ?></h4>
            <p>Under Construction</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-door-open"></i></div>
        <div class="stat-details">
            <h4><?= $counts['studio_apartments'] ?></h4>
            <p>Studio Apartments</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(217, 119, 6, 0.15); color: #d97706;"><i class="fas fa-map"></i></div>
        <div class="stat-details">
            <h4><?= $counts['land_properties'] ?></h4>
            <p>Titled Plots & Land</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-users"></i></div>
        <div class="stat-details">
            <h4><?= $counts['online_visitors'] ?></h4>
            <p>Live Active Visitors</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-user-friends"></i></div>
        <div class="stat-details">
            <h4><?= $counts['total_clients'] ?></h4>
            <p>Client Accounts</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;"><i class="fas fa-user-tie"></i></div>
        <div class="stat-details">
            <h4><?= $counts['total_internal_users'] ?></h4>
            <p>Internal Team</p>
        </div>
    </div>

    <?php if (user_can('manage_sales_pipeline')): ?>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(195, 154, 77, 0.12); color: var(--admin-accent);"><i class="fas fa-funnel-dollar"></i></div>
        <div class="stat-details">
            <h4><?= $counts['active_pipeline_deals'] ?></h4>
            <p>Active Pipeline</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (user_can('manage_sales_pipeline')): ?>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-trophy"></i></div>
        <div class="stat-details">
            <h4><?= $counts['won_pipeline_deals'] ?></h4>
            <p>Closed Won</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Main Two-Column Dashboard Layout -->
<div class="dashboard-grid-2">
    <!-- Left Column: Upcoming Visits & Leads -->
    <div>
        <!-- Scheduled Site Visits & Calendar -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-calendar-check"></i> Upcoming Site Tours & VIP Viewings</h3>
                <a href="bookings.php" class="btn-icon" title="View All Bookings"><i class="fas fa-arrow-right"></i></a>
            </div>

            <?php if (empty($upcoming_visits)): ?>
                <p style="color: var(--admin-muted); font-size: 13px; padding: 15px 0;">No upcoming site tours scheduled for today.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Phone</th>
                                <th>Date & Time</th>
                                <th>Property</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_visits as $vis): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($vis['client_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($vis['client_phone']) ?></td>
                                    <td><?= date('M d, Y', strtotime($vis['visit_date'])) ?> at <?= date('h:i A', strtotime($vis['visit_time'])) ?></td>
                                    <td><?= htmlspecialchars($vis['property_title'] ?: 'General Land Tour') ?></td>
                                    <td>
                                        <span class="status-pill <?= $vis['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                            <?= strtoupper($vis['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Contact Leads & Inquiries -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-envelope-open-text"></i> Recent Inquiries & Leads</h3>
                <a href="inquiries.php" class="btn-icon" title="View Inbox"><i class="fas fa-arrow-right"></i></a>
            </div>

            <?php if (empty($recent_inquiries)): ?>
                <p style="color: var(--admin-muted); font-size: 13px; padding: 15px 0;">Inbox is clear.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Received</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_inquiries as $inq): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($inq['name']) ?></strong><br>
                                        <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($inq['phone']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($inq['subject']) ?></td>
                                    <td>
                                        <span class="status-pill info"><?= htmlspecialchars($inq['inquiry_type']) ?></span>
                                    </td>
                                    <td><?= date('M d, H:i', strtotime($inq['created_at'])) ?></td>
                                    <td>
                                        <a href="inquiries.php?view=<?= $inq['id'] ?>" class="btn-icon" title="View Message"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Quick Actions & Personal Tasks -->
    <div>
        <!-- Quick Action Shortcuts -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <?php if (user_can('manage_properties')): ?>
                <a href="property-add.php" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                    <i class="fas fa-plus-circle" style="color: var(--admin-accent); font-size: 20px; margin-bottom: 8px; display: block;"></i>
                    <span style="font-size: 12px; font-weight: 600;">Add Property</span>
                </a>
                <?php endif; ?>
                <?php if (user_can('manage_invoices')): ?>
                <a href="invoice-create.php" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                    <i class="fas fa-receipt" style="color: #34d399; font-size: 20px; margin-bottom: 8px; display: block;"></i>
                    <span style="font-size: 12px; font-weight: 600;">New Invoice</span>
                </a>
                <?php endif; ?>
                <?php if (user_can('manage_bookings')): ?>
                <a href="bookings.php?action=new" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                    <i class="fas fa-calendar-plus" style="color: #60a5fa; font-size: 20px; margin-bottom: 8px; display: block;"></i>
                    <span style="font-size: 12px; font-weight: 600;">Book Tour</span>
                </a>
                <?php endif; ?>
                <?php if (user_can('manage_sales_pipeline')): ?>
                <a href="sales-pipeline.php" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                    <i class="fas fa-funnel-dollar" style="color: var(--admin-accent); font-size: 20px; margin-bottom: 8px; display: block;"></i>
                    <span style="font-size: 12px; font-weight: 600;">Sales Pipeline</span>
                </a>
                <?php endif; ?>
                <?php if (user_can('manage_cms')): ?>
                    <a href="cms-editor.php" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                        <i class="fas fa-sliders" style="color: var(--admin-warning); font-size: 20px; margin-bottom: 8px; display: block;"></i>
                        <span style="font-size: 12px; font-weight: 600;">CMS & Media</span>
                    </a>
                <?php endif; ?>
                <a href="profile.php" style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border); color: var(--admin-text); transition: var(--transition);">
                        <i class="fas fa-key" style="color: var(--admin-accent); font-size: 20px; margin-bottom: 8px; display: block;"></i>
                        <span style="font-size: 12px; font-weight: 600;">Google 2FA</span>
                </a>
            </div>
        </div>

        <!-- Personal Tasks & Reminders -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-tasks"></i> My Tasks & Reminders</h3>
                <a href="tasks.php" class="btn-icon" title="Manage Tasks"><i class="fas fa-plus"></i></a>
            </div>

            <?php if (empty($my_tasks)): ?>
                <p style="color: var(--admin-muted); font-size: 13px; padding: 15px 0;">No active tasks. You're all caught up!</p>
            <?php else: ?>
                <div>
                    <?php foreach ($my_tasks as $tsk): ?>
                        <div class="task-item <?= $tsk['status'] === 'completed' ? 'completed' : '' ?>">
                            <div class="task-left">
                                <input type="checkbox" class="task-checkbox" data-task-id="<?= $tsk['id'] ?>" <?= $tsk['status'] === 'completed' ? 'checked' : '' ?>>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($tsk['title']) ?></span><br>
                                    <span style="font-size: 11px; color: var(--admin-muted);">Due: <?= $tsk['due_date'] ? date('M d', strtotime($tsk['due_date'])) : 'No date' ?></span>
                                </div>
                            </div>
                            <span class="status-pill <?= $tsk['priority'] === 'urgent' ? 'danger' : ($tsk['priority'] === 'high' ? 'warning' : 'info') ?>">
                                <?= strtoupper($tsk['priority']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
