-- =============================================================
-- MealMate — Database Schema
-- =============================================================
-- Import this directly (phpMyAdmin: Import tab, or
-- `mysql -u root -p < mealmate_db.sql`) to set up the database
-- without relying on config/db.php's auto-create-on-first-request
-- behaviour.
--
-- IMPORTANT: this file is hand-kept in sync with the CREATE TABLE
-- statements in config/db.php. If you change a table there
-- (add/rename/drop a column), update this file to match, or the
-- two will drift apart.
--
-- Engine: InnoDB | Charset: utf8mb4 (utf8mb4_unicode_ci)
-- =============================================================

CREATE DATABASE IF NOT EXISTS `mealmate_db`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `mealmate_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `account_status` ENUM('pending','active') NOT NULL DEFAULT 'pending',
    `two_fa_enabled` TINYINT(1) DEFAULT 0,
    `listing_visibility` ENUM('public','private') DEFAULT 'public',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- email_verifications  (UC1: Register User & Privacy Settings)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `code` CHAR(6) NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_email_verifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- food_items  (UC2: Manage Food Inventory)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `food_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `category` ENUM('fruits','vegetables','dairy','meat','grains','other') NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'pieces',
    `expiry_date` DATE NOT NULL,
    `storage_location` ENUM('fridge','freezer','pantry') NOT NULL,
    `status` ENUM('available','consumed','donated') DEFAULT 'available',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_food_items_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- donations  (UC3: Browse Food Items)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `donor_id` INT NOT NULL,
    `claimer_id` INT DEFAULT NULL,
    `food_item_id` INT NOT NULL,
    `status` ENUM('available','claimed','completed') DEFAULT 'available',
    `listed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `claimed_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_donations_donor`
        FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_donations_claimer`
        FOREIGN KEY (`claimer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_donations_food_item`
        FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- notifications  (UC5: View Notifications)
-- `related_item_id` links an expiry notification back to the
-- food_items row it's about, so dashboard.php can check "have I
-- already notified this user about this item today?" with a
-- plain integer comparison instead of parsing it out of the
-- message text.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('expiry','donation','meal','account') NOT NULL,
    `message` TEXT NOT NULL,
    `related_item_id` INT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- meal_plans  (UC6: Plan Weekly Meals)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meal_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `meal_date` DATE NOT NULL,
    `meal_type` ENUM('breakfast','lunch','dinner') NOT NULL,
    `meal_name` VARCHAR(255) NOT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_meal_plans_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- food_saved_log  (UC4: Food Analytics)
-- Append-only event log: one row per consume/donate action,
-- which is what Analytics aggregates into its charts and stats.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `food_saved_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_name` VARCHAR(260) NOT NULL,
    `action` ENUM('consumed','donated') NOT NULL,
    `category` VARCHAR(55) NOT NULL,
    `logged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_food_saved_log_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Helpful indexes (not strictly required, but the queries in
-- inventory.php / dashboard.php / analytics.php / browse.php all
-- filter by these columns constantly).
--
-- Note: these CREATE INDEX statements are NOT wrapped in an
-- existence check (MySQL doesn't support IF NOT EXISTS here the
-- way it does for CREATE TABLE). That's fine for a first-time
-- import into a fresh database, which is this file's purpose —
-- but re-running this whole file against a database that already
-- has these indexes will error on "duplicate key name". If you
-- need to re-import, drop the database first:
--   DROP DATABASE IF EXISTS mealmate_db;
-- then re-run this file.
-- =============================================================
CREATE INDEX `idx_food_items_user_status` ON `food_items` (`user_id`, `status`);
CREATE INDEX `idx_food_items_expiry` ON `food_items` (`expiry_date`);
CREATE INDEX `idx_donations_status` ON `donations` (`status`);
CREATE INDEX `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`);
CREATE INDEX `idx_meal_plans_user_date` ON `meal_plans` (`user_id`, `meal_date`);
CREATE INDEX `idx_food_saved_log_user` ON `food_saved_log` (`user_id`, `action`);
