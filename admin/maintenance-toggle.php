<?php
/**
 * Panda Realty - Site Maintenance Mode Toggle (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_maintenance');

$conn = get_db_connection();
$msg = '';

$current_maint = get_setting('maintenance_mode', '0');
$current_maint_msg = get_setting('maintenance_message', 'Panda Realty portal is currently undergoing scheduled system upgrades. We will be back online shortly! For urgent inquiries, call or WhatsApp Perpetuah directly at 0708 289 852.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (verify_csrf_token($csrf)) {
        $new_status = isset($_POST['maintenance_mode']) ? '1' : '0';
        $new_msg = clean_input($_POST['maintenance_message'] ?? '');

        update_setting('maintenance_mode', $new_status);
        update_setting('maintenance_message', $new_msg);

        log_security_action('MAINTENANCE_TOGGLED', "Super Admin set Maintenance Mode to: " . ($new_status === '1' ? 'ON (ACTIVE)' : 'OFF (LIVE)'));

        $current_maint = $new_status;
        $current_maint_msg = $new_msg;
        $msg = "Maintenance settings updated successfully!";
    }
}

$admin_page_title = "Site Maintenance Mode Control";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Website Maintenance & System Availability</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Super Admins can switch the public website online or offline for scheduled upgrades.</p>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="admin-card" style="max-width: 700px;">
    <div class="admin-card-header">
        <h3><i class="fas fa-power-off"></i> Maintenance Switch</h3>
    </div>

    <form action="maintenance-toggle.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

        <div style="background: <?= $current_maint === '1' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; border: 1.5px solid <?= $current_maint === '1' ? 'var(--admin-danger)' : 'var(--admin-success)' ?>; border-radius: 8px; padding: 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h4 style="font-size: 18px; color: #fff; margin-bottom: 4px;">
                    Website Status: <?= $current_maint === '1' ? '<span style="color: #f87171;">OFFLINE (Maintenance Mode)</span>' : '<span style="color: #34d399;">LIVE (Online for Visitors)</span>' ?>
                </h4>
                <p style="font-size: 12px; color: #cbd5e1;">
                    <?= $current_maint === '1' ? 'Public visitors are redirected to the luxury maintenance page.' : 'Public visitors have full access to property listings and search.' ?>
                </p>
            </div>

            <label class="switch-toggle">
                <input type="checkbox" name="maintenance_mode" value="1" <?= $current_maint === '1' ? 'checked' : '' ?>>
                <span class="slider-round"></span>
            </label>
        </div>

        <div class="admin-form-group">
            <label>Public Maintenance Notice Message</label>
            <textarea name="maintenance_message" rows="4" required><?= htmlspecialchars($current_maint_msg) ?></textarea>
            <span style="font-size: 11px; color: var(--admin-muted);">This explanation and Perpetuah contact details will be shown on <code>maintenance.php</code>.</span>
        </div>

        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px 28px; border-radius: 6px; border: none; cursor: pointer;">
                <i class="fas fa-save"></i> Apply Status Update
            </button>
            <a href="../maintenance.php" target="_blank" class="btn" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--admin-border); padding: 14px 20px; border-radius: 6px;">
                <i class="fas fa-eye"></i> Preview Maintenance Page
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
