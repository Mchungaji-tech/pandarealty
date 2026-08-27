-- =======================================================
-- Delta Migration 002 — SEPARATE ROLE TABLES (Auth Phase 3)
-- Target: Existing databases that already ran 001_initial_schema.sql
-- Run this on production / remote databases to add standalone
-- Super Admin, CEO, and Staff tables WITHOUT touching existing data.
-- (For fresh installs, use 001_initial_schema.sql instead.)
-- =======================================================
USE `pandareality_db`;

-- A) Users table — add missing last_login_ip column if absent
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_login_ip'
);
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `last_login_ip` VARCHAR(45) NULL AFTER `last_active`',
    'SELECT ''SKIP: users.last_login_ip already exists'' AS message'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- B) Separate Role Tables (each is standalone — no FKs to users)

-- B1. Super Administrators (max 2 rows enforced in application)
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

-- B2. CEO Users
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

-- B3. Staff Members
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

-- C) Idempotent row copy from users.role -> separate tables (never overwrites)
-- Mirrors superadmin + developer (dev == full access) -> super_admins
INSERT IGNORE INTO `super_admins`
    (`full_name`, `email`, `phone`, `password_hash`,
     `two_factor_secret`, `two_factor_enabled`, `is_online`, `last_active`,
     `last_login_ip`, `created_at`, `updated_at`)
SELECT
    u.`name` AS `full_name`,
    u.`email`,
    u.`phone`,
    u.`password` AS `password_hash`,
    u.`two_factor_secret`,
    u.`two_factor_enabled`,
    u.`is_online`,
    u.`last_active`,
    u.`last_login_ip`,
    u.`created_at`,
    u.`updated_at`
FROM `users` u
WHERE u.`role` IN ('superadmin','developer');

-- Mirrors ceo -> ceo_users
INSERT IGNORE INTO `ceo_users`
    (`full_name`, `email`, `phone`, `password_hash`,
     `two_factor_secret`, `two_factor_enabled`, `is_online`, `last_active`,
     `last_login_ip`, `created_at`, `updated_at`)
SELECT
    u.`name` AS `full_name`,
    u.`email`,
    u.`phone`,
    u.`password` AS `password_hash`,
    u.`two_factor_secret`,
    u.`two_factor_enabled`,
    u.`is_online`,
    u.`last_active`,
    u.`last_login_ip`,
    u.`created_at`,
    u.`updated_at`
FROM `users` u
WHERE u.`role` = 'ceo';

-- Mirrors staff + admin (admin classed as staff in Phase 3) -> staff_members
INSERT IGNORE INTO `staff_members`
    (`full_name`, `email`, `phone`, `department`, `position_title`,
     `password_hash`, `two_factor_secret`, `two_factor_enabled`, `is_online`,
     `last_active`, `last_login_ip`, `created_at`, `updated_at`)
SELECT
    u.`name` AS `full_name`,
    u.`email`,
    u.`phone`,
    'Sales'                    AS `department`,
    'Migrated Staff'           AS `position_title`,
    u.`password` AS `password_hash`,
    u.`two_factor_secret`,
    u.`two_factor_enabled`,
    u.`is_online`,
    u.`last_active`,
    u.`last_login_ip`,
    u.`created_at`,
    u.`updated_at`
FROM `users` u
WHERE u.`role` IN ('staff','admin');

-- D) Done
SELECT '✅ Migration 002 complete — separate role tables installed + seeded from users.role' AS result;
SELECT COUNT(*) AS super_admins_count FROM `super_admins`;
SELECT COUNT(*) AS ceo_users_count    FROM `ceo_users`;
SELECT COUNT(*) AS staff_members_count FROM `staff_members`;
