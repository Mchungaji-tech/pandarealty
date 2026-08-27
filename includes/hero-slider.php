<?php
/**
 * Panda Realty - Dynamic Featured Properties Hero Slider
 * Designed & Developed by TekTrend
 */

$conn = get_db_connection();
$hero_res = mysqli_query($conn, "SELECT * FROM properties WHERE is_hero_slide = 1 OR is_featured = 1 ORDER BY is_hero_slide DESC, id DESC LIMIT 5");

$hero_properties = [];
if ($hero_res && mysqli_num_rows($hero_res) > 0) {
    while ($row = mysqli_fetch_assoc($hero_res)) {
        $hero_properties[] = $row;
    }
}
?>

<section class="hero-slider-section">
    <div class="hero-slider-container">
        <?php foreach ($hero_properties as $idx => $prop): 
            $images = get_property_images($prop['images']);
            $hero_img = $images[0] ?? 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1600';
            $features = get_property_features($prop['features']);
        ?>
            <div class="hero-slide <?= $idx === 0 ? 'active' : '' ?>">
                <div class="hero-slide-bg" style="background-image: url('<?= htmlspecialchars($hero_img) ?>');"></div>
                
                <div class="hero-slide-content">
                    <div class="hero-slide-left">
                        <div class="hero-tag">
                            <i class="fas fa-gem"></i> 
                            <?= $prop['status'] === 'under_construction' ? 'Under Construction (' . $prop['construction_progress'] . '%)' : ($prop['type'] === 'studio' ? 'Executive Studio' : 'Featured Property') ?>
                        </div>
                        
                        <h1 class="hero-slide-title"><?= htmlspecialchars($prop['title']) ?></h1>
                        
                        <p class="hero-slide-text">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($prop['location']) ?> — <?= substr(htmlspecialchars($prop['description']), 0, 160) ?>...
                        </p>

                        <div class="hero-price-box">
                            <div class="hero-price-label">Starting Price (<?= $prop['category'] === 'rent' ? 'Per Month' : 'Purchase' ?>)</div>
                            <div class="hero-price-val" data-price-kes="<?= (float)$prop['price_kes'] ?>" data-price-period="<?= $prop['category'] === 'rent' ? '/mo' : '' ?>">
                                <?= format_price($prop['price_kes']) ?><?= $prop['category'] === 'rent' ? '<span style="font-size: 16px; color: #cbd5e1;">/mo</span>' : '' ?>
                            </div>
                        </div>

                        <div class="hero-slide-cta">
                            <a href="<?= htmlspecialchars(app_path('property-details?id=' . (int)$prop['id'])) ?>" class="btn btn-gold">
                                <i class="fas fa-eye"></i> View Property Specs
                            </a>
                            <button type="button" class="btn btn-outline" style="border-color: white; color: white;" onclick="openModal('hireAgentModal')">
                                <i class="fas fa-user-tie"></i> Speak with Perpetuah
                            </button>
                        </div>
                    </div>

                    <!-- Floating Card Preview -->
                    <div class="hero-card-preview">
                        <h4>Property Highlights</h4>
                        <div class="hero-specs-list">
                            <?php if ($prop['type'] === 'land'): ?>
                                <span><i class="fas fa-ruler-combined"></i> Size: <?= htmlspecialchars($prop['land_size'] ?: '50x100') ?></span>
                                <span><i class="fas fa-file-contract"></i> Freehold Title</span>
                            <?php else: ?>
                                <span><i class="fas fa-bed"></i> <?= $prop['bedrooms'] ?> Bedroom(s)</span>
                                <span><i class="fas fa-bath"></i> <?= $prop['bathrooms'] ?> Bathroom(s)</span>
                                <span><i class="fas fa-vector-square"></i> <?= number_format($prop['area_sqft']) ?> Sq Ft</span>
                            <?php endif; ?>
                            <span><i class="fas fa-shield-alt"></i> Verified Listing</span>
                        </div>

                        <?php if ($prop['status'] === 'under_construction'): ?>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 5px; color: #cbd5e1;">
                                    <span>Construction Stage: <?= htmlspecialchars($prop['construction_stage']) ?></span>
                                    <span><?= (int)$prop['construction_progress'] ?>%</span>
                                </div>
                                <div class="progress-track" style="background: rgba(255,255,255,0.2);">
                                    <div class="progress-fill" style="width: <?= (int)$prop['construction_progress'] ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Navigation Arrows -->
    <div class="hero-slider-arrows">
        <button type="button" class="slider-arrow-btn" onclick="prevHeroSlide()"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="slider-arrow-btn" onclick="nextHeroSlide()"><i class="fas fa-chevron-right"></i></button>
    </div>

    <!-- Navigation Dots -->
    <div class="hero-slider-dots">
        <?php foreach ($hero_properties as $idx => $prop): ?>
            <div class="slider-dot <?= $idx === 0 ? 'active' : '' ?>" onclick="goToHeroSlide(<?= $idx ?>)"></div>
        <?php endforeach; ?>
    </div>
</section>
