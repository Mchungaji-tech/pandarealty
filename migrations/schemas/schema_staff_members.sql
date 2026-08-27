-- Schema: staff_members (Separate Role Table)
-- Used by:  admin/staff-login.php, admin/staff-register.php, admin/staff-dashboard.php
-- Session:   $_SESSION['staff_id'] (separate from all other roles)
-- Quota:     unlimited
-- Extra cols department + position_title (unlike super_admins / ceo_users)
-- Notes:     bcrypt password hash stored in password_hash

CREATE TABLE IF NOT EXISTS `staff_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `department` VARCHAR(80) NULL COMMENT 'e.g. Sales, Marketing, Property Mgmt, Finance',
    `position_title` VARCHAR(120) NULL COMMENT 'e.g. Sales Agent, Property Consultant, Operations Lead',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt (PASSWORD_DEFAULT)',
    `two_factor_secret` VARCHAR(64) NULL COMMENT 'Base32 TOTP secret',
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `is_online` TINYINT(1) DEFAULT 0,
    `last_active` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Staff / Operational Team accounts';
