<?php
/**
 * Panda Realty - Client Property Listing Portal (3 Images + 1 Video)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$conn = get_db_connection();
$error = '';
$success = '';

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_property'])) {
    if (!is_logged_in()) {
        $error = "Please sign in or register below to list your property.";
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
        $csrf = clean_input($_POST['csrf_token'] ?? '');

        if (!verify_csrf_token($csrf)) {
            $error = "Session expired. Please try again.";
        } elseif (empty($title) || $price_kes <= 0 || empty($location) || empty($description)) {
            $error = "Please fill in all required fields (Title, Asking Price, Location, Description).";
        } else {
            $usd_rate = (float)get_setting('currency_usd_rate', 130.00);
            $price_usd = $usd_rate > 0 ? ($price_kes / $usd_rate) : 0;

            // Process 3 Dedicated Image Inputs
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

            // Single Video URL
            $single_video_raw = clean_input($_POST['video_url'] ?? '');
            $video_arr = [];
            if (!empty($single_video_raw)) {
                $embed = normalize_single_property_video($single_video_raw);
                if (!empty($embed)) {
                    $video_arr[] = $embed;
                }
            }
            $videos_json = db_escape(json_encode($video_arr));

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . time();

            $sql = "INSERT INTO properties (title, slug, type, category, price_kes, price_usd, location, address, bedrooms, bathrooms, area_sqft, land_size, description, images, video_urls, status, construction_progress, is_featured) 
                    VALUES ('$title', '$slug', '$type', '$category', $price_kes, $price_usd, '$location', '$address', $bedrooms, $bathrooms, $area_sqft, '$land_size', '$description', '$images_json', '$videos_json', 'available', 100, 0)";
            
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                log_security_action('CLIENT_PROPERTY_LISTED', "Client {$_SESSION['user_name']} listed property '$title' (ID #$new_id)");
                $success = "Congratulations! Your property '$title' has been submitted and published on Panda Realty!";
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

$page_title = "List Your Property with Perpetuah Realtor | Panda Realty Eldoret";
$page_description = "Showcase your plots, studio apartments, residential homes, or commercial real estate to thousands of verified property buyers in Eldoret, Kenya.";
$page_keywords = "list property Eldoret, sell plot Eldoret, rent studio apartment Eldoret, real estate agent Eldoret";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="list-property-container">
    
    <div style="text-align: center; margin-bottom: clamp(25px, 4vw, 40px);">
        <span style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 11px; margin-bottom: 8px; display: block;">
            Reach Thousands of Verified Eldoret Buyers
        </span>
        <h1 class="font-serif" style="font-size: clamp(26px, 4vw, 38px); margin-bottom: 8px;">Apply to List Your Property</h1>
        <p style="color: var(--gray); font-size: clamp(13.5px, 1.8vw, 15px); max-width: 650px; margin: 0 auto;">
            Showcase your plots, studio apartments, residential homes, or commercial parcels with Perpetuah Realtor.
        </p>
    </div>

    <?php if (!empty($success)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1.5px solid #10b981; color: #065f46; padding: 18px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px;">
            <i class="fas fa-check-circle" style="font-size: 24px; flex-shrink: 0;"></i>
            <div>
                <strong><?= htmlspecialchars($success) ?></strong><br>
                <a href="<?= htmlspecialchars(app_path('properties')) ?>" style="color: var(--accent); font-weight: 600;">View in Property Catalog &rarr;</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1.5px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 20px; flex-shrink: 0;"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!is_logged_in()): ?>
        <!-- Sign In or Register to List Property Box -->
        <div class="auth-card" style="margin: 0 auto 30px; text-align: center; max-width: 560px;">
            <i class="fas fa-user-lock" style="font-size: 40px; color: var(--accent); margin-bottom: 12px;"></i>
            <h3 class="font-serif" style="font-size: 22px; margin-bottom: 8px;">Please Sign In to Submit Your Listing</h3>
            <p style="color: var(--gray); font-size: 13.5px; margin-bottom: 22px;">
                Create a free client account or log in to list and manage your properties.
            </p>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?= htmlspecialchars(app_path('login?redirect=list-property')) ?>" class="btn btn-gold" style="padding: 11px 24px;">
                    <i class="fas fa-sign-in-alt"></i> Sign In to Account
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Client Listing Form -->
        <form action="<?= htmlspecialchars(app_path('list-property')) ?>" method="POST" enctype="multipart/form-data" class="contact-card">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="submit_property" value="1">

            <h3 class="font-serif" style="font-size: 19px; color: var(--accent); margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                <i class="fas fa-home"></i> 1. Property Details
            </h3>
            
            <div class="form-group">
                <label>Property Title *</label>
                <input type="text" name="title" placeholder="e.g. Executive 1-Bedroom Studio Apartment in Pioneer Estate" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Property Type *</label>
                    <select name="type" required>
                        <option value="studio">Modern Studio Apartment</option>
                        <option value="house" selected>Residential House / Bungalow</option>
                        <option value="villa">Luxury Villa / Townhouse</option>
                        <option value="apartment">Apartment / Penthouse</option>
                        <option value="land">Titled Land / 50x100 Plot</option>
                        <option value="commercial">Commercial Real Estate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Purpose *</label>
                    <select name="category" required>
                        <option value="sale" selected>For Sale</option>
                        <option value="rent">For Rent / Lease</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Asking Price in KSh (KES) *</label>
                    <input type="number" step="1000" name="price_kes" placeholder="e.g. 2500000" required>
                </div>

                <div class="form-group">
                    <label>Location in Eldoret *</label>
                    <input type="text" name="location" placeholder="e.g. Annex, Elgon View, Pioneer, Kapsoya" required>
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label>Bedrooms</label>
                    <input type="number" name="bedrooms" value="1" min="0">
                </div>
                <div class="form-group">
                    <label>Bathrooms</label>
                    <input type="number" name="bathrooms" value="1" min="0">
                </div>
                <div class="form-group">
                    <label>Land Size (if Land/Plot)</label>
                    <input type="text" name="land_size" placeholder="e.g. 50 x 100 ft">
                </div>
            </div>

            <div class="form-group">
                <label>Specific Address / Landmark</label>
                <input type="text" name="address" placeholder="e.g. 500m from Eldoret-Nakuru Highway, Annex Scheme">
            </div>

            <h3 class="font-serif" style="font-size: 19px; color: var(--accent); margin: 25px 0 18px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                <i class="fas fa-images"></i> 2. Property Visuals (Upload Up to 3 Photos + 1 Video)
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 14px; margin-bottom: 18px;">
                <!-- Image 1 -->
                <div style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: 8px; padding: 14px;">
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px; font-size: 12px;">
                        Image 1: Cover Photo *
                    </label>
                    <input type="file" name="image_file_1" accept="image/*" style="margin-bottom: 6px; width: 100%; font-size: 12px;">
                    <input type="url" name="image_url_1" placeholder="Or paste Image 1 URL" style="font-size: 12px;">
                </div>

                <!-- Image 2 -->
                <div style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: 8px; padding: 14px;">
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px; font-size: 12px;">
                        Image 2: Living / Interior
                    </label>
                    <input type="file" name="image_file_2" accept="image/*" style="margin-bottom: 6px; width: 100%; font-size: 12px;">
                    <input type="url" name="image_url_2" placeholder="Or paste Image 2 URL" style="font-size: 12px;">
                </div>

                <!-- Image 3 -->
                <div style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: 8px; padding: 14px;">
                    <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px; font-size: 12px;">
                        Image 3: Compound / Plot
                    </label>
                    <input type="file" name="image_file_3" accept="image/*" style="margin-bottom: 6px; width: 100%; font-size: 12px;">
                    <input type="url" name="image_url_3" placeholder="Or paste Image 3 URL" style="font-size: 12px;">
                </div>
            </div>

            <!-- Single Video URL -->
            <div class="form-group" style="background: #fdfbf7; border: 1px solid rgba(195,154,77,0.3); border-radius: 8px; padding: 14px;">
                <label style="font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px; font-size: 12px;">
                    <i class="fab fa-youtube" style="color: #ef4444;"></i> Single Property Video Tour Link
                </label>
                <input type="url" name="video_url" placeholder="e.g. YouTube, TikTok, Reels, Facebook, or MP4 link">
            </div>

            <div class="form-group">
                <label>Detailed Property Description *</label>
                <textarea name="description" rows="4" placeholder="Describe title deed status, water & electricity connectivity, security, nearby schools, and amenities..." required></textarea>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; font-size: 13.5px; font-weight: 700; margin-top: 10px;">
                <i class="fas fa-check-circle"></i> Submit Property Listing Application
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
