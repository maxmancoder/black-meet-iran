-- ============================================================
-- Obsidian Flux (Black Meet) - Database Schema
-- ============================================================
-- Import via phpMyAdmin (XAMPP) or mysql CLI:
--   mysql -u root -p < sql/schema.sql
-- Or create database "blackmeet" manually and import tables only.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `blackmeet`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
USE `blackmeet`;

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(120) NOT NULL,
  `username`      VARCHAR(60)  NOT NULL,
  `email`         VARCHAR(160) NOT NULL,
  `phone`         VARCHAR(20)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name`  VARCHAR(120) NOT NULL,
  `is_manager`    TINYINT(1)   NOT NULL DEFAULT 0,
  `is_limited`    TINYINT(1)   NOT NULL DEFAULT 0,
  `avatar_color`  VARCHAR(7)   NOT NULL DEFAULT '#4f46e5',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Verification codes (OTP) - 5 minute expiry enforced in app
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(160) NOT NULL,
  `code`       VARCHAR(10)  NOT NULL,
  `purpose`    ENUM('signup','login_phone') NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Meetings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meetings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id`    VARCHAR(32)  NOT NULL,
  `title`      VARCHAR(160) NOT NULL,
  `creator_id` INT UNSIGNED NOT NULL,
  `active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_id` (`room_id`),
  KEY `idx_creator` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Meeting participants
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meeting_participants` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `meeting_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `display_name` VARCHAR(120) NOT NULL,
  `username`    VARCHAR(60)  NOT NULL,
  `status`      ENUM('pending','approved','removed') NOT NULL DEFAULT 'pending',
  `role`        ENUM('admin','member') NOT NULL DEFAULT 'member',
  `muted`       TINYINT(1)   NOT NULL DEFAULT 0,
  `cam_on`      TINYINT(1)   NOT NULL DEFAULT 0,
  `sharing`     TINYINT(1)   NOT NULL DEFAULT 0,
  `joined_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meeting_user` (`meeting_id`, `user_id`),
  KEY `idx_meeting` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Chat messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `meeting_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `display_name` VARCHAR(120) NOT NULL,
  `body`        TEXT         NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_meeting` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Emoji events (for replay on join)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emoji_events` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `meeting_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `display_name` VARCHAR(120) NOT NULL,
  `emoji`       VARCHAR(8)   NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_meeting` (`meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ------------------------------------------------------------
-- Seed: Manager account
--   phone: 09331660212
--   email: av0098056@gmail.com
--   pass : amirali1388  (bcrypt hashed below)
--   user : blackline
-- ------------------------------------------------------------
INSERT INTO `users`
  (`full_name`, `username`, `email`, `phone`, `password_hash`, `display_name`, `is_manager`, `avatar_color`)
VALUES
  ('blackline', 'blackline', 'av0098056@gmail.com', '09331660212',
   'amirali1388',
   'blackline', 1, '#bf0f3c');
