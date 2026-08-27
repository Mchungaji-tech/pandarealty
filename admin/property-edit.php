<?php
/**
 * Panda Realty - Edit Property Form (3 Image Uploads + 1 Video URL)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_properties');

$conn = get_db_connection();
$id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

$res = mysqli_query($conn, "SELECT * FROM properties WHERE id = $id LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) {
    header("Location: properties.php");
    exit;
}

$p = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        $error = "Security session expired. Please refresh.";
    } else {
        $title = clean_input($_POST['title'] ?? '');
        $type = clean_input($_POST['type'] ?? 'house');
        $category = clean_input($_POST['category'] ?? 'sale');
        $price_kes = (float)($_POST['price_kes'] ?? 0);
        $location = clean_input($_POST['location'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $bedrooms = (int)($_POST['bedrooms'] ?? 0);
        $bathrooms = (int)($_POST['bathrooms'] ?? 0);
        $area_sqft = (int)($_POST['area_sqft'] ?? 0);
        $land_size = clean_input($_POST['land_size'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $status = clean_input($_POST['status'] ?? 'available');
        $progress = (int)($_POST['construction_progress'] ?? 100);
        $stage = clean_input($_POST['construction_stage'] ?? 'Completed');
        $completion_date = clean_input($_POST['completion_date'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_hero_slide = isset($_POST['is_hero_slide']) ? 1 : 0;
        $installment_available = isset($_POST['installment_available']) ? 1 : 0;

        $usd_rate = (float)get_setting('currency_usd_rate', 130.00);
        $price_usd = $usd_rate > 0 ? ($price_kes / $usd_rate) : 0;

        // Current images
        $existing_imgs = get_property_images($p['images']);

        // Process 3 Dedicated Image Inputs
        $fallback_1 = clean_input($_POST['image_url_1'] ?? ($existing_imgs[0] ?? ''));
        $fallback_2 = clean_input($_POST['image_url_2'] ?? ($existing_imgs[1] ?? ''));
        $fallback_3 = clean_input($_POST['image_url_3'] ?? ($existing_imgs[2] ?? ''));

        $img1 = upload_property_image_file('image_file_1', $fallback_1);
        $img2 = upload_property_image_file('image_file_2', $fallback_2);
        $img3 = upload_property_image_file('image_file_3', $fallback_3);

        $images_arr = [];
        if (!empty($img1)) $images_arr[] = $img1;
        if (!empty($img2)) $images_arr[] = $img2;
        if (!empty($img3)) $images_arr[] = $img3;

        if (empty($images_arr)) {
            $images_arr = $existing_imgs;
        }
        $images_json = db_escape(json_encode($images_arr));

        // Process 1 Single Video URL
        $single_video_raw = clean_input($_POST['video_url'] ?? '');
        $video_arr = [];
        if (!empty($single_video_raw)) {
            $embed = normalize_single_property_video($single_video_raw);
            if (!empty($embed)) {
                $video_arr[] = $embed;
            }
        }
        $videos_json = db_escape(json_encode($video_arr));

        // Features
        $features_raw = clean_input($_POST['features'] ?? '');
        $features_arr = [];
        if (!empty($features_raw)) {
            $feats = explode(",", $features_raw);
            foreach ($feats as $f) {
                $f = trim($f);
                if (!empty($f)) $features_arr[] = $f;
            }
        }
        $features_json = db_escape(json_encode($features_arr));

        $sql = "UPDATE properties SET 
                title = '$title',
                type = '$type',
                category = '$category',
                price_kes = $price_kes,
                price_usd = $price_usd,
                location = '$location',
                address = '$address',
                bedrooms = $bedrooms,
                bathrooms = $bathrooms,
                area_sqft = $area_sqft,
                land_size = '$land_size',
                description = '$description',
                features = '$features_json',
                images = '$images_json',
                video_urls = '$videos_json',
                status = '$status',
                construction_progress = $progress,
                construction_stage = '$stage',
                completion_date = '$completion_date',
                is_featured = $is_featured,
                is_hero_slide = $is_hero_slide,
                installment_available = $installment_available
                WHERE id = $id";
        
        if (mysqli_query($conn, $sql)) {
            log_security_action('PROPERTY_UPDATED', "Updated property '$title' (ID #$id)");
            $success = "Property '$title' updated successfully!";
            
            $res_ref = mysqli_query($conn, "SELECT * FROM properties WHERE id = $id LIMIT 1");
            $p = mysqli_fetch_assoc($res_ref);
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}

$imgs_current = get_property_images($p['images']);
$videos_current = get_property_videos($p['video_urls'] ?? '');
$single_video_val = $videos_current[0] ?? '';

$admin_page_title = "Edit Property Listing";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 700; color: var(--admin-text);">Edit Property Listing: <?= htmlspecialchars($p['title']) ?></h3>
        <p style="font-size: 13px; color: var(--admin-muted);">Update photos, video tour link, construction progress, and pricing.</p>
    </div>
    <a href="properties.php" class="btn" style="background: #ffffff; color: var(--admin-text); border: 1px solid var(--admin-border); padding: 10px 18px; border-radius: 6px;">
        <i class="fas fa-arrow-left"></i> Back to Inventory
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form action="property-edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="admin-card">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

    <!-- 1. Property Essentials -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-info-circle"></i> 1. Property Details & Pricing
    </h4>

    <div class="admin-form-group">
        <label>Property Title *</label>
        <input type="text" name="title" value="<?= htmlspecialchars($p['title']) ?>" required>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Property Type *</label>
            <select name="type" required>
                <option value="house" <?= $p['type'] === 'house' ? 'selected' : '' ?>>Residential House / Bungalow</option>
                <option value="studio" <?= $p['type'] === 'studio' ? 'selected' : '' ?>>Modern Studio Apartment</option>
                <option value="villa" <?= $p['type'] === 'villa' ? 'selected' : '' ?>>Luxury Villa / Townhouse</option>
                <option value="apartment" <?= $p['type'] === 'apartment' ? 'selected' : '' ?>>Apartment / Penthouse</option>
                <option value="land" <?= $p['type'] === 'land' ? 'selected' : '' ?>>Titled Plot / Land</option>
                <option value="commercial" <?= $p['type'] === 'commercial' ? 'selected' : '' ?>>Commercial Property</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Listing Purpose *</label>
            <select name="category" required>
                <option value="sale" <?= $p['category'] === 'sale' ? 'selected' : '' ?>>For Sale (Outright & Installments)</option>
                <option value="rent" <?= $p['category'] === 'rent' ? 'selected' : '' ?>>For Rent / Lease</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Price in KES (Kenyan Shillings) *</label>
            <input type="number" step="1000" name="price_kes" value="<?= (float)$p['price_kes'] ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Neighborhood / Location in Eldoret *</label>
            <input type="text" name="location" value="<?= htmlspecialchars($p['location']) ?>" required>
        </div>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Bedrooms</label>
            <input type="number" name="bedrooms" value="<?= (int)$p['bedrooms'] ?>" min="0">
        </div>
        <div class="admin-form-group">
            <label>Bathrooms</label>
            <input type="number" name="bathrooms" value="<?= (int)$p['bathrooms'] ?>" min="0">
        </div>
        <div class="admin-form-group">
            <label>Land Size</label>
            <input type="text" name="land_size" value="<?= htmlspecialchars($p['land_size'] ?? '') ?>">
        </div>
        <div class="admin-form-group">
            <label>Physical Address / Landmark</label>
            <input type="text" name="address" value="<?= htmlspecialchars($p['address']) ?>">
        </div>
    </div>

    <div class="admin-form-group">
        <label>Property Description *</label>
        <textarea name="description" rows="4" required><?= htmlspecialchars($p['description']) ?></textarea>
    </div>

    <!-- 2. 3 Dedicated Image Inputs + 1 Video URL -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px; font-weight: 700;">
        <i class="fas fa-images"></i> 2. Property Visuals (3 Image Uploads + 1 Video URL)
    </h4>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
        <!-- Image 1 -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-camera"></i> Image 1: Main Cover Photo
            </label>
            <?php if (!empty($imgs_current[0])): ?>
                <div style="margin-bottom: 10px; height: 110px; overflow: hidden; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <img src="<?= htmlspecialchars(normalize_media_url($imgs_current[0])) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            <input type="file" name="image_file_1" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <input type="url" name="image_url_1" value="<?= htmlspecialchars($imgs_current[0] ?? '') ?>" placeholder="Image 1 URL">
        </div>

        <!-- Image 2 -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-couch"></i> Image 2: Living / Interior View
            </label>
            <?php if (!empty($imgs_current[1])): ?>
                <div style="margin-bottom: 10px; height: 110px; overflow: hidden; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <img src="<?= htmlspecialchars(normalize_media_url($imgs_current[1])) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            <input type="file" name="image_file_2" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <input type="url" name="image_url_2" value="<?= htmlspecialchars($imgs_current[1] ?? '') ?>" placeholder="Image 2 URL">
        </div>

        <!-- Image 3 -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-map-marked-alt"></i> Image 3: Aerial / Plot Angle
            </label>
            <?php if (!empty($imgs_current[2])): ?>
                <div style="margin-bottom: 10px; height: 110px; overflow: hidden; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <img src="<?= htmlspecialchars(normalize_media_url($imgs_current[2])) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            <?php endif; ?>
            <input type="file" name="image_file_3" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <input type="url" name="image_url_3" value="<?= htmlspecialchars($imgs_current[2] ?? '') ?>" placeholder="Image 3 URL">
        </div>
    </div>

    <!-- Single Video URL Input -->
    <div class="admin-form-group" style="background: #fdfbf7; border: 1px solid rgba(195,154,77,0.3); border-radius: 8px; padding: 20px;">
        <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
            <i class="fab fa-youtube" style="color: #ef4444;"></i> Single Property Video URL (YouTube, TikTok, Reels, Facebook, MP4)
        </label>
        <input type="url" name="video_url" value="<?= htmlspecialchars($single_video_val) ?>" placeholder="e.g. YouTube / TikTok / Reels video link">
    </div>

    <!-- 3. Status & Settings -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px; font-weight: 700;">
        <i class="fas fa-tools"></i> 3. Construction Progress & Installment Financing
    </h4>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Listing Status</label>
            <select name="status">
                <option value="available" <?= $p['status'] === 'available' ? 'selected' : '' ?>>Available for Purchase</option>
                <option value="under_construction" <?= $p['status'] === 'under_construction' ? 'selected' : '' ?>>Under Construction / Ongoing Project</option>
                <option value="reserved" <?= $p['status'] === 'reserved' ? 'selected' : '' ?>>Reserved by Client</option>
                <option value="sold" <?= $p['status'] === 'sold' ? 'selected' : '' ?>>Sold Out</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Construction Progress (0 - 100%)</label>
            <input type="number" name="construction_progress" min="0" max="100" value="<?= (int)$p['construction_progress'] ?>">
        </div>

        <div class="admin-form-group">
            <label>Current Construction Stage</label>
            <input type="text" name="construction_stage" value="<?= htmlspecialchars($p['construction_stage'] ?? 'Completed') ?>">
        </div>

        <div class="admin-form-group">
            <label>Estimated Completion Date</label>
            <input type="text" name="completion_date" value="<?= htmlspecialchars($p['completion_date'] ?? '') ?>">
        </div>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Installment Plans Permitted?</label>
            <select name="installment_available">
                <option value="1" <?= $p['installment_available'] ? 'selected' : '' ?>>Yes (Flexible Installments Allowed)</option>
                <option value="0" <?= !$p['installment_available'] ? 'selected' : '' ?>>No (Outright Lump Sum Only)</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Feature Flags</label>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?>> Featured in Catalog
                </label>
                <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="is_hero_slide" value="1" <?= $p['is_hero_slide'] ? 'checked' : '' ?>> Hero Slider
                </label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 16px 36px; font-size: 14px; font-weight: 700; border-radius: 8px; margin-top: 10px; cursor: pointer;">
        <i class="fas fa-save"></i> Save Changes to Property
    </button>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
