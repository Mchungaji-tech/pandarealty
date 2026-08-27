<?php
/**
 * Panda Realty - Front-End Navigation Bar
 * Designed & Developed by TekTrend
 */

$curr_active = isset($_COOKIE['panda_currency']) ? strtoupper($_COOKIE['panda_currency']) : 'KES';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Announcement & Contact Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="tel:<?= urlencode($contact_phone_intl) ?>">
            <i class="fas fa-phone-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($contact_phone) ?>
        </a>
        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20interested%20in%20Panda%20Realty%20properties%20in%20Eldoret." target="_blank">
            <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Perpetuah
        </a>
        <span>
            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> Eldoret, Kenya
        </span>
    </div>
    <div class="top-bar-right">
        <!-- Currency Switcher: KSh / USD -->
        <div class="currency-switcher" title="Toggle Display Currency">
            <button type="button" class="currency-btn <?= $curr_active === 'KES' ? 'active' : '' ?>" data-curr="KES" onclick="switchCurrency('KES')">KSH</button>
            <button type="button" class="currency-btn <?= $curr_active === 'USD' ? 'active' : '' ?>" data-curr="USD" onclick="switchCurrency('USD')">USD</button>
        </div>

        <?php if (is_logged_in()): ?>
            <a href="<?= htmlspecialchars(is_admin() ? app_path('admin') : app_path('list-property')) ?>" style="font-weight: 600; color: var(--accent);">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>
            </a>
            <a href="<?= htmlspecialchars(app_path('logout')) ?>" style="color: #ef4444;" title="Sign Out"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="<?= htmlspecialchars(app_path('login')) ?>">
                <i class="fas fa-lock"></i> Sign In
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Sticky Navbar -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="<?= htmlspecialchars(app_path('index')) ?>" class="logo-wrap">
            <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150'" alt="Perpetuah Realtor" class="logo-avatar">
            <div class="logo-text">
                <?php if (!empty($site_logo)): ?>
                    <img src="<?= htmlspecialchars(normalize_media_url($site_logo)) ?>" alt="<?= htmlspecialchars($site_name) ?>" style="max-height: 38px; object-fit: contain; margin-bottom: 2px;">
                <?php else: ?>
                    <span class="logo-main">PANDA <span>REALTY</span></span>
                <?php endif; ?>
                <span class="logo-sub">Perpetuah Realtor • Eldoret</span>
            </div>
        </a>

        <div class="nav-links" id="navLinks">
            <a href="<?= htmlspecialchars(app_path('index')) ?>" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="<?= $current_page === 'properties.php' && !isset($_GET['filter']) ? 'active' : '' ?>">Properties</a>
            <a href="<?= htmlspecialchars(app_path('properties?filter=studio')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'studio' ? 'active' : '' ?>" style="color: var(--accent);">Studio Apts</a>
            <a href="<?= htmlspecialchars(app_path('properties?filter=land')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'land' ? 'active' : '' ?>">Land & Plots</a>
            <a href="<?= htmlspecialchars(app_path('properties?filter=construction')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'construction' ? 'active' : '' ?>">Building Projects</a>
            <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="<?= $current_page === 'videos.php' ? 'active' : '' ?>">Videos</a>
            <a href="<?= htmlspecialchars(app_path('list-property')) ?>" class="<?= $current_page === 'list-property.php' ? 'active' : '' ?>" style="color: #34d399;"><i class="fas fa-plus-circle"></i> List Property</a>
            <a href="<?= htmlspecialchars(app_path('contact')) ?>" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Contact Us</a>
        </div>

        <div class="nav-buttons">
            <a href="<?= htmlspecialchars(app_path('list-property')) ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> List Property
            </a>
            <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20would%20like%20to%20inquire%20about%20properties%20in%20Eldoret." target="_blank" class="btn btn-whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        </div>
    </div>
</nav>
