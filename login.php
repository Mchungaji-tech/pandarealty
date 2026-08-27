<?php
/**
 * Panda Realty - User Authentication & Client Registration Portal
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/totp.php';

$error_msg = '';
$success_msg = '';
$auth_notice = consume_auth_notice();
$conn = get_db_connection();

$redirect_target = sanitize_redirect_target($_GET['redirect'] ?? ($_SESSION['redirect_after_login'] ?? ''), 'index');

if (is_logged_in()) {
    if (is_admin()) {
        redirect_to('admin');
    } else {
        perform_redirect(build_redirect_url($redirect_target));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['auth_action'] ?? 'login');
    $csrf = clean_input($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $error_msg = "Session expired. Please try again.";
    } elseif ($action === 'login') {
        $email = clean_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rate_limit = get_rate_limit_state(['FAILED_USER_LOGIN']);

        if (empty($email) || empty($password)) {
            $error_msg = "Please provide both email and password.";
        } elseif ($rate_limit['locked']) {
            $error_msg = get_rate_limit_message($rate_limit['remaining_seconds']);
        } else {
            $email_safe = db_escape($email);
            $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email_safe' LIMIT 1");
            
            if ($res && $user = mysqli_fetch_assoc($res)) {
                $pass_valid = password_verify($password, $user['password']);

                if ($pass_valid) {
                    if ($user['two_factor_enabled'] && !empty($user['two_factor_secret'])) {
                        $post_2fa_redirect = in_array($user['role'], ['admin', 'superadmin'], true) ? 'admin' : $redirect_target;
                        start_two_factor_challenge($user, $post_2fa_redirect);
                        redirect_to('admin/2fa-verify');
                    }

                    finalize_user_login($user);
                    log_security_action('USER_LOGIN', "User {$user['name']} logged in", 'success', $user['id']);
                    unset($_SESSION['redirect_after_login']);

                    if (in_array($user['role'], ['admin', 'superadmin'], true)) {
                        redirect_to('admin');
                    }

                    perform_redirect(build_redirect_url($redirect_target));
                } else {
                    log_security_action('FAILED_USER_LOGIN', "Failed user login for {$email}", 'failed');
                    $error_msg = "Invalid email or password.";
                }
            } else {
                log_security_action('FAILED_USER_LOGIN', "Unknown user login for {$email}", 'failed');
                $error_msg = "Invalid email or password.";
            }
        }
    } elseif ($action === 'register') {
        $name = clean_input($_POST['reg_name'] ?? '');
        $email = clean_input($_POST['reg_email'] ?? '');
        $phone = clean_input($_POST['reg_phone'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm = $_POST['reg_confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error_msg = "Please fill in all required registration fields.";
        } elseif ($password !== $confirm) {
            $error_msg = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        } else {
            $email_safe = db_escape($email);
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_safe'");
            if (mysqli_num_rows($check) > 0) {
                $error_msg = "An account with this email address already exists. Please sign in.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $secret = generate_totp_secret();
                $name_safe = db_escape($name);
                $email_safe = db_escape($email);
                $phone_safe = db_escape($phone);
                $secret_safe = db_escape($secret);
                $hash_safe = db_escape($hash);
                $ins = "INSERT INTO users (name, email, phone, password, role, two_factor_secret, two_factor_enabled) 
                        VALUES ('$name_safe', '$email_safe', '$phone_safe', '$hash_safe', 'client', '$secret_safe', 0)";
                if (mysqli_query($conn, $ins)) {
                    $new_id = mysqli_insert_id($conn);
                    finalize_user_login([
                        'id' => $new_id,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'client'
                    ]);

                    log_security_action('CLIENT_REGISTERED', "New client account registered: $email", 'success', $new_id);
                    perform_redirect(build_redirect_url($redirect_target));
                } else {
                    $error_msg = "Registration Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

$page_title = "Sign In & Client Portal | Panda Realty";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div style="margin-top: 100px; padding: 60px 20px; min-height: 75vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; border: 1px solid var(--border); border-radius: 12px; max-width: 480px; width: 100%; padding: 45px; box-shadow: var(--card-shadow);">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <div class="logo-main" style="font-size: 26px;">PANDA <span style="color: var(--accent);">REALTY</span></div>
            <p style="font-size: 13px; color: var(--gray); margin-top: 4px;">Sign in to list properties, book tours, or access controls.</p>
        </div>

        <?php if (!empty($auth_notice)): ?>
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #1d4ed8; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($auth_notice) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #b91c1c; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <div style="background: rgba(15, 23, 42, 0.04); border: 1px solid var(--border); color: #334155; padding: 12px 16px; border-radius: 6px; font-size: 12px; margin-bottom: 20px;">
            <i class="fas fa-shield-alt" style="color: var(--accent);"></i> Sign in securely with your email and password to continue to bookings, consultations, and saved property activity.
        </div>

        <!-- Auth Tabs -->
        <div style="display: flex; border-bottom: 2px solid var(--border); margin-bottom: 25px;">
            <button type="button" id="tabSignInBtn" onclick="switchAuthTab('signin')" style="flex: 1; padding: 10px; background: transparent; border: none; font-weight: 700; font-size: 13px; color: var(--accent); border-bottom: 2px solid var(--accent); cursor: pointer; margin-bottom: -2px;">Sign In</button>
            <button type="button" id="tabRegisterBtn" onclick="switchAuthTab('register')" style="flex: 1; padding: 10px; background: transparent; border: none; font-weight: 600; font-size: 13px; color: var(--gray); cursor: pointer; margin-bottom: -2px;">Create Account</button>
        </div>

        <!-- Sign In Form -->
        <form action="<?= htmlspecialchars(app_path('login')) ?>?redirect=<?= urlencode($redirect_target) ?>" method="POST" id="signInForm">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="auth_action" value="login">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@pandarealty.co.ke" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; font-size: 13px; margin-top: 10px;">
                <i class="fas fa-sign-in-alt"></i> Sign In to Your Account
            </button>

            <div style="text-align: center; margin-top: 18px; font-size: 12px;">
                <span style="color: var(--gray);">New here?</span>
                <button type="button" onclick="switchAuthTab('register')" style="background: none; border: none; color: var(--accent); font-weight: 700; cursor: pointer; padding: 0; text-decoration: underline;">
                    Create a free client account →
                </button>
            </div>
        </form>

        <!-- Register Form -->
        <form action="<?= htmlspecialchars(app_path('login')) ?>?redirect=<?= urlencode($redirect_target) ?>" method="POST" id="registerForm" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="auth_action" value="register">

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="reg_name" placeholder="e.g. Samuel Kiprono" required>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="reg_email" placeholder="samuel@gmail.com" required>
            </div>

            <div class="form-group">
                <label>Phone Number (WhatsApp)</label>
                <input type="tel" name="reg_phone" placeholder="0708 289 852">
            </div>

            <div class="form-group">
                <label>Create Password *</label>
                <input type="password" name="reg_password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="reg_confirm" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 13px; margin-top: 10px;">
                <i class="fas fa-user-plus"></i> Complete Free Client Registration
            </button>

            <div style="text-align: center; margin-top: 18px; font-size: 12px;">
                <span style="color: var(--gray);">Already have an account?</span>
                <button type="button" onclick="switchAuthTab('signin')" style="background: none; border: none; color: var(--accent); font-weight: 700; cursor: pointer; padding: 0; text-decoration: underline;">
                    <i class="fas fa-sign-in-alt"></i> Sign In instead →
                </button>
            </div>
        </form>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); font-size: 12px; text-align: center;">
            <p style="color: var(--gray); margin: 0;">
                <i class="fas fa-lock"></i> All registrations here are <strong>client accounts only</strong> (for property buyers &amp; tenants).
                <br>Staff, Admin, CEO, or Super Admin accounts are managed internally by the Panda Realty team.
            </p>
        </div>
    </div>
</div>

<script>
function switchAuthTab(tab) {
    if (tab === 'signin') {
        document.getElementById('signInForm').style.display = 'block';
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('tabSignInBtn').style.color = 'var(--accent)';
        document.getElementById('tabSignInBtn').style.borderBottom = '2px solid var(--accent)';
        document.getElementById('tabRegisterBtn').style.color = 'var(--gray)';
        document.getElementById('tabRegisterBtn').style.borderBottom = 'none';
    } else {
        document.getElementById('signInForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
        document.getElementById('tabRegisterBtn').style.color = 'var(--accent)';
        document.getElementById('tabRegisterBtn').style.borderBottom = '2px solid var(--accent)';
        document.getElementById('tabSignInBtn').style.color = 'var(--gray)';
        document.getElementById('tabSignInBtn').style.borderBottom = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
