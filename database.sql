-- ==========================================================
-- DIGITAL MARKETING CAMPAIGN TRACKER
-- Database Schema & Sample Seed Data
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `digital_marketing_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `digital_marketing_db`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `campaign_metrics`;
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Marketer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `campaigns`
-- --------------------------------------------------------
CREATE TABLE `campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `campaign_name` VARCHAR(200) NOT NULL,
  `channel` ENUM('Social Media', 'Email', 'PPC', 'SEO', 'Display') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `budget` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'Paused', 'Completed') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_campaigns_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `campaign_metrics`
-- --------------------------------------------------------
CREATE TABLE `campaign_metrics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `date_recorded` DATE NOT NULL,
  `impressions` INT NOT NULL DEFAULT 0,
  `clicks` INT NOT NULL DEFAULT 0,
  `conversions` INT NOT NULL DEFAULT 0,
  `amount_spent` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `engagement_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_metrics_campaign` 
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SAMPLE SEED DATA
-- Default accounts have password: password123
-- ==========================================================

-- 1. Insert sample users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Alex Morgan', 'alex@marketing.com', '$2y$10$VFeqbF2pa3qbhkvohjpQqeiAEBEvlNSKDhNgkQh03jze5jpfomWB6', 'Lead Marketer', NOW()),
(2, 'Demo Admin', 'admin@tracker.com', '$2y$10$VFeqbF2pa3qbhkvohjpQqeiAEBEvlNSKDhNgkQh03jze5jpfomWB6', 'Administrator', NOW());

-- 2. Insert sample campaigns (Budgets in Indian Rupees ₹)
INSERT INTO `campaigns` (`id`, `user_id`, `campaign_name`, `channel`, `start_date`, `end_date`, `budget`, `status`, `created_at`) VALUES
(1, 1, 'Summer Brand Awareness 2026', 'Social Media', '2026-06-01', '2026-08-31', 45000.00, 'Active', '2026-06-01 10:00:00'),
(2, 1, 'Q3 Product Newsletter Blast', 'Email', '2026-07-01', '2026-09-30', 12000.00, 'Active', '2026-07-01 09:30:00'),
(3, 1, 'High-Intent Search Ads', 'PPC', '2026-05-15', '2026-08-15', 60000.00, 'Completed', '2026-05-15 11:15:00'),
(4, 1, 'Organic Growth & Backlink Booster', 'SEO', '2026-04-01', '2026-10-31', 30000.00, 'Active', '2026-04-01 08:00:00'),
(5, 1, 'Retargeting Banner Network', 'Display', '2026-06-15', '2026-09-15', 25000.00, 'Paused', '2026-06-15 14:20:00'),
(6, 1, 'Holiday Flash Sale Promo', 'Social Media', '2026-08-01', '2026-08-10', 15000.00, 'Completed', '2026-08-01 07:45:00');

-- 3. Insert sample campaign metrics (Amounts spent in Indian Rupees ₹)
INSERT INTO `campaign_metrics` (`campaign_id`, `date_recorded`, `impressions`, `clicks`, `conversions`, `amount_spent`, `engagement_rate`) VALUES
-- Campaign 1: Summer Brand Awareness 2026 (Social Media)
(1, '2026-06-05', 18500, 740, 32, 2800.00, 4.00),
(1, '2026-06-12', 22400, 950, 48, 3500.00, 4.24),
(1, '2026-06-19', 26100, 1120, 61, 4100.00, 4.29),
(1, '2026-06-26', 31000, 1380, 77, 4900.00, 4.45),
(1, '2026-07-03', 29800, 1290, 70, 4600.00, 4.33),
(1, '2026-07-10', 34200, 1550, 89, 5400.00, 4.53),
(1, '2026-07-17', 36000, 1680, 96, 5800.00, 4.67),

-- Campaign 2: Q3 Product Newsletter Blast (Email)
(2, '2026-07-05', 8500, 890, 82, 1200.00, 10.47),
(2, '2026-07-15', 9200, 1040, 95, 1400.00, 11.30),
(2, '2026-07-25', 8900, 960, 88, 1300.00, 10.79),
(2, '2026-08-05', 9800, 1150, 110, 1600.00, 11.73),

-- Campaign 3: High-Intent Search Ads (PPC)
(3, '2026-05-20', 14500, 1450, 180, 8500.00, 10.00),
(3, '2026-06-01', 18200, 1920, 240, 11200.00, 10.55),
(3, '2026-06-15', 21000, 2250, 290, 13500.00, 10.71),
(3, '2026-07-01', 24500, 2680, 340, 15800.00, 10.94),
(3, '2026-07-15', 16000, 1750, 215, 10500.00, 10.94),

-- Campaign 4: Organic Growth & Backlink Booster (SEO)
(4, '2026-04-15', 12000, 480, 28, 3000.00, 4.00),
(4, '2026-05-15', 19500, 820, 52, 4500.00, 4.21),
(4, '2026-06-15', 28400, 1260, 84, 6000.00, 4.44),
(4, '2026-07-15', 38000, 1780, 125, 7500.00, 4.68),

-- Campaign 5: Retargeting Banner Network (Display)
(5, '2026-06-20', 45000, 680, 34, 4200.00, 1.51),
(5, '2026-06-30', 52000, 810, 42, 5100.00, 1.56),
(5, '2026-07-10', 48000, 740, 38, 4600.00, 1.54),

-- Campaign 6: Holiday Flash Sale Promo (Social Media)
(6, '2026-08-02', 35000, 2100, 210, 4500.00, 6.00),
(6, '2026-08-05', 48000, 3100, 330, 6200.00, 6.46),
(6, '2026-08-09', 32000, 1950, 195, 4100.00, 6.09);
