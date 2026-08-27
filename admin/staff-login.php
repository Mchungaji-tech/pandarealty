<?php
/**
 * Panda Realty — Staff Login (100% STANDALONE)
 * Uses ONLY staff_members table. Own isolated session namespace.
 * NO shared auth middleware. NO cross-role redirects.
 */

require_once __DIR__ . '/../config/functions.php';

function staff_csrf_token() {
    if (empty($_SESSION['staff_csrf'])) {
        $_SESSION['staff_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['staff_csrf'];
}
function staff_verify_csrf($t) {
    return !empty($t) && hash_equals($_SESSION['staff_csrf'] ?? '', $t);
}
function staff_client_ip() {
    foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) { $ip = trim(explode(',',$_SERVER[$k])[0]); if ($ip) return $ip; }
    }
    return '0.0.0.0';
}
function staff_logged_in() {
    return !empty($_SESSION['staff_id']);
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
if (staff_logged_in()) {
    header('Location: staff-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!staff_verify_csrf($csrf)) {
        $err = "Security session expired. Refresh the page and try again.";
    } elseif ($email === '' || $password === '') {
        $err = "Enter both email and password.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $res = mysqli_query($conn, "SELECT * FROM staff_members WHERE email = '$safe_email' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['staff_id']    = (int)$row['id'];
                $_SESSION['staff_name']  = $row['full_name'];
                $_SESSION['staff_email'] = $row['email'];
                $_SESSION['staff_dept']  = (string)($row['department'] ?? '');
                $_SESSION['staff_role']  = 'staff';
                $_SESSION['staff_login_at'] = time();

                $id = (int)$row['id'];
                $ip = mysqli_real_escape_string($conn, staff_client_ip());
                @mysqli_query($conn, "UPDATE staff_members SET is_online = 1, last_active = NOW(), last_login_ip = '$ip' WHERE id = $id");

                header('Location: staff-dashboard.php');
                exit;
            } else {
                $err = "Access denied. Wrong password.";
            }
        } else {
            $err = "Access denied. This email is not registered as Staff.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Login — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#0a0f1c;--card:#11172a;--accent:#3b82f6;--accent2:#60a5fa;--text:#fff;--muted:#94a3b8;--border:rgba(59,130,246,0.35);--danger:#ef4444;--success:#10b981}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .card{background:var(--card);border:2px solid var(--accent);border-radius:18px;max-width:460px;width:100%;padding:50px 40px;box-shadow:0 0 60px rgba(59,130,246,0.12)}
  .badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-bottom:16px}
  .logo{font-family:'Playfair Display',serif;font-size:30px;letter-spacing:2px;margin-bottom:6px}
  .logo span{color:var(--accent)}
  .sub{color:var(--muted);font-size:12px;margin-top:4px;margin-bottom:28px}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
  .alert.err{background:rgba(239,68,68,0.12);border:1px solid var(--danger);color:#fecaca}
  .alert.ok{background:rgba(16,185,129,0.12);border:1px solid var(--success);color:#a7f3d0}
  label{display:block;font-size:12px;font-weight:600;margin:0 0 6px;color:#cbd5e1}
  label i{color:var(--accent);margin-right:4px}
  input{width:100%;padding:12px 14px;border-radius:7px;border:1px solid #2a2a3e;background:#171c2e;color:#fff;font-size:13px;outline:none;margin-bottom:14px;transition:.2s}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 2px rgba(59,130,246,0.15)}
  button[type=submit]{width:100%;padding:14px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-weight:800;letter-spacing:1px;text-transform:uppercase;border:none;cursor:pointer;font-size:13px;margin-top:6px}
  button[type=submit]:hover{filter:brightness(1.06)}
  .foot{margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.06);font-size:12px}
  .row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
  a{color:var(--accent);text-decoration:none;font-weight:600}
  a.grey{color:var(--muted);font-weight:500}
  a.alt-gold{color:#dfb96f}
  a.alt-purple{color:#c084fc}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:12px;font-size:11px}
  .reg-box{background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:8px;padding:12px 14px;margin-top:18px}
  .reg-box .ttl{color:var(--accent);font-size:11px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px}
  .center{text-align:center}
</style>
</head>
<body>
<div class="card">
  <div class="center">
    <span class="badge"><i class="fas fa-user-friends"></i> Staff Only</span>
    <div class="logo">PANDA <span>REALTY</span></div>
    <div class="sub">Operations Portal — Only registered Staff accounts.</div>
  </div>

  <?php if ($err !== ''): ?>
    <div class="alert err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <?php if ($msg !== ''): ?>
    <div class="alert ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= staff_csrf_token() ?>">
    <label><i class="fas fa-envelope"></i> Staff Email</label>
    <input type="email" name="email" placeholder="staff@pandarealty.co.ke" required autofocus>
    <label><i class="fas fa-lock"></i> Password</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button type="submit"><i class="fas fa-sign-in-alt"></i> Sign In as Staff</button>
  </form>

  <div class="reg-box">
    <div class="ttl"><i class="fas fa-user-plus"></i> No Staff account yet?</div>
    <a href="staff-register.php"><i class="fas fa-user-friends"></i> Register a Staff account</a>
  </div>

  <div class="foot">
    <div class="row">
      <a class="grey" href="../index.php"><i class="fas fa-arrow-left"></i> Public Site</a>
      <a class="grey" href="choose-login.php"><i class="fas fa-th-large"></i> All Portals</a>
    </div>
    <div class="grid-2">
      <a class="alt-purple" href="super-admin-login.php"><i class="fas fa-crown"></i> Super Admin</a>
      <a class="alt-gold" href="ceo-login.php"><i class="fas fa-user-tie"></i> CEO Login</a>
      <a class="alt-purple" href="super-admin-register.php"><i class="fas fa-plus"></i> Super Admin Reg</a>
      <a class="alt-gold" href="ceo-register.php"><i class="fas fa-plus"></i> CEO Register</a>
    </div>
  </div>
</div>
</body>
</html>