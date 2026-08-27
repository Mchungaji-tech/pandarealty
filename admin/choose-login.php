<?php
/**
 * Panda Realty — Portal / Role Chooser (public landing)
 * No middleware, no auth redirect, no shared sessions.
 */
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal Login — Panda Realty</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{--bg:#05070b;--card:#10131a;--text:#fff;--muted:#94a3b8;--border:rgba(255,255,255,0.08)}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;padding:30px 18px}
  .wrap{max-width:1100px;margin:0 auto}
  .head{text-align:center;padding:20px 10px 30px}
  .logo{font-family:'Playfair Display',serif;font-size:36px;letter-spacing:3px;margin-bottom:6px}
  .logo span{color:#c5a059}
  .sub{color:var(--muted);font-size:13px;margin:0 0 6px}
  .headline{font-size:22px;letter-spacing:1px;margin:18px 0 4px;font-weight:700}
  .tag{color:var(--muted);font-size:12px}
  .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:28px}
  @media(max-width:900px){.grid{grid-template-columns:repeat(2,1fr)}}
  @media(max-width:520px){.grid{grid-template-columns:1fr}}
  .tile{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 22px;transition:.2s;position:relative;overflow:hidden}
  .tile:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,0.45)}
  .tile.purple{border-top:4px solid #c084fc;background:linear-gradient(180deg,rgba(192,132,252,0.1),transparent)}
  .tile.gold{border-top:4px solid #c5a059;background:linear-gradient(180deg,rgba(197,160,89,0.1),transparent)}
  .tile.blue{border-top:4px solid #60a5fa;background:linear-gradient(180deg,rgba(96,165,250,0.1),transparent)}
  .tile.client{border-top:4px solid #10b981;background:linear-gradient(180deg,rgba(16,185,129,0.08),transparent)}
  .icon{width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px}
  .icon.purple{background:rgba(192,132,252,0.2);color:#c084fc}
  .icon.gold{background:rgba(197,160,89,0.2);color:#c5a059}
  .icon.blue{background:rgba(96,165,250,0.2);color:#60a5fa}
  .icon.client{background:rgba(16,185,129,0.2);color:#10b981}
  .tile h3{margin:0 0 4px;font-size:17px;font-weight:700}
  .tile p{color:var(--muted);font-size:12px;margin:0 0 18px;line-height:1.6}
  .btn-row{display:flex;gap:8px;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;border-radius:7px;text-decoration:none;font-weight:700;font-size:12px;letter-spacing:0.3px;border:none;cursor:pointer}
  .btn.primary.purple{background:linear-gradient(135deg,#c084fc,#a855f7);color:#fff}
  .btn.primary.gold{background:linear-gradient(135deg,#c5a059,#dfb96f);color:#000}
  .btn.primary.blue{background:linear-gradient(135deg,#60a5fa,#3b82f6);color:#fff}
  .btn.primary.client{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
  .btn.ghost{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.12);color:#fff}
  .btn:hover{filter:brightness(1.08)}
  .sep{height:1px;background:var(--border);margin:30px 0 10px}
  .back{display:inline-flex;gap:6px;align-items:center;color:var(--muted);text-decoration:none;font-size:12px;margin-bottom:18px}
  .back:hover{color:#fff}
  .chips{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:14px}
  .chip{padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;border:1px solid var(--border);color:var(--muted)}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="../index.php"><i class="fas fa-arrow-left"></i> Back to Panda Realty public site</a>
  <div class="head">
    <div class="logo">PANDA <span>REALTY</span></div>
    <p class="sub">Designed & Developed by TekTrend • Eldoret, Kenya</p>
    <div class="headline">Portal Login & Registration</div>
    <p class="tag">Choose the access level that matches your role. Each portal is 100% separate.</p>
    <div class="chips">
      <span class="chip">Separate Tables</span>
      <span class="chip">Separate Sessions</span>
      <span class="chip">No Shared Middleware</span>
    </div>
  </div>

  <div class="grid">
    <div class="tile purple">
      <div class="icon purple"><i class="fas fa-shield-alt" style="font-size:22px"></i></div>
      <h3>Super Admin</h3>
      <p>Full executive + technical control. Security logs, settings, and all role management.</p>
      <div class="btn-row">
        <a class="btn primary purple" href="super-admin-login.php"><i class="fas fa-key"></i> Sign In</a>
        <a class="btn ghost" href="super-admin-register.php"><i class="fas fa-user-plus"></i> Register</a>
      </div>
    </div>

    <div class="tile gold">
      <div class="icon gold"><i class="fas fa-user-tie" style="font-size:22px"></i></div>
      <h3>CEO / Executive</h3>
      <p>Business visibility, reports, pipeline, finance, People & Roles (non-super-admin).</p>
      <div class="btn-row">
        <a class="btn primary gold" href="ceo-login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <a class="btn ghost" href="ceo-register.php"><i class="fas fa-user-plus"></i> Register</a>
      </div>
    </div>

    <div class="tile blue">
      <div class="icon blue"><i class="fas fa-user-friends" style="font-size:22px"></i></div>
      <h3>Staff / CRM</h3>
      <p>Frontline tours, inquiries, bookings, and pipeline follow-up. Frontend only.</p>
      <div class="btn-row">
        <a class="btn primary blue" href="staff-login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <a class="btn ghost" href="staff-register.php"><i class="fas fa-user-plus"></i> Register</a>
      </div>
    </div>

    <div class="tile client">
      <div class="icon client"><i class="fas fa-home" style="font-size:22px"></i></div>
      <h3>Client / Public</h3>
      <p>Buyers, tenants, investors. Property discovery, favourites, bookings, invoices.</p>
      <div class="btn-row">
        <a class="btn primary client" href="../login.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <a class="btn ghost" href="../register.php"><i class="fas fa-user-plus"></i> Register</a>
      </div>
    </div>
  </div>

  <div class="sep"></div>
  <p class="tag" style="text-align:center">&copy; <?= date('Y') ?> Panda Realty — Perpetuah Realtor. All rights reserved.</p>
</div>
</body>
</html>
