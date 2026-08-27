<?php
/**
 * Panda Realty - App & Currency System Settings (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_system_settings');

$conn = get_db_connection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (verify_csrf_token($csrf)) {
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $val) {
                $k_clean = clean_input($key);
                $v_clean = db_escape(trim($val));
                update_setting($k_clean, $v_clean);
            }
        }

        // Handle Site Logo Upload
        $logo_fallback = clean_input($_POST['site_logo_url'] ?? get_setting('site_logo', ''));
        $uploaded_logo = upload_branding_image_file('site_logo_file', 'branding', $logo_fallback);
        if ($uploaded_logo !== '') {
            update_setting('site_logo', db_escape($uploaded_logo));
        }

        log_security_action('SETTINGS_MODIFIED', "Super Admin updated application, logo, and currency exchange settings");
        $msg = "System and branding settings updated successfully!";
    }
}

// Fetch all settings
$res_set = mysqli_query($conn, "SELECT * FROM app_settings");
$settings = [];
if ($res_set) {
    while ($row = mysqli_fetch_assoc($res_set)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$admin_page_title = "App & Currency Settings";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">System & Technical Configuration</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Reserved for developer and super admin maintenance, currency, and environment-related settings.</p>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form action="settings.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

    <!-- Official Brand Logo -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-image"></i> Official Brand Logo</h3>
        </div>

        <div style="display: grid; grid-template-columns: 220px 1fr; gap: 25px; align-items: start;">
            <div style="background: #0f172a; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border);">
                <label style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; display: block;">Active Logo</label>
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="<?= htmlspecialchars(normalize_media_url($settings['site_logo'])) ?>" style="max-width: 100%; max-height: 80px; object-fit: contain;">
                <?php else: ?>
                    <div style="font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: #fff;">
                        PANDA <span style="color: var(--admin-accent);">REALTY</span>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="admin-form-group">
                    <label>Upload Official Logo Image</label>
                    <input type="file" name="site_logo_file" accept="image/*">
                </div>
                <div class="admin-form-group">
                    <label>Or Enter Logo Image URL Fallback</label>
                    <input type="url" name="site_logo_url" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" placeholder="https://example.com/logo.png">
                    <span style="font-size: 11px; color: var(--admin-muted);">Leave empty to display the luxury styled typography logo mark (PANDA REALTY).</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Currency & Finance -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-coins"></i> Currency & Exchange Rates</h3>
        </div>

        <div class="admin-form-group" style="max-width: 400px;">
            <label>USD to KES Exchange Rate (1 USD = ? KES) *</label>
            <input type="number" step="0.01" name="settings[currency_usd_rate]" value="<?= htmlspecialchars($settings['currency_usd_rate'] ?? '130.00') ?>" required>
            <span style="font-size: 11px; color: var(--admin-muted);">All front-end properties and calculators dynamically convert between KSh and USD using this rate.</span>
        </div>
    </div>

    <!-- Official Brand & Contact Lines -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-address-card"></i> Company Information & Contact Lines</h3>
        </div>

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label>Company / Brand Name</label>
                <input type="text" name="settings[site_name]" value="<?= htmlspecialchars($settings['site_name'] ?? 'Panda Realty - Perpetuah Realtor') ?>" required>
            </div>

            <div class="admin-form-group">
                <label>Primary Phone Number</label>
                <input type="text" name="settings[contact_phone]" value="<?= htmlspecialchars($settings['contact_phone'] ?? '0708 289 852') ?>" required>
            </div>

            <div class="admin-form-group">
                <label>WhatsApp Number (Without '+' e.g. 254708289852)</label>
                <input type="text" name="settings[whatsapp_number]" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '254708289852') ?>" required>
            </div>

            <div class="admin-form-group">
                <label>Primary Inquiry Email</label>
                <input type="email" name="settings[contact_email]" value="<?= htmlspecialchars($settings['contact_email'] ?? 'info@pandarealty.co.ke') ?>" required>
            </div>
        </div>

        <div class="admin-form-group">
            <label>Physical Eldoret Head Office Address</label>
            <input type="text" name="settings[contact_address]" value="<?= htmlspecialchars($settings['contact_address'] ?? 'KVDA Plaza, 4th Floor, Oginga Odinga Street, Eldoret, Kenya') ?>" required>
        </div>
    </div>

    <!-- Branding & Development Signature -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-code"></i> Developer Attribution & Technology Partner</h3>
        </div>

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label>Developer Name</label>
                <input type="text" name="settings[developer_name]" value="<?= htmlspecialchars($settings['developer_name'] ?? 'TekTrend Technologies') ?>" required>
            </div>

            <div class="admin-form-group">
                <label>Developer Website URL</label>
                <input type="url" name="settings[developer_url]" value="<?= htmlspecialchars($settings['developer_url'] ?? 'https://tektrend.co.ke') ?>" required>
            </div>
        </div>
    </div>

    <button type="submit" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 15px 30px; font-size: 14px; border-radius: 6px; border: none; cursor: pointer;">
        <i class="fas fa-save"></i> Save System Settings
    </button>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
