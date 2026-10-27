# MediCare Hospital Management System — Truehost & cPanel Production Deployment Runbook

- **Document Version:** 1.2.0
- **Target Environment:** Truehost Cloud Shared Hosting / cPanel Linux Environment
- **Stack:** PHP 8.3 / 8.4, MariaDB 10.4+ / MySQL 8.0, Apache/LiteSpeed, Laravel 13, Bootstrap, Blade
- **Production Domain:** `medicare.kennedycheroben.co.ke` (HMS Demonstration Platform)
- **cPanel Username:** `xqtrqexj`
- **Target Git Branch:** `fix/release-readiness-audit` (Approved Release Candidate Branch)
- **Deployment Safety Rule:** All audit fixes, demo safeguards, safe promotion scripts, and permission hardening are committed exclusively on `fix/release-readiness-audit`. **DO NOT** deploy from `main` or merge into `main` without explicit owner authorization.
- **Target Application Location:** `/home/xqtrqexj/medicare-hms-app` (strictly outside document root)
- **Public Web Document Root:** `/home/xqtrqexj/medicare.kennedycheroben.co.ke`

---

> [!IMPORTANT]
> **SAFETY & EXECUTION BOUNDARY NOTICE**  
> This runbook is divided into **Preparation Steps** (safe, pre-deployment local packaging and verification) and **Execution Steps** (production server commands).  
> **DO NOT** execute any production server commands until formal authorization is granted, maintenance windows are scheduled, and backups are confirmed.
> **DO NOT** deploy from `main`. The approved release candidate is on `fix/release-readiness-audit`. Merging into `main` requires explicit owner authorization.

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
# Expected: fix/release-readiness-audit

# Verify commit SHA
git rev-parse HEAD
# Record this exact commit SHA in the deployment audit record

# Verify status (must be clean, no uncommitted changes)
git status

# Run full automated test suite against isolated test database
php artisan test
# Verification Gate: Must report 100% pass (174 tests, 1,455 assertions, 0 failures)
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

#### Method 1: Git-Based Deployment via cPanel Git Version Control or SSH (Recommended)

##### Option A: cPanel Git™ Version Control Web Interface
1. In cPanel, navigate to **Git™ Version Control**.
2. Manage the repository at `/home/xqtrqexj/medicare-hms-app`.
3. Under **Pull or Deploy**:
   - Click **Update from Remote** to pull the latest commits from the remote repository.
   - Click **Deploy HEAD Commit** to trigger the automated, non-destructive deployment tasks defined in `.cpanel.yml`.
4. The `.cpanel.yml` deployment tasks safely synchronize the public bridge `index.php`, `favicon.ico`, `robots.txt`, and frontend assets (`admin-assets/`, `assets/`, `build/`) into the public document root `/home/xqtrqexj/medicare.kennedycheroben.co.ke/`, preserving server-specific `.htaccess` configurations and never overwriting `.env` or application secrets.

##### Option B: Terminal / SSH Workflow
```bash
# Clone the verified release candidate branch directly outside document root:
git clone -b fix/release-readiness-audit https://github.com/kennedycheroben/medicare-hms.git /home/xqtrqexj/medicare-hms-app

# For subsequent updates on this branch:
cd /home/xqtrqexj/medicare-hms-app
git fetch origin fix/release-readiness-audit
git checkout fix/release-readiness-audit
git pull origin fix/release-readiness-audit
```

> [!IMPORTANT]
> **Branch & Commit Boundary Enforcement:**
> The approved release fixes (demo safety disclaimers, emergency link suppression, hardened promotion, least-privilege permissions, and defensive symlink management) exist on `fix/release-readiness-audit`.
> **DO NOT** deploy from `main` while changes exist only on this audit branch. **DO NOT** merge into `main` without explicit owner authorization.

#### Method 2: Release Archive Upload (Fallback)
If SSH/Git is unavailable, upload `medicare-hms-deploy.tar.gz` to `/home/xqtrqexj/medicare-hms-app` and extract:
```bash
mkdir -p /home/xqtrqexj/medicare-hms-app
cd /home/xqtrqexj/medicare-hms-app
tar -xzvf medicare-hms-deploy.tar.gz
rm medicare-hms-deploy.tar.gz
```

### Step B.4: Safely Promote Built Assets to Public Web Root
Synchronize public frontend assets (`admin-assets`, `assets`, `build`, `favicon.ico`, `robots.txt`, and front controller `index.php`) from the application build into the public document root `/home/xqtrqexj/medicare.kennedycheroben.co.ke/`.

> [!WARNING]
> **Mandatory Non-Silent Overwrite Policy & Canonical Script Enforcement:**
> - Never execute blind copy commands (such as raw `cp -r` or uninspected recursive directory copying) directly into the production web root. Having a backup does **not** make silent overwrites acceptable.
> - Asset promotion **must** be executed using the canonical tool: `./scripts/promote_release_assets.sh`. Manual shell alternatives are strictly forbidden because they bypass collision gates, retention safeguards, and cPanel `.htaccess` preservation.
> - The script stages assets in isolation, inspects each destination item individually, preserves cPanel-generated `.htaccess` handlers, and **halts with an error if any destination file or directory would be replaced or merged ambiguously**.
> - Operators must explicitly review differences and provide explicit approval (`ALLOW_REPLACE=1` or `--confirm-replace`) before any colliding destination is modified.
> - All backups (`backup_webroot_<timestamp>` and `<item>_prev_<timestamp>`) are permanently retained until a human operator verifies the site and explicitly approves cleanup.

```bash
cd /home/xqtrqexj/medicare-hms-app

# 1. Preview changes and inspect destination web root in dry-run mode (no files modified):
./scripts/promote_release_assets.sh --dry-run

# 2. Inspect collision manifest. If collisions are expected and verified for this release,
# approve replacement explicitly:
ALLOW_REPLACE=1 ./scripts/promote_release_assets.sh
```

#### What `promote_release_assets.sh` Executes:
1. **Isolated Staging:** Copies `admin-assets/`, `assets/`, `build/`, `index.php`, `favicon.ico`, and `robots.txt` into an isolated temporary staging directory (`staging_webroot_<timestamp>`).
2. **cPanel `.htaccess` Preservation:** Reads existing `${WEB_ROOT}/.htaccess` to preserve cPanel-generated PHP handlers (`# php -- BEGIN cPanel-generated handler ... # php -- END cPanel-generated handler`) and merges Laravel front-controller rewrite rules without clobbering.
3. **Collision Detection:** Computes cryptographic/byte comparisons (`cmp -s` and `diff -qr`) against each destination item. Categorizes each item as `[NEW]`, `[UNCHANGED]`, `[COLLISION]`, or `[PRESERVED]`.
4. **Safety Gate:** If collisions are found and `ALLOW_REPLACE=1` is not set, halts execution immediately with an exit code of `1` and lists the conflicting items.
5. **Full Web Root Backup:** Once replacement is explicitly approved, creates a complete timestamped copy of the web root (`backup_webroot_<timestamp>`) before modifying any files.
6. **Atomic Directory Swap & Backup Retention:** Replaces managed directories by renaming the live directory to `<item>_prev_<timestamp>` and moving the new version in place. **Does not delete `<item>_prev_<timestamp>`**; preserves it for immediate rollback.
7. **Rollback Instructions:** Displays retained backup paths and exact rollback commands upon completion. Backups are retained until manual verification and explicit operator cleanup.

To roll back cleanly without leaving orphaned files introduced by this release:
```bash
# Move current web root aside and restore the verified pre-deployment backup cleanly:
mv /home/xqtrqexj/medicare.kennedycheroben.co.ke /home/xqtrqexj/medicare.kennedycheroben.co.ke_failed_<TIMESTAMP>
cp -a /home/xqtrqexj/backup_webroot_<TIMESTAMP> /home/xqtrqexj/medicare.kennedycheroben.co.ke
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

```bash
cd /home/xqtrqexj/medicare-hms-app

# Run defensive storage symlink configurator and apply least-privilege permissions:
./scripts/configure_storage_symlink.sh
```

#### What `configure_storage_symlink.sh` Enforces:
1. **Least-Privilege Shared Hosting Permissions:**
   - Recursively sets `755` (`u=rwx,go=rx`) on directories in `storage/` and `bootstrap/cache/`.
   - Recursively sets `644` (`u=rw,go=r`) on files in `storage/` and `bootstrap/cache/`.
   - Hardens `.env` with `600` (`u=rw,go=`) so only the application user can read secrets.
   - Eliminates group-write (`775` / `664`) to prevent suPHP Apache 500 errors.
2. **Safe Destination Handling & Confirmation Gates:**
   - **Healthy Link:** If `${WEB_ROOT}/storage` already points to the expected storage target, confirms status and exits `0` without making changes.
   - **Unexpected Target:** If the link points to an unexpected active target, halts execution and exits `1`. Requires explicit operator confirmation: `CONFIRM_STALE_SYMLINK=1 ./scripts/configure_storage_symlink.sh`.
   - **Broken Symlink:** If the link is dangling/broken, halts execution and requires `CONFIRM_STALE_SYMLINK=1` to remove and re-point.
   - **Physical Directory:** If a physical directory exists (potential user upload loss), halts execution and exits `1`. Requires `CONFIRM_DIR_MIGRATION=1 ./scripts/configure_storage_symlink.sh` to create a timestamped backup before linking.
   - **Unknown / Special Nodes:** Exits `1` immediately and demands human review.

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
2. Revert application codebase and public web root cleanly:
   ```bash
   # Revert application codebase to previous verified release archive:
   tar -xzvf medicare-hms-deploy-previous.tar.gz -C /home/xqtrqexj/medicare-hms-app
   cd /home/xqtrqexj/medicare-hms-app
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear

   # Revert public document root cleanly (accounts for any newly added files):
   mv /home/xqtrqexj/medicare.kennedycheroben.co.ke /home/xqtrqexj/medicare.kennedycheroben.co.ke_failed_<TIMESTAMP>
   cp -a /home/xqtrqexj/backup_webroot_<TIMESTAMP> /home/xqtrqexj/medicare.kennedycheroben.co.ke
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
