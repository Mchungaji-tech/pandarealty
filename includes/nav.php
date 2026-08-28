<?php
/**
 * Panda Realty - Front-End Responsive Navigation Bar & Mobile Drawer
 * Designed & Developed by TekTrend
 */

$curr_active = isset($_COOKIE['panda_currency']) ? strtoupper($_COOKIE['panda_currency']) : 'KES';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Announcement & Contact Bar -->
<div class="top-bar">
    <div class="top-bar-left">
        <a href="tel:<?= urlencode($contact_phone_intl) ?>" title="Direct Phone Call">
            <i class="fas fa-phone-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($contact_phone) ?>
        </a>
        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20inquiring%20about%20Panda%20Realty%20properties%20in%20Eldoret." target="_blank" title="WhatsApp Perpetuah">
            <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Perpetuah
        </a>
        <span class="top-bar-location">
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
            <a href="<?= htmlspecialchars(app_path('login')) ?>" class="top-login-link">
                <i class="fas fa-lock"></i> Sign In
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Sticky Navbar -->
<header class="site-header" id="siteHeader">
    <nav class="navbar" id="navbar" aria-label="Main Navigation">
        <div class="nav-container">
            
            <!-- Brand Logo with Realtor Avatar -->
            <a href="<?= htmlspecialchars(app_path('index')) ?>" class="logo-wrap" aria-label="Panda Realty Homepage">
                <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150'" alt="Perpetuah Realtor" class="logo-avatar" width="44" height="44">
                <div class="logo-text">
                    <?php if (!empty($site_logo)): ?>
                        <img src="<?= htmlspecialchars(normalize_media_url($site_logo)) ?>" alt="<?= htmlspecialchars($site_name) ?>" style="max-height: 36px; object-fit: contain; margin-bottom: 2px;">
                    <?php else: ?>
                        <span class="logo-main">PANDA <span>REALTY</span></span>
                    <?php endif; ?>
                    <span class="logo-sub">Perpetuah Realtor • Eldoret</span>
                </div>
            </a>

            <!-- Desktop Navigation Menu -->
            <div class="nav-links" id="desktopNavLinks">
                <a href="<?= htmlspecialchars(app_path('index')) ?>" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="<?= $current_page === 'properties.php' && !isset($_GET['filter']) ? 'active' : '' ?>">Properties</a>
                <a href="<?= htmlspecialchars(app_path('properties?filter=studio')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'studio' ? 'active' : '' ?>" style="color: var(--accent);">Studio Apts</a>
                <a href="<?= htmlspecialchars(app_path('properties?filter=land')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'land' ? 'active' : '' ?>">Land &amp; Plots</a>
                <a href="<?= htmlspecialchars(app_path('properties?filter=construction')) ?>" class="<?= isset($_GET['filter']) && $_GET['filter'] === 'construction' ? 'active' : '' ?>">Projects</a>
                <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="<?= $current_page === 'videos.php' ? 'active' : '' ?>">TikTok &amp; Videos</a>
                <a href="<?= htmlspecialchars(app_path('contact')) ?>" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Contact</a>
            </div>

            <!-- Desktop Actions -->
            <div class="nav-buttons">
                <a href="<?= htmlspecialchars(app_path('list-property')) ?>" class="btn btn-primary nav-list-btn">
                    <i class="fas fa-plus-circle"></i> List Property
                </a>
                <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20would%20like%20to%20inquire%20about%20properties%20in%20Eldoret." target="_blank" class="btn btn-whatsapp nav-wa-btn">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>

                <!-- Mobile Hamburger Menu Button -->
                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open Navigation Menu" aria-expanded="false" aria-controls="mobileNavDrawer">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                    <span class="menu-label">MENU</span>
                </button>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Slide-Out Navigation Drawer -->
<div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileMenu()"></div>
<aside class="mobile-nav-drawer" id="mobileNavDrawer" aria-label="Mobile Navigation Menu" aria-hidden="true">
    
    <!-- Drawer Header -->
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150'" alt="Perpetuah Realtor" class="drawer-avatar">
            <div>
                <strong style="color: #fff; font-size: 15px; display: block;">Panda Realty</strong>
                <span style="color: var(--accent); font-size: 11px;">Perpetuah Realtor • Eldoret</span>
            </div>
        </div>
        <button type="button" class="drawer-close-btn" id="mobileNavClose" onclick="closeMobileMenu()" aria-label="Close Menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Quick Currency Switcher Inside Drawer -->
    <div class="drawer-currency-box">
        <span class="currency-label"><i class="fas fa-coins" style="color: var(--accent);"></i> Currency:</span>
        <div class="currency-switcher">
            <button type="button" class="currency-btn <?= $curr_active === 'KES' ? 'active' : '' ?>" data-curr="KES" onclick="switchCurrency('KES')">KSH</button>
            <button type="button" class="currency-btn <?= $curr_active === 'USD' ? 'active' : '' ?>" data-curr="USD" onclick="switchCurrency('USD')">USD</button>
        </div>
    </div>

    <!-- Mobile Navigation Links List -->
    <div class="drawer-links">
        <a href="<?= htmlspecialchars(app_path('index')) ?>" class="drawer-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="drawer-link <?= $current_page === 'properties.php' && !isset($_GET['filter']) ? 'active' : '' ?>">
            <i class="fas fa-building"></i> All Properties
        </a>
        <a href="<?= htmlspecialchars(app_path('properties?filter=studio')) ?>" class="drawer-link <?= isset($_GET['filter']) && $_GET['filter'] === 'studio' ? 'active' : '' ?>">
            <i class="fas fa-door-open" style="color: var(--accent);"></i> Studio Apartments <span class="nav-pill-badge">High ROI</span>
        </a>
        <a href="<?= htmlspecialchars(app_path('properties?filter=land')) ?>" class="drawer-link <?= isset($_GET['filter']) && $_GET['filter'] === 'land' ? 'active' : '' ?>">
            <i class="fas fa-map" style="color: #10b981;"></i> Prime 50x100 Plots <span class="nav-pill-badge" style="background: rgba(16,185,129,0.15); color: #10b981;">Title Ready</span>
        </a>
        <a href="<?= htmlspecialchars(app_path('properties?filter=construction')) ?>" class="drawer-link <?= isset($_GET['filter']) && $_GET['filter'] === 'construction' ? 'active' : '' ?>">
            <i class="fas fa-hard-hat" style="color: #f59e0b;"></i> Building &amp; Villas
        </a>
        <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="drawer-link <?= $current_page === 'videos.php' ? 'active' : '' ?>">
            <i class="fab fa-tiktok" style="color: #ec4899;"></i> TikTok Videos &amp; Cinema
        </a>
        <a href="<?= htmlspecialchars(app_path('list-property')) ?>" class="drawer-link <?= $current_page === 'list-property.php' ? 'active' : '' ?>" style="color: #34d399;">
            <i class="fas fa-plus-circle"></i> List Your Property
        </a>
        <a href="<?= htmlspecialchars(app_path('contact')) ?>" class="drawer-link <?= $current_page === 'contact.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Contact Us
        </a>
        <?php if (is_logged_in()): ?>
            <a href="<?= htmlspecialchars(is_admin() ? app_path('admin') : app_path('list-property')) ?>" class="drawer-link" style="color: var(--accent);">
                <i class="fas fa-user-shield"></i> My Portal (<?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>)
            </a>
            <a href="<?= htmlspecialchars(app_path('logout')) ?>" class="drawer-link" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        <?php else: ?>
            <a href="<?= htmlspecialchars(app_path('login')) ?>" class="drawer-link">
                <i class="fas fa-lock"></i> Client / Staff Sign In
            </a>
        <?php endif; ?>
    </div>

    <!-- Drawer Fast Contact CTAs -->
    <div class="drawer-cta-wrap">
        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20would%20like%20to%20consult%20on%20Eldoret%20real%20estate%20properties." target="_blank" class="drawer-btn btn-wa">
            <i class="fab fa-whatsapp"></i> WhatsApp Perpetuah Directly
        </a>
        <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="drawer-btn btn-call">
            <i class="fas fa-phone-alt"></i> Call <?= htmlspecialchars($contact_phone) ?>
        </a>
    </div>
</aside>

<!-- Mobile Sticky Bottom Quick-Action Bar -->
<div class="mobile-bottom-bar" id="mobileBottomBar" aria-label="Quick Mobile Actions">
    <a href="<?= htmlspecialchars(app_path('index')) ?>" class="bottom-bar-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="bottom-bar-item <?= $current_page === 'properties.php' ? 'active' : '' ?>">
        <i class="fas fa-search"></i>
        <span>Properties</span>
    </a>
    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20interested%20in%20Panda%20Realty%20properties." target="_blank" class="bottom-bar-item wa-highlight" title="WhatsApp Chat">
        <i class="fab fa-whatsapp"></i>
        <span>WhatsApp</span>
    </a>
    <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="bottom-bar-item" title="Call Perpetuah">
        <i class="fas fa-phone-alt"></i>
        <span>Call</span>
    </a>
    <button type="button" class="bottom-bar-item" onclick="toggleMobileMenu()" aria-label="Open Full Menu">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </button>
</div>
