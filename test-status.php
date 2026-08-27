<?php
/**
 * Panda Realty - Live Server Diagnostic JSON API
 */
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

$response = [
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'server_path' => __DIR__,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'https' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'env_exists' => file_exists(__DIR__ . '/.env'),
    'env_cpanel_exists' => file_exists(__DIR__ . '/.env.cpanel'),
    'database' => [
        'connected' => false,
        'error' => null,
        'tables_count' => 0
    ],
    'directories' => []
];

// Read active .env or fallback to .env.cpanel
$env_vars = [];
$env_file = file_exists(__DIR__ . '/.env') ? (__DIR__ . '/.env') : (file_exists(__DIR__ . '/.env.cpanel') ? (__DIR__ . '/.env.cpanel') : null);
if ($env_file) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $l) {
        $l = trim($l);
        if ($l === '' || strpos($l, '#') === 0 || strpos($l, '=') === false) continue;
        list($k, $v) = explode('=', $l, 2);
        $env_vars[trim($k)] = trim(trim($v), "\"'");
    }
}

$db_host = $env_vars['DB_HOST'] ?? 'localhost';
$db_name = $env_vars['DB_NAME'] ?? 'tektxbzg_pandareality';
$db_user = $env_vars['DB_USER'] ?? 'tektxbzg_realty';
$db_pass = $env_vars['DB_PASS'] ?? 'Gkf^u(9^Hv6x9~8#';

try {
    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if ($conn) {
        $response['database']['connected'] = true;
        $res = mysqli_query($conn, "SHOW TABLES");
        if ($res) {
            $response['database']['tables_count'] = mysqli_num_rows($res);
        }
        mysqli_close($conn);
    } else {
        $response['database']['error'] = mysqli_connect_error();
    }
} catch (Throwable $e) {
    $response['database']['error'] = $e->getMessage();
}

$dirs = ['uploads', 'uploads/properties', 'uploads/branding', 'uploads/realtor', 'uploads/avatars'];
foreach ($dirs as $d) {
    $p = __DIR__ . '/' . $d;
    $response['directories'][$d] = [
        'exists' => is_dir($p),
        'writable' => is_dir($p) && is_writable($p)
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
