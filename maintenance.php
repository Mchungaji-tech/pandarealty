<?php
/**
 * Panda Realty - System Maintenance Page
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$site_name = get_setting('site_name', 'Panda Realty - Perpetuah Realtor');
$contact_phone = get_setting('contact_phone', '0708 289 852');
$contact_phone_intl = get_setting('contact_phone_intl', '+254708289852');
$whatsapp_number = get_setting('whatsapp_number', '254708289852');
$maintenance_msg = get_setting('maintenance_message', 'Panda Realty portal is currently undergoing scheduled system upgrades. We will be back online shortly! For urgent inquiries, call or WhatsApp Perpetuah directly at 0708 289 852.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance | <?= htmlspecialchars($site_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #090d16;
            --card: #111827;
            --accent: #c5a059;
            --text: #f3f4f6;
            --border: #1f293d;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }
        .maint-container {
            max-width: 650px;
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 50px 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6);
        }
        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            letter-spacing: 2px;
            color: #fff;
            margin-bottom: 5px;
        }
        .brand-title span { color: var(--accent); }
        .brand-subtitle {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #9ca3af;
            margin-bottom: 35px;
        }
        .icon-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(197, 160, 89, 0.15);
            border: 2px solid var(--accent);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(197, 160, 89, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(197, 160, 89, 0); }
            100% { box-shadow: 0 0 0 0 rgba(197, 160, 89, 0); }
        }
        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            margin-bottom: 15px;
            color: #fff;
        }
        p {
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 35px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-whatsapp:hover { background: #1eb956; }
        .btn-phone { background: var(--accent); color: #000; }
        .btn-phone:hover { background: #dfb96f; }
        .admin-link {
            font-size: 12px;
            color: #6b7280;
            text-decoration: none;
        }
        .admin-link:hover { color: var(--accent); }
    </style>
</head>
<body>

    <div class="maint-container">
        <div class="brand-title">PANDA <span>REALTY</span></div>
        <div class="brand-subtitle">Perpetuah Realtor • Eldoret Property Expert</div>

        <div class="icon-circle">
            <i class="fas fa-tools"></i>
        </div>

        <h2>System Upgrades in Progress</h2>
        <p><?= nl2br(htmlspecialchars($maintenance_msg)) ?></p>

        <div class="btn-group">
            <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20reaching%20out%20while%20the%20website%20is%20updating." target="_blank" class="btn btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
            <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="btn btn-phone">
                <i class="fas fa-phone-alt"></i> Call <?= htmlspecialchars($contact_phone) ?>
            </a>
        </div>

        <div>
            <a href="<?= htmlspecialchars(app_path('admin/staff-login')) ?>" class="admin-link"><i class="fas fa-lock"></i> Staff / Administrator Access</a>
        </div>
    </div>

</body>
</html>
