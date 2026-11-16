# Veritolytics & Veritopinion – Core PHP & MySQL Platform

**Version:** 1.0.0  
**Stack:** Core PHP 8.2+, MySQL 8.0+ / MariaDB, Bootstrap 5.3, Vanilla JavaScript, HTML5, CSS3  
**Architecture:** Zero-framework, MVC-inspired modular architecture with database-backed PHP session persistence, CSRF security, and responsive UI.

---

## 🚀 Quick Setup with XAMPP (Local Development)

### Step 1: Copy Project to XAMPP htdocs
Copy or clone the project folder into your XAMPP web root directory:
- **Windows:** `C:\xampp\htdocs\veritolytics`
- **macOS:** `/Applications/XAMPP/xamppfiles/htdocs/veritolytics`
- **Linux:** `/opt/lampp/htdocs/veritolytics`

---

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

---

### Step 3: Create & Import the Database
1. Open your browser and navigate to phpMyAdmin:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** in the left sidebar to create a new database.
3. Enter database name: `veritolytics_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click **Create**.
4. Select `veritolytics_db` in the sidebar and click the **Import** tab.
5. Click **Choose File** and select:
   ```
   database/database.sql
   ```
6. Click **Import** at the bottom.
   *(Alternatively, via command line: `mysql -u root -p veritolytics_db < database/database.sql`)*

---

### Step 4: Access Application in Browser
Navigate to:
```
http://localhost/veritolytics/
```

---

## 🔑 Pre-Configured Demo Credentials

The included `database/database.sql` file seeds the following demonstration accounts:

| Portal | Email Address | Password | Role | Features |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Portal** | `admin@veritopinion.com`<br>*(or `admin@veritolytics.com`)* | `Admin@12345` | Administrator | KPI counters, survey creator, member directory, points adjustments, redemption approval queue, ledger audit trail, and customer support ticket replies. |
| **Member Portal** | `member@veritopinion.com`<br>*(or `member@veritolytics.com`)* | `Member@12345` | Member | Live 1,650 points balance ($16.50 USD), interactive survey participation, rewards catalog (PayPal, Amazon, Apple, Visa), wallet transactions, and profile settings. |

> **Note:** The authentication system automatically accepts both `@veritopinion.com` and `@veritolytics.com` domains interchangeably.

---

## ⚙️ Configuration & Environment Settings

The database connection is managed centrally in [`config/database.php`](config/database.php).  
By default, standard local XAMPP settings work out of the box:
- **Host:** `127.0.0.1`
- **Port:** `3306`
- **Database:** `veritolytics_db`
- **Username:** `root`
- **Password:** `""` (empty)

If you are deploying to a server with custom environment variables, copy `.env.example` to `.env`:
```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=veritolytics_db
DB_USER=root
DB_PASS=your_password_here
```

Cloud database URLs (`DATABASE_URL`, `MYSQL_URL`) from providers such as Railway, PlanetScale, TiDB Cloud, and AWS RDS are automatically recognized.

---

## 🛡️ Security Architecture

1. **Database-Backed Sessions:** Custom `DatabaseSessionHandler` stores user sessions directly in MySQL (`php_sessions` table) to guarantee zero session loss across Vercel serverless requests or multi-container clusters.
2. **Strict CSRF Validation:** Every state-changing form submission requires a cryptographically random CSRF token verified via `hash_equals()`.
3. **Bcrypt Password Security:** All user credentials use salted bcrypt hashes (`password_hash()`) and are verified via `password_verify()`.
4. **Parameterized PDO Queries:** Complete immunity from SQL injection via prepared statements with bound parameters.
5. **Output Escaping:** Strict sanitization (`htmlspecialchars()`) prevents cross-site scripting (XSS).
6. **Role-Based Access Control (RBAC):** Strict gatekeepers (`require_login()`, `require_admin()`, `require_guest()`) separate administrative and member spaces.

---

## 📂 Project Directory Structure

```text
veritolytics/
├── admin/                     # Administrative Management Console
│   ├── dashboard.php          # Real-time KPIs & rapid redemption review queue
│   ├── login.php              # Dedicated admin sign-in
│   ├── logout.php             # Admin session sign-out
│   ├── rewards.php            # Store catalog & payout approvals/rejections
│   ├── support.php            # Support tickets helpdesk inbox & replies
│   ├── surveys.php            # Survey creator, quota progress & status toggler
│   ├── transactions.php       # Master financial ledger audit trail
│   └── users.php              # Member directory, balance adjustments & status
├── assets/                    # Production Static Assets
│   ├── css/style.css          # Brand styles & responsive layout rules
│   ├── images/                # Brand graphics, logos, favicons, and hero backgrounds
│   └── js/main.js             # Chatbot engine, profile score recalculator, togglers
├── auth/                      # Public Authentication Suite
│   ├── forgot-password.php    # Password recovery token generator
│   ├── login.php              # Member login with CSRF & session fixation protection
│   ├── logout.php             # Member session termination with confirmation
│   ├── register.php           # Registration with instant 500 welcome bonus
│   └── reset-password.php     # Token-verified password reset form
├── config/                    # Application Configuration
│   ├── app.php                # Platform constants, 100 pts = $1.00 rule, session bootstrap
│   ├── database.php           # Universal PDO connection manager (MySQL + SQLite fallback)
│   ├── app.example.php        # Safe config template
│   └── database.example.php   # Safe database config template
├── database/                  # Database Schemas & Migrations
│   ├── database.sql           # Full MySQL schema + clean demo seed data
│   ├── schema_backup_safe.sql # Clean DDL schema without user records
│   └── schema_sqlite.sql      # SQLite fallback schema
├── design-assets/             # Master Branding & Graphic Assets
│   └── branding/              # High-res logos, icons, vector SVG, and supersampled renders
├── docs/                      # Technical Documentation & Design Guides
│   ├── database_setup.md      # Detailed database setup guide
│   ├── vercel_deployment.md   # Cloud & Vercel deployment documentation
│   ├── veritolytics_login_redesign.md # Login redesign documentation
│   ├── veritopinion_logo_suite.md     # Official logo suite specifications
│   ├── veritopinion_rebrand.md        # Platform rebrand documentation
│   └── templates/
│       └── login-split-screen.php     # Standalone 69/31 split-screen login template
├── includes/                  # Security Helpers & Layout Templates
│   ├── admin_footer.php       # Admin layout footer
│   ├── admin_header.php       # Admin layout top bar
│   ├── admin_sidebar.php      # Admin navigation sidebar
│   ├── auth_check.php         # RBAC checks (require_login, require_admin)
│   ├── chatbot.php            # Floating customer support bot
│   ├── csrf.php               # CSRF token generator & validator
│   ├── DatabaseSessionHandler.php # MySQL/PDO custom session handler
│   ├── footer.php             # Public footer
│   ├── functions.php          # Core helpers (points format, ledger, flash alerts)
│   ├── header.php             # Public top navbar
│   ├── member_footer.php      # Member layout footer
│   ├── member_header.php      # Member balance pill, notifications, profile menu
│   └── member_sidebar.php     # Member navigation sidebar
├── member/                    # Authenticated Member Dashboard
│   ├── dashboard.php          # Overview, points balance, recommended studies
│   ├── mark_notifications.php # Quick notification read marker
│   ├── notifications.php      # Member in-app notifications
│   ├── profile.php            # Editable demographics & real-time completion score
│   ├── redeem_reward.php      # Payout submission handler & balance deduction
│   ├── rewards.php            # Rewards store & redemption history table
│   ├── settings.php           # Password updater, notification toggles, logout
│   ├── support.php            # Member support ticket desk
│   ├── surveys.php            # Category-filtered surveys & participation modal
│   ├── take_survey.php        # Survey submission handler & points crediting
│   └── wallet.php             # Points wallet, lifetime earnings & transaction ledger
├── uploads/                   # User Uploads & Fallback Sessions
│   ├── avatars/               # User avatar storage (.gitkeep)
│   └── sessions/              # Fallback session cache (.gitkeep)
├── .dockerignore              # Docker build exclusions
├── .env.example               # Safe environment variable configuration template
├── .gitignore                 # Git ignore rules (protects credentials and binary databases)
├── Caddyfile                  # Production web server configuration
├── Dockerfile.vercel          # Containerized cloud runtime (FrankenPHP)
├── index.php                  # Public landing page (Hero, Features, Workflows, FAQ)
├── submit_ticket.php          # Support ticket submission endpoint
├── vercel.json                # Vercel deployment configuration
└── README.md                  # Comprehensive setup & operations documentation
```

---

## ☁️ Cloud & Vercel Deployment

The project is preconfigured for deployment on **Vercel** and container platforms using **FrankenPHP** (`dunglas/frankenphp:latest-php8.2-alpine`):
- Deployment configuration: [`vercel.json`](vercel.json)
- Container build: [`Dockerfile.vercel`](Dockerfile.vercel)
- Web server routing: [`Caddyfile`](Caddyfile)
- Full cloud deployment guide: [`docs/vercel_deployment.md`](docs/vercel_deployment.md)

---

## 💬 Floating Support Chatbot
The interactive chatbot is available across all pages in the bottom-right corner. It features pre-programmed answers for common inquiries and offers a 1-click escalation to submit an official support ticket directly into the database.
