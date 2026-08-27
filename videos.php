<?php
/**
 * Panda Realty - Interactive Video Library & Reels Experience
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$conn = get_db_connection();
$view_mode = clean_input($_GET['mode'] ?? 'split'); // 'tiktok' or 'split'
$filter_platform = clean_input($_GET['platform'] ?? 'all');
$selected_video_id = (int)($_GET['id'] ?? 0);

// Fetch properties with video
$res_props = mysqli_query($conn, "SELECT id, title, slug, type, price_kes, location, images, video_urls FROM properties WHERE video_urls IS NOT NULL AND video_urls != '' AND video_urls != '[]'");
$video_items = [];

if ($res_props) {
    while ($p = mysqli_fetch_assoc($res_props)) {
        $vids = get_property_videos($p['video_urls']);
        $imgs = get_property_images($p['images']);
        foreach ($vids as $idx => $v_url) {
            $video_items[] = [
                'id' => (int)$p['id'],
                'video_index' => $idx,
                'title' => $p['title'],
                'slug' => $p['slug'],
                'type' => $p['type'],
                'price_kes' => (float)$p['price_kes'],
                'location' => $p['location'],
                'thumbnail' => $imgs[0] ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200',
                'video_url' => $v_url,
                'platform' => (strpos($v_url, 'tiktok') !== false ? 'tiktok' : (strpos($v_url, 'facebook') !== false ? 'facebook' : (strpos($v_url, 'instagram') !== false ? 'instagram' : 'youtube')))
            ];
        }
    }
}

// Fallback curated Eldoret videos if database list is small
if (count($video_items) < 3) {
    $video_items[] = [
        'id' => 1,
        'video_index' => 0,
        'title' => 'Elgon View Royal Manor VIP Tour',
        'slug' => 'elgon-view-royal-manor',
        'type' => 'mansion',
        'price_kes' => 35000000,
        'location' => 'Elgon View, Eldoret',
        'thumbnail' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'platform' => 'youtube'
    ];
    $video_items[] = [
        'id' => 2,
        'video_index' => 0,
        'title' => 'Pioneer Modern Studio Apartments Walkthrough',
        'slug' => 'pioneer-studio-apartments',
        'type' => 'studio',
        'price_kes' => 2800000,
        'location' => 'Pioneer, Eldoret',
        'thumbnail' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'platform' => 'tiktok'
    ];
    $video_items[] = [
        'id' => 5,
        'video_index' => 0,
        'title' => 'Annex Prime 50x100 Plots Aerial Survey',
        'slug' => 'annex-50x100-plots',
        'type' => 'land',
        'price_kes' => 2200000,
        'location' => 'Annex, Eldoret',
        'thumbnail' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=1200',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'platform' => 'facebook'
    ];
}

// Active video for cinema split mode
$active_video = $video_items[0];
if ($selected_video_id > 0) {
    foreach ($video_items as $vi) {
        if ($vi['id'] === $selected_video_id) {
            $active_video = $vi;
            break;
        }
    }
}

$page_title = "Video Library & TikTok Reels | Panda Realty";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div style="margin-top: 100px; padding: 40px 20px; max-width: 1300px; margin-left: auto; margin-right: auto;">
    
    <!-- Top Mode Switcher Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <span style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 11px; display: block; margin-bottom: 4px;">
                Virtual Property Tours &amp; Reels
            </span>
            <h1 class="font-serif" style="font-size: 32px; margin: 0; color: #0f172a;">Panda Realty Video Experience</h1>
        </div>

        <!-- View Mode Switch Buttons -->
        <div style="display: flex; gap: 10px; background: #ffffff; padding: 6px; border-radius: 30px; border: 1px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
            <a href="videos.php?mode=split&id=<?= $active_video['id'] ?>" class="btn" style="border-radius: 20px; padding: 8px 18px; font-size: 13px; font-weight: 700; <?= $view_mode === 'split' ? 'background: var(--accent); color: #000;' : 'background: transparent; color: var(--gray);' ?>">
                <i class="fas fa-columns"></i> Cinema Split View
            </a>
            <a href="videos.php?mode=tiktok" class="btn" style="border-radius: 20px; padding: 8px 18px; font-size: 13px; font-weight: 700; <?= $view_mode === 'tiktok' ? 'background: #000000; color: #ffffff;' : 'background: transparent; color: var(--gray);' ?>">
                <i class="fab fa-tiktok" style="color: #00f2fe;"></i> TikTok / Reels Feed
            </a>
        </div>
    </div>

    <?php if ($view_mode === 'tiktok'): ?>
        <!-- ========================================== -->
        <!-- MODE 1: TIKTOK / REELS VERTICAL SNAP FEED -->
        <!-- ========================================== -->
        <div style="max-width: 440px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span style="background: #000; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-arrows-alt-v"></i> Scroll or Swipe Vertically to Explore
                </span>
            </div>

            <!-- TikTok Snap Feed Container -->
            <div class="tiktok-feed-container" style="height: 82vh; overflow-y: scroll; scroll-snap-type: y mandatory; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); background: #000;">
                <?php foreach ($video_items as $index => $item): ?>
                    <div class="tiktok-card" style="height: 82vh; scroll-snap-align: start; position: relative; background: #0b0f19; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        
                        <!-- Video Frame -->
                        <iframe src="<?= htmlspecialchars($item['video_url']) ?>" style="width: 100%; height: 100%; border: none;" allow="autoplay; encrypted-media" allowfullscreen></iframe>

                        <!-- Overlay Content on the Left Bottom -->
                        <div style="position: absolute; bottom: 0; left: 0; right: 80px; padding: 30px 20px; background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%); color: #fff; pointer-events: none;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--accent); object-fit: cover;">
                                <span style="font-weight: 700; font-size: 13px;">@perpetuah.realtor</span>
                                <span style="background: var(--accent); color: #000; font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 10px;">VERIFIED</span>
                            </div>
                            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px; line-height: 1.3; color: #fff;"><?= htmlspecialchars($item['title']) ?></h3>
                            <p style="font-size: 12px; color: #cbd5e1; margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($item['location']) ?></p>
                            <div style="font-size: 18px; font-weight: 800; color: var(--accent);" data-price-kes="<?= (float)$item['price_kes'] ?>">
                                <?= format_price((float)$item['price_kes']) ?>
                            </div>
                        </div>

                        <!-- Right Floating Action Icons (TikTok Style) -->
                        <div style="position: absolute; right: 15px; bottom: 40px; display: flex; flex-direction: column; gap: 20px; align-items: center; z-index: 10;">
                            <!-- WhatsApp Inquiry -->
                            <a href="https://wa.me/254708289852?text=Hello%20Perpetuah,%20I%20saw%20the%20video%20for%20<?= urlencode($item['title']) ?>%20and%20I%20am%20interested!" target="_blank" style="background: #25D366; width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; box-shadow: 0 4px 12px rgba(37,211,102,0.4);">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <!-- View Property -->
                            <a href="property-details.php?id=<?= $item['id'] ?>" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px;">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <!-- Like Button -->
                            <button type="button" onclick="this.style.color = this.style.color === 'rgb(239, 68, 68)' ? '#fff' : '#ef4444'" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); width: 46px; height: 46px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; cursor: pointer;">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================================== -->
        <!-- MODE 2: CINEMA SPLIT VIEW (IG / YOUTUBE VIDEO + DETAILS & COMMENTS) -->
        <!-- ============================================================== -->
        <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 30px; align-items: start;">
            
            <!-- Left Side: Main Large Video Player + Property Carousel -->
            <div>
                <div style="background: #000; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15); margin-bottom: 25px;">
                    <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                        <iframe src="<?= htmlspecialchars($active_video['video_url']) ?>" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border: none;" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 12px; padding: 25px; box-shadow: var(--card-shadow); margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="background: rgba(195,154,77,0.12); color: var(--accent); font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                                <?= htmlspecialchars(strtoupper($active_video['type'])) ?>
                            </span>
                            <h2 class="font-serif" style="font-size: 24px; color: #0f172a; margin-top: 6px;"><?= htmlspecialchars($active_video['title']) ?></h2>
                            <p style="color: var(--gray); font-size: 14px;"><i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($active_video['location']) ?></p>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: 800; color: var(--accent);" data-price-kes="<?= (float)$active_video['price_kes'] ?>">
                                <?= format_price((float)$active_video['price_kes']) ?>
                            </div>
                            <span style="font-size: 11px; color: var(--gray);">Ready Title Deed</span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                        <a href="https://wa.me/254708289852?text=Hello%20Perpetuah,%20I%20am%20watching%20the%20video%20for%20<?= urlencode($active_video['title']) ?>%20and%20would%20like%20to%20inquire." target="_blank" class="btn btn-whatsapp" style="padding: 12px 20px;">
                            <i class="fab fa-whatsapp"></i> WhatsApp Perpetuah
                        </a>
                        <a href="property-details.php?id=<?= $active_video['id'] ?>" class="btn btn-primary" style="padding: 12px 20px;">
                            <i class="fas fa-info-circle"></i> Full Property Details
                        </a>
                    </div>
                </div>

                <!-- Video Playlist Selector -->
                <h3 class="font-serif" style="font-size: 20px; margin-bottom: 15px; color: #0f172a;">More Eldoret Property Videos</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach ($video_items as $v): ?>
                        <a href="videos.php?mode=split&id=<?= $v['id'] ?>" style="background: #ffffff; border: 1.5px solid <?= $v['id'] === $active_video['id'] ? 'var(--accent)' : 'var(--border)' ?>; border-radius: 10px; overflow: hidden; text-decoration: none; color: inherit; transition: transform 0.2s; display: block;">
                            <div style="height: 120px; position: relative; background: #000;">
                                <img src="<?= htmlspecialchars(normalize_media_url($v['thumbnail'])) ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; color: var(--accent);">
                                        <i class="fas fa-play" style="font-size: 13px; margin-left: 2px;"></i>
                                    </div>
                                </div>
                            </div>
                            <div style="padding: 10px;">
                                <strong style="font-size: 13px; color: #0f172a; display: block; line-height: 1.3; margin-bottom: 4px;"><?= htmlspecialchars($v['title']) ?></strong>
                                <span style="font-size: 12px; color: var(--accent); font-weight: 700;">KES <?= number_format($v['price_kes']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Side: Property Inquiry Form & Live Comments -->
            <div>
                <!-- Schedule Site Visit / Inquiry Card -->
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 12px; padding: 30px; box-shadow: var(--card-shadow); margin-bottom: 25px;">
                    <h3 class="font-serif" style="font-size: 20px; margin-bottom: 6px; color: #0f172a;">Book a Viewing / Inquire</h3>
                    <p style="font-size: 13px; color: var(--gray); margin-bottom: 20px;">
                        Interested in <strong><?= htmlspecialchars($active_video['title']) ?></strong>? Send a direct message to Perpetuah Realtor.
                    </p>

                    <form action="contact.php?action=book_tour" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="property_id" value="<?= $active_video['id'] ?>">

                        <div class="form-group">
                            <label>Your Full Name *</label>
                            <input type="text" name="client_name" placeholder="Dr. Evans Kipchumba" required>
                        </div>

                        <div class="form-group">
                            <label>Phone (WhatsApp Preferred) *</label>
                            <input type="tel" name="client_phone" placeholder="0708 289 852" required>
                        </div>

                        <div class="form-group">
                            <label>Preferred Visit Date</label>
                            <input type="date" name="visit_date" min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label>Questions / Notes</label>
                            <textarea name="notes" rows="3" placeholder="e.g. Inquiring about 12-month installment options..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; font-weight: 700;">
                            <i class="fas fa-paper-plane"></i> Send Inquiry to Perpetuah
                        </button>
                    </form>
                </div>

                <!-- Verified Realtor Contact Box -->
                <div style="background: linear-gradient(135deg, #0f172a, #1e293b); color: #fff; border-radius: 12px; padding: 25px; box-shadow: var(--card-shadow);">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120" style="width: 54px; height: 54px; border-radius: 50%; border: 2px solid var(--accent); object-fit: cover;">
                        <div>
                            <h4 style="font-size: 16px; margin: 0; color: #fff;">Perpetuah Chepchirchir</h4>
                            <span style="font-size: 12px; color: var(--accent);">Eldoret Property Expert 🔑</span>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-bottom: 15px;">
                        "We don't just sell property — we change lives. Call or message me directly for titled plots and high-yield studio apartments in Eldoret."
                    </p>
                    <a href="tel:0708289852" style="color: var(--accent); font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-phone-alt"></i> 0708 289 852
                    </a>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
