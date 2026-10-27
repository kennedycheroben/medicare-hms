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

### Step B.4: Configure Web Document Root, Inspect Collisions & Preserve cPanel Configuration
The public web document root is `/home/xqtrqexj/medicare.kennedycheroben.co.ke`.

> [!WARNING]
> **Safety Protocol & Non-Silent Overwrite Policy:**
> - Never execute blind `cp -r` or `cp -a` commands directly into the production web root. Having a pre-sync backup is essential, but it does **not** make silent file replacement acceptable.
> - Every destination file and directory must be inspected individually before modification.
> - If an existing destination file or directory differs from the release build, the process **must stop immediately for human review**.
> - Operators must explicitly review differences and provide explicit approval (`ALLOW_REPLACE=1` or `--confirm-replace`) before any file is replaced.
> - Truehost cPanel-generated `.htaccess` configurations (such as PHP version handlers) must be preserved; never replace `.htaccess` wholesale.

#### Option 1: Execute Verified Script (Recommended)
```bash
cd /home/xqtrqexj/medicare-hms-app

# 1. First run inspection in dry-run mode (no files modified)
./scripts/promote_release_assets.sh --dry-run

# 2. If collisions exist, inspect differences. When replacement is verified and intended:
ALLOW_REPLACE=1 ./scripts/promote_release_assets.sh
```

#### Option 2: Step-by-Step SSH Procedure
If executing manually in the shell:

```bash
set -euo pipefail

APP_DIR="/home/xqtrqexj/medicare-hms-app"
WEB_ROOT="/home/xqtrqexj/medicare.kennedycheroben.co.ke"
ALLOW_REPLACE="${ALLOW_REPLACE:-0}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/xqtrqexj/backup_webroot_${TIMESTAMP}"
STAGING_DIR="/home/xqtrqexj/staging_webroot_${TIMESTAMP}"

# 1. Pre-flight sanity checks
if [ ! -d "${APP_DIR}/public" ]; then
    echo "ERROR: Source directory ${APP_DIR}/public does not exist. Aborting." >&2
    exit 1
fi

mkdir -p "${WEB_ROOT}" "${STAGING_DIR}"

# 2. Stage new release assets in isolation
MANAGED_ITEMS=("admin-assets" "assets" "build" "index.php" "favicon.ico" "robots.txt")
for item in "${MANAGED_ITEMS[@]}"; do
    if [ -e "${APP_DIR}/public/${item}" ]; then
        cp -r "${APP_DIR}/public/${item}" "${STAGING_DIR}/${item}"
    fi
done

# 3. Preserve existing cPanel .htaccess directives
if [ -f "${WEB_ROOT}/.htaccess" ]; then
    cp "${WEB_ROOT}/.htaccess" "${STAGING_DIR}/.htaccess"
    if ! grep -q "RewriteEngine On" "${STAGING_DIR}/.htaccess"; then
        echo -e "\n# --- Laravel Front Controller Directives (Added ${TIMESTAMP}) ---" >> "${STAGING_DIR}/.htaccess"
        cat "${APP_DIR}/public/.htaccess" >> "${STAGING_DIR}/.htaccess"
    fi
else
    cp "${APP_DIR}/public/.htaccess" "${STAGING_DIR}/.htaccess"
fi

# 4. Individual Destination Inspection & Collision Gate
COLLISIONS=0
for item in "${MANAGED_ITEMS[@]}" ".htaccess"; do
    DEST_PATH="${WEB_ROOT}/${item}"
    if [ -e "${DEST_PATH}" ]; then
        if [ -d "${DEST_PATH}" ] && ! diff -qr "${STAGING_DIR}/${item}" "${DEST_PATH}" >/dev/null 2>&1; then
            echo "COLLISION DETECTED: ${item}/ differs from staged release."
            COLLISIONS=$((COLLISIONS + 1))
        elif [ -f "${DEST_PATH}" ] && ! cmp -s "${STAGING_DIR}/${item}" "${DEST_PATH}"; then
            echo "COLLISION DETECTED: ${item} differs from staged release."
            COLLISIONS=$((COLLISIONS + 1))
        fi
    fi
done

# 5. Stop for Human Review if Replacements are Required
if [ "${COLLISIONS}" -gt 0 ] && [ "${ALLOW_REPLACE}" != "1" ]; then
    echo "ERROR: Destination contains ${COLLISIONS} item(s) that would be modified or overwritten." >&2
    echo "Silent replacement blocked. Review differences and rerun with ALLOW_REPLACE=1 to approve replacement." >&2
    rm -rf "${STAGING_DIR}"
    exit 1
fi

# 6. Create Pre-Sync Backup and Promote Only Approved Items
if [ "$(ls -A "${WEB_ROOT}" 2>/dev/null)" ]; then
    echo "Backing up web root to ${BACKUP_DIR}..."
    cp -a "${WEB_ROOT}" "${BACKUP_DIR}"
fi

for item in "${MANAGED_ITEMS[@]}" ".htaccess"; do
    if [ -e "${STAGING_DIR}/${item}" ]; then
        if [ -d "${STAGING_DIR}/${item}" ] && [ -d "${WEB_ROOT}/${item}" ]; then
            mv "${WEB_ROOT}/${item}" "${WEB_ROOT}/${item}_prev_${TIMESTAMP}"
            cp -a "${STAGING_DIR}/${item}" "${WEB_ROOT}/${item}"
            rm -rf "${WEB_ROOT}/${item}_prev_${TIMESTAMP}"
        else
            cp -a "${STAGING_DIR}/${item}" "${WEB_ROOT}/${item}"
        fi
        echo "Promoted: ${item}"
    fi
done

rm -rf "${STAGING_DIR}"
echo "Web root safely updated. Existing files preserved."
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

### Step C.4: Configure File Storage Symlink & Least-Privilege Permissions
Configure permissions and safely establish the public media storage symlink.

> [!IMPORTANT]
> **Least-Privilege Shared Hosting Permissions (suPHP / PHP-FPM):**
> On cPanel shared hosting (Truehost), the web server runs PHP under the account user (`xqtrqexj:xqtrqexj`). Group write permissions (`775` or `664`) are **unnecessary** and violate least privilege. In suPHP and hardened cPanel environments, group-writable directories cause **HTTP 500 Internal Server Errors** ("Directory is writable by group").
> - **Directories:** `755` (`u=rwx,go=rx`)
> - **Files:** `644` (`u=rw,go=r`)
> - **Environment (`.env`):** `600` (`u=rw,go=`)

> [!CAUTION]
> **Storage Symlink Safeguards:**
> - Do **not** automatically remove or replace a symlink whose target differs from the expected storage target. It may point to an active alternate mount; stop and require explicit manual review (`CONFIRM_STALE_SYMLINK=1`).
> - Do **not** silently overwrite an existing physical directory at the destination. An existing directory may contain uploaded media; stop and require explicit confirmation (`CONFIRM_DIR_MIGRATION=1`) to back up and link.
> - Broken or dangling symlinks must be surfaced and require operator confirmation to remove and re-point.

#### Option 1: Execute Verified Script (Recommended)
```bash
cd /home/xqtrqexj/medicare-hms-app

# Run defensive storage symlink configurator:
./scripts/configure_storage_symlink.sh
```

#### Option 2: Step-by-Step SSH Procedure
```bash
set -euo pipefail

APP_DIR="/home/xqtrqexj/medicare-hms-app"
WEB_ROOT="/home/xqtrqexj/medicare.kennedycheroben.co.ke"
STORAGE_TARGET="${APP_DIR}/storage/app/public"
STORAGE_LINK="${WEB_ROOT}/storage"
CONFIRM_STALE_SYMLINK="${CONFIRM_STALE_SYMLINK:-0}"
CONFIRM_DIR_MIGRATION="${CONFIRM_DIR_MIGRATION:-0}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# 1. Apply least-privilege permissions (755 directories / 644 files)
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod 755 {} +
find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type f -exec chmod 644 {} +
if [ -f "${APP_DIR}/.env" ]; then
    chmod 600 "${APP_DIR}/.env"
fi

# 2. Validate source storage target exists
if [ ! -d "${STORAGE_TARGET}" ]; then
    mkdir -p "${STORAGE_TARGET}"
    chmod 755 "${STORAGE_TARGET}"
fi

# 3. Evaluate destination state
if [ -L "${STORAGE_LINK}" ]; then
    CURRENT_DEST=$(readlink "${STORAGE_LINK}" || true)
    CURRENT_CANONICAL=$(readlink -f "${STORAGE_LINK}" 2>/dev/null || true)
    EXPECTED_CANONICAL=$(readlink -f "${STORAGE_TARGET}" 2>/dev/null || true)

    if [ "${CURRENT_CANONICAL}" = "${EXPECTED_CANONICAL}" ] && [ -d "${STORAGE_LINK}" ]; then
        echo "STATUS: [HEALTHY] Symlink correctly points to ${STORAGE_TARGET}. No change required."
        exit 0
    elif [ -e "${STORAGE_LINK}" ]; then
        echo "ERROR: Destination symlink points to alternate active target: ${CURRENT_DEST}" >&2
        if [ "${CONFIRM_STALE_SYMLINK}" != "1" ]; then
            echo "SAFETY STOP: Automatic replacement blocked. Review target and rerun with CONFIRM_STALE_SYMLINK=1." >&2
            exit 1
        fi
        rm "${STORAGE_LINK}"
        ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    else
        echo "WARNING: Broken/dangling symlink detected: ${CURRENT_DEST}" >&2
        if [ "${CONFIRM_STALE_SYMLINK}" != "1" ]; then
            echo "SAFETY STOP: Rerun with CONFIRM_STALE_SYMLINK=1 to remove broken link and recreate." >&2
            exit 1
        fi
        rm "${STORAGE_LINK}"
        ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    fi

elif [ -d "${STORAGE_LINK}" ]; then
    echo "WARNING: Destination is an existing physical directory (not a symlink)." >&2
    if [ "${CONFIRM_DIR_MIGRATION}" != "1" ]; then
        echo "SAFETY STOP: Potential media data loss. Review files in ${STORAGE_LINK} and rerun with CONFIRM_DIR_MIGRATION=1." >&2
        exit 1
    fi
    mv "${STORAGE_LINK}" "${STORAGE_LINK}_dir_backup_${TIMESTAMP}"
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
    echo "Physical directory backed up to ${STORAGE_LINK}_dir_backup_${TIMESTAMP}."

elif [ -e "${STORAGE_LINK}" ]; then
    echo "ERROR: Destination ${STORAGE_LINK} is an unexpected file/special node. Stopping for human review." >&2
    exit 1

else
    ln -s "${STORAGE_TARGET}" "${STORAGE_LINK}"
fi

# 4. Final verification
if [ -L "${STORAGE_LINK}" ] && [ -d "${STORAGE_LINK}" ]; then
    echo "SUCCESS: Storage symlink verified: ${STORAGE_LINK} -> $(readlink "${STORAGE_LINK}")"
else
    echo "ERROR: Storage symlink verification failed." >&2
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
