<?php
/**
 * Panda Realty - Front-End Common Header
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';

// Track visitor for analytics
$page_title_meta = isset($page_title) ? $page_title . " | " . $site_name : $site_name . " - " . $site_tagline;
track_visitor($page_title_meta);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title_meta) ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="<?= htmlspecialchars($site_tagline) ?>. Buy, rent, or invest in titled plots, studio apartments, and luxury homes in Eldoret, Kenya with Perpetuah Realtor.">
    <meta name="keywords" content="Panda Realty, Perpetuah Realtor, Eldoret real estate, plots for sale Eldoret, studio apartments Eldoret, Elgon View homes, Annex plots, Kenya property">
    <meta name="author" content="TekTrend Technologies">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    
    <script>
        // Global Exchange Rate
        window.USD_EXCHANGE_RATE = <?= json_encode($currency_usd_rate) ?>;
    </script>
</head>
<body>

<!-- Luxury Brand Page Loader -->
<div id="pageLoader" class="page-loader">
    <div class="loader-container">
        <?php if (!empty($site_logo)): ?>
            <img src="<?= htmlspecialchars(normalize_media_url($site_logo)) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="loader-logo-img">
        <?php else: ?>
            <div class="loader-logo-text">PANDA <span>REALTY</span></div>
        <?php endif; ?>
        <div class="loader-ring"></div>
        <div class="loader-subtext">Perpetuah Realtor • Eldoret Luxury Living</div>
    </div>
</div>

<script>
(function() {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;

    var startTime = Date.now();
    var minDisplayTime = 1300; // 1.3s elegant presentation

    function hideLoader() {
        if (!loader.classList.contains('loader-hidden')) {
            loader.classList.add('loader-hidden');
            setTimeout(function() {
                if (loader.parentNode) loader.style.display = 'none';
            }, 550);
        }
    }

    function scheduleDismiss() {
        var elapsed = Date.now() - startTime;
        var remaining = Math.max(0, minDisplayTime - elapsed);
        setTimeout(hideLoader, remaining);
    }

    if (document.readyState === 'complete') {
        scheduleDismiss();
    } else {
        window.addEventListener('load', scheduleDismiss);
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(scheduleDismiss, 200);
        });
    }

    // Safety timeout: Maximum 2.5s total visibility
    setTimeout(hideLoader, 2500);

    // Immediate dismissal if user clicks
    window.addEventListener('click', hideLoader, { once: true });
})();
</script>

<?php if (is_maintenance_mode() && is_admin()): ?>
    <div style="background: #ef4444; color: white; text-align: center; padding: 8px 20px; font-size: 13px; font-weight: 700; z-index: 9999; position: relative;">
        ⚠️ MAINTENANCE MODE IS CURRENTLY ACTIVE. Normal visitors see the maintenance page. (Logged in as <?= strtoupper($_SESSION['user_role'] ?? 'ADMIN') ?>)
    </div>
<?php endif; ?>

