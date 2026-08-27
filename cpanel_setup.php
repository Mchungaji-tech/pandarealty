<?php
/**
 * Panda Realty - cPanel Production Environment Diagnostics & Deployment Health Check
 * Target Server Path: /home/tektxbzg/public_html/pandareality
 * Designed & Developed by TekTrend
 */

header('Content-Type: text/html; charset=UTF-8');

$env_file = __DIR__ . '/.env';
$env_cpanel = __DIR__ . '/.env.cpanel';
$is_cpanel_env = false;

// If .env doesn't exist but .env.cpanel exists, allow 1-click activation
$activated_msg = '';
if (isset($_GET['activate_cpanel_env']) && $_GET['activate_cpanel_env'] === '1') {
    if (file_exists($env_cpanel)) {
        if (@copy($env_cpanel, $env_file)) {
            $activated_msg = "Successfully activated .env.cpanel as active .env configuration!";
        } else {
            $activated_msg = "Could not copy .env.cpanel automatically. Please rename .env.cpanel to .env in cPanel File Manager.";
        }
    }
}

// Load .env or fallback to .env.cpanel
$env_vars = [];
$target_env = file_exists($env_file) ? $env_file : (file_exists($env_cpanel) ? $env_cpanel : null);
if ($target_env) {
    $lines = file($target_env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            $env_vars[trim($k)] = trim(trim($v), "\"'");
        }
    }
}

$db_host = $env_vars['DB_HOST'] ?? 'localhost';
$db_name = $env_vars['DB_NAME'] ?? 'tektxbzg_pandareality';
$db_user = $env_vars['DB_USER'] ?? 'tektxbzg_realty';
$db_pass = $env_vars['DB_PASS'] ?? 'Gkf^u(9^Hv6x9~8#';


// Test DB Connection
$db_connected = false;
$db_error = '';
$table_count = 0;
$tables = [];

try {
    $mysqli = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$mysqli && ($db_host === 'localhost' || $db_host === '127.0.0.1')) {
        $alt_host = ($db_host === 'localhost') ? '127.0.0.1' : 'localhost';
        $mysqli = @mysqli_connect($alt_host, $db_user, $db_pass, $db_name);
        if ($mysqli) {
            $db_host = $alt_host;
        }
    }

    if ($mysqli) {
        $db_connected = true;
        $res = mysqli_query($mysqli, "SHOW TABLES");
        if ($res) {
            while ($row = mysqli_fetch_row($res)) {
                $tables[] = $row[0];
            }
            $table_count = count($tables);
        }
    } else {
        $db_error = mysqli_connect_error();
    }
} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

// Check Upload Directories
$upload_dirs = [
    'uploads',
    'uploads/properties',
    'uploads/branding',
    'uploads/realtor',
    'uploads/avatars'
];
$dir_status = [];
foreach ($upload_dirs as $d) {
    $full_path = __DIR__ . '/' . $d;
    $exists = is_dir($full_path);
    if (!$exists) {
        @mkdir($full_path, 0755, true);
        $exists = is_dir($full_path);
    }
    $writable = $exists && is_writable($full_path);
    $dir_status[$d] = [
        'path' => $full_path,
        'exists' => $exists,
        'writable' => $writable
    ];
}

// PHP Extensions
$extensions = ['mysqli', 'mbstring', 'openssl', 'json', 'gd', 'fileinfo'];
$ext_status = [];
foreach ($extensions as $ext) {
    $ext_status[$ext] = extension_loaded($ext);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panda Realty | cPanel Server Readiness & Health Check</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0f19;
            --card: #151c2e;
            --accent: #C5A059;
            --text: #f8fafc;
            --muted: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 40px 20px; }
        .container { max-width: 850px; margin: 0 auto; }
        .card { background: var(--card); border-radius: 12px; padding: 25px 30px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        h1 { font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 6px; color: #fff; }
        h1 span { color: var(--accent); }
        h2 { font-size: 18px; margin-bottom: 15px; color: var(--accent); display: flex; align-items: center; gap: 8px; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-badge.ok { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid var(--success); }
        .status-badge.fail { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid var(--danger); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
        .info-label { color: var(--muted); }
        .info-val { font-weight: 600; font-family: monospace; }
        .btn { display: inline-block; background: var(--accent); color: #000; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 13px; cursor: pointer; border: none; }
        .alert { background: rgba(197, 160, 89, 0.15); border: 1px solid var(--accent); color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1>PANDA <span>REALTY</span></h1>
        <p style="color: var(--muted); font-size: 13px;">cPanel Production Deployment & Server Diagnostics</p>
    </div>

    <?php if (!empty($activated_msg)): ?>
        <div class="alert"><?= htmlspecialchars($activated_msg) ?></div>
    <?php endif; ?>

    <!-- 1. Server Environment Details -->
    <div class="card">
        <h2>🌐 1. Server Environment & Path Verification</h2>
        <div class="info-row">
            <span class="info-label">Expected cPanel Root</span>
            <span class="info-val">/home/tektxbzg/public_html/pandareality</span>
        </div>
        <div class="info-row">
            <span class="info-label">Current Server Path (__DIR__)</span>
            <span class="info-val"><?= htmlspecialchars(__DIR__) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">PHP Version</span>
            <span class="info-val"><?= PHP_VERSION ?> <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? '<span class="status-badge ok">COMPATIBLE</span>' : '<span class="status-badge fail">PHP >= 7.4 REQUIRED</span>' ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Server Software</span>
            <span class="info-val"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Apache') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">HTTPS SSL Active</span>
            <span class="info-val"><?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '<span class="status-badge ok">SECURE (HTTPS)</span>' : '<span class="status-badge fail">HTTP ONLY</span>' ?></span>
        </div>
    </div>

    <!-- 2. Database Connection -->
    <div class="card">
        <h2>🗄️ 2. MySQL Database Health</h2>
        <div class="info-row">
            <span class="info-label">Database Host / Name</span>
            <span class="info-val"><?= htmlspecialchars($db_host) ?> / <?= htmlspecialchars($db_name) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Database User</span>
            <span class="info-val"><?= htmlspecialchars($db_user) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Connection Status</span>
            <span class="info-val">
                <?php if ($db_connected): ?>
                    <span class="status-badge ok">CONNECTED (<?= $table_count ?> Tables Found)</span>
                <?php else: ?>
                    <span class="status-badge fail">DISCONNECTED: <?= htmlspecialchars($db_error) ?></span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!$db_connected): ?>
            <div style="margin-top: 20px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 16px; font-size: 13px; line-height: 1.6;">
                <strong style="color: #f87171;">💡 How to Fix 'Access denied' in cPanel in 3 Quick Steps:</strong>
                <ol style="margin-top: 10px; margin-left: 20px; color: #cbd5e1;">
                    <li style="margin-bottom: 6px;">Open <strong>cPanel > MySQL Databases</strong>.</li>
                    <li style="margin-bottom: 6px;">Scroll down to <strong>"Add User To Database"</strong> section:
                        <br>• User: Select <code>tektxbzg_realty</code>
                        <br>• Database: Select <code>tektxbzg_pandareality</code>
                        <br>• Click <strong>Add</strong> $\rightarrow$ Check <strong>ALL PRIVILEGES</strong> $\rightarrow$ Click <strong>Make Changes</strong>.
                    </li>
                    <li>Under <strong>Current Users</strong>, if the password differs, click <strong>Change Password</strong> next to <code>tektxbzg_realty</code> and set it to match <code>.env</code>.</li>
                </ol>
            </div>
        <?php elseif ($db_connected && $table_count > 0): ?>
            <div style="margin-top: 15px; font-size: 12px; color: var(--muted);">
                Installed Tables: <?= implode(', ', array_slice($tables, 0, 8)) ?><?= count($tables) > 8 ? '...' : '' ?>
            </div>
        <?php elseif ($db_connected && $table_count === 0): ?>
            <div style="margin-top: 15px; color: var(--warning); font-size: 13px;">
                ⚠️ Connected to database, but 0 tables found. Please import <code>database.sql</code> via cPanel phpMyAdmin.
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Upload Directories Permissions -->
    <div class="card">
        <h2>📁 3. File Uploads Storage Permissions</h2>
        <?php foreach ($dir_status as $dir_name => $stat): ?>
            <div class="info-row">
                <span class="info-label"><?= htmlspecialchars($dir_name) ?>/</span>
                <span class="info-val">
                    <?php if ($stat['exists'] && $stat['writable']): ?>
                        <span class="status-badge ok">WRITABLE (0755)</span>
                    <?php else: ?>
                        <span class="status-badge fail">PERMISSIONS NEEDED (chmod 755)</span>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 4. Quick Actions -->
    <div class="card" style="text-align: center;">
        <h2>🚀 4. Launch Application</h2>
        <p style="color: var(--muted); font-size: 13px; margin-bottom: 20px;">
            Once all badges above are green, Panda Realty is fully functional and ready for live production traffic!
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="index.php" class="btn">View Website</a>
            <a href="admin/index.php" class="btn" style="background: #334155; color: #fff;">Admin Workspace</a>
            <a href="admin/login.php" class="btn" style="background: #334155; color: #fff;">Staff Sign In</a>
        </div>
    </div>
</div>
</body>
</html>
