# 🎓 EduSchool ERP — School Management System

A comprehensive, modular school administration platform for primary, secondary, and vocational institutions.

![Version](https://img.shields.io/badge/version-2.1.1-blue)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)
![License](https://img.shields.io/badge/license-Commercial-green)

---

## 🔑 Demo Access

> **Live Demo:** [https://your-railway-app.up.railway.app/SistemaAdmin](https://your-railway-app.up.railway.app)

All demo accounts use the password **`admin123`**:

| Role | Email |
|------|-------|
| **Administrator** | `admin@escuela.edu` |
| **Principal / Director** | `director@greenfield.edu` |
| **Homeroom Teacher** | `preceptor@greenfield.edu` |
| **Teacher** | `p.williams@greenfield.edu` |

---

## ✨ Features

### 👥 People Management
- **Students**: Full registration, individual profile, academic record & transcript.
- **Teachers**: Staff profiles, subject and course assignments.
- **Users & Roles**: Granular RBAC system (Admin, Principal, Homeroom Teacher, Teacher, Secretary).

### 📚 Academic Management
- **Courses**: Organized by grade, division, and shift (morning/afternoon).
- **Subjects**: General or vocational/technical track subjects (workshops & specializations).
- **Grades & Report Cards**: Continuous assessment, multi-period grading (2–4 terms), and PDF report cards.
- **Attendance**: Daily attendance tracking, reports, and absence alerts.
- **Disciplinary Records**: Student conduct log and follow-up tracking.

### 🛠️ Advanced Features
- **PDF Report Cards (Boletines)**: Generate and print official grade reports.
- **Dashboard & Analytics**: Real-time statistics and visual charts.
- **Family Portal**: Quick access landing page for parents/guardians using their ID number.
- **Responsive Design**: Optimized for mobile, tablet, and desktop.
- **Multi-Language (i18n)**: Full native support for English and Spanish (EN/ES switch on all pages).
- **Timetable / Schedule**: Weekly schedule editor per course.
- **Security**: CSRF protection, RBAC, Argon2id password hashing, session management, injection protection middlewares.

---

## 💻 System Requirements

| Component | Minimum |
|-----------|---------|
| **PHP** | 8.1+ (ext: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`) |
| **MySQL / MariaDB** | 8.0 / 10.4+ |
| **Web Server** | Apache (with mod_rewrite) or Nginx |
| **Composer** | Latest stable |

---

## 🚀 Quick Start

### Option 1: Automatic Install (Recommended)

**Windows (XAMPP / WampServer)**
```bat
install.bat
```

**Linux / macOS**
```bash
chmod +x install.sh && ./install.sh
```

### Option 2: Manual Install

```bash
# 1. Copy files to your web root
#    e.g. /var/www/html/SistemaAdmin  or  C:/xampp/htdocs/SistemaAdmin

# 2. Create your environment file
cp env.production.example.php env.php
# Edit env.php and set your DB credentials, school name, and APP_URL

# 3. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 4. Create database and import schema
mysql -u root -p -e "CREATE DATABASE school_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p school_admin < database/school_admin.sql

# 5. (Optional but recommended) Import demo data for a pre-populated dashboard
mysql -u root -p school_admin < database/demo_data.sql

# 6. Open in browser
#    http://localhost/SistemaAdmin
```

> For full step-by-step instructions (including Railway, Nginx, and Linux VPS), see **[INSTALLATION.md](INSTALLATION.md)**.

---

## 📁 Directory Structure

```
SistemaAdmin/
├── config/           # Database config, environment loader
├── database/         # SQL schema (school_admin.sql) + demo data (demo_data.sql)
├── docs/             # Full technical documentation
├── includes/         # Global PHP includes and template components
├── lang/             # Language files (en.php, es.php)
├── public/           # Login page, family portal, error pages
├── src/              # PSR-4 PHP source code
│   ├── Bootstrap/    # App initialization (AppRequestInit)
│   ├── Controllers/  # HTTP controllers
│   ├── Middleware/   # Security middlewares
│   ├── Models/       # Domain models
│   └── Services/     # Business logic layer
├── css/              # Stylesheets
├── js/               # JavaScript modules
├── admin/            # Admin panel (security monitoring)
├── index.php         # Main dashboard
├── students.php   # Students management
├── teachers.php    # Teachers management
├── courses.php        # Courses management
├── grades.php         # Grades management
├── attendance_reports.php  # Attendance dashboard
├── schedules.php      # Timetable editor
├── install.bat       # Windows installer
├── install.sh        # Linux/macOS installer
├── env.production.example.php  # Environment file template
└── CHANGELOG.md      # Version history
```

---

## 🔒 Security

The system ships with active security middlewares:

| Middleware | Purpose |
|-----------|---------|
| `SecurityHeadersMiddleware` | CSP, HSTS, referrer policies |
| `OpenRedirectProtectionMiddleware` | Blocks external redirect attacks |
| `UploadSecurityMiddleware` | File upload validation & sanitization |
| `InjectionProtectionMiddleware` | SQL injection & XSS filters |
| `XXEProtectionMiddleware` | XML external entity protection |
| `SessionSecurityMiddleware` | Session integrity & hijacking detection |

---

## 🌐 Multi-Language

Switch between **English** and **Spanish** on any page using the language selector in the top navigation or on the login page. Language setting is persisted per user session.

---

## 📄 License

### 100% Open Source Code (No Encryption Required)
- All PHP, JS, and SQL source code is fully readable and modifiable.
- No ionCube, no activation keys, no domain locks.
- Works immediately after installation on any server.

### Commercial License Terms
Purchase through **Codester** grants:
- **Regular License**: 1 installation / site
- **Extended License**: Multiple projects / clients

Per Codester's official license terms. All intellectual property rights belong to the original author.

---

## 📦 What's in the Package

```
✅ Full PHP source code (PSR-4, service layer, middlewares)
✅ Database schema + demo seed data
✅ Windows (install.bat) and Linux (install.sh) installers
✅ English & Spanish language files
✅ Full documentation (README, INSTALLATION, CHANGELOG, docs/)
✅ CSS + JavaScript assets
✅ composer.json (run composer install to set up dependencies)
```

---

*See [CHANGELOG.md](CHANGELOG.md) for version history.*
