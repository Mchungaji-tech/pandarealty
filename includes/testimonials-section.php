<?php
/**
 * Panda Realty - Public Testimonials Section
 * Designed & Developed by TekTrend
 */

$testimonials = fetch_public_testimonials(6);
if (empty($testimonials)) {
    return;
}
?>
<section class="testimonials-section reveal-fade">
    <div class="section-header">
        <div>
            <span class="section-eyebrow">Client Confidence</span>
            <h2 class="font-serif">What Clients Say About Panda Realty</h2>
            <p>Real feedback from consultation, booking, land purchase, and investment clients.</p>
        </div>
        <a href="<?= htmlspecialchars(app_path('contact')) ?>" class="btn btn-outline">
            Request Consultation <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="testimonials-grid">
        <?php foreach ($testimonials as $testimonial): ?>
            <article class="testimonial-card">
                <div class="testimonial-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="opacity: <?= $i <= (int)$testimonial['rating'] ? '1' : '0.2' ?>;"></i>
                    <?php endfor; ?>
                </div>
                <p class="testimonial-quote">"<?= htmlspecialchars($testimonial['quote_text']) ?>"</p>
                <div class="testimonial-author">
                    <img src="<?= htmlspecialchars($testimonial['avatar_url'] ?: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200') ?>" alt="<?= htmlspecialchars($testimonial['client_name']) ?>">
                    <div>
                        <strong><?= htmlspecialchars($testimonial['client_name']) ?></strong>
                        <span><?= htmlspecialchars(trim(($testimonial['client_role'] ?: 'Client') . ($testimonial['client_location'] ? ' • ' . $testimonial['client_location'] : ''))) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
