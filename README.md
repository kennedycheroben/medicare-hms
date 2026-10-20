# MediCare Hospital Management System (HMS)

Enterprise-grade Hospital Management System built on Laravel, Blade, Bootstrap, and MySQL/MariaDB.

---

## 1. System Overview

MediCare HMS provides an integrated digital healthcare operations and patient service platform. The system encompasses:
- **Public Hospital Portal**: 12 responsive clinical and informational service pages based on the MediCare theme.
- **Administrative & Clinical Dashboard**: Comprehensive hospital management back-office based on Modern Admin.
- **Patient & Appointment Management**: Online scheduling, doctor profiles, departmental directories, and emergency contact routing.

---

## 2. Technology Stack

- **Backend Framework**: Laravel 13.x
- **Runtime Environment**: PHP 8.4+ (CLI & Web)
- **Database Engine**: MySQL / MariaDB (XAMPP stack)
- **Frontend Presentation**: Blade templates, Bootstrap 5, Vanilla JavaScript & CSS
- **Testing Framework**: PHPUnit 12.x / Laravel Test Runner

---

## 3. Environment & Prerequisites

1. **PHP 8.4+**:
   - Required extensions: `pdo_mysql`, `mbstring`, `openssl`, `xml`, `curl`, `zip`, `gd`, `fileinfo`.
2. **Composer**: Version 2.x+
3. **Database**: MySQL/MariaDB daemon running on `127.0.0.1:3306`.
   - Primary database: `medicare_hms_db`
   - Testing database: `medicare_hms_testing`

---

## 4. Getting Started

### Clone / Open Project
```bash
cd /opt/lampp/htdocs/medicare-hms
```

### Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### Database Migration
Ensure the MySQL service is running, then run migrations:
```bash
php artisan migrate
```

### Health Check Verification
The system provides two health check endpoints:
- `GET /health`: JSON endpoint returning application status, environment, and ISO timestamp.
- `GET /up`: Framework diagnostic health endpoint.

### Running Automated Tests
The automated test suite runs against the dedicated `medicare_hms_testing` MySQL database:
```bash
php artisan test
```

---

## 5. Project Conventions & Architecture Boundaries

- **Separation of Concerns**: Business logic resides in Eloquent models and dedicated service controllers; public presentation uses clean Blade templates.
- **Security First**: No `.env` files or credentials are ever tracked in version control. All inputs are strictly validated on the server.
- **Source Template Preservation**: Approved source design assets in `/opt/lampp/htdocs/medicare` and `/opt/lampp/htdocs/modern_admin` are strictly read-only templates.
