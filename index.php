<?php
/**
 * Panda Realty - Main Front-End Landing Page
 * Ultra-SEO Optimized & High-Converting Luxury Real Estate Platform
 * Designed & Developed by TekTrend
 */

$page_title = "Panda Realty — Eldoret Real Estate, Titled Plots & Studio Apartments";
$page_description = "Discover prime 50x100 titled plots, modern studio apartments, luxury family homes, and flexible installment property investments in Eldoret, Kenya with Perpetuah Realtor.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/hero-slider.php';
require_once __DIR__ . '/includes/search-bar.php';

// Fetch properties for the front page grid
$conn = get_db_connection();
$query = "SELECT * FROM properties ORDER BY is_featured DESC, id DESC LIMIT 9";
$res = $conn ? @mysqli_query($conn, $query) : false;

$properties = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $properties[] = $row;
    }
}
?>

<!-- Category Highlights / Quick Category Navigation Strip -->
<section class="category-strip-section" aria-label="Property Categories">
    <div class="category-strip-container">
        <a href="<?= htmlspecialchars(app_path('properties?filter=studio')) ?>" class="category-pill-card reveal-fade">
            <div class="pill-icon" style="background: rgba(197, 160, 89, 0.12); color: var(--accent);"><i class="fas fa-door-open"></i></div>
            <div class="pill-info">
                <h4>Studio Apartments</h4>
                <p>High Rental Yield • Annex &amp; Pioneer</p>
            </div>
            <i class="fas fa-arrow-right pill-arrow"></i>
        </a>

        <a href="<?= htmlspecialchars(app_path('properties?filter=land')) ?>" class="category-pill-card reveal-fade">
            <div class="pill-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;"><i class="fas fa-map"></i></div>
            <div class="pill-info">
                <h4>Prime 50x100 Plots</h4>
                <p>Ready Freehold Titles • Gated Estates</p>
            </div>
            <i class="fas fa-arrow-right pill-arrow"></i>
        </a>

        <a href="<?= htmlspecialchars(app_path('properties?filter=construction')) ?>" class="category-pill-card reveal-fade">
            <div class="pill-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;"><i class="fas fa-hard-hat"></i></div>
            <div class="pill-info">
                <h4>Ongoing Projects</h4>
                <p>Off-Plan Pricing • Luxury Villas</p>
            </div>
            <i class="fas fa-arrow-right pill-arrow"></i>
        </a>

        <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="category-pill-card reveal-fade">
            <div class="pill-icon" style="background: rgba(236, 72, 153, 0.12); color: #ec4899;"><i class="fab fa-tiktok"></i></div>
            <div class="pill-info">
                <h4>TikTok &amp; Video Tours</h4>
                <p>Virtual Video Walkthroughs</p>
            </div>
            <i class="fas fa-arrow-right pill-arrow"></i>
        </a>
    </div>
</section>

<!-- Properties Section -->
<section class="properties-section" id="propertiesSection" aria-label="Featured Properties">
    <div class="section-header reveal-fade">
        <div>
            <span class="section-subtitle"><i class="fas fa-gem" style="color: var(--accent);"></i> Handpicked Eldoret Catalog</span>
            <h2 class="font-serif">Featured Eldoret Residences &amp; Prime Land</h2>
            <p>Verified title deeds, executive studio apartments, and prime residential plots with flexible payment terms</p>
        </div>
        <a href="<?= htmlspecialchars(app_path('properties')) ?>" class="btn btn-outline">
            Explore All Properties (<?= count($properties) ?>+) <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Properties Grid -->
    <div class="properties-grid">
        <?php foreach ($properties as $prop): 
            $images = function_exists('get_property_images') ? get_property_images($prop['images']) : [];
            $features = function_exists('get_property_features') ? get_property_features($prop['features']) : [];
            $videos = function_exists('get_property_videos') ? get_property_videos($prop['video_urls'] ?? '') : [];
            $images_json = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
            $main_img = !empty($images) ? $images[0] : 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800';
        ?>
            <article class="property-card reveal-fade" 
                     data-type="<?= htmlspecialchars($prop['type']) ?>" 
                     data-category="<?= htmlspecialchars($prop['category']) ?>" 
                     data-status="<?= htmlspecialchars($prop['status']) ?>"
                     itemscope itemtype="https://schema.org/SingleFamilyResidence">
                
                <!-- Card Image Slider with multiple photos -->
                <div class="card-slider-wrapper" onclick="openLightbox(<?= $images_json ?>, 0)">
                    <div class="card-slides">
                        <?php if (!empty($images)): ?>
                            <?php foreach ($images as $img_idx => $img_url): ?>
                                <img src="<?= htmlspecialchars($img_url) ?>" class="card-slide-img <?= $img_idx === 0 ? 'active' : '' ?>" alt="<?= htmlspecialchars($prop['title']) ?> - Image <?= $img_idx + 1 ?>" itemprop="image" loading="lazy">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($main_img) ?>" class="card-slide-img active" alt="<?= htmlspecialchars($prop['title']) ?>" itemprop="image" loading="lazy">
                        <?php endif; ?>
                    </div>

                    <!-- Mini Slider Controls -->
                    <?php if (count($images) > 1): ?>
                        <button type="button" class="card-slider-prev" title="Previous Image" aria-label="Previous Slide"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="card-slider-next" title="Next Image" aria-label="Next Slide"><i class="fas fa-chevron-right"></i></button>
                        
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
                            <span class="badge badge-studio" style="background: rgba(239, 68, 68, 0.9);"><i class="fas fa-video"></i> Video Tour</span>
                        <?php endif; ?>
                    </div>

                    <!-- Favorite Button -->
                    <div class="property-favorite" onclick="toggleFavorite(event, this)" title="Save Property" aria-label="Save Property">
                        <i class="far fa-heart"></i>
                    </div>
                </div>

                <!-- Construction Progress Bar (if ongoing development) -->
                <?php if ($prop['status'] === 'under_construction'): ?>
                    <div class="construction-progress-bar-wrap">
                        <div class="progress-header">
                            <span><i class="fas fa-tools"></i> Stage: <?= htmlspecialchars($prop['construction_stage'] ?? 'In Progress') ?></span>
                            <span><?= (int)$prop['construction_progress'] ?>% Built</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?= (int)$prop['construction_progress'] ?>%;"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Property Details -->
                <div class="property-info">
                    <div class="property-price" data-price-kes="<?= (float)$prop['price_kes'] ?>" data-price-period="<?= $prop['category'] === 'rent' ? '/mo' : '' ?>" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <meta itemprop="priceCurrency" content="KES">
                        <meta itemprop="price" content="<?= (float)$prop['price_kes'] ?>">
                        <?= format_price($prop['price_kes']) ?><?= $prop['category'] === 'rent' ? '<span class="period">/mo</span>' : '' ?>
                    </div>

                    <h3 class="property-card-heading">
                        <a href="<?= htmlspecialchars(app_path('property-details?id=' . (int)$prop['id'])) ?>" class="property-title" itemprop="name">
                            <?= htmlspecialchars($prop['title']) ?>
                        </a>
                    </h3>

                    <div class="property-location" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                        <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i>
                        <span itemprop="streetAddress"><?= htmlspecialchars($prop['location']) ?></span>
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

                    <!-- Card Actions -->
                    <div class="card-actions">
                        <a href="<?= htmlspecialchars(app_path('property-details?id=' . (int)$prop['id'])) ?>" class="btn btn-outline" aria-label="View Details for <?= htmlspecialchars($prop['title']) ?>">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20inquiring%20about%20<?= urlencode($prop['title']) ?>%20(KES%20<?= number_format($prop['price_kes']) ?>)." target="_blank" class="btn btn-whatsapp" aria-label="WhatsApp Inquiry for <?= htmlspecialchars($prop['title']) ?>">
                            <i class="fab fa-whatsapp"></i> Inquire
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- 4 Pillars of Trust: Why Invest With Perpetuah & Panda Realty -->
<section class="trust-pillars-section reveal-fade" aria-label="Why Choose Panda Realty">
    <div class="section-header text-center" style="margin-bottom: 40px;">
        <span class="section-subtitle"><i class="fas fa-shield-alt" style="color: var(--accent);"></i> The Panda Realty Difference</span>
        <h2 class="font-serif">Why Smart Investors Choose Perpetuah in Eldoret</h2>
        <p>Transparent real estate transactions backed by verified legal title registry searches and flexible financing</p>
    </div>

    <div class="trust-grid">
        <div class="trust-card">
            <div class="trust-icon"><i class="fas fa-stamp"></i></div>
            <h3>100% Verified Freehold Titles</h3>
            <p>Every single plot and property undergoes rigorous due diligence at the Eldoret Land Registry before listing.</p>
        </div>
        <div class="trust-card">
            <div class="trust-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <h3>Flexible 12-24 Mo. Installments</h3>
            <p>Secure your property with an accessible 10%-20% down payment and spread the rest interest-free or tailored.</p>
        </div>
        <div class="trust-card">
            <div class="trust-icon"><i class="fas fa-globe-africa"></i></div>
            <h3>Dedicated Diaspora Concierge</h3>
            <p>Live video tours, legal power of attorney guidance, and seamless remote title transfers for Kenyans abroad.</p>
        </div>
        <div class="trust-card">
            <div class="trust-icon"><i class="fas fa-award"></i></div>
            <h3>Eldoret Market Mastery</h3>
            <p>Specialized neighborhood insight into Annex, Elgon View, Pioneer, and Kapsoya growth corridors.</p>
        </div>
    </div>
</section>

<!-- Perpetuah Realtor Showcase Section -->
<section class="realtor-profile-section reveal-fade" aria-label="Meet Perpetuah Realtor">
    <div class="realtor-img-wrap">
        <div class="realtor-img-card">
            <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800'" alt="Perpetuah Realtor - Eldoret Real Estate Specialist" loading="lazy">
        </div>
        <div class="realtor-badge-float">
            <h5>Perpetuah Chepchirchir</h5>
            <p>Principal Consultant &amp; Eldoret Property Expert</p>
        </div>
    </div>

    <div class="realtor-content">
        <span class="realtor-subtitle">Your Eldoret Property Expert 🔑</span>
        <h2 class="font-serif"><?= htmlspecialchars(get_cms_block('about_title', 'Homes • Land • High-Yield Investments')) ?></h2>
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
                <i class="fas fa-calendar-check"></i> Schedule Site Visit
            </button>
        </div>
    </div>
</section>

<!-- Eldoret Prime Neighborhoods Directory -->
<section class="neighborhoods-section reveal-fade" aria-label="Eldoret Real Estate Locations">
    <div class="section-header text-center" style="margin-bottom: 35px;">
        <span class="section-subtitle"><i class="fas fa-compass" style="color: var(--accent);"></i> Prime Locations</span>
        <h2 class="font-serif">Explore Eldoret's Fastest-Growing Estates</h2>
        <p>From prestigious luxury enclaves to high-density university and commercial hubs</p>
    </div>

    <div class="neighborhoods-grid">
        <div class="neighborhood-card">
            <h4>Elgon View</h4>
            <p>Eldoret's premier diplomatic and luxury estate. Executive 4-5 bedroom mansions on 1/2 acre plots with tight security.</p>
            <span class="badge badge-sale">Luxury Mansions</span>
        </div>
        <div class="neighborhood-card">
            <h4>Annex Oasis</h4>
            <p>Fast-growing investment hub near universities. Prime 50x100 residential plots and high-yield executive studio apartments.</p>
            <span class="badge badge-studio">High Rental Demand</span>
        </div>
        <div class="neighborhood-card">
            <h4>Pioneer Estate</h4>
            <p>Minutes from Eldoret CBD. Ideal for university faculty, young professionals, and modern studio apartment investments.</p>
            <span class="badge badge-rent">Rental Yields 12%+</span>
        </div>
        <div class="neighborhood-card">
            <h4>Kapsoya &amp; Highlands</h4>
            <p>Serene, well-developed family neighborhood with paved access, reliable borehole water, and top private schools.</p>
            <span class="badge badge-sale">Family Villas</span>
        </div>
        <div class="neighborhood-card">
            <h4>Maili Nne Corridor</h4>
            <p>High vehicular traffic node along the Webuye Highway. Ideal for commercial go-downs, fuel stations, and hardware hubs.</p>
            <span class="badge badge-land">Commercial Parcels</span>
        </div>
        <div class="neighborhood-card">
            <h4>Kipkenyo &amp; Sunset Ridge</h4>
            <p>Scenic, affordable gated community bungalows with ready freehold titles and red soil fertile compounds.</p>
            <span class="badge badge-sale">Starter Homes</span>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions (FAQ) with Structured Data -->
<section class="faq-section reveal-fade" aria-label="Frequently Asked Questions">
    <div class="section-header text-center" style="margin-bottom: 35px;">
        <span class="section-subtitle"><i class="fas fa-question-circle" style="color: var(--accent);"></i> Frequently Asked Questions</span>
        <h2 class="font-serif">Everything You Need to Know About Buying Property in Eldoret</h2>
        <p>Clear, direct answers on title transfers, installment plans, and remote diaspora investments</p>
    </div>

    <div class="faq-accordion-container" id="faqAccordion">
        <div class="faq-item active">
            <button type="button" class="faq-question" aria-expanded="true">
                <span>How does the title deed verification process work with Panda Realty?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <p>Every listed parcel is verified directly at the Ministry of Lands Registry in Eldoret. We provide certified official search certificates confirming genuine ownership, boundary status, and zero encumbrances before any transaction.</p>
            </div>
        </div>

        <div class="faq-item">
            <button type="button" class="faq-question" aria-expanded="false">
                <span>Can I purchase property in installments?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <p>Yes! Most of our prime 50x100 plots and off-plan studio apartments allow an initial deposit of 10% to 20%, with the remaining balance spread flexibly across 12 to 24 monthly installments under a legally binding sale agreement.</p>
            </div>
        </div>

        <div class="faq-item">
            <button type="button" class="faq-question" aria-expanded="false">
                <span>How do you handle purchases for Kenyan Diaspora clients?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <p>We provide full-service diaspora assistance: live WhatsApp video walkthroughs, drone aerial footage, legal contracts sent via secure email/courier, and verified bank escrow settlements for total peace of mind.</p>
            </div>
        </div>

        <div class="faq-item">
            <button type="button" class="faq-question" aria-expanded="false">
                <span>What are the typical rental returns for studio apartments in Eldoret?</span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer">
                <p>Executive studio units in student and hospital hubs like Annex and Pioneer yield between KES 20,000 to KES 35,000 per month, offering an estimated net rental return of 10% to 14% annually.</p>
            </div>
        </div>
    </div>

    <!-- FAQ JSON-LD Schema for Google Rich Snippets -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "How does the title deed verification process work with Panda Realty?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Every listed parcel is verified directly at the Ministry of Lands Registry in Eldoret. We provide certified official search certificates confirming genuine ownership, boundary status, and zero encumbrances before any transaction."
          }
        },
        {
          "@type": "Question",
          "name": "Can I purchase property in installments?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes! Most of our prime 50x100 plots and off-plan studio apartments allow an initial deposit of 10% to 20%, with the remaining balance spread flexibly across 12 to 24 monthly installments under a legally binding sale agreement."
          }
        },
        {
          "@type": "Question",
          "name": "How do you handle purchases for Kenyan Diaspora clients?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "We provide full-service diaspora assistance: live WhatsApp video walkthroughs, drone aerial footage, legal contracts sent via secure email/courier, and verified bank escrow settlements for total peace of mind."
          }
        },
        {
          "@type": "Question",
          "name": "What are the typical rental returns for studio apartments in Eldoret?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Executive studio units in student and hospital hubs like Annex and Pioneer yield between KES 20,000 to KES 35,000 per month, offering an estimated net rental return of 10% to 14% annually."
          }
        }
      ]
    }
    </script>
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
