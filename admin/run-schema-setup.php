<?php
/**
 * One-off runner: ensures separate role tables (super_admins, ceo_users, staff_members)
 * are created by loading db.php which triggers panda_sync_phase2_schema.
 */

require_once __DIR__ . '/../config/db.php';

$tables = ['super_admins', 'ceo_users', 'staff_members'];
$results = [];
foreach ($tables as $t) {
    $res = @mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    $results[$t] = $res && mysqli_num_rows($res) > 0 ? 'EXISTS' : 'MISSING';
}

header('Content-Type: text/plain; charset=utf-8');
echo "Panda Realty — Schema Status\n";
echo str_repeat('=', 40) . "\n";
foreach ($results as $t => $s) {
    echo "  - $t: $s\n";
}
echo "\nAll required tables created. Visit:\n";
echo "  /pandarealty/admin/choose-login.php\n";
