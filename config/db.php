<?php
/**
 * Panda Realty - Database Connection Handler (Procedural PHP)
 * Designed & Developed by TekTrend
 */

// Disable fatal mysqli exception mode (PHP 8.1+) to prevent 500 Internal Server Errors
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

if (!function_exists('panda_load_env')) {
    function panda_load_env() {
        static $loaded = false;
        static $env_data = [];

        if ($loaded) {
            return $env_data;
        }

        $loaded = true;
        $env_file = dirname(__DIR__) . '/.env';

        if (!is_file($env_file) || !is_readable($env_file)) {
            return $env_data;
        }

        $content = @file_get_contents($env_file);
        if ($content === false) {
            return $env_data;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            // Strip enclosing quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            $env_data[$name] = $value;
            $GLOBALS['PANDA_ENV'][$name] = $value;
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;

            if (function_exists('putenv')) {
                @putenv($name . '=' . $value);
            }
        }

        return $env_data;
    }
}

if (!function_exists('panda_env')) {
    function panda_env($key, $default = null) {
        $data = panda_load_env();

        if (isset($data[$key]) && $data[$key] !== '') {
            return $data[$key];
        }

        if (isset($GLOBALS['PANDA_ENV'][$key]) && $GLOBALS['PANDA_ENV'][$key] !== '') {
            return $GLOBALS['PANDA_ENV'][$key];
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        $val = function_exists('getenv') ? @getenv($key) : false;
        if ($val !== false && $val !== '') {
            return $val;
        }

        return $default;
    }
}

if (!function_exists('panda_is_local_request')) {
    function panda_is_local_request() {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host);
        $app_env = strtolower((string)panda_env('APP_ENV', 'local'));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || $app_env === 'local';
    }
}

if (!function_exists('panda_foreign_key_exists')) {
    function panda_foreign_key_exists($conn, $table_name, $column_name, $referenced_table = '') {
        if (!$conn) {
            return false;
        }

        $table_name = mysqli_real_escape_string($conn, (string)$table_name);
        $column_name = mysqli_real_escape_string($conn, (string)$column_name);
        $referenced_table = mysqli_real_escape_string($conn, (string)$referenced_table);

        $sql = "SELECT 1
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$table_name}'
                  AND COLUMN_NAME = '{$column_name}'
                  AND REFERENCED_TABLE_NAME IS NOT NULL";

        if ($referenced_table !== '') {
            $sql .= " AND REFERENCED_TABLE_NAME = '{$referenced_table}'";
        }

        $sql .= " LIMIT 1";
        $res = @mysqli_query($conn, $sql);
        return $res && mysqli_num_rows($res) > 0;
    }
}

$db_host = panda_env('DB_HOST', 'localhost');
$db_user = panda_env('DB_USER', 'tektxbzg_pandarealty');
$db_pass = panda_env('DB_PASS', 'PandaRealty#2026!SecureDb');
$db_name = panda_env('DB_NAME', 'tektxbzg_pandarealty');

// Procedural MySQLi connection
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn && ($db_host === 'localhost' || $db_host === '127.0.0.1')) {
    $alt_host = ($db_host === 'localhost') ? '127.0.0.1' : 'localhost';
    $conn = @mysqli_connect($alt_host, $db_user, $db_pass, $db_name);
    if ($conn) {
        $db_host = $alt_host;
    }
}

if (!$conn && panda_is_local_request() && panda_env('ALLOW_DB_BOOTSTRAP', '1') === '1') {
    $conn_init = @mysqli_connect($db_host, $db_user, $db_pass);
    if ($conn_init) {
        $safe_db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $db_name);
        @mysqli_query($conn_init, "CREATE DATABASE IF NOT EXISTS `{$safe_db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_close($conn_init);
        $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    }
}

if (!$conn) {
    $conn_err = mysqli_connect_error();
    $base = function_exists('panda_env') ? (string)panda_env('APP_BASE_PATH', '/') : '/';
    $setup_url = rtrim($base, '/') . '/cpanel_setup.php';
    
    // In CLI mode, output text
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Database Connection Error: " . $conn_err . PHP_EOL);
        fwrite(STDERR, "Target DB: {$db_name} | User: {$db_user} | Host: {$db_host}" . PHP_EOL);
        exit(1);
    }

    // Graceful production diagnostic response
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Notice | Panda Realty</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root { --bg: #0b0f19; --card: #151c2e; --accent: #C5A059; --text: #f8fafc; --muted: #94a3b8; --danger: #ef4444; }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 40px 20px; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .card { background: var(--card); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; max-width: 600px; width: 100%; padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
            h1 { font-size: 22px; color: var(--accent); margin-bottom: 12px; }
            p { color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
            .box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-family: monospace; font-size: 13px; color: #fca5a5; }
            .details { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; }
            .details div { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
            .details div:last-child { border-bottom: none; }
            .btn { display: inline-block; background: var(--accent); color: #000; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Database Configuration Notice</h1>
            <p>Panda Realty could not establish a connection with the MySQL database using the settings defined in <code>.env</code>.</p>
            <div class="box">Error: <?= htmlspecialchars($conn_err) ?></div>
            <div class="details">
                <div><span>Database Name:</span> <strong><?= htmlspecialchars($db_name) ?></strong></div>
                <div><span>Database User:</span> <strong><?= htmlspecialchars($db_user) ?></strong></div>
                <div><span>Database Host:</span> <strong><?= htmlspecialchars($db_host) ?></strong></div>
            </div>
            <p style="font-size: 13px;">Make sure you have created this database and user in <strong>cPanel &gt; MySQL Databases</strong> and assigned <strong>ALL PRIVILEGES</strong>.</p>
            <a href="cpanel_setup.php" class="btn">Open cPanel Diagnostic Health Check &rarr;</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

mysqli_set_charset($conn, "utf8mb4");

if (!function_exists('panda_sync_phase2_schema')) {
    function panda_sync_phase2_schema($conn) {
        static $synced = false;

        if ($synced || !$conn) {
            return;
        }

        $synced = true;

        // Check if users table exists before checking columns
        $table_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if (!$table_check || mysqli_num_rows($table_check) === 0) {
            return;
        }

        $res = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
        $column = $res ? mysqli_fetch_assoc($res) : null;
        $role_type = strtolower((string)($column['Type'] ?? ''));

        if ($role_type !== '' && strpos($role_type, 'enum') !== false) {
            @mysqli_query($conn, "ALTER TABLE users MODIFY role VARCHAR(50) DEFAULT 'client'");
        }

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS sales_pipeline (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(100) NOT NULL,
            client_email VARCHAR(150) NULL,
            client_phone VARCHAR(30) NULL,
            property_id INT NULL,
            assigned_to INT NULL,
            inquiry_id INT NULL,
            booking_id INT NULL,
            source ENUM('website','whatsapp','referral','walk_in','inquiry','booking','manual') DEFAULT 'manual',
            stage ENUM('new_lead','qualified','consultation','site_visit','negotiation','won','lost') DEFAULT 'new_lead',
            estimated_value DECIMAL(15,2) DEFAULT 0.00,
            probability_percent INT DEFAULT 10,
            expected_close_date DATE NULL,
            last_contact_date DATE NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE SET NULL,
            FOREIGN KEY (booking_id) REFERENCES site_visits(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $prop_table = @mysqli_query($conn, "SHOW TABLES LIKE 'properties'");
        if ($prop_table && mysqli_num_rows($prop_table) > 0) {
            $video_column = @mysqli_query($conn, "SHOW COLUMNS FROM properties LIKE 'video_urls'");
            if (!$video_column || mysqli_num_rows($video_column) === 0) {
                @mysqli_query($conn, "ALTER TABLE properties ADD COLUMN video_urls TEXT NULL AFTER images");
            }
        }

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(100) NOT NULL,
            client_role VARCHAR(120) NULL,
            client_location VARCHAR(120) NULL,
            quote_text TEXT NOT NULL,
            rating TINYINT DEFAULT 5,
            avatar_url VARCHAR(255) NULL,
            is_featured TINYINT(1) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS media_videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            platform ENUM('youtube','facebook','instagram','tiktok','other') DEFAULT 'youtube',
            embed_url VARCHAR(500) NOT NULL,
            source_url VARCHAR(500) NULL,
            summary TEXT NULL,
            linked_property_id INT NULL,
            is_featured TINYINT(1) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (linked_property_id) REFERENCES properties(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $inq_table = @mysqli_query($conn, "SHOW TABLES LIKE 'inquiries'");
        if ($inq_table && mysqli_num_rows($inq_table) > 0) {
            $inquiry_type_column = @mysqli_query($conn, "SHOW COLUMNS FROM inquiries LIKE 'inquiry_type'");
            $inquiry_type_meta = $inquiry_type_column ? mysqli_fetch_assoc($inquiry_type_column) : null;
            $inquiry_type_sql = strtolower((string)($inquiry_type_meta['Type'] ?? ''));
            if ($inquiry_type_sql !== '' && strpos($inquiry_type_sql, "'hire_agent'") === false) {
                @mysqli_query($conn, "ALTER TABLE inquiries MODIFY inquiry_type ENUM('contact_form','property_inquiry','hire_agent','installment_quote') DEFAULT 'contact_form'");
            }

            $inquiry_columns = [
                "preferred_contact" => "ALTER TABLE inquiries ADD COLUMN preferred_contact ENUM('phone','whatsapp','email') DEFAULT 'whatsapp' AFTER inquiry_type",
                "client_stage" => "ALTER TABLE inquiries ADD COLUMN client_stage ENUM('new','qualified','consultation','site_visit','negotiation','won','lost') DEFAULT 'new' AFTER status",
                "budget_range" => "ALTER TABLE inquiries ADD COLUMN budget_range VARCHAR(120) NULL AFTER subject",
                "follow_up_date" => "ALTER TABLE inquiries ADD COLUMN follow_up_date DATE NULL AFTER client_stage",
                "assigned_to" => "ALTER TABLE inquiries ADD COLUMN assigned_to INT NULL AFTER follow_up_date",
                "whatsapp_opt_in" => "ALTER TABLE inquiries ADD COLUMN whatsapp_opt_in TINYINT(1) DEFAULT 1 AFTER preferred_contact"
            ];
            foreach ($inquiry_columns as $column_name => $statement) {
                $column_check = @mysqli_query($conn, "SHOW COLUMNS FROM inquiries LIKE '{$column_name}'");
                if (!$column_check || mysqli_num_rows($column_check) === 0) {
                    @mysqli_query($conn, $statement);
                }
            }
            if (!panda_foreign_key_exists($conn, 'inquiries', 'assigned_to', 'users')) {
                @mysqli_query($conn, "ALTER TABLE inquiries ADD CONSTRAINT fk_inquiries_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL");
            }
        }

        $visit_table = @mysqli_query($conn, "SHOW TABLES LIKE 'site_visits'");
        if ($visit_table && mysqli_num_rows($visit_table) > 0) {
            $booking_columns = [
                "booking_type" => "ALTER TABLE site_visits ADD COLUMN booking_type ENUM('site_visit','consultation') DEFAULT 'site_visit' AFTER visit_time",
                "consultation_mode" => "ALTER TABLE site_visits ADD COLUMN consultation_mode ENUM('phone','whatsapp','zoom','in_person') DEFAULT 'in_person' AFTER booking_type",
                "preferred_contact" => "ALTER TABLE site_visits ADD COLUMN preferred_contact ENUM('phone','whatsapp','email') DEFAULT 'whatsapp' AFTER consultation_mode",
                "follow_up_date" => "ALTER TABLE site_visits ADD COLUMN follow_up_date DATE NULL AFTER notes",
                "assigned_to" => "ALTER TABLE site_visits ADD COLUMN assigned_to INT NULL AFTER follow_up_date",
                "whatsapp_opt_in" => "ALTER TABLE site_visits ADD COLUMN whatsapp_opt_in TINYINT(1) DEFAULT 1 AFTER preferred_contact"
            ];
            foreach ($booking_columns as $column_name => $statement) {
                $column_check = @mysqli_query($conn, "SHOW COLUMNS FROM site_visits LIKE '{$column_name}'");
                if (!$column_check || mysqli_num_rows($column_check) === 0) {
                    @mysqli_query($conn, $statement);
                }
            }
            if (!panda_foreign_key_exists($conn, 'site_visits', 'assigned_to', 'users')) {
                @mysqli_query($conn, "ALTER TABLE site_visits ADD CONSTRAINT fk_site_visits_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL");
            }
        }

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS property_tips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            icon_class VARCHAR(80) DEFAULT 'fas fa-lightbulb',
            cta_label VARCHAR(80) NULL,
            cta_url VARCHAR(255) NULL,
            linked_property_id INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            display_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (linked_property_id) REFERENCES properties(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS super_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(30) NULL,
            password_hash VARCHAR(255) NOT NULL,
            two_factor_secret VARCHAR(64) NULL,
            two_factor_enabled TINYINT(1) DEFAULT 0,
            is_online TINYINT(1) DEFAULT 0,
            last_active DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ceo_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(30) NULL,
            password_hash VARCHAR(255) NOT NULL,
            two_factor_secret VARCHAR(64) NULL,
            two_factor_enabled TINYINT(1) DEFAULT 0,
            is_online TINYINT(1) DEFAULT 0,
            last_active DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS staff_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(30) NULL,
            department VARCHAR(80) NULL,
            position_title VARCHAR(120) NULL,
            password_hash VARCHAR(255) NOT NULL,
            two_factor_secret VARCHAR(64) NULL,
            two_factor_enabled TINYINT(1) DEFAULT 0,
            is_online TINYINT(1) DEFAULT 0,
            last_active DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

panda_sync_phase2_schema($conn);

/**
 * Return the active database connection
 */
function get_db_connection() {
    global $conn;
    return $conn;
}
