# Veritopinion – Platform Rebrand Specifications

This report documents the platform-wide rebrand from **Veritolytics** to **Veritopinion** across all public interfaces, member features, administrative consoles, automated emails, and database records.

---

## 1. Brand Transformation Summary

- **Primary Brand Name**: `Veritopinion`
- **Core Tagline**: *"Your Voice. Your Opinions. Your Impact."*
- **Visual Identity**: Geometric typography with an interlocking orange `o` and navy `p` emblem, accompanied by the iconic royal blue 3D badge and midnight navy `EST. 2017` heritage badge.
- **Support Communication**: `support@veritopinion.com` / `Response from Veritopinion Operations Desk`
- **Virtual Assistant**: `Veritopinion Assistant`

---

## 2. Platform-Wide Updates Applied

### A. Core Configuration & Constants
- `APP_NAME` set to `Veritopinion` in [`config/app.php`](../config/app.php) and [`config/app.example.php`](../config/app.example.php).
- `APP_TAGLINE` updated to `Your Voice. Your Opinions. Your Impact.`.
- Economy settings preserved: 100 points = $1.00 USD, 1,000 points minimum payout ($10.00), 500 Welcome Bonus points ($5.00).

### B. Public Interfaces & Navigation
- Navbar brand logo updated to `veritopinion-logo.png` with accessible label `Veritopinion`.
- Browser favicons updated to `veritopinion-icon.png`.
- Landing page copy updated across all sections (hero, features, survey categories, testimonials, and FAQs).

### C. Member Portal & Operations
- Member header and offcanvas navigation dynamically render `veritopinion-logo.png`.
- Welcome notifications and points bonus messages branded with Veritopinion.
- Support replies branded as `Veritopinion Operations Desk`.

### D. Identity & Alias Matching
- Both [`auth/login.php`](../auth/login.php) and [`admin/login.php`](../admin/login.php) include seamless automatic alias resolution between `@veritopinion.com` and `@veritolytics.com`. Users can log in using either domain seamlessly.
