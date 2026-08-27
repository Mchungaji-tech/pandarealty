# 🐼 Panda Realty — Luxury Real Estate Web Application & CRM Management System

> **"We don't just sell property — we change lives."**  
> Built for **Perpetuah Realtor** (Your Eldoret Property Expert 🔑 — Homes • Land • Investments).  
> **Designed & Developed by [TekTrend Technologies](https://tektrend.co.ke)**

---

## 📖 Architecture & Data Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          MySQL Database Engine                          │
│                      Database: `pandareality_db`                        │
│   (users, properties, crm_deals, inquiries, site_visits, invoices...)  │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ Procedural MySQLi Connection
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      Procedural PHP Backend Layer                       │
│  - config/db.php          : Safe environment loading & connection pool  │
│  - config/functions.php   : Global output buffering, auth, file uploads │
│  - config/totp.php        : Google 2FA MFA RFC 6238 TOTP engine        │
│  - config/settings.php    : Dynamic brand, currency, CMS loader        │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
         ┌───────────────────────────┴───────────────────────────┐
         ▼                                                       ▼
┌───────────────────────────────────┐   ┌───────────────────────────────────┐
│       Front-End Web Platform      │   │     Staff & Admin CRM Suite       │
│ - Homepage & Hero Featured Slider │   │ - Real Estate CRM & Deal Pipeline │
│ - Studio Apartments & Land Filter │   │ - Property Inventory CRUD         │
│ - Live Currency Switch (KSh/USD)  │   │   (3 Image Uploads + 1 Video URL) │
│ - TikTok/Reels & Cinema Split View│   │ - Deal Inspector with Video Modal │
│ - Client Property Listing Portal  │   │ - Invoicing & Installment Engine  │
│ - Fullscreen Lightbox & Toasts    │   │ - Super Admin CMS & Audit Trail   │
└───────────────────────────────────┘   └───────────────────────────────────┘
```

---

## 🔑 Testing Credentials & Role Summary

| Role | Name | Email Address | Password | Dedicated Access Portal |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin 1** | Super Administrator | `superadmin@pandarealty.co.ke` | `SuperAdmin@2026!` | [`admin/login.php`](http://localhost/pandareality/admin/login.php) |
| **Super Admin 2** | Perpetuah Chepchirchir | `perpetuah@pandarealty.co.ke` | `Perpetuah@2026!` | [`admin/login.php`](http://localhost/pandareality/admin/login.php) |
| **Technical Admin** | TekTrend Admin | `admin@tektrend.co.ke` | `Admin@2026!` | [`admin/login.php`](http://localhost/pandareality/admin/login.php) |
| **Client / User** | King Tim | `test1@gmail.com` | `GoogleAuth@2026!` | [`login.php`](http://localhost/pandareality/login.php) |

---

## 🌟 Key Features

### 1. 📱 TikTok / Reels Scrolling & Cinema Split View ([`videos.php`](http://localhost/pandareality/videos.php))
- **Mode 1: TikTok / Reels Feed**: Vertical full-screen snap-scrolling card stream (`scroll-snap-type: y mandatory`) with overlay property price (KES/USD), verified badge, 1-click WhatsApp chat, and like button.
- **Mode 2: Cinema Split View**: High-definition video player on the left column with property details, asking price, tour booking form, and Perpetuah realtor contact box on the right.

### 2. 📸 3 Property Image Uploads + 1 Single Video URL
- Available across **Admin Add Property** ([`admin/property-add.php`](http://localhost/pandareality/admin/property-add.php)), **Admin Edit Property** ([`admin/property-edit.php`](http://localhost/pandareality/admin/property-edit.php)), and **Client Listing Portal** ([`list-property.php`](http://localhost/pandareality/list-property.php)):
  - **Image 1**: Main Cover Photo (File Upload + URL fallback)
  - **Image 2**: Living Room / Interior (File Upload + URL fallback)
  - **Image 3**: Aerial / Compound / Plot Angle (File Upload + URL fallback)
  - **Video URL**: 1 single link field for YouTube, TikTok, Facebook, or MP4 video.

### 3. 💼 Executive CRM & Deal Pipeline with Video Modal ([`admin/crm.php`](http://localhost/pandareality/admin/crm.php))
- Multi-stage deal board (*New Inquiries*, *Contacted*, *Site Tour Scheduled*, *In Negotiation*, *Closed/Won*, *Lost*).
- One-click WhatsApp & call buttons.
- Direct **Watch Property Tour Video** modal attached to each client opportunity.

### 4. 🎨 Form Styling
- Polished form inputs across front-end and admin dashboards with gold focus rings, high-contrast labels, file selector badges, and smooth error/success feedback.

---

## 🚀 Quick Setup (XAMPP)

1. **Location**: Ensure the folder is in `c:\xampp\htdocs\pandareality\`.
2. **Start Services**: Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. **Access the Application**:
   - Website: [`http://localhost/pandareality/`](http://localhost/pandareality/)
   - Video Library & TikTok Reels: [`http://localhost/pandareality/videos.php`](http://localhost/pandareality/videos.php)
   - Staff & Admin Portal: [`http://localhost/pandareality/admin/login.php`](http://localhost/pandareality/admin/login.php)
   - Real Estate CRM: [`http://localhost/pandareality/admin/crm.php`](http://localhost/pandareality/admin/crm.php)
   - Client Listing Portal: [`http://localhost/pandareality/list-property.php`](http://localhost/pandareality/list-property.php)
