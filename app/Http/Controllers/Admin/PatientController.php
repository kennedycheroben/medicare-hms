<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Display a listing of registered patients with search and filters.
     */
    public function index(Request $request): View
    {
        $query = Patient::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('mrn', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'archived') {
                $query->where('is_active', false);
            }
        }

        $patients = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => Patient::count(),
            'active' => Patient::where('is_active', true)->count(),
            'registered_today' => Patient::whereDate('created_at', today())->count(),
        ];

        return view('admin.patients.index', [
            'patients' => $patients,
            'stats' => $stats,
            'filters' => $request->only(['search', 'gender', 'status']),
        ]);
    }

    /**
     * Show the form for registering a new patient.
     */
    public function create(): View
    {
        return view('admin.patients.create');
    }

    /**
     * Store a newly registered patient in the Patient Master Index.
     */
    public function store(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:male,female,other,unspecified'],
            'phone' => ['required', 'string', new \App\Rules\ValidPhoneNumber()],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', new \App\Rules\ValidPhoneNumber()],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'force_duplicate_override' => ['nullable', 'boolean'],
        ]);

        $forceOverride = $request->boolean('force_duplicate_override');

        // Check for potential duplicate records
        $potentialDuplicates = Patient::findPotentialDuplicates(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            dob: $validated['date_of_birth'],
            phone: $validated['phone'],
            email: $validated['email'] ?? null
        );

        if ($potentialDuplicates->isNotEmpty() && ! $forceOverride) {
            // Present potential matches to staff for auditable decision
            return view('admin.patients.create', [
                'potentialDuplicates' => $potentialDuplicates,
                'formData' => $validated,
            ]);
        }

        // Transactional creation of Patient and unique MRN
        $patient = DB::transaction(function () use ($validated, $request, $potentialDuplicates, $forceOverride) {
            $mrn = Patient::generateUniqueMrn();

            $newPatient = Patient::create([
                'mrn' => $mrn,
                'first_name' => ucwords(strtolower(trim($validated['first_name']))),
                'last_name' => ucwords(strtolower(trim($validated['last_name']))),
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'phone' => trim($validated['phone']),
                'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
                'national_id' => $validated['national_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? 'Nairobi',
                'blood_group' => $validated['blood_group'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'is_active' => true,
                'registered_by' => Auth::id(),
            ]);

            if ($potentialDuplicates->isNotEmpty() && $forceOverride) {
                AuditService::record(
                    action: 'patient.duplicate_overridden',
                    resourceType: 'patient',
                    resourceId: (string) $newPatient->id,
                    details: [
                        'new_mrn' => $newPatient->mrn,
                        'matched_existing_mrns' => $potentialDuplicates->pluck('mrn')->toArray(),
                    ],
                    outcome: 'success'
                );
            }

            AuditService::record(
                action: 'patient.registered',
                resourceType: 'patient',
                resourceId: (string) $newPatient->id,
                details: [
                    'mrn' => $newPatient->mrn,
                    'name' => $newPatient->full_name,
                    'phone' => $newPatient->phone,
                ],
                outcome: 'success'
            );

            return $newPatient;
        });

        return redirect()->route('admin.patients.show', $patient->id)
            ->with('status', "Patient {$patient->full_name} successfully registered with Medical Record Number {$patient->mrn}.");
    }

    /**
     * Display the specified patient profile, clinical summary, and visit history.
     */
    public function show(Patient $patient): View
    {
        $patient->load([
            'visits.department',
            'visits.doctor',
            'visits.triageRecord.nurse',
            'visits.consultation.clinician',
            'appointments.department',
            'appointments.doctor',
            'registeredBy',
            'consultations.clinician',
            'consultations.doctor',
            'consultations.addenda.author',
            'prescriptions.prescriber',
            'prescriptions.items.medicine',
            'prescriptions.dispensings.batch',
        ]);

        return view('admin.patients.show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the form for editing patient demographics.
     */
    public function edit(Patient $patient): View
    {
        return view('admin.patients.edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update patient demographics in storage.
     */
    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:male,female,other,unspecified'],
            'phone' => ['required', 'string', new \App\Rules\ValidPhoneNumber()],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', new \App\Rules\ValidPhoneNumber()],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $patient->update([
            'first_name' => ucwords(strtolower(trim($validated['first_name']))),
            'last_name' => ucwords(strtolower(trim($validated['last_name']))),
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'phone' => trim($validated['phone']),
            'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'national_id' => $validated['national_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? 'Nairobi',
            'blood_group' => $validated['blood_group'] ?? null,
            'allergies' => $validated['allergies'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::record(
            action: 'patient.updated',
            resourceType: 'patient',
            resourceId: (string) $patient->id,
            details: [
                'mrn' => $patient->mrn,
                'name' => $patient->full_name,
                'is_active' => $patient->is_active,
            ],
            outcome: 'success'
        );

        return redirect()->route('admin.patients.show', $patient->id)
            ->with('status', 'Patient demographic records updated successfully.');
    }
}
