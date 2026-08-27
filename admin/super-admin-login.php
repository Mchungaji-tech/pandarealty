<?php
/**
 * Panda Realty — Super Admin Login (100% STANDALONE)
 * Uses ONLY super_admins table. Own isolated session namespace.
 * NO shared auth middleware. NO cross-role redirects.
 */

require_once __DIR__ . '/../config/functions.php';

function super_csrf_token() {
    if (empty($_SESSION['superadmin_csrf'])) {
        $_SESSION['superadmin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['superadmin_csrf'];
}
function super_verify_csrf($t) {
    return !empty($t) && hash_equals($_SESSION['superadmin_csrf'] ?? '', $t);
}
function super_client_ip() {
    foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) { $ip = trim(explode(',',$_SERVER[$k])[0]); if ($ip) return $ip; }
    }
    return '0.0.0.0';
}
function super_logged_in() {
    return !empty($_SESSION['superadmin_id']);
}

$msg = '';
$err = '';

if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
if (!empty($_SESSION['flash_err'])) {
    $err = $_SESSION['flash_err'];
    unset($_SESSION['flash_err']);
}
if (super_logged_in()) {
    header('Location: super-admin-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!super_verify_csrf($csrf)) {
        $err = "Security session expired. Refresh the page and try again.";
    } elseif ($email === '' || $password === '') {
        $err = "Enter both email and password.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $res = mysqli_query($conn, "SELECT * FROM super_admins WHERE email = '$safe_email' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['superadmin_id']    = (int)$row['id'];
                $_SESSION['superadmin_name']  = $row['full_name'];
                $_SESSION['superadmin_email'] = $row['email'];
                $_SESSION['superadmin_login_at'] = time();

                $id = (int)$row['id'];
                $ip = mysqli_real_escape_string($conn, super_client_ip());
                @mysqli_query($conn, "UPDATE super_admins SET is_online = 1, last_active = NOW(), last_login_ip = '$ip' WHERE id = $id");

                header('Location: super-admin-dashboard.php');
                exit;
            } else {
                $err = "Access denied. Wrong password.";
            }
        } else {
            $err = "Access denied. This email is not registered as a Super Admin.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Login — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#060910;--card:#0f172a;--accent:#c084fc;--accent2:#a855f7;--text:#fff;--muted:#94a3b8;--border:rgba(192,132,252,0.35);--danger:#ef4444;--success:#10b981}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .card{background:var(--card);border:2px solid var(--accent);border-radius:18px;max-width:460px;width:100%;padding:50px 40px;box-shadow:0 0 60px rgba(192,132,252,0.18)}
  .badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-bottom:16px}
  .logo{font-family:'Playfair Display',serif;font-size:30px;letter-spacing:2px;margin-bottom:6px}
  .logo span{color:var(--accent)}
  .sub{color:var(--muted);font-size:12px;margin-top:4px;margin-bottom:28px}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
  .alert.err{background:rgba(239,68,68,0.12);border:1px solid var(--danger);color:#fecaca}
  .alert.ok{background:rgba(16,185,129,0.12);border:1px solid var(--success);color:#a7f3d0}
  label{display:block;font-size:12px;font-weight:600;margin:0 0 6px;color:#cbd5e1}
  label i{color:var(--accent);margin-right:4px}
  input{width:100%;padding:12px 14px;border-radius:7px;border:1px solid #334155;background:#1e293b;color:#fff;font-size:13px;outline:none;margin-bottom:14px;transition:.2s}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 2px rgba(192,132,252,0.15)}
  button[type=submit]{width:100%;padding:14px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-weight:800;letter-spacing:1px;text-transform:uppercase;border:none;cursor:pointer;font-size:13px;margin-top:6px}
  button[type=submit]:hover{filter:brightness(1.08)}
  .foot{margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.08);font-size:12px}
  .row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
  a{color:var(--accent);text-decoration:none;font-weight:600}
  a.grey{color:var(--muted);font-weight:500}
  a.alt-blue{color:#60a5fa}
  a.alt-gold{color:#c5a059}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:12px;font-size:11px}
  .reg-box{background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.25);border-radius:8px;padding:12px 14px;margin-top:18px}
  .reg-box .ttl{color:var(--accent);font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px}
  .center{text-align:center}
</style>
</head>
<body>
<div class="card">
  <div class="center">
    <span class="badge"><i class="fas fa-crown"></i> Super Admin Only</span>
    <div class="logo">PANDA <span>REALTY</span></div>
    <div class="sub">Secure Control Gateway — Only registered Super Administrators.</div>
  </div>

  <?php if ($err !== ''): ?>
    <div class="alert err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if ($msg !== ''): ?>
    <div class="alert ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= super_csrf_token() ?>">
    <label><i class="fas fa-envelope"></i> Super Admin Email</label>
    <input type="email" name="email" placeholder="superadmin@pandarealty.co.ke" required autofocus>
    <label><i class="fas fa-lock"></i> Password</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button type="submit"><i class="fas fa-key"></i> Sign In as Super Admin</button>
  </form>

  <div class="reg-box">
    <div class="ttl"><i class="fas fa-user-plus"></i> Don't have a Super Admin account?</div>
    <a href="super-admin-register.php"><i class="fas fa-crown"></i> Register as Super Admin</a>
  </div>

  <div class="foot">
    <div class="row">
      <a class="grey" href="../index.php"><i class="fas fa-arrow-left"></i> Public Site</a>
      <a class="grey" href="choose-login.php"><i class="fas fa-th-large"></i> All Portals</a>
    </div>
    <div class="grid-2">
      <a class="alt-gold" href="ceo-login.php"><i class="fas fa-user-tie"></i> CEO Login</a>
      <a class="alt-blue" href="staff-login.php"><i class="fas fa-user-friends"></i> Staff Login</a>
      <a class="alt-gold" href="ceo-register.php"><i class="fas fa-plus"></i> CEO Register</a>
      <a class="alt-blue" href="staff-register.php"><i class="fas fa-plus"></i> Staff Register</a>
    </div>
  </div>
</div>
</body>
</html>
