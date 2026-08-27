<?php
/**
 * Panda Realty - Real-Time Active Visitors & Online Users Tracker (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('view_online_users');

$conn = get_db_connection();

// Fetch Active Visitors (last 5 minutes heartbeat)
$res_online = mysqli_query($conn, "SELECT * FROM visitor_analytics WHERE last_heartbeat >= NOW() - INTERVAL 5 MINUTE ORDER BY last_heartbeat DESC LIMIT 50");
$online_visitors = [];
if ($res_online) {
    while ($row = mysqli_fetch_assoc($res_online)) {
        $online_visitors[] = $row;
    }
}

// Fetch Logged-in Staff & Users
$res_users = mysqli_query($conn, "SELECT id, name, email, role, is_online, last_active FROM users ORDER BY is_online DESC, last_active DESC");
$all_users = [];
if ($res_users) {
    while ($row = mysqli_fetch_assoc($res_users)) {
        $all_users[] = $row;
    }
}

$admin_page_title = "Live Online Visitors & Sessions";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Real-Time Visitor Radar & Active Sessions</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Live tracking of website visitors, page hits, device types, and active staff sessions.</p>
    </div>

    <div class="online-indicator" style="font-size: 14px; padding: 8px 18px;">
        <span class="pulse-dot"></span>
        <span><strong><?= count($online_visitors) ?></strong> Current Active Visitor(s)</span>
    </div>
</div>

<div class="dashboard-grid-2">
    <!-- Active Visitors Radar -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-satellite-dish" style="color: var(--admin-success);"></i> Live Website Visitors (Real-Time)</h3>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Current Page</th>
                        <th>Device</th>
                        <th>Referrer</th>
                        <th>Last Heartbeat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($online_visitors)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 25px; color: var(--admin-muted);">No active visitors in the last 5 minutes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($online_visitors as $v): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--admin-accent);"><?= htmlspecialchars($v['ip_address']) ?></strong><br>
                                    <span style="font-size: 10px; color: var(--admin-muted);"><?= htmlspecialchars($v['city']) ?>, <?= htmlspecialchars($v['country']) ?></span>
                                </td>
                                <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span style="font-size: 12px; color: #fff;"><?= htmlspecialchars($v['page_url']) ?></span>
                                </td>
                                <td>
                                    <span class="status-pill info"><i class="fas <?= $v['device_type'] === 'Mobile' ? 'fa-mobile-alt' : 'fa-desktop' ?>"></i> <?= htmlspecialchars($v['device_type']) ?></span>
                                </td>
                                <td style="font-size: 11px; color: var(--admin-muted);">
                                    <?= htmlspecialchars($v['referrer'] ?: 'Direct Visit') ?>
                                </td>
                                <td style="font-size: 12px;">
                                    <?= date('H:i:s', strtotime($v['last_heartbeat'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Logged in System Users -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-users-cog"></i> Staff & User Presence</h3>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Presence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $usr): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($usr['name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($usr['email']) ?></span>
                            </td>
                            <td>
                                <span class="sidebar-role-badge <?= $usr['role'] === 'superadmin' ? 'badge-superadmin' : 'badge-admin' ?>">
                                    <?= strtoupper($usr['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($usr['is_online']): ?>
                                    <span class="status-pill success"><span class="pulse-dot" style="display: inline-block; margin-right: 4px;"></span> ONLINE</span>
                                <?php else: ?>
                                    <span class="status-pill" style="background: rgba(255,255,255,0.05); color: var(--admin-muted);">OFFLINE</span>
                                    <span style="font-size: 10px; color: var(--admin-muted); display: block; margin-top: 2px;">Last: <?= $usr['last_active'] ? date('M d, H:i', strtotime($usr['last_active'])) : 'Never' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
