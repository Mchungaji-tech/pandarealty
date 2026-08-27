<?php
/**
 * Panda Realty - Main Homepage
 * Designed & Developed by TekTrend
 */

$page_title = "Panda Realty | Your Eldoret Property Expert";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/hero-slider.php';
require_once __DIR__ . '/includes/search-bar.php';

// Fetch properties for the front page grid
$conn = get_db_connection();
$query = "SELECT * FROM properties ORDER BY is_featured DESC, id DESC LIMIT 9";
$res = mysqli_query($conn, $query);

$properties = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $properties[] = $row;
    }
}
?>

<!-- Properties Section -->
<section class="properties-section" id="propertiesSection">
    <div class="section-header reveal-fade">
        <div>
            <h2 class="font-serif">Featured Eldoret Residences & Land</h2>
            <p>Handpicked luxury homes, high-yield studio apartments, and prime plots in Eldoret</p>
        </div>
        <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="btn btn-outline">
            View All Properties (<?= count($properties) ?>+) <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Properties Grid -->
    <div class="properties-grid">
        <?php foreach ($properties as $prop): 
            $images = get_property_images($prop['images']);
            $features = get_property_features($prop['features']);
            $videos = get_property_videos($prop['video_urls'] ?? '');
            $images_json = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
        ?>
            <div class="property-card reveal-fade" 
                 data-type="<?= htmlspecialchars($prop['type']) ?>" 
                 data-category="<?= htmlspecialchars($prop['category']) ?>" 
                 data-status="<?= htmlspecialchars($prop['status']) ?>">
                
                <!-- Card Image Slider with multiple photos -->
                <div class="card-slider-wrapper" onclick="openLightbox(<?= $images_json ?>, 0)">
                    <div class="card-slides">
                        <?php foreach ($images as $img_idx => $img_url): ?>
                            <img src="<?= htmlspecialchars($img_url) ?>" class="card-slide-img <?= $img_idx === 0 ? 'active' : '' ?>" alt="<?= htmlspecialchars($prop['title']) ?>">
                        <?php endforeach; ?>
                    </div>

                    <!-- Mini Slider Controls -->
                    <?php if (count($images) > 1): ?>
                        <button type="button" class="card-slider-prev" title="Previous Image"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="card-slider-next" title="Next Image"><i class="fas fa-chevron-right"></i></button>
                        
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

                    <!-- Favorite Button -->
                    <div class="property-favorite" onclick="toggleFavorite(event, this)" title="Save Property">
                        <i class="far fa-heart"></i>
                    </div>
                </div>

                <!-- Construction Progress Bar (if ongoing development) -->
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

                <!-- Property Details -->
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
                            <span><i class="fas fa-file-contract"></i> Freehold Title</span>
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
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20interested%20in%20<?= urlencode($prop['title']) ?>%20(KES%20<?= number_format($prop['price_kes']) ?>)." target="_blank" class="btn btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> Inquire
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Perpetuah Realtor Showcase Section -->
<section class="realtor-profile-section reveal-fade">
    <div class="realtor-img-wrap">
        <div class="realtor-img-card">
            <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800'" alt="Perpetuah Realtor - Eldoret Property Expert">
        </div>
        <div class="realtor-badge-float">
            <h5>Perpetuah Chepchirchir</h5>
            <p>Principal Consultant & Eldoret Specialist</p>
        </div>
    </div>

    <div class="realtor-content">
        <span class="realtor-subtitle">Your Eldoret Property Expert 🔑</span>
        <h2 class="font-serif"><?= htmlspecialchars(get_cms_block('about_title', 'Homes • Land • Investments')) ?></h2>
        <p class="realtor-bio">
            <?= nl2br(htmlspecialchars(get_cms_block('about_story', '"We don\'t just sell property — we change lives." Whether you are looking for ready-to-build 50x100 plots in Annex, modern executive studio apartments with high rental yields in Pioneer, or luxury family residences in Elgon View, I guide you every step of the way with verified land registries, clean title transfers, and flexible payment plans.'))) ?>
        </p>

        <div class="realtor-contact-pills">
            <div class="contact-pill">
                <i class="fas fa-phone-alt"></i> 0708 289 852
            </div>
            <div class="contact-pill">
                <i class="fab fa-whatsapp"></i> Instant WhatsApp Support
            </div>
            <div class="contact-pill">
                <i class="fas fa-map-marker-alt"></i> KVDA Plaza, Eldoret
            </div>
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20would%20like%20to%20consult%20with%20you%20on%20Eldoret%20investments." target="_blank" class="btn btn-gold">
                <i class="fab fa-whatsapp"></i> Chat with Perpetuah
            </a>
            <button type="button" class="btn btn-primary" onclick="openModal('hireAgentModal')">
                <i class="fas fa-envelope"></i> Request Consultation
            </button>
        </div>
    </div>
</section>

<!-- Background Video & Metrics Component -->
<?php require_once __DIR__ . '/includes/video-section.php'; ?>

<!-- Testimonials -->
<?php require_once __DIR__ . '/includes/testimonials-section.php'; ?>

<!-- Featured Videos -->
<?php require_once __DIR__ . '/includes/video-library-preview.php'; ?>

<!-- Banking & Regulatory Affiliations -->
<?php require_once __DIR__ . '/includes/partners-section.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
