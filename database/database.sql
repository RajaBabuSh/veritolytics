-- ==============================================================================
-- VERITOLYTICS - PURE CORE PHP & MYSQL DATABASE SCHEMA
-- Version: 1.0.0
-- Charset: utf8mb4 (Full Unicode Support)
-- Compatible with MySQL 8.0+, MariaDB 10.4+, and XAMPP
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `veritolytics_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `veritolytics_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `redemptions`;
DROP TABLE IF EXISTS `rewards`;
DROP TABLE IF EXISTS `survey_responses`;
DROP TABLE IF EXISTS `surveys`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. USERS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `pending_email` VARCHAR(191) DEFAULT NULL,
  `email_verification_token` VARCHAR(255) DEFAULT NULL,
  `email_verification_expires_at` DATETIME DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'United States',
  `state` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` VARCHAR(50) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `employment_status` VARCHAR(100) DEFAULT NULL,
  `annual_income` VARCHAR(100) DEFAULT NULL,
  `education_level` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('member', 'admin') NOT NULL DEFAULT 'member',
  `status` ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
  `balance_points` INT NOT NULL DEFAULT 0,
  `lifetime_earnings_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `completed_surveys_count` INT NOT NULL DEFAULT 0,
  `profile_completion_pct` INT NOT NULL DEFAULT 35,
  `notification_preferences` TEXT DEFAULT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_expires_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. SURVEYS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `surveys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Consumer Insights',
  `reward_points` INT NOT NULL DEFAULT 100,
  `reward_usd` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `duration_minutes` INT NOT NULL DEFAULT 5,
  `quota` INT NOT NULL DEFAULT 100,
  `completed_count` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'paused', 'completed', 'draft') NOT NULL DEFAULT 'active',
  `provider` VARCHAR(100) NOT NULL DEFAULT 'Veritopinion Direct',
  `sample_questions_json` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_surveys_status` (`status`),
  INDEX `idx_surveys_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. SURVEY RESPONSES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `survey_responses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `survey_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('in_progress', 'completed', 'screened_out') NOT NULL DEFAULT 'completed',
  `points_awarded` INT NOT NULL DEFAULT 0,
  `usd_awarded` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `answers_json` TEXT DEFAULT NULL,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_survey` (`survey_id`, `user_id`),
  INDEX `idx_sr_user` (`user_id`),
  INDEX `idx_sr_survey` (`survey_id`),
  CONSTRAINT `fk_sr_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. REWARDS CATALOG TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `rewards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` ENUM('Cash & Crypto', 'Gift Cards', 'Gaming & Media') NOT NULL,
  `points_required` INT NOT NULL,
  `cash_equivalent_usd` DECIMAL(10,2) NOT NULL,
  `delivery_type` VARCHAR(150) NOT NULL DEFAULT 'Instant Digital Delivery',
  `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-gift',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rewards_active` (`is_active`),
  INDEX `idx_rewards_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. REDEMPTIONS TABLE (Cashout Requests)
-- ------------------------------------------------------------------------------
CREATE TABLE `redemptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `reward_id` INT DEFAULT NULL,
  `reward_title` VARCHAR(255) NOT NULL,
  `points_spent` INT NOT NULL,
  `amount_usd` DECIMAL(10,2) NOT NULL,
  `payout_destination` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'settled') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_redemptions_user` (`user_id`),
  INDEX `idx_redemptions_status` (`status`),
  CONSTRAINT `fk_redemptions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemptions_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_redemptions_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. TRANSACTIONS LEDGER TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('welcome_bonus', 'survey_reward', 'redemption', 'bonus', 'adjustment') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `points` INT NOT NULL,
  `amount_usd` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tx_user` (`user_id`),
  INDEX `idx_tx_type` (`type`),
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. NOTIFICATIONS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'survey', 'reward', 'system') NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. SUPPORT TICKETS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `support_tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open', 'in_review', 'resolved') NOT NULL DEFAULT 'open',
  `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `admin_reply` TEXT DEFAULT NULL,
  `admin_id` INT DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tickets_user` (`user_id`),
  INDEX `idx_tickets_status` (`status`),
  INDEX `idx_tickets_admin` (`admin_id`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tickets_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. TICKET REPLIES TABLE (Support Thread History & Admin Responses)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 1,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_replies_ticket` (`ticket_id`),
  INDEX `idx_replies_user` (`user_id`),
  CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. SESSIONS TABLE (Serverless & Distributed PHP Session Persistence)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `data` MEDIUMTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  INDEX `idx_sessions_last_activity` (`last_activity`),
  INDEX `idx_sessions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- REALISTIC SEED DATA (CLEARLY LABELED DEMO DATA)
-- ==============================================================================

-- Admin Credentials: admin@veritopinion.com (or admin@veritolytics.com) / Admin@12345
-- Member Credentials: member@veritopinion.com (or member@veritolytics.com) / Member@12345
INSERT INTO `users` (
  `id`, `first_name`, `last_name`, `email`, `password_hash`, `country`, `phone`, 
  `date_of_birth`, `gender`, `employment_status`, `annual_income`, `education_level`, 
  `role`, `status`, `balance_points`, `lifetime_earnings_usd`, `completed_surveys_count`, `profile_completion_pct`
) VALUES
(1, 'System', 'Administrator', 'admin@veritopinion.com', '$2y$10$ZA48YSa5IybOgCK.mA81VOKdoSyGiwr4i6jSVDAwMvY92ZXmphvoe', 'United States', '+1-555-0199', '1985-05-15', 'Male', 'Executive / Senior Manager', '$150,000+', 'Master\'s Degree', 'admin', 'active', 5000, 50.00, 10, 100),
(2, 'Alex', 'Morgan', 'member@veritopinion.com', '$2y$10$Zt9uUkJyGIHwQncveUbt3ev7ZSvzLha0neSLhNPhUBAopZnC0BTue', 'United States', '+1-555-0142', '1992-08-22', 'Female', 'Employed Full-Time', '$75,000 - $99,999', 'Bachelor\'s Degree', 'member', 'active', 1650, 16.50, 4, 85);

-- SEED REWARDS
INSERT INTO `rewards` (`id`, `title`, `category`, `points_required`, `cash_equivalent_usd`, `delivery_type`, `icon`, `is_active`) VALUES
(1, '$10 PayPal Cash Transfer', 'Cash & Crypto', 1000, 10.00, 'Direct PayPal Email Transfer (1-2 Days)', 'bi-paypal', 1),
(2, '$25 PayPal Cash Transfer', 'Cash & Crypto', 2500, 25.00, 'Direct PayPal Email Transfer (1-2 Days)', 'bi-paypal', 1),
(3, '$10 Amazon.com eGift Code', 'Gift Cards', 1000, 10.00, 'Instant Digital Gift Card Code via Email', 'bi-amazon', 1),
(4, '$25 Amazon.com eGift Code', 'Gift Cards', 2500, 25.00, 'Instant Digital Gift Card Code via Email', 'bi-amazon', 1),
(5, '$15 Apple Store & iTunes Code', 'Gift Cards', 1500, 15.00, 'Instant Digital Code for App Store & iTunes', 'bi-apple', 1),
(6, '$25 Virtual Visa Prepaid Card', 'Cash & Crypto', 2500, 25.00, 'Virtual Prepaid Card usable worldwide online', 'bi-credit-card-2-front', 1);

-- SEED SURVEYS
INSERT INTO `surveys` (`id`, `title`, `description`, `category`, `reward_points`, `reward_usd`, `duration_minutes`, `quota`, `completed_count`, `status`, `provider`, `sample_questions_json`) VALUES
(1, 'Smart Home & Connected Devices 2026', 'Share your usage habits with smart speakers, thermostats, home security systems, and IoT devices.', 'Technology', 250, 2.50, 8, 250, 84, 'active', 'Veritopinion Research Lab', '[{"q": "How many smart home devices do you actively use?", "type": "radio", "options": ["None", "1-3 devices", "4-7 devices", "8 or more"]}, {"q": "Which voice assistant ecosystem do you prefer most?", "type": "radio", "options": ["Amazon Alexa", "Google Assistant", "Apple Siri", "None"]}, {"q": "What is the primary factor when choosing smart home tech?", "type": "radio", "options": ["Data Privacy & Security", "Ease of Setup", "Price & Promotions", "Brand Trust"]}]'),
(2, 'Sustainable Consumer Goods & Grocery Habits', 'Evaluate eco-friendly packaging, grocery shopping frequency, plant-based alternatives, and household brands.', 'Consumer Insights', 180, 1.80, 6, 300, 126, 'active', 'EcoTrend Analytics', '[{"q": "How often do you deliberately choose sustainably packaged products?", "type": "radio", "options": ["Always", "Most of the time", "Occasionally", "Rarely or Never"]}, {"q": "Where do you do the majority of your weekly grocery shopping?", "type": "radio", "options": ["Supermarket / Hypermarket", "Organic / Specialty Grocer", "Online Delivery", "Local Farmers Market"]}]'),
(3, 'Streaming Entertainment & Subscription Trends', 'Evaluate film/series watching time, ad-supported tier preferences, and overall subscription value.', 'Media & Entertainment', 150, 1.50, 5, 400, 210, 'active', 'OmniMedia Metrics', '[{"q": "How many paid video streaming services does your household subscribe to?", "type": "radio", "options": ["1 service", "2-3 services", "4-5 services", "6 or more"]}, {"q": "Are you comfortable with cheaper subscription tiers that include commercials?", "type": "radio", "options": ["Yes, if the price difference is significant", "No, I strictly prefer ad-free experiences", "Indifferent"]}]'),
(4, 'Digital Banking & Mobile Payments Adoption', 'Feedback on fintech applications, contactless payment methods, fee structures, and credit monitoring.', 'Finance', 300, 3.00, 10, 150, 42, 'active', 'Apex Financial Research', '[{"q": "What is your primary method for everyday peer-to-peer money transfers?", "type": "radio", "options": ["Zelle", "Venmo / PayPal", "Apple Pay / Google Wallet", "Direct Bank Wire"]}, {"q": "How confident are you in biometric authentication (Face ID / Fingerprint) for mobile banking?", "type": "radio", "options": ["Extremely confident", "Moderately confident", "Slightly hesitant", "Prefer passwords only"]}]'),
(5, 'Remote Work Productivity & Wellness Study', 'How hybrid work arrangements impact team collaboration tools, ergonomics, work-life balance, and well-being.', 'Workplace', 220, 2.20, 7, 200, 189, 'active', 'FutureWork Global', '[{"q": "What best describes your current work setup?", "type": "radio", "options": ["100% Remote", "Hybrid (2-3 days in office)", "100% In-Office / On-Site", "Independent Freelancer / Contractor"]}]');

-- SEED TRANSACTIONS FOR DEMO MEMBER
INSERT INTO `transactions` (`user_id`, `type`, `title`, `points`, `amount_usd`, `status`, `created_at`) VALUES
(2, 'welcome_bonus', 'Welcome Bonus - Account Created', 500, 5.00, 'completed', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 'survey_reward', 'Completed Survey: Smart Home & Connected Devices', 250, 2.50, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'survey_reward', 'Completed Survey: Streaming Entertainment & Trends', 150, 1.50, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'survey_reward', 'Completed Survey: Digital Banking Adoption', 300, 3.00, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'survey_reward', 'Completed Survey: Remote Work Productivity', 220, 2.20, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'bonus', 'Profile Demographics Completion Bonus', 230, 2.30, 'completed', DATE_SUB(NOW(), INTERVAL 12 HOUR));

-- SEED SURVEY RESPONSES FOR DEMO MEMBER (Surveys 1, 3, 4, 5 completed)
INSERT INTO `survey_responses` (`survey_id`, `user_id`, `status`, `points_awarded`, `usd_awarded`, `completed_at`) VALUES
(1, 2, 'completed', 250, 2.50, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 2, 'completed', 150, 1.50, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 2, 'completed', 300, 3.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 2, 'completed', 220, 2.20, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- SEED NOTIFICATIONS FOR DEMO MEMBER
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(2, 'Welcome to Veritopinion!', 'Your account has been created. 500 Welcome Points have been credited to your wallet.', 'success', '/member/wallet.php', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 'New Survey Available', 'Smart Home & Connected Devices is now available in your surveys queue (250 pts).', 'survey', '/member/surveys.php', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'Survey Completed: +300 Points', 'Your responses for Digital Banking Adoption were validated and 300 points were added to your wallet.', 'success', '/member/wallet.php', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Cashout Unlocked!', 'Congratulations! You have over 1,000 points. You are eligible to redeem PayPal Cash or Gift Cards in the Rewards store.', 'reward', '/member/rewards.php', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- SEED SAMPLE SUPPORT TICKET
INSERT INTO `support_tickets` (`ticket_number`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `priority`, `admin_reply`, `created_at`) VALUES
('TCK-2026-0814', 2, 'Alex Morgan', 'member@veritopinion.com', 'Question about PayPal redemption timeline', 'Hello support team! When I redeem PayPal cash, does it credit within 24 hours or on business days only?', 'resolved', 'normal', 'Hello Alex, thank you for reaching out! PayPal transfers are processed within 1 to 2 business days. Please ensure your PayPal email matches your payout destination.', DATE_SUB(NOW(), INTERVAL 2 DAY));
