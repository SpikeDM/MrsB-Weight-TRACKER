-- MrsB Tracker — Database Schema
-- Run this once via phpMyAdmin or MySQL CLI before first use
-- mysql -u youruser -p yourdb < schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- Table: admin_users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username = admin, password = MrsB2024! (change immediately after first login)
INSERT INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------
-- Table: clients
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `token`         VARCHAR(64) NOT NULL,
  `start_date`    DATE DEFAULT NULL,
  `start_weight`  VARCHAR(20) DEFAULT NULL,
  `end_weight`    VARCHAR(20) DEFAULT NULL,
  `chest_start`   VARCHAR(20) DEFAULT NULL,
  `chest_end`     VARCHAR(20) DEFAULT NULL,
  `waist_start`   VARCHAR(20) DEFAULT NULL,
  `waist_end`     VARCHAR(20) DEFAULT NULL,
  `bum_start`     VARCHAR(20) DEFAULT NULL,
  `bum_end`       VARCHAR(20) DEFAULT NULL,
  `arms_start`    VARCHAR(20) DEFAULT NULL,
  `arms_end`      VARCHAR(20) DEFAULT NULL,
  `belly_start`   VARCHAR(20) DEFAULT NULL,
  `belly_end`     VARCHAR(20) DEFAULT NULL,
  `thigh_start`   VARCHAR(20) DEFAULT NULL,
  `thigh_end`     VARCHAR(20) DEFAULT NULL,
  `status`        ENUM('active','complete') NOT NULL DEFAULT 'active',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: weeks
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `weeks` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `client_id`   INT(11) NOT NULL,
  `week_number` TINYINT(1) NOT NULL,
  `entry1`      VARCHAR(255) DEFAULT NULL,
  `entry2`      VARCHAR(255) DEFAULT NULL,
  `entry3`      VARCHAR(255) DEFAULT NULL,
  `entry4`      VARCHAR(255) DEFAULT NULL,
  `notes1`      TEXT DEFAULT NULL,
  `notes2`      TEXT DEFAULT NULL,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_week` (`client_id`, `week_number`),
  CONSTRAINT `fk_weeks_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
