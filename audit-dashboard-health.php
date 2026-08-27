<?php
/**
 * System Health Audit:
 * 1) Verify EVERY table that dashboards + CRM pages query actually EXISTS in database + has data
 * 2) Detect missing tables / empty tables that cause "dashboards have nothing" feeling
 */
require_once __DIR__ . '/config/functions.php';
$conn = get_db_connection();

$checks = [
    // --- Core CRM Tables (what admin/index.php KPI widgets select from) ---
    'properties'                  => "SELECT COUNT(*) c FROM `properties`",
    'inquiries'                   => "SELECT COUNT(*) c FROM `inquiries`",
    'site_visits (aka bookings)'  => "SELECT COUNT(*) c FROM `site_visits`",
    'tasks'                       => "SELECT COUNT(*) c FROM `tasks`",
    'invoices'                    => "SELECT COUNT(*) c FROM `invoices`",
    'sales_pipeline'              => "SELECT COUNT(*) c FROM `sales_pipeline`",

    // --- Super Admin Tables ---
    'security_audit_logs'         => "SELECT COUNT(*) c FROM `security_audit_logs`",
    'online_visitors / sessions info' => "SELECT COUNT(*) c FROM `sessions`",
    'site_settings / cms content' => "SELECT COUNT(*) c FROM `site_settings`",
    'users (now client only)'     => "SELECT COUNT(*) c FROM `users`",
    'super_admins (separate)'     => "SELECT COUNT(*) c FROM `super_admins`",
    'ceo_users (separate)'        => "SELECT COUNT(*) c FROM `ceo_users`",
    'staff_members (separate)'    => "SELECT COUNT(*) c FROM `staff_members`",

    // --- Other CRM Management Pages (sidebar links) ---
    'testimonials'                => "SELECT COUNT(*) c FROM `testimonials`",
    'media_videos'                => "SELECT COUNT(*) c FROM `media_videos`",
    'property_tips'               => "SELECT COUNT(*) c FROM `property_tips`",
];

echo "<pre style=\"font-family:Consolas,monospace;line-height:1.5;background:#0f172a;color:#e2e8f0;padding:22px;border-radius:10px\">";
echo str_repeat("═", 72) . "\n";
echo "          PANDA REALTY — DASHBOARD HEALTH AUDIT\n";
echo "          WHY DASHBOARDS 'HAVE NOTHING'? → DATA AUDIT\n";
echo str_repeat("═", 72) . "\n\n";

$missing = [];
$empty   = [];
$ok      = [];
foreach ($checks as $label => $sql) {
    try {
        $res = mysqli_query($conn, $sql);
    } catch (Exception $e) {
        $res = false;
    }
    if (!$res) {
        $missing[] = $label;
        echo "  ❌ MISSING TABLE:    $label   (SQL error: " . trim(mysqli_error($conn)) . ")\n";
    } else {
        $row = mysqli_fetch_assoc($res);
        $c = (int)($row['c'] ?? 0);
        if ($c === 0) {
            $empty[] = $label;
            echo "  ⚠️  EMPTY (0 rows):   $label  — widgets show 0/null → user sees 'nothing'\n";
        } else {
            $ok[] = $label;
            echo "  ✅ EXISTS + DATA:    $label  → rows: $c\n";
        }
    }
}

echo "\n", str_repeat("─", 72), "\n";
echo "SUMMARY:\n";
echo "  ✅ Tables with data:   ", count($ok), "\n";
echo "  ⚠️  Tables empty (0):  ", count($empty), "\n";
echo "  ❌ Tables missing:     ", count($missing), "\n";

if (!empty($missing)) {
    echo "\n❌ CRITICAL MISSING TABLES (README mentions them but they don't EXIST yet — pages break with mysqli errors):\n  • ",
         implode("\n  • ", $missing), "\n";
    echo "\n  → Fix: Add CREATE TABLE IF NOT EXISTS in db.php schema sync + seed sample data.\n";
}
if (!empty($empty)) {
    echo "\n⚠️  EMPTY TABLES (show 0 on dashboard KPI widgets → 'I have nothing' UX impression):\n  • ",
         implode("\n  • ", $empty), "\n";
    echo "\n  → Fix: Either (a) seed sample data (in database.sql + install.php), OR\n            (b) let staff enter data via their existing CRUD pages (CRUD pages exist per file-level audit).\n";
}
echo "\n</pre>";