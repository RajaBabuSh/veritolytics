-- ==============================================================================
-- VERITOLYTICS - SQLITE FALLBACK SCHEMA
-- Used for zero-configuration standalone testing
-- ==============================================================================

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  pending_email TEXT DEFAULT NULL,
  email_verification_token TEXT DEFAULT NULL,
  email_verification_expires_at TEXT DEFAULT NULL,
  password_hash TEXT NOT NULL,
  country TEXT NOT NULL DEFAULT 'United States',
  state TEXT DEFAULT NULL,
  city TEXT DEFAULT NULL,
  phone TEXT DEFAULT NULL,
  date_of_birth TEXT DEFAULT NULL,
  gender TEXT DEFAULT NULL,
  avatar TEXT DEFAULT NULL,
  employment_status TEXT DEFAULT NULL,
  annual_income TEXT DEFAULT NULL,
  education_level TEXT DEFAULT NULL,
  role TEXT NOT NULL DEFAULT 'member',
  status TEXT NOT NULL DEFAULT 'active',
  balance_points INTEGER NOT NULL DEFAULT 0,
  lifetime_earnings_usd REAL NOT NULL DEFAULT 0.00,
  completed_surveys_count INTEGER NOT NULL DEFAULT 0,
  profile_completion_pct INTEGER NOT NULL DEFAULT 35,
  notification_preferences TEXT DEFAULT NULL,
  reset_token TEXT DEFAULT NULL,
  reset_expires_at TEXT DEFAULT NULL,
  deleted_at TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_users_deleted ON users (deleted_at);

CREATE TABLE IF NOT EXISTS surveys (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT,
  category TEXT NOT NULL DEFAULT 'Consumer Insights',
  reward_points INTEGER NOT NULL DEFAULT 100,
  reward_usd REAL NOT NULL DEFAULT 1.00,
  duration_minutes INTEGER NOT NULL DEFAULT 5,
  quota INTEGER NOT NULL DEFAULT 100,
  completed_count INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  provider TEXT NOT NULL DEFAULT 'Veritolytics Direct',
  sample_questions_json TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS survey_responses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  survey_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'completed',
  points_awarded INTEGER NOT NULL DEFAULT 0,
  usd_awarded REAL NOT NULL DEFAULT 0.00,
  answers_json TEXT DEFAULT NULL,
  completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(survey_id, user_id),
  FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rewards (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  category TEXT NOT NULL,
  points_required INTEGER NOT NULL,
  cash_equivalent_usd REAL NOT NULL,
  delivery_type TEXT NOT NULL DEFAULT 'Instant Digital Delivery',
  icon TEXT NOT NULL DEFAULT 'bi-gift',
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS redemptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  reward_id INTEGER DEFAULT NULL,
  reward_title TEXT NOT NULL,
  points_spent INTEGER NOT NULL,
  amount_usd REAL NOT NULL,
  payout_destination TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  admin_notes TEXT DEFAULT NULL,
  reviewed_by INTEGER DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  title TEXT NOT NULL,
  points INTEGER NOT NULL,
  amount_usd REAL NOT NULL,
  status TEXT NOT NULL DEFAULT 'completed',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  type TEXT NOT NULL DEFAULT 'info',
  link TEXT DEFAULT NULL,
  is_read INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS support_tickets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_number TEXT NOT NULL UNIQUE,
  user_id INTEGER DEFAULT NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  subject TEXT NOT NULL,
  message TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  priority TEXT NOT NULL DEFAULT 'normal',
  admin_reply TEXT DEFAULT NULL,
  admin_id INTEGER DEFAULT NULL,
  replied_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ticket_replies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  is_admin INTEGER NOT NULL DEFAULT 1,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sessions (
  id TEXT PRIMARY KEY,
  user_id INTEGER DEFAULT NULL,
  ip_address TEXT DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  data TEXT NOT NULL,
  last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions (last_activity);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions (user_id);

-- SEED DATA
INSERT OR IGNORE INTO users (id, first_name, last_name, email, password_hash, country, phone, date_of_birth, gender, employment_status, annual_income, education_level, role, status, balance_points, lifetime_earnings_usd, completed_surveys_count, profile_completion_pct)
VALUES 
(1, 'System', 'Administrator', 'admin@veritolytics.com', '$2y$10$swdq8ywEpnzd9TgqxQAHu.dp6n/whKucIHMKWAosbdE/M7nkAovjK', 'United States', '+1-555-0199', '1985-05-15', 'Male', 'Executive / Senior Manager', '$150,000+', 'Master''s Degree', 'admin', 'active', 5000, 50.00, 10, 100),
(2, 'Alex', 'Morgan', 'member@veritolytics.com', '$2y$10$MU3NL3tpULHqARBsuFG9ROd3aYkSX01tock/WYHEygBKZzZ5UhKBC', 'United States', '+1-555-0142', '1992-08-22', 'Female', 'Employed Full-Time', '$75,000 - $99,999', 'Bachelor''s Degree', 'member', 'active', 1650, 16.50, 4, 85);

INSERT OR IGNORE INTO rewards (id, title, category, points_required, cash_equivalent_usd, delivery_type, icon, is_active)
VALUES
(1, '$10 PayPal Cash Transfer', 'Cash & Crypto', 1000, 10.00, 'Direct PayPal Email Transfer (1-2 Days)', 'bi-paypal', 1),
(2, '$25 PayPal Cash Transfer', 'Cash & Crypto', 2500, 25.00, 'Direct PayPal Email Transfer (1-2 Days)', 'bi-paypal', 1),
(3, '$10 Amazon.com eGift Code', 'Gift Cards', 1000, 10.00, 'Instant Digital Gift Card Code via Email', 'bi-amazon', 1),
(4, '$25 Amazon.com eGift Code', 'Gift Cards', 2500, 25.00, 'Instant Digital Gift Card Code via Email', 'bi-amazon', 1),
(5, '$15 Apple Store & iTunes Code', 'Gift Cards', 1500, 15.00, 'Instant Digital Code for App Store & iTunes', 'bi-apple', 1),
(6, '$25 Virtual Visa Prepaid Card', 'Cash & Crypto', 2500, 25.00, 'Virtual Prepaid Card usable worldwide online', 'bi-credit-card-2-front', 1);

INSERT OR IGNORE INTO surveys (id, title, description, category, reward_points, reward_usd, duration_minutes, quota, completed_count, status, provider, sample_questions_json)
VALUES
(1, 'Smart Home & Connected Devices 2026', 'Share your usage habits with smart speakers, thermostats, home security systems, and IoT devices.', 'Technology', 250, 2.50, 8, 250, 84, 'active', 'Veritopinion Research Lab', '[{"q": "How many smart home devices do you actively use?", "type": "radio", "options": ["None", "1-3 devices", "4-7 devices", "8 or more"]}, {"q": "Which voice assistant ecosystem do you prefer most?", "type": "radio", "options": ["Amazon Alexa", "Google Assistant", "Apple Siri", "None"]}, {"q": "What is the primary factor when choosing smart home tech?", "type": "radio", "options": ["Data Privacy & Security", "Ease of Setup", "Price & Promotions", "Brand Trust"]}]'),
(2, 'Sustainable Consumer Goods & Grocery Habits', 'Evaluate eco-friendly packaging, grocery shopping frequency, plant-based alternatives, and household brands.', 'Consumer Insights', 180, 1.80, 6, 300, 126, 'active', 'EcoTrend Analytics', '[{"q": "How often do you deliberately choose sustainably packaged products?", "type": "radio", "options": ["Always", "Most of the time", "Occasionally", "Rarely or Never"]}, {"q": "Where do you do the majority of your weekly grocery shopping?", "type": "radio", "options": ["Supermarket / Hypermarket", "Organic / Specialty Grocer", "Online Delivery", "Local Farmers Market"]}]'),
(3, 'Streaming Entertainment & Subscription Trends', 'Evaluate film/series watching time, ad-supported tier preferences, and overall subscription value.', 'Media & Entertainment', 150, 1.50, 5, 400, 210, 'active', 'OmniMedia Metrics', '[{"q": "How many paid video streaming services does your household subscribe to?", "type": "radio", "options": ["1 service", "2-3 services", "4-5 services", "6 or more"]}, {"q": "Are you comfortable with cheaper subscription tiers that include commercials?", "type": "radio", "options": ["Yes, if the price difference is significant", "No, I strictly prefer ad-free experiences", "Indifferent"]}]'),
(4, 'Digital Banking & Mobile Payments Adoption', 'Feedback on fintech applications, contactless payment methods, fee structures, and credit monitoring.', 'Finance', 300, 3.00, 10, 150, 42, 'active', 'Apex Financial Research', '[{"q": "What is your primary method for everyday peer-to-peer money transfers?", "type": "radio", "options": ["Zelle", "Venmo / PayPal", "Apple Pay / Google Wallet", "Direct Bank Wire"]}, {"q": "How confident are you in biometric authentication (Face ID / Fingerprint) for mobile banking?", "type": "radio", "options": ["Extremely confident", "Moderately confident", "Slightly hesitant", "Prefer passwords only"]}]'),
(5, 'Remote Work Productivity & Wellness Study', 'How hybrid work arrangements impact team collaboration tools, ergonomics, work-life balance, and well-being.', 'Workplace', 220, 2.20, 7, 200, 189, 'active', 'FutureWork Global', '[{"q": "What best describes your current work setup?", "type": "radio", "options": ["100% Remote", "Hybrid (2-3 days in office)", "100% In-Office / On-Site", "Independent Freelancer / Contractor"]}]');

INSERT OR IGNORE INTO transactions (id, user_id, type, title, points, amount_usd, status, created_at)
VALUES
(1, 2, 'welcome_bonus', 'Welcome Bonus - Account Created', 500, 5.00, 'completed', datetime('now', '-7 days')),
(2, 2, 'survey_reward', 'Completed Survey: Smart Home & Connected Devices', 250, 2.50, 'completed', datetime('now', '-5 days')),
(3, 2, 'survey_reward', 'Completed Survey: Streaming Entertainment & Trends', 150, 1.50, 'completed', datetime('now', '-3 days')),
(4, 2, 'survey_reward', 'Completed Survey: Digital Banking Adoption', 300, 3.00, 'completed', datetime('now', '-2 days')),
(5, 2, 'survey_reward', 'Completed Survey: Remote Work Productivity', 220, 2.20, 'completed', datetime('now', '-1 day')),
(6, 2, 'bonus', 'Profile Demographics Completion Bonus', 230, 2.30, 'completed', datetime('now', '-12 hours'));

INSERT OR IGNORE INTO survey_responses (survey_id, user_id, status, points_awarded, usd_awarded, completed_at)
VALUES
(1, 2, 'completed', 250, 2.50, datetime('now', '-5 days')),
(3, 2, 'completed', 150, 1.50, datetime('now', '-3 days')),
(4, 2, 'completed', 300, 3.00, datetime('now', '-2 days')),
(5, 2, 'completed', 220, 2.20, datetime('now', '-1 day'));

INSERT OR IGNORE INTO notifications (id, user_id, title, message, type, link, is_read, created_at)
VALUES
(1, 2, 'Welcome to Veritopinion!', 'Your account has been created. 500 Welcome Points have been credited to your wallet.', 'success', '/member/wallet.php', 1, datetime('now', '-7 days')),
(2, 2, 'New Survey Available', 'Smart Home & Connected Devices is now available in your surveys queue (250 pts).', 'survey', '/member/surveys.php', 1, datetime('now', '-5 days')),
(3, 2, 'Survey Completed: +300 Points', 'Your responses for Digital Banking Adoption were validated and 300 points were added to your wallet.', 'success', '/member/wallet.php', 0, datetime('now', '-2 days')),
(4, 2, 'Cashout Unlocked!', 'Congratulations! You have over 1,000 points. You are eligible to redeem PayPal Cash or Gift Cards in the Rewards store.', 'reward', '/member/rewards.php', 0, datetime('now', '-1 day'));

INSERT OR IGNORE INTO support_tickets (id, ticket_number, user_id, name, email, subject, message, status, priority, admin_reply, created_at)
VALUES
(1, 'TCK-2026-0814', 2, 'Alex Morgan', 'member@veritolytics.com', 'Question about PayPal redemption timeline', 'Hello support team! When I redeem PayPal cash, does it credit within 24 hours or on business days only?', 'resolved', 'normal', 'Hello Alex, thank you for reaching out! PayPal transfers are processed within 1 to 2 business days. Please ensure your PayPal email matches your payout destination.', datetime('now', '-2 days'));
