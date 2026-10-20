<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IntakeController extends Controller
{
    /**
     * Front-Desk Intake Station & Waiting Queue.
     */
    public function index(Request $request): View
    {
        $todayVisits = Visit::with(['patient', 'department', 'doctor', 'triageRecord.nurse'])
            ->whereDate('checked_in_at', today())
            ->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'standard') ASC")
            ->latest('checked_in_at')
            ->paginate(15);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        // Search patient if query provided
        $patientSearchResults = collect();
        if ($searchPatient = $request->input('search_patient')) {
            $patientSearchResults = Patient::where('is_active', true)
                ->where(function ($q) use ($searchPatient) {
                    $q->where('mrn', 'like', "%{$searchPatient}%")
                        ->orWhere('first_name', 'like', "%{$searchPatient}%")
                        ->orWhere('last_name', 'like', "%{$searchPatient}%")
                        ->orWhere('phone', 'like', "%{$searchPatient}%");
                })
                ->limit(8)
                ->get();
        }

        // Search appointment if reference provided
        $appointmentSearchResult = null;
        if ($appointmentRef = $request->input('search_appointment')) {
            $appointmentSearchResult = Appointment::with(['department', 'doctor', 'patient'])
                ->where('reference_code', trim($appointmentRef))
                ->first();
        }

        $stats = [
            'waiting_triage' => Visit::whereDate('checked_in_at', today())->where('status', 'waiting_triage')->count(),
            'triaged' => Visit::whereDate('checked_in_at', today())->where('status', 'triaged')->count(),
            'in_consultation' => Visit::whereDate('checked_in_at', today())->where('status', 'in_consultation')->count(),
            'total_today' => Visit::whereDate('checked_in_at', today())->count(),
        ];

        return view('admin.intake.index', [
            'todayVisits' => $todayVisits,
            'departments' => $departments,
            'doctors' => $doctors,
            'patientSearchResults' => $patientSearchResults,
            'appointmentSearchResult' => $appointmentSearchResult,
            'stats' => $stats,
        ]);
    }

    /**
     * Register and check in a walk-in patient.
     */
    public function walkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'reason_for_visit' => ['required', 'string', 'max:500'],
            'priority' => ['required', 'in:emergency,urgent,standard'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Prevent duplicate check-in if patient already has an active visit today
        $existingActiveVisit = Visit::where('patient_id', $patient->id)
            ->whereDate('checked_in_at', today())
            ->whereIn('status', ['waiting_triage', 'triaged', 'in_consultation'])
            ->first();

        if ($existingActiveVisit) {
            return back()->withInput()->withErrors([
                'patient_id' => "Patient {$patient->full_name} is already checked in for visit {$existingActiveVisit->visit_number} (Status: {$existingActiveVisit->status}).",
            ]);
        }

        $visit = DB::transaction(function () use ($validated, $patient) {
            $newVisit = Visit::create([
                'visit_number' => Visit::generateUniqueVisitNumber(),
                'patient_id' => $patient->id,
                'appointment_id' => null,
                'department_id' => $validated['department_id'],
                'doctor_id' => $validated['doctor_id'] ?? null,
                'visit_type' => $validated['priority'] === 'emergency' ? 'emergency' : 'walk_in',
                'reason_for_visit' => $validated['reason_for_visit'],
                'priority' => $validated['priority'],
                'status' => 'waiting_triage',
                'checked_in_by' => Auth::id(),
                'checked_in_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            AuditService::record(
                action: 'visit.walk_in',
                resourceType: 'visit',
                resourceId: (string) $newVisit->id,
                details: [
                    'visit_number' => $newVisit->visit_number,
                    'patient_mrn' => $patient->mrn,
                    'priority' => $newVisit->priority,
                ],
                outcome: 'success'
            );

            return $newVisit;
        });

        return redirect()->route('admin.intake.index')
            ->with('status', "Patient {$patient->full_name} successfully checked in as walk-in (Visit: {$visit->visit_number}). Dispatched to Triage Queue.");
    }

    /**
     * Check in a patient with a scheduled appointment and reconcile records.
     */
    public function checkInAppointment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_id' => ['required', 'exists:appointments,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'reason_for_visit' => ['nullable', 'string', 'max:500'],
            'priority' => ['required', 'in:emergency,urgent,standard'],
        ]);

        $appointment = Appointment::findOrFail($validated['appointment_id']);
        $patient = Patient::findOrFail($validated['patient_id']);

        // Check if appointment has already been checked in
        if ($appointment->visit()->exists()) {
            return back()->withErrors([
                'appointment_id' => "Appointment {$appointment->reference_code} has already been checked in.",
            ]);
        }

        $visit = DB::transaction(function () use ($validated, $appointment, $patient) {
            // Reconcile and link appointment with registered patient record
            $appointment->update([
                'patient_id' => $patient->id,
                'status' => 'confirmed',
            ]);

            $reason = ! empty($validated['reason_for_visit'])
                ? $validated['reason_for_visit']
                : ($appointment->symptoms ?: 'Scheduled Consultation Appointment');

            $newVisit = Visit::create([
                'visit_number' => Visit::generateUniqueVisitNumber(),
                'patient_id' => $patient->id,
                'appointment_id' => $appointment->id,
                'department_id' => $appointment->department_id,
                'doctor_id' => $appointment->doctor_id,
                'visit_type' => 'appointment',
                'reason_for_visit' => $reason,
                'priority' => $validated['priority'],
                'status' => 'waiting_triage',
                'checked_in_by' => Auth::id(),
                'checked_in_at' => now(),
            ]);

            AuditService::record(
                action: 'visit.checked_in',
                resourceType: 'visit',
                resourceId: (string) $newVisit->id,
                details: [
                    'visit_number' => $newVisit->visit_number,
                    'appointment_ref' => $appointment->reference_code,
                    'patient_mrn' => $patient->mrn,
                ],
                outcome: 'success'
            );

            return $newVisit;
        });

        return redirect()->route('admin.intake.index')
            ->with('status', "Appointment {$appointment->reference_code} checked in for {$patient->full_name} (Visit: {$visit->visit_number}). Dispatched to Triage Queue.");
    }
}
