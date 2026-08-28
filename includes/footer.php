<?php
/**
 * Panda Realty - Front-End Common Footer
 * Designed & Developed by TekTrend
 */
?>

<!-- Main Footer -->
<footer class="footer">
    <div class="footer-content">
        <!-- Brand Summary -->
        <div class="footer-brand">
            <?php if (!empty($site_logo)): ?>
                <a href="<?= htmlspecialchars(app_path('index')) ?>">
                    <img src="<?= htmlspecialchars(normalize_media_url($site_logo)) ?>" alt="<?= htmlspecialchars($site_name) ?>" style="max-height: 42px; object-fit: contain; margin-bottom: 12px; display: block;">
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(app_path('index')) ?>" class="logo-main">PANDA <span style="color: var(--accent);">REALTY</span></a>
            <?php endif; ?>
            <p class="footer-text">
                Led by Perpetuah, Panda Realty is Eldoret's premier real estate consultancy. We specialize in verified freehold title deeds, modern studio apartments, executive residential homes, and commercial parcels across Uasin Gishu County.
            </p>
            <div class="footer-socials">
                <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>" target="_blank" class="social-link" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                <a href="https://facebook.com" target="_blank" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://instagram.com" target="_blank" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://tiktok.com" target="_blank" class="social-link" title="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://linkedin.com" target="_blank" class="social-link" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://youtube.com" target="_blank" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <!-- Quick Links -->
        <div>
            <h4 class="footer-title">Explore Properties</h4>
            <ul class="footer-links">
                <li><a href="<?= htmlspecialchars(app_path('properties?filter=studio')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Studio Apartments</a></li>
                <li><a href="<?= htmlspecialchars(app_path('properties?filter=land')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Prime 50x100 Plots</a></li>
                <li><a href="<?= htmlspecialchars(app_path('properties?filter=sale')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Houses for Sale</a></li>
                <li><a href="<?= htmlspecialchars(app_path('properties?filter=construction')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Building Projects</a></li>
                <li><a href="<?= htmlspecialchars(app_path('properties?filter=rent')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Rental Properties</a></li>
                <li><a href="<?= htmlspecialchars(app_path('videos')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Video Library</a></li>
            </ul>
        </div>

        <!-- Resources & Legal -->
        <div>
            <h4 class="footer-title">Company & Legal</h4>
            <ul class="footer-links">
                <li><a href="<?= htmlspecialchars(app_path('contact')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Contact Perpetuah</a></li>
                <li><a href="<?= htmlspecialchars(app_path('privacy')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Privacy Policy</a></li>
                <li><a href="<?= htmlspecialchars(app_path('terms')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Terms & Conditions</a></li>
                <li><a href="<?= htmlspecialchars(app_path('admin/staff-login')) ?>"><i class="fas fa-chevron-right" style="font-size: 10px;"></i> Staff Portal</a></li>
            </ul>
        </div>

        <!-- Direct Contact -->
        <div>
            <h4 class="footer-title">Eldoret Office</h4>
            <ul class="footer-links">
                <li style="color: #cbd5e1;"><i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($contact_address) ?></li>
                <li><a href="tel:<?= urlencode($contact_phone_intl) ?>"><i class="fas fa-phone-alt" style="color: var(--accent);"></i> <?= htmlspecialchars($contact_phone) ?></a></li>
                <li><a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>" target="_blank"><i class="fab fa-whatsapp" style="color: #25D366;"></i> Direct WhatsApp</a></li>
                <li><a href="mailto:<?= htmlspecialchars($contact_email) ?>"><i class="fas fa-envelope" style="color: var(--accent);"></i> <?= htmlspecialchars($contact_email) ?></a></li>
            </ul>
        </div>
    </div>

    <!-- Bottom Copyright & Signature -->
    <div class="footer-bottom">
        <div>
            &copy; <?= date('Y') ?> <strong>Panda Realty</strong>. All Rights Reserved. "We don't just sell property — we change lives."
        </div>
        <div class="developer-signature">
            Designed & Developed by <a href="https://tektrend.co.ke" target="_blank">TekTrend</a>
        </div>
    </div>
</footer>

<!-- Modals & Live Toasts -->
<?php require_once __DIR__ . '/toast-notifications.php'; ?>
<?php require_once __DIR__ . '/promo-modal.php'; ?>
<?php require_once __DIR__ . '/modals.php'; ?>

<!-- Main JS Engine -->
<script src="<?= htmlspecialchars(app_path('assets/js/main.js')) ?>"></script>

</body>
</html>
