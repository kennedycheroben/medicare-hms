<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\Medicine;
use App\Models\Visit;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    /**
     * Display the doctor's consultation queue and recent consultations.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $doctorId = $user->doctor_id;

        // Active queue: visits triaged or currently in consultation
        $query = Visit::with(['patient', 'department', 'doctor', 'triageRecord.nurse'])
            ->whereIn('status', ['triaged', 'in_consultation']);

        // Scope to this doctor or unassigned visits for doctor's department
        if ($doctorId) {
            $query->where(function ($q) use ($doctorId, $user) {
                $q->where('doctor_id', $doctorId)
                    ->orWhereNull('doctor_id');

                if ($user->department_id) {
                    $q->orWhere('department_id', $user->department_id);
                }
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('visit_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('mrn', 'like', "%{$search}%");
                    });
            });
        }

        // Order by priority (emergency first), then check-in time
        $queue = $query->orderByRaw("FIELD(priority, 'emergency', 'urgent', 'standard') ASC")
            ->orderBy('checked_in_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Recent completed/signed consultations by this clinician
        $recentConsultations = Consultation::with(['patient', 'visit'])
            ->where('clinician_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.consultations.index', [
            'queue' => $queue,
            'recentConsultations' => $recentConsultations,
            'search' => $search,
        ]);
    }

    /**
     * Start or resume a medical consultation for an eligible hospital visit.
     */
    public function start(Request $request, Visit $visit): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! in_array($visit->status, ['triaged', 'in_consultation'], true)) {
            return redirect()->route('admin.consultations.index')
                ->withErrors(['error' => 'This visit is not in a triaged or consultation-ready state.']);
        }

        // If visit assigned to another doctor, prevent unauthorized takeover
        if ($visit->doctor_id && $user->doctor_id && (int) $visit->doctor_id !== (int) $user->doctor_id) {
            AuditService::record(
                action: 'consultation.unauthorized_takeover_attempt',
                resourceType: 'visit',
                resourceId: (string) $visit->id,
                details: [
                    'assigned_doctor_id' => $visit->doctor_id,
                    'attempting_user_id' => $user->id,
                ],
                outcome: 'denied',
                actor: $user
            );

            abort(403, 'This clinical visit is assigned to another attending physician.');
        }

        $consultation = DB::transaction(function () use ($visit, $user) {
            // Update visit status if triaged
            $updateData = ['status' => 'in_consultation'];
            if (! $visit->doctor_id && $user->doctor_id) {
                $updateData['doctor_id'] = $user->doctor_id;
            }
            $visit->update($updateData);

            $consultation = Consultation::firstOrCreate(
                ['visit_id' => $visit->id],
                [
                    'patient_id' => $visit->patient_id,
                    'doctor_id' => $visit->doctor_id ?? $user->doctor_id,
                    'clinician_id' => $user->id,
                    'status' => 'draft',
                ]
            );

            AuditService::record(
                action: 'consultation.started',
                resourceType: 'consultation',
                resourceId: (string) $consultation->id,
                details: [
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                ],
                outcome: 'success',
                actor: $user
            );

            return $consultation;
        });

        return redirect()->route('admin.consultations.show', $consultation->id);
    }

    /**
     * Display the clinical consultation workspace.
     */
    public function show(Consultation $consultation): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Enforce confidentiality: draft notes only visible to authoring clinician
        if ($consultation->isDraft() && $consultation->clinician_id !== $user->id) {
            AuditService::record(
                action: 'consultation.unauthorized_draft_access',
                resourceType: 'consultation',
                resourceId: (string) $consultation->id,
                details: [
                    'author_id' => $consultation->clinician_id,
                    'attempting_user_id' => $user->id,
                ],
                outcome: 'denied',
                actor: $user
            );

            abort(403, 'Unauthorized. Draft consultation records are confidential and only accessible by the authoring clinician.');
        }

        // If viewing signed notes of another clinician, log auditable cross-clinician review
        if ($consultation->isSigned() && $consultation->clinician_id !== $user->id) {
            AuditService::record(
                action: 'consultation.cross_clinician_view',
                resourceType: 'consultation',
                resourceId: (string) $consultation->id,
                details: [
                    'author_id' => $consultation->clinician_id,
                    'viewer_id' => $user->id,
                ],
                outcome: 'success',
                actor: $user
            );
        }

        $consultation->load([
            'patient',
            'visit.triageRecord.nurse',
            'visit.department',
            'clinician',
            'signedByUser',
            'addenda.author',
            'prescriptions.items.medicine',
            'prescriptions.prescriber',
        ]);

        $medicines = Medicine::where('is_active', true)
            ->orderBy('brand_name')
            ->get();

        return view('admin.consultations.show', [
            'consultation' => $consultation,
            'medicines' => $medicines,
        ]);
    }

    /**
     * Save/update draft consultation notes.
     */
    public function update(Request $request, Consultation $consultation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $consultation->canBeEditedBy($user)) {
            abort(403, 'Unauthorized. This consultation cannot be edited because it is signed or you are not the author.');
        }

        $validated = $request->validate([
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'history_present_illness' => ['nullable', 'string', 'max:5000'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
            'examination_findings' => ['nullable', 'string', 'max:5000'],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'diagnosis_type' => ['nullable', 'in:provisional,confirmed,differential'],
            'primary_diagnosis' => ['nullable', 'string', 'max:255'],
            'secondary_diagnosis' => ['nullable', 'string', 'max:255'],
            'icd11_code' => ['nullable', 'string', 'max:50'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $consultation->update($validated);

        AuditService::record(
            action: 'consultation.draft_saved',
            resourceType: 'consultation',
            resourceId: (string) $consultation->id,
            details: ['visit_id' => $consultation->visit_id],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.consultations.show', $consultation->id)
            ->with('status', 'Consultation draft saved successfully.');
    }

    /**
     * Finalize and sign the clinical consultation note.
     */
    public function sign(Request $request, Consultation $consultation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $consultation->canBeEditedBy($user)) {
            abort(403, 'Only the authoring clinician can sign and lock this consultation record.');
        }

        $validated = $request->validate([
            'chief_complaint' => ['required', 'string', 'min:3', 'max:1000'],
            'history_present_illness' => ['required', 'string', 'min:5', 'max:5000'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
            'examination_findings' => ['required', 'string', 'min:5', 'max:5000'],
            'assessment' => ['required', 'string', 'min:3', 'max:5000'],
            'diagnosis_type' => ['required', 'in:provisional,confirmed,differential'],
            'primary_diagnosis' => ['required', 'string', 'min:2', 'max:255'],
            'secondary_diagnosis' => ['nullable', 'string', 'max:255'],
            'icd11_code' => ['nullable', 'string', 'max:50'],
            'treatment_plan' => ['required', 'string', 'min:3', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        DB::transaction(function () use ($consultation, $validated, $user) {
            $consultation->update(array_merge($validated, [
                'status' => 'signed',
                'signed_at' => now(),
                'signed_by' => $user->id,
            ]));

            // Complete the linked encounter/visit
            if ($consultation->visit) {
                $consultation->visit->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            AuditService::record(
                action: 'consultation.signed',
                resourceType: 'consultation',
                resourceId: (string) $consultation->id,
                details: [
                    'primary_diagnosis' => $validated['primary_diagnosis'],
                    'diagnosis_type' => $validated['diagnosis_type'],
                    'visit_id' => $consultation->visit_id,
                ],
                outcome: 'success',
                actor: $user
            );
        });

        return redirect()->route('admin.consultations.show', $consultation->id)
            ->with('status', 'Consultation finalized and signed. The record is permanently locked; further changes require an addendum.');
    }

    /**
     * Record a post-signature clinical addendum/amendment.
     */
    public function addendum(Request $request, Consultation $consultation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $consultation->isSigned()) {
            return redirect()->route('admin.consultations.show', $consultation->id)
                ->withErrors(['addendum' => 'Addenda can only be appended to signed consultation records.']);
        }

        $validated = $request->validate([
            'addendum_notes' => ['required', 'string', 'min:5', 'max:5000'],
            'reason_for_amendment' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $addendum = DB::transaction(function () use ($consultation, $validated, $user) {
            $addendum = ConsultationAddendum::create([
                'consultation_id' => $consultation->id,
                'author_id' => $user->id,
                'addendum_notes' => $validated['addendum_notes'],
                'reason_for_amendment' => $validated['reason_for_amendment'],
            ]);

            AuditService::record(
                action: 'consultation.addendum_created',
                resourceType: 'consultation_addendum',
                resourceId: (string) $addendum->id,
                details: [
                    'consultation_id' => $consultation->id,
                    'reason_for_amendment' => $validated['reason_for_amendment'],
                ],
                outcome: 'success',
                actor: $user
            );

            return $addendum;
        });

        return redirect()->route('admin.consultations.show', $consultation->id)
            ->with('status', 'Clinical addendum successfully appended to the patient consultation record.');
    }
}
