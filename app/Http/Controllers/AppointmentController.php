<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Rules\ValidPhoneNumber;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function create(Request $request)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::with('department')->published()->orderBy('name')->get();

        $selectedDeptId = $request->query('dept');
        $selectedDoctorId = $request->query('doctor');

        return view('pages.appointment-booking', compact('departments', 'doctors', 'selectedDeptId', 'selectedDoctorId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'min:3', 'max:100'],
            'patient_email' => ['required', 'email', 'max:100'],
            'patient_phone' => ['required', 'string', new ValidPhoneNumber],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string'],
        ], [
            'patient_name.required' => 'Please provide the patient’s full legal name.',
            'patient_email.required' => 'A valid email address is required for appointment confirmation.',
            'patient_phone.required' => 'A telephone contact is required for SMS triage notification.',
            'appointment_date.after_or_equal' => 'Appointment date must be today or a future date.',
            'appointment_time.required' => 'Please choose a morning or afternoon time slot.',
        ]);

        $departmentId = $validated['department_id'] ?? null;
        $doctorId = $validated['doctor_id'] ?? null;

        // 1. If department is specified, ensure it is active
        if ($departmentId) {
            $department = Department::find($departmentId);
            if (! $department || ! $department->is_active) {
                return back()->withInput()->withErrors([
                    'department_id' => 'The selected medical department is currently not accepting new bookings.',
                ]);
            }
        }

        // 2. If doctor is specified, ensure doctor is available and published
        if ($doctorId) {
            $doctor = Doctor::find($doctorId);
            if (! $doctor || ! $doctor->is_available || ! $doctor->is_published) {
                return back()->withInput()->withErrors([
                    'doctor_id' => 'The selected physician is currently unavailable for appointment scheduling.',
                ]);
            }

            // Scenario 2: Infer department from doctor if not explicitly supplied
            if (! $departmentId) {
                $departmentId = $doctor->department_id;
            } else {
                // Reject mismatched doctor and department
                if ((int) $doctor->department_id !== (int) $departmentId) {
                    return back()->withInput()->withErrors([
                        'doctor_id' => 'The selected doctor does not belong to the selected department.',
                    ]);
                }
            }
        }

        // Scenario 3: General intake when neither department nor doctor is specified
        $triageNotes = null;
        if (! $departmentId && ! $doctorId) {
            $triageNotes = 'General Intake: Unassigned specialty. Awaiting receptionist/clinical triage to assign appropriate department and doctor.';
        }

        // Generate unique reference code (MC-YYYYMMDD-XXXX)
        do {
            $refCode = 'MC-'.date('Ymd').'-'.strtoupper(Str::random(5));
        } while (Appointment::where('reference_code', $refCode)->exists());

        $appointment = Appointment::create([
            'reference_code' => $refCode,
            'patient_name' => $validated['patient_name'],
            'patient_email' => $validated['patient_email'],
            'patient_phone' => ValidPhoneNumber::normalize($validated['patient_phone']),
            'department_id' => $departmentId,
            'doctor_id' => $doctorId,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'symptoms' => null, // Omitted from public scheduling form for clinical privacy
            'status' => 'pending',
            'triage_notes' => $triageNotes,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        NotificationService::notifyAppointmentCreated($appointment);

        return redirect()->route('appointments.confirmation', ['reference' => $appointment->reference_code])
            ->with('success', 'Your appointment request has been successfully registered in our clinical queue!');
    }

    public function confirmation(string $reference)
    {
        // Enforce strict reference code format validation (MC-YYYYMMDD-XXXXX)
        if (! preg_match('/^MC-\d{8}-[A-Z0-9]{4,6}$/', $reference)) {
            abort(404, 'Invalid appointment reference format.');
        }

        // Validate existence in database without exposing model attributes
        $appointmentExists = Appointment::where('reference_code', $reference)->exists();

        if (! $appointmentExists) {
            abort(404, 'Appointment request not found.');
        }

        // Return only the verified reference code to prevent PII exposure
        return view('pages.appointment-confirmation', [
            'referenceCode' => $reference,
        ]);
    }
}
