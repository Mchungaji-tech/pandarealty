<?php
/**
 * Panda Realty - Core Procedural Helper Functions
 * Designed & Developed by TekTrend
 */

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/totp.php';

function is_local_environment() {
    return panda_is_local_request();
}

function is_https_request() {
    if (PHP_SAPI === 'cli') {
        return false;
    }

    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function get_app_base_path() {
    static $base_path = null;

    if ($base_path !== null) {
        return $base_path;
    }

    $configured = trim((string)panda_env('APP_BASE_PATH', '/pandareality'));

    if ($configured === '' || $configured === '/') {
        $base_path = '';
        return $base_path;
    }

    if ($configured[0] !== '/') {
        $configured = '/' . $configured;
    }

    $base_path = rtrim($configured, '/');
    return $base_path;
}

function app_path($path = '') {
    $path = ltrim((string)$path, '/');
    $base_path = get_app_base_path();

    if ($path === '') {
        return $base_path === '' ? '/' : $base_path . '/';
    }

    return $base_path === '' ? '/' . $path : $base_path . '/' . $path;
}

function normalize_media_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#^(https?://|data:|//)#i', $url)) {
        return $url;
    }
    $clean = ltrim($url, '/\\');
    return app_path($clean);
}

function build_redirect_url($path, $params = []) {
    $url = app_path($path);
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }
    return $url;
}

function perform_redirect($url) {
    header('Location: ' . $url);
    exit;
}

function redirect_to($path, $params = []) {
    perform_redirect(build_redirect_url($path, $params));
}

function get_request_path() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = is_string($path) && $path !== '' ? $path : '/';

    $base_path = get_app_base_path();
    if ($base_path !== '' && strpos($path, $base_path) === 0) {
        $path = substr($path, strlen($base_path));
    }

    return '/' . ltrim($path, '/');
}

function sanitize_redirect_target($target, $default = 'index') {
    $target = trim(str_replace(["\r", "\n", "\0"], '', (string)$target));

    if ($target === '') {
        return $default;
    }

    $parts = parse_url($target);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return $default;
    }

    $path = $parts['path'] ?? '';
    if ($path === '' || $path === '/') {
        return $default;
    }

    if (strpos($path, '..') !== false) {
        return $default;
    }

    $base_path = get_app_base_path();
    if ($base_path !== '' && strpos($path, $base_path) === 0) {
        $path = substr($path, strlen($base_path));
    }

    $path = ltrim($path, '/');
    if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
        return $default;
    }

    if (substr($path, -4) === '.php') {
        $path = substr($path, 0, -4);
    }

    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    return $path . $query;
}

function setup_secure_session() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $cookie_params = [
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookie_params);
    } else {
        session_set_cookie_params(
            $cookie_params['lifetime'],
            $cookie_params['path'] . '; samesite=' . $cookie_params['samesite'],
            '',
            $cookie_params['secure'],
            $cookie_params['httponly']
        );
    }

    session_start();
}

setup_secure_session();

function destroy_active_session($notice = '') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    session_start();

    if ($notice !== '') {
        $_SESSION['auth_notice'] = $notice;
    }
}

function consume_auth_notice() {
    $notice = $_SESSION['auth_notice'] ?? '';
    unset($_SESSION['auth_notice']);
    return $notice;
}

/**
 * Clear ONLY a specific role's session namespace (and update its `is_online` flag).
 * Does NOT call session_destroy() — other concurrent role logins survive.
 * Use this on standalone dashboards sign-out, not session_destroy().
 */
function panda_clear_role_session($role, $redirect_to = null) {
    $conn = function_exists('get_db_connection') ? get_db_connection() : null;
    $updates = [
        'superadmin' => ['table'=>'super_admins', 'sid'=>'superadmin_id',
                         'keys'=>['superadmin_id','superadmin_name','superadmin_email','superadmin_login_at','superadmin_csrf']],
        'ceo'        => ['table'=>'ceo_users',    'sid'=>'ceo_id',
                         'keys'=>['ceo_id','ceo_name','ceo_email','ceo_login_at','ceo_csrf']],
        'staff'      => ['table'=>'staff_members','sid'=>'staff_id',
                         'keys'=>['staff_id','staff_name','staff_email','staff_dept','staff_role','staff_login_at','staff_csrf']],
        'user'       => ['table'=>'users',        'sid'=>'user_id',
                         'keys'=>['user_id','user_name','user_email','user_role','user_login_at','user_csrf']],
    ];
    $cfg = $updates[$role] ?? null;
    if ($cfg && $conn) {
        $id = (int)($_SESSION[$cfg['sid']] ?? 0);
        if ($id > 0) {
            $t = mysqli_real_escape_string($conn, $cfg['table']);
            @mysqli_query($conn, "UPDATE `$t` SET is_online = 0 WHERE id = $id");
        }
    }
    if ($cfg) {
        foreach ($cfg['keys'] as $k) unset($_SESSION[$k]);
    }
    if ($redirect_to !== null) {
        header('Location: ' . $redirect_to);
        exit;
    }
}

function get_session_timeout_seconds() {
    return (int) panda_env('SESSION_TIMEOUT_SECONDS', 7200);
}

function get_login_attempt_limit() {
    return (int) panda_env('LOGIN_MAX_ATTEMPTS', 5);
}

function get_login_lockout_seconds() {
    return (int) panda_env('LOGIN_LOCKOUT_SECONDS', 180);
}

function get_role_labels() {
    return [
        'superadmin' => 'Super Admin',
        'developer' => 'Developer',
        'ceo' => 'CEO',
        'admin' => 'Admin',
        'staff' => 'Staff',
        'client' => 'Client',
        'guest' => 'Guest'
    ];
}

function get_role_label($role) {
    $labels = get_role_labels();
    return $labels[$role] ?? ucfirst((string)$role);
}

function get_internal_roles() {
    return ['superadmin', 'developer', 'ceo', 'admin', 'staff'];
}

function get_internal_auth_roles() {
    return "'" . implode("','", get_internal_roles()) . "'";
}

function is_internal_role($role) {
    return in_array((string)$role, get_internal_roles(), true);
}

function get_role_badge_class($role) {
    $map = [
        'superadmin' => 'badge-superadmin',
        'developer' => 'badge-developer',
        'ceo' => 'badge-ceo',
        'admin' => 'badge-admin',
        'staff' => 'badge-staff',
        'client' => 'badge-client'
    ];

    return $map[$role] ?? 'badge-client';
}

function get_role_capabilities() {
    return [
        'superadmin' => [
            'access_admin',
            'manage_sales_pipeline',
            'manage_properties',
            'manage_bookings',
            'manage_inquiries',
            'manage_invoices',
            'manage_tasks',
            'manage_cms',
            'manage_users',
            'view_security_logs',
            'view_online_users',
            'manage_maintenance',
            'manage_system_settings',
            'view_ceo_dashboard',
            'view_reports'
        ],
        'developer' => [
            'access_admin',
            'manage_sales_pipeline',
            'manage_properties',
            'manage_bookings',
            'manage_inquiries',
            'manage_invoices',
            'manage_tasks',
            'manage_cms',
            'view_security_logs',
            'view_online_users',
            'manage_maintenance',
            'manage_system_settings'
        ],
        'ceo' => [
            'access_admin',
            'manage_sales_pipeline',
            'manage_properties',
            'manage_bookings',
            'manage_inquiries',
            'manage_invoices',
            'manage_tasks',
            'manage_cms',
            'manage_users',
            'view_ceo_dashboard',
            'view_reports'
        ],
        'admin' => [
            'access_admin',
            'manage_sales_pipeline',
            'manage_properties',
            'manage_bookings',
            'manage_inquiries',
            'manage_invoices',
            'manage_tasks',
            'manage_cms'
        ],
        'staff' => [
            'access_admin',
            'manage_sales_pipeline',
            'manage_properties',
            'manage_bookings',
            'manage_inquiries',
            'manage_tasks'
        ],
        'client' => []
    ];
}

function user_can($capability) {
    if (!is_logged_in()) {
        return false;
    }

    $role = get_current_user_role();
    $capability_map = get_role_capabilities();
    $allowed = $capability_map[$role] ?? [];

    return in_array($capability, $allowed, true);
}

function require_capability($capability) {
    if (!user_can($capability)) {
        redirect_to('admin/login', ['error' => 'unauthorized']);
    }
}

function get_manageable_roles() {
    $role = get_current_user_role();

    if ($role === 'superadmin') {
        return ['staff', 'admin', 'ceo', 'developer', 'client', 'superadmin'];
    }

    if ($role === 'ceo') {
        return ['staff', 'admin', 'client'];
    }

    return [];
}

function get_dashboard_title_for_role($role) {
    $titles = [
        'ceo' => 'CEO Dashboard',
        'developer' => 'Developer Operations Dashboard',
        'admin' => 'Admin CRM Dashboard',
        'staff' => 'Staff CRM Workspace',
        'superadmin' => 'Executive Control Dashboard'
    ];

    return $titles[$role] ?? 'CRM Dashboard';
}

function get_dashboard_subtitle_for_role($role) {
    $subtitles = [
        'ceo' => 'Business visibility, pipeline movement, property performance, and team oversight.',
        'developer' => 'Technical operations, maintenance, settings, visibility, and control systems.',
        'admin' => 'Day-to-day operations across listings, bookings, leads, and client follow-up.',
        'staff' => 'Practical CRM tools for handling tours, inquiries, and frontline client work.',
        'superadmin' => 'Full operational and technical control across the entire Panda Realty platform.'
    ];

    return $subtitles[$role] ?? 'Internal CRM overview and day-to-day activity.';
}

function get_sales_pipeline_stages() {
    return [
        'new_lead' => ['label' => 'New Lead', 'class' => 'info'],
        'qualified' => ['label' => 'Qualified', 'class' => 'warning'],
        'consultation' => ['label' => 'Consultation', 'class' => 'info'],
        'site_visit' => ['label' => 'Site Visit', 'class' => 'warning'],
        'negotiation' => ['label' => 'Negotiation', 'class' => 'warning'],
        'won' => ['label' => 'Won', 'class' => 'success'],
        'lost' => ['label' => 'Lost', 'class' => 'danger']
    ];
}

function get_sales_pipeline_sources() {
    return [
        'website' => 'Website',
        'whatsapp' => 'WhatsApp',
        'referral' => 'Referral',
        'walk_in' => 'Walk-In',
        'inquiry' => 'Inquiry',
        'booking' => 'Booking',
        'manual' => 'Manual'
    ];
}

function get_sales_pipeline_stage_label($stage) {
    $stages = get_sales_pipeline_stages();
    return $stages[$stage]['label'] ?? ucfirst(str_replace('_', ' ', (string)$stage));
}

function get_sales_pipeline_stage_class($stage) {
    $stages = get_sales_pipeline_stages();
    return $stages[$stage]['class'] ?? 'info';
}

/**
 * Sanitize User Inputs
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }

    $data = trim((string)$data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape raw string for SQL query
 */
function db_escape($string) {
    $conn = get_db_connection();
    return mysqli_real_escape_string($conn, (string)$string);
}

/**
 * Generate and retrieve CSRF Token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Get dynamic app setting value
 */
function get_setting($key, $default = '') {
    $conn = get_db_connection();
    $key = db_escape($key);
    $res = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_key = '$key' LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row['setting_value'];
    }
    return $default;
}

/**
 * Update app setting
 */
function update_setting($key, $value) {
    $conn = get_db_connection();
    $key = db_escape($key);
    $value = db_escape($value);
    return mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_value = '$value'");
}

/**
 * Get CMS Content Block
 */
function get_cms_block($key, $default = '') {
    $conn = get_db_connection();
    $key = db_escape($key);
    $res = mysqli_query($conn, "SELECT content_value FROM content_blocks WHERE block_key = '$key' LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row['content_value'];
    }
    return $default;
}

/**
 * Format Currency based on active currency preference (KES or USD)
 */
function format_price($price_kes, $preferred_currency = null) {
    if ($preferred_currency === null) {
        $preferred_currency = isset($_COOKIE['panda_currency']) ? $_COOKIE['panda_currency'] : (isset($_SESSION['currency']) ? $_SESSION['currency'] : 'KES');
    }

    $usd_rate = (float) get_setting('currency_usd_rate', 130.00);
    if ($usd_rate <= 0) {
        $usd_rate = 130.00;
    }

    if (strtoupper((string)$preferred_currency) === 'USD') {
        $price_usd = $price_kes / $usd_rate;
        return '$' . number_format($price_usd, 0, '.', ',');
    }

    return 'KSh ' . number_format($price_kes, 0, '.', ',');
}

/**
 * Format raw numbers with thousands separator
 */
function format_number($num) {
    return number_format((float) $num, 0, '.', ',');
}

function get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function get_rate_limit_state($actions, $window_seconds = null, $max_attempts = null) {
    $window_seconds = $window_seconds ?? get_login_lockout_seconds();
    $max_attempts = $max_attempts ?? get_login_attempt_limit();
    $actions = array_values(array_filter(array_map('trim', (array)$actions)));

    if (empty($actions)) {
        return ['locked' => false, 'attempts' => 0, 'remaining_seconds' => 0];
    }

    $conn = get_db_connection();
    $ip = db_escape(get_client_ip());
    $action_sql = "'" . implode("','", array_map('db_escape', $actions)) . "'";
    $window_seconds = max(1, (int)$window_seconds);
    $max_attempts = max(1, (int)$max_attempts);

    $sql = "SELECT COUNT(*) AS attempt_count, MIN(created_at) AS first_attempt
            FROM security_logs
            WHERE ip_address = '$ip'
              AND action IN ($action_sql)
              AND status IN ('failed', 'critical')
              AND created_at >= DATE_SUB(NOW(), INTERVAL $window_seconds SECOND)";

    $res = mysqli_query($conn, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $attempt_count = (int)($row['attempt_count'] ?? 0);
    $remaining_seconds = 0;

    if ($attempt_count >= $max_attempts && !empty($row['first_attempt'])) {
        $first_attempt_ts = strtotime($row['first_attempt']);
        if ($first_attempt_ts !== false) {
            $remaining_seconds = max(0, $window_seconds - (time() - $first_attempt_ts));
        }
    }

    return [
        'locked' => $attempt_count >= $max_attempts && $remaining_seconds > 0,
        'attempts' => $attempt_count,
        'remaining_seconds' => $remaining_seconds
    ];
}

function get_rate_limit_message($remaining_seconds) {
    $minutes = (int) ceil(max(1, $remaining_seconds) / 60);
    return "Too many failed attempts. Try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.';
}

function clear_pending_two_factor_session() {
    unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'], $_SESSION['2fa_redirect']);
}

function start_two_factor_challenge($user, $redirect_target = 'admin') {
    session_regenerate_id(true);
    clear_pending_two_factor_session();

    $_SESSION['2fa_pending_user_id'] = (int)$user['id'];
    $_SESSION['2fa_pending_role'] = $user['role'];
    $_SESSION['2fa_redirect'] = sanitize_redirect_target($redirect_target, 'admin');
    $_SESSION['last_activity'] = time();
}

function finalize_user_login($user) {
    session_regenerate_id(true);
    clear_pending_two_factor_session();

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = (string)$user['role'];
    $_SESSION['last_activity'] = time();

    $conn = get_db_connection();
    @mysqli_query($conn, "UPDATE users SET is_online = 1, last_active = NOW() WHERE id = " . (int)$user['id']);
}

/**
 * Get current logged in user session data
 */
function get_current_user_data() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $conn = get_db_connection();
    $user_id = (int) $_SESSION['user_id'];
    $res = mysqli_query($conn, "SELECT id, name, email, phone, role, avatar, two_factor_enabled, is_online, last_active FROM users WHERE id = $user_id LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row;
    }

    return null;
}

/**
 * Authentication Check Helpers
 *
 * SEPARATE TABLES SESSION BRIDGE:
 *   New standalone logins write to isolated session namespaces:
 *     superadmin_id / ceo_id / staff_id
 *   Legacy admin-header + capabilities checks still read unified names:
 *     user_id + user_role (is_logged_in / is_admin / can_capability)
 *
 *   This helper SYNCs the standalone namespace into the unified runtime namespace
 *   so the admin sidebar, capability checks, KPI widgets etc. light up correctly.
 *   Only runs when namespaces are set — never overwrites client (unified user_id).
 */
function panda_sync_standalone_session_to_unified() {
    if (PHP_SESSION_NONE === session_status()) return;

    if (!empty($_SESSION['superadmin_id'])) {
        $_SESSION['user_id']   = (int)$_SESSION['superadmin_id'];
        $_SESSION['user_role'] = 'superadmin';
        $_SESSION['user_name'] = $_SESSION['superadmin_name']  ?? $_SESSION['user_name']  ?? '';
        $_SESSION['user_email']= $_SESSION['superadmin_email'] ?? $_SESSION['user_email'] ?? '';
        $_SESSION['_synced_from'] = 'super_admins';
    } elseif (!empty($_SESSION['ceo_id'])) {
        $_SESSION['user_id']   = (int)$_SESSION['ceo_id'];
        $_SESSION['user_role'] = 'ceo';
        $_SESSION['user_name'] = $_SESSION['ceo_name']  ?? $_SESSION['user_name']  ?? '';
        $_SESSION['user_email']= $_SESSION['ceo_email'] ?? $_SESSION['user_email'] ?? '';
        $_SESSION['_synced_from'] = 'ceo_users';
    } elseif (!empty($_SESSION['staff_id'])) {
        $_SESSION['user_id']   = (int)$_SESSION['staff_id'];
        $_SESSION['user_role'] = 'staff';
        $_SESSION['user_name'] = $_SESSION['staff_name']  ?? $_SESSION['user_name']  ?? '';
        $_SESSION['user_email']= $_SESSION['staff_email'] ?? $_SESSION['user_email'] ?? '';
        $_SESSION['_synced_from'] = 'staff_members';
    }
}

panda_sync_standalone_session_to_unified();

/**
 * Handle Property Image Upload (File or URL fallback)
 */
function upload_property_image_file($file_key, $fallback_url = '') {
    $upload_dir = dirname(__DIR__) . '/uploads/properties/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES[$file_key]) && !empty($_FILES[$file_key]['tmp_name']) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES[$file_key]['tmp_name'];
        $orig = $_FILES[$file_key]['name'];
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed, true)) {
            $filename = 'prop_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($tmp, $target)) {
                return 'uploads/properties/' . $filename;
            }
        }
    }

    return trim((string)$fallback_url);
}

/**
 * Handle Branding / Profile / Realtor Image Upload
 */
function upload_branding_image_file($file_key, $subfolder = 'branding', $fallback_url = '') {
    $subfolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $subfolder);
    $upload_dir = dirname(__DIR__) . '/uploads/' . $subfolder . '/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES[$file_key]) && !empty($_FILES[$file_key]['tmp_name']) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES[$file_key]['tmp_name'];
        $orig = $_FILES[$file_key]['name'];
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];

        if (in_array($ext, $allowed, true)) {
            $filename = $subfolder . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($tmp, $target)) {
                return 'uploads/' . $subfolder . '/' . $filename;
            }
        }
    }

    return trim((string)$fallback_url);
}

/**
 * Normalize a single property video URL for embedding
 */
function normalize_single_property_video($raw_url) {
    $raw = trim((string)$raw_url);
    if ($raw === '') return '';
    $platform = 'other';
    $embed = normalize_embed_video_url($raw, $platform);
    return $embed !== '' ? $embed : $raw;
}

function is_logged_in() {
    return
        (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']))
        || !empty($_SESSION['superadmin_id'])
        || !empty($_SESSION['ceo_id'])
        || !empty($_SESSION['staff_id']);
}

function is_superadmin() {
    return !empty($_SESSION['superadmin_id']) || (is_logged_in() && get_current_user_role() === 'superadmin');
}

function get_current_user_role() {
    if (!empty($_SESSION['superadmin_id'])) return 'superadmin';
    if (!empty($_SESSION['ceo_id']))      return 'ceo';
    if (!empty($_SESSION['staff_id']))    return 'staff';
    return $_SESSION['user_role'] ?? 'guest';
}

function is_admin() {
    return is_logged_in() && is_internal_role(get_current_user_role());
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = sanitize_redirect_target(get_request_path(), 'index');
        redirect_to('login', ['redirect' => $_SESSION['redirect_after_login']]);
    }
}

function require_admin() {
    if (!is_admin()) {
        redirect_to('admin/login', ['error' => 'unauthorized']);
    }
}

function require_superadmin() {
    if (!is_superadmin()) {
        redirect_to('admin/super-login', ['error' => 'superadmin_required']);
    }
}

/**
 * Log Security & Audit Trail
 */
function log_security_action($action, $details = '', $status = 'success', $user_id = null) {
    $conn = get_db_connection();
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];
    }

    $user_role = $_SESSION['user_role'] ?? 'guest';
    $ip = get_client_ip();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 255);

    $action = db_escape($action);
    $details = db_escape($details);
    $user_role = db_escape($user_role);
    $ip = db_escape($ip);
    $ua = db_escape($ua);
    $status = db_escape($status);

    $user_id_sql = $user_id ? (int) $user_id : 'NULL';

    $query = "INSERT INTO security_logs (user_id, user_role, action, details, ip_address, user_agent, status)
              VALUES ($user_id_sql, '$user_role', '$action', '$details', '$ip', '$ua', '$status')";
    @mysqli_query($conn, $query);
}

/**
 * Track Live Visitors & Page Views
 */
function track_visitor($page_title = '') {
    $conn = get_db_connection();
    $ip = get_client_ip();
    $url = $_SERVER['REQUEST_URI'] ?? '/';
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $sess_id = session_id();

    $device = 'Desktop';
    if (preg_match('/(mobile|android|iphone|ipad)/i', $ua)) {
        $device = 'Mobile';
    }

    $ip = db_escape($ip);
    $url = db_escape($url);
    $ref = db_escape(substr($ref, 0, 255));
    $ua = db_escape(substr($ua, 0, 255));
    $page_title = db_escape($page_title);
    $device = db_escape($device);
    $sess_id = db_escape($sess_id);

    $sql = "INSERT INTO visitor_analytics (ip_address, page_url, page_title, referrer, user_agent, device_type, session_id, is_online, last_heartbeat)
            VALUES ('$ip', '$url', '$page_title', '$ref', '$ua', '$device', '$sess_id', 1, NOW())";
    @mysqli_query($conn, $sql);

    if (isset($_SESSION['user_id'])) {
        $uid = (int) $_SESSION['user_id'];
        @mysqli_query($conn, "UPDATE users SET is_online = 1, last_active = NOW() WHERE id = $uid");
    }
}

/**
 * Check if site is in Maintenance Mode
 */
function is_maintenance_mode() {
    return get_setting('maintenance_mode', '0') === '1';
}

/**
 * Handle Maintenance Mode Redirection
 */
function check_maintenance_redirect() {
    if (is_maintenance_mode()) {
        if (!is_admin()) {
            $current_script = basename($_SERVER['PHP_SELF'] ?? '');
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            if ($current_script !== 'maintenance.php' && strpos($request_uri, '/admin') === false && $current_script !== 'login.php') {
                redirect_to('maintenance');
            }
        }
    }
}

function enforce_https_if_configured() {
    if (PHP_SAPI === 'cli' || is_local_environment()) {
        return;
    }

    if (panda_env('FORCE_HTTPS', '1') !== '1' || is_https_request()) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    perform_redirect('https://' . $host . $request_uri);
}

function enforce_session_timeout() {
    $now = time();

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
        return;
    }

    if (isset($_SESSION['user_id']) && ($now - (int)$_SESSION['last_activity']) > get_session_timeout_seconds()) {
        $uid = (int)$_SESSION['user_id'];
        $conn = get_db_connection();
        @mysqli_query($conn, "UPDATE users SET is_online = 0 WHERE id = $uid");
        log_security_action('SESSION_TIMEOUT', 'Session expired after inactivity timeout.', 'warning', $uid);
        destroy_active_session('Your session expired after 2 hours of inactivity. Please sign in again.');
    }

    $_SESSION['last_activity'] = $now;
}

/**
 * Get array of image URLs from Property JSON
 */
function get_property_images($json) {
    if (empty($json)) {
        return ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200'];
    }

    $images = json_decode($json, true);
    if (!is_array($images) || empty($images)) {
        return ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200'];
    }

    return $images;
}

/**
 * Get array of features from Property JSON
 */
function get_property_features($json) {
    if (empty($json)) {
        return [];
    }

    $features = json_decode($json, true);
    return is_array($features) ? $features : [];
}

function parse_multiline_entries($value) {
    $items = [];
    $lines = preg_split('/\r\n|\r|\n/', (string)$value);

    foreach ((array)$lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

function get_property_videos($json) {
    if (empty($json)) {
        return [];
    }

    $videos = json_decode($json, true);
    return is_array($videos) ? $videos : [];
}

function get_video_platform_label($platform) {
    $labels = [
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'other' => 'Video'
    ];

    return $labels[$platform] ?? 'Video';
}

function normalize_embed_video_url($url, &$platform = 'other') {
    $url = trim((string)$url);
    if ($url === '') {
        $platform = 'other';
        return '';
    }

    if (strpos($url, 'youtube.com/embed/') !== false || strpos($url, 'youtube-nocookie.com/embed/') !== false) {
        $platform = 'youtube';
        return $url;
    }

    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})#', $url, $matches)) {
        $platform = 'youtube';
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if (strpos($url, 'facebook.com/plugins/video.php') !== false) {
        $platform = 'facebook';
        return $url;
    }

    if (preg_match('#https?://(?:www\.)?facebook\.com/.+/(videos|reel)/[^?\s]+#i', $url)) {
        $platform = 'facebook';
        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=false';
    }

    if (strpos($url, 'instagram.com/') !== false) {
        if (preg_match('#https?://(?:www\.)?instagram\.com/(?:p|reel)/([^/?#]+)/embed/?#i', $url, $matches)) {
            $platform = 'instagram';
            return 'https://www.instagram.com/reel/' . $matches[1] . '/embed/';
        }
        if (preg_match('#https?://(?:www\.)?instagram\.com/(?:p|reel)/([^/?#]+)/?#i', $url, $matches)) {
            $platform = 'instagram';
            return 'https://www.instagram.com/reel/' . $matches[1] . '/embed/';
        }
    }

    if (strpos($url, 'tiktok.com/embed/') !== false) {
        $platform = 'tiktok';
        return $url;
    }

    if (preg_match('#https?://(?:www\.)?tiktok\.com/.*/video/(\d+)#i', $url, $matches)) {
        $platform = 'tiktok';
        return 'https://www.tiktok.com/embed/v2/' . $matches[1];
    }

    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host)) {
        $platform = 'other';
        return '';
    }

    $host = strtolower($host);
    $allowed_hosts = [
        'www.youtube.com',
        'youtube.com',
        'www.youtube-nocookie.com',
        'youtube-nocookie.com',
        'www.facebook.com',
        'facebook.com',
        'www.instagram.com',
        'instagram.com',
        'www.tiktok.com',
        'tiktok.com'
    ];

    if (!in_array($host, $allowed_hosts, true)) {
        $platform = 'other';
        return '';
    }

    $platform = strpos($host, 'youtube') !== false ? 'youtube' : (strpos($host, 'facebook') !== false ? 'facebook' : (strpos($host, 'instagram') !== false ? 'instagram' : (strpos($host, 'tiktok') !== false ? 'tiktok' : 'other')));
    return $url;
}

function fetch_public_testimonials($limit = 6) {
    $conn = get_db_connection();
    $limit = max(1, (int)$limit);
    $items = [];
    $res = mysqli_query($conn, "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY is_featured DESC, display_order ASC, id DESC LIMIT {$limit}");

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
    }

    return $items;
}

function fetch_public_videos($limit = 12) {
    $conn = get_db_connection();
    $limit = max(1, (int)$limit);
    $items = [];
    $res = mysqli_query($conn, "SELECT * FROM media_videos WHERE is_active = 1 ORDER BY is_featured DESC, display_order ASC, id DESC LIMIT {$limit}");

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
    }

    return $items;
}

function get_default_whatsapp_country_code() {
    $code = preg_replace('/\D+/', '', (string)get_setting('whatsapp_default_country_code', '254'));
    return $code !== '' ? $code : '254';
}

function normalize_whatsapp_number($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if ($digits === '') {
        return '';
    }

    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    if ($digits[0] === '0') {
        $digits = get_default_whatsapp_country_code() . substr($digits, 1);
    }

    return $digits;
}

function build_whatsapp_link($phone, $message = '') {
    $normalized = normalize_whatsapp_number($phone);
    if ($normalized === '') {
        return '';
    }

    $url = 'https://wa.me/' . rawurlencode($normalized);
    $message = trim((string)$message);
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }

    return $url;
}

function send_whatsapp_message($recipient_phone, $message, &$transport = 'disabled') {
    $recipient = normalize_whatsapp_number($recipient_phone);
    $message = trim((string)$message);
    if ($recipient === '' || $message === '') {
        $transport = 'invalid';
        return false;
    }

    $token = trim((string)panda_env('WHATSAPP_ACCESS_TOKEN', get_setting('whatsapp_access_token', '')));
    $phone_number_id = trim((string)panda_env('WHATSAPP_PHONE_NUMBER_ID', get_setting('whatsapp_phone_number_id', '')));

    if ($token === '' || $phone_number_id === '' || !function_exists('curl_init')) {
        $transport = 'link_only';
        return false;
    }

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => $recipient,
        'type' => 'text',
        'text' => ['body' => $message]
    ]);

    if ($payload === false) {
        $transport = 'encode_failed';
        return false;
    }

    $ch = curl_init('https://graph.facebook.com/v20.0/' . rawurlencode($phone_number_id) . '/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $transport = ($http_code >= 200 && $http_code < 300) ? 'api_sent' : 'api_failed';
    return $response !== false && $http_code >= 200 && $http_code < 300;
}

function fetch_business_team_members() {
    $conn = get_db_connection();
    $items = [];
    $res = mysqli_query($conn, "SELECT id, name, role FROM users WHERE role IN ('ceo','admin','staff','superadmin') ORDER BY FIELD(role, 'ceo', 'admin', 'staff', 'superadmin'), name ASC");

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
    }

    return $items;
}

function fetch_public_property_tips($limit = 5) {
    $conn = get_db_connection();
    $limit = max(1, (int)$limit);
    $items = [];
    $sql = "SELECT pt.*, p.title AS property_title
            FROM property_tips pt
            LEFT JOIN properties p ON pt.linked_property_id = p.id
            WHERE pt.is_active = 1
            ORDER BY pt.display_order ASC, pt.id DESC
            LIMIT {$limit}";
    $res = @mysqli_query($conn, $sql);

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
    }

    return $items;
}

function fetch_featured_advert_property() {
    $conn = get_db_connection();
    $res = @mysqli_query($conn, "SELECT * FROM properties WHERE is_featured = 1 AND status != 'sold' ORDER BY views_count DESC, id DESC LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return $row;
    }

    return null;
}

function get_ceo_dashboard_snapshot() {
    $conn = get_db_connection();
    $snapshot = [
        'pipeline_value' => 0,
        'open_invoices_value' => 0,
        'due_followups' => 0,
        'consultations_scheduled' => 0,
        'site_visits_confirmed' => 0,
        'top_property_title' => '',
        'top_property_views' => 0
    ];

    $pipeline_res = @mysqli_query($conn, "SELECT SUM(estimated_value * (probability_percent / 100)) AS weighted_value FROM sales_pipeline WHERE stage NOT IN ('won','lost')");
    if ($pipeline_res && ($row = mysqli_fetch_assoc($pipeline_res))) {
        $snapshot['pipeline_value'] = (float)($row['weighted_value'] ?? 0);
    }

    $invoice_res = @mysqli_query($conn, "SELECT SUM(balance_due) AS open_balance FROM invoices WHERE status IN ('unpaid','partially_paid','overdue')");
    if ($invoice_res && ($row = mysqli_fetch_assoc($invoice_res))) {
        $snapshot['open_invoices_value'] = (float)($row['open_balance'] ?? 0);
    }

    $followup_res = @mysqli_query($conn, "SELECT
        (SELECT COUNT(*) FROM inquiries WHERE follow_up_date IS NOT NULL AND follow_up_date <= CURDATE() AND status NOT IN ('resolved','archived')) +
        (SELECT COUNT(*) FROM site_visits WHERE follow_up_date IS NOT NULL AND follow_up_date <= CURDATE() AND status NOT IN ('completed','cancelled'))
        AS due_count");
    if ($followup_res && ($row = mysqli_fetch_assoc($followup_res))) {
        $snapshot['due_followups'] = (int)($row['due_count'] ?? 0);
    }

    $booking_res = @mysqli_query($conn, "SELECT
        SUM(CASE WHEN booking_type = 'consultation' AND status IN ('pending','confirmed') THEN 1 ELSE 0 END) AS consultations,
        SUM(CASE WHEN booking_type = 'site_visit' AND status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_visits
        FROM site_visits");
    if ($booking_res && ($row = mysqli_fetch_assoc($booking_res))) {
        $snapshot['consultations_scheduled'] = (int)($row['consultations'] ?? 0);
        $snapshot['site_visits_confirmed'] = (int)($row['confirmed_visits'] ?? 0);
    }

    $top_property_res = @mysqli_query($conn, "SELECT title, views_count FROM properties ORDER BY views_count DESC, id DESC LIMIT 1");
    if ($top_property_res && ($row = mysqli_fetch_assoc($top_property_res))) {
        $snapshot['top_property_title'] = (string)($row['title'] ?? '');
        $snapshot['top_property_views'] = (int)($row['views_count'] ?? 0);
    }

    return $snapshot;
}

/**
 * Get Counts for Dashboard
 */
function get_system_counts() {
    $conn = get_db_connection();
    $counts = [
        'total_properties' => 0,
        'sold_properties' => 0,
        'under_construction' => 0,
        'completed_properties' => 0,
        'land_properties' => 0,
        'studio_apartments' => 0,
        'new_inquiries' => 0,
        'pending_visits' => 0,
        'online_visitors' => 0,
        'total_pageviews' => 0,
        'total_clients' => 0,
        'total_staff_users' => 0,
        'total_internal_users' => 0,
        'active_pipeline_deals' => 0,
        'won_pipeline_deals' => 0
    ];

    $res = mysqli_query($conn, "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold,
        SUM(CASE WHEN status = 'under_construction' THEN 1 ELSE 0 END) as under_construction,
        SUM(CASE WHEN status = 'available' AND construction_progress = 100 THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN type = 'land' THEN 1 ELSE 0 END) as land,
        SUM(CASE WHEN type = 'studio' THEN 1 ELSE 0 END) as studio
        FROM properties");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $counts['total_properties'] = (int) $row['total'];
        $counts['sold_properties'] = (int) ($row['sold'] ?? 0);
        $counts['under_construction'] = (int) ($row['under_construction'] ?? 0);
        $counts['completed_properties'] = (int) ($row['completed'] ?? 0);
        $counts['land_properties'] = (int) ($row['land'] ?? 0);
        $counts['studio_apartments'] = (int) ($row['studio'] ?? 0);
    }

    $res2 = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'");
    if ($res2 && $row = mysqli_fetch_assoc($res2)) {
        $counts['new_inquiries'] = (int) $row['count'];
    }

    $res3 = mysqli_query($conn, "SELECT COUNT(*) as count FROM site_visits WHERE status = 'pending' AND visit_date >= CURDATE()");
    if ($res3 && $row = mysqli_fetch_assoc($res3)) {
        $counts['pending_visits'] = (int) $row['count'];
    }

    $res4 = mysqli_query($conn, "SELECT COUNT(DISTINCT ip_address) as online FROM visitor_analytics WHERE last_heartbeat >= NOW() - INTERVAL 5 MINUTE");
    if ($res4 && $row = mysqli_fetch_assoc($res4)) {
        $counts['online_visitors'] = (int) ($row['online'] ?? 0);
    }

    $res5 = mysqli_query($conn, "SELECT COUNT(*) as total_views FROM visitor_analytics");
    if ($res5 && $row = mysqli_fetch_assoc($res5)) {
        $counts['total_pageviews'] = (int) $row['total_views'];
    }

    $res6 = mysqli_query($conn, "SELECT
        SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) AS clients,
        SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) AS staff_users,
        SUM(CASE WHEN role IN ('superadmin','developer','ceo','admin','staff') THEN 1 ELSE 0 END) AS internal_users
        FROM users");
    if ($res6 && $row = mysqli_fetch_assoc($res6)) {
        $counts['total_clients'] = (int) ($row['clients'] ?? 0);
        $counts['total_staff_users'] = (int) ($row['staff_users'] ?? 0);
        $counts['total_internal_users'] = (int) ($row['internal_users'] ?? 0);
    }

    $res7 = @mysqli_query($conn, "SELECT
        SUM(CASE WHEN stage NOT IN ('won','lost') THEN 1 ELSE 0 END) AS active_deals,
        SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) AS won_deals
        FROM sales_pipeline");
    if ($res7 && $row = mysqli_fetch_assoc($res7)) {
        $counts['active_pipeline_deals'] = (int) ($row['active_deals'] ?? 0);
        $counts['won_pipeline_deals'] = (int) ($row['won_deals'] ?? 0);
    }

    return $counts;
}

enforce_https_if_configured();
enforce_session_timeout();
