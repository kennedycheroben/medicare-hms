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

### Phase 6: Core Clinical Documentation, Electronic Health Records (EHR), Prescriptions & Pharmacy Dispensary
* **Scope**: Core clinical documentation, Electronic Health Records (EHR), doctor consultation workflows, electronic prescriptions, pharmacy dispensary, and medicine inventory management.
* **Status**: **Completed**.
* **Implemented Capabilities**:
  1. **Doctor Consultation Queue & Clinical Workspace**: Outpatient consultation queue prioritized by triage acuity, active consultation workspace with real-time triage vitals display, SOAP clinical notes (Subjective, Objective, Assessment, Plan), provisional diagnosis, and doctor sign-off.
  2. **Immutable Record Locking & Addenda Audit Trail**: Draft consultations can be updated by the attending doctor; once signed/finalized, core clinical records become strictly immutable (`is_finalized`), and subsequent clinical corrections must be made via signed, timestamped append-only addenda (`consultation_addenda`).
  3. **Doctor-Only Per-Record Authorization**: Enforced on the server; only doctors can initiate, draft, or finalize consultations. SuperAdmin and HospitalDirector have read-only clinical oversight without the ability to forge or tamper with medical notes.
  4. **Electronic Prescriptions (e-Rx)**: Structured multi-item prescribing linked to patient visit and consultation. Captures medicine name, dosage, frequency, duration, route, and clinical instructions. Supports draft creation, finalization, cancellation with mandatory auditable reason, and duplicate submission protection via unique submission tokens.
  5. **Pharmacy Dispensary & FEFO Batch Allocation**: Dispensary queue displaying finalized prescriptions awaiting dispensing. Concurrency-safe First-Expiry-First-Out (FEFO) batch allocation using database-level `lockForUpdate()` transactions, ensuring earliest expiring batches are dispensed first.
  6. **Negative Inventory Prevention & Idempotency Safeguards**: Database CHECK constraints (`quantity_current >= 0`) and transactional checks prevent stock from going below zero under race conditions. Unique idempotency keys prevent duplicate dispensing on network retries or double clicks. Unfulfilled or declined prescriptions require mandatory clinical reasons.
  7. **Medicine Formulary & Batch Inventory Management**: Complete formulary catalog (brand/generic names, categories, strengths, units, reorder thresholds) and batch management (batch numbers, expiry dates, purchase/selling costs, supplier details). Tracks near-expiry alerts and stock movements with an immutable audit ledger.
  8. **Patient Profile EHR Integration**: Patient record detail view consolidated with complete chronological electronic health record timeline, including visit history, nursing triage vitals, consultation notes, signed addenda, and prescribed medications.
* **Clinical Governance & Regulatory Disclaimer**:
  - Clinical workflows, triage prioritization, and medication management are engineered to assist qualified healthcare professionals and adhere to institutional policies. All medical decisions, diagnoses, dosages, and dispensings remain the professional responsibility of licensed clinical practitioners. This release does not claim automated drug-drug interaction contraindication screening or official ICD-11 coding certification.
* **Acceptance Criteria**:
  - [x] Strict server-side RBAC enforced (`role:doctor`, `role:pharmacist`, `role:super_admin,hospital_director,pharmacist`).
  - [x] Immutable finalized consultation records with append-only signed addenda.
  - [x] Concurrency-safe FEFO medicine batch allocation with row-level locks and negative stock prevention.
  - [x] Idempotency key protection preventing double-dispensing.
  - [x] 73 automated feature tests passing with 428 assertions and 0 failures.
  - [x] Verified via real browser preview across public site, doctor consultation queue/workspace, pharmacy dispensary queue, and medicine inventory.

---

### Phase 7: Comprehensive Security Auditing, Performance & Quality Assurance
* **Scope**: Penetration testing, OWASP Top 10 mitigation, SQL injection testing, XSS testing, broken object level authorization (BOLA) tests, load testing, and accessibility audit.
* **Acceptance Criteria**:
  - [ ] Strict compliance with Kenya Data Protection Act 2019 and HIPAA principles.
  - [ ] 0 high or critical vulnerabilities identified in automated security audits.
  - [ ] WCAG 2.2 AA accessibility verified across all public and staff screens.

---

### Phase 8: Deployment Preparation & Release Documentation
* **Scope**: cPanel shared hosting compatibility, MySQL export/import pipelines, environment variable setup guide, cron job scheduling, and comprehensive administrator user manual.
* **Acceptance Criteria**:
  - [ ] Clean deployment checklist for cPanel production hosting.
  - [ ] Automated database backup routines documented.
  - [ ] User workbook and operations guide provided for hospital staff.
