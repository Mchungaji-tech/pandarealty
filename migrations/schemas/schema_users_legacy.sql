-- Schema: users (LEGACY Shared Table — still used for Clients / Public Portal)
-- Used by:  login.php (public client portal), admin/login.php (old internal portal),
--           users.php, inquiries.*, tasks.*, invoices.created_by (FK still uses users.id)
-- Session:   $_SESSION['user_id'] (shared with clients + legacy portal admins)
-- Role ENUM values (still present for backwards compatibility):
--      superadmin | developer | ceo | admin | staff | client
--
-- IMPORTANT: For Phase 3 standalone portals, use super_admins / ceo_users / staff_members
-- instead. This table remains because:
--   1. Foreign keys (inquiries.assigned_to, tasks.user_id, invoices.created_by, etc.)
--      still reference users.id.
--   2. Public client registrations continue writing here (role='client').

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt (PASSWORD_DEFAULT). Legacy column name; separate tables use password_hash.',
    `role` ENUM('superadmin','developer','ceo','admin','staff','client') DEFAULT 'client',
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `two_factor_secret` VARCHAR(64) NULL COMMENT 'Base32 TOTP secret',
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Legacy shared users table (clients + backwards-compat staff roles)';
