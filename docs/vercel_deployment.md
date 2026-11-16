# Veritolytics – Vercel & Cloud Deployment Guide

This guide details how to deploy the **Veritolytics / Veritopinion** Core PHP + MySQL application on **Vercel** or container-based cloud platforms (Railway, Render, Fly.io, AWS ECS).

---

## 1. Architecture Overview

Vercel functions primarily as a serverless platform. Traditional PHP file sessions can lose state across serverless invocations. To provide ultra-low latency, full Core PHP 8.2+ performance, and reliable session persistence, the repository includes a cloud-native deployment configuration:

- **Runtime**: **FrankenPHP** (Caddy Web Server with modern PHP 8.2 embedding).
- **Session Handler**: `DatabaseSessionHandler` stores user and admin sessions directly in MySQL (`php_sessions` table).
- **Static Assets**: Automatically served with gzip/zstd compression and long-lived cache headers.

---

## 2. Included Deployment Files

- [`Dockerfile.vercel`](../Dockerfile.vercel): Docker build configuration based on `dunglas/frankenphp:latest-php8.2-alpine`.
- [`vercel.json`](../vercel.json): Vercel configuration specifying custom container builds and route redirects.
- [`Caddyfile`](../Caddyfile): Production Caddy configuration managing rewrite rules and security headers.
- [`.dockerignore`](../.dockerignore): Prevents local artifacts and secrets from being copied into the container image.

---

## 3. Environment Variables Configuration

Set these environment variables in your Vercel Project Dashboard (**Settings > Environment Variables**) or in your `.env` file:

```ini
# Database Connection (Single URL format supported by Railway, Supabase, PlanetScale)
DATABASE_URL=mysql://user:password@host:port/database

# Or Split Variables:
DB_HOST=your-mysql-host.com
DB_PORT=3306
DB_NAME=veritolytics_db
DB_USER=your_db_username
DB_PASS=your_db_password

# Application Settings
APP_ENV=production
APP_DEBUG=false
```

---

## 4. Deployment Steps via Vercel CLI

1. Ensure the Vercel CLI is installed:
   ```bash
   npm install -g vercel
   ```
2. Link your repository:
   ```bash
   vercel link
   ```
3. Set your production environment variables:
   ```bash
   vercel env add DATABASE_URL production
   ```
4. Deploy to production:
   ```bash
   vercel --prod
   ```

---

## 5. Session & CSRF Continuity Verification

Because sessions are backed by the database:
- Admin and Member sessions remain active across serverless cold starts.
- Form CSRF tokens are validated reliably against the user's session record in MySQL.
- Strict session cookie flags (`Secure`, `HttpOnly`, `SameSite=Lax`) are automatically configured.
