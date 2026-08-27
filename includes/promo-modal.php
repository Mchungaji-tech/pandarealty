<?php
/**
 * Panda Realty - Promotional & Advertisement Modal
 * Designed & Developed by TekTrend
 */

$promo_title = get_cms_block('promo_banner_title', 'Special Launch: Eldoret Annex Prime 50x100 Plots & Studio Apartments');
$promo_text  = get_cms_block('promo_banner_text', 'Ready title deeds with flexible installment plans up to 24 months! Deposit only 10% today and secure your high-return property in Eldoret.');
$promo_active = get_cms_block('promo_banner_active', '1') === '1';
$promo_scroll_percent = max(5, min(95, (int)get_cms_block('promo_scroll_percent', '35')));
$featured_advert_property = fetch_featured_advert_property();

if ($featured_advert_property) {
    $promo_title = $featured_advert_property['title'];
    $promo_text = 'Featured now in ' . $featured_advert_property['location'] . ' for ' . format_price($featured_advert_property['price_kes']) . '. ' . substr(strip_tags($featured_advert_property['description']), 0, 140) . (strlen(strip_tags($featured_advert_property['description'])) > 140 ? '...' : '');
    $promo_primary_url = app_path('property-details?id=' . (int)$featured_advert_property['id']);
    $promo_primary_label = 'View Featured Property';
    $promo_whatsapp_message = 'Hello Perpetuah, I saw the featured advert for ' . $featured_advert_property['title'] . ' and want more details.';
} else {
    $promo_primary_url = app_path('properties');
    $promo_primary_label = 'Browse Featured Listings';
    $promo_whatsapp_message = 'Hello Perpetuah, I saw the special Panda Realty offer and want more details.';
}

if ($promo_active):
?>
<!-- Promotional Ad Modal -->
<div class="modal" id="promoModal" data-scroll-trigger="<?= (int)$promo_scroll_percent ?>">
    <div class="promo-modal-box">
        <button type="button" class="modal-close" style="color: #94a3b8;" onclick="closePromoModal()">
            <i class="fas fa-times"></i>
        </button>

        <span class="promo-badge"><i class="fas fa-fire"></i> Limited Time Offer</span>
        
        <h2 class="font-serif"><?= htmlspecialchars($promo_title) ?></h2>
        
        <p><?= $promo_text ?></p>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="<?= htmlspecialchars($promo_primary_url) ?>" class="btn btn-gold">
                <i class="fas fa-arrow-right"></i> <?= htmlspecialchars($promo_primary_label) ?>
            </a>
            <a href="<?= htmlspecialchars(build_whatsapp_link($whatsapp_number, $promo_whatsapp_message)) ?>" target="_blank" class="btn btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Claim Offer via WhatsApp
            </a>
            <button type="button" class="btn btn-outline" style="border-color: #64748b; color: #fff;" onclick="closePromoModal()">
                Maybe Later
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
