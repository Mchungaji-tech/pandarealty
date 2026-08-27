<?php
/**
 * Panda Realty - Production Super Admin Setup & Master Initializer
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/totp.php';

$setup_allowed = is_local_environment() || panda_env('ALLOW_SUPER_SETUP', '0') === '1';

if (!$setup_allowed) {
    http_response_code(403);
    log_security_action('BLOCKED_SUPER_SETUP_ACCESS', 'Blocked super setup access outside approved setup environment.', 'critical');
    exit('Super admin setup is disabled outside the approved setup environment.');
}

$conn = get_db_connection();
$msg = '';
$err = '';

// Check how many superadmins currently exist
$res_check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'superadmin'");
$superadmin_count = ($res_check && $row = mysqli_fetch_assoc($res_check)) ? (int)$row['cnt'] : 0;

$is_locked = ($superadmin_count >= 2);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf = clean_input($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh and try again.";
    } elseif (empty($name) || empty($email) || empty($password)) {
        $err = "Please provide Name, Email, and Password.";
    } elseif ($password !== $confirm_password) {
        $err = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $err = "Password must be at least 8 characters long.";
    } else {
        $email_safe = db_escape($email);
        $check_existing = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_safe'");
        if ($check_existing && mysqli_num_rows($check_existing) > 0) {
            $err = "A user account with this email address already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $secret = generate_totp_secret();
            $ins = "INSERT INTO users (name, email, phone, password, role, two_factor_secret, two_factor_enabled) 
                    VALUES ('$name', '$email', '$phone', '$hash', 'superadmin', '$secret', 0)";
            
            if (mysqli_query($conn, $ins)) {
                $new_id = mysqli_insert_id($conn);
                log_security_action('SUPERADMIN_INITIALIZED', "Super Admin '$name' ($email) initialized via Master Setup Link", 'success', $new_id);
                $msg = "Super Administrator '$name' successfully initialized! You can now log into the Super Admin Gateway.";
                $superadmin_count++;
                if ($superadmin_count >= 2) $is_locked = true;
            } else {
                $err = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Super Admin Setup | Panda Realty</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .setup-card {
            background: #0f172a;
            border: 2px solid var(--admin-accent);
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            padding: 45px 40px;
            box-shadow: 0 0 50px rgba(197, 160, 89, 0.25);
        }
        .badge-setup {
            background: linear-gradient(135deg, var(--admin-accent), #dfb96f);
            color: #000;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 4px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 12px;
        }
    </style>
</head>
<body style="background: #060910; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div class="setup-card">
        <div style="text-align: center; margin-bottom: 25px;">
            <span class="badge-setup"><i class="fas fa-crown"></i> Production Setup Gateway</span>
            <div style="font-family: 'Playfair Display', serif; font-size: 26px; color: #fff;">
                PANDA <span style="color: var(--admin-accent);">REALTY</span>
            </div>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                Super Administrator Master Initialization
            </p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
            <div style="margin-top: 20px; text-align: center;">
                <a href="super-login.php" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px 28px; border-radius: 6px; display: inline-block;">
                    <i class="fas fa-key"></i> Proceed to Super Admin Gateway
                </a>
            </div>
        <?php elseif ($is_locked): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1.5px solid var(--admin-success); border-radius: 8px; padding: 25px; text-align: center; margin-bottom: 25px;">
                <i class="fas fa-shield-alt" style="font-size: 40px; color: var(--admin-success); margin-bottom: 12px;"></i>
                <h4 style="font-size: 18px; color: #fff; margin-bottom: 6px;">Super Admin Quota Established</h4>
                <p style="font-size: 13px; color: #cbd5e1; line-height: 1.6;">
                    The system already has the maximum of <strong>2 Super Administrators</strong> configured (e.g. Perpetuah & Root Super Admin). Additional administrators can be created directly from inside the Super Admin Dashboard.
                </p>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <a href="super-login.php" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px;">
                    <i class="fas fa-sign-in-alt"></i> Super Admin Login
                </a>
                <a href="../index.php" class="btn" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--admin-border); padding: 12px 20px; border-radius: 6px;">
                    Public Site
                </a>
            </div>
        <?php else: ?>
            <?php if (!empty($err)): ?>
                <div class="alert-box alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($err) ?></span>
                </div>
            <?php endif; ?>

            <div style="background: rgba(197, 160, 89, 0.1); border: 1px solid var(--admin-accent); border-radius: 8px; padding: 15px; font-size: 12px; color: #cbd5e1; margin-bottom: 20px;">
                <i class="fas fa-info-circle" style="color: var(--admin-accent);"></i> 
                Currently <strong><?= $superadmin_count ?>/2</strong> Super Admins exist. Use this portal to initialize your primary Super Administrator.
            </div>

            <form action="super-setup.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                <div class="admin-form-group">
                    <label style="color: var(--admin-accent);">Super Admin Name *</label>
                    <input type="text" name="name" placeholder="e.g. Perpetuah Chepchirchir" required style="background: #1e293b; border-color: #334155;">
                </div>

                <div class="admin-form-group">
                    <label style="color: var(--admin-accent);">Email Address *</label>
                    <input type="email" name="email" placeholder="perpetuah@pandarealty.co.ke" required style="background: #1e293b; border-color: #334155;">
                </div>

                <div class="admin-form-group">
                    <label style="color: var(--admin-accent);">Phone Number (WhatsApp)</label>
                    <input type="tel" name="phone" placeholder="0708 289 852" style="background: #1e293b; border-color: #334155;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="admin-form-group">
                        <label style="color: var(--admin-accent);">Master Password *</label>
                        <input type="password" name="password" placeholder="••••••••" required style="background: #1e293b; border-color: #334155;">
                    </div>
                    <div class="admin-form-group">
                        <label style="color: var(--admin-accent);">Confirm Password *</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required style="background: #1e293b; border-color: #334155;">
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%; background: linear-gradient(135deg, var(--admin-accent), #dfb96f); color: #000; font-weight: 800; padding: 14px; border-radius: 6px; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px;">
                    <i class="fas fa-crown"></i> Create Super Administrator
                </button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; font-size: 12px;">
            <a href="login.php" style="color: #94a3b8;"><i class="fas fa-shield-alt"></i> Staff Login</a>
            <a href="super-login.php" style="color: var(--admin-accent);"><i class="fas fa-key"></i> Super Admin Gateway</a>
        </div>
    </div>

</body>
</html>
