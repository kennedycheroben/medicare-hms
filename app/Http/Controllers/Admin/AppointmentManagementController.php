<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AppointmentManagementController extends Controller
{
    /**
     * Display searchable, filterable list of incoming online appointment bookings.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $status = $request->input('status');
        $departmentId = $request->input('department_id');
        $search = $request->input('search');
        $dateFilter = $request->input('date');

        $query = Appointment::with(['department', 'doctor', 'patient', 'visit'])
            ->latest('appointment_date');

        // If user is a Doctor, restrict default view to their assigned department or doctor_id if not super_admin/hospital_director/receptionist
        if ($user->hasRole('doctor') && ! $user->hasRole(['super_admin', 'hospital_director', 'receptionist'])) {
            if ($user->doctor_id) {
                $query->where('doctor_id', $user->doctor_id);
            } elseif ($user->department_id) {
                $query->where('department_id', $user->department_id);
            }
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($dateFilter === 'today') {
            $query->whereDate('appointment_date', today());
        } elseif ($dateFilter === 'upcoming') {
            $query->whereDate('appointment_date', '>=', today());
        } elseif ($dateFilter === 'past') {
            $query->whereDate('appointment_date', '<', today());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('patient_email', 'like', "%{$search}%");
            });
        }

        $appointments = $query->paginate(20)->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        $stats = [
            'pending' => Appointment::where('status', 'pending')->count(),
            'confirmed' => Appointment::where('status', 'confirmed')->count(),
            'rescheduled' => Appointment::where('status', 'rescheduled')->count(),
            'today' => Appointment::whereDate('appointment_date', today())->count(),
            'unreconciled' => Appointment::whereNull('patient_id')->whereNotIn('status', ['cancelled'])->count(),
        ];

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'departments' => $departments,
            'doctors' => $doctors,
            'status' => $status,
            'departmentId' => $departmentId,
            'search' => $search,
            'dateFilter' => $dateFilter,
            'stats' => $stats,
        ]);
    }

    /**
     * Display comprehensive details of an individual appointment.
     */
    public function show(Appointment $appointment): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Role-based access control: Doctor can only access appointments assigned to them or within their clinical department
        if ($user->hasRole('doctor') && ! $user->hasRole(['super_admin', 'hospital_director', 'receptionist'])) {
            $doctorMatches = $user->doctor_id && (int) $appointment->doctor_id === (int) $user->doctor_id;
            $deptMatches = $user->getClinicalDepartmentId() && (int) $appointment->department_id === (int) $user->getClinicalDepartmentId();

            if (! $doctorMatches && ! $deptMatches) {
                abort(403, 'Unauthorized. Access is restricted to appointments assigned to you or within your clinical department.');
            }
        }

        $appointment->load(['department', 'doctor', 'preferredDoctor', 'assignedBy', 'patient.visits', 'visit.triageRecord', 'confirmedBy', 'cancelledBy', 'rescheduledBy']);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->where('status', 'approved')->orderBy('name')->get();

        // Potential matching patients if not yet linked
        $potentialPatients = collect();
        if (! $appointment->patient_id) {
            $potentialPatients = Patient::where('is_active', true)
                ->where(function ($q) use ($appointment) {
                    $q->where('phone', $appointment->patient_phone)
                        ->orWhere(function ($sub) use ($appointment) {
                            if (! empty($appointment->patient_email)) {
                                $sub->where('email', $appointment->patient_email);
                            }
                        })
                        ->orWhere(function ($sub) use ($appointment) {
                            $parts = explode(' ', trim($appointment->patient_name));
                            if (count($parts) >= 2) {
                                $sub->where('first_name', 'like', "%{$parts[0]}%")
                                    ->where('last_name', 'like', "%{$parts[count($parts) - 1]}%");
                            } else {
                                $sub->where('first_name', 'like', "%{$appointment->patient_name}%")
                                    ->orWhere('last_name', 'like', "%{$appointment->patient_name}%");
                            }
                        });
                })
                ->limit(5)
                ->get();
        }

        $auditLogs = AuditLog::where('resource_type', 'appointment')
            ->where('resource_id', (string) $appointment->id)
            ->with('performer')
            ->latest()
            ->get();

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'departments' => $departments,
            'doctors' => $doctors,
            'potentialPatients' => $potentialPatients,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Receptionist or administrative assignment/reassignment of an eligible doctor to an appointment.
     */
    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized. Doctor assignment is restricted to admissions reception and hospital administration.');
        }

        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['status' => "Appointment {$appointment->reference_code} cannot be assigned or reassigned from status {$appointment->status}."]);
        }

        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'triage_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $doctor = Doctor::findOrFail($validated['doctor_id']);

        // Doctor eligibility validation: must be active, published, and approved
        if (! $doctor->is_available || ! $doctor->is_published || $doctor->status !== 'approved') {
            return back()->withInput()->withErrors([
                'doctor_id' => "Dr. {$doctor->name} is currently not available for clinical assignments.",
            ]);
        }

        // Doctor must have an active linked staff account in system
        $doctorUser = User::where('is_active', true)
            ->where('doctor_id', $doctor->id)
            ->first();
        if (! $doctorUser) {
            return back()->withInput()->withErrors([
                'doctor_id' => "Dr. {$doctor->name} does not have an active linked staff account.",
            ]);
        }

        // Check scheduling conflicts (overlapping active appointments at the same date and time slot)
        $conflict = Appointment::where('doctor_id', $doctor->id)
            ->where('id', '!=', $appointment->id)
            ->whereDate('appointment_date', $appointment->appointment_date)
            ->where('appointment_time', $appointment->appointment_time)
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->exists();

        if ($conflict) {
            return back()->withInput()->withErrors([
                'doctor_id' => "Dr. {$doctor->name} already has a consultation scheduled for {$appointment->appointment_date?->format('Y-m-d')} during slot {$appointment->appointment_time}.",
            ]);
        }

        $formerDoctor = $appointment->doctor;
        $isReassignment = $formerDoctor && (int) $formerDoctor->id !== (int) $doctor->id;

        DB::transaction(function () use ($appointment, $doctor, $validated, $user, $formerDoctor, $isReassignment) {
            $appointment->update([
                'doctor_id' => $doctor->id,
                'department_id' => $doctor->department_id ?? $appointment->department_id,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'triage_notes' => $validated['triage_notes'] ?? $appointment->triage_notes,
            ]);

            AuditService::record(
                action: $isReassignment ? 'appointment.reassigned' : 'appointment.assigned',
                resourceType: 'appointment',
                resourceId: (string) $appointment->id,
                details: [
                    'reference_code' => $appointment->reference_code,
                    'patient_name' => $appointment->patient_name,
                    'assigned_doctor_id' => $doctor->id,
                    'assigned_doctor_name' => $doctor->name,
                    'former_doctor_id' => $formerDoctor?->id,
                    'is_reassignment' => $isReassignment,
                ],
                outcome: 'success',
                actor: $user
            );
        });

        // Notifications dispatched outside transaction
        try {
            if ($isReassignment) {
                NotificationService::notifyAppointmentReassigned($appointment, $formerDoctor, $doctor);
            } else {
                NotificationService::notifyAppointmentAssigned($appointment);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch appointment assignment notification: '.$e->getMessage());
        }

        $msg = $isReassignment
            ? "Appointment {$appointment->reference_code} successfully reassigned to Dr. {$doctor->name}."
            : "Appointment {$appointment->reference_code} successfully assigned to Dr. {$doctor->name}.";

        return redirect()->route('admin.appointments.show', $appointment->id)->with('status', $msg);
    }

    /**
     * Confirm a pending or rescheduled appointment.
     */
    public function confirm(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized. Confirming appointments is restricted to admissions and administration.');
        }

        if (! $appointment->canBeConfirmed()) {
            return back()->withErrors(['status' => "Appointment {$appointment->reference_code} cannot be confirmed from current status: {$appointment->status}."]);
        }

        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'triage_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newDoctorId = $validated['doctor_id'] ?? $appointment->doctor_id;
        $newDoctor = $newDoctorId ? Doctor::find($newDoctorId) : null;
        $formerDoctor = $appointment->doctor;
        $isDoctorAssigned = false;
        $isReassignment = false;

        if ($newDoctor) {
            if (! $newDoctor->is_available) {
                return back()->withInput()->withErrors([
                    'doctor_id' => "Dr. {$newDoctor->name} is currently not available for clinical assignment.",
                ]);
            }

            $conflict = Appointment::where('doctor_id', $newDoctor->id)
                ->where('id', '!=', $appointment->id)
                ->whereDate('appointment_date', $appointment->appointment_date)
                ->where('appointment_time', $appointment->appointment_time)
                ->whereIn('status', ['confirmed', 'rescheduled'])
                ->exists();

            if ($conflict) {
                return back()->withInput()->withErrors([
                    'doctor_id' => "Dr. {$newDoctor->name} already has a consultation scheduled for {$appointment->appointment_date?->format('Y-m-d')} during slot {$appointment->appointment_time}.",
                ]);
            }

            if (! $appointment->assigned_at || (int) $appointment->doctor_id !== (int) $newDoctor->id) {
                $isDoctorAssigned = true;
                $isReassignment = $formerDoctor && (int) $formerDoctor->id !== (int) $newDoctor->id;
            }
        }

        $appointment->update([
            'status' => 'confirmed',
            'department_id' => $validated['department_id'] ?? ($newDoctor?->department_id ?? $appointment->department_id),
            'doctor_id' => $newDoctorId,
            'assigned_by' => $newDoctorId ? ($appointment->assigned_by ?? $user->id) : null,
            'assigned_at' => $newDoctorId ? ($appointment->assigned_at ?? now()) : null,
            'triage_notes' => $validated['triage_notes'] ?? $appointment->triage_notes,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
        ]);

        AuditService::record(
            action: 'appointment.confirmed',
            resourceType: 'appointment',
            resourceId: (string) $appointment->id,
            details: [
                'reference_code' => $appointment->reference_code,
                'patient_name' => $appointment->patient_name,
                'department_id' => $appointment->department_id,
                'doctor_id' => $appointment->doctor_id,
            ],
            outcome: 'success',
            actor: $user
        );

        if ($isDoctorAssigned && $newDoctor) {
            try {
                if ($isReassignment) {
                    NotificationService::notifyAppointmentReassigned($appointment, $formerDoctor, $newDoctor);
                } else {
                    NotificationService::notifyAppointmentAssigned($appointment);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch appointment assignment notification on confirm: '.$e->getMessage());
            }
        }

        return redirect()->route('admin.appointments.show', $appointment->id)
            ->with('status', "Appointment {$appointment->reference_code} confirmed successfully.");
    }

    /**
     * Reschedule an appointment to a new date/time slot.
     */
    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized.');
        }

        if (! $appointment->canBeRescheduled()) {
            return back()->withErrors(['status' => "Appointment {$appointment->reference_code} cannot be rescheduled (current status: {$appointment->status})."]);
        }

        $validated = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string', 'max:50'],
            'reschedule_reason' => ['required', 'string', 'min:5', 'max:500'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $oldDate = $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null;
        $oldTime = $appointment->appointment_time;

        $appointment->update([
            'status' => 'rescheduled',
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'reschedule_reason' => $validated['reschedule_reason'],
            'doctor_id' => $validated['doctor_id'] ?? $appointment->doctor_id,
            'department_id' => $validated['department_id'] ?? $appointment->department_id,
            'rescheduled_at' => now(),
            'rescheduled_by' => $user->id,
        ]);

        AuditService::record(
            action: 'appointment.rescheduled',
            resourceType: 'appointment',
            resourceId: (string) $appointment->id,
            details: [
                'reference_code' => $appointment->reference_code,
                'previous_date' => $oldDate,
                'previous_time' => $oldTime,
                'new_date' => $validated['appointment_date'],
                'new_time' => $validated['appointment_time'],
                'reason' => $validated['reschedule_reason'],
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.appointments.show', $appointment->id)
            ->with('status', "Appointment {$appointment->reference_code} rescheduled to {$validated['appointment_date']} at {$validated['appointment_time']}.");
    }

    /**
     * Cancel an appointment with mandatory clinical or administrative reason.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized.');
        }

        if (! $appointment->canBeCancelled()) {
            return back()->withErrors(['status' => "Appointment {$appointment->reference_code} cannot be cancelled (current status: {$appointment->status})."]);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        AuditService::record(
            action: 'appointment.cancelled',
            resourceType: 'appointment',
            resourceId: (string) $appointment->id,
            details: [
                'reference_code' => $appointment->reference_code,
                'reason' => $validated['cancellation_reason'],
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.appointments.show', $appointment->id)
            ->with('status', "Appointment {$appointment->reference_code} cancelled.");
    }

    /**
     * Reconcile an appointment booking with an existing patient master record.
     */
    public function reconcile(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        $appointment->update([
            'patient_id' => $patient->id,
        ]);

        AuditService::record(
            action: 'appointment.reconciled',
            resourceType: 'appointment',
            resourceId: (string) $appointment->id,
            details: [
                'reference_code' => $appointment->reference_code,
                'patient_mrn' => $patient->mrn,
                'patient_name' => $patient->full_name,
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.appointments.show', $appointment->id)
            ->with('status', "Appointment successfully reconciled and linked with patient {$patient->full_name} ({$patient->mrn}).");
    }

    /**
     * Check in an appointment directly from the appointment workspace without duplicate active visits.
     */
    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['super_admin', 'receptionist'])) {
            abort(403, 'Unauthorized.');
        }

        if (! $appointment->canBeCheckedIn()) {
            return back()->withErrors(['status' => "Appointment {$appointment->reference_code} cannot be checked in (current status: {$appointment->status})."]);
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'priority' => ['required', 'in:emergency,urgent,standard'],
            'reason_for_visit' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Prevent duplicate check-in if patient already has an active visit today
        $existingActiveVisit = Visit::where('patient_id', $patient->id)
            ->whereDate('checked_in_at', today())
            ->whereIn('status', ['waiting_triage', 'triaged', 'in_consultation'])
            ->first();

        if ($existingActiveVisit) {
            return back()->withInput()->withErrors([
                'patient_id' => "Patient {$patient->full_name} ({$patient->mrn}) is already checked in for active visit {$existingActiveVisit->visit_number} (Status: {$existingActiveVisit->status}).",
            ]);
        }

        // Check if appointment has already been checked in
        if ($appointment->visit()->exists()) {
            return back()->withErrors([
                'appointment_id' => "Appointment {$appointment->reference_code} has already been checked in.",
            ]);
        }

        $visit = DB::transaction(function () use ($appointment, $patient, $validated, $user) {
            $appointment->update([
                'patient_id' => $patient->id,
                'status' => 'completed',
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
                'checked_in_by' => $user->id,
                'checked_in_at' => now(),
            ]);

            AuditService::record(
                action: 'appointment.checked_in',
                resourceType: 'appointment',
                resourceId: (string) $appointment->id,
                details: [
                    'reference_code' => $appointment->reference_code,
                    'visit_number' => $newVisit->visit_number,
                    'patient_mrn' => $patient->mrn,
                ],
                outcome: 'success',
                actor: $user
            );

            return $newVisit;
        });

        return redirect()->route('admin.appointments.show', $appointment->id)
            ->with('status', "Appointment {$appointment->reference_code} checked in for {$patient->full_name}. Visit {$visit->visit_number} dispatched to Triage Queue.");
    }
}
