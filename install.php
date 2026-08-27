<?php
/**
 * Panda Realty - Automated Database Installer & Health Check
 * Designed & Developed by TekTrend
 */

header('Content-Type: text/html; charset=UTF-8');

function install_load_env_value($key, $default = null) {
    static $loaded = false;

    if (!$loaded) {
        $loaded = true;
        $env_file = __DIR__ . '/.env';

        if (is_file($env_file) && is_readable($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                        continue;
                    }

                    list($name, $value) = array_map('trim', explode('=', $line, 2));
                    if ($name === '') {
                        continue;
                    }

                    $value = trim($value, "\"'");
                    if (getenv($name) === false) {
                        putenv($name . '=' . $value);
                    }
                }
            }
        }
    }

    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

function install_is_local_request() {
    if (PHP_SAPI === 'cli') {
        return true;
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $app_env = strtolower((string)install_load_env_value('APP_ENV', 'local'));

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || $app_env === 'local';
}

function generate_install_password($length = 20) {
    $raw = rtrim(strtr(base64_encode(random_bytes($length)), '+/', 'AZ'), '=');
    return substr($raw . '9!', 0, max(14, $length));
}

$installer_allowed = install_is_local_request() || install_load_env_value('ALLOW_INSTALLER', '0') === '1';

if (!$installer_allowed) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Installer Disabled</title></head><body style="font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:24px;"><div style="max-width:520px; background:#111827; border:1px solid #334155; border-radius:12px; padding:32px;"><h1 style="margin-top:0; font-size:24px;">Installer Disabled</h1><p style="line-height:1.6;">The installer is only available on localhost, CLI, or when <code>ALLOW_INSTALLER=1</code> is explicitly configured.</p></div></body></html>';
    exit;
}

$host = install_load_env_value('DB_HOST', 'localhost');
$user = install_load_env_value('DB_USER', 'root');
$pass = install_load_env_value('DB_PASS', '');
$dbname = install_load_env_value('DB_NAME', 'pandareality_db');
$sqlFile = __DIR__ . '/database.sql';

$statusMessages = [];
$isSuccess = false;
$generatedCredentials = [];

try {
    // 1. Connect to MySQL Server
    $mysqli = new mysqli($host, $user, $pass);
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    $statusMessages[] = ["type" => "success", "text" => "Successfully connected to MySQL server (host: $host)."];

    // 2. Read SQL File
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found at: " . $sqlFile);
    }
    $sqlContent = file_get_contents($sqlFile);
    $statusMessages[] = ["type" => "success", "text" => "Found database.sql definition file."];

    // 3. Execute Multi Query
    if ($mysqli->multi_query($sqlContent)) {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        $statusMessages[] = ["type" => "success", "text" => "Database `pandareality_db` and all tables created and seeded successfully."];
    } else {
        throw new Exception("Error executing SQL script: " . $mysqli->error);
    }

    // 4. Generate one-time credentials for seeded administrative accounts
    $mysqli->select_db($dbname);
    
    $generatedCredentials = [
        'superadmin@pandarealty.co.ke' => generate_install_password(),
        'perpetuah@pandarealty.co.ke' => generate_install_password(),
        'admin@tektrend.co.ke' => generate_install_password()
    ];

    $superadmin1_pass = password_hash($generatedCredentials['superadmin@pandarealty.co.ke'], PASSWORD_BCRYPT);
    $superadmin2_pass = password_hash($generatedCredentials['perpetuah@pandarealty.co.ke'], PASSWORD_BCRYPT);
    $admin_pass = password_hash($generatedCredentials['admin@tektrend.co.ke'], PASSWORD_BCRYPT);

    $stmt1 = $mysqli->prepare("UPDATE users SET password = ? WHERE id = 1");
    $stmt1->bind_param("s", $superadmin1_pass);
    $stmt1->execute();

    $stmt2 = $mysqli->prepare("UPDATE users SET password = ? WHERE id = 2");
    $stmt2->bind_param("s", $superadmin2_pass);
    $stmt2->execute();

    $stmt3 = $mysqli->prepare("UPDATE users SET password = ? WHERE id = 3");
    $stmt3->bind_param("s", $admin_pass);
    $stmt3->execute();

    $statusMessages[] = ["type" => "success", "text" => "Seeded admin accounts were rotated to one-time passwords for this installation."];

    // 5. Ensure Uploads and Cache Directories Exist
    $dirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/properties',
        __DIR__ . '/uploads/avatars',
        __DIR__ . '/assets/images'
    ];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            mkdir($d, 0755, true);
        }
    }
    $statusMessages[] = ["type" => "success", "text" => "Verified filesystem media & upload directories."];

    $isSuccess = true;
} catch (Exception $e) {
    $statusMessages[] = ["type" => "error", "text" => $e->getMessage()];
}

// If run from CLI, print text output
if (php_sapi_name() === 'cli') {
    echo "========================================\n";
    echo "  PANDA REALTY - DATABASE INSTALLER\n";
    echo "========================================\n\n";
    foreach ($statusMessages as $msg) {
        echo "[" . strtoupper($msg['type']) . "] " . $msg['text'] . "\n";
    }
    if ($isSuccess && !empty($generatedCredentials)) {
        echo "\nOne-time administrative credentials:\n";
        foreach ($generatedCredentials as $email => $plainPassword) {
            echo " - {$email} => {$plainPassword}\n";
        }
        echo "\nStore these securely and change them after first login.\n";
    }
    echo "\n" . ($isSuccess ? "INSTALLATION COMPLETED SUCCESSFULLY!\n" : "INSTALLATION FAILED!\n");
    exit($isSuccess ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panda Realty | System Setup & Installer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #c5a059;
            --bg: #090d16;
            --card: #131b2e;
            --text: #e2e8f0;
            --border: #22304e;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-width: 650px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .logo-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #fff;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .accent { color: var(--accent); }
        .subtitle { color: #94a3b8; font-size: 14px; margin-bottom: 30px; }
        .log-box {
            background: #090d16;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-family: monospace;
            font-size: 13px;
            max-height: 250px;
            overflow-y: auto;
        }
        .log-item {
            padding: 6px 0;
            display: flex;
            gap: 10px;
        }
        .log-item.success { color: #4ade80; }
        .log-item.error { color: #f87171; }
        .btn-group { display: flex; gap: 15px; }
        .btn {
            flex: 1;
            display: inline-block;
            text-align: center;
            padding: 14px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: var(--accent); color: #000; }
        .btn-primary:hover { background: #dfb96f; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: #fff; }
        .btn-outline:hover { background: var(--border); }
        .credentials-box {
            background: rgba(197, 160, 89, 0.08);
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 13px;
        }
        .credentials-box h4 { color: var(--accent); margin-bottom: 8px; }
        .credentials-box ul { padding-left: 20px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-title">PANDA <span class="accent">REALTY</span></div>
        <p class="subtitle">System Database Setup & Environment Initialization</p>

        <div class="log-box">
            <?php foreach ($statusMessages as $msg): ?>
                <div class="log-item <?= htmlspecialchars($msg['type']) ?>">
                    <span>[<?= strtoupper(htmlspecialchars($msg['type'])) ?>]</span>
                    <span><?= htmlspecialchars($msg['text']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($isSuccess): ?>
            <div class="credentials-box">
                <h4>One-Time Administrative Credentials</h4>
                <p style="margin-bottom: 10px;">Store these securely. They are shown only for this installer run and should be changed immediately after first login.</p>
                <ul>
                    <li><strong>Root Super Admin:</strong> <code>superadmin@pandarealty.co.ke</code> | <code><?= htmlspecialchars($generatedCredentials['superadmin@pandarealty.co.ke'] ?? 'generated during CLI install') ?></code></li>
                    <li><strong>CEO (Perpetuah):</strong> <code>perpetuah@pandarealty.co.ke</code> | <code><?= htmlspecialchars($generatedCredentials['perpetuah@pandareality.co.ke'] ?? 'generated during CLI install') ?></code></li>
                    <li><strong>Developer:</strong> <code>admin@tektrend.co.ke</code> | <code><?= htmlspecialchars($generatedCredentials['admin@tektrend.co.ke'] ?? 'generated during CLI install') ?></code></li>
                </ul>
            </div>
            <div class="btn-group">
                <a href="<?= htmlspecialchars(app_path('index')) ?>" class="btn btn-primary">Go to Front End Website</a>
                <a href="<?= htmlspecialchars(app_path('admin/admin-login')) ?>" class="btn btn-outline">Go to Admin Portal</a>
                <a href="<?= htmlspecialchars(app_path('admin/super-login')) ?>" class="btn btn-outline" style="border-color: var(--accent); color: var(--accent);">Super Admin Gateway</a>
            </div>
        <?php else: ?>
            <div class="btn-group">
                <a href="<?= htmlspecialchars(app_path('install')) ?>" class="btn btn-primary">Retry Installation</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
