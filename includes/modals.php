<?php
/**
 * Panda Realty - Common Interactive Modals
 * Designed & Developed by TekTrend
 */
?>

<!-- 1. Lightbox Gallery Viewer Modal -->
<div class="lightbox-modal" id="lightboxModal">
    <button type="button" class="modal-close" style="color: white; font-size: 32px; top: 30px; right: 40px;" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="lightbox-img-wrap">
        <button type="button" class="lightbox-btn prev" onclick="prevLightboxImg()"><i class="fas fa-chevron-left"></i></button>
        <img src="" id="lightboxImg" class="lightbox-img" alt="Property High-Res Gallery">
        <button type="button" class="lightbox-btn next" onclick="nextLightboxImg()"><i class="fas fa-chevron-right"></i></button>
    </div>

    <div class="lightbox-thumbnails" id="lightboxThumbnails"></div>
</div>

<!-- 2. Welcome Video Modal -->
<div class="modal" id="welcomeVideoModal">
    <div class="modal-content modal-lg" style="background: #000; padding: 15px; border-radius: 12px;">
        <button type="button" class="modal-close" style="color: white;" onclick="closeModal('welcomeVideoModal')">
            <i class="fas fa-times"></i>
        </button>
        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
            <iframe src="<?= htmlspecialchars($welcome_video_url) ?>" data-src="<?= htmlspecialchars($welcome_video_url) ?>" style="position: absolute; top:0; left: 0; width: 100%; height: 100%;" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>
</div>

<!-- 3. Schedule Site Visit / Tour Modal -->
<div class="modal" id="scheduleVisitModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('scheduleVisitModal')">
            <i class="fas fa-times"></i>
        </button>

        <h3 class="modal-title font-serif">Book a Free Site Tour</h3>
        <p style="font-size: 13px; color: var(--gray); margin-bottom: 20px;">
            Experience our prime Eldoret properties and plots in person with Perpetuah Realtor.
        </p>

        <form action="<?= htmlspecialchars(app_path('contact?action=book_tour')) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="booking_type" value="site_visit">
            <input type="hidden" name="consultation_mode" value="in_person">

            <div class="form-group">
                <label>Your Full Name *</label>
                <input type="text" name="client_name" placeholder="e.g. Dr. Evans Kipchumba" required>
            </div>

            <div class="form-group">
                <label>Phone Number (WhatsApp Preferred) *</label>
                <input type="tel" name="client_phone" placeholder="e.g. 0708 289 852" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="client_email" placeholder="e.g. evans@gmail.com" required>
            </div>

            <div class="form-group">
                <label>Preferred Confirmation Channel</label>
                <select name="preferred_contact">
                    <option value="whatsapp">WhatsApp</option>
                    <option value="phone">Phone Call</option>
                    <option value="email">Email</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Preferred Date *</label>
                    <input type="date" name="visit_date" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Preferred Time *</label>
                    <input type="time" name="visit_time" value="10:00" required>
                </div>
            </div>

            <div class="form-group">
                <label>Property or Area of Interest</label>
                <select name="property_id">
                    <option value="">Any Eldoret Property / General Land Tour</option>
                    <?php
                    $p_res = mysqli_query($conn, "SELECT id, title, location FROM properties WHERE status != 'sold' LIMIT 15");
                    if ($p_res) {
                        while ($p = mysqli_fetch_assoc($p_res)) {
                            echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['title']) . ' (' . htmlspecialchars($p['location']) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Special Requests / Transportation Notes</label>
                <textarea name="notes" placeholder="e.g. Need pickup from Eldoret CBD office..."></textarea>
            </div>

            <label style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px; font-size: 13px; color: var(--gray);">
                <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                <span>Send me a WhatsApp confirmation for this booking.</span>
            </label>

            <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px;">
                <i class="fas fa-calendar-check"></i> Confirm Site Visit Booking
            </button>
        </form>
    </div>
</div>

<!-- 4. Hire an Agent Modal -->
<div class="modal" id="hireAgentModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('hireAgentModal')">
            <i class="fas fa-times"></i>
        </button>

        <h3 class="modal-title font-serif">Consult with Perpetuah</h3>
        <p style="font-size: 13px; color: var(--gray); margin-bottom: 20px;">
            Let Eldoret's top real estate expert match you with high-yield investments or your dream home.
        </p>

        <form action="<?= htmlspecialchars(app_path('contact?action=hire_agent')) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="Your name" required>
            </div>

            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" placeholder="0708 289 852" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label>Location Preference in Eldoret</label>
                <input type="text" name="subject" placeholder="e.g. Elgon View, Annex, Pioneer, Kapsoya..." required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
                    <input type="text" name="budget_range" placeholder="e.g. KSh 5M - 8M">
                </div>
            </div>

            <div class="form-group">
                <label>Property Requirements & Budget</label>
                <textarea name="message" placeholder="Describe what you are looking for (e.g. 50x100 plot, Studio apartment, 4-bed villa with 24-month installments)..." required></textarea>
            </div>

            <label style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px; font-size: 13px; color: var(--gray);">
                <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                <span>Send me a WhatsApp confirmation and follow-up message.</span>
            </label>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </form>
    </div>
</div>

<!-- 5. User Sign In Modal -->
<div class="modal" id="loginModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('loginModal')">
            <i class="fas fa-times"></i>
        </button>

        <div style="text-align: center; margin-bottom: 20px;">
            <h3 class="modal-title font-serif" style="margin-bottom: 4px;">Panda Realty Portal</h3>
            <p style="font-size: 13px; color: var(--gray);">Sign in to list properties, schedule site visits, or access controls.</p>
        </div>

        <div style="background: #f8fafc; border: 1px solid var(--border); color: var(--gray); padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 12px;">
            Account sign-in is available now. Social sign-in will be added later through a real OAuth integration.
        </div>

        <form action="<?= htmlspecialchars(app_path('login')) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="auth_action" value="login">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@pandarealty.co.ke" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; margin-top: 10px;">
                <i class="fas fa-sign-in-alt"></i> Sign In to Account
            </button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; font-size: 12px;">
            <a href="<?= htmlspecialchars(app_path('login')) ?>" style="color: var(--accent); font-weight: 600;"><i class="fas fa-user-plus"></i> Create Free Account</a>
            <a href="<?= htmlspecialchars(app_path('admin/staff-login')) ?>" style="color: var(--gray); font-weight: 600;"><i class="fas fa-shield-alt"></i> Staff Portal</a>
        </div>
    </div>
</div>
