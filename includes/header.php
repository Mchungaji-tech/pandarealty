<?php
/**
 * Panda Realty - Front-End Common Header & SEO Engine
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';

// SEO & Meta Resolution
$site_title_full = !empty($page_title) ? $page_title . " | " . $site_name : $site_name . " — " . $site_tagline;
$meta_desc = !empty($page_description) ? $page_description : "Find prime 50x100 titled plots, modern studio apartments, luxury family villas, and high-yield real estate investments in Eldoret, Kenya with Perpetuah Realtor.";
$meta_keys = !empty($page_keywords) ? $page_keywords : "Panda Realty, Perpetuah Realtor, Eldoret real estate, plots for sale Eldoret, studio apartments Eldoret, Elgon View homes, Annex 50x100 plots, Pioneer studios, Eldoret property, Kenya diaspora real estate, title deeds Eldoret";

$protocol = is_https_request() ? 'https://' : 'http://';
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$site_base_url = $protocol . $http_host . app_path();
$canonical_url = !empty($page_canonical) ? $page_canonical : ($protocol . $http_host . ($_SERVER['REQUEST_URI'] ?? '/'));

$og_image = !empty($page_image) ? normalize_media_url($page_image) : ($protocol . $http_host . app_path('assets/images/perpetuah.jpg'));
$og_type = !empty($page_og_type) ? $page_og_type : 'website';

track_visitor($site_title_full);
?>
<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Primary SEO Meta Tags -->
    <title><?= htmlspecialchars($site_title_full) ?></title>
    <meta name="title" content="<?= htmlspecialchars($site_title_full) ?>">
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keys) ?>">
    <meta name="author" content="TekTrend Technologies">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    
    <!-- Geographic & Regional Meta Tags for Eldoret Local SEO -->
    <meta name="geo.region" content="KE-44">
    <meta name="geo.placename" content="Eldoret, Uasin Gishu County, Kenya">
    <meta name="geo.position" content="0.5143;35.2698">
    <meta name="ICBM" content="0.5143, 35.2698">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title_full) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Panda Realty">
    <meta property="og:locale" content="en_KE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($site_title_full) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Theme & Mobile App Configuration -->
    <meta name="theme-color" content="#0B0F19">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Panda Realty">
    <meta name="format-detection" content="telephone=no">

    <?php if (!empty($site_favicon)): ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars(normalize_media_url($site_favicon)) ?>">
        <link rel="apple-touch-icon" href="<?= htmlspecialchars(normalize_media_url($site_favicon)) ?>">
    <?php endif; ?>

    <!-- Preconnect to CDNs for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Google Fonts with display=swap for Core Web Vitals -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_path('assets/css/animations.css')) ?>">

    <!-- JSON-LD: Real Estate Agency & Local Business Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": ["RealEstateAgent", "LocalBusiness"],
      "name": "Panda Realty - Perpetuah Realtor",
      "image": "<?= htmlspecialchars($og_image) ?>",
      "url": "<?= htmlspecialchars($site_base_url) ?>",
      "telephone": "+254708289852",
      "priceRange": "KES 25,000 - KES 100,000,000",
      "description": "Eldoret's premier real estate consultancy for prime 50x100 titled plots, modern studio apartments, luxury family villas, and commercial property investments.",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "KVDA Plaza, 4th Floor, Oginga Odinga Street",
        "addressLocality": "Eldoret",
        "addressRegion": "Uasin Gishu",
        "postalCode": "30100",
        "addressCountry": "KE"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": 0.5143,
        "longitude": 35.2698
      },
      "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        "opens": "08:00",
        "closes": "18:00"
      },
      "sameAs": [
        "https://www.facebook.com/PandaRealtyKenya",
        "https://www.instagram.com/PandaRealtyKenya",
        "https://www.tiktok.com/@pandarealtyke"
      ]
    }
    </script>

    <!-- JSON-LD: Sitelinks SearchBox WebSite Schema -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Panda Realty",
      "url": "<?= htmlspecialchars($site_base_url) ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= htmlspecialchars($site_base_url) ?>/properties?location={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <!-- Page Specific Additional JSON-LD Schema (if defined) -->
    <?php if (!empty($schema_json_ld_extra)): ?>
        <?php if (is_array($schema_json_ld_extra)): ?>
            <?php foreach ($schema_json_ld_extra as $extra_schema): ?>
                <script type="application/ld+json">
                <?= is_string($extra_schema) ? $extra_schema : json_encode($extra_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>
                </script>
            <?php endforeach; ?>
        <?php else: ?>
            <script type="application/ld+json">
            <?= $schema_json_ld_extra ?>
            </script>
        <?php endif; ?>
    <?php endif; ?>
    
    <script>
        // Global Exchange Rate
        window.USD_EXCHANGE_RATE = <?= json_encode($currency_usd_rate ?? 130.00) ?>;
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
    var minDisplayTime = 900;

    function hideLoader() {
        if (!loader.classList.contains('loader-hidden')) {
            loader.classList.add('loader-hidden');
            setTimeout(function() {
                if (loader.parentNode) loader.style.display = 'none';
            }, 450);
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
            setTimeout(scheduleDismiss, 100);
        });
    }

    setTimeout(hideLoader, 2000);
    window.addEventListener('click', hideLoader, { once: true });
})();
</script>

<?php if (is_maintenance_mode() && is_admin()): ?>
    <div style="background: #ef4444; color: white; text-align: center; padding: 8px 20px; font-size: 13px; font-weight: 700; z-index: 9999; position: relative;">
        ⚠️ MAINTENANCE MODE IS CURRENTLY ACTIVE. Normal visitors see the maintenance page. (Logged in as <?= strtoupper($_SESSION['user_role'] ?? 'ADMIN') ?>)
    </div>
<?php endif; ?>
