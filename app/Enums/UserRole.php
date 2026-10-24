<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case HOSPITAL_DIRECTOR = 'hospital_director';
    case DOCTOR = 'doctor';
    case NURSE = 'nurse';
    case PHARMACIST = 'pharmacist';
    case LAB_TECHNICIAN = 'lab_technician';
    case CASHIER = 'cashier';
    case RECEPTIONIST = 'receptionist';

    /**
     * Get human-readable role name.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::HOSPITAL_DIRECTOR => 'Hospital Director',
            self::DOCTOR => 'Medical Doctor',
            self::NURSE => 'Nursing Officer',
            self::PHARMACIST => 'Pharmacist',
            self::LAB_TECHNICIAN => 'Laboratory Technician',
            self::CASHIER => 'Cashier / Billing Officer',
            self::RECEPTIONIST => 'Receptionist / Triage Desk',
        };
    }

    /**
     * Get authorized capabilities for this role according to least-privilege matrix.
     *
     * @return array<string>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'system.manage_settings',
                'staff.manage_accounts',
                'staff.assign_roles',
                'audit.view_logs',
                'hospital.manage_departments',
                'hospital.manage_doctors',
                'doctors.publish',
                'reviews.moderate',
                'news.manage',
                'patient_portal.issue_link',
                'appointments.view_all',
                'appointments.triage',
                'reports.view_all',
            ],
            self::HOSPITAL_DIRECTOR => [
                'reports.view_all',
                'hospital.view_departments',
                'hospital.view_doctors',
                'doctors.publish',
                'reviews.moderate',
                'news.manage',
                'appointments.view_all',
                'census.view_overview',
                'patients.view_summary',
            ],
            self::DOCTOR => [
                'clinical.consultations',
                'clinical.soap_notes',
                'clinical.prescriptions',
                'clinical.lab_requests',
                'appointments.view_assigned',
                'patients.view',
                'triage.view',
            ],
            self::NURSE => [
                'triage.vital_signs',
                'triage.assess',
                'triage.queue',
                'patients.view',
                'inpatient.observations',
                'inpatient.bed_allocation',
                'inpatient.emar',
            ],
            self::PHARMACIST => [
                'pharmacy.dispense',
                'pharmacy.formulary',
                'pharmacy.inventory',
                'inventory.otc_sale',
                'inventory.adjust',
                'inventory.wastage',
            ],
            self::LAB_TECHNICIAN => [
                'lab.accession_specimens',
                'lab.enter_results',
                'lab.inventory',
            ],
            self::CASHIER => [
                'billing.invoicing',
                'billing.collect_payments',
                'billing.mpesa_reconcile',
                'billing.receipts',
            ],
            self::RECEPTIONIST => [
                'patients.register',
                'patients.view',
                'patient_portal.issue_link',
                'visits.intake',
                'appointments.create',
                'appointments.triage',
                'inquiries.process',
            ],
        };
    }

    /**
     * Check if this role has a specific capability.
     */
    public function can(string $capability): bool
    {
        // SuperAdmin possesses all capabilities
        if ($this === self::SUPER_ADMIN) {
            return true;
        }

        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * Plain-language summary of the role's purpose within the hospital.
     */
    public function purpose(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'System administrator with comprehensive configuration, staff management, audit trail inspection, and governance capabilities.',
            self::HOSPITAL_DIRECTOR => 'Executive clinical and operational oversight across hospital census, service departments, and high-level reporting.',
            self::DOCTOR => 'Licensed medical practitioner authorized to evaluate patients, record SOAP clinical notes, finalize consultations, and author electronic prescriptions.',
            self::NURSE => 'Nursing officer responsible for initial assessment, vital signs recording, 5-tier triage prioritization, and bedside inpatient care.',
            self::PHARMACIST => 'Licensed pharmaceutical officer managing medication dispensing, FEFO batch allocation, warehouse balances, and drug integrity.',
            self::LAB_TECHNICIAN => 'Laboratory professional conducting specimen accessioning, diagnostic investigations, and results verification.',
            self::CASHIER => 'Financial officer managing patient billing, payments collection, M-Pesa reconciliation, and official receipt issuance.',
            self::RECEPTIONIST => 'Front-desk administrative officer managing patient registration (PMI), appointment review, and intake check-in.',
        };
    }

    /**
     * Plain-language descriptions of what staff in this role can view.
     *
     * @return array<string>
     */
    public function canViewDescriptions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'All hospital departments, clinics, and staff accounts',
                'Patient Master Index (PMI) and visit histories',
                'Complete system audit logs and security events',
                'Clinical consultations, pharmacy inventory, and executive reports',
            ],
            self::HOSPITAL_DIRECTOR => [
                'Executive analytics, census, and departmental throughput',
                'Physician rosters, specialties, and clinical schedules',
                'Patient demographic and intake volume summaries',
                'Read-only clinical activity oversight',
            ],
            self::DOCTOR => [
                'Assigned outpatient consultation queues and appointments',
                'Patient medical records, allergy profiles, and previous visits',
                'Nursing vital signs and initial triage assessments',
                'Formulary medicine catalogue and previous prescriptions',
            ],
            self::NURSE => [
                'Intake waiting queue and nursing assessment station',
                'Patient demographics, emergency contacts, and vital histories',
                'Inpatient observation charts and ward allocations',
            ],
            self::PHARMACIST => [
                'Finalized prescription dispensing queue',
                'Medicine inventory balances, batch expiry dates, and unit prices',
                'Complete historical stock movement audit ledger',
            ],
            self::LAB_TECHNICIAN => [
                'Diagnostic lab order queues and specimen logs',
                'Laboratory reagent inventory and consumable supplies',
            ],
            self::CASHIER => [
                'Patient billing invoices, outstanding balances, and tariffs',
                'Payment transactions and mobile money reconciliation logs',
            ],
            self::RECEPTIONIST => [
                'Patient Master Index (PMI) demographic directory',
                'Incoming public online appointment requests',
                'Front-desk intake waiting lists and walk-in queues',
            ],
        };
    }

    /**
     * Plain-language descriptions of what staff in this role can create or change.
     *
     * @return array<string>
     */
    public function canManageDescriptions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'Create and manage staff accounts and RBAC role assignments',
                'Configure institutional parameters and department directories',
                'Perform administrative overrides with full audit logging',
            ],
            self::HOSPITAL_DIRECTOR => [
                'Authorize and publish physician profiles and hospital news',
                'Generate and export institutional compliance and census reports',
            ],
            self::DOCTOR => [
                'Initiate, draft, and sign immutable SOAP consultation notes',
                'Author append-only signed clinical consultation addenda',
                'Create multi-item electronic prescriptions and clinical instructions',
            ],
            self::NURSE => [
                'Record comprehensive patient vital signs and BMI',
                'Assign 5-tier clinical triage acuity prioritization',
                'Record bedside inpatient observations and nurse notes',
            ],
            self::PHARMACIST => [
                'Dispense prescribed medications with automated FEFO batch allocation',
                'Receive new medicine stock batches and perform auditable adjustments',
                'Decline or cancel unfulfilled prescription items with mandatory reasons',
                'Record inventory wastage, returns, and internal use',
            ],
            self::LAB_TECHNICIAN => [
                'Accession specimens and record pathology laboratory findings',
            ],
            self::CASHIER => [
                'Generate patient invoices, collect payments, and issue receipts',
                'Reconcile M-Pesa mobile money transactions',
            ],
            self::RECEPTIONIST => [
                'Enroll new patients and generate collision-safe MRNs',
                'Check in walk-in arrivals and scheduled appointments to triage',
                'Confirm, reschedule, cancel, and reconcile online appointment bookings',
                'Issue secure Patient Portal activation links to verified patient emails',
            ],
        };
    }

    /**
     * Plain-language descriptions of important role restrictions.
     *
     * @return array<string>
     */
    public function restrictions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'Cannot forge or tamper with signed clinical consultation records',
                'Cannot bypass mandatory reasons on privileged actions',
            ],
            self::HOSPITAL_DIRECTOR => [
                'Read-only clinical access; cannot draft doctor notes or dispense medications',
                'Cannot delete audit records or alter patient clinical history',
            ],
            self::DOCTOR => [
                'Restricted from dispensing medications or adjusting pharmacy stock',
                'Restricted from collecting cashier payments or issuing billing receipts',
                'Signed consultation notes become permanently immutable',
            ],
            self::NURSE => [
                'Cannot sign physician consultation notes or finalize prescriptions',
                'Cannot access financial billing or administrative user management',
            ],
            self::PHARMACIST => [
                'Restricted from authoring medical diagnoses or modifying doctor notes',
                'Cannot bypass FEFO batch allocation or force negative stock balances',
            ],
            self::LAB_TECHNICIAN => [
                'Cannot prescribe medications or alter physician consultation notes',
            ],
            self::CASHIER => [
                'Restricted from viewing private clinical notes or diagnostic lab results',
                'Cannot dispense medications or modify medical record data',
            ],
            self::RECEPTIONIST => [
                'Strictly restricted from viewing confidential clinical diagnoses or medical notes',
                'Cannot access pharmacy dispensary or modify drug inventory',
            ],
        };
    }

    /**
     * Map a technical permission/capability key into a plain-language human description.
     */
    public static function describeCapability(string $key): string
    {
        $map = [
            'system.manage_settings' => 'Configure hospital institutional settings and security parameters',
            'staff.manage_accounts' => 'Create, modify, and manage staff user accounts',
            'staff.assign_roles' => 'Assign role-based access control (RBAC) roles to staff',
            'audit.view_logs' => 'Inspect immutable system-wide security and clinical audit logs',
            'hospital.manage_departments' => 'Create and configure hospital clinical departments',
            'hospital.manage_doctors' => 'Create, verify, and publish physician profiles',
            'hospital.view_departments' => 'View all hospital departments and service directories',
            'hospital.view_doctors' => 'View physician rosters, specialties, and schedules',
            'appointments.view_all' => 'View all scheduled and online appointment requests',
            'appointments.view_assigned' => 'View clinical appointments assigned to self or department',
            'appointments.triage' => 'Review, confirm, reschedule, or cancel patient appointments',
            'appointments.create' => 'Book patient appointments at front desk',
            'reports.view_all' => 'Generate and view hospital executive reports and metrics',
            'census.view_overview' => 'Monitor hospital bed occupancy and inpatient census',
            'patients.view_summary' => 'Access high-level patient demographic and intake summaries',
            'patients.view' => 'Look up registered patient files in Patient Master Index (PMI)',
            'patients.register' => 'Enroll new patients and issue Medical Record Numbers (MRNs)',
            'visits.intake' => 'Check in scheduled appointments and walk-in arrivals',
            'inquiries.process' => 'Process public contact enquiries and patient feedback',
            'triage.queue' => 'Access the nursing intake and initial assessment queue',
            'triage.vital_signs' => 'Record patient vital signs (BP, pulse, SpO2, temp, glucose)',
            'triage.assess' => 'Perform 5-tier clinical triage acuity prioritization',
            'triage.view' => 'Review recorded vital signs and nursing assessments',
            'clinical.consultations' => 'Conduct outpatient medical consultations and clinical examinations',
            'clinical.soap_notes' => 'Author and sign immutable SOAP clinical records and addenda',
            'clinical.prescriptions' => 'Author electronic multi-item medical prescriptions',
            'clinical.lab_requests' => 'Order diagnostic laboratory investigations and imaging',
            'inpatient.observations' => 'Record inpatient bedside observations and nursing notes',
            'inpatient.bed_allocation' => 'Admit, assign, and transfer patients between hospital wards',
            'inpatient.emar' => 'Administer medications via Electronic Medication Administration Record',
            'pharmacy.dispense' => 'Perform FEFO batch allocation and dispense prescribed medications',
            'pharmacy.formulary' => 'Maintain pharmaceutical catalogue, strengths, and units of measure',
            'pharmacy.inventory' => 'Receive batches, perform stock adjustments, and record wastage/returns',
            'lab.accession_specimens' => 'Receive and accession pathology laboratory diagnostic specimens',
            'lab.enter_results' => 'Validate and publish diagnostic pathology laboratory findings',
            'lab.inventory' => 'Manage laboratory reagents and diagnostic consumable supplies',
            'billing.invoicing' => 'Generate patient invoices for consultations, procedures, and drugs',
            'billing.collect_payments' => 'Collect and record cash, insurance, and electronic payments',
            'billing.mpesa_reconcile' => 'Reconcile mobile money (M-Pesa) payment receipts',
            'billing.receipts' => 'Issue official hospital payment receipts and clear financial holds',
        ];

        return $map[$key] ?? ucwords(str_replace(['.', '_'], ' ', $key));
    }
}
