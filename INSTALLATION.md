# Installation Guide — EduSchool ERP

> **Version:** 2.1.1  
> **PHP:** 8.1+  | **MySQL:** 8.0+ / MariaDB 10.4+

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Windows / XAMPP Installation](#2-windows--xampp-installation)
3. [Linux / Ubuntu Server Installation](#3-linux--ubuntu-server-installation)
4. [Railway Cloud Deployment](#4-railway-cloud-deployment)
5. [Environment Configuration](#5-environment-configuration)
6. [First Login & Demo Data](#6-first-login--demo-data)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Prerequisites

Ensure the following are installed before proceeding:

| Dependency | Minimum Version | Check Command |
|-----------|----------------|---------------|
| PHP | 8.1 | `php --version` |
| MySQL or MariaDB | 8.0 / 10.4 | `mysql --version` |
| Composer | 2.x | `composer --version` |
| Web Server | Apache / Nginx | — |

**Required PHP extensions:**
```
pdo_mysql  mbstring  openssl  curl  json  gd  fileinfo
```

Check your extensions:
```bash
php -m | grep -E "pdo_mysql|mbstring|openssl|curl|json|gd|fileinfo"
```

---

## 2. Windows / XAMPP Installation

### Automatic (Recommended)

1. Extract the zip to `C:\xampp\htdocs\SistemaAdmin`
2. Double-click `install.bat`
3. Edit `env.php` with your database credentials
4. Import the database (step 5 below)

### Manual Steps

**Step 1 — Place files**
```
Extract zip contents to: C:\xampp\htdocs\SistemaAdmin\
```

**Step 2 — Create environment file**
```bat
copy env.production.example.php env.php
```
Edit `env.php` — set at minimum:
```php
'SCHOOL_NAME' => 'Your School Name',
'APP_URL'     => 'http://localhost/SistemaAdmin',
'APP_BASE_PATH' => '/SistemaAdmin',
'DB_HOST'     => 'localhost',
'DB_NAME'     => 'school_admin',
'DB_USER'     => 'root',
'DB_PASS'     => '',   // XAMPP default: empty
```

**Step 3 — Install PHP dependencies**
```bat
composer install --no-dev --optimize-autoloader
```

**Step 4 — Create database**

Open phpMyAdmin → New → Database name: `school_admin` → Collation: `utf8mb4_unicode_ci` → Create.

Or via terminal:
```bat
mysql -u root -p -e "CREATE DATABASE school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Step 5 — Import database schema**
```bat
mysql -u root -p school_admin < database\school_admin.sql
```

**Step 6 — Import demo data (optional but recommended)**
```bat
mysql -u root -p school_admin < database\demo_data.sql
```

**Step 7 — Open in browser**
```
http://localhost/SistemaAdmin
```

---

## 3. Linux / Ubuntu Server Installation

### Automatic

```bash
chmod +x install.sh
./install.sh
```

### Manual Steps

**Step 1 — Install Apache, PHP, MySQL**
```bash
sudo apt update
sudo apt install apache2 php8.1 php8.1-mysql php8.1-mbstring php8.1-curl php8.1-gd php8.1-json php8.1-openssl mysql-server composer -y
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Step 2 — Place files**
```bash
sudo cp -r SistemaAdmin/ /var/www/html/SistemaAdmin
cd /var/www/html/SistemaAdmin
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 logs/ backups/ uploads/
```

**Step 3 — Create environment file**
```bash
cp env.production.example.php env.php
nano env.php
```

Set the following values:
```php
'SCHOOL_NAME'   => 'Your School Name',
'APP_URL'       => 'https://yourdomain.com',
'APP_BASE_PATH' => '/',  // if running at root; '/SistemaAdmin' if in subfolder
'DB_HOST'       => 'localhost',
'DB_NAME'       => 'school_admin',
'DB_USER'       => 'school_user',
'DB_PASS'       => 'StrongPassword123!',
'APP_KEY'       => 'generate-a-random-64-char-string',
```

**Step 4 — Install dependencies**
```bash
composer install --no-dev --optimize-autoloader
```

**Step 5 — Set up MySQL**
```bash
sudo mysql -e "CREATE DATABASE school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'school_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON school_admin.* TO 'school_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
mysql -u school_user -p school_admin < database/school_admin.sql
mysql -u school_user -p school_admin < database/demo_data.sql
```

**Step 6 — Configure Apache VirtualHost**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/SistemaAdmin
    DirectoryIndex index.php

    <Directory /var/www/html/SistemaAdmin>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/school_error.log
    CustomLog ${APACHE_LOG_DIR}/school_access.log combined
</VirtualHost>
```

Enable and restart:
```bash
sudo a2ensite school.conf
sudo systemctl reload apache2
```

---

## 4. Railway Cloud Deployment

### Prerequisites
- [Railway account](https://railway.app) (free tier available)
- MySQL plugin added to your Railway project

### Steps

**Step 1 — Create Railway project**
1. Go to [railway.app](https://railway.app) → New Project
2. Add a **MySQL** plugin to your project
3. Note the connection variables: `MYSQL_HOST`, `MYSQL_PORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

**Step 2 — Import database**
Using Railway's database shell or a local client connected to Railway MySQL:
```sql
-- Run school_admin.sql then demo_data.sql
SOURCE database/school_admin.sql;
SOURCE database/demo_data.sql;
```

**Step 3 — Configure environment**

Create `env.php` with Railway MySQL credentials:
```php
<?php return [
    'SCHOOL_NAME'   => 'Your School Name',
    'APP_ENV'       => 'production',
    'APP_DEBUG'     => 'false',
    'APP_KEY'       => 'your-64-char-random-key',
    'APP_URL'       => 'https://yourapp.up.railway.app',
    'APP_BASE_PATH' => '/',
    'DB_HOST'       => 'your-railway-mysql-host',
    'DB_PORT'       => '3306',
    'DB_NAME'       => 'railway',
    'DB_USER'       => 'root',
    'DB_PASS'       => 'your-railway-mysql-password',
    'SESSION_LIFETIME'       => '120',
    'MAX_LOGIN_ATTEMPTS'     => '5',
    'BACKUP_ENCRYPTION_KEY'  => 'your-random-key',
    'BACKUP_DOWNLOAD_SECRET' => 'your-random-key',
    'SUPPORT_EMAIL' => 'admin@yourschool.edu',
    'LOG_LEVEL'     => 'error',
];
```

**Step 4 — Deploy**
1. Connect your GitHub repo to Railway (or upload files via Railway CLI)
2. Railway will detect PHP and serve the application
3. Set `NIXPACKS_PHP_ROOT_DIR` to `/` if needed

> **Tip:** The `.htaccess` file at root handles all routing. Make sure the Railway deployment runs Apache or configure Nginx accordingly.

---

## 5. Environment Configuration

Full reference for `env.php` options:

| Key | Description | Example |
|-----|-------------|---------|
| `SCHOOL_NAME` | Institution display name | `"Greenfield Academy"` |
| `SCHOOL_SLOGAN` | Tagline shown in portal | `"Excellence in Education"` |
| `APP_ENV` | `production` or `development` | `"production"` |
| `APP_DEBUG` | Show detailed errors | `"false"` |
| `APP_KEY` | 32+ char secret key for encryption | `"abc123...xyz"` |
| `APP_URL` | Public URL (no trailing slash) | `"https://school.example.com"` |
| `APP_BASE_PATH` | URL path prefix | `"/"` or `"/SistemaAdmin"` |
| `DB_HOST` | Database server | `"localhost"` |
| `DB_PORT` | Database port | `"3306"` |
| `DB_NAME` | Database name | `"school_admin"` |
| `DB_USER` | Database username | `"root"` |
| `DB_PASS` | Database password | `"SecurePass!"` |
| `SESSION_LIFETIME` | Minutes before session expires | `"120"` |
| `MAX_LOGIN_ATTEMPTS` | Brute-force lockout threshold | `"5"` |
| `BACKUP_ENCRYPTION_KEY` | Key for encrypted backups | random 32+ chars |
| `BACKUP_DOWNLOAD_SECRET` | URL token for backup downloads | random 32+ chars |
| `SUPPORT_EMAIL` | Support contact shown in UI | `"admin@school.edu"` |
| `LOG_LEVEL` | `debug`, `info`, `error` | `"error"` |

---

## 6. First Login & Demo Data

### Demo Credentials (all use password: `admin123`)

| Role | Email |
|------|-------|
| **Administrator** | `admin@escuela.edu` |
| **Principal** | `director@greenfield.edu` |
| **Homeroom Teacher** | `preceptor@greenfield.edu` |
| **Teacher** | `p.williams@greenfield.edu` |
| **Secretary** | `secretary@greenfield.edu` |

> **Security Note:** Change all passwords immediately in a production environment via the Users module.

### Setting Your School Name

After first login, go to **Settings → System Configuration** and update:
- School name and slogan
- Academic year and current period
- Timezone

### Required First Steps

1. Configure courses (`courses.php`)
2. Add subjects (`subjects.php`)
3. Assign subjects to courses
4. Add teachers (`teachers.php`)
5. Assign teachers to courses and subjects
6. Enroll students (`students.php`)
7. Begin recording grades and attendance

---

## 7. Troubleshooting

### Blank page / 500 error
- Set `APP_DEBUG = true` in `env.php` temporarily
- Check `logs/` directory for error files
- Verify PHP extensions are all enabled

### Database connection error
- Confirm `DB_NAME`, `DB_USER`, `DB_PASS` are correct in `env.php`
- Test connection: `mysql -u your_user -p your_database`

### .htaccess not working (404 on all pages)
- Enable Apache mod_rewrite: `sudo a2enmod rewrite`
- Set `AllowOverride All` in your VirtualHost

### PDF generation fails
- Ensure the `dompdf/dompdf` package was installed: `composer install`
- Check PHP `gd` extension is enabled

### Language not switching
- Clear browser cookies/session
- Ensure `lang/en.php` and `lang/es.php` exist and are readable

### Composer install fails with "Your requirements could not be resolved"
```bash
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

---

*For additional help, contact support via the email in your Codester purchase confirmation.*
