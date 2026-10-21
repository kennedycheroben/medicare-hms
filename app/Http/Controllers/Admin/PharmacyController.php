<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispensing;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    /**
     * Display the active pharmacy dispensary prescription queue.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'active');
        $search = $request->input('search');

        $query = Prescription::with(['patient', 'prescriber', 'items.medicine'])
            ->orderBy('created_at', 'asc');

        if ($statusFilter === 'active') {
            $query->whereIn('status', ['finalized', 'partially_dispensed']);
        } elseif ($statusFilter === 'dispensed') {
            $query->where('status', 'dispensed');
        } elseif ($statusFilter === 'cancelled') {
            $query->where('status', 'cancelled');
        } elseif ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('mrn', 'like', "%{$search}%");
                    });
            });
        }

        $prescriptions = $query->paginate(15)->withQueryString();

        // Operational metrics for the dispensary dashboard
        $pendingCount = Prescription::whereIn('status', ['finalized', 'partially_dispensed'])->count();
        $dispensedTodayCount = Dispensing::whereDate('dispensed_at', today())->count();
        $lowStockMedicines = Medicine::where('is_active', true)->get()->filter->isLowStock()->count();
        $expiredBatchesCount = MedicineBatch::where('is_active', true)->where('expiry_date', '<', today())->where('quantity_current', '>', 0)->count();

        return view('admin.pharmacy.index', [
            'prescriptions' => $prescriptions,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'pendingCount' => $pendingCount,
            'dispensedTodayCount' => $dispensedTodayCount,
            'lowStockCount' => $lowStockMedicines,
            'expiredBatchesCount' => $expiredBatchesCount,
        ]);
    }

    /**
     * Display the prescription fulfillment and dispensing interface.
     */
    public function show(Prescription $prescription): View
    {
        $prescription->load([
            'patient',
            'visit.triageRecord',
            'consultation.clinician',
            'prescriber',
            'cancelledBy',
            'items.medicine.batches' => function ($query) {
                $query->where('is_active', true)
                    ->where('expiry_date', '>=', today())
                    ->where('quantity_current', '>', 0)
                    ->orderBy('expiry_date', 'asc');
            },
            'items.dispensings.batch',
            'items.dispensings.dispensedByUser',
        ]);

        return view('admin.pharmacy.show', [
            'prescription' => $prescription,
        ]);
    }

    /**
     * Concurrency-safe, transactional dispensing of a prescription item.
     * Enforces FEFO batch allocation, negative stock prevention, and idempotency.
     */
    public function dispense(Request $request, PrescriptionItem $item): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized. Medication dispensing is restricted to licensed pharmacy personnel.');
        }

        $validated = $request->validate([
            'quantity_to_dispense' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'pharmacist_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $quantityToDispense = (int) $validated['quantity_to_dispense'];
        $idempotencyKey = $validated['idempotency_key'];

        // Execute all state validation, batch locks, inventory deduction, and dispensing records in an ACID transaction
        $result = DB::transaction(function () use ($item, $quantityToDispense, $idempotencyKey, $validated, $user) {
            // Check for previous execution with the same idempotency key
            if (Dispensing::where('idempotency_key', $idempotencyKey)->exists()) {
                return [
                    'success' => true,
                    'message' => 'Duplicate submission detected: this dispensing operation was already processed.',
                ];
            }

            // Lock the item and parent prescription row-level to prevent concurrent race conditions
            /** @var PrescriptionItem $lockedItem */
            $lockedItem = PrescriptionItem::where('id', $item->id)->lockForUpdate()->firstOrFail();
            /** @var Prescription $prescription */
            $prescription = Prescription::where('id', $lockedItem->prescription_id)->lockForUpdate()->firstOrFail();

            if (! in_array($prescription->status, ['finalized', 'partially_dispensed'], true)) {
                return [
                    'success' => false,
                    'error' => "Prescription #{$prescription->prescription_number} is in '{$prescription->status}' status and cannot be dispensed.",
                ];
            }

            if (in_array($lockedItem->dispense_status, ['dispensed', 'cancelled'], true)) {
                return [
                    'success' => false,
                    'error' => "Medication line item is already {$lockedItem->dispense_status}.",
                ];
            }

            $remainingQuantity = $lockedItem->remaining_quantity;
            if ($quantityToDispense > $remainingQuantity) {
                return [
                    'success' => false,
                    'error' => "Cannot dispense {$quantityToDispense} units. Outstanding prescribed quantity is only {$remainingQuantity}.",
                ];
            }

            // Lock eligible non-expired active batches for this medicine in FEFO order
            $batches = MedicineBatch::where('medicine_id', $lockedItem->medicine_id)
                ->where('is_active', true)
                ->where('expiry_date', '>=', today())
                ->where('quantity_current', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailableStock = $batches->sum('quantity_current');
            if ($totalAvailableStock < $quantityToDispense) {
                return [
                    'success' => false,
                    'error' => "Insufficient stock in pharmacy. Available non-expired stock: {$totalAvailableStock}, Requested: {$quantityToDispense}.",
                ];
            }

            // Deduct stock across batches according to FEFO
            $remainingNeeded = $quantityToDispense;

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0) {
                    break;
                }

                $takeFromBatch = min($batch->quantity_current, $remainingNeeded);
                $qtyBefore = $batch->quantity_current;

                // Decrement batch stock
                $batch->decrement('quantity_current', $takeFromBatch);
                $qtyAfter = $qtyBefore - $takeFromBatch;

                // Create individual dispensing audit record
                $dispensing = Dispensing::create([
                    'dispensation_number' => Dispensing::generateUniqueDispensationNumber(),
                    'idempotency_key' => $idempotencyKey,
                    'prescription_id' => $prescription->id,
                    'prescription_item_id' => $lockedItem->id,
                    'medicine_id' => $lockedItem->medicine_id,
                    'medicine_batch_id' => $batch->id,
                    'quantity_dispensed' => $takeFromBatch,
                    'dispensed_by' => $user->id,
                    'pharmacist_notes' => $validated['pharmacist_notes'] ?? null,
                    'dispensed_at' => now(),
                ]);

                // Record immutable stock movement
                StockMovement::create([
                    'medicine_id' => $lockedItem->medicine_id,
                    'medicine_batch_id' => $batch->id,
                    'movement_type' => 'dispensed',
                    'quantity' => $takeFromBatch,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'reference_type' => 'dispensing',
                    'reference_id' => $dispensing->id,
                    'performed_by' => $user->id,
                    'reason' => 'Prescription fulfillment',
                    'notes' => "Dispensed for Prescription #{$prescription->prescription_number} (Batch {$batch->batch_number})",
                    'created_at' => now(),
                ]);

                $remainingNeeded -= $takeFromBatch;
            }

            // Update item fulfillment state
            $newQuantityDispensed = $lockedItem->quantity_dispensed + $quantityToDispense;
            $newStatus = $newQuantityDispensed >= $lockedItem->quantity_prescribed ? 'dispensed' : 'partially_dispensed';

            $lockedItem->update([
                'quantity_dispensed' => $newQuantityDispensed,
                'dispense_status' => $newStatus,
            ]);

            // Refresh parent prescription lifecycle status
            $prescription->load('items');
            $prescription->refreshDispenseStatus();

            AuditService::record(
                action: 'dispensing.recorded',
                resourceType: 'prescription',
                resourceId: (string) $prescription->id,
                details: [
                    'prescription_number' => $prescription->prescription_number,
                    'item_id' => $lockedItem->id,
                    'medicine_id' => $lockedItem->medicine_id,
                    'quantity_dispensed' => $quantityToDispense,
                    'new_dispense_status' => $newStatus,
                ],
                outcome: 'success',
                actor: $user
            );

            return [
                'success' => true,
                'message' => "Successfully dispensed {$quantityToDispense} units of {$lockedItem->medication_name}.",
            ];
        });

        if (! $result['success']) {
            return redirect()->back()->withErrors(['dispense' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Decline to dispense an individual prescription line item with mandatory clinical rationale.
     */
    public function decline(Request $request, PrescriptionItem $item): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'non_dispense_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($item, $validated, $user) {
            /** @var PrescriptionItem $lockedItem */
            $lockedItem = PrescriptionItem::where('id', $item->id)->lockForUpdate()->firstOrFail();
            /** @var Prescription $prescription */
            $prescription = Prescription::where('id', $lockedItem->prescription_id)->lockForUpdate()->firstOrFail();

            $lockedItem->update([
                'dispense_status' => 'cancelled',
                'non_dispense_reason' => $validated['non_dispense_reason'],
            ]);

            $prescription->load('items');
            $prescription->refreshDispenseStatus();

            AuditService::record(
                action: 'dispensing.item_declined',
                resourceType: 'prescription_item',
                resourceId: (string) $lockedItem->id,
                details: [
                    'prescription_number' => $prescription->prescription_number,
                    'reason' => $validated['non_dispense_reason'],
                ],
                outcome: 'success',
                actor: $user
            );
        });

        return redirect()->back()->with('status', "Item {$item->medication_name} declined with documented reason.");
    }

    /**
     * Display chronological dispensing transaction log.
     */
    public function history(Request $request): View
    {
        $search = $request->input('search');

        $query = Dispensing::with(['prescription.patient', 'prescriptionItem', 'medicine', 'batch', 'dispensedByUser'])
            ->orderBy('dispensed_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dispensation_number', 'like', "%{$search}%")
                    ->orWhereHas('prescription', function ($pq) use ($search) {
                        $pq->where('prescription_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('medicine', function ($mq) use ($search) {
                        $mq->where('brand_name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%");
                    });
            });
        }

        $dispensings = $query->paginate(20)->withQueryString();

        return view('admin.pharmacy.history', [
            'dispensings' => $dispensings,
            'search' => $search,
        ]);
    }
}
