<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Display the medicine inventory catalogue and stock balances.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $lowStockOnly = $request->boolean('low_stock');

        $query = Medicine::query()
            ->with(['batches' => function ($q) {
                $q->where('is_active', true)->orderBy('expiry_date', 'asc');
            }])
            ->withSum(['batches as available_stock' => function ($q) {
                $q->where('is_active', true)->where('expiry_date', '>=', today());
            }], 'quantity_current')
            ->orderBy('brand_name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brand_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($lowStockOnly) {
            $query->lowStock();
        }

        $medicines = $query->paginate(20)->withQueryString();

        $categories = Medicine::select('category')->distinct()->pluck('category');

        return view('admin.inventory.index', [
            'medicines' => $medicines,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'lowStockOnly' => $lowStockOnly,
        ]);
    }

    /**
     * Store a new medicine catalogue entry with server-side collision-safe code generation.
     */
    public function storeMedicine(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized. Creating medication catalogue entries is restricted to pharmacy staff.');
        }

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:150'],
            'generic_name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'dosage_form' => ['required', 'in:tablet,capsule,syrup,suspension,injection,cream,ointment,drops,inhaler,suppository'],
            'strength' => ['required', 'string', 'max:100'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $code = Medicine::generateUniqueCode();

        $medicine = Medicine::create(array_merge($validated, [
            'code' => $code,
            'is_active' => true,
        ]));

        AuditService::record(
            action: 'inventory.medicine_created',
            resourceType: 'medicine',
            resourceId: (string) $medicine->id,
            details: [
                'code' => $medicine->code,
                'name' => "{$medicine->brand_name} ({$medicine->generic_name})",
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->route('admin.inventory.index')
            ->with('status', "Medicine {$medicine->brand_name} ({$medicine->code}) created successfully.");
    }

    /**
     * Display detailed medicine information, batches, and movements.
     */
    public function show(Medicine $medicine): View
    {
        $medicine->load([
            'batches' => function ($q) {
                $q->orderBy('expiry_date', 'asc');
            },
            'stockMovements.batch',
            'stockMovements.performer',
        ]);

        return view('admin.inventory.show', [
            'medicine' => $medicine,
        ]);
    }

    /**
     * Receive and log a new stock batch for a specific medicine.
     */
    public function storeBatch(
        Request $request,
        Medicine $medicine,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'batch_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('medicine_batches')->where(fn ($q) => $q->where('medicine_id', $medicine->id)),
            ],
            'expiry_date' => ['required', 'date', 'after:today'],
            'quantity_initial' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit_purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'unit_dispensing_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $batch = $inventoryService->receiveBatch(
            medicine: $medicine,
            data: $validated,
            performer: $user
        );

        return redirect()->route('admin.inventory.medicines.show', $medicine->id)
            ->with('status', "Batch {$batch->batch_number} with {$batch->quantity_initial} units received successfully.");
    }

    /**
     * Perform an auditable manual stock adjustment on a medicine batch.
     */
    public function adjustStock(
        Request $request,
        MedicineBatch $batch,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'adjustment_type' => ['required', 'in:adjustment_add,adjustment_deduct'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $result = $inventoryService->adjustBatch(
            batch: $batch,
            type: $validated['adjustment_type'],
            quantity: (int) $validated['quantity'],
            reason: $validated['reason'],
            performer: $user
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['adjustment' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Process an Over-the-Counter (OTC) sale directly from pharmacy inventory.
     */
    public function otcSale(
        Request $request,
        Medicine $medicine,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'customer_name' => ['required', 'string', 'max:150'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        $result = $inventoryService->otcSale(
            medicine: $medicine,
            quantity: (int) $validated['quantity'],
            idempotencyKey: $validated['idempotency_key'],
            customerName: $validated['customer_name'],
            paymentReference: $validated['payment_reference'] ?? null,
            notes: $validated['notes'] ?? null,
            performer: $user
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['otc_sale' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Issue medication stock for internal hospital use.
     */
    public function internalUse(
        Request $request,
        MedicineBatch $batch,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'department' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $result = $inventoryService->internalUse(
            batch: $batch,
            quantity: (int) $validated['quantity'],
            department: $validated['department'],
            reason: $validated['reason'],
            performer: $user
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['internal_use' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Record inventory wastage or disposal of expired/damaged medication.
     */
    public function wastage(
        Request $request,
        MedicineBatch $batch,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $result = $inventoryService->recordWastage(
            batch: $batch,
            quantity: (int) $validated['quantity'],
            reason: $validated['reason'],
            performer: $user
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['wastage' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Record stock return back into an active non-expired batch.
     */
    public function returnStock(
        Request $request,
        MedicineBatch $batch,
        InventoryService $inventoryService
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $inventoryService->recordReturn(
            batch: $batch,
            quantity: (int) $validated['quantity'],
            reason: $validated['reason'],
            referenceType: $validated['reference_type'] ?? null,
            referenceId: $validated['reference_id'] ?? null,
            performer: $user
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['return' => $result['error']]);
        }

        return redirect()->back()->with('status', $result['message']);
    }

    /**
     * Display comprehensive inventory stock movements audit trail.
     */
    public function movements(Request $request): View
    {
        $movementType = $request->input('movement_type');
        $medicineId = $request->input('medicine_id');

        $query = StockMovement::with(['medicine', 'batch', 'performer'])
            ->orderBy('created_at', 'desc');

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        if ($medicineId) {
            $query->where('medicine_id', $medicineId);
        }

        $movements = $query->paginate(25)->withQueryString();
        $medicines = Medicine::select('id', 'brand_name', 'code')->orderBy('brand_name')->get();

        return view('admin.inventory.movements', [
            'movements' => $movements,
            'medicines' => $medicines,
            'movementType' => $movementType,
            'medicineId' => $medicineId,
        ]);
    }
}
