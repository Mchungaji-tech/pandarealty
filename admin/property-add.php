<?php
/**
 * Panda Realty - Add New Property Form (3 Image Uploads + 1 Video URL)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_properties');

$conn = get_db_connection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        $error = "Security session expired. Please try again.";
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
        $min_deposit_percent = (int)($_POST['min_deposit_percent'] ?? 10);
        $max_installment_months = (int)($_POST['max_installment_months'] ?? 24);

        // Calculate USD price based on current rate
        $usd_rate = (float)get_setting('currency_usd_rate', 130.00);
        $price_usd = $usd_rate > 0 ? ($price_kes / $usd_rate) : 0;

        // Process 3 Dedicated Image Inputs (File Upload or URL)
        $img1 = upload_property_image_file('image_file_1', clean_input($_POST['image_url_1'] ?? ''));
        $img2 = upload_property_image_file('image_file_2', clean_input($_POST['image_url_2'] ?? ''));
        $img3 = upload_property_image_file('image_file_3', clean_input($_POST['image_url_3'] ?? ''));

        $images_arr = [];
        if (!empty($img1)) $images_arr[] = $img1;
        if (!empty($img2)) $images_arr[] = $img2;
        if (!empty($img3)) $images_arr[] = $img3;

        if (empty($images_arr)) {
            $images_arr = ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200'];
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

        // Process Features
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

        // Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . time();

        if (empty($title) || $price_kes <= 0 || empty($location)) {
            $error = "Please fill in Title, Asking Price in KES, and Location.";
        } else {
            $sql = "INSERT INTO properties (title, slug, type, category, price_kes, price_usd, location, address, bedrooms, bathrooms, area_sqft, land_size, description, features, images, video_urls, status, construction_progress, construction_stage, completion_date, is_featured, is_hero_slide, installment_available, min_deposit_percent, max_installment_months) 
                    VALUES ('$title', '$slug', '$type', '$category', $price_kes, $price_usd, '$location', '$address', $bedrooms, $bathrooms, $area_sqft, '$land_size', '$description', '$features_json', '$images_json', '$videos_json', '$status', $progress, '$stage', '$completion_date', $is_featured, $is_hero_slide, $installment_available, $min_deposit_percent, $max_installment_months)";
            
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                log_security_action('PROPERTY_ADDED', "Added new property '$title' (ID #$new_id)");
                $success = "Property '$title' created successfully with 3 images & video!";
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

$admin_page_title = "Add New Property Listing";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 22px; font-weight: 700; color: var(--admin-text);">Add New Real Estate Listing</h3>
        <p style="font-size: 13px; color: var(--admin-muted);">Upload up to 3 high-definition photos and attach 1 property tour video.</p>
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

<form action="property-add.php" method="POST" enctype="multipart/form-data" class="admin-card">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

    <!-- 1. Property Essentials -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-info-circle"></i> 1. Property Details & Pricing
    </h4>

    <div class="admin-form-group">
        <label>Property Title *</label>
        <input type="text" name="title" placeholder="e.g. Luxury 4-Bedroom Villa in Elgon View" required>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Property Type *</label>
            <select name="type" required>
                <option value="house">Residential House / Bungalow</option>
                <option value="studio">Modern Studio Apartment</option>
                <option value="villa">Luxury Villa / Townhouse</option>
                <option value="apartment">Apartment / Penthouse</option>
                <option value="land">Titled Plot / Land</option>
                <option value="commercial">Commercial Property</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Listing Purpose *</label>
            <select name="category" required>
                <option value="sale" selected>For Sale (Outright & Installments)</option>
                <option value="rent">For Rent / Lease</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Price in KES (Kenyan Shillings) *</label>
            <input type="number" step="1000" name="price_kes" placeholder="e.g. 18500000" required>
            <span style="font-size: 11px; color: var(--admin-muted);">USD price is auto-calculated using the global exchange rate.</span>
        </div>

        <div class="admin-form-group">
            <label>Neighborhood / Location in Eldoret *</label>
            <input type="text" name="location" placeholder="e.g. Elgon View, Annex, Pioneer, Kapsoya" required>
        </div>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Bedrooms</label>
            <input type="number" name="bedrooms" value="0" min="0">
        </div>
        <div class="admin-form-group">
            <label>Bathrooms</label>
            <input type="number" name="bathrooms" value="0" min="0">
        </div>
        <div class="admin-form-group">
            <label>Land Size (e.g. 50x100, 1/4 Acre)</label>
            <input type="text" name="land_size" placeholder="50 x 100 ft">
        </div>
        <div class="admin-form-group">
            <label>Physical Address / Landmark</label>
            <input type="text" name="address" placeholder="e.g. Off Nairobi Road, Annex">
        </div>
    </div>

    <div class="admin-form-group">
        <label>Property Description *</label>
        <textarea name="description" rows="4" placeholder="Highlight key features, neighborhood serenity, ready title deed status, water and power connectivity..." required></textarea>
    </div>

    <!-- 2. 3 Dedicated Image Inputs + 1 Video URL -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px; font-weight: 700;">
        <i class="fas fa-images"></i> 2. Property Visuals (3 Image Uploads + 1 Video URL)
    </h4>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
        <!-- Image 1: Cover -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-camera"></i> Image 1: Main Cover Photo *
            </label>
            <input type="file" name="image_file_1" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <span style="font-size: 11px; color: var(--admin-muted); display: block; margin-bottom: 6px;">Or enter Image 1 URL fallback:</span>
            <input type="url" name="image_url_1" placeholder="https://images.unsplash.com/...">
        </div>

        <!-- Image 2: Interior -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-couch"></i> Image 2: Living / Interior View
            </label>
            <input type="file" name="image_file_2" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <span style="font-size: 11px; color: var(--admin-muted); display: block; margin-bottom: 6px;">Or enter Image 2 URL fallback:</span>
            <input type="url" name="image_url_2" placeholder="https://images.unsplash.com/...">
        </div>

        <!-- Image 3: Aerial / Plot Angle -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--admin-border); border-radius: 8px; padding: 20px;">
            <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
                <i class="fas fa-map-marked-alt"></i> Image 3: Aerial / Compound / Plot View
            </label>
            <input type="file" name="image_file_3" accept="image/*" style="margin-bottom: 10px; width: 100%;">
            <span style="font-size: 11px; color: var(--admin-muted); display: block; margin-bottom: 6px;">Or enter Image 3 URL fallback:</span>
            <input type="url" name="image_url_3" placeholder="https://images.unsplash.com/...">
        </div>
    </div>

    <!-- 1 Video URL Input -->
    <div class="admin-form-group" style="background: #fdfbf7; border: 1px solid rgba(195,154,77,0.3); border-radius: 8px; padding: 20px;">
        <label style="font-weight: 700; color: var(--admin-text); display: block; margin-bottom: 8px;">
            <i class="fab fa-youtube" style="color: #ef4444;"></i> Single Property Video URL (YouTube, TikTok, Reels, Facebook, MP4)
        </label>
        <input type="url" name="video_url" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ or TikTok / Reels link">
        <span style="font-size: 12px; color: var(--admin-muted); margin-top: 4px; display: block;">
            This video will be embedded in the Property Details page, TikTok Video Feed, and CRM Deal Inspector.
        </span>
    </div>

    <!-- 3. Construction & Installment Settings -->
    <h4 style="font-size: 16px; color: var(--admin-accent); margin: 30px 0 20px; font-weight: 700;">
        <i class="fas fa-tools"></i> 3. Construction Progress & Installment Financing
    </h4>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Listing Status</label>
            <select name="status">
                <option value="available" selected>Available for Purchase</option>
                <option value="under_construction">Under Construction / Ongoing Project</option>
                <option value="reserved">Reserved by Client</option>
                <option value="sold">Sold Out</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Construction Progress (0 - 100%)</label>
            <input type="number" name="construction_progress" min="0" max="100" value="100">
        </div>

        <div class="admin-form-group">
            <label>Current Construction Stage</label>
            <input type="text" name="construction_stage" value="Completed" placeholder="e.g. Foundation, Roofing, Finishing, Completed">
        </div>

        <div class="admin-form-group">
            <label>Estimated Completion Date</label>
            <input type="text" name="completion_date" placeholder="e.g. Q4 2026 / Ready Now">
        </div>
    </div>

    <div class="admin-form-grid">
        <div class="admin-form-group">
            <label>Installment Plans Permitted?</label>
            <select name="installment_available">
                <option value="1" selected>Yes (Flexible Installments Allowed)</option>
                <option value="0">No (Outright Lump Sum Only)</option>
            </select>
        </div>

        <div class="admin-form-group">
            <label>Minimum Down Payment Deposit (%)</label>
            <input type="number" name="min_deposit_percent" value="10" min="5" max="50">
        </div>

        <div class="admin-form-group">
            <label>Maximum Installment Duration (Months)</label>
            <input type="number" name="max_installment_months" value="24" min="1" max="60">
        </div>

        <div class="admin-form-group">
            <label>Feature Flags</label>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1"> Featured in Catalog
                </label>
                <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="is_hero_slide" value="1"> Hero Slider
                </label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 16px 36px; font-size: 14px; font-weight: 700; border-radius: 8px; margin-top: 10px; cursor: pointer;">
        <i class="fas fa-plus-circle"></i> Save & Publish Property Listing
    </button>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
