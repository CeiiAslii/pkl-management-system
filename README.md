# 🎓 E-PKL

### Sistem Manajemen Praktik Kerja Lapangan

📍 Selfie + GPS · 📝 Laporan Harian · 🩺 Izin/Sakit · 🤖 Telegram · 📊 Rekap Excel · 🔐 Multi-Role

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![MariaDB](https://img.shields.io/badge/MariaDB-MySQL-003545?logo=mariadb&logoColor=white)](https://mariadb.org)
[![Security](https://img.shields.io/badge/security-policy-0F766E)](SECURITY.md)
[![Tests](https://img.shields.io/badge/tests-PHPUnit-3C9CD7?logo=php&logoColor=white)](tests)

## 📖 About

E-PKL is a Laravel-based PKL management system for students, teachers, and administrators, with GPS/selfie attendance, daily reports, leave requests, Telegram integration, Excel recap, and security-focused access control.

This portfolio repository presents a generic implementation and is not an official system of any particular school.

## ✨ Main Features

- Student registration with administrator approval
- Multi-role dashboards for students, teachers, and administrators
- GPS and selfie-based attendance with server timestamps
- Daily activity reports with a private photo lifecycle
- Leave and sick requests with teacher review
- Teacher notes and student recap views
- Per-student Excel recap export
- Queued Telegram attendance and report notifications
- PKL location and geofence management

## 👥 Roles

- **Student:** maintains a safe profile, records attendance, submits reports, and tracks requests.
- **Teacher:** reviews attendance, reports, requests, recaps, and notes for active students.
- **Administrator:** approves registrations and manages accounts and reference data.

## 🧰 Tech Stack

- Laravel 13, PHP 8.3+
- Blade and Tailwind CSS 4
- MariaDB / MySQL
- OpenSpout for Excel workbooks
- Telegram Bot API through queued jobs
- PHPUnit

## 🔐 Security

The application enforces authorization on the server through role middleware and policies, protects owned resources against IDOR, stores uploads privately, validates upload MIME, size, and image dimensions, rate-limits authentication and sensitive actions, invalidates stale sessions after sensitive account changes, and applies security headers. See [SECURITY.md](SECURITY.md) for reporting guidance.

## 🧪 Testing

```bash
php artisan test
./vendor/bin/pint --test
npm run build
composer audit
npm audit
```

Tests use isolated configuration and fake external integrations; they must not contact a real Telegram bot or production database.

## 🚀 Installation

See the complete [installation guide](INSTALLATION.md) for environment requirements, database setup, queues, the scheduler, and optional Telegram configuration.

## 📷 Screenshots

Screenshots will be added in a future documentation update.

---

**E-PKL — Sistem Manajemen Praktik Kerja Lapangan**
