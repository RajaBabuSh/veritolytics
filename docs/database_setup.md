# Veritolytics – Database Setup & Schema Guide

This guide details how to initialize and configure the **MySQL** database for both local development (XAMPP / WAMP) and cloud hosting (Railway, TiDB Cloud, Aiven, PlanetScale, AWS RDS).

---

## 1. Database Files in Repository

- [`database/database.sql`](../database/database.sql): Complete MySQL DDL schema and safe, realistic demo data (users, surveys, rewards, transactions, notifications, and support tickets).
- [`database/schema_backup_safe.sql`](../database/schema_backup_safe.sql): Clean DDL schema with catalog items (rewards, surveys) without user accounts.
- [`database/schema_sqlite.sql`](../database/schema_sqlite.sql): SQLite schema for local standalone fallback testing.

---

## 2. Setting Up in XAMPP (Local Development)

### Method A: Via phpMyAdmin Web Interface
1. Launch **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open your browser and navigate to:
   `http://localhost/phpmyadmin/`
3. Click **New** in the left sidebar.
4. Set Database name to: `veritolytics_db`
5. Select Collation: `utf8mb4_unicode_ci` and click **Create**.
6. Select `veritolytics_db`, click the **Import** tab at the top.
7. Click **Choose File**, select `database/database.sql`, and click **Import**.

### Method B: Via Command Line (MySQL CLI)
```bash
# If MySQL has no root password (standard XAMPP):
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS veritolytics_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root veritolytics_db < database/database.sql
```

---

## 3. Demo Login Credentials

The `database/database.sql` file seeds the following demonstration accounts:

| Portal | Email Address | Password | Role | Description |
| :--- | :--- | :--- | :--- | :--- |
| **Admin Portal** | `admin@veritopinion.com`<br>*(or `admin@veritolytics.com`)* | `Admin@12345` | Administrator | Full administrative dashboard, survey creation, member management, redemptions, and support tickets. |
| **Member Portal** | `member@veritopinion.com`<br>*(or `member@veritolytics.com`)* | `Member@12345` | Member | Active member portal with sample survey history, points balance, and rewards. |

> **Note:** The authentication system automatically accepts both `@veritopinion.com` and `@veritolytics.com` handles interchangeably.

---

## 4. Key Database Tables

| Table | Purpose |
| :--- | :--- |
| `users` | Member and administrator accounts with bcrypt password hashes, points balance, and demographic profile. |
| `surveys` | Market research surveys, reward values, quotas, status, and JSON question definitions. |
| `survey_responses` | Member survey completions, audit answers, and timestamps. |
| `rewards` | Redemption store catalog (PayPal, Amazon, Apple, Visa prepaid cards). |
| `redemptions` | Member cash-out requests, audit trail, reviewer admin ID, and status (`pending`, `approved`, `rejected`). |
| `transactions` | Ledger recording points earned, bonus awards, and redemption deductions. |
| `support_tickets` | Member support requests, priority, status, and resolution details. |
| `ticket_replies` | Threaded customer support messages and administrator responses. |
| `notifications` | In-app member alerts and notifications. |
| `php_sessions` | Centralized database-backed session storage for seamless multi-instance / serverless persistence. |
