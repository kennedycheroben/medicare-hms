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

### Step B.4: Configure Web Document Root, Stage Assets & Preserve cPanel Configuration
The public web document root is `/home/xqtrqexj/medicare.kennedycheroben.co.ke`.

> [!WARNING]
> **Safety Protocol:** Do not run blind `cp -r` commands directly into the production web root. cPanel generates server-specific PHP handler configurations, and existing custom or staging files can be lost. Always verify paths, create a timestamped backup first, stage updates, and carefully preserve existing `.htaccess` directives.

Run the following defensive synchronization procedure via SSH:

```bash
set -euo pipefail

APP_DIR="/home/xqtrqexj/medicare-hms-app"
WEB_ROOT="/home/xqtrqexj/medicare.kennedycheroben.co.ke"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/xqtrqexj/backup_webroot_${TIMESTAMP}"
STAGING_DIR="/home/xqtrqexj/staging_webroot_${TIMESTAMP}"

# 1. Pre-flight sanity checks
if [ ! -d "${APP_DIR}/public" ]; then
    echo "ERROR: Source directory ${APP_DIR}/public does not exist. Aborting." >&2
    exit 1
fi

if [ ! -d "${WEB_ROOT}" ]; then
    echo "Creating destination web root directory ${WEB_ROOT}..."
    mkdir -p "${WEB_ROOT}"
fi

# 2. Defensive Backup: If web root already contains files, back them up before making any changes
if [ "$(ls -A "${WEB_ROOT}" 2>/dev/null)" ]; then
    echo "Existing files detected in ${WEB_ROOT}. Creating full archive backup at ${BACKUP_DIR}..."
    cp -a "${WEB_ROOT}" "${BACKUP_DIR}"
    echo "Backup completed successfully."
fi

# 3. Create isolated staging directory for new release assets
mkdir -p "${STAGING_DIR}"

# Stage frontend assets from application build
echo "Staging production assets..."
cp -r "${APP_DIR}/public/admin-assets" "${STAGING_DIR}/"
cp -r "${APP_DIR}/public/assets" "${STAGING_DIR}/"
cp -r "${APP_DIR}/public/build" "${STAGING_DIR}/"
cp "${APP_DIR}/public/index.php" "${STAGING_DIR}/index.php"
cp "${APP_DIR}/public/favicon.ico" "${STAGING_DIR}/favicon.ico"
cp "${APP_DIR}/public/robots.txt" "${STAGING_DIR}/robots.txt"

# 4. Handle .htaccess preservation safely
# Truehost cPanel frequently prepends/appends PHP handler blocks:
# e.g., "# php -- BEGIN cPanel-generated handler ... # php -- END cPanel-generated handler"
if [ -f "${WEB_ROOT}/.htaccess" ]; then
    echo "Existing .htaccess detected in ${WEB_ROOT}. Checking for cPanel handlers..."
    # Preserve existing .htaccess as base
    cp "${WEB_ROOT}/.htaccess" "${STAGING_DIR}/.htaccess"

    # If Laravel rewrite rules are not present, safely append them
    if ! grep -q "RewriteEngine On" "${STAGING_DIR}/.htaccess"; then
        echo "Appending Laravel rewrite directives to existing .htaccess..."
        echo -e "\n# --- Laravel Front Controller Directives (Added ${TIMESTAMP}) ---" >> "${STAGING_DIR}/.htaccess"
        cat "${APP_DIR}/public/.htaccess" >> "${STAGING_DIR}/.htaccess"
    else
        echo "Laravel rewrite directives already present in .htaccess."
    fi
else
    echo "No existing .htaccess found. Using application default .htaccess."
    cp "${APP_DIR}/public/.htaccess" "${STAGING_DIR}/.htaccess"
fi

# 5. Review before replacing (Safe Promotion)
# Ensure destination directories are safe to update:
for item in admin-assets assets build index.php favicon.ico robots.txt .htaccess; do
    if [ -e "${WEB_ROOT}/${item}" ] && [ ! -w "${WEB_ROOT}/${item}" ]; then
        echo "ERROR: Destination ${WEB_ROOT}/${item} is not writable. Human review required." >&2
        exit 1
    fi
done

# Copy staged files into document root safely
cp -a "${STAGING_DIR}/." "${WEB_ROOT}/"

# Clean up temporary staging directory
rm -rf "${STAGING_DIR}"

echo "Web document root safely updated. Existing files preserved and backed up to ${BACKUP_DIR}."
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

### Step C.4: Configure File Storage Symlink, Directory Safeguards & Permissions
Configure permissions and safely establish the public media storage symlink.

> [!CAUTION]
> **Storage Symlink Safeguards:**
> - Never overwrite an existing physical directory at the destination with `ln -sfn` without backing it up; doing so will cause upload loss or create a nested link inside the directory.
> - Broken or stale symlinks must be cleanly removed and recreated.
> - Source storage target must be validated before symlinking.
> - If an unexpected file or permission issue is encountered, stop and require manual review.

Run the following safe symlink configuration script via SSH:

```bash
set -euo pipefail

APP_DIR="/home/xqtrqexj/medicare-hms-app"
WEB_ROOT="/home/xqtrqexj/medicare.kennedycheroben.co.ke"
STORAGE_TARGET="${APP_DIR}/storage/app/public"
STORAGE_LINK="${WEB_ROOT}/storage"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# 1. Set runtime permissions for Laravel writable directories
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# 2. Validate source storage target exists
if [ ! -d "${STORAGE_TARGET}" ]; then
    echo "Creating public storage target directory: ${STORAGE_TARGET}..."
    mkdir -p "${STORAGE_TARGET}"
fi

# 3. Evaluate destination state at ${STORAGE_LINK}
if [ -L "${STORAGE_LINK}" ]; then
    CURRENT_DEST=$(readlink "${STORAGE_LINK}" || true)
    echo "Existing symlink detected at ${STORAGE_LINK} pointing to: ${CURRENT_DEST}"
    if [ "${CURRENT_DEST}" = "${STORAGE_TARGET}" ]; then
        echo "Symlink is already correctly configured and points to target. Validating target access..."
        if [ -d "${STORAGE_LINK}" ]; then
            echo "Storage link is healthy and operational. No changes required."
        else
            echo "ERROR: Symlink exists but target is inaccessible. Stop and require human review." >&2
            exit 1
        fi
    else
        echo "Symlink points to an alternate or stale target. Safely removing stale symlink..."
        rm "${STORAGE_LINK}"
        ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
        echo "Symlink updated to ${STORAGE_TARGET}."
    fi
elif [ -d "${STORAGE_LINK}" ]; then
    # Destination is a physical directory (NOT a symlink)
    echo "WARNING: A physical directory already exists at ${STORAGE_LINK}."
    echo "To prevent silent data loss, moving physical directory to backup: ${STORAGE_LINK}_dir_backup_${TIMESTAMP}"
    mv "${STORAGE_LINK}" "${STORAGE_LINK}_dir_backup_${TIMESTAMP}"
    echo "Physical directory backed up. Creating symlink to ${STORAGE_TARGET}..."
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    echo "IMPORTANT: Inspect ${STORAGE_LINK}_dir_backup_${TIMESTAMP} for any previously uploaded media files that should be migrated to ${STORAGE_TARGET}."
elif [ -e "${STORAGE_LINK}" ]; then
    # Destination exists as a regular file or special node
    echo "ERROR: Destination ${STORAGE_LINK} exists as an unexpected file type (not directory or symlink). Stopping for human review." >&2
    exit 1
else
    # Destination does not exist; create symlink
    echo "Creating new public storage symlink..."
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
fi

# 4. Final verification
if [ -L "${STORAGE_LINK}" ] && [ -d "${STORAGE_LINK}" ]; then
    echo "Storage symlink successfully verified: ${STORAGE_LINK} -> $(readlink "${STORAGE_LINK}")"
else
    echo "ERROR: Storage symlink verification failed. Immediate manual investigation required." >&2
    exit 1
fi
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
