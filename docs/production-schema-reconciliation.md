# MediCare HMS — Production Database Reconciliation & Schema Audit

**Document Reference:** `docs/production-schema-reconciliation.md`  
**Audit Date:** October 28, 2026  
**Auditor:** Senior Laravel Architecture & Security Engineer  
**Target Repository:** `kennedycheroben/medicare-hms`  
**Current Target Commit:** `42037275b08f6c4b7420d84507283ee5bbf021af` (Head of `main`)  
**Production Database:** `xqtrqexj_medicare`  
**Target Environment:** Truehost cPanel, MySQL 8.0.41, PHP 8.4, Laravel 13  
**Live Site URL:** `https://medicare.kennedycheroben.co.ke`  
**Audit Nature:** Comprehensive read-only audit and safe baseline reconciliation strategy. Zero live database modification.

---

## 1. Executive Summary

### 1.1 Background & Problem Statement
During deployment preparation and release audits for the MediCare HMS platform, Laravel Artisan reported **all 24 database migrations as "Pending"**, despite the production database `xqtrqexj_medicare` actively hosting 28 tables and containing critical production records (11 staff accounts, 2 published news articles, 36 security audit logs, and live contact inquiries).

Inspection of the live database backup (`xqtrqexj_medicare.sql`) confirmed that the `migrations` table exists in the production database but contains **0 rows**. 

If an operator were to blindly execute `php artisan migrate` on production:
1. Laravel would attempt to run Migration 1 (`0001_01_01_000000_create_users_table.php`).
2. The MySQL engine would immediately fail with fatal error: `SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'users' already exists`.
3. The migration process would abort instantly.
4. If a destructive command (such as `migrate:fresh` or `migrate:reset`) were run, all live staff accounts, published articles, patient records, and audit logs would be irrevocably wiped.

### 1.2 Core Audit Findings
Forensic comparison between the 24 repository migration files and the confirmed live database schema revealed:
1. **Migrations 1 through 17, and Migrations 19 through 22 are 100% physically present** in the production schema. All tables, columns, indexes, and foreign key constraints introduced by these 21 migrations exist on the live database.
2. **Migration 18 (`phase6_refinements`) is partially applied**: all added columns (`prescriptions.submission_token`, `prescription_items.non_dispense_reason`, `dispensings.idempotency_key`, `stock_movements.reason`) and unique indexes exist, but the table check constraint `medicine_batches_qty_nonneg` (`CHECK (quantity_current >= 0)`) is **missing**.
3. **Migration 23 (`add_status_to_doctors_table`) is NOT applied**: the `doctors.status` column and the composite index `doctors(status, is_published)` are missing.
4. **Migration 24 (`create_notifications_table`) is NOT applied**: the `notifications` table is completely missing.
5. **Critical Application Runtime Blocker**: The absence of the `notifications` table causes `AppointmentController::store()`, `ContactController::submit()`, and doctor approval workflows to throw fatal 500 exceptions because `NotificationService` dispatches events through Laravel's `database` channel (`HospitalNotification::via() => ['database']`).
6. **Data Content Status**:
   - `users`: 11 preserved staff accounts (Super Admin, Hospital Director, 4 Doctors, Nurse, Pharmacist, Lab Technician, Cashier, Receptionist).
   - `articles`: 2 preserved published articles with uploaded cover images.
   - `audit_logs`: 36 preserved immutable audit entries.
   - `contact_inquiries`: 1 preserved live inquiry.
   - `departments`: 0 rows (causes empty public departments section and 404s on department routes).
   - `doctors`: 0 rows (causes empty doctor directory and prevents doctor staff accounts from linking to clinical profiles).

---

## 2. Complete Migration-by-Migration Reconciliation Matrix

Below is the forensic analysis of all 24 migration files in `/database/migrations/`, comparing every table, column, index, foreign key, and constraint against the confirmed live production schema.

| # | Migration File Name | Expected Side Effects (`up()` / `down()`) | Live Schema Status | Forensic Evidence from Live Database Backup | Action Required |
|---|---|---|---|---|---|
| **1** | `0001_01_01_000000_create_users_table.php` | **up():** Creates `users`, `password_reset_tokens`, `sessions`<br>**down():** Drops the 3 tables | **VERIFIED MATCH**<br>(100% Present) | Tables `users`, `password_reset_tokens`, `sessions` exist with full primary keys, indexes, and timestamps. `users` holds 11 active accounts. | Mark as applied in baseline (Batch 1). Do not rerun. |
| **2** | `0001_01_01_000001_create_cache_table.php` | **up():** Creates `cache`, `cache_locks`<br>**down():** Drops both tables | **VERIFIED MATCH**<br>(100% Present) | Both tables exist with `key` as primary key and expiration indexes. Holds live cache locks and application state. | Mark as applied in baseline (Batch 1). Do not rerun. |
| **3** | `0001_01_01_000002_create_jobs_table.php` | **up():** Creates `jobs`, `job_batches`, `failed_jobs`<br>**down():** Drops all 3 tables | **VERIFIED MATCH**<br>(100% Present) | Tables `jobs`, `job_batches`, `failed_jobs` exist with UUID indexes and connection/queue composite index. | Mark as applied in baseline (Batch 1). Do not rerun. |
| **4** | `2026_10_20_000634_create_departments_table.php` | **up():** Creates `departments` (slug unique index, JSON services, is_active, sort_order)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with exact column definitions. Currently 0 rows. | Mark as applied in baseline (Batch 1). Populate data via seeder. |
| **5** | `2026_10_20_000635_create_doctors_table.php` | **up():** Creates `doctors` (FK `department_id` ON DELETE CASCADE, slug unique index, ratings, availability)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with exact base columns and FK to `departments`. Currently 0 rows. | Mark as applied in baseline (Batch 1). |
| **6** | `2026_10_20_000636_create_appointments_table.php` | **up():** Creates `appointments` (reference_code unique, FKs `department_id` and `doctor_id` ON DELETE SET NULL, status enum)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with nullable FKs to departments and doctors. (Status column was upgraded to varchar(30) in migration 19). | Mark as applied in baseline (Batch 1). |
| **7** | `2026_10_20_000637_create_contact_inquiries_table.php` | **up():** Creates `contact_inquiries` (ticket_number unique index, status enum)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with exact columns. Contains 1 live patient inquiry (`beatrice@gmail.com`). | Mark as applied in baseline (Batch 1). |
| **8** | `2026_10_21_010001_add_role_and_status_to_users_table.php` | **up():** Adds `role`, `is_active`, `department_id` (FK), `doctor_id` (FK), `last_login_at`, `last_login_ip` to `users`<br>**down():** Drops FKs and columns | **VERIFIED MATCH**<br>(100% Present) | `users` table contains all 6 columns, indexes, and FKs `department_id` and `doctor_id`. | Mark as applied in baseline (Batch 1). |
| **9** | `2026_10_21_010002_create_audit_logs_table.php` | **up():** Creates `audit_logs` (FK `user_id` ON DELETE SET NULL, actor_role, action, outcome indexes)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with exact structure. Contains 36 live immutable security audit log entries. | Mark as applied in baseline (Batch 1). |
| **10** | `2026_10_21_020001_create_patients_table.php` | **up():** Creates `patients` (mrn unique, demographics, emergency contact, FK `registered_by` ON DELETE SET NULL)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with all columns, indexes, and FKs. Currently 0 rows. | Mark as applied in baseline (Batch 1). |
| **11** | `2026_10_21_020002_add_patient_id_to_appointments_table.php` | **up():** Adds `patient_id` (FK `patients` ON DELETE SET NULL) to `appointments`<br>**down():** Drops FK and column | **VERIFIED MATCH**<br>(100% Present) | `appointments` table contains `patient_id` placed after `id` with active FK constraint `appointments_patient_id_foreign`. | Mark as applied in baseline (Batch 1). |
| **12** | `2026_10_21_020003_create_visits_table.php` | **up():** Creates `visits` (visit_number unique, FKs `patient_id`, `appointment_id`, `department_id`, `doctor_id`, `checked_in_by`)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with all columns, priority/status enums, and foreign keys. | Mark as applied in baseline (Batch 1). |
| **13** | `2026_10_21_020004_create_triage_records_table.php` | **up():** Creates `triage_records` (FK `visit_id` unique, vitals, triage_category, FKs to nurse, referred dept/doctor)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with full clinical vitals schema, triage category enum, and handoff foreign keys. | Mark as applied in baseline (Batch 1). |
| **14** | `2026_10_21_030001_create_consultations_table.php` | **up():** Creates `consultations` and `consultation_addenda` (FKs `visit_id`, `patient_id`, `doctor_id`, `clinician_id`, diagnosis, ICD-11)<br>**down():** Drops both tables | **VERIFIED MATCH**<br>(100% Present) | Both tables exist with clinical documentation fields, addendum audit trail, and signing timestamps. | Mark as applied in baseline (Batch 1). |
| **15** | `2026_10_21_030002_create_medicines_and_inventory_tables.php` | **up():** Creates `medicines`, `medicine_batches`, `stock_movements`<br>**down():** Drops all 3 tables | **VERIFIED MATCH**<br>(100% Present) | All 3 tables exist with brand/generic indexes, batch unique constraints (`medicine_id`, `batch_number`), and movement audit trail. | Mark as applied in baseline (Batch 1). |
| **16** | `2026_10_21_030003_create_prescriptions_table.php` | **up():** Creates `prescriptions` and `prescription_items`<br>**down():** Drops both tables | **VERIFIED MATCH**<br>(100% Present) | Both tables exist with prescription number unique, prescriber FK, and items dispense tracking. | Mark as applied in baseline (Batch 1). |
| **17** | `2026_10_21_030004_create_dispensings_table.php` | **up():** Creates `dispensings` (dispensation_number unique, FKs to prescription, item, medicine, batch, user)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with all relational FKs and pharmacist notes. | Mark as applied in baseline (Batch 1). |
| **18** | `2026_10_21_030005_phase6_refinements.php` | **up():**<br>1. Makes consultation notes nullable<br>2. Adds `prescriptions.submission_token` (unique)<br>3. Adds `prescription_items.non_dispense_reason`<br>4. Adds `dispensings.idempotency_key` (composite unique with `medicine_batch_id`)<br>5. Adds `stock_movements.reason`<br>6. Adds CHECK constraint `medicine_batches_qty_nonneg`<br>**down():** Drops constraint, drops added columns and unique indexes | **PARTIAL MATCH**<br>(Missing Check Constraint) | **Present:** `prescriptions.submission_token` & unique index; `prescription_items.non_dispense_reason`; `dispensings.idempotency_key` & `dispensings_idem_batch_unique`; `stock_movements.reason`; nullable consultation columns.<br>**MISSING:** Check constraint `medicine_batches_qty_nonneg`. | Reconcile missing CHECK constraint via targeted DDL statement, then mark as applied (Batch 1). |
| **19** | `2026_10_22_000001_enhance_appointments_for_staff_management.php` | **up():** Alters `appointments.status` to varchar(30); adds `cancellation_reason`, `reschedule_reason`, `confirmed_at`/`confirmed_by`, `cancelled_at`/`cancelled_by`, `rescheduled_at`/`rescheduled_by`, index `[status, appointment_date]`<br>**down():** Drops index, FKs, and columns | **VERIFIED MATCH**<br>(100% Present) | All 8 audit columns, 3 user foreign keys, and composite index `(status, appointment_date)` exist on `appointments`. | Mark as applied in baseline (Batch 1). |
| **20** | `2026_10_22_000002_add_publication_and_reviews_to_doctors.php` | **up():** Adds `is_published`, `published_at`, `approved_by` (FK), `publication_notes` to `doctors`; creates `doctor_reviews`<br>**down():** Drops `doctor_reviews`, drops doctor publication columns | **VERIFIED MATCH**<br>(100% Present) | `doctors` contains `is_published`, `published_at`, `approved_by`, `publication_notes`. Table `doctor_reviews` exists with visit unique constraint. | Mark as applied in baseline (Batch 1). |
| **21** | `2026_10_22_000003_create_articles_table.php` | **up():** Creates `articles` (slug unique, category, cover_image_path, FKs `created_by`, `published_by`, status, published_at index)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with exact columns and indexes. Contains 2 live published news articles. | Mark as applied in baseline (Batch 1). Preserve rows. |
| **22** | `2026_10_22_000004_create_patient_accounts_table.php` | **up():** Creates `patient_accounts` (FK `patient_id` unique cascade, token_hash index, must_change_password)<br>**down():** Drops table | **VERIFIED MATCH**<br>(100% Present) | Table exists with SHA-256 token hashing structure, security expiry timestamps, and unique patient link. | Mark as applied in baseline (Batch 1). |
| **23** | `2026_10_27_234320_add_status_to_doctors_table.php` | **up():** Adds `status` (varchar 30 default 'approved') to `doctors`; adds index `[status, is_published]`<br>**down():** Drops index and column | **VERIFIED MISSING** | `doctors` table currently lacks `status` column and index `(status, is_published)`. | **MUST RUN VIA ARTISAN MIGRATE.** |
| **24** | `2026_10_27_235338_create_notifications_table.php` | **up():** Creates `notifications` (uuid PK, type, morphs `notifiable`, data text, read_at timestamp)<br>**down():** Drops table | **VERIFIED MISSING** | Table `notifications` does not exist in the production database. | **MUST RUN VIA ARTISAN MIGRATE.** |

---

## 3. Confirmed Gaps and Remaining Unknowns

### 3.1 Confirmed Gaps on Production
1. **Empty Migration Registry (`migrations` table has 0 rows)**:
   - Laravel cannot determine which migrations have executed.
   - Any raw `php artisan migrate` command fails on migration 1 because `users` already exists.
2. **Missing `doctors.status` Column & Index**:
   - `app/Models/Doctor.php` defines `scopePublished()`:
     ```php
     return $query->where('is_published', true)
                  ->where('is_available', true)
                  ->where('status', 'approved');
     ```
   - In the absence of `doctors.status`, any query calling `Doctor::published()` on production triggers:
     `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'status' in 'where clause'`.
   - This breaks `PageController::home()`, `PageController::about()`, `PageController::departments()`, `PageController::doctors()`, and `AppointmentController::create()`.
3. **Missing `notifications` Table**:
   - `HospitalNotification::via()` routes exclusively to the `database` driver.
   - Any appointment booking (`AppointmentController::store`), contact inquiry (`ContactController::submit`), or doctor profile submission/approval immediately attempts to insert into `notifications`, throwing:
     `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'xqtrqexj_medicare.notifications' doesn't exist`.
4. **Missing Check Constraint on `medicine_batches`**:
   - `medicine_batches_qty_nonneg` (`CHECK (quantity_current >= 0)`) is absent.
   - While application-level checks in `InventoryService` prevent negative stock, the database-level defensive constraint is unenforced.
5. **Empty `departments` (0 rows) and `doctors` (0 rows)**:
   - The public website cannot display clinical services or medical specialists.
   - The 4 registered doctor staff accounts (`sarah@gmail.com`, `eunas@gmail.com`, `john@gmail.com`, `abigael@gmail.com`) currently have `doctor_id = null`, preventing them from using the doctor consultation portal.

### 3.2 Remaining Unknowns & Server Verification Requirements
1. **Storage Symlink Verification**:
   - Truehost cPanel uses two distinct directories:
     - Application Root: `/home/xqtrqexj/medicare-hms-app/`
     - Public Document Root: `/home/xqtrqexj/medicare.kennedycheroben.co.ke/`
   - Unknown: Whether the symbolic link `/home/xqtrqexj/medicare.kennedycheroben.co.ke/storage` actively and correctly points to `/home/xqtrqexj/medicare-hms-app/storage/app/public`.
   - If missing, news cover images and uploaded doctor photos return HTTP 404.
2. **Physical Existence of Cover Images on Disk**:
   - Live database `articles` table references two cover image files:
     - Article 1: `news-covers/DuG2Yxva9itURZoLqyD6punzJmMlU64IPwLzinVw.png`
     - Article 2: `news-covers/SdyWedjEwWRfbDnrA0pUAeLCdI1otv08qggfi0N0.jpg`
   - Unknown: Whether these two physical files exist inside `/home/xqtrqexj/medicare-hms-app/storage/app/public/news-covers/` on the server disk. (If omitted during a Git deployment, the graceful fallback displays the Bootstrap placeholder).
3. **Environment Production Flags**:
   - Must verify that production `.env` specifies:
     - `APP_ENV=production`
     - `APP_DEBUG=false`
     - `FILESYSTEM_DISK=public` (or default disk local with public disk configured)
     - `APP_URL=https://medicare.kennedycheroben.co.ke`

---

## 4. Public Visibility and Booking Findings

### 4.1 Department Visibility Requirements
For a department to appear on the public website:
1. `departments.is_active` must be `true` (or `1`).
2. Public query logic:
   - `PageController::home()` queries `Department::where('is_active', true)->orderBy('sort_order')->take(6)->get()`.
   - `PageController::departments()` queries `Department::withCount(['doctors' => fn($q) => $q->published()])->where('is_active', true)->orderBy('sort_order')->get()`.
   - `PageController::departmentDetail(slug)` executes `firstOrFail()` requiring `where('slug', $slug)->where('is_active', true)`.
3. Inactive departments:
   - Excluded from lists.
   - Directly requesting an inactive department's URL safely returns HTTP 404 (preventing unauthorized clinical discovery).

### 4.2 Doctor Visibility and Approval Requirements
For a doctor to appear in the public directory or homepage:
1. `Doctor::scopePublished()` enforces **three cumulative conditions**:
   $$\text{Visible} \iff (\text{is\_published} = \text{true}) \land (\text{is\_available} = \text{true}) \land (\text{status} = \text{'approved'})$$
2. **Audit of Seeded Doctors in `HospitalDataSeeder`**:
   - `HospitalDataSeeder.php` seeds 6 doctors with:
     - `'is_available' => true`
     - `'is_published'` is **omitted** from the seeder array, but its database default in Migration 20 is `true` (`1`).
     - `'status'` is **omitted** from the seeder array, but its database default in Migration 23 is `'approved'`.
   - **Crucial Caution**: If `HospitalDataSeeder` is executed *before* Migration 23 is applied, the `status` column does not exist. If executed *after* Migration 23 is applied, MySQL will assign `'approved'`, making them published.
   - To guarantee visibility without relying on implicit default behavior, the post-seeding reconciliation sequence must explicitly verify and enforce:
     ```sql
     UPDATE doctors 
     SET status = 'approved', is_published = 1, is_available = 1, published_at = NOW() 
     WHERE status IS NULL OR status = 'approved';
     ```
3. **Self-Service Profile Protection**:
   - When a doctor edits their profile in `DoctorProfileController::update()`, the application deliberately sets:
     `'status' => 'pending_review'`, `'is_published' => false`.
   - This immediately hides the profile from public view until the Super Admin or Hospital Director reviews credentials and clicks "Approve and Publish" in `DoctorManagementController::publish()`.

### 4.3 Appointment Booking Validation & Optional Department Intake
1. **Validation Design in `AppointmentController::store()`**:
   ```php
   $validated = $request->validate([
       'patient_name'     => ['required', 'string', 'min:3', 'max:100'],
       'patient_email'    => ['required', 'email', 'max:100'],
       'patient_phone'    => ['required', 'string', new ValidPhoneNumber],
       'department_id'    => ['nullable', 'integer', 'exists:departments,id'],
       'doctor_id'        => ['nullable', 'integer', 'exists:doctors,id'],
       'appointment_date' => ['required', 'date', 'after_or_equal:today'],
       'appointment_time' => ['required', 'string'],
   ]);
   ```
2. **Evaluation of "Optional Department"**:
   - **Should missing department block booking? NO.**
   - In healthcare intake, patients frequently experience non-specific symptoms (e.g. chronic fatigue, acute fever, headache) and do not know whether they require Internal Medicine, Neurology, or General Practice.
   - Forcing patients to select a department creates incorrect bookings and triage confusion.
   - When `department_id` and `doctor_id` are omitted:
     - The appointment is recorded with `department_id = NULL` and `doctor_id = NULL`.
     - System auto-generates `triage_notes`:
       `"General Intake: Unassigned specialty. Awaiting receptionist/clinical triage to assign appropriate department and doctor."`
     - The staff management queue displays a `General Intake` badge, signaling reception staff to assign an appropriate department upon clinical intake.
3. **Integrity Enforcement**:
   - If a department *is* selected, `'exists:departments,id'` and `is_active` validation prevents orphan or defunct clinic bookings.
   - If a doctor *is* selected without a department, the system auto-infers `$departmentId = $doctor->department_id`.
   - If both doctor and department are selected, the controller strictly verifies that the doctor belongs to the specified department:
     ```php
     if ((int) $doctor->department_id !== (int) $departmentId) {
         return back()->withInput()->withErrors(['doctor_id' => 'The selected doctor does not belong to the selected department.']);
     }
     ```

---

## 5. News Media, Image Uploads, and Storage Architecture

### 5.1 Upload Configuration & Security
- Admin news uploads are handled by `app/Http/Controllers/Admin/ArticleController.php`.
- Strict validation rules:
  ```php
  'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048']
  ```
- Upload storage path: `$request->file('cover_image')->store('news-covers', 'public')`.
- Uploads are assigned cryptographically random 40-character filenames (preventing path traversal and filename collisions).
- Safe Replacement Deletion: When an existing article's cover image is replaced or removed, `ArticleController::update()` saves the database record *first*, checks whether any other article shares the old image path, and only then executes `Storage::disk('public')->delete($oldCover)`.

### 5.2 Public Storage Symlink in cPanel Environment
- In `config/filesystems.php`, the `public` disk is configured as:
  - Root: `storage_path('app/public')` (`/home/xqtrqexj/medicare-hms-app/storage/app/public`)
  - URL: `env('APP_URL') . '/storage'` (`https://medicare.kennedycheroben.co.ke/storage`)
- In Truehost cPanel, incoming HTTP requests are served from the document root `/home/xqtrqexj/medicare.kennedycheroben.co.ke/`.
- Therefore, the symlink must connect:
  ```
  /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage -> /home/xqtrqexj/medicare-hms-app/storage/app/public
  ```
- The deployment file `.cpanel.yml` automates this check on line 14:
  ```yaml
  - /bin/bash -c "if [ ! -e ${DEPLOYPATH:-/home/xqtrqexj/medicare.kennedycheroben.co.ke}/storage ] && [ -d /home/xqtrqexj/medicare-hms-app/storage/app/public ]; then /bin/ln -s /home/xqtrqexj/medicare-hms-app/storage/app/public ${DEPLOYPATH:-/home/xqtrqexj/medicare.kennedycheroben.co.ke}/storage; fi"
  ```

### 5.3 Graceful Degradation and Image URL Generation
- In `app/Models/Article.php`, `coverUrl()` performs multi-tier resolution:
  1. If `cover_image_path` is null, returns `null`.
  2. If an external URL (`http://` or `https://`), returns as-is.
  3. If `Storage::disk('public')->exists($this->cover_image_path)`, returns public URL (`/storage/news-covers/...`).
  4. If file exists in `public_path()`, returns `asset(...)`.
  5. Otherwise, returns `null`.
- In Blade views (`news.blade.php`, `news-show.blade.php`):
  - When `coverUrl()` is null, renders an accessible SVG/Bootstrap card placeholder (`bi-newspaper`).
  - When `coverUrl()` is non-null, includes an inline `onerror` fallback:
    ```html
    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-100 bg-light d-flex align-items-center justify-content-center text-muted\' style=\'height: 200px;\'><i class=\'bi bi-newspaper fs-1 text-primary opacity-50\'></i></div>';"
    ```
  - This guarantees that missing physical files or broken symlinks will never show a broken browser image icon on the live website.

---

## 6. Proposed Safe Repair and Migration-Baseline Strategy

### 6.1 Guiding Safety Principles
1. **Never use `migrate:fresh`, `migrate:reset`, or `migrate:rollback`**.
2. **Never truncate or drop existing tables**.
3. **Do not mark unverified migrations as complete**.
4. **Reconcile schema gaps *before* recording migration completion**.
5. **Run the two latest missing migrations through standard Artisan migration runners**.

---

### 6.2 Step-by-Step Implementation Sequence

#### Phase 1: Pre-Flight Safety Verification
Ensure a verified, uncompressed SQL dump of `xqtrqexj_medicare` is secured locally (already confirmed at `/home/cheroben/Downloads/xqtrqexj_medicare.sql`). Verify that table row counts are recorded:
- `users`: 11 rows
- `articles`: 2 rows
- `audit_logs`: 36 rows
- `contact_inquiries`: 1 row

#### Phase 2: Reconcile Missing Check Constraint on `medicine_batches`
Since MySQL 8.0.41 fully supports table check constraints, execute the missing statement from Migration 18 directly on the database:
```sql
ALTER TABLE medicine_batches 
ADD CONSTRAINT medicine_batches_qty_nonneg CHECK (quantity_current >= 0);
```
*Rationale:* `medicine_batches` contains 0 rows; the constraint binds instantaneously with zero lock contention and zero validation failure.

#### Phase 3: Baseline Migrations 1 Through 22
With all physical schema effects of Migrations 1 through 22 now 100% verified and reconciled on the production database, insert the baseline history records into `migrations` under `batch = 1`:

```sql
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_10_20_000634_create_departments_table', 1),
('2026_10_20_000635_create_doctors_table', 1),
('2026_10_20_000636_create_appointments_table', 1),
('2026_10_20_000637_create_contact_inquiries_table', 1),
('2026_10_21_010001_add_role_and_status_to_users_table', 1),
('2026_10_21_010002_create_audit_logs_table', 1),
('2026_10_21_020001_create_patients_table', 1),
('2026_10_21_020002_add_patient_id_to_appointments_table', 1),
('2026_10_21_020003_create_visits_table', 1),
('2026_10_21_020004_create_triage_records_table', 1),
('2026_10_21_030001_create_consultations_table', 1),
('2026_10_21_030002_create_medicines_and_inventory_tables', 1),
('2026_10_21_030003_create_prescriptions_table', 1),
('2026_10_21_030004_create_dispensings_table', 1),
('2026_10_21_030005_phase6_refinements', 1),
('2026_10_22_000001_enhance_appointments_for_staff_management', 1),
('2026_10_22_000002_add_publication_and_reviews_to_doctors', 1),
('2026_10_22_000003_create_articles_table', 1),
('2026_10_22_000004_create_patient_accounts_table', 1);
```

*Note on Safe Resumability:* The `INSERT IGNORE` keyword prevents primary/unique key collisions if execution is interrupted and resumed, guaranteeing safe idempotency.

#### Phase 4: Verify Migration Status via Artisan
Run in terminal on the server:
```bash
php artisan migrate:status
```
**Expected Output:**
- Migrations 1 through 22: **Ran** (Batch 1)
- `2026_10_27_234320_add_status_to_doctors_table`: **Pending**
- `2026_10_27_235338_create_notifications_table`: **Pending**

#### Phase 5: Execute Pending Migrations via Artisan
Run the standard Laravel migration runner:
```bash
php artisan migrate --force
```
**Execution Effect:**
1. Executes `2026_10_27_234320_add_status_to_doctors_table`:
   - Adds `status` VARCHAR(30) DEFAULT `'approved'` to `doctors`.
   - Adds composite index `doctors(status, is_published)`.
2. Executes `2026_10_27_235338_create_notifications_table`:
   - Creates `notifications` table with UUID primary key, polymorphic morphs, data payload, and read timestamps.
3. Automatically inserts both migration records into `migrations` under `batch = 2`.

#### Phase 6: Populate Departments & Doctors Without Creating Demo Staff Accounts
Run **specifically and exclusively** the `HospitalDataSeeder` class:
```bash
php artisan db:seed --class=HospitalDataSeeder --force
```
*Why this is safe:*
- `HospitalDataSeeder` interacts **only** with `Department` and `Doctor` models.
- It does **not** touch `users`, `articles`, `patients`, or `audit_logs`.
- It uses `updateOrCreate` keyed on unique `slug` fields, ensuring idempotent execution.
- It avoids running `StaffUserSeeder` (which is also protected by `if (app()->isProduction()) { return; }`).

#### Phase 7: Explicitly Enforce Doctor Visibility
Execute SQL verification to ensure all 6 seeded doctors satisfy `Doctor::scopePublished()`:
```sql
UPDATE doctors 
SET status = 'approved', 
    is_published = 1, 
    is_available = 1, 
    published_at = COALESCE(published_at, NOW()) 
WHERE status IS NULL OR status = 'approved';
```

#### Phase 8: Storage Symlink & Caching
Run via cPanel terminal:
```bash
# Verify or create the public storage symlink in the web document root
if [ ! -e /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage ]; then
    ln -s /home/xqtrqexj/medicare-hms-app/storage/app/public /home/xqtrqexj/medicare.kennedycheroben.co.ke/storage
fi

# Re-optimize application cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Automated Test Suite Alignment & Empirical Validation

The test suite contains **216 automated tests (100% passing)**, including a dedicated verification suite in `tests/Feature/ProductionReconciliationVerificationTest.php` (6 tests, 62 assertions, 0 failures):

1. **Production Reconciliation Verification (`tests/Feature/ProductionReconciliationVerificationTest.php`)**:
   - **Execution Command**: `/usr/bin/php vendor/phpunit/phpunit/phpunit --testdox tests/Feature/ProductionReconciliationVerificationTest.php`
   - **PHP Runtime**: **PHP 8.5.4 (CLI)** (Ubuntu NTS, Zend Engine v4.5.4 with OPcache) — meets and exceeds the `>= PHP 8.4.1` application requirement.
   - **PHPUnit Version**: PHPUnit 12.5.40.
   - **Exit Code**: **0** (Clean exit, 0 errors, 0 failures).
   - **Duration**: 40.861s (Memory: 56.50 MB).
   - **Test Results**:
     - `✔ Public departments and doctor counts strictly require approved status`
     - `✔ Appointment booking without department stores general intake and dispatches notification`
     - `✔ Seeding hospital data populates six departments and six published doctors`
     - `✔ Seeding hospital data preserves existing users and articles without mutation`
     - `✔ Medicine batches check constraint rejects negative stock quantity`
     - `✔ Article cover image url and fallback behavior`
2. **Investigation of Test Suite Execution Time & Test Isolation**:
   - During the initial audit, running tests against local MySQL appeared to delay output for ~40 seconds.
   - **Root Cause Confirmed**: Laravel's `RefreshDatabase` trait executes `migrate:fresh` on the first test, dropping and recreating 28 InnoDB tables with full foreign keys and check constraints over a local TCP connection to `medicare_hms_testing`.
   - Subsequent test transactions execute in milliseconds. The test database configuration in `phpunit.xml` is explicitly bound to `127.0.0.1:3306` database `medicare_hms_testing`. It is structurally isolated and cannot reach production (`xqtrqexj_medicare`).
3. **Database Engine Precision & Evidence**:
   - **Production Engine**: **MySQL 8.0.41** (Confirmed via mysqldump header in `/home/cheroben/Downloads/xqtrqexj_medicare.sql`: `Distrib 8.0.41, for Linux (x86_64)`).
   - **Local Test Engine**: **MariaDB 10.4.32** (via XAMPP).
   - **Engine Nuance**: MariaDB 10.4 does not support `utf8mb4_0900_ai_ci` collation (which required converting to `utf8mb4_unicode_ci` in a scratch SQL file for local import). In MySQL 8.0.41, `utf8mb4_0900_ai_ci` is the native default. Furthermore, MySQL 8.0.16+ is the first MySQL release that officially enforces `CHECK` constraints (earlier MySQL 5.7 silently ignored them).
4. **Why `INSERT IGNORE` is Safe ONLY After Schema Verification**:
   - `INSERT IGNORE` tells Laravel that Migrations 1–22 have already completed. If any table, column, index, or constraint from Migrations 1–22 were missing and marked as ran, Laravel would never run that migration, leaving the live database in a corrupt, unrecoverable state.
   - Because our exhaustive schema audit verified that all 26 tables, columns, indexes, and foreign keys from Migrations 1–22 already physically exist on production, marking them as ran via `INSERT IGNORE` is safe, idempotent, and interruption-proof.
5. **Companion Operational Runbook**:
   - A step-by-step operator guide has been authored in [`docs/production-repair-runbook.md`](file:///opt/lampp/htdocs/medicare-hms/docs/production-repair-runbook.md) containing copy-paste pre-flight checks, SQL statements, and verification procedures.

---

## 8. Post-Reconciliation Verification Checklist

Following execution of the safe reconciliation strategy, the operator must verify each item below:

### 8.1 Database Verification (SQL Queries)
```sql
USE xqtrqexj_medicare;

-- 1. Verify all 24 migrations are recorded
SELECT count(*) AS total_migrations FROM migrations;
-- Expected result: 24

-- 2. Verify check constraint exists and is enforced on medicine_batches
SELECT CONSTRAINT_NAME, ENFORCED 
FROM information_schema.CHECK_CONSTRAINTS
JOIN information_schema.TABLE_CONSTRAINTS USING (CONSTRAINT_SCHEMA, CONSTRAINT_NAME)
WHERE CONSTRAINT_SCHEMA = 'xqtrqexj_medicare' 
  AND TABLE_NAME = 'medicine_batches'
  AND CONSTRAINT_NAME = 'medicine_batches_qty_nonneg';
-- Expected result: medicine_batches_qty_nonneg | YES

-- 3. Verify notifications table exists
SHOW TABLES LIKE 'notifications';
-- Expected result: notifications

-- 4. Verify doctors.status and composite index exist
SHOW COLUMNS FROM doctors LIKE 'status';
SHOW INDEX FROM doctors WHERE Key_name = 'doctors_status_is_published_index';
-- Expected result: status column present, index present

-- 5. Verify preserved row counts
SELECT 
    (SELECT count(*) FROM users) AS users_count,
    (SELECT count(*) FROM articles) AS articles_count,
    (SELECT count(*) FROM audit_logs) AS audit_logs_count,
    (SELECT count(*) FROM contact_inquiries) AS inquiries_count,
    (SELECT count(*) FROM departments) AS departments_count,
    (SELECT count(*) FROM doctors) AS doctors_count;
-- Expected results:
-- users_count: 11
-- articles_count: 2
-- audit_logs_count: >= 36
-- inquiries_count: >= 1
-- departments_count: 6
-- doctors_count: 6

-- 6. Verify public doctor visibility
SELECT count(*) AS published_doctors_count 
FROM doctors 
WHERE is_published = 1 AND is_available = 1 AND status = 'approved';
-- Expected result: 6
```

### 8.2 Application Endpoint Verification (HTTP Status & Rendering)
1. **Homepage (`/`)**:
   - Loads with HTTP 200.
   - Displays all 6 departments with proper icons.
   - Displays the 6 published doctors with photos and specialties.
2. **Departments Overview (`/departments`)**:
   - Loads with HTTP 200.
   - Renders 6 active clinical department cards with doctor counts (`1 Specialist`).
3. **Department Detail (`/departments/cardiology`)**:
   - Loads with HTTP 200.
   - Displays Dr. Marcus Vance under clinical faculty.
4. **Doctors Directory (`/doctors`)**:
   - Loads with HTTP 200.
   - Displays all 6 doctors; department filter dropdown is populated with the 6 departments.
5. **Appointment Booking (`/book-appointment`)**:
   - Department and doctor dropdowns are populated.
   - Submit General Intake appointment (no department, no doctor selected):
     - Redirects to `/appointment-confirmation/{reference}` with HTTP 302/200.
     - Creates record in `appointments` with `department_id = NULL`.
     - Creates notification in `notifications` table for receptionist without 500 error.
   - Submit Specialty appointment (Cardiology + Dr. Marcus Vance):
     - Redirects to confirmation successfully.
6. **Hospital News (`/news`)**:
   - Displays the 2 existing published articles.
   - Cover images render from `/storage/news-covers/...` without 404 broken icons.

---

## 9. Rollback Precautions & Contingency Plan

1. **Pre-Reconciliation Database Backup**:
   - A fresh SQL dump exists at `/home/cheroben/Downloads/xqtrqexj_medicare.sql`. This file is preserved as an untouched external cold backup.
   - Before executing Phase 1 through Phase 7, take a secondary snapshot directly on cPanel with verification of exit code and completion footer:
     ```bash
     DB_USER=$(grep '^DB_USERNAME=' /home/xqtrqexj/medicare-hms-app/.env | cut -d '=' -f2 | tr -d ' "\r')
     BACKUP_FILE="/home/xqtrqexj/backup_before_repair_$(date +%Y%m%d_%H%M%S).sql"
     mysqldump -u "$DB_USER" -p xqtrqexj_medicare > "$BACKUP_FILE"
     if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ] && tail -n 10 "$BACKUP_FILE" | grep -q 'Dump completed'; then
         echo "Verified backup created at $BACKUP_FILE"
     else
         echo "FATAL: Backup failed or invalid. ABORT."
         exit 1
     fi
     ```
2. **Reversibility of Steps**:
   - Phase 1 (Check constraint): Dropped via `ALTER TABLE medicine_batches DROP CONSTRAINT medicine_batches_qty_nonneg;`.
   - Phase 2 (Baseline table rows): Removed via `DELETE FROM migrations WHERE batch = 1;`.
   - Phase 4 (Migrations 23 & 24): Rolled back cleanly via `php artisan migrate:rollback --step=2`.
   - Phase 5 (Seeded demo rows): If needed, departments and doctors can be cleared without affecting users:
     ```sql
     SET FOREIGN_KEY_CHECKS = 0;
     TRUNCATE TABLE doctor_reviews;
     TRUNCATE TABLE doctors;
     TRUNCATE TABLE departments;
     SET FOREIGN_KEY_CHECKS = 1;
     ```
   - User accounts (`users`) and news articles (`articles`) are never modified by these rollback procedures.

---
**Audit Completed & Document Sealed:** October 28, 2026  
**Status:** Ready for Owner Review & Scheduled Maintenance Execution. Zero production changes made.

