# VERITOLYTICS — PURE CORE PHP & MYSQL REWARDS PLATFORM
**Version:** 1.0.0  
**Stack:** Pure Core PHP 8.2+, MySQL 8.0+ / MariaDB, HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5.3  
**Architecture:** Zero-framework, MVC-inspired modular structure. No React, No Vite, No Node.js, No Laravel.

---

## 🚀 Quick Start with XAMPP on Windows

### Step 1: Copy Project to XAMPP htdocs
Copy the entire `Veritolytics-PHP` folder into your XAMPP web root:
```
C:\xampp\htdocs\Veritolytics-PHP
```

---

### Step 2: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

---

### Step 3: Import the MySQL Database
1. Open your web browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Databases** tab.
3. Enter database name: `veritolytics_db` and click **Create** (Collation: `utf8mb4_unicode_ci`).
4. Click on the newly created `veritolytics_db` database in the left sidebar.
5. Click on the **Import** tab at the top.
6. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\Veritolytics-PHP\database\database.sql
   ```
7. Click **Import** (or **Go**) at the bottom.
8. All 8 tables (`users`, `surveys`, `survey_responses`, `rewards`, `redemptions`, `transactions`, `notifications`, `support_tickets`) and seed data will be imported instantly.

---

### Step 4: Open Veritolytics in Browser
Navigate to:
```
http://localhost/Veritolytics-PHP/
```

---

## 🔑 Pre-Configured Demo Credentials

> [!NOTE]
> All seed records are realistic demonstration data clearly designated for staging and verification.

| Portal | Email | Password | Role | Features |
| :--- | :--- | :--- | :--- | :--- |
| **Member Portal** | `member@veritolytics.com` | `Member@12345` | Member | Overview, 1,650 points balance, surveys, rewards store, cashout requests, demographics |
| **Admin Portal** | `admin@veritolytics.com` | `Admin@12345` | Administrator | KPI counters, user management, survey manager, redemption approval queue, ledger audit |

---

## ⚙️ Configuration & Environment Variables

Database configuration is centralized in [`config/database.php`](config/database.php).  
By default, standard XAMPP settings are used:
- **Host:** `127.0.0.1`
- **Port:** `3306`
- **Database:** `veritolytics_db`
- **Username:** `root`
- **Password:** `""` (empty)

If you are deploying to cPanel, Plesk, or a VPS with environment variables:
```bash
export DB_HOST="your-db-host"
export DB_PORT="3306"
export DB_NAME="your-db-name"
export DB_USER="your-db-user"
export DB_PASS="your-db-password"
```

### Zero-Configuration Standalone Testing (SQLite Fallback)
If the MySQL daemon is not running on port 3306, the database manager automatically initializes a standalone SQLite database at `database/veritolytics.sqlite` using `database/schema_sqlite.sql`. This allows running the platform instantly with PHP's built-in web server:
```bash
cd C:\xampp\htdocs\Veritolytics-PHP
php -S localhost:8000
```
Then visit `http://localhost:8000/`.

---

## 🛡️ Security Architecture

1. **Prepared Statements (PDO):** Every query uses parameterized PDO prepared statements to guarantee complete immunity from SQL Injection.
2. **Session Security:** 
   - `session_regenerate_id(true)` prevents session fixation attacks upon login.
   - Session cookies utilize `HttpOnly`, `SameSite=Lax`, and `Secure` (when on HTTPS).
3. **Password Security:** All passwords are encrypted with `password_hash($pwd, PASSWORD_DEFAULT)` and verified via `password_verify()`.
4. **CSRF Protection:** Every POST submission incorporates unique cryptographic tokens (`generate_csrf_token()`) validated by `hash_equals()`.
5. **Output Escaping:** Strict HTML character escaping (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) eliminates XSS vulnerabilities.
6. **Role-Based Access Control (RBAC):** Strict gatekeepers (`require_login()`, `require_admin()`, `require_guest()`) separate member and administrator spaces.

---

## 📂 Project Directory Structure

```text
Veritolytics-PHP/
├── admin/                     # Administrator Management Console
│   ├── dashboard.php          # Real-time KPIs & rapid redemption review queue
│   ├── login.php              # Dedicated admin sign-in
│   ├── logout.php             # Admin session sign-out
│   ├── rewards.php            # Store catalog & payout approvals/rejections
│   ├── support.php            # Support tickets helpdesk inbox & replies
│   ├── surveys.php            # Survey creator, quota progress & status toggler
│   ├── transactions.php       # Master financial ledger audit trail
│   └── users.php              # Member directory, balance adjustments & status
├── assets/
│   ├── css/style.css          # Veritolytics custom brand palette & responsive CSS
│   ├── images/                # Brand assets (veritolytics-logo.png)
│   └── js/main.js             # Chatbot engine, profile score recalculator, togglers
├── auth/                      # Public Authentication Suite
│   ├── forgot-password.php    # Password recovery token generator
│   ├── login.php              # Member login with CSRF & session fixation protection
│   ├── logout.php             # Member session termination with confirmation
│   ├── register.php           # Registration with instant 500 welcome bonus
│   └── reset-password.php     # Token-verified password reset form
├── config/
│   ├── app.php                # Platform constants, 100 pts = $1.00 rule, session init
│   └── database.php           # PDO connection engine (MySQL + SQLite fallback)
├── database/
│   ├── database.sql           # MySQL 8.0+ / MariaDB schema with seeds
│   └── schema_sqlite.sql      # SQLite fallback schema
├── includes/
│   ├── admin_footer.php       # Admin layout footer
│   ├── admin_header.php       # Admin layout top bar
│   ├── admin_sidebar.php      # Admin navigation
│   ├── auth_check.php         # RBAC checks (require_login, require_admin)
│   ├── chatbot.php            # Floating Intercom-style customer support bot
│   ├── csrf.php               # CSRF token generator & validator
│   ├── footer.php             # Public footer
│   ├── functions.php          # Core helpers (points format, ledger, flash alerts)
│   ├── header.php             # Public top navbar
│   ├── member_footer.php      # Member layout footer
│   ├── member_header.php      # Member balance pill, notifications, profile menu
│   └── member_sidebar.php      # Member navigation (Overview, Surveys, My Rewards, Settings, Logout)
├── member/                    # Authenticated Member Dashboard
│   ├── dashboard.php          # Overview, points balance, recommended studies
│   ├── mark_notifications.php # Quick notification read marker
│   ├── profile.php            # Editable demographics & real-time completion score
│   ├── redeem_reward.php      # Payout submission handler & balance deduction
│   ├── rewards.php            # My Rewards store & redemption history table
│   ├── settings.php           # Password updater, notification toggles, logout
│   ├── surveys.php            # Category-filtered surveys & participation modal
│   ├── take_survey.php        # Survey submission handler & points crediting
│   └── wallet.php             # Points wallet, lifetime earnings & transaction ledger
├── uploads/                   # Media & file attachments
├── index.php                  # Public landing page (Hero, About, How It Works, FAQ, Contact)
├── submit_ticket.php          # Support ticket submission processor
└── README.md                  # Comprehensive setup & operations documentation
```

---

## 💬 Floating Support Chatbot
The floating chatbot appears on all pages in the bottom-right corner (`fixed bottom: 24px; right: 24px;`). It features:
- Pre-programmed FAQ answers for **Survey Help**, **Points & Wallet**, **Rewards & Payouts**, and **Account & Login**.
- Quick reply action pills.
- One-click trigger to submit an official support ticket to the database (`support_tickets` table) for human review.
