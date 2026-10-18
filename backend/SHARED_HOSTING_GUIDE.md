# 🚀 ERP Platform - Shared Hosting Deployment Guide

This directory contains the **unified, self-contained monolithic version** of the ERP system, specifically pre-packaged and optimized for deployment on **Shared Hosting environments** (cPanel, Plesk, LiteSpeed, Apache, DirectAdmin, Hostinger, Namecheap, Bluehost, etc.).

---

## 🌟 Why this version is optimized for Shared Hosting

1. **Unified Monolith (Everything in One Place)**:
   - Laravel serves both the REST API backend (`/api/*`) and the compiled Vue 3 Single Page Application (`/`) directly from `public/`.
   - **No Node.js or Docker required on the hosting server**.
2. **Pre-Bundled Composer Vendor**:
   - Dependencies are pre-installed in `vendor/`. You don't need SSH access or Composer on your hosting server.
3. **Dual `.htaccess` Architecture**:
   - **Root `.htaccess`**: Automatically and securely routes web traffic into the `/public` directory while hiding `.env`, `composer.json`, and source code.
   - **`public/.htaccess`**: Manages authorization headers and SPA route redirection to `index.html` or `index.php`.
4. **No Redis Requirement**:
   - Uses file-based caching, file-based sessions, and synchronous or database queues (`QUEUE_CONNECTION=sync` / `database`).
5. **Standard MySQL / MariaDB Support**:
   - Pre-configured for standard cPanel MySQL databases with UTF-8mb4 collation.

---

## 📁 Project Directory Structure

```
/home/username/public_html/ (or project folder)
├── .htaccess                 <-- Root rewrite to /public (security guard)
├── .env.shared-hosting.example
├── .env                      <-- Your server configuration
├── artisan
├── app/
├── bootstrap/
├── config/
├── database/
├── frontend/                 <-- Vue 3 source code (for local customization)
├── public/                   <-- Web Document Root
│   ├── .htaccess             <-- Front controller & SPA routing
│   ├── index.html            <-- Compiled Vue 3 application
│   ├── index.php             <-- Laravel entry point
│   ├── assets/               <-- Compiled JS, CSS, and images
│   └── storage -> ../storage/app/public
├── routes/
│   ├── api.php
│   └── web.php               <-- SPA fallback catch-all route
├── storage/                  <-- Must be chmod 775
└── vendor/                   <-- Pre-bundled PHP libraries
```

---

## 🛠️ Step-by-Step cPanel Deployment

### Step 1: Upload the Project Files
1. In your cPanel dashboard, open **File Manager**.
2. Navigate to your domain's folder (e.g. `public_html/` or a subdomain folder like `public_html/erp/`).
3. Upload and extract the project files.

---

### Step 2: Configure the Document Root

You have two options depending on your hosting setup:

#### Option A: Dedicated Domain or Subdomain (Recommended)
- In cPanel, go to **Domains** or **Subdomains**.
- Set the **Document Root** directly to the `public/` directory:
  ```
  public_html/erp/public
  ```

#### Option B: Standard Root Folder (Using Root `.htaccess`)
- If your host doesn't let you modify the document root away from `public_html/`, leave the included root `.htaccess` in place. It will transparently forward all incoming web requests into the `/public` folder without exposing sensitive files.

---

### Step 3: Create MySQL Database & User
1. In cPanel, go to **MySQL Databases**.
2. Create a new database (e.g. `cpaneluser_erp`).
3. Create a new MySQL user with a strong password (e.g. `cpaneluser_erpuser`).
4. Add the user to the database with **ALL PRIVILEGES**.

---

### Step 4: Configure Environment (`.env`)
In your project root, open `.env` (or copy `.env.shared-hosting.example` to `.env`) and update the following values:

```env
APP_NAME="ERP Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_erp
DB_USERNAME=cpaneluser_erpuser
DB_PASSWORD=your_mysql_password_here

# Cache & Queues (File-based, no Redis required)
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public

# Currency
APP_CURRENCY=ETB
```

---

### Step 5: Set Directory Permissions
Ensure Laravel can write to the cache and storage folders.
In cPanel File Manager, right-click and set permissions (or via Terminal):

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

### Step 6: Run Migrations & Seeders
If your hosting plan provides **Terminal** access:
```bash
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\PlanSeeder --force
php artisan db:seed --class=Database\\Seeders\\SuperAdminSeeder --force
```

> **Note for shared hosts WITHOUT Terminal access**:
> You can import the database schema using cPanel **phpMyAdmin**:
> 1. Export your local database dump (`.sql`).
> 2. Go to **phpMyAdmin** in cPanel, select your database, and click **Import**.

---

### Step 7: Set Up Background Cron Job (Optional but Recommended)
In cPanel, go to **Cron Jobs** and add a cron job that runs every minute:
```bash
* * * * * cd /home/yourcpaneluser/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## 💻 Local Development & Rebuilding Frontend

If you make modifications to the Vue frontend in `frontend/`:
1. Run `./build-production.sh` in the project root.
2. The script will recompile the Vue application and automatically update `public/index.html` and `public/assets/`.
3. Upload the newly updated `public/` files to your server.

---

## 🔑 Default Super Administrator Account

- **Login URL**: `https://your-domain.com/login`
- **Email**: `superadmin@erp.local`
- **Password**: `password123`

*(Please change this password immediately under Settings upon first login!)*
