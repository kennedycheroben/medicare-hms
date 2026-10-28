<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispensing;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
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
    public function dispense(
        Request $request,
        PrescriptionItem $item,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized. Medication dispensing is restricted to licensed pharmacy personnel.');
        }

        $validated = $request->validate([
            'quantity_to_dispense' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'pharmacist_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $inventoryService->dispensePrescriptionItem(
            item: $item,
            quantityToDispense: (int) $validated['quantity_to_dispense'],
            idempotencyKey: $validated['idempotency_key'],
            pharmacistNotes: $validated['pharmacist_notes'] ?? null,
            performer: $user
        );

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
        /** @var User $user */
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
