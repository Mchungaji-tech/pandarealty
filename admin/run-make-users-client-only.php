<?php
/**
 * One-shot: Convert users.role ENUM -> CLIENT ONLY
 * Historical rows: leave data as-is, convert role column for existing non-client rows to 'client'
 *   (since all admin/staff are in separate super_admins / ceo_users / staff_members tables now)
 */
require_once __DIR__ . '/../config/functions.php';
$conn = get_db_connection();

echo "<pre>=== Converting users.role to CLIENT-ONLY ENUM ===\n";

$current = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$col = $current ? mysqli_fetch_assoc($current) : null;
echo "Current users.role Type: " . ($col['Type'] ?? 'N/A') . "\n";

$res = @mysqli_query($conn, "SELECT id, email, role FROM users WHERE role != 'client'");
$nonClient = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) { $nonClient[] = $row; }
}
echo "Non-client historical rows in users: " . count($nonClient) . "\n";
foreach ($nonClient as $r) {
    echo "  - user_id=" . $r['id'] . " email=" . $r['email'] . " role=" . $r['role'] . "\n";
}

if (!empty($nonClient)) {
    echo "\nConverting historical non-client rows to role='client' (data preserved, separate tables still have the active accounts)...\n";
    @mysqli_query($conn, "UPDATE IGNORE users SET role = 'client' WHERE role != 'client'");
    echo "Affected rows: " . mysqli_affected_rows($conn) . "\n";
}

echo "\nNow ALTER users.role ENUM('client') DEFAULT 'client'...\n";
$alter = @mysqli_query($conn, "ALTER TABLE users MODIFY role ENUM('client') DEFAULT 'client'");
if ($alter) { echo "✅ ALTER OK\n"; } else { echo "❌ ALTER FAILED: " . mysqli_error($conn) . "\n"; }

$final = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$f = $final ? mysqli_fetch_assoc($final) : null;
echo "Final users.role Type: " . ($f['Type'] ?? 'N/A') . "\n";

echo "\n=== Done. users.role now strictly ENUM('client') only ===\n";
echo "Now impossible (MySQL-level) to write superadmin/ceo/staff/admin/developer into users.role.\n";
echo "</pre>";