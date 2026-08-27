<?php
/**
 * Panda Realty - Live Activity Toast Notifications Component
 * Designed & Developed by TekTrend
 */
?>

<?php
$public_tips = fetch_public_property_tips(6);
$toast_items = [];

foreach ($public_tips as $tip) {
    $cta_url = trim((string)($tip['cta_url'] ?? ''));
    if ($cta_url === '' && !empty($tip['linked_property_id'])) {
        $cta_url = app_path('property-details?id=' . (int)$tip['linked_property_id']);
    } elseif ($cta_url !== '' && !preg_match('#^(https?:)?//#i', $cta_url) && strpos($cta_url, '/') !== 0) {
        $cta_url = app_path($cta_url);
    }

    $toast_items[] = [
        'icon' => trim((string)($tip['icon_class'] ?? 'fas fa-lightbulb')),
        'title' => trim((string)($tip['title'] ?? 'Property Tip')),
        'desc' => trim((string)($tip['message'] ?? '')),
        'cta_label' => trim((string)($tip['cta_label'] ?? '')),
        'cta_url' => $cta_url
    ];
}
?>
<!-- Real-time Live Notifications Container -->
<div class="toast-container" id="toastContainer" aria-live="polite" data-events='<?= htmlspecialchars(json_encode($toast_items), ENT_QUOTES, 'UTF-8') ?>'></div>
