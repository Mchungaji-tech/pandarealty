<?php
/**
 * Panda Realty — Staff Registration (100% STANDALONE)
 * Writes ONLY to staff_members table. Own CSRF namespace.
 */

require_once __DIR__ . '/../config/functions.php';

function staff_reg_csrf_token() {
    if (empty($_SESSION['staff_reg_csrf'])) {
        $_SESSION['staff_reg_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['staff_reg_csrf'];
}
function staff_reg_verify_csrf($t) {
    return !empty($t) && hash_equals($_SESSION['staff_reg_csrf'] ?? '', $t);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim((string)($_POST['full_name'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $phone    = trim((string)($_POST['phone'] ?? ''));
    $dept     = trim((string)($_POST['department'] ?? ''));
    $position = trim((string)($_POST['position_title'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $csrf     = $_POST['csrf_token'] ?? '';

    if (!staff_reg_verify_csrf($csrf)) {
        $err = "Security session expired. Refresh the page and try again.";
    } elseif ($name === '' || $email === '' || $password === '') {
        $err = "Full name, email, and password are required.";
    } elseif (strlen($password) < 8) {
        $err = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $err = "Passwords do not match.";
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM staff_members WHERE email = '$safe_email' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $err = "A Staff account with that email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $safe_name = mysqli_real_escape_string($conn, $name);
            $safe_phone = mysqli_real_escape_string($conn, $phone);
            $safe_dept = mysqli_real_escape_string($conn, $dept);
            $safe_pos = mysqli_real_escape_string($conn, $position);
            $safe_hash = mysqli_real_escape_string($conn, $hash);
            $sql = "INSERT INTO staff_members (full_name, email, phone, department, position_title, password_hash)
                    VALUES ('$safe_name', '$safe_email', '$safe_phone', '$safe_dept', '$safe_pos', '$safe_hash')";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash_msg'] = "✅ Staff account for $name created successfully! Please sign in below.";
                header('Location: staff-login.php');
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
<title>Register Staff — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#0b1220;--card:#111a2e;--accent:#60a5fa;--accent2:#3b82f6;--text:#fff;--muted:#94a3b8;--danger:#ef4444;--success:#10b981}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
  .card{background:var(--card);border:2px solid var(--accent);border-radius:18px;max-width:540px;width:100%;padding:50px 40px;box-shadow:0 0 60px rgba(59,130,246,0.12)}
  .badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-size:10px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 16px;border-radius:20px;margin-bottom:16px}
  .logo{font-family:'Playfair Display',serif;font-size:30px;letter-spacing:2px;margin-bottom:6px}
  .logo span{color:var(--accent)}
  .sub{color:var(--muted);font-size:12px;margin-top:4px;margin-bottom:28px}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px}
  .alert.err{background:rgba(239,68,68,0.12);border:1px solid var(--danger);color:#fecaca}
  .alert.ok{background:rgba(16,185,129,0.12);border:1px solid var(--success);color:#a7f3d0}
  label{display:block;font-size:12px;font-weight:600;margin:0 0 6px;color:#cbd5e1}
  label i{color:var(--accent);margin-right:4px}
  input, select{width:100%;padding:12px 14px;border-radius:7px;border:1px solid #1e293b;background:#0f172a;color:#fff;font-size:13px;outline:none;margin-bottom:14px;transition:.2s;font-family:inherit}
  input:focus, select:focus{border-color:var(--accent);box-shadow:0 0 0 2px rgba(96,165,250,0.15)}
  button[type=submit]{width:100%;padding:14px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;font-weight:800;letter-spacing:1px;text-transform:uppercase;border:none;cursor:pointer;font-size:13px;margin-top:6px}
  .row{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:18px;font-size:12px}
  a{color:var(--accent);text-decoration:none;font-weight:600}
  a.grey{color:var(--muted);font-weight:500}
  a.alt-purple{color:#c084fc}
  a.alt-gold{color:#c5a059}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-top:12px;font-size:11px}
  .grid-2.inputs{gap:10px 16px;margin-bottom:4px}
  .cap{background:rgba(96,165,250,0.08);border:1px solid rgba(96,165,250,0.25);border-radius:8px;padding:14px;margin:6px 0 18px}
  .cap h5{margin:0 0 8px;color:#fff;font-size:12px}
  .cap p{margin:0;color:var(--muted);font-size:11px;line-height:1.6}
  .center{text-align:center}
  .foot{margin-top:26px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.06);font-size:12px}
  .ctas{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:10px}
</style>
</head>
<body>
<div class="card">
  <div class="center">
    <span class="badge"><i class="fas fa-user-friends"></i> Staff Registration</span>
    <div class="logo">PANDA <span>REALTY</span></div>
    <div class="sub">Frontline staff CRM access for tours, inquiries, follow-up.</div>
  </div>

  <?php if ($msg !== ''): ?>
    <div class="alert ok"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
    <div class="ctas">
      <a href="staff-login.php" style="padding:12px 22px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;text-decoration:none;font-weight:800;font-size:13px">
        <i class="fas fa-sign-in-alt"></i> Sign In as Staff
      </a>
    </div>
  <?php else: ?>
    <?php if ($err !== ''): ?>
      <div class="alert err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= staff_reg_csrf_token() ?>">
      <label><i class="fas fa-user"></i> Full Name</label>
      <input type="text" name="full_name" placeholder="Staff Full Name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      <div class="grid-2 inputs">
        <div>
          <label><i class="fas fa-envelope"></i> Email</label>
          <input type="email" name="email" placeholder="staff@pandarealty.co.ke" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div>
          <label><i class="fas fa-phone"></i> Phone (WhatsApp)</label>
          <input type="tel" name="phone" placeholder="0700 000 000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>
      <div class="grid-2 inputs">
        <div>
          <label><i class="fas fa-building"></i> Department</label>
          <select name="department">
            <option value="">— Select —</option>
            <option <?= ($_POST['department'] ?? '') === 'Sales' ? 'selected' : '' ?>>Sales</option>
            <option <?= ($_POST['department'] ?? '') === 'Operations' ? 'selected' : '' ?>>Operations</option>
            <option <?= ($_POST['department'] ?? '') === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
            <option <?= ($_POST['department'] ?? '') === 'Customer Support' ? 'selected' : '' ?>>Customer Support</option>
            <option <?= ($_POST['department'] ?? '') === 'Administration' ? 'selected' : '' ?>>Administration</option>
            <option <?= ($_POST['department'] ?? '') === 'Finance' ? 'selected' : '' ?>>Finance</option>
          </select>
        </div>
        <div>
          <label><i class="fas fa-id-badge"></i> Position / Title</label>
          <input type="text" name="position_title" placeholder="e.g. Property Consultant" value="<?= htmlspecialchars($_POST['position_title'] ?? '') ?>">
        </div>
      </div>
      <div class="cap">
        <h5><i class="fas fa-clipboard-list" style="color:var(--accent)"></i> Staff Role Capabilities</h5>
        <p>Staff CRM Workspace, Pipeline, Property management, Bookings & Tours, Inquiry handling, Tasks.</p>
      </div>
      <div class="grid-2 inputs">
        <div>
          <label><i class="fas fa-lock"></i> Password <span style="font-size:9px;color:var(--muted)">(min 8)</span></label>
          <input type="password" name="password" placeholder="Min 8 chars" required>
        </div>
        <div>
          <label><i class="fas fa-lock"></i> Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Re-enter" required>
        </div>
      </div>
      <button type="submit"><i class="fas fa-user-plus"></i> Register Staff Account</button>
    </form>
  <?php endif; ?>

  <div class="foot">
    <div class="row">
      <a class="grey" href="../index.php"><i class="fas fa-arrow-left"></i> Public Site</a>
      <a class="grey" href="choose-login.php"><i class="fas fa-th-large"></i> All Portals</a>
    </div>
    <div class="grid-2">
      <a class="alt-gold" href="ceo-login.php"><i class="fas fa-user-tie"></i> CEO Login</a>
      <a class="alt-purple" href="super-admin-login.php"><i class="fas fa-crown"></i> Super Admin</a>
      <a class="alt-gold" href="ceo-register.php"><i class="fas fa-plus"></i> CEO Register</a>
      <a class="alt-purple" href="super-admin-register.php"><i class="fas fa-plus"></i> Super Admin Reg</a>
    </div>
  </div>
</div>
</body>
</html>
