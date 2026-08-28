<?php
/**
 * Panda Realty - Property Catalog & Search Page
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$conn = get_db_connection();

// Filter parameters
$filter_type     = clean_input($_GET['type'] ?? '');
$filter_category = clean_input($_GET['category'] ?? '');
$filter_location = clean_input($_GET['location'] ?? '');
$filter_bedrooms = clean_input($_GET['bedrooms'] ?? '');
$filter_tab      = clean_input($_GET['filter'] ?? '');

$where_clauses = ["1=1"];

if (!empty($filter_location)) {
    $loc_safe = db_escape($filter_location);
    $where_clauses[] = "(location LIKE '%$loc_safe%' OR title LIKE '%$loc_safe%' OR description LIKE '%$loc_safe%')";
}

if (!empty($filter_type)) {
    $type_safe = db_escape($filter_type);
    $where_clauses[] = "type = '$type_safe'";
}

if (!empty($filter_category)) {
    $cat_safe = db_escape($filter_category);
    $where_clauses[] = "category = '$cat_safe'";
}

if (!empty($filter_bedrooms)) {
    if ($filter_bedrooms === 'studio') {
        $where_clauses[] = "type = 'studio'";
    } else {
        $beds = (int)$filter_bedrooms;
        $where_clauses[] = "bedrooms >= $beds";
    }
}

// Handle quick tab filter
if ($filter_tab === 'studio') {
    $where_clauses[] = "type = 'studio'";
    $catalog_heading = "Executive Studio Apartments in Eldoret";
    $page_title = "Executive Studio Apartments in Eldoret | High ROI";
    $page_description = "Browse modern executive studio apartments in Annex and Pioneer, Eldoret. Verified high-demand rental investments with superior annual yields.";
} elseif ($filter_tab === 'land') {
    $where_clauses[] = "type = 'land'";
    $catalog_heading = "Prime Titled Land & 50x100 Plots in Eldoret";
    $page_title = "Plots & Land for Sale in Eldoret | 50x100 Ready Title Deeds";
    $page_description = "Secure prime 50x100 freehold titled plots in Annex, Elgon View, Kapsoya, and Pioneer Eldoret. Verified land searches and installment plans.";
} elseif ($filter_tab === 'construction') {
    $where_clauses[] = "status = 'under_construction'";
    $catalog_heading = "Ongoing Building Projects & Luxury Villas";
    $page_title = "Building Projects & Off-Plan Properties in Eldoret";
    $page_description = "Discover off-plan townhouses, luxury villas, and multi-unit studio developments under construction in Eldoret with milestone handover tracking.";
} elseif ($filter_tab === 'sale') {
    $where_clauses[] = "category = 'sale'";
    $catalog_heading = "Properties & Houses for Sale in Eldoret";
    $page_title = "Houses & Land for Sale in Eldoret | Verified Titles";
    $page_description = "Explore freehold residential homes, executive bungalows, townhouses, and plots for sale in Eldoret with flexible financing options.";
} elseif ($filter_tab === 'rent') {
    $where_clauses[] = "category = 'rent'";
    $catalog_heading = "Properties for Rent in Eldoret";
    $page_title = "Rental Houses & Studio Apartments in Eldoret";
    $page_description = "Find verified rental apartments, executive studio units, and family homes for rent across Eldoret and Uasin Gishu County.";
} else {
    $catalog_heading = "Explore All Eldoret Properties & Real Estate";
    $page_title = "Browse Properties, Plots & Studio Apartments in Eldoret";
    $page_description = "Search prime 50x100 titled plots, modern studio apartments, and luxury villas for sale and rent in Eldoret, Kenya with Perpetuah Realtor.";
}

if (!empty($filter_location)) {
    $page_title = "Properties in " . htmlspecialchars($filter_location) . ", Eldoret | Panda Realty";
}

// BreadcrumbList JSON-LD Schema
$site_url = (is_https_request() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . app_path();
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
            ]
        ]
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';

$where_sql = implode(' AND ', $where_clauses);
$query = "SELECT * FROM properties WHERE $where_sql ORDER BY id DESC";
$res = mysqli_query($conn, $query);

$properties = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $properties[] = $row;
    }
}
?>

<div class="page-header-banner">
    <h1 class="font-serif">
        <?= htmlspecialchars($catalog_heading) ?>
    </h1>
    <p>
        Found <?= count($properties) ?> verified listing(s) matching your criteria in Eldoret and Uasin Gishu County.
    </p>
</div>

<?php require_once __DIR__ . '/includes/search-bar.php'; ?>

<!-- Properties Grid -->
<section class="properties-section" style="padding-top: 40px;">
    <?php if (empty($properties)): ?>
        <div style="text-align: center; padding: 80px 20px; background: var(--light-gray); border-radius: 12px; border: 1px solid var(--border);">
            <i class="fas fa-search" style="font-size: 48px; color: var(--accent); margin-bottom: 20px;"></i>
            <h3 class="font-serif" style="font-size: 24px; margin-bottom: 10px;">No properties matched your exact filter</h3>
            <p style="color: var(--gray); margin-bottom: 25px;">Try adjusting your search criteria or contact Perpetuah directly for off-market listings in Eldoret.</p>
            <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="btn btn-gold">Reset All Filters</a>
        </div>
    <?php else: ?>
        <div class="properties-grid">
            <?php foreach ($properties as $prop): 
                $images = get_property_images($prop['images']);
                $features = get_property_features($prop['features']);
                $videos = get_property_videos($prop['video_urls'] ?? '');
                $images_json = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
            ?>
                <div class="property-card" 
                     data-type="<?= htmlspecialchars($prop['type']) ?>" 
                     data-category="<?= htmlspecialchars($prop['category']) ?>" 
                     data-status="<?= htmlspecialchars($prop['status']) ?>">
                    
                    <!-- Card Mini-Slider -->
                    <div class="card-slider-wrapper" onclick="openLightbox(<?= $images_json ?>, 0)">
                        <div class="card-slides">
                            <?php foreach ($images as $img_idx => $img_url): ?>
                                <img src="<?= htmlspecialchars($img_url) ?>" class="card-slide-img <?= $img_idx === 0 ? 'active' : '' ?>" alt="<?= htmlspecialchars($prop['title']) ?>">
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($images) > 1): ?>
                            <button type="button" class="card-slider-prev"><i class="fas fa-chevron-left"></i></button>
                            <button type="button" class="card-slider-next"><i class="fas fa-chevron-right"></i></button>
                            
                            <div class="card-slider-dots">
                                <?php foreach ($images as $d_idx => $img_url): ?>
                                    <span class="card-dot <?= $d_idx === 0 ? 'active' : '' ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Badges -->
                        <div class="badge-group">
                            <?php if ($prop['status'] === 'sold'): ?>
                                <span class="badge badge-sold"><i class="fas fa-check-circle"></i> Sold Out</span>
                            <?php elseif ($prop['status'] === 'under_construction'): ?>
                                <span class="badge badge-construction"><i class="fas fa-hard-hat"></i> Under Construction</span>
                            <?php elseif ($prop['type'] === 'studio'): ?>
                                <span class="badge badge-studio"><i class="fas fa-door-open"></i> Studio Apt</span>
                            <?php elseif ($prop['type'] === 'land'): ?>
                                <span class="badge badge-land"><i class="fas fa-map"></i> Titled Plot</span>
                            <?php elseif ($prop['category'] === 'sale'): ?>
                                <span class="badge badge-sale">For Sale</span>
                            <?php else: ?>
                                <span class="badge badge-rent">For Rent</span>
                            <?php endif; ?>
                            <?php if (!empty($videos)): ?>
                                <span class="badge badge-studio"><i class="fas fa-video"></i> Video Tour</span>
                            <?php endif; ?>
                        </div>

                        <div class="property-favorite" onclick="toggleFavorite(event, this)">
                            <i class="far fa-heart"></i>
                        </div>
                    </div>

                    <?php if ($prop['status'] === 'under_construction'): ?>
                        <div class="construction-progress-bar-wrap">
                            <div class="progress-header">
                                <span><i class="fas fa-tools"></i> Stage: <?= htmlspecialchars($prop['construction_stage']) ?></span>
                                <span><?= (int)$prop['construction_progress'] ?>% Built</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?= (int)$prop['construction_progress'] ?>%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="property-info">
                        <div class="property-price" data-price-kes="<?= (float)$prop['price_kes'] ?>" data-price-period="<?= $prop['category'] === 'rent' ? '/mo' : '' ?>">
                            <?= format_price($prop['price_kes']) ?><?= $prop['category'] === 'rent' ? '<span class="period">/mo</span>' : '' ?>
                        </div>

                        <a href="<?= htmlspecialchars(app_path('property-details?id=' . (int)$prop['id'])) ?>" class="property-title">
                            <?= htmlspecialchars($prop['title']) ?>
                        </a>

                        <div class="property-location">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($prop['location']) ?>
                        </div>

                        <div class="property-features">
                            <?php if ($prop['type'] === 'land'): ?>
                                <span><i class="fas fa-ruler-combined"></i> <?= htmlspecialchars($prop['land_size'] ?: '50 x 100 ft') ?></span>
                                <span><i class="fas fa-file-contract"></i> Clean Title</span>
                            <?php else: ?>
                                <span><i class="fas fa-bed"></i> <?= $prop['bedrooms'] ?> <?= $prop['type'] === 'studio' ? 'Studio' : 'Bed' ?></span>
                                <span><i class="fas fa-bath"></i> <?= $prop['bathrooms'] ?> Bath</span>
                                <?php if ($prop['area_sqft'] > 0): ?>
                                    <span><i class="fas fa-vector-square"></i> <?= number_format($prop['area_sqft']) ?> sqft</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <a href="<?= htmlspecialchars(app_path('property-details?id=' . (int)$prop['id'])) ?>" class="btn btn-outline">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                            <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20interested%20in%20<?= urlencode($prop['title']) ?>." target="_blank" class="btn btn-whatsapp">
                                <i class="fab fa-whatsapp"></i> Inquire
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
