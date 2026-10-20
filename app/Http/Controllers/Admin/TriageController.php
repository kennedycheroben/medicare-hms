<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\TriageRecord;
use App\Models\Visit;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TriageController extends Controller
{
    /**
     * Display nursing triage queue with clinical prioritization.
     */
    public function index(Request $request): View
    {
        $query = Visit::with(['patient', 'department', 'doctor', 'triageRecord.nurse'])
            ->whereDate('checked_in_at', today())
            ->whereIn('status', ['waiting_triage', 'triaged']);

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // Emergency escalation must always be visible at top
        $visits = $query->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'standard') ASC")
            ->orderBy('checked_in_at', 'ASC')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'waiting' => Visit::whereDate('checked_in_at', today())->where('status', 'waiting_triage')->count(),
            'emergency' => Visit::whereDate('checked_in_at', today())->where('status', 'waiting_triage')->where('priority', 'emergency')->count(),
            'triaged_today' => TriageRecord::whereDate('triaged_at', today())->count(),
        ];

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.triage.index', [
            'visits' => $visits,
            'stats' => $stats,
            'departments' => $departments,
            'filters' => $request->only(['priority', 'department_id']),
        ]);
    }

    /**
     * Show the triage assessment form for a specific patient visit.
     */
    public function assess(Visit $visit): View
    {
        $visit->load(['patient', 'department', 'doctor', 'triageRecord']);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $doctors = Doctor::where('is_available', true)->orderBy('name')->get();

        return view('admin.triage.assess', [
            'visit' => $visit,
            'patient' => $visit->patient,
            'triageRecord' => $visit->triageRecord,
            'departments' => $departments,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Record nursing triage vitals, clinical category, and physician handoff.
     */
    public function store(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'systolic_bp' => ['nullable', 'integer', 'between:40,300'],
            'diastolic_bp' => ['nullable', 'integer', 'between:20,200'],
            'pulse_rate' => ['nullable', 'integer', 'between:30,250'],
            'respiratory_rate' => ['nullable', 'integer', 'between:8,80'],
            'temperature' => ['nullable', 'numeric', 'between:30.0,45.0'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'pain_score' => ['nullable', 'integer', 'between:0,10'],
            'weight_kg' => ['nullable', 'numeric', 'between:1.0,400.0'],
            'height_cm' => ['nullable', 'numeric', 'between:30.0,250.0'],
            'blood_glucose' => ['nullable', 'numeric', 'between:1.0,50.0'],
            'triage_category' => ['required', 'in:emergency,very_urgent,urgent,standard,non_urgent'],
            'presenting_complaint' => ['required', 'string', 'max:1000'],
            'nursing_notes' => ['nullable', 'string', 'max:2000'],
            'referred_department_id' => ['nullable', 'exists:departments,id'],
            'referred_doctor_id' => ['nullable', 'exists:doctors,id'],
        ]);

        $bmi = TriageRecord::computeBmi(
            $validated['weight_kg'] ? (float) $validated['weight_kg'] : null,
            $validated['height_cm'] ? (float) $validated['height_cm'] : null
        );

        DB::transaction(function () use ($validated, $visit, $bmi) {
            $triage = TriageRecord::updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'patient_id' => $visit->patient_id,
                    'triage_nurse_id' => Auth::id(),
                    'systolic_bp' => $validated['systolic_bp'] ?? null,
                    'diastolic_bp' => $validated['diastolic_bp'] ?? null,
                    'pulse_rate' => $validated['pulse_rate'] ?? null,
                    'respiratory_rate' => $validated['respiratory_rate'] ?? null,
                    'temperature' => $validated['temperature'] ?? null,
                    'oxygen_saturation' => $validated['oxygen_saturation'] ?? null,
                    'pain_score' => $validated['pain_score'] ?? null,
                    'weight_kg' => $validated['weight_kg'] ?? null,
                    'height_cm' => $validated['height_cm'] ?? null,
                    'bmi' => $bmi,
                    'blood_glucose' => $validated['blood_glucose'] ?? null,
                    'triage_category' => $validated['triage_category'],
                    'presenting_complaint' => $validated['presenting_complaint'],
                    'nursing_notes' => $validated['nursing_notes'] ?? null,
                    'referred_department_id' => $validated['referred_department_id'] ?? $visit->department_id,
                    'referred_doctor_id' => $validated['referred_doctor_id'] ?? $visit->doctor_id,
                    'triaged_at' => now(),
                ]
            );

            // Escalate visit priority if triage reveals emergency or very urgent
            $newPriority = match ($validated['triage_category']) {
                'emergency' => 'emergency',
                'very_urgent' => 'urgent',
                default => $visit->priority,
            };

            $visit->update([
                'status' => 'triaged',
                'priority' => $newPriority,
                'department_id' => $validated['referred_department_id'] ?? $visit->department_id,
                'doctor_id' => $validated['referred_doctor_id'] ?? $visit->doctor_id,
            ]);

            AuditService::record(
                action: 'triage.recorded',
                resourceType: 'triage_record',
                resourceId: (string) $triage->id,
                details: [
                    'visit_number' => $visit->visit_number,
                    'patient_mrn' => $visit->patient->mrn,
                    'triage_category' => $triage->triage_category,
                    'systolic_bp' => $triage->systolic_bp,
                    'diastolic_bp' => $triage->diastolic_bp,
                    'temperature' => $triage->temperature,
                    'oxygen_saturation' => $triage->oxygen_saturation,
                ],
                outcome: 'success'
            );
        });

        return redirect()->route('admin.triage.index')
            ->with('status', "Triage assessment completed for {$visit->patient->full_name} ({$visit->visit_number}). Dispatched to consultation queue.");
    }
}
