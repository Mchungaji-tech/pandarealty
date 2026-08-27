-- Schema: ceo_users (Separate Role Table)
-- Used by:  admin/ceo-login.php, admin/ceo-register.php, admin/ceo-dashboard.php
-- Session:   $_SESSION['ceo_id'] (separate from all other roles)
-- Quota:     unlimited (typically 1)
-- Notes:     bcrypt password hash stored in password_hash

CREATE TABLE IF NOT EXISTS `ceo_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt (PASSWORD_DEFAULT)',
    `two_factor_secret` VARCHAR(64) NULL COMMENT 'Base32 TOTP secret',
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chief Executive Officer accounts';
