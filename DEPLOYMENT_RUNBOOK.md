# MediCare Hospital Management System — Truehost & cPanel Production Deployment Runbook

- **Document Version:** 1.2.0
- **Target Environment:** Truehost Cloud Shared Hosting / cPanel Linux Environment
- **Stack:** PHP 8.3 / 8.4, MariaDB 10.4+ / MySQL 8.0, Apache/LiteSpeed, Laravel 13, Bootstrap, Blade
- **Production Domain:** `medicare.kennedycheroben.co.ke` (HMS Demonstration Platform)
- **cPanel Username:** `xqtrqexj`
- **Target Git Branch:** `main` (Verified Release Baseline)
- **Target Application Location:** `/home/xqtrqexj/medicare-hms-app` (strictly outside document root)
- **Public Web Document Root:** `/home/xqtrqexj/medicare.kennedycheroben.co.ke`

---

> [!IMPORTANT]
> **SAFETY & EXECUTION BOUNDARY NOTICE**  
> This runbook is divided into **Preparation Steps** (safe, pre-deployment local packaging and verification) and **Execution Steps** (production server commands).  
> **DO NOT** execute any production server commands until formal authorization is granted, maintenance windows are scheduled, and backups are confirmed.

---

## 1. Architectural Architecture & Directory Strategy on cPanel

### Critical Security Rule: Never Deploy Directly Inside Public Document Root
Placing a complete Laravel application inside the public document root exposes `.env`, `storage/logs/`, `database/`, and sensitive vendor code to direct public HTTP access if Apache `.htaccess` is ever bypassed or ignored.

### Truehost cPanel Directory Layout:
```
/home/xqtrqexj/
├── medicare-hms-app/          <-- Laravel Application Root (Private, inaccessible via web)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── admin-assets/
│   │   ├── assets/
│   │   └── build/             <-- Pre-compiled Vite assets (CSS/JS/fonts)
│   ├── resources/
│   ├── routes/
│   ├── scripts/
│   ├── storage/
│   ├── vendor/
│   ├── .env                   <-- Server-only production secrets (chmod 600)
│   ├── artisan
│   └── composer.json
└── medicare.kennedycheroben.co.ke/  <-- Public Document Root (Public HTTP access only)
    ├── admin-assets/          <-- Isolated admin dashboard CSS/JS/fonts
    ├── assets/                <-- Public website static assets
    ├── build/                 <-- Pre-compiled Vite assets (CSS/JS/fonts)
    ├── storage/               <-- Symlink to /home/xqtrqexj/medicare-hms-app/storage/app/public
    ├── index.php              <-- Front controller forwarding to /home/xqtrqexj/medicare-hms-app
    ├── .htaccess              <-- Preserves cPanel PHP handler + Laravel rewrite rules
    ├── favicon.ico
    └── robots.txt
```

---

## 2. Phase A: Local Pre-Deployment Preparation & Packaging

These steps are executed locally on the developer workstation or CI/CD runner prior to uploading anything to the hosting server.

### Step A.1: Verify Codebase, Git Cleanliness & Test Suite
Ensure the local release branch is up to date, passes all automated tests, and has a clean working tree:
```bash
# Verify branch
git branch --show-current
# Expected: main (or dedicated verified release/audit branch)

# Verify status
git status
# Must be clean, no uncommitted changes

# Run full automated test suite against isolated test database
php artisan test
# Verification Gate: Must report 100% pass (164 tests, 1,365 assertions, 0 failures)
```

### Step A.2: Compile Production Frontend Assets
Truehost shared hosting typically lacks Node.js and npm in its default shared SSH environment. Frontend assets **must** be pre-compiled locally:
```bash
npm run build
```
Verify that `public/build/manifest.json`, `public/build/assets/*.css`, and `public/build/assets/*.js` are created cleanly.

### Step A.3: Prepare Deployment Archive Using Verified Packaging Script
Use the automated, verified packaging script which automatically checks asset compilation, enforces strict exclusions (excluding `.git`, `.env*`, `node_modules`, `tests`, `phpunit*.xml`, and temporary logs), and self-verifies the archive:
```bash
# Generate verified release archive to /tmp or designated output directory
./scripts/package_release.sh -o /tmp -n medicare-hms-deploy

# Inspect archive contents and verify absence of secrets or development artifacts:
tar -tzf /tmp/medicare-hms-deploy_*.tar.gz | grep -E '\.env$|\.git|node_modules|tests/' || echo "Clean: No forbidden files found"

# Check SHA256 checksum
sha256sum /tmp/medicare-hms-deploy_*.tar.gz
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
2. Create database: `xqtrqexj_medicare_prod`.
3. Create user: `xqtrqexj_medicare_user` with a strong, randomly generated password (minimum 24 characters).
4. Assign user to database with **ALL PRIVILEGES**.
5. Record database name, user, and password securely in password manager (never commit to repository).

### Step B.3: Deploy Application Code via Git (Recommended) or Archive
Log in via SSH or cPanel Terminal:

#### Method 1: Git-Based Deployment (Recommended)
```bash
# Clone the verified release branch directly outside document root
git clone -b main https://github.com/kennedycheroben/medicare-hms.git /home/xqtrqexj/medicare-hms-app

# For subsequent updates:
cd /home/xqtrqexj/medicare-hms-app
git fetch origin main
git checkout main
git pull origin main
```

#### Method 2: Release Archive Upload (Fallback)
If SSH/Git is unavailable, upload `medicare-hms-deploy.tar.gz` to `/home/xqtrqexj/medicare-hms-app` and extract:
```bash
mkdir -p /home/xqtrqexj/medicare-hms-app
cd /home/xqtrqexj/medicare-hms-app
tar -xzvf medicare-hms-deploy.tar.gz
rm medicare-hms-deploy.tar.gz
```

### Step B.4: Configure Web Document Root & Preserve cPanel Configuration
The public web document root is `/home/xqtrqexj/medicare.kennedycheroben.co.ke`.

1. Copy required public assets and front controller from the application:
   ```bash
   # Copy public assets into public document root
   cp -r /home/xqtrqexj/medicare-hms-app/public/admin-assets /home/xqtrqexj/medicare.kennedycheroben.co.ke/
   cp -r /home/xqtrqexj/medicare-hms-app/public/assets /home/xqtrqexj/medicare.kennedycheroben.co.ke/
   cp -r /home/xqtrqexj/medicare-hms-app/public/build /home/xqtrqexj/medicare.kennedycheroben.co.ke/
   cp /home/xqtrqexj/medicare-hms-app/public/index.php /home/xqtrqexj/medicare.kennedycheroben.co.ke/index.php
   cp /home/xqtrqexj/medicare-hms-app/public/favicon.ico /home/xqtrqexj/medicare.kennedycheroben.co.ke/
   cp /home/xqtrqexj/medicare-hms-app/public/robots.txt /home/xqtrqexj/medicare.kennedycheroben.co.ke/
   ```

2. **Preserve existing `.htaccess` & cPanel PHP configuration:**
   Truehost cPanel automatically writes PHP version handlers into the document root `.htaccess` (e.g., `# php -- BEGIN cPanel-generated handler ... # php -- END cPanel-generated handler`).
   **DO NOT overwrite `.htaccess` blindly.** If `/home/xqtrqexj/medicare.kennedycheroben.co.ke/.htaccess` already exists:
   - Keep the cPanel-generated PHP handler block at the top/bottom.
   - Append the Laravel rewrite directives from `/home/xqtrqexj/medicare-hms-app/public/.htaccess` without removing cPanel configuration lines:
   ```bash
   if [ ! -f /home/xqtrqexj/medicare.kennedycheroben.co.ke/.htaccess ]; then
       cp /home/xqtrqexj/medicare-hms-app/public/.htaccess /home/xqtrqexj/medicare.kennedycheroben.co.ke/.htaccess
   else
       # If .htaccess exists, verify mod_rewrite block is present or append it:
       grep -q "RewriteEngine On" /home/xqtrqexj/medicare.kennedycheroben.co.ke/.htaccess || cat /home/xqtrqexj/medicare-hms-app/public/.htaccess >> /home/xqtrqexj/medicare.kennedycheroben.co.ke/.htaccess
   fi
   ```

### Step B.5: Install Production Dependencies (Composer)
Via SSH on cPanel:
```bash
cd /home/xqtrqexj/medicare-hms-app

# Run Composer using the active PHP version (PHP 8.3 / 8.4)
composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
```

---

## 4. Phase C: Environment Configuration & Security Initialization

### Step C.1: Provision Production `.env`
1. Copy template:
   ```bash
   cd /home/xqtrqexj/medicare-hms-app
   cp .env.production.example .env
   chmod 600 .env
   ```
2. Update `.env` with actual production values:
   - `APP_URL=https://medicare.kennedycheroben.co.ke`
   - `DB_DATABASE=xqtrqexj_medicare_prod`
   - `DB_USERNAME=xqtrqexj_medicare_user`
   - `DB_PASSWORD=YOUR_RECORDED_DATABASE_PASSWORD`
   - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
   - `HOSPITAL_DEMO_MODE=true` (Keep `true` for demonstration deployment so software evaluation notices are shown)
   - `HOSPITAL_ACCREDITATION_NOTICE="Demonstration Installation — Software Evaluation and Workflow Preview"`

### Step C.2: Generate Application Cryptographic Key
```bash
cd /home/xqtrqexj/medicare-hms-app
php artisan key:generate --force
```

### Step C.3: Run Database Migrations
Run database migrations safely against the clean production database:
```bash
cd /home/xqtrqexj/medicare-hms-app
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
cd /home/xqtrqexj/medicare-hms-app

# Set permissions for Laravel runtime directories
chmod -R 775 storage bootstrap/cache

# Safely check and link public media directory:
# Verify that destination is not already an existing physical directory before symlinking
if [ -d "/home/xqtrqexj/medicare.kennedycheroben.co.ke/storage" ] && [ ! -L "/home/xqtrqexj/medicare.kennedycheroben.co.ke/storage" ]; then
    echo "Backing up existing physical storage directory..."
    mv /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage_backup_$(date +%Y%m%d_%H%M%S)
fi

ln -sfn /home/xqtrqexj/medicare-hms-app/storage/app/public /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage
```

### Step C.5: Cache Configuration, Routes, and Views
Optimize runtime performance on shared hosting:
```bash
cd /home/xqtrqexj/medicare-hms-app
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Phase D: Automated Cron Job & Background Tasks

In cPanel **Cron Jobs**, add the standard Laravel scheduled task runner:

```cron
* * * * * cd /home/xqtrqexj/medicare-hms-app && php artisan schedule:run >> /dev/null 2>&1
```

### Database Backup Cron Schedule:
To automate daily database backups using the verified backup script:
```cron
0 2 * * * cd /home/xqtrqexj/medicare-hms-app && ./scripts/backup_database.sh >> storage/logs/backup.log 2>&1
```

---

## 6. Phase E: Post-Deployment Smoke & Verification Checklist

Before releasing the system to hospital staff and patients, complete every verification step:

| Check | Target URL / Flow | Expected Result | Verified By |
| :--- | :--- | :--- | :--- |
| **HTTPS & SSL** | `https://medicare.kennedycheroben.co.ke` | Padlock valid, HTTP strictly redirects to HTTPS | |
| **Public Homepage** | `/` | Departments, doctors, services render without console errors; demo notice visible | |
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
   cd /home/xqtrqexj/medicare-hms-app
   php artisan down --secret="medicare-emergency-access"
   ```
2. Revert codebase to previous verified release archive:
   ```bash
   tar -xzvf medicare-hms-deploy-previous.tar.gz -C /home/xqtrqexj/medicare-hms-app
   cd /home/xqtrqexj/medicare-hms-app
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
3. Bring site back online:
   ```bash
   cd /home/xqtrqexj/medicare-hms-app
   php artisan up
   ```

### Full Database Recovery:
If data corruption occurs, restore the verified pre-deployment backup:
```bash
cd /home/xqtrqexj/medicare-hms-app
./scripts/restore_database.sh \
    --file storage/app/backups/xqtrqexj_medicare_prod_PRE_DEPLOY.sql.gz \
    --target-db xqtrqexj_medicare_prod \
    --confirm
```
