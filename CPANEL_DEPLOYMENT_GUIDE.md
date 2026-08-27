# Panda Realty — cPanel Deployment Guide
**Target Server Path**: `/home/tektxbzg/public_html/pandareality`

---

## 📋 Quick Deployment Steps

### 1. Upload Project Files
1. Zip the `pandareality` directory or use cPanel Git Version Control.
2. In **cPanel File Manager**, navigate to `/home/tektxbzg/public_html/`.
3. Create or extract the project inside `pandareality` so the full path is:
   ```
   /home/tektxbzg/public_html/pandareality
   ```

---

### 2. Configure Environment (`.env`)
1. In the `pandareality` folder, copy `.env.cpanel` to `.env` (or edit `.env`):
   ```env
   APP_ENV=production
   APP_BASE_PATH=/pandareality
   FORCE_HTTPS=1

   DB_HOST=localhost
   DB_NAME=tektxbzg_pandareality
   DB_USER=tektxbzg_realty
   DB_PASS=Gkf^u(9^Hv6x9~8#
   DB_PORT=3306

   ALLOW_INSTALLER=0
   ALLOW_DB_BOOTSTRAP=0
   ALLOW_SUPER_SETUP=0
   ```
2. *Note*: If `pandareality` is mapped to a dedicated subdomain or domain root (e.g. `https://pandarealty.co.ke/`), set `APP_BASE_PATH=/`.

---

### 3. MySQL Database Setup
1. In **cPanel > MySQL Databases**:
   - Create Database: `tektxbzg_pandareality`
   - Create User: `tektxbzg_realty` with password `Gkf^u(9^Hv6x9~8#`
   - Assign User to Database with **ALL PRIVILEGES**.
2. In **cPanel > phpMyAdmin**:
   - Select `tektxbzg_pandareality`.
   - Click **Import** > Select `database.sql` > Click **Go**.

---

### 4. Set Directory Permissions
Ensure upload folders have write permissions (`0755` or `0775`):
- `/home/tektxbzg/public_html/pandareality/uploads/`
- `/home/tektxbzg/public_html/pandareality/uploads/properties/`
- `/home/tektxbzg/public_html/pandareality/uploads/branding/`
- `/home/tektxbzg/public_html/pandareality/uploads/realtor/`
- `/home/tektxbzg/public_html/pandareality/uploads/avatars/`

---

### 5. Verify Server Health
Visit the server diagnostics check in your browser:
```
https://yourdomain.com/pandareality/cpanel_setup.php
```
It will verify:
- ✅ PHP Version & Extensions (`mysqli`, `mbstring`, `gd`, `openssl`)
- ✅ Database Connection & Tables
- ✅ Upload Directory Permissions
- ✅ HTTPS SSL Status
