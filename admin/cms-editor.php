<?php
/**
 * Panda Realty - Front-End CMS Content, Logo & Realtor Bio Editor (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_cms');

$conn = get_db_connection();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh.";
    } else {
        // 1. Text blocks
        if (isset($_POST['blocks']) && is_array($_POST['blocks'])) {
            foreach ($_POST['blocks'] as $key => $val) {
                $k_clean = clean_input($key);
                $v_clean = db_escape(trim($val));
                mysqli_query($conn, "INSERT INTO content_blocks (block_key, content_value) VALUES ('$k_clean', '$v_clean') ON DUPLICATE KEY UPDATE content_value = '$v_clean'");
            }
        }

        // 2. Handle Official Logo Upload
        $logo_fallback = clean_input($_POST['site_logo_url'] ?? get_setting('site_logo', ''));
        $uploaded_logo = upload_branding_image_file('site_logo_file', 'branding', $logo_fallback);
        if ($uploaded_logo !== '') {
            $logo_safe = db_escape($uploaded_logo);
            update_setting('site_logo', $logo_safe);
            mysqli_query($conn, "INSERT INTO content_blocks (block_key, content_value) VALUES ('site_logo', '$logo_safe') ON DUPLICATE KEY UPDATE content_value = '$logo_safe'");
        }

        // 3. Handle Realtor Profile Photo Upload
        $realtor_fallback = clean_input($_POST['realtor_image_url'] ?? get_setting('realtor_image', 'assets/images/perpetuah.jpg'));
        $uploaded_realtor = upload_branding_image_file('realtor_image_file', 'realtor', $realtor_fallback);
        if ($uploaded_realtor !== '') {
            $realtor_safe = db_escape($uploaded_realtor);
            update_setting('realtor_image', $realtor_safe);
            mysqli_query($conn, "INSERT INTO content_blocks (block_key, content_value) VALUES ('realtor_image', '$realtor_safe') ON DUPLICATE KEY UPDATE content_value = '$realtor_safe'");
        }

        log_security_action('CMS_CONTENT_UPDATED', "Super Admin updated website copy, logo, and realtor profile image");
        $msg = "Front-end website copy, logo, and realtor bio photo updated successfully!";
    }
}

// Fetch all content blocks
$res_blocks = mysqli_query($conn, "SELECT * FROM content_blocks");
$blocks = [];
if ($res_blocks) {
    while ($row = mysqli_fetch_assoc($res_blocks)) {
        $blocks[$row['block_key']] = $row['content_value'];
    }
}

$current_site_logo = get_setting('site_logo', $blocks['site_logo'] ?? '');
$current_realtor_img = get_setting('realtor_image', $blocks['realtor_image'] ?? 'assets/images/perpetuah.jpg');

$admin_page_title = "Front-End Content & Hero Editor";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">Front-End Visual Content & Brand Manager</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Super Admins can edit the official website logo, Perpetuah's bio photo, headlines, welcome video, and promotional banner in real time.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="testimonials.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-quote-left"></i> Testimonials
        </a>
        <a href="../videos.php" target="_blank" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-video"></i> Video Library
        </a>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<form action="cms-editor.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

    <!-- 1. Official Logo & Brand Visuals -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-image"></i> Official Website Logo</h3>
        </div>

        <div style="display: grid; grid-template-columns: 220px 1fr; gap: 25px; align-items: start;">
            <!-- Current Logo Preview -->
            <div style="background: #0f172a; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border);">
                <label style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; display: block;">Current Logo</label>
                <?php if (!empty($current_site_logo)): ?>
                    <img src="<?= htmlspecialchars(normalize_media_url($current_site_logo)) ?>" style="max-width: 100%; max-height: 80px; object-fit: contain;">
                <?php else: ?>
                    <div style="font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: #fff;">
                        PANDA <span style="color: var(--admin-accent);">REALTY</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload / URL Inputs -->
            <div>
                <div class="admin-form-group">
                    <label>Upload New Website Logo Image (PNG / SVG / WebP / JPG)</label>
                    <input type="file" name="site_logo_file" accept="image/*">
                </div>
                <div class="admin-form-group">
                    <label>Or Enter Logo Image URL Fallback</label>
                    <input type="url" name="site_logo_url" value="<?= htmlspecialchars($current_site_logo) ?>" placeholder="https://example.com/logo.png">
                    <span style="font-size: 11px; color: var(--admin-muted);">Leave empty to use the luxury typography logo mark (PANDA REALTY).</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Perpetuah Realtor Profile Photo & Story -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-user-tie"></i> Perpetuah Realtor Profile Picture & Bio</h3>
        </div>

        <div style="display: grid; grid-template-columns: 220px 1fr; gap: 25px; align-items: start; margin-bottom: 20px;">
            <!-- Current Realtor Photo Preview -->
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--admin-border);">
                <label style="font-size: 11px; font-weight: 700; color: var(--admin-muted); text-transform: uppercase; margin-bottom: 8px; display: block;">Current Photo</label>
                <img src="<?= htmlspecialchars(normalize_media_url($current_realtor_img)) ?>" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 3px solid var(--admin-accent); margin: 0 auto;">
            </div>

            <!-- Upload Inputs -->
            <div>
                <div class="admin-form-group">
                    <label>Upload New Realtor Profile Photo</label>
                    <input type="file" name="realtor_image_file" accept="image/*">
                </div>
                <div class="admin-form-group">
                    <label>Or Enter Realtor Image URL</label>
                    <input type="url" name="realtor_image_url" value="<?= htmlspecialchars($current_realtor_img) ?>" placeholder="assets/images/perpetuah.jpg">
                    <span style="font-size: 11px; color: var(--admin-muted);">This photo appears on the front-end Meet Perpetuah bio, navbar avatar, and footer signature.</span>
                </div>
            </div>
        </div>

        <div class="admin-form-group">
            <label>About Section Headline</label>
            <input type="text" name="blocks[about_title]" value="<?= htmlspecialchars($blocks['about_title'] ?? 'Your Trusted Eldoret Property Expert') ?>">
        </div>

        <div class="admin-form-group">
            <label>Perpetuah Story & Mission Bio</label>
            <textarea name="blocks[about_story]" rows="4"><?= htmlspecialchars($blocks['about_story'] ?? 'Led by Perpetuah, Panda Realty is dedicated to connecting families and visionary investors with premier real estate opportunities in Eldoret and across Uasin Gishu County.') ?></textarea>
        </div>
    </div>

    <!-- 3. Hero Section Copy -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-magic"></i> Hero Slider & Top Deck Copy</h3>
        </div>

        <div class="admin-form-group">
            <label>Hero Badge Text</label>
            <input type="text" name="blocks[hero_badge]" value="<?= htmlspecialchars($blocks['hero_badge'] ?? 'Exclusive Eldoret Luxury Real Estate') ?>">
        </div>

        <div class="admin-form-group">
            <label>Hero Main Title Headline</label>
            <input type="text" name="blocks[hero_main_title]" value="<?= htmlspecialchars($blocks['hero_main_title'] ?? 'Find Your Prime Home & Land in Eldoret') ?>">
        </div>

        <div class="admin-form-group">
            <label>Hero Subtitle Description</label>
            <textarea name="blocks[hero_subtitle]" rows="3"><?= htmlspecialchars($blocks['hero_subtitle'] ?? 'Discover titled plots, modern studio apartments, luxury family homes, and high-yield investment properties with Eldoret\'s leading realtor, Perpetuah.') ?></textarea>
        </div>
    </div>

    <!-- 4. Video & Media Showcase -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-video"></i> Welcome Video Tour</h3>
        </div>

        <div class="admin-form-group">
            <label>Welcome Video Embed URL (YouTube / Vimeo / MP4)</label>
            <input type="text" name="blocks[welcome_video_url]" value="<?= htmlspecialchars($blocks['welcome_video_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ') ?>">
            <span style="font-size: 11px; color: var(--admin-muted);">Paste the embed URL for the modal video tour.</span>
        </div>
    </div>

    <!-- 5. Promotional Ad Banner Modal -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-bullhorn"></i> Promotional Launch & Ad Banner Modal</h3>
        </div>

        <div class="admin-form-group">
            <label>Ad Modal Active Status</label>
            <select name="blocks[promo_banner_active]">
                <option value="1" <?= ($blocks['promo_banner_active'] ?? '1') === '1' ? 'selected' : '' ?>>Active (Show on visitor scroll)</option>
                <option value="0" <?= ($blocks['promo_banner_active'] ?? '1') === '0' ? 'selected' : '' ?>>Disabled</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Promo Scroll Trigger Percentage</label>
            <input type="number" min="5" max="95" name="blocks[promo_scroll_percent]" value="<?= htmlspecialchars($blocks['promo_scroll_percent'] ?? '35') ?>">
            <span style="font-size: 11px; color: var(--admin-muted);">The featured advert modal appears once per session after the visitor reaches this scroll depth.</span>
        </div>

        <div class="admin-form-group">
            <label>Promo Banner Headline</label>
            <input type="text" name="blocks[promo_banner_title]" value="<?= htmlspecialchars($blocks['promo_banner_title'] ?? '🔥 Special Launch: Eldoret Annex Prime 50x100 Plots & Studio Apartments') ?>">
        </div>

        <div class="admin-form-group">
            <label>Promo Banner Content / Terms</label>
            <textarea name="blocks[promo_banner_text]" rows="3"><?= htmlspecialchars($blocks['promo_banner_text'] ?? 'Ready title deeds with flexible installment plans up to 24 months! Deposit only 10% today and secure your high-return property in Eldoret.') ?></textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 16px 36px; font-size: 14px; font-weight: 700; border-radius: 8px; cursor: pointer;">
        <i class="fas fa-save"></i> Save All Front-End & Brand Changes
    </button>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
