<?php
/**
 * Panda Realty - Admin Profile & Google 2FA MFA Settings
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/totp.php';
require_admin();

$conn = get_db_connection();
$uid = (int)($_SESSION['user_id'] ?? 0);
$error = '';
$success = '';

$res = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid LIMIT 1");
$u = mysqli_fetch_assoc($res);

// Generate pending 2FA secret if not yet enabled
if (empty($u['two_factor_secret'])) {
    $new_secret = generate_totp_secret();
    mysqli_query($conn, "UPDATE users SET two_factor_secret = '$new_secret' WHERE id = $uid");
    $u['two_factor_secret'] = $new_secret;
}

$qr_url = get_totp_qr_url("PandaRealty", $u['email'], $u['two_factor_secret']);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['form_action'] ?? '');
    $csrf = clean_input($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $error = "Security session expired. Please refresh and try again.";
    } elseif ($action === 'update_profile') {
        $name = clean_input($_POST['name'] ?? '');
        $phone = clean_input($_POST['phone'] ?? '');
        $avatar_fallback = clean_input($_POST['avatar_url'] ?? ($u['avatar'] ?? ''));

        // Process Avatar Upload
        $new_avatar = upload_branding_image_file('avatar_file', 'avatars', $avatar_fallback);
        $avatar_sql = '';
        if (!empty($new_avatar)) {
            $avatar_safe = db_escape($new_avatar);
            $avatar_sql = ", avatar = '$avatar_safe'";
            $u['avatar'] = $new_avatar;
        }

        if (!empty($name)) {
            $name_safe = db_escape($name);
            $phone_safe = db_escape($phone);
            mysqli_query($conn, "UPDATE users SET name = '$name_safe', phone = '$phone_safe' $avatar_sql WHERE id = $uid");
            $_SESSION['user_name'] = $name;
            $success = "Profile and avatar photo updated successfully!";
            $u['name'] = $name;
            $u['phone'] = $phone;
        }
    } elseif ($action === 'change_password') {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if (password_verify($curr_pass, $u['password'])) {
            if (strlen($new_pass) >= 8) {
                if ($new_pass === $conf_pass) {
                    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                    $new_hash_safe = db_escape($new_hash);
                    mysqli_query($conn, "UPDATE users SET password = '$new_hash_safe' WHERE id = $uid");
                    log_security_action('PASSWORD_CHANGED', "User changed their administrative password", 'success', $uid);
                    $success = "Password changed successfully!";
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Password must be at least 8 characters long.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    } elseif ($action === 'enable_2fa') {
        $code = trim(clean_input($_POST['totp_code'] ?? ''));
        if (verify_totp_code($u['two_factor_secret'], $code)) {
            mysqli_query($conn, "UPDATE users SET two_factor_enabled = 1 WHERE id = $uid");
            log_security_action('2FA_ACTIVATED', "Google Authenticator 2FA enabled", 'success', $uid);
            $u['two_factor_enabled'] = 1;
            $success = "Google 2FA Multi-Factor Authentication is now ACTIVE on your account!";
        } else {
            $error = "Invalid 6-digit code. Please check your Google Authenticator app and try again.";
        }
    } elseif ($action === 'disable_2fa') {
        mysqli_query($conn, "UPDATE users SET two_factor_enabled = 0 WHERE id = $uid");
        log_security_action('2FA_DEACTIVATED', "Google Authenticator 2FA disabled", 'warning', $uid);
        $u['two_factor_enabled'] = 0;
        $success = "2FA has been disabled.";
    }
}

$admin_page_title = "Account Profile & 2FA Security";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Profile Settings & Google 2FA Security</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage your account credentials, contact information, and Google Authenticator app protection.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Profile & Password -->
    <div>
        <!-- Profile Info Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-user-edit"></i> Profile Details</h3>
            </div>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="form_action" value="update_profile">

                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--admin-border);">
                    <img src="<?= htmlspecialchars(normalize_media_url($u['avatar'] ?? 'assets/images/perpetuah.jpg')) ?>" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--admin-accent);">
                    <div style="flex-grow: 1;">
                        <label style="font-size: 11px; font-weight: 700; color: #1e293b; text-transform: uppercase; margin-bottom: 4px; display: block;">Upload Profile Picture</label>
                        <input type="file" name="avatar_file" accept="image/*" style="font-size: 12px; margin-bottom: 4px;">
                        <input type="url" name="avatar_url" value="<?= htmlspecialchars($u['avatar'] ?? '') ?>" placeholder="Or paste image URL" style="font-size: 12px;">
                    </div>
                </div>

                <div class="admin-form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($u['name']) ?>" required>
                </div>

                <div class="admin-form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($u['email']) ?>" disabled style="opacity: 0.6;">
                    <span style="font-size: 11px; color: var(--admin-muted);">Email changes must be requested through Super Admin.</span>
                </div>

                <div class="admin-form-group">
                    <label>Phone Number (WhatsApp)</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" placeholder="0708 289 852">
                </div>

                <button type="submit" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 24px; border-radius: 6px; border: none; cursor: pointer;">
                    <i class="fas fa-save"></i> Save Profile & Avatar
                </button>
            </form>
        </div>

        <!-- Password Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-key"></i> Update Password</h3>
            </div>

            <form action="profile.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="form_action" value="change_password">

                <div class="admin-form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="admin-form-group">
                    <label>New Password (Min. 8 characters)</label>
                    <input type="password" name="new_password" required>
                </div>

                <div class="admin-form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn" style="background: rgba(255,255,255,0.1); color: #fff; font-weight: 600; padding: 12px 24px; border-radius: 6px; border: 1px solid var(--admin-border); cursor: pointer;">
                    <i class="fas fa-shield-alt"></i> Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- Google 2FA MFA Setup Box -->
    <div>
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-mobile-alt"></i> Google 2FA Multi-Factor Authentication</h3>
            </div>

            <?php if ($u['two_factor_enabled']): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1.5px solid var(--admin-success); border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 25px;">
                    <i class="fas fa-check-circle" style="font-size: 38px; color: var(--admin-success); margin-bottom: 10px;"></i>
                    <h4 style="font-size: 16px; color: #fff; margin-bottom: 4px;">Google 2FA is ACTIVE</h4>
                    <p style="font-size: 12px; color: #cbd5e1;">Your login requires a 6-digit code from your Google Authenticator app.</p>
                </div>

                <form action="profile.php" method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA?');">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="form_action" value="disable_2fa">
                    <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid var(--admin-danger); width: 100%; padding: 12px; font-weight: 700; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-times-circle"></i> Disable Google 2FA
                    </button>
                </form>
            <?php else: ?>
                <p style="font-size: 13px; color: var(--admin-muted); line-height: 1.6; margin-bottom: 20px;">
                    1. Open the <strong>Google Authenticator</strong> app on your smartphone.<br>
                    2. Scan the QR code below or manually input the secret key.<br>
                    3. Enter the generated 6-digit code below to activate.
                </p>

                <div style="text-align: center;">
                    <div class="qr-box">
                        <img src="<?= htmlspecialchars($qr_url) ?>" alt="Google 2FA QR Code">
                    </div>
                    <div style="font-family: monospace; font-size: 13px; color: var(--admin-accent); margin-bottom: 20px;">
                        Secret: <strong><?= htmlspecialchars($u['two_factor_secret']) ?></strong>
                    </div>
                </div>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="form_action" value="enable_2fa">

                    <div class="admin-form-group">
                        <label>Enter 6-Digit Code from Authenticator App *</label>
                        <input type="text" name="totp_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required style="text-align: center; font-size: 24px; letter-spacing: 6px; font-weight: 700;">
                    </div>

                    <button type="submit" class="btn" style="width: 100%; background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px; border-radius: 6px; border: none; cursor: pointer;">
                        <i class="fas fa-shield-alt"></i> Verify & Enable Google 2FA
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
