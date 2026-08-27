<?php
/**
 * Panda Realty - Public Video Library Preview
 * Designed & Developed by TekTrend
 */

$featured_videos = fetch_public_videos(3);
if (empty($featured_videos)) {
    return;
}
?>
<section class="video-library-section reveal-fade">
    <div class="section-header">
        <div>
            <span class="section-eyebrow">Media Library</span>
            <h2 class="font-serif">Watch Property Walkthroughs & Explainers</h2>
            <p>Browse embedded video updates, property tours, and short-form reels from Panda Realty.</p>
        </div>
        <a href="<?= htmlspecialchars(app_path('videos')) ?>" class="btn btn-outline">
            Open Video Page <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="video-library-grid">
        <?php foreach ($featured_videos as $video): ?>
            <article class="video-embed-card">
                <div class="video-embed-frame">
                    <iframe src="<?= htmlspecialchars($video['embed_url']) ?>" title="<?= htmlspecialchars($video['title']) ?>" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
                <div class="video-embed-meta">
                    <span class="video-platform-chip"><?= htmlspecialchars(get_video_platform_label($video['platform'])) ?></span>
                    <strong><?= htmlspecialchars($video['title']) ?></strong>
                    <p><?= htmlspecialchars($video['summary'] ?: 'Featured property media from Panda Realty.') ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
