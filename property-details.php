<?php
/**
 * Panda Realty - Property Details Page
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$property_id = (int)($_GET['id'] ?? 0);
$conn = get_db_connection();

// Increment views
if ($property_id > 0 && $conn) {
    @mysqli_query($conn, "UPDATE properties SET views_count = views_count + 1 WHERE id = $property_id");
}

$res = mysqli_query($conn, "SELECT * FROM properties WHERE id = $property_id LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: properties.php");
    exit;
}

$prop = mysqli_fetch_assoc($res);
$images = get_property_images($prop['images']);
$features = get_property_features($prop['features']);
$videos = get_property_videos($prop['video_urls'] ?? '');
$images_json = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');

// SEO Resolution
$clean_desc_excerpt = !empty($prop['description']) ? mb_substr(strip_tags($prop['description']), 0, 160) : "Verified real estate listing in Eldoret, Kenya by Perpetuah Realtor.";
$page_title = $prop['title'] . " in " . $prop['location'] . " — Panda Realty";
$page_description = "KES " . number_format($prop['price_kes']) . " - " . $prop['title'] . " located in " . $prop['location'] . ", Eldoret. " . $clean_desc_excerpt;
$page_image = !empty($images[0]) ? $images[0] : '';
$page_og_type = "article";

$site_url = (is_https_request() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . app_path();
$property_url = $site_url . '/property-details?id=' . (int)$prop['id'];

// Rich Structured Data Schema
$schema_json_ld_extra = [
    [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Home",
                "item" => $site_url . '/'
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => "Properties",
                "item" => $site_url . '/properties'
            ],
            [
                "@type" => "ListItem",
                "position" => 3,
                "name" => $prop['title'],
                "item" => $property_url
            ]
        ]
    ],
    [
        "@context" => "https://schema.org",
        "@type" => ($prop['type'] === 'land' ? "Place" : "SingleFamilyResidence"),
        "name" => $prop['title'],
        "description" => $clean_desc_excerpt,
        "url" => $property_url,
        "image" => array_map(function($img) use ($site_url) {
            return preg_match('#^https?://#i', $img) ? $img : ($site_url . '/' . ltrim($img, '/'));
        }, $images),
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => !empty($prop['address']) ? $prop['address'] : $prop['location'],
            "addressLocality" => "Eldoret",
            "addressRegion" => "Uasin Gishu",
            "postalCode" => "30100",
            "addressCountry" => "KE"
        ],
        "geo" => [
            "@type" => "GeoCoordinates",
            "latitude" => 0.5143,
            "longitude" => 35.2698
        ],
        "numberOfRooms" => max(1, (int)$prop['bedrooms']),
        "numberOfBedrooms" => (int)$prop['bedrooms'],
        "numberOfBathroomsTotal" => (int)$prop['bathrooms'],
        "offers" => [
            "@type" => "Offer",
            "price" => (float)$prop['price_kes'],
            "priceCurrency" => "KES",
            "availability" => ($prop['status'] === 'sold' ? "https://schema.org/SoldOut" : "https://schema.org/InStock"),
            "url" => $property_url,
            "seller" => [
                "@type" => "RealEstateAgent",
                "name" => "Panda Realty - Perpetuah Realtor",
                "telephone" => "+254708289852"
            ]
        ]
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="property-details-container">
    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" style="font-size: 13px; color: var(--gray); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a href="<?= htmlspecialchars(app_path('index')) ?>">Home</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <a href="<?= htmlspecialchars(app_path('properties')) ?>">Properties</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <span style="color: var(--primary); font-weight: 600;"><?= htmlspecialchars($prop['title']) ?></span>
    </nav>

    <!-- Header Section -->
    <header class="property-details-header">
        <div>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px; flex-wrap: wrap;">
                <span class="badge badge-sale"><?= strtoupper(htmlspecialchars($prop['category'])) ?></span>
                <?php if ($prop['type'] === 'studio'): ?>
                    <span class="badge badge-studio">Studio Apartment</span>
                <?php elseif ($prop['type'] === 'land'): ?>
                    <span class="badge badge-land">Titled Plot</span>
                <?php endif; ?>
                <?php if ($prop['status'] === 'under_construction'): ?>
                    <span class="badge badge-construction">Under Construction</span>
                <?php elseif ($prop['status'] === 'sold'): ?>
                    <span class="badge badge-sold">Sold Out</span>
                <?php endif; ?>
            </div>
            <h1 class="font-serif property-details-title"><?= htmlspecialchars($prop['title']) ?></h1>
            <p style="color: var(--gray); font-size: 15px; margin-top: 4px;">
                <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($prop['address'] ?: $prop['location']) ?>, Eldoret, Kenya
            </p>
        </div>

        <div style="text-align: right;">
            <div style="font-size: 11px; color: var(--gray); text-transform: uppercase; letter-spacing: 1px;">Starting Price</div>
            <div class="property-price property-details-price" data-price-kes="<?= (float)$prop['price_kes'] ?>" data-price-period="<?= $prop['category'] === 'rent' ? '/mo' : '' ?>">
                <?= format_price($prop['price_kes']) ?><?= $prop['category'] === 'rent' ? '<span class="period">/mo</span>' : '' ?>
            </div>
        </div>
    </header>

    <!-- Main Image Gallery (Responsive Grid with Lightbox trigger) -->
    <section class="property-gallery-grid" aria-label="Property Photos">
        <div class="property-gallery-main" onclick="openLightbox(<?= $images_json ?>, 0)" role="button" aria-label="Open fullscreen photo viewer">
            <img src="<?= htmlspecialchars($images[0] ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200') ?>" alt="<?= htmlspecialchars($prop['title']) ?> - Main Photo" loading="eager">
            <div style="position: absolute; bottom: 16px; left: 16px; background: rgba(0,0,0,0.75); color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; backdrop-filter: blur(4px);">
                <i class="fas fa-expand"></i> View All <?= count($images) ?> Photos
            </div>
        </div>

        <div class="property-gallery-side">
            <?php for ($i = 1; $i <= 2; $i++): 
                $extra_img = $images[$i] ?? ($images[0] ?? '');
            ?>
                <div class="property-gallery-item" onclick="openLightbox(<?= $images_json ?>, <?= $i ?>)" role="button" aria-label="View photo <?= $i + 1 ?>">
                    <img src="<?= htmlspecialchars($extra_img) ?>" alt="<?= htmlspecialchars($prop['title']) ?> - View <?= $i + 1 ?>" loading="lazy">
                    <?php if ($i === 2 && count($images) > 3): ?>
                        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.65); color: white; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700;">
                            +<?= count($images) - 3 ?> More Photos
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Main Content & Sidebar Grid -->
    <div class="property-main-layout">
        <article>
            <!-- Construction Progress (if applicable) -->
            <?php if ($prop['status'] === 'under_construction'): ?>
                <div style="background: var(--light-gray); border: 1px solid var(--border); border-left: 4px solid var(--accent); border-radius: 10px; padding: 22px; margin-bottom: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                        <h4 style="font-size: 15px; font-weight: 700;"><i class="fas fa-hard-hat" style="color: var(--accent);"></i> Construction Progress</h4>
                        <span style="font-weight: 700; color: var(--accent); font-size: 17px;"><?= (int)$prop['construction_progress'] ?>% Complete</span>
                    </div>
                    <div class="progress-track" style="height: 8px; margin-bottom: 10px;">
                        <div class="progress-fill" style="width: <?= (int)$prop['construction_progress'] ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--gray); flex-wrap: wrap; gap: 8px;">
                        <span>Current Stage: <strong><?= htmlspecialchars($prop['construction_stage'] ?: 'In Progress') ?></strong></span>
                        <span>Estimated Handover: <strong><?= htmlspecialchars($prop['completion_date'] ?: 'Late 2026') ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Key Quick Specs Strip -->
            <div class="property-specs-card" style="margin-bottom: 35px;">
                <h3 class="font-serif" style="font-size: 20px; margin-bottom: 15px;">Property Details</h3>
                <div class="property-features-chip-grid">
                    <?php if ($prop['type'] === 'land'): ?>
                        <div class="property-feature-chip">
                            <i class="fas fa-ruler-combined"></i>
                            <span>Size: <strong><?= htmlspecialchars($prop['land_size'] ?: '50 x 100 ft') ?></strong></span>
                        </div>
                        <div class="property-feature-chip">
                            <i class="fas fa-file-contract"></i>
                            <span>Title: <strong>Freehold Ready</strong></span>
                        </div>
                    <?php else: ?>
                        <div class="property-feature-chip">
                            <i class="fas fa-bed"></i>
                            <span>Bedrooms: <strong><?= $prop['bedrooms'] ?> <?= $prop['type'] === 'studio' ? '(Studio)' : '' ?></strong></span>
                        </div>
                        <div class="property-feature-chip">
                            <i class="fas fa-bath"></i>
                            <span>Bathrooms: <strong><?= $prop['bathrooms'] ?></strong></span>
                        </div>
                        <?php if ($prop['area_sqft'] > 0): ?>
                            <div class="property-feature-chip">
                                <i class="fas fa-vector-square"></i>
                                <span>Area: <strong><?= number_format($prop['area_sqft']) ?> sqft</strong></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="property-feature-chip">
                        <i class="fas fa-tag"></i>
                        <span>Type: <strong><?= ucfirst(htmlspecialchars($prop['type'])) ?></strong></span>
                    </div>
                    <div class="property-feature-chip">
                        <i class="fas fa-shield-alt"></i>
                        <span>Status: <strong>Verified by Registry</strong></span>
                    </div>
                </div>
            </div>

            <!-- Property Description -->
            <div style="margin-bottom: 35px;">
                <h3 class="font-serif" style="font-size: 22px; margin-bottom: 12px;">Property Overview</h3>
                <p style="color: var(--gray); font-size: 15px; line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($prop['description'])) ?>
                </p>
            </div>

            <?php if (!empty($videos)): ?>
                <div style="margin-bottom: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 18px;">
                        <h3 class="font-serif" style="font-size: 22px;">Video Tours &amp; Cinema Walkthrough</h3>
                        <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="btn btn-outline" style="font-size: 11px; padding: 8px 16px;">
                            <i class="fas fa-film"></i> All Videos
                        </a>
                    </div>
                    <div class="video-library-grid">
                        <?php foreach ($videos as $video_url): ?>
                            <?php $video_platform = 'other'; $embed_url = normalize_embed_video_url($video_url, $video_platform); ?>
                            <?php if ($embed_url !== ''): ?>
                                <div class="video-embed-card">
                                    <div class="video-embed-frame">
                                        <iframe src="<?= htmlspecialchars($embed_url) ?>" title="<?= htmlspecialchars($prop['title']) ?> video" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                    </div>
                                    <div class="video-embed-meta">
                                        <span class="video-platform-chip"><?= htmlspecialchars(get_video_platform_label($video_platform)) ?></span>
                                        <strong style="display: block; font-size: 14px;"><?= htmlspecialchars($prop['title']) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Features & Amenities -->
            <?php if (!empty($features)): ?>
                <div style="margin-bottom: 35px;">
                    <h3 class="font-serif" style="font-size: 22px; margin-bottom: 15px;">Amenities &amp; Estate Highlights</h3>
                    <div class="property-features-chip-grid">
                        <?php foreach ($features as $feat): ?>
                            <div class="property-feature-chip">
                                <i class="fas fa-check-circle"></i>
                                <span><?= htmlspecialchars($feat) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Front-End Installment & Mortgage Calculator -->
            <?php if ($prop['category'] === 'sale' && $prop['installment_available']): ?>
                <div class="calculator-box">
                    <h3 class="font-serif"><i class="fas fa-calculator" style="color: var(--accent);"></i> Flexible Installment &amp; Financing Calculator</h3>
                    <p style="font-size: 13px; color: var(--gray); margin-bottom: 15px;">
                        Estimate your down payment deposit and monthly installment schedule tailored for this listing.
                    </p>

                    <input type="hidden" id="calcPropertyPrice" value="<?= (float)$prop['price_kes'] ?>">

                    <div class="calc-grid">
                        <div>
                            <div class="form-group">
                                <label>Initial Down Payment: <strong id="calcDepositPercentText" style="color: var(--accent);">10%</strong></label>
                                <input type="range" id="calcDepositSlider" min="10" max="50" step="5" value="10" style="width: 100%; accent-color: var(--accent); cursor: pointer;">
                            </div>

                            <div class="form-group">
                                <label>Repayment Duration</label>
                                <select id="calcMonthsSelect">
                                    <option value="6">6 Months Plan (0% Interest)</option>
                                    <option value="12" selected>12 Months Plan</option>
                                    <option value="18">18 Months Plan</option>
                                    <option value="24">24 Months Plan</option>
                                    <option value="36">36 Months Plan</option>
                                </select>
                            </div>
                        </div>

                        <div class="calc-result-box">
                            <h4>Estimated Monthly Dues</h4>
                            <div class="calc-result-val" id="calcMonthlyVal">Calculating...</div>
                            <div style="font-size: 12px; color: #cbd5e1; display: flex; justify-content: space-between; margin-top: 10px;">
                                <span>Deposit: <strong id="calcDepositVal" style="color: #fff;">-</strong></span>
                                <span>Balance: <strong id="calcBalanceVal" style="color: #fff;">-</strong></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20calculated%20an%20installment%20plan%20for%20<?= urlencode($prop['title']) ?>%20and%20would%20like%20to%20apply." target="_blank" class="btn btn-whatsapp" style="width: 100%;">
                            <i class="fab fa-whatsapp"></i> Apply for Installment Plan on WhatsApp
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </article>

        <!-- Sidebar Contact & Booking Form -->
        <aside class="property-sidebar-sticky">
            <div class="contact-card">
                <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                    <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120'" alt="Perpetuah Realtor" style="width: 58px; height: 58px; border-radius: 50%; border: 2px solid var(--accent); object-fit: cover;">
                    <div>
                        <h4 style="font-size: 17px; margin-bottom: 2px;">Perpetuah Chepchirchir</h4>
                        <p style="font-size: 11px; color: var(--accent); font-weight: 700; text-transform: uppercase;">Lead Eldoret Realtor</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 22px;">
                    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20viewing%20<?= urlencode($prop['title']) ?>%20(KES%20<?= number_format($prop['price_kes']) ?>)%20and%20want%20more%20information." target="_blank" class="btn btn-whatsapp" style="width: 100%;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Perpetuah
                    </a>
                    <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="btn btn-outline" style="width: 100%;">
                        <i class="fas fa-phone-alt"></i> Call <?= htmlspecialchars($contact_phone) ?>
                    </a>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin-bottom: 20px;">

                <!-- Instant Site Tour Form -->
                <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 12px;">Book a Free Site Inspection</h4>
                <form action="<?= htmlspecialchars(app_path('contact?action=book_tour')) ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                    <input type="hidden" name="booking_type" value="site_visit">
                    <input type="hidden" name="consultation_mode" value="in_person">

                    <div class="form-group">
                        <label>Your Full Name *</label>
                        <input type="text" name="client_name" placeholder="e.g. Mary Jepkemboi" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number (WhatsApp) *</label>
                        <input type="tel" name="client_phone" placeholder="0708 289 852" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="client_email" placeholder="you@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label>Preferred Visit Date *</label>
                        <input type="date" name="visit_date" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Preferred Contact Channel</label>
                        <select name="preferred_contact">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="phone">Phone Call</option>
                            <option value="email">Email</option>
                        </select>
                    </div>

                    <label style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px; font-size: 12px; color: var(--gray);">
                        <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                        <span>Send WhatsApp confirmation</span>
                    </label>

                    <button type="submit" class="btn btn-gold" style="width: 100%; padding: 12px;">
                        <i class="fas fa-calendar-check"></i> Confirm Free Site Tour
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

