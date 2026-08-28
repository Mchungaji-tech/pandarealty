<?php
/**
 * Panda Realty - Dynamic XML Sitemap Generator
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

// Set XML headers
header('Content-Type: application/xml; charset=utf-8');

$protocol = is_https_request() ? 'https://' : 'http://';
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = rtrim($protocol . $http_host . app_path(), '/');

$conn = get_db_connection();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Static Core Pages -->
    <url>
        <loc><?= htmlspecialchars($base_url . '/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties?filter=studio') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties?filter=land') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties?filter=construction') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties?filter=sale') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/properties?filter=rent') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/videos') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/contact') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/list-property') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/privacy') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    <url>
        <loc><?= htmlspecialchars($base_url . '/terms') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    <!-- Dynamic Property Listing Pages with Image Sitemaps -->
    <?php
    if ($conn) {
        $props_res = @mysqli_query($conn, "SELECT id, title, slug, images, updated_at, created_at FROM properties ORDER BY id DESC");
        if ($props_res) {
            while ($p = mysqli_fetch_assoc($props_res)) {
                $prop_url = $base_url . '/property-details?id=' . (int)$p['id'];
                $lastmod = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : (!empty($p['created_at']) ? date('Y-m-d', strtotime($p['created_at'])) : date('Y-m-d'));
                $images = function_exists('get_property_images') ? get_property_images($p['images']) : [];
                ?>
    <url>
        <loc><?= htmlspecialchars($prop_url) ?></loc>
        <lastmod><?= htmlspecialchars($lastmod) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
        <?php foreach ($images as $img_url): 
            $full_img = preg_match('#^https?://#i', $img_url) ? $img_url : ($base_url . '/' . ltrim($img_url, '/'));
        ?>
        <image:image>
            <image:loc><?= htmlspecialchars($full_img) ?></image:loc>
            <image:title><?= htmlspecialchars($p['title']) ?></image:title>
            <image:caption><?= htmlspecialchars($p['title']) ?> in Eldoret, Kenya</image:caption>
        </image:image>
        <?php endforeach; ?>
    </url>
                <?php
            }
        }
    }
    ?>
</urlset>
