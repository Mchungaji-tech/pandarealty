<?php
/**
 * Panda Realty - Security & Audit Logs Dashboard (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('view_security_logs');

$conn = get_db_connection();
$msg = '';

// Handle Clear Logs
if (isset($_POST['clear_logs'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (verify_csrf_token($csrf)) {
        mysqli_query($conn, "TRUNCATE TABLE security_logs");
        log_security_action('LOGS_CLEARED', "Super Admin purged security audit logs");
        $msg = "Security audit logs have been purged.";
    }
}

// Fetch logs
$res_logs = mysqli_query($conn, "SELECT sl.*, u.name as user_name FROM security_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.id DESC LIMIT 100");
$logs = [];
if ($res_logs) {
    while ($row = mysqli_fetch_assoc($res_logs)) {
        $logs[] = $row;
    }
}

$admin_page_title = "Security & Audit Logs Dashboard";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">System Security & Audit Trail</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Real-time event logging of authentication attempts, privilege escalations, setting changes, and IP addresses.</p>
    </div>

    <form action="security-logs.php" method="POST" onsubmit="return confirm('Purge all audit logs?');">
        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
        <input type="hidden" name="clear_logs" value="1">
        <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid var(--admin-danger); padding: 10px 18px; border-radius: 6px; font-size: 12px; cursor: pointer;">
            <i class="fas fa-trash-alt"></i> Clear Audit Logs
        </button>
    </form>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action Event</th>
                    <th>User & Role</th>
                    <th>Event Details</th>
                    <th>IP Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 25px; color: var(--admin-muted);">No security events recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 12px;">
                                <?= date('M d, Y H:i:s', strtotime($l['created_at'])) ?>
                            </td>
                            <td>
                                <strong style="color: var(--admin-accent); font-family: monospace;"><?= htmlspecialchars($l['action']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($l['user_name'] ?? 'System / Guest') ?></strong><br>
                                <span class="sidebar-role-badge <?= $l['user_role'] === 'superadmin' ? 'badge-superadmin' : 'badge-admin' ?>" style="font-size: 9px;">
                                    <?= strtoupper(htmlspecialchars($l['user_role'] ?? 'guest')) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: #cbd5e1; max-width: 300px;">
                                <?= htmlspecialchars($l['details'] ?? '') ?>
                            </td>
                            <td style="font-family: monospace; font-size: 11px; color: var(--admin-muted);">
                                <?= htmlspecialchars($l['ip_address'] ?? 'UNKNOWN') ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $l['status'] === 'success' ? 'success' : ($l['status'] === 'warning' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper($l['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
