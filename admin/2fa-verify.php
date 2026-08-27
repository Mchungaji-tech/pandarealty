<?php
/**
 * Panda Realty - Google 2FA Authenticator Verification
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/totp.php';

$error_msg = '';
$conn = get_db_connection();
$pending_role = $_SESSION['2fa_pending_role'] ?? 'admin';
$cancel_path = $pending_role === 'superadmin' ? 'admin/super-login' : 'admin/login';

if (empty($_SESSION['2fa_pending_user_id'])) {
    redirect_to($cancel_path);
}

$pending_user_id = (int)$_SESSION['2fa_pending_user_id'];
$res = mysqli_query($conn, "SELECT * FROM users WHERE id = $pending_user_id LIMIT 1");
if (!$res || !$user = mysqli_fetch_assoc($res)) {
    clear_pending_two_factor_session();
    redirect_to($cancel_path);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim(clean_input($_POST['two_factor_code'] ?? ''));
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $rate_limit = get_rate_limit_state(['2FA_FAILED']);

    if (!verify_csrf_token($csrf)) {
        $error_msg = "Session expired. Please try again.";
    } elseif (empty($code)) {
        $error_msg = "Please enter the 6-digit Google Authenticator code.";
    } elseif ($rate_limit['locked']) {
        $error_msg = get_rate_limit_message($rate_limit['remaining_seconds']);
    } else {
        $secret = $user['two_factor_secret'];
        
        if (verify_totp_code($secret, $code)) {
            finalize_user_login($user);
            log_security_action('2FA_VERIFIED', "User {$user['name']} completed 2FA challenge", 'success', $user['id']);

            $redirect = sanitize_redirect_target($_SESSION['2fa_redirect'] ?? 'admin', 'admin');
            perform_redirect(build_redirect_url($redirect));
        } else {
            log_security_action('2FA_FAILED', "Failed 2FA code attempt for user {$user['email']}", 'failed', $user['id']);
            $error_msg = "Invalid verification code. Please check your Google Authenticator app.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification | Panda Realty</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="background: var(--admin-bg); color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div style="background: var(--admin-card); border: 1px solid var(--admin-border); border-radius: 12px; max-width: 440px; width: 100%; padding: 45px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); text-align: center;">
        
        <div style="width: 65px; height: 65px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); color: var(--admin-accent); display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 20px;">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h3 class="font-serif" style="font-size: 24px; margin-bottom: 6px;">2-Step Verification</h3>
        <p style="font-size: 13px; color: var(--admin-muted); margin-bottom: 25px;">
            Enter the 6-digit verification code from your Google Authenticator app for <strong><?= htmlspecialchars($user['email']) ?></strong>.
        </p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-box alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <form action="2fa-verify.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

            <div class="admin-form-group">
                <input type="text" name="two_factor_code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus style="text-align: center; font-size: 28px; letter-spacing: 8px; font-weight: 700; font-family: monospace; padding: 14px;">
            </div>

            <button type="submit" class="btn" style="width: 100%; background: var(--admin-accent); color: var(--admin-bg); padding: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; border: none; font-size: 13px; margin-top: 10px;">
                <i class="fas fa-check-circle"></i> Verify Code & Continue
            </button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--admin-border); font-size: 12px;">
            <a href="<?= htmlspecialchars(app_path($cancel_path)) ?>" style="color: var(--admin-muted);"><i class="fas fa-arrow-left"></i> Cancel & Return to Login</a>
        </div>
    </div>

</body>
</html>
