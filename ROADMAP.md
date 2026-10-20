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

### Phase 6: Core Clinical Documentation, Electronic Health Records (EHR) & Consultation Workflows (Next Phase)

1. **Dashboard & Executive Analytics**: Bed occupancy rates, daily outpatient visits, pending triage, revenue summaries, emergency department status.
2. **Patient Registration & Master Patient Index (MPI)**: Unique Medical Record Numbers (MRN), demographics, emergency contacts, national ID/passport records, biometric photo capture.
3. **Appointments & Clinical Scheduling**: Centralized clinic calendar, doctor shift management, room allocation, public booking triage conversion.
4. **Outpatient Workflows & Nurse Triage**: Vital signs recording (BP, pulse, temp, SpO2, BMI), triage acuity categorization (Emergency Severity Index), outpatient queues.
5. **Inpatient Admissions, Wards & Beds**: Ward categorization (General, Semi-Private, Private, ICU, NICU, HDU), bed allocation, admission/transfer/discharge (ADT) tracking.
6. **Doctors & Staff Roster**: Duty rosters, departmental assignments, specialty accreditation tracking.
7. **Clinical Records & SOAP Documentation**: Subjective, Objective, Assessment, Plan (SOAP) clinical notes, ICD-11 coding integration, allergy tracking, chronic condition tracking.
8. **Pharmacy & Dispensary**: Drug formulary, inventory management, e-prescriptions dispensing, batch tracking, expiry date alerts, stock reconciliation.
9. **Laboratory Information Management (LIMS)**: Test requests, specimen accessioning, barcoding, test parameter entry, reference ranges, critical value alerts, report sign-off.
10. **Inventory & Medical Supplies**: Central store requisitions, departmental stock transfers, minimum stock level reorder alerts.
11. **Billing, Invoicing & Cashiering**: Charge master pricing, automated fee calculation (consultation, lab, pharmacy, procedure, bed charges), itemized invoicing, receipts.
12. **M-Pesa Payment Integration**: C2B, STK Push (Daraja API), automated payment callback reconciliation, split transaction settlement.
13. **Insurance Claims Management**: Provider pre-authorizations, policy cover limits, co-pay handling, claim batch generation and status tracking.
14. **Radiology & Diagnostic Imaging**: Imaging orders (X-ray, Ultrasound, CT, MRI), technician scheduling, radiologist reporting.
15. **Nursing Care & Medication Administration Records (eMAR)**: Medication administration schedules, nursing notes, fluid balance charts.
16. **Operating Theatre & Surgery Management**: Theatre booking, pre-op checklist, surgical team assignment, post-anesthesia care unit (PACU) recovery tracking.
17. **Institutional Reporting & Health Analytics**: Ministry of Health compliant statistical reports, mortality/morbidity summaries, financial audits.
18. **User & System Administration**: Role management, database backup schedules, hospital department configuration.
19. **Comprehensive Audit Logs**: Immutable log recording every data read/write on sensitive clinical entities.
20. **Hospital Settings & CMS**: Live management of public site banners, consultation hours, emergency hotlines, and service descriptions.

---

### Phase 6: System-Wide Integration & Workflow Unification
* **Scope**: End-to-end integration connecting the public booking funnel, front-desk triage, clinical consultation, diagnostic testing, pharmacy fulfillment, cashier settlement, and patient portal review.
* **Acceptance Criteria**:
  - [ ] A patient appointment request from the public website is triaged by reception, converted to an outpatient encounter, attended by a doctor, billed by cashiering, and visible on the patient portal without data duplication.
  - [ ] Unified database foreign keys and transaction integrity enforced with zero orphan records.

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
