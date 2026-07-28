# Changelog

All notable changes to EduSchool ERP are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [2.1.1] — 2026-07-28

### Added
- `database/demo_data.sql` — Neutral fictional demo seed with 42 students, 7 teachers, 5 courses, grades, attendance, and discipline records for a quick demo experience.
- `INSTALLATION.md` — Standalone step-by-step installation guide (Windows, Linux, Railway).
- Demo credentials box on the login page for easy reviewer access.
- `build_zip.bat` — Clean distributable packaging script.

### Changed
- `README.md` fully translated to English with credentials table and feature overview.
- `install.bat` and `install.sh` updated to reference `env.production.example.php → env.php` (correct filename).
- School name in all source code and documentation changed from institution-specific to generic "EduSchool ERP".
- `configuracion_sistema` demo values updated to neutral "Greenfield Academy" when demo_data.sql is imported.
- Improved demo credentials visibility on login page (green highlighted banner).

### Fixed
- `install.bat` env file copy now targets the correct `env.production.example.php` source.
- `install.bat` and `install.sh` final step messages now in English.
- Removed real institution name from `index.php` docblock.

---

## [2.1.0] — 2026-06-25

### Added
- Multi-language support (EN / ES) with full `lang/en.php` and `lang/es.php` translation files.
- `force_password_change.php` — Forced first-login password change flow.
- Family Portal (`public/portal.php`) — Guardian access via ID number.
- QR Code generation for student certificates.
- Automatic database backup scheduler (`BackupSchedulerService`).
- Admin security panel (`admin/`) with system monitoring.
- Excel import/export (`ExcelImportService`, `ExcelGeneratorService`).
- Timetable / schedule module (`schedules.php`).
- Disciplinary records module (`discipline.php`).
- Attendance virtual dashboard (`attendance.php`).
- `SessionSecurityMiddleware` — session integrity and hijacking detection.
- `MonitoringService` and `SystemMonitoringService` for health checks.

### Changed
- Migrated to PSR-4 autoloading with full `src/` service layer.
- Passwords upgraded from bcrypt to Argon2id.
- Dashboard now shows real-time statistics from database.
- PDF report cards (boletines) redesigned with DomPDF.

### Fixed
- Race condition in session regeneration.
- Attendance records now correctly filtered by course preceptor scope.

---

## [2.0.0] — 2025-09-29

### Added
- Full rewrite with service-oriented architecture.
- RBAC (Role-Based Access Control) with granular permissions.
- `SecurityHeadersMiddleware`, `InjectionProtectionMiddleware`, `XXEProtectionMiddleware`.
- `ConfigurationService` — all school settings stored in database.
- Multi-period grade system (2, 3, or 4 terms configurable).
- `ServicioBoletinNotas` — grade report card service.
- Docker support (`.dockerignore`, deployment config).

### Changed
- Database schema fully normalized with foreign key constraints.
- Login system hardened against brute-force with lockout logic.

---

## [1.0.0] — 2025-03-01

### Added
- Initial release.
- Students, teachers, courses, and subjects management.
- Basic grades and attendance recording.
- Admin user management.
- Apache `.htaccess` configuration.
