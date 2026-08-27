<?php
/**
 * Panda Realty — Super Admin Registration (100% STANDALONE)
 * Writes ONLY to super_admins table. Own CSRF namespace.
 * Enforces max 2 Super Admins (hard quota).
 */

require_once __DIR__ . '/../config/functions.php';

function super_reg_csrf_token() {
    if (empty($_SESSION['superadmin_reg_csrf'])) {
        $_SESSION['superadmin_reg_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['superadmin_reg_csrf'];
}
function super_reg_verify_csrf($t) {
    return !empty($t) && hash_equals($_SESSION['superadmin_reg_csrf'] ?? '', $t);
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

$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM super_admins");
$row = $res ? mysqli_fetch_assoc($res) : null;
$count = $row ? (int)$row['cnt'] : 0;
$locked = $count >= 2;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!super_reg_verify_csrf($csrf)) {
        $err = "Security session expired. Refresh the page and try again.";
    } elseif ($name === '' || $email === '' || $password === '') {
        $err = "Full name, email, and password are required.";
    } elseif (strlen($password) < 10) {
        $err = "Super Admin password must be at least 10 characters.";
    } elseif ($password !== $confirm) {
        $err = "Passwords do not match.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM super_admins WHERE email = '$safe_email' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $err = "A Super Admin with that email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $safe_name = mysqli_real_escape_string($conn, $name);
            $safe_phone = mysqli_real_escape_string($conn, $phone);
            $safe_hash = mysqli_real_escape_string($conn, $hash);
            $sql = "INSERT INTO super_admins (full_name, email, phone, password_hash)
                    VALUES ('$safe_name', '$safe_email', '$safe_phone', '$safe_hash')";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash_msg'] = "✅ Super Admin account for $name created successfully! Please sign in below.";
                header('Location: super-admin-login.php');
                exit;
            } else {
                $err = "Database error: " . mysqli_error($conn);
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
<title>Register Super Admin — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#060910;--card:#0f172a;--accent:#c084fc;--accent2:#a855f7;--text:#fff;--muted:#94a3b8;--border:rgba(192,132,252,0.35);--danger:#ef4444;--success:#10b981}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .card{background:var(--card);border:2px solid var(--accent);border-radius:18px;max-width:540px;width:100%;padding:50px 40px;box-shadow:0 0 60px rgba(192,132,252,0.18)}
  .badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-bottom:16px}
  .logo{font-family:'Playfair Display',serif;font-size:30px;letter-spacing:2px;margin-bottom:6px}
  .logo span{color:var(--accent)}
  .sub{color:var(--muted);font-size:12px;margin-top:4px;margin-bottom:28px}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
  .alert.err{background:rgba(239,68,68,0.12);border:1px solid var(--danger);color:#fecaca}
  .alert.ok{background:rgba(16,185,129,0.12);border:1px solid var(--success);color:#a7f3d0}
  .locked{background:rgba(16,185,129,0.1);border:1.5px solid var(--success);border-radius:8px;padding:22px;text-align:center;margin-bottom:18px}
  label{display:block;font-size:12px;font-weight:600;margin:0 0 6px;color:#cbd5e1}
  label i{color:var(--accent);margin-right:4px}
  input{width:100%;padding:12px 14px;border-radius:7px;border:1px solid #334155;background:#1e293b;color:#fff;font-size:13px;outline:none;margin-bottom:14px;transition:.2s}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 2px rgba(192,132,252,0.15)}
  button[type=submit]{width:100%;padding:14px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-weight:800;letter-spacing:1px;text-transform:uppercase;border:none;cursor:pointer;font-size:13px;margin-top:6px}
  button[type=submit]:hover{filter:brightness(1.08)}
  .btn-alt{display:inline-block;padding:12px 22px;border-radius:7px;background:rgba(255,255,255,0.05);border:1px solid #334155;color:#fff;text-decoration:none;font-weight:700;font-size:13px}
  .row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:18px;font-size:12px}
  a{color:var(--accent);text-decoration:none;font-weight:600}
  a.grey{color:var(--muted);font-weight:500}
  a.alt-gold{color:#c5a059}
  a.alt-blue{color:#60a5fa}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:12px;font-size:11px}
  .grid-2.inputs{gap:10px 16px;margin-bottom:4px}
  .grid-2.inputs > div label{margin-bottom:6px}
  .count{background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.25);border-radius:6px;padding:10px 14px;font-size:11px;color:#cbd5e1;margin-bottom:18px}
  .count strong{color:#fff}
  .cap{background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.25);border-radius:8px;padding:14px;margin:6px 0 18px}
  .cap h5{margin:0 0 8px;color:#fff;font-size:12px}
  .cap div{display:grid;grid-template-columns:1fr 1fr;gap:4px 10px;font-size:10px;color:#cbd5e1}
  .cap div i{color:var(--accent);margin-right:3px}
  .sec{color:#fca5a5;font-size:10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:6px;padding:10px 12px;margin:0 0 18px}
  .center{text-align:center}
  .foot{margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.08);font-size:12px}
  .ctas{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:10px}
</style>
</head>
<body>
<div class="card">
  <div class="center">
    <span class="badge"><i class="fas fa-crown"></i> Super Admin Registration</span>
    <div class="logo">PANDA <span>REALTY</span></div>
    <div class="sub">Full executive control across the platform.</div>
  </div>

  <?php if ($msg !== ''): ?>
    <div class="alert ok"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
    <div class="ctas">
      <a href="super-admin-login.php" style="padding:12px 22px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;text-decoration:none;font-weight:800;font-size:13px">
        <i class="fas fa-key"></i> Sign In as Super Admin
      </a>
    </div>
  <?php elseif ($locked): ?>
    <div class="locked">
      <i class="fas fa-shield-alt" style="font-size:36px;color:var(--success);margin-bottom:10px"></i>
      <h4 style="margin:0 0 6px;color:#fff">Super Admin Quota Established</h4>
      <p style="font-size:12px;color:#cbd5e1;margin:0 0 12px">
        Max of <strong style="color:var(--success)">2 Super Administrators</strong> reached.
      </p>
      <div class="ctas">
        <a href="super-admin-login.php" style="padding:11px 20px;border-radius:6px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;text-decoration:none;font-weight:700;font-size:12px">
          <i class="fas fa-sign-in-alt"></i> Super Admin Login
        </a>
        <a href="ceo-register.php" class="btn-alt"><i class="fas fa-user-tie"></i> Register CEO</a>
      </div>
    </div>
  <?php else: ?>
    <?php if ($err !== ''): ?>
      <div class="alert err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <div class="count">
      <i class="fas fa-chart-pie" style="color:var(--accent);margin-right:6px"></i>
      Currently <strong><?= $count ?>/2</strong> Super Admin slots in use.
    </div>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= super_reg_csrf_token() ?>">
      <label><i class="fas fa-user-shield"></i> Full Name</label>
      <input type="text" name="full_name" placeholder="Full Super Admin name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      <div class="grid-2 inputs">
        <div>
          <label><i class="fas fa-envelope"></i> Executive Email</label>
          <input type="email" name="email" placeholder="superadmin@pandarealty.co.ke" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div>
          <label><i class="fas fa-phone"></i> Direct Phone</label>
          <input type="tel" name="phone" placeholder="+254 700 000 000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="cap">
        <h5><i class="fas fa-key" style="color:var(--accent)"></i> Full Executive Capabilities</h5>
        <div>
          <span><i class="fas fa-check"></i> All CEO Features</span>
          <span><i class="fas fa-check"></i> Security Logs</span>
          <span><i class="fas fa-check"></i> Live Visitors</span>
          <span><i class="fas fa-check"></i> Maintenance</span>
          <span><i class="fas fa-check"></i> System Settings</span>
          <span><i class="fas fa-check"></i> All User Roles</span>
        </div>
      </div>
      <div class="grid-2 inputs">
        <div>
          <label><i class="fas fa-lock"></i> Master Password <span style="font-size:9px;color:var(--muted);font-weight:500">(min 10 chars)</span></label>
          <input type="password" name="password" placeholder="10+ characters" required>
        </div>
        <div>
          <label><i class="fas fa-lock"></i> Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Re-enter" required>
        </div>
      </div>
      <div class="sec"><i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-right:5px"></i><strong>SECURITY:</strong> Enable Google 2FA immediately after registration. Never share credentials via email/SMS.</div>
      <button type="submit"><i class="fas fa-crown"></i> Create Super Administrator</button>
    </form>
  <?php endif; ?>

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
