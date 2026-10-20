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
                'appointments.view_all',
                'appointments.triage',
                'reports.view_all',
            ],
            self::HOSPITAL_DIRECTOR => [
                'reports.view_all',
                'hospital.view_departments',
                'hospital.view_doctors',
                'appointments.view_all',
                'census.view_overview',
            ],
            self::DOCTOR => [
                'clinical.consultations',
                'clinical.soap_notes',
                'clinical.prescriptions',
                'clinical.lab_requests',
                'appointments.view_assigned',
            ],
            self::NURSE => [
                'triage.vital_signs',
                'inpatient.observations',
                'inpatient.bed_allocation',
                'inpatient.emar',
            ],
            self::PHARMACIST => [
                'pharmacy.dispense',
                'pharmacy.formulary',
                'pharmacy.inventory',
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
}
