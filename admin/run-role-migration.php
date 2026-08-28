<?php
/**
 * One-time MIGRATION:
 * Copies existing superadmin/ceo/staff/admin from the shared `users` table
 * into the separate role tables (super_admins / ceo_users / staff_members)
 * so the new standalone logins work with old passwords (bcrypt hashes preserved).
 *
 * Safe to re-run: uses INSERT IGNORE on email unique key — never overwrites existing rows.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Panda Realty — User Table -> Separate Role Tables Migration\n";
echo str_repeat('=', 55) . "\n\n";

$conn = get_db_connection();

$stats = [];

function migrate_role($conn, $src_role, $target_table, $extra_cols_sql = '') {
    $safe_role = mysqli_real_escape_string($conn, $src_role);

    $cols = [];
    $check_cols = [
        'name'                => 'name AS full_name',
        'email'               => 'email',
        'phone'               => 'phone',
        'password'            => 'password AS password_hash',
        'two_factor_secret'   => 'two_factor_secret',
        'two_factor_enabled'  => 'two_factor_enabled',
        'is_online'           => 'is_online',
        'last_active'         => 'last_active',
        'last_login_ip'       => 'last_login_ip',
        'created_at'          => 'created_at',
        'updated_at'          => 'updated_at',
    ];
    foreach (array_keys($check_cols) as $c) {
        $res = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '$c'");
        if ($res && mysqli_num_rows($res) > 0) {
            $cols[] = $check_cols[$c];
        }
    }
    if (empty($cols)) return ['total_in_users' => 0, 'copied' => 0, 'skipped' => 0, 'names' => []];

    $select = "SELECT " . implode(', ', $cols) . " FROM users WHERE role = '$safe_role'";
    $res = mysqli_query($conn, $select);
    if (!$res) {
        return ["error" => mysqli_error($conn)];
    }
    $total = mysqli_num_rows($res);
    $copied = 0;
    $skipped = 0;
    $names = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $fn  = mysqli_real_escape_string($conn, $row['full_name'] ?? $row['name'] ?? '');
        $em  = mysqli_real_escape_string($conn, $row['email'] ?? '');
        $ph  = mysqli_real_escape_string($conn, $row['phone'] ?? '');
        $pw  = mysqli_real_escape_string($conn, $row['password_hash'] ?? $row['password'] ?? '');
        $tfs = mysqli_real_escape_string($conn, $row['two_factor_secret'] ?? '');
        $tfe = (int)($row['two_factor_enabled'] ?? 0);
        $on  = (int)($row['is_online'] ?? 0);
        $la  = !empty($row['last_active']) ? "'" . mysqli_real_escape_string($conn, $row['last_active']) . "'" : 'NULL';
        $lip = mysqli_real_escape_string($conn, $row['last_login_ip'] ?? '');
        $ca  = !empty($row['created_at']) ? "'" . mysqli_real_escape_string($conn, $row['created_at']) . "'" : 'CURRENT_TIMESTAMP';
        $ua  = !empty($row['updated_at']) ? "'" . mysqli_real_escape_string($conn, $row['updated_at']) . "'" : 'CURRENT_TIMESTAMP';

        $extra_values = '';
        $extra_cols_sql = '';
        if ($target_table === 'staff_members') {
            $extra_cols_sql = ', department, position_title';
            $extra_values = ", 'Sales', 'Migrated Staff'";
        }

        $tgt_cols = "full_name, email, phone, password_hash, two_factor_secret, two_factor_enabled,
                     is_online, last_active, last_login_ip, created_at, updated_at $extra_cols_sql";
        $tgt_vals = "'$fn','$em','$ph','$pw','$tfs',$tfe,$on,$la,'$lip',$ca,$ua $extra_values";

        $sql = "INSERT IGNORE INTO $target_table ($tgt_cols) VALUES ($tgt_vals)";

        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                $copied++;
                $names[] = ($row['full_name'] ?? $row['name'] ?? '?') . " <" . ($row['email'] ?? '?') . ">";
            } else {
                $skipped++;
            }
        }
    }
    return ['total_in_users' => $total, 'copied' => $copied, 'skipped' => $skipped, 'names' => $names];
}

function table_count($conn, $table) {
    $r = @mysqli_query($conn, "SELECT COUNT(*) as c FROM $table");
    if ($r && $row = mysqli_fetch_assoc($r)) return (int)$row['c'];
    return -1;
}

echo "BEFORE migration row counts:\n";
echo "  super_admins : " . table_count($conn, 'super_admins') . "\n";
echo "  ceo_users    : " . table_count($conn, 'ceo_users') . "\n";
echo "  staff_members: " . table_count($conn, 'staff_members') . "\n\n";

echo "Migrating superadmin -> super_admins ...\n";
$stats['superadmin'] = migrate_role($conn, 'superadmin', 'super_admins');

echo "Migrating ceo -> ceo_users ...\n";
$stats['ceo'] = migrate_role($conn, 'ceo', 'ceo_users');

echo "Migrating staff -> staff_members ...\n";
$stats['staff'] = migrate_role($conn, 'staff', 'staff_members');

echo "Migrating admin -> staff_members ... (admin classed as staff on new schema)\n";
$stats['admin_as_staff'] = migrate_role($conn, 'admin', 'staff_members');

echo "Migrating developer -> super_admins ... (developer = full access)\n";
$stats['developer_as_super'] = migrate_role($conn, 'developer', 'super_admins');

echo "\n--- Migration Report ---\n";
foreach ($stats as $key => $s) {
    echo "  [$key] ";
    if (isset($s['error'])) { echo "ERROR: " . $s['error'] . "\n"; continue; }
    echo "found=" . ($s['total_in_users'] ?? 0) . "  copied=" . $s['copied'] . "  skipped(dup email)=" . $s['skipped'] . "\n";
    if (!empty($s['names'])) {
        foreach ($s['names'] as $n) echo "    + $n\n";
    }
}

echo "\nAFTER migration row counts:\n";
echo "  super_admins : " . table_count($conn, 'super_admins') . "\n";
echo "  ceo_users    : " . table_count($conn, 'ceo_users') . "\n";
echo "  staff_members: " . table_count($conn, 'staff_members') . "\n";

echo "\n✅ Done. You can now sign in via the standalone portals using your SAME email & password.\n";
echo "   Start: /pandarealty/admin/choose-login.php\n";
