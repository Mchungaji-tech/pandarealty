<?php
/**
 * Panda Realty - Dedicated Contact Page & Lead Processor
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/config/settings.php';

$success_msg = '';
$error_msg = '';
$followup_whatsapp_url = '';

$conn = get_db_connection();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_GET['action'] ?? 'contact');
    $csrf = clean_input($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $error_msg = "Security session expired. Please refresh the page and try again.";
    } else {
        if ($action === 'book_tour') {
            $client_name  = clean_input($_POST['client_name'] ?? '');
            $client_email = clean_input($_POST['client_email'] ?? '');
            $client_phone = clean_input($_POST['client_phone'] ?? '');
            $visit_date   = clean_input($_POST['visit_date'] ?? '');
            $visit_time   = clean_input($_POST['visit_time'] ?? '10:00:00');
            $property_id  = (int)($_POST['property_id'] ?? 0);
            $booking_type = clean_input($_POST['booking_type'] ?? 'site_visit');
            $consultation_mode = clean_input($_POST['consultation_mode'] ?? 'in_person');
            $preferred_contact = clean_input($_POST['preferred_contact'] ?? 'whatsapp');
            $whatsapp_opt_in = isset($_POST['whatsapp_opt_in']) ? 1 : 0;
            $notes        = clean_input($_POST['notes'] ?? '');

            if (!empty($client_name) && !empty($client_phone) && !empty($visit_date)) {
                $prop_sql = $property_id > 0 ? $property_id : "NULL";
                $booking_type = in_array($booking_type, ['site_visit', 'consultation'], true) ? $booking_type : 'site_visit';
                $consultation_mode = in_array($consultation_mode, ['phone', 'whatsapp', 'zoom', 'in_person'], true) ? $consultation_mode : 'in_person';
                $preferred_contact = in_array($preferred_contact, ['phone', 'whatsapp', 'email'], true) ? $preferred_contact : 'whatsapp';
                $ins_sql = "INSERT INTO site_visits (property_id, client_name, client_email, client_phone, visit_date, visit_time, booking_type, consultation_mode, preferred_contact, whatsapp_opt_in, status, notes) 
                            VALUES ($prop_sql, '$client_name', '$client_email', '$client_phone', '$visit_date', '$visit_time', '$booking_type', '$consultation_mode', '$preferred_contact', $whatsapp_opt_in, 'pending', '$notes')";
                if (mysqli_query($conn, $ins_sql)) {
                    log_security_action('SITE_VISIT_BOOKED', "Tour booked by $client_name ($client_phone)");
                    $followup_whatsapp_url = build_whatsapp_link($whatsapp_number, "Hello Perpetuah, I have just booked a " . str_replace('_', ' ', $booking_type) . " for " . date('F d, Y', strtotime($visit_date)) . " under the name $client_name.");
                    if ($whatsapp_opt_in === 1) {
                        $transport = 'disabled';
                        $confirmation_message = "Hello $client_name, your Panda Realty " . str_replace('_', ' ', $booking_type) . " request for " . date('F d, Y', strtotime($visit_date)) . " has been received. We will confirm the details shortly.";
                        send_whatsapp_message($client_phone, $confirmation_message, $transport);
                    }
                    $success_msg = "Thank you $client_name! Your " . str_replace('_', ' ', $booking_type) . " request has been scheduled for " . date('F d, Y', strtotime($visit_date)) . ". Perpetuah will contact you shortly via " . strtoupper($preferred_contact) . " to confirm details.";
                } else {
                    $error_msg = "Error booking tour. Please contact us directly via phone or WhatsApp.";
                }
            } else {
                $error_msg = "Please fill in your name, phone number, and preferred date.";
            }

        } elseif ($action === 'hire_agent' || $action === 'contact') {
            $name    = clean_input($_POST['name'] ?? '');
            $email   = clean_input($_POST['email'] ?? '');
            $phone   = clean_input($_POST['phone'] ?? '');
            $subject = clean_input($_POST['subject'] ?? 'General Inquiry');
            $budget_range = clean_input($_POST['budget_range'] ?? '');
            $preferred_contact = clean_input($_POST['preferred_contact'] ?? 'whatsapp');
            $whatsapp_opt_in = isset($_POST['whatsapp_opt_in']) ? 1 : 0;
            $message = clean_input($_POST['message'] ?? '');
            $type    = ($action === 'hire_agent') ? 'hire_agent' : 'contact_form';
            $client_stage = $action === 'hire_agent' ? 'consultation' : 'new';
            $follow_up_date = $action === 'hire_agent' ? date('Y-m-d', strtotime('+1 day')) : null;
            $follow_up_sql = $follow_up_date ? "'" . db_escape($follow_up_date) . "'" : "NULL";

            if (!empty($name) && !empty($phone) && !empty($message)) {
                $preferred_contact = in_array($preferred_contact, ['phone', 'whatsapp', 'email'], true) ? $preferred_contact : 'whatsapp';
                $ins_sql = "INSERT INTO inquiries (name, email, phone, subject, budget_range, message, inquiry_type, preferred_contact, whatsapp_opt_in, status, client_stage, follow_up_date) 
                            VALUES ('$name', '$email', '$phone', '$subject', '$budget_range', '$message', '$type', '$preferred_contact', $whatsapp_opt_in, 'new', '$client_stage', $follow_up_sql)";
                if (mysqli_query($conn, $ins_sql)) {
                    log_security_action('INQUIRY_SUBMITTED', "Lead from $name: $subject");
                    $followup_whatsapp_url = build_whatsapp_link($whatsapp_number, "Hello Perpetuah, I have just submitted a request about \"$subject\" under the name $name.");
                    if ($whatsapp_opt_in === 1) {
                        $transport = 'disabled';
                        $confirmation_message = "Hello $name, your Panda Realty request about \"$subject\" has been received. We will follow up with you shortly.";
                        send_whatsapp_message($phone, $confirmation_message, $transport);
                    }
                    $success_msg = "Thank you, $name! Your request has been received and added to our consultation workflow. Perpetuah will follow up with you promptly.";
                } else {
                    $error_msg = "Error sending message. Please reach out to Perpetuah on WhatsApp.";
                }
            } else {
                $error_msg = "Please complete all required fields.";
            }
        }
    }
}

$page_title = "Contact Perpetuah Realtor | Panda Realty Eldoret Office";
$page_description = "Get in touch with Perpetuah Realtor at KVDA Plaza, Eldoret for freehold titled plots in Annex, executive studio apartments in Pioneer, and Elgon View homes.";
$page_keywords = "contact Panda Realty, Perpetuah Realtor phone, real estate office Eldoret, KVDA plaza Eldoret, buy land Eldoret contact";

$site_url = (is_https_request() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . app_path();
$schema_json_ld_extra = [
    [
        "@context" => "https://schema.org",
        "@type" => "ContactPage",
        "name" => "Contact Perpetuah Realtor - Panda Realty",
        "url" => $site_url . '/contact',
        "description" => $page_description
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<div class="contact-container">
    
    <div style="text-align: center; max-width: 800px; margin: 0 auto clamp(30px, 4vw, 50px);">
        <span style="color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 11px; margin-bottom: 8px; display: block;">
            Get in Touch with Eldoret's Property Authority
        </span>
        <h1 class="font-serif" style="font-size: clamp(28px, 4vw, 42px); margin-bottom: 12px;">Let's Find Your Dream Home or Land</h1>
        <p style="color: var(--gray); font-size: clamp(14px, 1.8vw, 16px);">
            Have questions regarding titled plots in Annex, executive studio apartments, or luxury villas in Elgon View? Perpetuah and the Panda Realty team are here to assist.
        </p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1.5px solid #10b981; color: #065f46; padding: 16px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px;">
            <i class="fas fa-check-circle" style="font-size: 20px; flex-shrink: 0;"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
        <?php if ($followup_whatsapp_url !== ''): ?>
            <div style="margin-bottom: 24px;">
                <a href="<?= htmlspecialchars($followup_whatsapp_url) ?>" target="_blank" class="btn btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> Continue On WhatsApp
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1.5px solid #ef4444; color: #991b1b; padding: 16px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 20px; flex-shrink: 0;"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="contact-grid-wrap">
        <!-- Contact Details & Profile -->
        <div>
            <div class="contact-card" style="margin-bottom: 25px;">
                <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 22px;">
                    <img src="<?= htmlspecialchars(normalize_media_url($realtor_image ?? 'assets/images/perpetuah.jpg')) ?>" onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=160'" alt="Perpetuah Realtor" style="width: 70px; height: 70px; border-radius: 50%; border: 3px solid var(--accent); object-fit: cover; flex-shrink: 0;">
                    <div>
                        <h3 class="font-serif" style="font-size: 20px; margin-bottom: 2px;">Perpetuah Chepchirchir</h3>
                        <p style="font-size: 11px; color: var(--accent); font-weight: 700; text-transform: uppercase;">Lead Eldoret Realtor</p>
                    </div>
                </div>

                <ul style="list-style: none; display: flex; flex-direction: column; gap: 18px; font-size: 14px;">
                    <li style="display: flex; align-items: flex-start; gap: 15px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--accent); font-size: 18px; margin-top: 3px;"></i>
                        <div>
                            <strong>Eldoret Head Office</strong><br>
                            <span style="color: var(--gray);"><?= htmlspecialchars($contact_address) ?></span>
                        </div>
                    </li>

                    <li style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-phone-alt" style="color: var(--accent); font-size: 18px;"></i>
                        <div>
                            <strong>Phone / Call:</strong><br>
                            <a href="tel:<?= urlencode($contact_phone_intl) ?>" style="color: var(--primary); font-weight: 600;"><?= htmlspecialchars($contact_phone) ?></a>
                        </div>
                    </li>

                    <li style="display: flex; align-items: center; gap: 15px;">
                        <i class="fab fa-whatsapp" style="color: #25D366; font-size: 20px;"></i>
                        <div>
                            <strong>Direct WhatsApp:</strong><br>
                            <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>" target="_blank" style="color: #25D366; font-weight: 600;">+<?= htmlspecialchars($whatsapp_number) ?></a>
                        </div>
                    </li>

                    <li style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-envelope" style="color: var(--accent); font-size: 18px;"></i>
                        <div>
                            <strong>Email Inquiries:</strong><br>
                            <a href="mailto:<?= htmlspecialchars($contact_email) ?>" style="color: var(--gray);"><?= htmlspecialchars($contact_email) ?></a>
                        </div>
                    </li>
                </ul>

                <div style="margin-top: 30px; display: flex; gap: 12px;">
                    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_number) ?>?text=Hello%20Perpetuah,%20I%20would%20like%20to%20schedule%20a%20meeting%20with%20you%20in%20Eldoret." target="_blank" class="btn btn-whatsapp" style="flex: 1;">
                        <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Interactive Contact Form -->
        <div class="contact-card">
            <h3 class="font-serif" style="font-size: 24px; margin-bottom: 6px;">Send an Inquiry</h3>
            <p style="font-size: 14px; color: var(--gray); margin-bottom: 22px;">
                Fill in the form below and we will respond within 1 business hour.
            </p>

            <form action="<?= htmlspecialchars(app_path('contact?action=contact')) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" placeholder="e.g. Mary Jepkemboi" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Phone Number (WhatsApp) *</label>
                        <input type="tel" name="phone" placeholder="0708 289 852" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="mary@example.com" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Preferred Contact Channel</label>
                        <select name="preferred_contact">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="phone">Phone Call</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Budget Range</label>
                        <input type="text" name="budget_range" placeholder="e.g. KSh 3M - 6M">
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject / Interest Area</label>
                    <select name="subject">
                        <option value="Annex Plots Inquiry">Annex Titled 50x100 Plots</option>
                        <option value="Studio Apartment Purchase/Rent">Studio Apartments in Pioneer / Annex</option>
                        <option value="Elgon View Luxury Home">Elgon View / Kapsoya Residential House</option>
                        <option value="Installment Payment Scheme">Invoicing & Installment Packages</option>
                        <option value="Commercial Land Eldoret">Commercial Parcel Consultation</option>
                        <option value="General Question">General Inquiry</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Your Message *</label>
                    <textarea name="message" rows="5" placeholder="Tell us how we can assist you with Eldoret real estate..." required></textarea>
                </div>

                <label style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px; font-size: 13px; color: var(--gray);">
                    <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                    <span>Allow Panda Realty to send a WhatsApp confirmation and follow-up message.</span>
                </label>

                <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; font-size: 13px;">
                    <i class="fas fa-paper-plane"></i> Send Inquiry to Perpetuah
                </button>
            </form>
        </div>
    </div>

    <!-- Eldoret Map Embed -->
    <div style="border-radius: 12px; overflow: hidden; border: 1px solid var(--border); height: 400px; box-shadow: var(--card-shadow);">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15959.04374351336!2d35.2697801!3d0.5142774!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x178101ae0c7f76eb%3A0x6a2c2069695d6666!2sKVDA%20Plaza%2C%20Eldoret!5e0!3m2!1sen!2ske!4v1700000000000!5m2!1sen!2ske" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
