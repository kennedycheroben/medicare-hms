# MediCare Hospital Management System — Truehost & cPanel Production Deployment Runbook

**Document Version:** 1.0.0  
**Target Environment:** Truehost Cloud Shared Hosting / cPanel Linux Environment  
**Stack:** PHP 8.3 / 8.4, MariaDB 10.4+ / MySQL 8.0, Apache/LiteSpeed, Laravel 13, Bootstrap, Blade  
**Target Application Location:** `~/medicare-hms` (strictly outside `public_html`)  
**Public Web Document Root:** `~/public_html` or domain document root pointed to `~/medicare-hms/public`  

---

> [!IMPORTANT]
> **SAFETY & EXECUTION BOUNDARY NOTICE**  
> This runbook is divided into **Preparation Steps** (safe, pre-deployment local packaging and verification) and **Execution Steps** (production server commands).  
> **DO NOT** execute any production server commands until formal authorization is granted, maintenance windows are scheduled, and backups are confirmed.

---

## 1. Architectural Architecture & Directory Strategy on cPanel

### Critical Security Rule: Never Deploy Directly Inside `public_html`
Placing a complete Laravel application inside `public_html/` exposes `.env`, `storage/logs/`, `database/`, and sensitive vendor code to direct public HTTP access if Apache `.htaccess` is ever bypassed or ignored.

### Recommended cPanel Directory Layout:
```
/home/cpanelusername/
├── medicare-hms/              <-- Laravel Application Root (Private, inaccessible via web)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env                   <-- Server-only production secrets (chmod 600)
│   ├── artisan
│   └── composer.json
└── public_html/               <-- Public Document Root (Public HTTP access only)
    ├── build/                 <-- Pre-compiled Vite assets (CSS/JS)
    ├── storage                <-- Symlink to ~/medicare-hms/storage/app/public
    ├── index.php              <-- Front controller forwarding to ~/medicare-hms
    ├── .htaccess              <-- Security headers and URL rewriting
    ├── favicon.ico
    └── robots.txt
```

---

## 2. Phase A: Local Pre-Deployment Preparation & Packaging

These steps are executed locally on the developer workstation or CI/CD runner prior to uploading anything to the hosting server.

### Step A.1: Verify Codebase, Git Cleanliness & Test Suite
Ensure the local feature branch is up to date, passes all automated tests, and has a clean working tree:
```bash
# Verify branch
git branch --show-current
# Expected: feat/ehr-doctor-consultation-pharmacy

# Verify status
git status
# Must be clean, no uncommitted changes

# Run full automated test suite against isolated test database
php artisan test
# Verification Gate: Must report 100% pass (127 tests, 910 assertions, 0 failures)
```

### Step A.2: Compile Production Frontend Assets
Truehost shared hosting typically lacks Node.js and npm in its default shared SSH environment. Frontend assets **must** be pre-compiled locally:
```bash
npm run build
```
Verify that `public/build/manifest.json`, `public/build/assets/*.css`, and `public/build/assets/*.js` are created cleanly.

### Step A.3: Prepare Deployment Archive
Create a clean distribution archive excluding local scratch files, `.git`, `.env`, and test databases:
```bash
# Create deployment package
tar --exclude='.git' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='storage/app/backups*' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    -czvf medicare-hms-deploy.tar.gz .
```

---

## 3. Phase B: Production Environment Setup on cPanel

### Step B.1: Configure PHP Version & Required Extensions
1. Log in to cPanel.
2. Open **MultiPHP Manager** or **Select PHP Version** (CloudLinux).
3. Set the domain PHP version to **PHP 8.3** or **PHP 8.4**.
4. In PHP Extensions, ensure the following are enabled:
   - `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.
5. In **PHP Options / INI Editor**, configure:
   - `memory_limit` = `256M` (or `512M`)
   - `max_execution_time` = `60` (or `120`)
   - `upload_max_filesize` = `20M`
   - `post_max_size` = `25M`
   - `date.timezone` = `Africa/Nairobi`

### Step B.2: Create Production Database & User
1. Open **MySQL Database Wizard** in cPanel.
2. Create database: `cpaneluser_medicare_prod`.
3. Create user: `cpaneluser_medicare_user` with a strong, randomly generated password (minimum 24 characters).
4. Assign user to database with **ALL PRIVILEGES**.
5. Record database name, user, and password securely in password manager (never commit to repository).

### Step B.3: Upload and Extract Application Files
1. Open cPanel **File Manager** (or connect via SFTP / SSH).
2. Create directory `/home/cpanelusername/medicare-hms`.
3. Upload `medicare-hms-deploy.tar.gz` into `/home/cpanelusername/medicare-hms`.
4. Extract the archive:
   ```bash
   cd ~/medicare-hms
   tar -xzvf medicare-hms-deploy.tar.gz
   rm medicare-hms-deploy.tar.gz
   ```

### Step B.4: Configure Web Root & Front Controller
Depending on whether cPanel allows custom document roots:

#### Option 1: Custom Document Root (Subdomain or Primary Domain with Root Change)
If cPanel allows setting document root, point the domain directly to:
`/home/cpanelusername/medicare-hms/public`

#### Option 2: Standard Locked `public_html` (Primary Shared Hosting Domain)
If cPanel locks the primary domain to `public_html`:
1. Move all contents of `~/medicare-hms/public/` into `~/public_html/`:
   ```bash
   cp -r ~/medicare-hms/public/* ~/public_html/
   cp ~/medicare-hms/public/.htaccess ~/public_html/
   ```
2. Edit `/home/cpanelusername/public_html/index.php` to reference the private application directory:
   ```php
   // Change:
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';

   // To:
   require __DIR__.'/../medicare-hms/vendor/autoload.php';
   $app = require_once __DIR__.'/../medicare-hms/bootstrap/app.php';
   ```

### Step B.5: Install Production Dependencies (Composer)
Via SSH on cPanel:
```bash
cd ~/medicare-hms

# Use the specific PHP binary matching the cPanel version (e.g. ea-php83)
/usr/local/bin/ea-php83 /usr/local/bin/composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
```

---

## 4. Phase C: Environment Configuration & Security Initialization

### Step C.1: Provision Production `.env`
1. Copy template:
   ```bash
   cd ~/medicare-hms
   cp .env.production.example .env
   chmod 600 .env
   ```
2. Update `.env` with actual production values:
   - `APP_URL=https://medicare.example.com`
   - `DB_DATABASE=cpaneluser_medicare_prod`
   - `DB_USERNAME=cpaneluser_medicare_user`
   - `DB_PASSWORD=YOUR_RECORDED_DATABASE_PASSWORD`
   - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
   - `HOSPITAL_DEMO_MODE=false`

### Step C.2: Generate Application Cryptographic Key
```bash
php artisan key:generate --force
```

### Step C.3: Run Database Migrations
Run database migrations safely against the clean production database:
```bash
php artisan migrate --force
```

> [!CAUTION]
> **DO NOT RUN `php artisan db:seed` IN PRODUCTION.**  
> The database seeder contains demo staff and test patient data. Running seeders in production is blocked by `app()->isProduction()` checks. For initial administrative access, use the dedicated administrative provisioning command:
> ```bash
> php artisan hms:create-admin --name="System Administrator" --email="admin@medicare.co.ke"
> ```
> *(Or manually insert the initial SuperAdmin account with a secure, rotated Bcrypt password).*

### Step C.4: Configure File Storage Symlink & Permissions
Ensure writable permissions and create public media link:
```bash
cd ~/medicare-hms

# Set permissions
chmod -R 775 storage bootstrap/cache

# Create storage symlink
php artisan storage:link
```
If using Option 2 (public files in `public_html`), create the symlink manually:
```bash
ln -s ~/medicare-hms/storage/app/public ~/public_html/storage
```

### Step C.5: Cache Configuration, Routes, and Views
Optimize runtime performance on shared hosting:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Phase D: Automated Cron Job & Background Tasks

In cPanel **Cron Jobs**, add the standard Laravel scheduled task runner:

```cron
* * * * * cd /home/cpanelusername/medicare-hms && /usr/local/bin/ea-php83 artisan schedule:run >> /dev/null 2>&1
```

### Database Backup Cron Schedule:
To automate daily database backups using the verified backup script:
```cron
0 2 * * * cd /home/cpanelusername/medicare-hms && ./scripts/backup_database.sh >> storage/logs/backup.log 2>&1
```

---

## 6. Phase E: Post-Deployment Smoke & Verification Checklist

Before releasing the system to hospital staff and patients, complete every verification step:

| Check | Target URL / Flow | Expected Result | Verified By |
| :--- | :--- | :--- | :--- |
| **HTTPS & SSL** | `https://medicare.example.com` | Padlock valid, HTTP strictly redirects to HTTPS | |
| **Public Homepage** | `/` | Departments, doctors, services render without console errors | |
| **Appointment Booking** | `/appointments/book` | Booking submission succeeds, reference code generated, zero PII displayed | |
| **Staff Login** | `/admin/login` | SuperAdmin logs in, session cookie flagged `Secure` and `HttpOnly` | |
| **Role Authorization** | `/admin/consultations` | Doctor access permitted; Receptionist access returns HTTP 403 Forbidden | |
| **Patient Registration** | `/admin/patients/create` | New patient registers, unique non-sequential MRN assigned | |
| **Nursing Triage** | `/admin/triage` | Triage vitals recorded, BMI calculated automatically | |
| **Doctor Consultation** | `/admin/consultations/{id}` | SOAP notes saved, consultation finalized, immutable record locked | |
| **Pharmacy & FEFO** | `/admin/pharmacy` | Finalized e-Rx appears in dispensary queue, FEFO batch allocated | |
| **Stock Deduplication** | `/admin/pharmacy/dispense` | Idempotency key prevents double-dispensing; negative stock prevented | |
| **Patient Portal** | `/portal/login` | Dedicated patient guard active; isolated from staff session state | |
| **Legal Documentation** | `/privacy` & `/terms` | KDPA 2019 Privacy Policy and Terms of Service rendered | |
| **User Workbook** | `/guide` | Operational manual accessible to patients and hospital staff | |

---

## 7. Phase F: Rollback & Disaster Recovery Procedures

If a critical blocker is encountered during or after deployment:

### Fast Rollback (File Level):
1. Put site in maintenance mode:
   ```bash
   php artisan down --secret="medicare-emergency-access"
   ```
2. Revert codebase to previous verified release archive:
   ```bash
   tar -xzvf medicare-hms-deploy-previous.tar.gz -C ~/medicare-hms
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
3. Bring site back online:
   ```bash
   php artisan up
   ```

### Full Database Recovery:
If data corruption occurs, restore the verified pre-deployment backup:
```bash
cd ~/medicare-hms
./scripts/restore_database.sh \
    --file storage/app/backups/cpaneluser_medicare_prod_PRE_DEPLOY.sql.gz \
    --target-db cpaneluser_medicare_prod \
    --confirm
```
