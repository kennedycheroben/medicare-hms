<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    /**
     * Staff Management Module (SuperAdmin only).
     */
    public function staff(Request $request): View
    {
        $staffMembers = User::with(['department', 'doctor'])->latest('id')->paginate(15);

        return view('admin.modules.staff', [
            'staffMembers' => $staffMembers,
        ]);
    }

    /**
     * Hospital Executive Reports (SuperAdmin, HospitalDirector).
     */
    public function reports(Request $request): View
    {
        return view('admin.modules.reports');
    }

    /**
     * Clinical Consultations & EHR (SuperAdmin, Doctor).
     */
    public function clinical(Request $request): View
    {
        return view('admin.modules.clinical');
    }

    /**
     * Triage & Vitals (SuperAdmin, Nurse, Receptionist).
     */
    public function triage(Request $request): View
    {
        return view('admin.modules.triage');
    }

    /**
     * Pharmacy & Formulary Dispensation (SuperAdmin, Pharmacist).
     */
    public function pharmacy(Request $request): View
    {
        return view('admin.modules.pharmacy');
    }

    /**
     * Laboratory Specimen & Results (SuperAdmin, LabTechnician).
     */
    public function laboratory(Request $request): View
    {
        return view('admin.modules.laboratory');
    }

    /**
     * Billing, Invoicing & MPesa Reconciliation (SuperAdmin, Cashier).
     */
    public function billing(Request $request): View
    {
        return view('admin.modules.billing');
    }

    /**
     * Centralized Security Audit Logs (SuperAdmin only).
     */
    public function auditLogs(Request $request): View
    {
        $logs = AuditLog::with('user')->latest('id')->paginate(25);

        return view('admin.modules.audit-logs', [
            'logs' => $logs,
        ]);
    }
}
