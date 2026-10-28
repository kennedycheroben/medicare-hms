<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    /**
     * Display a specific prescription and its fulfillment audit trail.
     */
    public function show(Prescription $prescription): View
    {
        $prescription->load([
            'patient',
            'visit.department',
            'consultation.clinician',
            'prescriber',
            'cancelledBy',
            'items.medicine',
            'items.dispensings.batch',
            'items.dispensings.dispensedByUser',
        ]);

        return view('admin.prescriptions.show', [
            'prescription' => $prescription,
        ]);
    }

    /**
     * Store a new prescription generated from a clinical consultation.
     */
    public function store(Request $request, Consultation $consultation): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Enforce that only the attending/authoring doctor can prescribe
        if ($consultation->clinician_id !== $user->id && ! $user->hasRole('doctor')) {
            abort(403, 'Unauthorized. Prescriptions must be initiated by an authorized clinical doctor.');
        }

        // Concurrency / Duplicate-submission guard: Check if submission_token was already processed
        $submissionToken = $request->input('submission_token');
        if ($submissionToken) {
            $existing = Prescription::where('submission_token', $submissionToken)->first();
            if ($existing) {
                return redirect()->route('admin.prescriptions.show', $existing->id)
                    ->with('status', 'Duplicate submission prevented. Displaying previously created prescription.');
            }
        }

        $validated = $request->validate([
            'submission_token' => ['nullable', 'string', 'max:64'],
            'clinical_notes' => ['nullable', 'string', 'max:2000'],
            'action_type' => ['required', 'in:draft,finalize'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.dosage' => ['required', 'string', 'max:100'],
            'items.*.route' => ['required', 'in:oral,intravenous,intramuscular,topical,inhalation,ophthalmic,rectal,sublingual'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration' => ['required', 'string', 'max:100'],
            'items.*.quantity_prescribed' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $prescription = DB::transaction(function () use ($consultation, $validated, $user) {
            $isFinal = $validated['action_type'] === 'finalize';

            $prescription = Prescription::create([
                'prescription_number' => Prescription::generateUniquePrescriptionNumber(),
                'submission_token' => $validated['submission_token'] ?? null,
                'patient_id' => $consultation->patient_id, // Derived strictly from clinical consultation
                'visit_id' => $consultation->visit_id,       // Derived strictly from clinical consultation
                'consultation_id' => $consultation->id,
                'prescriber_id' => $user->id,
                'clinical_notes' => $validated['clinical_notes'] ?? null,
                'status' => $isFinal ? 'finalized' : 'draft',
                'finalized_at' => $isFinal ? now() : null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $medicine = Medicine::findOrFail($itemData['medicine_id']);
                $medicationName = "{$medicine->brand_name} ({$medicine->generic_name}) {$medicine->strength}";

                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicine->id,
                    'medication_name' => $medicationName,
                    'dosage' => trim($itemData['dosage']),
                    'route' => $itemData['route'],
                    'frequency' => trim($itemData['frequency']),
                    'duration' => trim($itemData['duration']),
                    'quantity_prescribed' => (int) $itemData['quantity_prescribed'],
                    'quantity_dispensed' => 0,
                    'instructions' => $itemData['instructions'] ?? null,
                    'dispense_status' => 'pending',
                ]);
            }

            AuditService::record(
                action: 'prescription.created',
                resourceType: 'prescription',
                resourceId: (string) $prescription->id,
                details: [
                    'prescription_number' => $prescription->prescription_number,
                    'consultation_id' => $consultation->id,
                    'patient_id' => $consultation->patient_id,
                    'item_count' => count($validated['items']),
                    'status' => $prescription->status,
                ],
                outcome: 'success',
                actor: $user
            );

            return $prescription;
        });

        $message = $prescription->isFinalized()
            ? "Prescription {$prescription->prescription_number} signed and transmitted to pharmacy dispensary."
            : "Prescription {$prescription->prescription_number} saved as draft.";

        return redirect()->route('admin.prescriptions.show', $prescription->id)->with('status', $message);
    }

    /**
     * Finalize and sign off on a draft prescription.
     */
    public function finalize(Request $request, Prescription $prescription): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($prescription->prescriber_id !== $user->id && ! $user->isSuperAdmin()) {
            abort(403, 'Only the prescribing clinician can finalize this prescription.');
        }

        if (! $prescription->isDraft()) {
            return redirect()->route('admin.prescriptions.show', $prescription->id)
                ->withErrors(['status' => 'Only draft prescriptions can be finalized.']);
        }

        $prescription->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        AuditService::record(
            action: 'prescription.finalized',
            resourceType: 'prescription',
            resourceId: (string) $prescription->id,
            details: ['prescription_number' => $prescription->prescription_number],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.prescriptions.show', $prescription->id)
            ->with('status', "Prescription {$prescription->prescription_number} finalized and queued for dispensing.");
    }

    /**
     * Cancel an unfilled or partially filled prescription with auditable rationale.
     */
    public function cancel(Request $request, Prescription $prescription): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Allowed for doctor (prescriber) or pharmacist
        if (! $user->hasRole(['doctor', 'pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized. Prescriptions may only be cancelled by a doctor or pharmacist.');
        }

        if ($prescription->status === 'dispensed') {
            return redirect()->route('admin.prescriptions.show', $prescription->id)
                ->withErrors(['status' => 'Fully dispensed prescriptions cannot be cancelled.']);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($prescription, $validated, $user) {
            $prescription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['cancellation_reason'],
                'cancelled_by' => $user->id,
            ]);

            // Mark any pending or partially dispensed items as cancelled
            foreach ($prescription->items as $item) {
                if ($item->dispense_status !== 'dispensed') {
                    $item->update([
                        'dispense_status' => 'cancelled',
                        'non_dispense_reason' => 'Cancelled as part of prescription cancellation: '.$validated['cancellation_reason'],
                    ]);
                }
            }

            AuditService::record(
                action: 'prescription.cancelled',
                resourceType: 'prescription',
                resourceId: (string) $prescription->id,
                details: [
                    'prescription_number' => $prescription->prescription_number,
                    'reason' => $validated['cancellation_reason'],
                ],
                outcome: 'success',
                actor: $user
            );
        });

        return redirect()->route('admin.prescriptions.show', $prescription->id)
            ->with('status', "Prescription {$prescription->prescription_number} has been cancelled.");
    }
}
