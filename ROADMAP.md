# MediCare Hospital Management System (HMS) — Comprehensive Project Roadmap & Architecture Specification

## 1. System Vision & Architecture Strategy

MediCare HMS is designed as an integrated, multi-tier healthcare operating system built with **Laravel, Blade, Bootstrap, and MySQL/MariaDB**. It combines:
1. **Public Hospital Website (`/`)**: Patient-facing clinical information, service directory, specialist profiles, contact triage, and online appointment booking.
2. **Administrative & Clinical Dashboard (`/admin`)**: Operations management for hospital leadership, medical officers, nursing staff, pharmacy, laboratory, cashiering, and records personnel (utilizing Modern Admin design assets).
3. **Patient Portal Gateway (`/portal`)**: Authenticated self-service portal for patients to view appointment statuses, verify lab results, review discharge summaries, and handle billing/receipts securely.

```
+-----------------------------------------------------------------------------------+
|                                 CLIENT ACCESS TIER                                |
+-----------------------+----------------------------------+------------------------+
|    Public Website     |        Patient Portal            |    Staff/Admin Portal  |
|  (Anonymous Visitors) |     (Authenticated Patients)     |  (Role-Based Employees)|
|    Layout: public     |         Layout: portal           |      Layout: admin     |
+-----------+-----------+-----------------+----------------+------------+-----------+
            |                             |                             |
            |                             |                             |
+-----------v-----------------------------v-----------------------------v-----------+
|                              LARAVEL ROUTING & MIDDLEWARE                         |
|  - Throttle (Rate Limiting)   - Sanctum / Session Auth   - Role & Permission RBAC |
|  - CSRF Protection            - Patient Boundary Guard   - Audit Logging Engine   |
+-----------------------------------------------------------------------------------+
|                                BUSINESS DOMAIN TIER                               |
|  - Scheduling & Triage        - Clinical Records (SOAP)  - Pharmacy & Dispensary  |
|  - Inpatient Ward/Bed Engine  - Laboratory Information   - Billing, M-Pesa & Cash |
+-----------------------------------------------------------------------------------+
|                           SINGLE SOURCE OF TRUTH (PERSISTENCE)                    |
|  - MySQL Database: Shared Normalized Relational Schema                            |
|  - Department, Doctor, Appointment, Inquiry, User, Patient, Bill, Prescription    |
+-----------------------------------------------------------------------------------+
```

---

## 2. Integrated Application Architecture

### 2.1 Route Hierarchy & Layout Structure

The application separates concerns across three clear route namespaces, layouts, and access gates:

| Section | Route Prefix | Primary Layout | Authentication Gate | Description |
| :--- | :--- | :--- | :--- | :--- |
| **Public Site** | `/` | `layouts/public.blade.php` | None (Public) | Informational pages, doctor directory, department details, appointment booking, contact. |
| **Admin Panel** | `/admin` | `layouts/admin.blade.php` | `auth:web` + `role:admin\|doctor\|nurse\|cashier...` | Clinical operations, ward management, lab, pharmacy, cashiering, settings. |
| **Admin Auth** | `/admin/login` | `layouts/auth.blade.php` | `guest:web` | Secure staff authentication, password reset, and session initialization. |
| **Patient Portal**| `/portal` | `layouts/portal.blade.php` | `auth:patient` | Self-service appointment verification, lab result access, telehealth notes. |
| **System Status** | `/health`, `/up`| JSON Response | None / IP-restricted | Application runtime health, environment status, and diagnostic checks. |

### 2.2 Single Source of Truth for Hospital Data

The public website and back-office administration never duplicate data entities. Both consume the identical Eloquent models:

1. **Hospital Identity & Configuration**:
   - `config/hospital.php` provides foundational system defaults.
   - Database table `hospital_settings` will allow administrators to update address, hotlines, visiting policies, and emergency instructions live without code modifications.
2. **Departments & Specialties**:
   - `Department` model (`departments` table): managed by admin; published directly to public `/departments` and `/departments/{slug}`.
3. **Doctors & Clinicians**:
   - `Doctor` model (`doctors` table): managed by admin; controls specialty, schedule, room number, bio, consultation hours, and active status (`is_available`).
4. **Appointment Requests**:
   - Submitted via public `/appointments/book`.
   - Stored in `appointments` table with `status = 'pending'`.
   - Triage staff in `/admin/appointments` review clinical department availability, assign a slot, update status (`confirmed`, `rescheduled`, `cancelled`, `completed`), and record internal notes.
   - The public visitor can check only the generic status via their secure reference code at `/appointments/confirmation/{reference}` without any personal medical data or PII being reflected.
5. **Contact Enquiries**:
   - Submitted via public `/contact`.
   - Stored in `contact_inquiries` table with unique ticket code (`INQ-YYYYMMDD-XXXXX`).
   - Admin coordination desk processes tickets at `/admin/inquiries`, updates status (`new`, `in_progress`, `resolved`), and replies via hospital SMTP.

---

## 3. Comprehensive Phased Roadmap & Acceptance Criteria

### Phase 0: Discovery, Foundation & Baseline Verification
* **Scope**: Business problem analysis, reference-template asset audit (`medicare` & `modern_admin`), Git repository establishment, and architectural constitution enforcement.
* **Status**: **Completed**.
* **Acceptance Criteria**:
  - [x] Strict project boundaries enforced (`/opt/lampp/htdocs/medicare-hms` isolated).
  - [x] Reference templates inspected without runtime coupling.
  - [x] Git baseline repository committed and published to GitHub.

---

### Phase 1: Requirements, Architecture & Data Model Design
* **Scope**: System domain modeling, relational database schema specification, role permissions definition, and public-admin workflow architecture.
* **Status**: **Completed** (Documented in ROADMAP.md and database migrations).
* **Acceptance Criteria**:
  - [x] Normalized relational schema designed for departments, doctors, appointments, inquiries, users, and audit logs.
  - [x] Role-Based Access Control (RBAC) boundaries established.
  - [x] Complete public-to-admin integration flow mapped with privacy safeguards.

---

### Phase 2: Laravel Project & Local Development Environment
* **Scope**: Laravel Framework 13 setup, Composer dependency locking, MySQL/MariaDB schema migrations, PHP 8.4 runtime verification, and test database configuration.
* **Status**: **Completed**.
* **Acceptance Criteria**:
  - [x] Clean Laravel foundation running on PHP 8.4+ CLI and web server.
  - [x] Automated test suite configured with isolated testing database in `phpunit.xml`.
  - [x] Zero deprecated dependencies; reproducibility verified.

---

### Phase 3: Public Hospital Website & Preview Stabilization
* **Scope**: 12 complete responsive Blade templates, fail-safe CSS visibility, robust progressive enhancement, rate limiting, and zero-PII appointment confirmation.
* **Status**: **Completed & Stabilized**.
* **Acceptance Criteria**:
  - [x] Local development preview running reliably on `http://127.0.0.1:8000`.
  - [x] Permanent resolution of blank-page defects: content remains 100% visible even if JavaScript or AOS animations fail to load.
  - [x] Fully responsive layout on mobile (375px) with zero horizontal overflow and functional navigation drawer.
  - [x] Client and server-side validation on appointment booking and contact enquiry forms.
  - [x] Zero patient PII exposed on public appointment confirmation screen.
  - [x] Rate limiting active on submission endpoints (`throttle:10,1`).
  - [x] 100% automated test pass rate (20 feature tests, 95 assertions).

---

### Phase 4: Authentication, Access Control & Staff Administration
* **Scope**: Multi-guard authentication for staff and patients, secure session lifecycle, password recovery, role-based authorization, and user administration.
* **Status**: **Completed**.
* **Implemented Modules**:
  1. **Staff Authentication**: `/admin/login`, `/admin/logout`, session management, brute-force throttling (`throttle:5,1`).
  2. **Role & Permission Management**: 8 standard roles (`SuperAdmin`, `HospitalDirector`, `Doctor`, `Nurse`, `Pharmacist`, `LabTechnician`, `Cashier`, `Receptionist`) with granular capabilities.
  3. **Patient Portal Authentication**: `/portal/login` entry shell.
  4. **Audit Logging**: Structured audit trail recording staff actions, logins, status changes, and record modifications with credential redaction.
* **Acceptance Criteria**:
  - [x] Unauthenticated requests to `/admin/*` securely redirect to `/admin/login`.
  - [x] Role middleware strictly prevents unauthorized role escalation.
  - [x] Passwords hashed with Bcrypt (cost factor 12 in production, cost factor 4 in testing).
  - [x] CSRF protection and secure session cookies enforced across all back-office routes.
  - [x] 100% automated test pass rate (34 feature tests, 184 assertions).

---

### Phase 5: Staff Management, Patient Registration & Front-Desk Triage
* **Scope**: Complete administrative staff management, Patient Master Index (PMI) with collision-resistant MRN generation, duplicate patient detection with explicit override, front-desk intake & appointment reconciliation, duplicate check-in prevention, and nursing triage station with vital signs & 5-tier clinical prioritization.
* **Status**: **Completed**.
* **Implemented Capabilities**:
  1. **SuperAdmin Staff Management**: Full CRUD, search/filtering, role reassignment, account activation/deactivation, safeguards against self-demotion, self-deactivation, and last active SuperAdmin lockout.
  2. **Patient Master Index (PMI)**: Server-generated unique Medical Record Numbers (`MRN-YYYY-XXXXXX`), legal demographics, emergency contacts, allergies, blood groups, and transactional creation.
  3. **Duplicate Detection & Auditable Override**: Real-time detection of matching contact/demographic records with staff override confirmation audit logging (`patient.duplicate_overridden`).
  4. **Front-Desk Patient Intake**: Walk-in visit check-in, scheduled appointment reconciliation with patient records, duplicate check-in prevention for active same-day encounters.
  5. **Nursing Triage Station**: Triage queue with prominent emergency escalation, comprehensive vital signs recording (BP, pulse, RR, temperature, SpO2, pain score, weight, height, blood glucose, automatic BMI calculation), 5-tier clinical triage categorization, and doctor consultation handoff.
* **Acceptance Criteria**:
  - [x] Server-side authorization enforced on every action; no client-only guards.
  - [x] Safe non-sequential unique MRN generation with database-level uniqueness.
  - [x] Full audit logging for staff, patient, visit, and triage actions with sensitive data redacted.
  - [x] 53 automated feature tests passing with 305 assertions and 0 failures.
  - [x] Verified in real browser preview across public, admin, intake, and triage workflows.

---

### Phase 6: Core Clinical Documentation, EHR, Prescriptions, Dispensary & Patient Portal Ecosystem
* **Scope**: Core clinical documentation, Electronic Health Records (EHR), doctor consultation workflows, electronic prescriptions, pharmacy dispensary, inventory integrity, verified doctor reviews, news CMS, and authenticated patient portal.
* **Status**: **Completed**.
* **Implemented Capabilities**:
  1. **Doctor Consultation Queue & Clinical Workspace**: Outpatient consultation queue prioritized by triage acuity, active consultation workspace with real-time triage vitals display, SOAP clinical notes (Subjective, Objective, Assessment, Plan), provisional diagnosis, and doctor sign-off.
  2. **Immutable Record Locking & Addenda Audit Trail**: Draft consultations can be updated by the attending doctor; once signed/finalized, core clinical records become strictly immutable (`is_finalized`), and subsequent clinical corrections must be made via signed, timestamped append-only addenda (`consultation_addenda`).
  3. **Doctor-Only Per-Record Authorization**: Enforced on the server; only doctors can initiate, draft, or finalize consultations. SuperAdmin and HospitalDirector have read-only clinical oversight without the ability to forge or tamper with medical notes.
  4. **Electronic Prescriptions (e-Rx)**: Structured multi-item prescribing linked to patient visit and consultation. Captures medicine name, dosage, frequency, duration, route, and clinical instructions. Supports draft creation, finalization, cancellation with mandatory auditable reason, and duplicate submission protection via unique submission tokens.
  5. **Pharmacy Dispensary & FEFO Batch Allocation**: Dispensary queue displaying finalized prescriptions awaiting dispensing. Concurrency-safe First-Expiry-First-Out (FEFO) batch allocation using database-level `lockForUpdate()` transactions, ensuring earliest expiring batches are dispensed first.
  6. **Inventory Integrity & Transactional Service (`App\Services\InventoryService`)**: Transactional execution of all inventory movements (`dispensePrescriptionItem`, `otcSale`, `internalUse`, `recordWastage`, `recordReturn`, `adjustBatch`), database-level negative stock prevention, and immutable movement logging. Low-stock database-level scope (`Medicine::scopeLowStock`) filtering against `reorder_threshold`.
  7. **Staff Appointment Operations & Kenyan Localization**: `/admin/appointments` board with status filtering, confirmation with attending clinician, rescheduling with documented reason, cancellation with reason, and one-click intake check-in. E.164 phone normalization and Kenyan mobile validation (`07xx`, `01xx`, `+254`), localized hospital configuration (`config/hospital.php`), and national health scheme recognition (SHA/NHIF).
  8. **Physician Directory Publication & Verified Patient Reviews**: Super Administrator and Hospital Director publication oversight (`is_published`). Verified in-person patient reviews authenticated by patient MRN + contact phone + completed clinical visit with the specific doctor, preserving patient privacy with consent-based public display names.
  9. **Hospital News & Health Advisories CMS**: Complete database-backed news engine (`/news`, `/news/{slug}`). Draft preview mode with staff banner, publisher-only publication permissions (`role:super_admin,hospital_director`), server-side slug uniqueness, cover image uploads, and XSS sanitization.
  10. **Authenticated Patient Portal**: Isolated `patient` session guard (`config/auth.php`) completely segregated from clinical staff administrative sessions. Cryptographic one-time activation tokens stored as SHA-256 hashes (`token_hash`) with automatic expiration, mandatory first-time password rotation, and self-service outpatient health dashboard.
* **Acceptance Criteria**:
  - [x] Strict server-side RBAC enforced across clinical, administrative, pharmacy, and patient boundaries.
  - [x] Immutable finalized consultation records with append-only signed addenda.
  - [x] Concurrency-safe FEFO medicine batch allocation with row-level locks and negative stock prevention.
  - [x] Idempotency key protection preventing double-dispensing.
  - [x] Verified visit validation for doctor review submissions.
  - [x] Dedicated multi-guard patient authentication isolated from staff session state.
  - [x] 127 automated feature tests passing with 910 assertions and 0 failures.

---

### Phase 7: Comprehensive Security Auditing, Performance & Quality Assurance
* **Scope**: Penetration testing, OWASP Top 10 mitigation, SQL injection testing, XSS testing, broken object level authorization (BOLA/IDOR) verification, session boundary security, rate limiting, and accessibility audit.
* **Status**: **Hardened & Verified (Automated Suite Passing / External Penetration Gates Retained)**.
* **Implemented & Verified Controls**:
  - [x] 0 known security vulnerabilities reported by Composer security audit (`composer audit`).
  - [x] Defensive HTTP security headers middleware (`App\Http\Middleware\SecurityHeaders`) active across all web responses:
    - `X-Content-Type-Options: nosniff`
    - `X-Frame-Options: SAMEORIGIN`
    - `Referrer-Policy: strict-origin-when-cross-origin`
    - `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`
    - `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https: blob:; frame-ancestors 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'` (tightened: removed unnecessary `'unsafe-eval'` and unused external CDN domain).
    - Automatic removal of `X-Powered-By` header in Laravel response bag to mitigate technology fingerprinting (supplemented by Apache/PHP-FPM web-server runbook controls).
    - Conditional `Strict-Transport-Security: max-age=31536000; includeSubDomains` on HTTPS connections.
  - [x] Signed consultation note immutability:
    - Application-level immutability enforced through model and controller authorization boundaries: once signed (`status === 'signed'`, `signed_at !== null`), original chief complaints, diagnoses, and assessments are immutable against direct update attempts (`403 Forbidden`). (Note: ordinary database application-level immutability; not cryptographic PKI signing).
    - Amendments can only be made via dedicated append-only addenda (`admin.consultations.addenda.store`).
    - Addenda creation is strictly restricted to attending clinicians (`role:doctor`).
    - Attempting to append addenda to unsigned draft consultations is rejected.
    - Draft consultation notes are strictly confidential and inaccessible to other doctors (`403 Forbidden`).
    - Validation failures inside DB transaction roll back cleanly with zero partial persistence.
    - All addenda creation events are logged to the audit trail with author attribution and amendment rationale.
  - [x] Multi-guard session separation & IDOR defense:
    - Separate `patient` and `web` guards strictly segregated with independent session drivers and explicit `auth:web` route protection.
    - Authenticated patient accounts attempting to access `/admin/*` are rejected and redirected to `/admin/login`.
    - Authenticated staff accounts without an active patient session attempting to access `/portal/*` are redirected to `/portal/login`.
    - Patient portal endpoints strictly restrict data retrieval to `Auth::guard('patient')->user()->patient` to eliminate Insecure Direct Object References (IDOR/BOLA).
  - [x] Brute-force rate limiting & anti-enumeration:
    - Patient portal sign-in is rate-limited to 5 attempts per identifier/IP window with generic error messages (`"We could not sign you in with those details..."`) to prevent patient account enumeration.
    - Password recovery is rate-limited to 3 attempts with uniform generic success messaging regardless of whether the email exists.
  - [x] Single-use activation/reset token hashing:
    - SHA-256 hashed tokens stored in database with automatic expiry timestamps; plain tokens are never stored and cannot be replayed after activation.
  - [x] Inactive staff access denial:
    - `EnsureUserIsActive` middleware strictly terminates sessions for deactivated staff accounts across administrative endpoints.
  - [x] Audit log secret and credential redaction:
    - `AuditService` filters passwords, tokens, API keys, and sensitive financial credentials before logging.
  - [x] Production demo-seeder safeguards:
    - Demo seeders abort if executed against an environment with `APP_ENV=production`.
  - [x] Clean browser preview across public, staff, and patient views with 0 JavaScript console errors.
* **Remaining Acceptance Criteria (External Production Release Gates)**:
  - [ ] Formal independent penetration testing and black-box vulnerability scan on staging infrastructure.
  - [ ] Comprehensive automated WCAG 2.2 Level AA accessibility scan with screen reader verification.
  - [ ] Application load and stress testing under simulated concurrent dispensary and consultation load.
  - [ ] Web-server level TLS/HSTS enforcement in Apache/cPanel `.htaccess` or Nginx reverse proxy.

---

### Phase 8: Deployment Preparation, Backup Verification & Operational Readiness
* **Scope**: Production environment configuration, MySQL automated backup and point-in-time recovery verification, cPanel/Truehost deployment automation, release packaging verification, and hospital staff user workbook.
* **Status**: **Hardened & Verified Locally (Pre-Deployment Operational Readiness Established)**.
* **Implemented & Verified Deliverables**:
  - [x] Integrated User Workbook & Operational Guide (`/guide`) providing plain-language instructions for patients, receptionists, nurses, doctors, pharmacists, and administrators.
  - [x] Published Terms of Service (`/terms`) with explicit clinical governance and emergency disclaimers.
  - [x] Production asset compilation (`npm run build`) verified clean with zero build warnings.
  - [x] Production environment template (`.env.production.example`) created with hardened session, cookie, and localization settings.
  - [x] Truehost and cPanel production deployment runbook (`DEPLOYMENT_RUNBOOK.md`) established with ordered phase boundaries.
  - [x] Database backup (`scripts/backup_database.sh`) and restore (`scripts/restore_database.sh`) scripts engineered with `chmod 600` permissions, retention pruning, safe database identifier validation (`^[a-zA-Z0-9_]+$`), and mandatory `--confirm` flags.
  - [x] Automated release packaging script (`scripts/package_release.sh`) with strict security exclusions (excluding `.git`, `.env*`, `node_modules`, `tests`, `phpunit*.xml`) and archive self-verification.
  - [x] Automated deployment readiness test suite (`tests/Feature/DeploymentReadinessTest.php`) verifying production configuration parity, security hardening flags, route health, and script error safeguards.
  - [x] Backup and restore disaster recovery drill verified in an isolated disposable database environment with 100% schema and record integrity (28 tables verified).
* **Remaining Acceptance Criteria (Pre-Deployment Checklist & External Release Gates)**:
  - [ ] Production SMTP gateway configuration (e.g., Amazon SES, Postmark, Mailgun) for live patient email activation delivery.
  - [ ] Production symlink verification (`php artisan storage:link`) for uploaded media.
  - [ ] Formal legal sign-off from hospital legal counsel on Privacy Policy and Terms of Service.
  - [ ] Execution of deployment runbook against Truehost cPanel environment during an authorized maintenance window.

---

## 4. Current Test Suite Status & Quality Verification

| Test Suite | Tests | Assertions | Status |
| :--- | :---: | :---: | :--- |
| `tests/Feature/HealthCheckTest.php` | 2 | 10 | Passed |
| `tests/Feature/PublicWebsiteTest.php` | 16 | 86 | Passed |
| `tests/Feature/StaffAuthenticationTest.php` | 20 | 170 | Passed |
| `tests/Feature/StaffAppointmentManagementAndLocalizationTest.php` | 4 | 29 | Passed |
| `tests/Feature/StaffManagementTest.php` | 13 | 91 | Passed |
| `tests/Feature/PatientRegistrationAndTriageTest.php` | 7 | 56 | Passed |
| `tests/Feature/ConsultationWorkflowTest.php` | 21 | 158 | Passed |
| `tests/Feature/PrescriptionWorkflowTest.php` | 5 | 39 | Passed |
| `tests/Feature/PharmacyDispensingAndInventoryTest.php` | 9 | 63 | Passed |
| `tests/Feature/InventoryIntegrityAndTransactionsTest.php` | 6 | 45 | Passed |
| `tests/Feature/DoctorPublicationAndReviewsTest.php` | 7 | 47 | Passed |
| `tests/Feature/NewsCmsTest.php` | 5 | 37 | Passed |
| `tests/Feature/PatientPortalAuthTest.php` | 10 | 114 | Passed |
| `tests/Feature/SecurityHardeningAndPenetrationTest.php` | 16 | 203 | Passed |
| `tests/Feature/DeploymentReadinessTest.php` | 9 | 129 | Passed |
| `tests/Unit/ExampleTest.php` | 1 | 1 | Passed |
| `tests/Feature/ExampleTest.php` | 1 | 1 | Passed |
| **Total Test Suite** | **152** | **1,242** | **100% Pass (0 Failures)** |

---

## 5. Scope Boundaries: Completed System vs. Future Hospital Modules

### 5.1 Completed Outpatient Clinical & Operational Scope (Release Candidate 1.0)
The following core hospital operations are fully engineered, secured, and locally verified:
1. Public clinical portal, doctor directory, departmental routing, online appointment scheduling, and contact inquiries.
2. Patient Master Index (PMI) with collision-resistant MRN generation and audited duplicate detection.
3. Front-desk patient intake, walk-in registration, and appointment reconciliation.
4. Nursing triage station with 5-tier clinical prioritization and comprehensive vital signs recording.
5. Attending physician consultation workspace with SOAP notes, diagnosis coding, and immutable signing with append-only addenda.
6. Multi-item electronic prescriptions (e-Rx) and pharmacy dispensary queue with concurrency-safe FEFO batch allocation.
7. Centralized transactional inventory management (`InventoryService`) with negative stock prevention and movement logging.
8. Physician directory publication oversight and verified moderated patient reviews.
9. Database-backed hospital news CMS with draft privacy and publisher authorization.
10. Multi-guard authenticated patient self-service portal with SHA-256 single-use tokens and isolated sessions.
11. Defensive security headers middleware with tightened CSP (no `unsafe-eval`, no CDN domains), session revocation, and audit secret redaction.
12. Truehost cPanel deployment automation, configuration templates, database backup/restore scripts, and release packaging tooling.

### 5.2 Separate Future Hospital Development Scope
The following modules currently exist as architectural view shells in `/admin/*` and represent distinct development phases for subsequent project milestones:
1. **Diagnostic Laboratory & Specimen Accessioning** (`/admin/laboratory`): Laboratory order entry from consultation, specimen accessioning barcodes, equipment calibration logging, and pathologist result verification.
2. **Billing, Invoicing & M-Pesa Integration** (`/admin/billing`): Itemized consultation/pharmacy billing, Daraja API / C2B M-Pesa callback reconciliation, insurance pre-authorization claims, and cashier register closing.
3. **Executive Reporting & Business Intelligence** (`/admin/reports`): Aggregate departmental clinical census, pharmacy inventory velocity, revenue reconciliation, and physician workload analytics.
4. **Inpatient Ward & Bed Management**: Ward layout, bed occupancy tracking, admission/discharge/transfer (ADT) workflows, and inpatient medication administration records (eMAR).
