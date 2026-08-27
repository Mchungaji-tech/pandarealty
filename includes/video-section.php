<?php
/**
 * Panda Realty - Video & Luxury Showcase Component
 * Designed & Developed by TekTrend
 */

$welcome_video_url = get_cms_block('welcome_video_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ');
?>

<!-- Luxury Background Video & Metric Counter Section -->
<section class="video-banner-section">
    <div class="video-bg-container">
        <!-- High-res luxury architectural background loop or image fallback -->
        <img src="https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1800" alt="Eldoret Luxury Real Estate" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.28);">
    </div>

    <div class="video-banner-content reveal-fade">
        <div class="video-play-btn" onclick="openModal('welcomeVideoModal')" title="Watch Video Tour">
            <i class="fas fa-play"></i>
        </div>

        <span style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 12px; margin-bottom: 15px; display: block;">
            Eldoret's Premier Property Vision
        </span>

        <h2 class="video-banner-title font-serif">
            We Don't Just Sell Property — <span class="gold-shimmer">We Change Lives</span>
        </h2>

        <p class="video-banner-text">
            From the serene luxury of Elgon View to the high-yield studio apartments of Annex and Pioneer, Panda Realty delivers unmatched transparency, verified land title deeds, and tailored installment packages.
        </p>

        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-gold" onclick="openModal('scheduleVisitModal')">
                <i class="fas fa-calendar-check"></i> Book a Free Site Tour
            </button>
            <a href="tel:<?= urlencode($contact_phone_intl) ?>" class="btn btn-outline" style="border-color: white; color: white;">
                <i class="fas fa-phone-alt"></i> Call Perpetuah Now
            </a>
        </div>

        <!-- Metric Counter Strip -->
        <div class="stats-strip">
            <div class="stat-item">
                <h3>320+</h3>
                <p>Plots & Homes Sold</p>
            </div>
            <div class="stat-item">
                <h3>100%</h3>
                <p>Title Deed Guarantee</p>
            </div>
            <div class="stat-item">
                <h3>45+</h3>
                <p>Studio Apts Managed</p>
            </div>
            <div class="stat-item">
                <h3>24 Mo</h3>
                <p>Flexible Installments</p>
            </div>
        </div>
    </div>
</section>
