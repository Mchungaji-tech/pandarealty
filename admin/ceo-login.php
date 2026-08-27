<?php
/**
 * Panda Realty — CEO Login (100% STANDALONE)
 * Uses ONLY ceo_users table. Own isolated session namespace.
 * NO shared auth middleware. NO cross-role redirects.
 */

require_once __DIR__ . '/../config/functions.php';

function ceo_csrf_token() {
    if (empty($_SESSION['ceo_csrf'])) {
        $_SESSION['ceo_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ceo_csrf'];
}
function ceo_verify_csrf($t) {
    return !empty($t) && hash_equals($_SESSION['ceo_csrf'] ?? '', $t);
}
function ceo_client_ip() {
    foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) { $ip = trim(explode(',',$_SERVER[$k])[0]); if ($ip) return $ip; }
    }
    return '0.0.0.0';
}
function ceo_logged_in() {
    return !empty($_SESSION['ceo_id']);
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
if (ceo_logged_in()) {
    header('Location: ceo-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!ceo_verify_csrf($csrf)) {
        $err = "Security session expired. Refresh the page and try again.";
    } elseif ($email === '' || $password === '') {
        $err = "Enter both email and password.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $res = mysqli_query($conn, "SELECT * FROM ceo_users WHERE email = '$safe_email' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['ceo_id']    = (int)$row['id'];
                $_SESSION['ceo_name']  = $row['full_name'];
                $_SESSION['ceo_email'] = $row['email'];
                $_SESSION['ceo_login_at'] = time();

                $id = (int)$row['id'];
                $ip = mysqli_real_escape_string($conn, ceo_client_ip());
                @mysqli_query($conn, "UPDATE ceo_users SET is_online = 1, last_active = NOW(), last_login_ip = '$ip' WHERE id = $id");

                header('Location: ceo-dashboard.php');
                exit;
            } else {
                $err = "Access denied. Wrong password.";
            }
        } else {
            $err = "Access denied. This email is not registered as a CEO.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CEO Login — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#0a0a0a;--card:#111318;--accent:#c5a059;--accent2:#dfb96f;--text:#fff;--muted:#94a3b8;--border:rgba(197,160,89,0.35);--danger:#ef4444;--success:#10b981}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .card{background:var(--card);border:2px solid var(--accent);border-radius:18px;max-width:460px;width:100%;padding:50px 40px;box-shadow:0 0 60px rgba(197,160,89,0.12)}
  .badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-bottom:16px}
  .logo{font-family:'Playfair Display',serif;font-size:30px;letter-spacing:2px;margin-bottom:6px}
  .logo span{color:var(--accent)}
  .sub{color:var(--muted);font-size:12px;margin-top:4px;margin-bottom:28px}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
  .alert.err{background:rgba(239,68,68,0.12);border:1px solid var(--danger);color:#fecaca}
  .alert.ok{background:rgba(16,185,129,0.12);border:1px solid var(--success);color:#a7f3d0}
  label{display:block;font-size:12px;font-weight:600;margin:0 0 6px;color:#cbd5e1}
  label i{color:var(--accent);margin-right:4px}
  input{width:100%;padding:12px 14px;border-radius:7px;border:1px solid #2a2a2a;background:#17181d;color:#fff;font-size:13px;outline:none;margin-bottom:14px;transition:.2s}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 2px rgba(197,160,89,0.15)}
  button[type=submit]{width:100%;padding:14px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000;font-weight:800;letter-spacing:1px;text-transform:uppercase;border:none;cursor:pointer;font-size:13px;margin-top:6px}
  button[type=submit]:hover{filter:brightness(1.06)}
  .foot{margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.06);font-size:12px}
  .row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
  a{color:var(--accent);text-decoration:none;font-weight:600}
  a.grey{color:var(--muted);font-weight:500}
  a.alt-purple{color:#c084fc}
  a.alt-blue{color:#60a5fa}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:12px;font-size:11px}
  .reg-box{background:rgba(197,160,89,0.08);border:1px solid rgba(197,160,89,0.25);border-radius:8px;padding:12px 14px;margin-top:18px}
  .reg-box .ttl{color:var(--accent);font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px}
  .center{text-align:center}
</style>
</head>
<body>
<div class="card">
  <div class="center">
    <span class="badge"><i class="fas fa-user-tie"></i> CEO Only</span>
    <div class="logo">PANDA <span>REALTY</span></div>
    <div class="sub">Executive Portal — Only registered CEO accounts.</div>
  </div>

  <?php if ($err !== ''): ?>
    <div class="alert err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if ($msg !== ''): ?>
    <div class="alert ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= ceo_csrf_token() ?>">
    <label><i class="fas fa-envelope"></i> CEO Email</label>
    <input type="email" name="email" placeholder="ceo@pandarealty.co.ke" required autofocus>
    <label><i class="fas fa-lock"></i> Password</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button type="submit"><i class="fas fa-sign-in-alt"></i> Sign In as CEO</button>
  </form>

  <div class="reg-box">
    <div class="ttl"><i class="fas fa-user-plus"></i> No CEO account yet?</div>
    <a href="ceo-register.php"><i class="fas fa-user-tie"></i> Register a CEO account</a>
  </div>

  <div class="foot">
    <div class="row">
      <a class="grey" href="../index.php"><i class="fas fa-arrow-left"></i> Public Site</a>
      <a class="grey" href="choose-login.php"><i class="fas fa-th-large"></i> All Portals</a>
    </div>
    <div class="grid-2">
      <a class="alt-purple" href="super-admin-login.php"><i class="fas fa-crown"></i> Super Admin</a>
      <a class="alt-blue" href="staff-login.php"><i class="fas fa-user-friends"></i> Staff Login</a>
      <a class="alt-purple" href="super-admin-register.php"><i class="fas fa-plus"></i> Super Admin Reg</a>
      <a class="alt-blue" href="staff-register.php"><i class="fas fa-plus"></i> Staff Register</a>
    </div>
  </div>
</div>
</body>
</html>
