<?php
/**
 * Panda Realty - Property Details Page
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$property_id = (int)($_GET['id'] ?? 0);
$conn = get_db_connection();

// Increment views
@mysqli_query($conn, "UPDATE properties SET views_count = views_count + 1 WHERE id = $property_id");

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

$page_title = $prop['title'] . " in " . $prop['location'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div style="margin-top: 100px; padding: 40px 60px; max-width: 1600px; margin-left: auto; margin-right: auto;">
    <!-- Breadcrumbs -->
    <div style="font-size: 13px; color: var(--gray); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <a href="<?= htmlspecialchars(app_path('index')) ?>">Home</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <a href="<?= htmlspecialchars(app_path('properties')) ?>">Properties</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
        <span style="color: var(--primary); font-weight: 600;"><?= htmlspecialchars($prop['title']) ?></span>
    </div>

    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
        <div>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                <span class="badge badge-sale"><?= strtoupper(htmlspecialchars($prop['category'])) ?></span>
                <?php if ($prop['type'] === 'studio'): ?>
                    <span class="badge badge-studio">Studio Apartment</span>
                <?php elseif ($prop['type'] === 'land'): ?>
                    <span class="badge badge-land">Titled Plot</span>
                <?php endif; ?>
                <?php if ($prop['status'] === 'under_construction'): ?>
                    <span class="badge badge-construction">Under Construction</span>
                <?php endif; ?>
            </div>
            <h1 class="font-serif" style="font-size: 38px; color: var(--primary); line-height: 1.2;"><?= htmlspecialchars($prop['title']) ?></h1>
            <p style="color: var(--gray); font-size: 15px; margin-top: 5px;">
                <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($prop['address']) ?>, <?= htmlspecialchars($prop['location']) ?>
            </p>
        </div>

        <div style="text-align: right;">
            <div style="font-size: 12px; color: var(--gray); text-transform: uppercase; letter-spacing: 1px;">Price</div>
            <div class="property-price" style="font-size: 36px; color: var(--accent);" data-price-kes="<?= (float)$prop['price_kes'] ?>" data-price-period="<?= $prop['category'] === 'rent' ? '/mo' : '' ?>">
                <?= format_price($prop['price_kes']) ?><?= $prop['category'] === 'rent' ? '<span class="period">/mo</span>' : '' ?>
            </div>
        </div>
    </div>

    <!-- Main Image Gallery (Grid with Lightbox trigger) -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 40px; border-radius: 12px; overflow: hidden; height: 500px;">
        <div style="position: relative; cursor: pointer; height: 100%;" onclick="openLightbox(<?= $images_json ?>, 0)">
            <img src="<?= htmlspecialchars($images[0] ?? '') ?>" alt="Main Photo" style="width: 100%; height: 100%; object-fit: cover;">
            <div style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.7); color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px;">
                <i class="fas fa-expand"></i> Click to View Fullscreen Lightbox
            </div>
        </div>

        <div style="display: grid; grid-template-rows: 1fr 1fr; gap: 15px; height: 100%;">
            <?php for ($i = 1; $i <= 2; $i++): 
                $extra_img = $images[$i] ?? $images[0];
            ?>
                <div style="position: relative; cursor: pointer; height: 100%;" onclick="openLightbox(<?= $images_json ?>, <?= $i ?>)">
                    <img src="<?= htmlspecialchars($extra_img) ?>" alt="Property View" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php if ($i === 2 && count($images) > 3): ?>
                        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700;">
                            +<?= count($images) - 3 ?> More Photos
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Main Content & Sidebar Grid -->
    <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 50px;">
        <div>
            <!-- Construction Progress (if applicable) -->
            <?php if ($prop['status'] === 'under_construction'): ?>
                <div style="background: var(--light-gray); border: 1px solid var(--border); border-left: 4px solid var(--accent); border-radius: 8px; padding: 25px; margin-bottom: 35px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="font-size: 16px; font-weight: 700;"><i class="fas fa-hard-hat" style="color: var(--accent);"></i> Construction Progress</h4>
                        <span style="font-weight: 700; color: var(--accent); font-size: 18px;"><?= (int)$prop['construction_progress'] ?>% Complete</span>
                    </div>
                    <div class="progress-track" style="height: 10px; margin-bottom: 12px;">
                        <div class="progress-fill" style="width: <?= (int)$prop['construction_progress'] ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--gray);">
                        <span>Current Stage: <strong><?= htmlspecialchars($prop['construction_stage']) ?></strong></span>
                        <span>Estimated Handover: <strong><?= htmlspecialchars($prop['completion_date'] ?: 'Late 2026') ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Property Description -->
            <div style="margin-bottom: 40px;">
                <h3 class="font-serif" style="font-size: 24px; margin-bottom: 15px;">Property Overview</h3>
                <p style="color: var(--gray); font-size: 16px; line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($prop['description'])) ?>
                </p>
            </div>

            <?php if (!empty($videos)): ?>
                <div style="margin-bottom: 40px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                        <h3 class="font-serif" style="font-size: 24px;">Video Tours</h3>
                        <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="btn btn-outline">
                            <i class="fas fa-film"></i> Browse More Videos
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
                                        <strong><?= htmlspecialchars($prop['title']) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Features & Amenities -->
            <?php if (!empty($features)): ?>
                <div style="margin-bottom: 40px;">
                    <h3 class="font-serif" style="font-size: 24px; margin-bottom: 20px;">Amenities & Features</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                        <?php foreach ($features as $feat): ?>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: var(--light-gray); border-radius: 6px; font-size: 14px; font-weight: 500;">
                                <i class="fas fa-check-circle" style="color: var(--accent);"></i>
                                <?= htmlspecialchars($feat) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Front-End Installment & Mortgage Calculator -->
            <?php if ($prop['category'] === 'sale' && $prop['installment_available']): ?>
                <div class="calculator-box">
                    <h3 class="font-serif"><i class="fas fa-calculator" style="color: var(--accent);"></i> Installment & Financing Calculator</h3>
                    <p style="font-size: 13px; color: var(--gray);">
                        Estimate your deposit and monthly installment schedule tailored for this property.
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
                            <div style="font-size: 12px; color: #cbd5e1; display: flex; justify-content: space-between;">
                                <span>Deposit Required: <strong id="calcDepositVal" style="color: #fff;">-</strong></span>
                                <span>Balance Financed: <strong id="calcBalanceVal" style="color: #fff;">-</strong></span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 20px; display: flex; gap: 12px;">
                        <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20calculated%20an%20installment%20plan%20for%20<?= urlencode($prop['title']) ?>%20and%20would%20like%20to%20apply." target="_blank" class="btn btn-whatsapp" style="flex: 1;">
                            <i class="fab fa-whatsapp"></i> Apply for Installment Plan
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Contact & Booking Form -->
        <div>
            <!-- Perpetuah Realtor Card -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 30px; box-shadow: var(--card-shadow); margin-bottom: 30px;">
                <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120" alt="Perpetuah" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--accent); object-fit: cover;">
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 2px;">Perpetuah Chepchirchir</h4>
                        <p style="font-size: 12px; color: var(--accent); font-weight: 600; text-transform: uppercase;">Lead Eldoret Realtor</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
                    <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="btn btn-outline" style="width: 100%;">
                        <i class="fas fa-phone-alt"></i> Call 0708 289 852
                    </a>
                    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20am%20viewing%20<?= urlencode($prop['title']) ?>%20and%20want%20more%20information." target="_blank" class="btn btn-whatsapp" style="width: 100%;">
                        <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border); margin-bottom: 25px;">

                <!-- Instant Inquiry Form -->
                <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 15px;">Book a Private Site Tour</h4>
                <form action="<?= htmlspecialchars(app_path('contact?action=book_tour')) ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="property_id" value="<?= $prop['id'] ?>">
                    <input type="hidden" name="booking_type" value="site_visit">
                    <input type="hidden" name="consultation_mode" value="in_person">

                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="client_name" placeholder="Full name" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="client_phone" placeholder="0708 289 852" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="client_email" placeholder="you@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label>Tour Date</label>
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

                    <label style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px; font-size: 13px; color: var(--gray);">
                        <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                        <span>Send me a WhatsApp confirmation for this tour.</span>
                    </label>

                    <button type="submit" class="btn btn-gold" style="width: 100%; padding: 12px;">
                        <i class="fas fa-calendar-check"></i> Schedule Free Tour
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
