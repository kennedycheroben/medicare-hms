<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function create(Request $request)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        $selectedDeptId = $request->query('dept');
        $selectedDoctorId = $request->query('doctor');

        return view('pages.appointment-booking', compact('departments', 'doctors', 'selectedDeptId', 'selectedDoctorId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'min:3', 'max:100'],
            'patient_email' => ['required', 'email', 'max:100'],
            'patient_phone' => ['required', 'string', new \App\Rules\ValidPhoneNumber()],
            'department_id' => ['required', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string'],
        ], [
            'patient_name.required' => 'Please provide the patient’s full legal name.',
            'patient_email.required' => 'A valid email address is required for appointment confirmation.',
            'patient_phone.required' => 'A telephone contact is required for SMS triage notification.',
            'department_id.required' => 'Please select the relevant medical department.',
            'appointment_date.after_or_equal' => 'Appointment date must be today or a future date.',
            'appointment_time.required' => 'Please choose a morning or afternoon time slot.',
        ]);

        // Generate unique reference code (MC-YYYYMMDD-XXXX)
        do {
            $refCode = 'MC-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Appointment::where('reference_code', $refCode)->exists());

        $appointment = Appointment::create([
            'reference_code' => $refCode,
            'patient_name' => $validated['patient_name'],
            'patient_email' => $validated['patient_email'],
            'patient_phone' => \App\Rules\ValidPhoneNumber::normalize($validated['patient_phone']),
            'department_id' => $validated['department_id'],
            'doctor_id' => $validated['doctor_id'] ?? null,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'symptoms' => null, // Omitted from public scheduling form for clinical privacy
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 500),
        ]);

        return redirect()->route('appointments.confirmation', ['reference' => $appointment->reference_code])
            ->with('success', 'Your appointment request has been successfully registered in our clinical queue!');
    }

    public function confirmation(string $reference)
    {
        // Enforce strict reference code format validation (MC-YYYYMMDD-XXXXX)
        if (!preg_match('/^MC-\d{8}-[A-Z0-9]{4,6}$/', $reference)) {
            abort(404, 'Invalid appointment reference format.');
        }

        // Validate existence in database without exposing model attributes
        $appointmentExists = Appointment::where('reference_code', $reference)->exists();

        if (!$appointmentExists) {
            abort(404, 'Appointment request not found.');
        }

        // Return only the verified reference code to prevent PII exposure
        return view('pages.appointment-confirmation', [
            'referenceCode' => $reference,
        ]);
    }
}
