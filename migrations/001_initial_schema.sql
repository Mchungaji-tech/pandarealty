-- Panda Realty Database Schema
-- Designed & Developed by TekTrend
-- Note: In cPanel phpMyAdmin, select your created database (e.g. tektxbzg_pandarealty) and import this file directly.

-- 1. Users Table (Legacy shared table — still used for clients & portal admins; separate role tables below)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'developer', 'ceo', 'admin', 'staff', 'client') DEFAULT 'client',
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `two_factor_secret` VARCHAR(64) NULL,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- SEPARATE ROLE TABLES (Schema Phase 3 — for standalone auth)
-- Super Admin, CEO, Staff each have their own table so auth
-- and session namespace. These are AUTHORITATIVE for standalone
-- portals (see admin/*-login.php, admin/*-register.php).
-- =======================================================

-- 1a. Super Administrators Table (Separate table + own session)
CREATE TABLE IF NOT EXISTS `super_admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `two_factor_secret` VARCHAR(64) NULL,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1b. CEO Users Table (Separate table + own session)
CREATE TABLE IF NOT EXISTS `ceo_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `two_factor_secret` VARCHAR(64) NULL,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1c. Staff Members Table (Separate table + own session)
CREATE TABLE IF NOT EXISTS `staff_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `department` VARCHAR(80) NULL,
    `position_title` VARCHAR(120) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `two_factor_secret` VARCHAR(64) NULL,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Properties Table
CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `type` ENUM('house', 'apartment', 'studio', 'villa', 'penthouse', 'mansion', 'land', 'commercial') NOT NULL,
    `category` ENUM('sale', 'rent') NOT NULL,
    `price_kes` DECIMAL(15,2) NOT NULL,
    `price_usd` DECIMAL(15,2) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `bedrooms` INT DEFAULT 0,
    `bathrooms` INT DEFAULT 0,
    `area_sqft` INT DEFAULT 0,
    `land_size` VARCHAR(100) NULL, -- e.g. 50x100, 1/4 Acre, 1 Acre
    `description` TEXT NOT NULL,
    `features` TEXT NULL, -- JSON array of features
    `images` TEXT NOT NULL, -- JSON array of image URLs
    `video_urls` TEXT NULL, -- JSON array of embedded video URLs
    `status` ENUM('available', 'under_construction', 'sold', 'reserved') DEFAULT 'available',
    `construction_progress` INT DEFAULT 100, -- 0 to 100%
    `construction_stage` VARCHAR(100) DEFAULT 'Completed', -- Foundation, Framing, Roofing, Finishing, Completed
    `completion_date` VARCHAR(100) NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_hero_slide` TINYINT(1) DEFAULT 0,
    `installment_available` TINYINT(1) DEFAULT 1,
    `min_deposit_percent` INT DEFAULT 10,
    `max_installment_months` INT DEFAULT 24,
    `views_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Inquiries & Contact Leads Table
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `subject` VARCHAR(200) DEFAULT 'General Inquiry',
    `budget_range` VARCHAR(120) NULL,
    `message` TEXT NOT NULL,
    `inquiry_type` ENUM('contact_form', 'property_inquiry', 'hire_agent', 'installment_quote') DEFAULT 'contact_form',
    `preferred_contact` ENUM('phone', 'whatsapp', 'email') DEFAULT 'whatsapp',
    `whatsapp_opt_in` TINYINT(1) DEFAULT 1,
    `status` ENUM('new', 'in_progress', 'resolved', 'archived') DEFAULT 'new',
    `client_stage` ENUM('new', 'qualified', 'consultation', 'site_visit', 'negotiation', 'won', 'lost') DEFAULT 'new',
    `follow_up_date` DATE NULL,
    `assigned_to` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Site Visits & Bookings Table (Calendar)
CREATE TABLE IF NOT EXISTS `site_visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT NULL,
    `client_name` VARCHAR(100) NOT NULL,
    `client_email` VARCHAR(150) NOT NULL,
    `client_phone` VARCHAR(30) NOT NULL,
    `visit_date` DATE NOT NULL,
    `visit_time` TIME NOT NULL,
    `booking_type` ENUM('site_visit', 'consultation') DEFAULT 'site_visit',
    `consultation_mode` ENUM('phone', 'whatsapp', 'zoom', 'in_person') DEFAULT 'in_person',
    `preferred_contact` ENUM('phone', 'whatsapp', 'email') DEFAULT 'whatsapp',
    `whatsapp_opt_in` TINYINT(1) DEFAULT 1,
    `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT NULL,
    `follow_up_date` DATE NULL,
    `assigned_to` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tasks & Reminders Table
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Invoices & Installments Table
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `property_id` INT NULL,
    `client_name` VARCHAR(100) NOT NULL,
    `client_email` VARCHAR(150) NOT NULL,
    `client_phone` VARCHAR(30) NOT NULL,
    `client_address` VARCHAR(255) NULL,
    `currency` ENUM('KES', 'USD') DEFAULT 'KES',
    `total_amount` DECIMAL(15,2) NOT NULL,
    `amount_paid` DECIMAL(15,2) DEFAULT 0.00,
    `balance_due` DECIMAL(15,2) NOT NULL,
    `deposit_paid` DECIMAL(15,2) DEFAULT 0.00,
    `installment_months` INT DEFAULT 1,
    `monthly_installment` DECIMAL(15,2) DEFAULT 0.00,
    `due_date` DATE NOT NULL,
    `status` ENUM('unpaid', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'unpaid',
    `items_json` TEXT NULL,
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Security & Audit Logs Table
CREATE TABLE IF NOT EXISTS `sales_pipeline` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(100) NOT NULL,
    `client_email` VARCHAR(150) NULL,
    `client_phone` VARCHAR(30) NULL,
    `property_id` INT NULL,
    `assigned_to` INT NULL,
    `inquiry_id` INT NULL,
    `booking_id` INT NULL,
    `source` ENUM('website', 'whatsapp', 'referral', 'walk_in', 'inquiry', 'booking', 'manual') DEFAULT 'manual',
    `stage` ENUM('new_lead', 'qualified', 'consultation', 'site_visit', 'negotiation', 'won', 'lost') DEFAULT 'new_lead',
    `estimated_value` DECIMAL(15,2) DEFAULT 0.00,
    `probability_percent` INT DEFAULT 10,
    `expected_close_date` DATE NULL,
    `last_contact_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`booking_id`) REFERENCES `site_visits`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Testimonials Table
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(100) NOT NULL,
    `client_role` VARCHAR(120) NULL,
    `client_location` VARCHAR(120) NULL,
    `quote_text` TEXT NOT NULL,
    `rating` TINYINT DEFAULT 5,
    `avatar_url` VARCHAR(255) NULL,
    `is_featured` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Public Video Library Table
CREATE TABLE IF NOT EXISTS `media_videos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(180) NOT NULL,
    `platform` ENUM('youtube', 'facebook', 'instagram', 'tiktok', 'other') DEFAULT 'youtube',
    `embed_url` VARCHAR(500) NOT NULL,
    `source_url` VARCHAR(500) NULL,
    `summary` TEXT NULL,
    `linked_property_id` INT NULL,
    `is_featured` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`linked_property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Public Property Tips & Notifications Table
CREATE TABLE IF NOT EXISTS `property_tips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(160) NOT NULL,
    `message` TEXT NOT NULL,
    `icon_class` VARCHAR(80) DEFAULT 'fas fa-lightbulb',
    `cta_label` VARCHAR(80) NULL,
    `cta_url` VARCHAR(255) NULL,
    `linked_property_id` INT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`linked_property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Security & Audit Logs Table
CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `user_role` VARCHAR(50) DEFAULT 'guest',
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `user_agent` VARCHAR(255) NULL,
    `status` ENUM('success', 'warning', 'failed', 'critical') DEFAULT 'success',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Visitor Analytics & Live Sessions Table
CREATE TABLE IF NOT EXISTS `visitor_analytics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(50) NOT NULL,
    `page_url` VARCHAR(255) NOT NULL,
    `page_title` VARCHAR(200) NULL,
    `referrer` VARCHAR(255) NULL,
    `user_agent` VARCHAR(255) NULL,
    `device_type` VARCHAR(50) DEFAULT 'Desktop',
    `country` VARCHAR(100) DEFAULT 'Kenya',
    `city` VARCHAR(100) DEFAULT 'Eldoret',
    `session_id` VARCHAR(100) NULL,
    `is_online` TINYINT(1) DEFAULT 1,
    `last_heartbeat` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Front-End CMS Content Blocks Table
CREATE TABLE IF NOT EXISTS `content_blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `block_key` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `content_value` TEXT NOT NULL,
    `block_type` ENUM('text', 'html', 'image', 'video', 'json') DEFAULT 'text',
    `category` VARCHAR(50) DEFAULT 'general',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. App & System Settings Table
CREATE TABLE IF NOT EXISTS `app_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- SEED INITIAL DATA
-- =======================================================

-- 1. Users Seed (Root Super Admin, CEO, Developer)
-- Initial seeded passwords are rotated by install.php during approved setup.
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `avatar`, `two_factor_enabled`) VALUES
(1, 'Super Administrator', 'superadmin@pandarealty.co.ke', '+254708289852', '$2y$10$73zXmm.pmVvXDpBzMnSYG.qEtIqjIaYz.WAw85I.0QssjJ7EyhDR6', 'superadmin', 'assets/images/superadmin.png', 0),
(2, 'Perpetuah Chepchirchir', 'perpetuah@pandarealty.co.ke', '+254708289852', '$2y$10$AYYrPOfX4JknHhQgoGWG2OlDXMeSEXmgxboc1tcwPFNU7IiHm7lau', 'ceo', 'assets/images/perpetuah.jpg', 0),
(3, 'TekTrend Technical Admin', 'admin@tektrend.co.ke', '+254700112233', '$2y$10$K/11gI9u.rLsdLJUh00yBuiiFjTWK9GmXPzcDtMsg1Sth6J0JBpJu', 'developer', 'assets/images/admin.png', 0)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 1b. Separate-Role Table Seeds (for standalone portals — same bcrypt hashes, same passwords)
-- These are authoritative for admin/super-admin-login.php, ceo-login.php, staff-login.php.
-- Developer accounts are mirrored into super_admins since developer == full access.
INSERT INTO `super_admins` (`id`, `full_name`, `email`, `phone`, `password_hash`, `two_factor_enabled`) VALUES
(1, 'Super Administrator', 'superadmin@pandarealty.co.ke', '+254708289852', '$2y$10$73zXmm.pmVvXDpBzMnSYG.qEtIqjIaYz.WAw85I.0QssjJ7EyhDR6', 0),
(2, 'TekTrend Technical Admin', 'admin@tektrend.co.ke', '+254700112233', '$2y$10$K/11gI9u.rLsdLJUh00yBuiiFjTWK9GmXPzcDtMsg1Sth6J0JBpJu', 0)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

INSERT INTO `ceo_users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `two_factor_enabled`) VALUES
(1, 'Perpetuah Chepchirchir', 'perpetuah@pandarealty.co.ke', '+254708289852', '$2y$10$AYYrPOfX4JknHhQgoGWG2OlDXMeSEXmgxboc1tcwPFNU7IiHm7lau', 0)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- Staff: initially empty. Use /admin/staff-register.php to onboard staff.

-- Installer performs final password rotation after schema import.

-- 2. System Settings Seed
INSERT INTO `app_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'Panda Realty - Perpetuah Realtor', 'general'),
('site_tagline', 'Your Eldoret Property Expert | Homes • Land • Investments', 'general'),
('site_slogan', 'We don\'t just sell property — we change lives', 'general'),
('contact_phone', '0708 289 852', 'contact'),
('contact_phone_intl', '+254708289852', 'contact'),
('contact_email', 'info@pandarealty.co.ke', 'contact'),
('contact_address', 'KVDA Plaza, 4th Floor, Oginga Odinga Street, Eldoret, Kenya', 'contact'),
('whatsapp_number', '254708289852', 'contact'),
('currency_usd_rate', '130.00', 'finance'),
('maintenance_mode', '0', 'system'),
('maintenance_message', 'Panda Realty portal is currently undergoing scheduled system upgrades. We will be back online shortly! For urgent inquiries, call or WhatsApp Perpetuah directly at 0708 289 852.', 'system'),
('two_factor_enforced', '0', 'security'),
('developer_name', 'TekTrend Technologies', 'branding'),
('developer_url', 'https://tektrend.co.ke', 'branding')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);

-- 3. Front-End CMS Content Blocks Seed
INSERT INTO `content_blocks` (`block_key`, `title`, `content_value`, `block_type`, `category`) VALUES
('hero_badge', 'Hero Badge Text', 'Exclusive Eldoret Luxury Real Estate', 'text', 'hero'),
('hero_main_title', 'Hero Main Title', 'Find Your Prime Home & Land in Eldoret', 'text', 'hero'),
('hero_subtitle', 'Hero Subtitle Text', 'Discover titled plots, modern studio apartments, luxury family homes, and high-yield investment properties with Eldoret\'s leading realtor, Perpetuah.', 'text', 'hero'),
('about_title', 'About Panda Realty Title', 'Your Trusted Eldoret Property Expert', 'text', 'about'),
('about_story', 'About Bio / Story', 'Led by Perpetuah, Panda Realty is dedicated to connecting families and visionary investors with premier real estate opportunities in Eldoret and across Uasin Gishu County. From title deed verification to structured installment payments, we ensure seamless, transparent, and rewarding property acquisitions.', 'html', 'about'),
('welcome_video_url', 'Welcome Video Embed URL', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'video', 'media'),
('promo_banner_title', 'Ad Modal Title', '🔥 Special Launch: Eldoret Annex Prime 50x100 Plots & Studio Apartments', 'text', 'promo'),
('promo_banner_text', 'Ad Modal Content', 'Ready title deeds with flexible installment plans up to 24 months! Deposit only 10% today and secure your high-return property in Eldoret\'s fastest-growing corridor.', 'html', 'promo'),
('promo_banner_active', 'Ad Modal Active Toggle', '1', 'text', 'promo')
ON DUPLICATE KEY UPDATE `content_value`=VALUES(`content_value`);

-- 4. Sample Properties Seed (Including Studio Apartments, Land & Plots, Under Construction, Sold, Luxury Villas)
INSERT INTO `properties` (`id`, `title`, `slug`, `type`, `category`, `price_kes`, `price_usd`, `location`, `address`, `bedrooms`, `bathrooms`, `area_sqft`, `land_size`, `description`, `features`, `images`, `video_urls`, `status`, `construction_progress`, `construction_stage`, `completion_date`, `is_featured`, `is_hero_slide`, `installment_available`, `min_deposit_percent`, `max_installment_months`) VALUES
(1, 'Elgon View Royal Manor', 'elgon-view-royal-manor', 'mansion', 'sale', 35000000.00, 269230.00, 'Elgon View, Eldoret', 'Plot 42, Off Nairobi Road, Elgon View, Eldoret', 5, 6, 6800, '1/2 Acre', 'An exquisite luxury mansion located in the prestigious Elgon View estate of Eldoret. Features master en-suite with walk-in closets, heated swimming pool, manicured gardens, smart home automation, and perimeter stone wall with electric fencing.', '["Swimming Pool", "Smart Security", "En-suite Bedrooms", "Manicured Lawn", "Perimeter Wall", "Borehole Water", "Solar Power"]', '["https://images.unsplash.com/photo-1613977257363-707ba9348227?w=1200", "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200", "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200"]', '["https://www.youtube.com/embed/dQw4w9WgXcQ"]', 'available', 100, 'Completed', 'Ready for Occupation', 1, 1, 1, 20, 36),

(2, 'Pioneer Modern Studio Apartments', 'pioneer-modern-studio-apartments', 'studio', 'rent', 25000.00, 192.00, 'Pioneer Estate, Eldoret', 'Near Catholic University, Pioneer, Eldoret', 1, 1, 450, NULL, 'Ultra-modern, high-finish executive studio apartment situated in Pioneer, minutes from Eldoret CBD and Universities. Ideal for young professionals and university faculty, featuring high-speed WiFi, fitted kitchenettes, backup generator, and 24/7 biometric access.', '["Fitted Kitchenette", "High-Speed WiFi", "24/7 Security", "Balcony View", "CCTV Cameras", "Constant Water Supply", "Ample Parking"]', '["https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200", "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200", "https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200"]', '[]', 'available', 100, 'Completed', 'Available Immediately', 1, 0, 0, 0, 0),

(3, 'Eldoret Annex Heights Executive Studios', 'eldoret-annex-heights-studios', 'studio', 'sale', 2800000.00, 21538.00, 'Annex, Eldoret', 'Next to Moi University School of Law, Annex, Eldoret', 1, 1, 520, NULL, 'High-yield investment opportunity! Modern studio apartment units in Annex with guaranteed rental income. Features modern ceramic finishes, built-in wardrobes, granite kitchen tops, rooftop terrace lounge, and biometric security.', '["Guaranteed Rental Income", "Rooftop Lounge", "Biometric Access", "Fitted Kitchen", "CCTV Surveillance", "Borehole"]', '["https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200", "https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200", "https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200"]', '["https://www.youtube.com/embed/jNQXAC9IVRw", "https://www.instagram.com/reel/C5L6ghfxyza/embed/"]', 'under_construction', 75, 'Finishing & Interior Works', 'Dec 2026', 1, 1, 1, 15, 24),

(4, 'Kapsoya Green Valley 4-Bedroom Villa', 'kapsoya-green-valley-villa', 'villa', 'sale', 18500000.00, 142307.00, 'Kapsoya, Eldoret', 'Kapsoya Phase 2, Near Highlands School, Eldoret', 4, 4, 4200, '1/4 Acre', 'Stunning contemporary villa in serene Kapsoya. Features spacious sunken lounge, gypsum ceilings with LED mood lighting, solar water heating, bio-digester, detached DSQ, and landscaped compound with mature trees.', '["Sunken Lounge", "Detached DSQ", "Solar Water Heating", "Gypsum Lighting", "Gated Community", "Paved Driveway"]', '["https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200", "https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=1200", "https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=1200"]', '[]', 'available', 100, 'Completed', 'Ready for Title Transfer', 1, 1, 1, 10, 36),

(5, 'Prime 50x100 Residential Plots - Annex', 'prime-50x100-plots-annex', 'land', 'sale', 2200000.00, 16923.00, 'Annex, Eldoret', 'Annex Oasis Scheme, 800m from Eldoret-Nakuru Highway', 0, 0, 0, '50 x 100 ft (1/8 Acre)', 'Prime residential 50x100 plots with individual freehold title deeds ready. Electricity and water on site, all-weather murram access roads, ideal for immediate family home construction or rental apartment development.', '["Ready Title Deeds", "Electricity on Site", "Water Available", "All-Weather Roads", "Developed Neighborhood", "Free Site Visits"]', '["https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=1200", "https://images.unsplash.com/photo-1500076656116-558758c991c1?w=1200", "https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200"]', '["https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2FPandaRealtyKenya%2Fvideos%2F1234567890&show_text=false"]', 'available', 100, 'Completed', 'Title Deeds Ready', 1, 1, 1, 10, 18),

(6, 'West Indies Luxury Gated Estate Villas', 'west-indies-luxury-villas', 'house', 'sale', 24000000.00, 184615.00, 'West Indies, Eldoret', 'West Indies Park Lane, Eldoret', 4, 5, 4800, '1/4 Acre', 'Exclusive master-planned gated community of 8 luxury townhouses in serene West Indies. Ongoing construction with first phase handing over soon. Features custom Italian kitchens, private roof garden, and dedicated clubhouse.', '["Gated Security", "Private Roof Terrace", "Italian Kitchen", "Clubhouse Access", "Electric Fence", "Intercom System"]', '["https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1200", "https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=1200", "https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200"]', '[]', 'under_construction', 45, 'Structural Framework & Walling', 'March 2027', 1, 1, 1, 15, 24),

(7, 'Maili Nne 1-Acre Commercial Plot', 'maili-nne-1-acre-commercial-plot', 'land', 'sale', 16000000.00, 123076.00, 'Maili Nne, Eldoret', 'Along Eldoret-Webuye Highway, Maili Nne', 0, 0, 0, '1 Acre', 'Commercial prime parcel fronting the highway at Maili Nne. High vehicular traffic, flat terrain, ideal for petrol station, hardware hub, commercial go-downs, or mixed-use commercial development. Clean commercial title deed.', '["Highway Frontage", "Clean Commercial Title", "Three-Phase Power", "Flat Red Soil", "High Traffic Node"]', '["https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200", "https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=1200"]', '[]', 'available', 100, 'Completed', 'Immediate Transfer', 0, 0, 1, 20, 24),

(8, 'Kipkenyo Sunset Ridge 3-Bedroom Bungalow', 'kipkenyo-sunset-ridge-bungalow', 'house', 'sale', 9500000.00, 73076.00, 'Kipkenyo, Eldoret', 'Sunset Ridge, Kipkenyo, Eldoret', 3, 3, 2600, '50 x 100 ft', 'Charming modern standalone bungalow in Kipkenyo. Sold out in Phase 1, Phase 2 now complete. Spacious living room, dining area, modern open-plan kitchen, master ensuite, and perimeter wall with sliding gate.', '["Master Ensuite", "Perimeter Wall", "Modern Kitchen", "Sliding Gate", "Red Soil Garden"]', '["https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1200", "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200"]', '[]', 'sold', 100, 'Completed', 'Sold Out', 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 5. Sample Inquiries Seed
INSERT INTO `inquiries` (`id`, `property_id`, `name`, `email`, `phone`, `subject`, `message`, `inquiry_type`, `status`) VALUES
(1, 5, 'Dr. Evans Kipchumba', 'evans.kip@gmail.com', '+254712345678', 'Plot Inquiry in Annex', 'Hello Perpetuah, I am interested in viewing the 50x100 plots in Annex this Saturday. Do you have corner plots available?', 'property_inquiry', 'new'),
(2, 3, 'Faith Jepchirchir', 'faith.j@yahoo.com', '+254723456789', 'Studio Apartment Investment', 'Hi Panda Realty team, I would like to receive the payment breakdown and expected ROI for 2 studio units at Annex Heights.', 'installment_quote', 'in_progress'),
(3, 1, 'Eng. David Mwangi', 'dmwangi@consulting.co.ke', '+254734567890', 'Elgon View Manor Tour', 'Can we arrange a private viewing of Elgon View Royal Manor for me and my family on Friday afternoon?', 'property_inquiry', 'resolved')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 6. Sample Bookings / Site Visits Seed
INSERT INTO `site_visits` (`id`, `property_id`, `client_name`, `client_email`, `client_phone`, `visit_date`, `visit_time`, `status`, `notes`) VALUES
(1, 5, 'Dr. Evans Kipchumba', 'evans.kip@gmail.com', '+254712345678', CURDATE() + INTERVAL 2 DAY, '10:00:00', 'confirmed', 'Site tour to Annex Oasis plots. Pick up at Eldoret CBD office.'),
(2, 1, 'Eng. David Mwangi', 'dmwangi@consulting.co.ke', '+254734567890', CURDATE() + INTERVAL 1 DAY, '14:30:00', 'confirmed', 'VIP private viewing with Perpetuah.'),
(3, 3, 'Faith Jepchirchir', 'faith.j@yahoo.com', '+254723456789', CURDATE() + INTERVAL 3 DAY, '11:00:00', 'pending', 'Studio apartments construction inspection.')
ON DUPLICATE KEY UPDATE `client_name`=VALUES(`client_name`);

-- 7. Sample Tasks Seed
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `priority`, `status`) VALUES
(1, 2, 'Follow up Annex Title Deed Issuance', 'Collect sealed title deeds from the Land Registry in Eldoret for Batch 4 Annex plots.', CURDATE() + INTERVAL 1 DAY, 'urgent', 'in_progress'),
(2, 2, 'Site Visit: Elgon View Royal Manor', 'Host Eng. Mwangi for high-end luxury mansion inspection.', CURDATE() + INTERVAL 1 DAY, 'high', 'pending'),
(3, 3, 'Run Database Backup & Security Audit', 'Perform monthly automated database backup and verify 2FA logs.', CURDATE() + INTERVAL 4 DAY, 'medium', 'pending')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 8. Sample Invoices Seed
INSERT INTO `invoices` (`id`, `invoice_number`, `property_id`, `client_name`, `client_email`, `client_phone`, `client_address`, `currency`, `total_amount`, `amount_paid`, `balance_due`, `deposit_paid`, `installment_months`, `monthly_installment`, `due_date`, `status`, `items_json`, `notes`, `created_by`) VALUES
(1, 'INV-2026-0081', 5, 'Dr. Evans Kipchumba', 'evans.kip@gmail.com', '+254712345678', 'P.O Box 4010, Eldoret', 'KES', 2200000.00, 440000.00, 1760000.00, 440000.00, 12, 146666.67, CURDATE() + INTERVAL 30 DAY, 'partially_paid', '[{"description":"Annex 50x100 Residential Plot (Plot No. 18)","amount":2200000,"quantity":1}]', '20% Initial Deposit Paid via Bank Transfer. 12 Monthly Installments agreement signed.', 2),
(2, 'INV-2026-0082', 3, 'Faith Jepchirchir', 'faith.j@yahoo.com', '+254723456789', 'P.O Box 1200, Eldoret', 'KES', 2800000.00, 2800000.00, 0.00, 2800000.00, 1, 0.00, CURDATE() - INTERVAL 5 DAY, 'paid', '[{"description":"Annex Heights Studio Apartment Unit 3B","amount":2800000,"quantity":1}]', 'Full payment settled via bank escrow. Title transfer in progress.', 1)
ON DUPLICATE KEY UPDATE `invoice_number`=VALUES(`invoice_number`);

-- 9. Sample Sales Pipeline Seed
INSERT INTO `sales_pipeline` (`id`, `client_name`, `client_email`, `client_phone`, `property_id`, `assigned_to`, `inquiry_id`, `source`, `stage`, `estimated_value`, `probability_percent`, `expected_close_date`, `last_contact_date`, `notes`) VALUES
(1, 'Mercy Chebet', 'mercy.chebet@gmail.com', '+254711223344', 5, 2, NULL, 'website', 'consultation', 2200000.00, 45, CURDATE() + INTERVAL 14 DAY, CURDATE(), 'Client is comparing Annex plots and requested a structured consultation.'),
(2, 'Brian Kiptoo', 'brian.kiptoo@gmail.com', '+254722334455', 3, 3, NULL, 'whatsapp', 'site_visit', 2800000.00, 60, CURDATE() + INTERVAL 10 DAY, CURDATE() - INTERVAL 1 DAY, 'Site visit booked for a studio apartment after WhatsApp follow-up.')
ON DUPLICATE KEY UPDATE `client_name`=VALUES(`client_name`);

-- 10. Sample Testimonials Seed
INSERT INTO `testimonials` (`id`, `client_name`, `client_role`, `client_location`, `quote_text`, `rating`, `avatar_url`, `is_featured`, `is_active`, `display_order`) VALUES
(1, 'Faith Jepchirchir', 'Investor', 'Eldoret', 'Perpetuah made the entire purchase process feel safe and clear. I moved from inquiry to title transfer without guesswork, and the updates were very professional.', 5, 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=300', 1, 1, 1),
(2, 'Dr. Evans Kipchumba', 'Land Buyer', 'Annex, Eldoret', 'The site visit was organized well and the title documents were explained in simple language. I would absolutely recommend Panda Realty for serious property buyers.', 5, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300', 1, 1, 2),
(3, 'Mercy Chebet', 'Diaspora Client', 'Nairobi / UK', 'I needed a responsive team that could send media, explain payment plans, and keep me updated remotely. Panda Realty delivered exactly that.', 5, 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300', 1, 1, 3)
ON DUPLICATE KEY UPDATE `client_name`=VALUES(`client_name`);

-- 11. Sample Video Library Seed
INSERT INTO `media_videos` (`id`, `title`, `platform`, `embed_url`, `source_url`, `summary`, `linked_property_id`, `is_featured`, `is_active`, `display_order`) VALUES
(1, 'Panda Realty Brand Story', 'youtube', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Welcome video introducing Panda Realty and Perpetuah\'s consultation style.', NULL, 1, 1, 1),
(2, 'Annex Heights Construction Update', 'instagram', 'https://www.instagram.com/reel/C5L6ghfxyza/embed/', 'https://www.instagram.com/reel/C5L6ghfxyza/', 'Short-form construction progress reel for the Annex Heights studio project.', 3, 1, 1, 2),
(3, 'Prime Annex Plots Walkthrough', 'facebook', 'https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2FPandaRealtyKenya%2Fvideos%2F1234567890&show_text=false', 'https://www.facebook.com/PandaRealtyKenya/videos/1234567890', 'Walkthrough video for Annex plots and title-ready investment options.', 5, 1, 1, 3)
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- 12. Sample Security Logs Seed
INSERT INTO `security_logs` (`user_id`, `user_role`, `action`, `details`, `ip_address`, `user_agent`, `status`) VALUES
(1, 'superadmin', 'SYSTEM_INITIALIZATION', 'Panda Realty database initialized with full schema and sample Eldoret catalog.', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success'),
(2, 'ceo', 'ADMIN_LOGIN', 'CEO Perpetuah logged in successfully.', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success'),
(3, 'developer', 'SETTINGS_UPDATE', 'Developer updated USD exchange rate to 130.00 KES.', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'success');
