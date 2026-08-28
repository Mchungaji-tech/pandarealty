# Panda Realty — Official cPanel Deployment Guide

**Target Server Path**: `/home/tektxbzg/public_html/pandarealty` (Subfolder: `/pandarealty`)  
**cPanel Account Username**: `tektxbzg`

---

## 🔑 Exact MySQL Database Credentials to Create in cPanel

| Field | Exact Value to Enter | What cPanel Shows |
| :--- | :--- | :--- |
| **Database Name** | `pandarealty` | `tektxbzg_pandarealty` |
| **Database Username** | `pandarealty` | `tektxbzg_pandarealty` |
| **Database Password** | `PandaRealty#2026!SecureDb` | *(Strong / 100% Strength)* |
| **Database Host** | `localhost` | `localhost` |
| **Privileges** | **ALL PRIVILEGES** | `ALL PRIVILEGES` |

---

## 📋 4 Step-by-Step Instructions (Subfolder: `/pandarealty`)

### Step 1: Create Database & User in cPanel
1. Log into your **cPanel** and click **MySQL® Databases**.
2. **Create New Database**:
   - In "New Database", enter: `pandarealty`
   - Click **Create Database**. *(Result: `tektxbzg_pandarealty`)*.
3. **Add New User**:
   - Username: `pandarealty` *(Result: `tektxbzg_pandarealty`)*
   - Password: `PandaRealty#2026!SecureDb`
   - Click **Create User**.
4. **Add User To Database**:
   - Select User: `tektxbzg_pandarealty`
   - Select Database: `tektxbzg_pandarealty`
   - Click **Add** $\rightarrow$ Check **ALL PRIVILEGES** $\rightarrow$ Click **Make Changes**.

---

### Step 2: Import Database Tables in phpMyAdmin
1. In cPanel, click **phpMyAdmin**.
2. In the left sidebar, click **`tektxbzg_pandarealty`**.
3. Click the **Import** tab at the top.
4. Click **Choose File**, select `database.sql`, and click **Import** (or **Go**).
   *(All 13 tables, admin users, properties, and settings import immediately).*

---

### Step 3: Upload Project Files to `/public_html/pandarealty/`
1. Open **cPanel > File Manager**.
2. Navigate into **`public_html/`**.
3. Create a folder named **`pandarealty`** (so the full path is `/home/tektxbzg/public_html/pandarealty`).
4. Upload and extract all project files inside this `pandarealty` folder.
5. Verify that your `.env` contains:
   ```env
   APP_ENV=production
   APP_BASE_PATH=/pandarealty
   FORCE_HTTPS=1

   DB_HOST=localhost
   DB_NAME=tektxbzg_pandarealty
   DB_USER=tektxbzg_pandarealty
   DB_PASS=PandaRealty#2026!SecureDb
   DB_PORT=3306

   ALLOW_INSTALLER=0
   ALLOW_DB_BOOTSTRAP=0
   ALLOW_SUPER_SETUP=0

   SERVER_ROOT_PATH=/home/tektxbzg/public_html/pandarealty
   UPLOADS_PATH=/home/tektxbzg/public_html/pandarealty/uploads
   ```

---

### Step 4: Verify Deployment Health
Open your live diagnostics check in the browser:
```
https://yourdomain.com/pandarealty/cpanel_setup.php
```
You will see all green badges:
- ✅ **PHP Version & Extensions** (`mysqli`, `mbstring`, `gd`, `openssl`)
- ✅ **Database Connection** (all 13 tables found)
- ✅ **Upload Storage Permissions** (`uploads/` directories set to 0755)
- ✅ **HTTPS SSL Status**

---

## 👥 Seeded Login Credentials

| Role | Name | Email | Password | Direct Portal |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | Super Administrator | `superadmin@pandarealty.co.ke` | `SuperAdmin@2026!` | [`/pandarealty/admin/super-admin-login.php`](/pandarealty/admin/super-admin-login.php) |
| **CEO / Realtor** | Perpetuah Chepchirchir | `perpetuah@pandarealty.co.ke` | `Perpetuah@2026!` | [`/pandarealty/admin/ceo-login.php`](/pandarealty/admin/ceo-login.php) |
| **Tech Admin** | TekTrend Admin | `admin@tektrend.co.ke` | `Admin@2026!` | [`/pandarealty/admin/login.php`](/pandarealty/admin/login.php) |
| **Client / User** | Demo Client | `test1@gmail.com` | `GoogleAuth@2026!` | [`/pandarealty/login.php`](/pandarealty/login.php) |
