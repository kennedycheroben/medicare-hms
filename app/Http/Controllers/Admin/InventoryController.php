<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $query = Medicine::with(['batches' => function ($q) {
            $q->where('is_active', true)->orderBy('expiry_date', 'asc');
        }])->orderBy('brand_name', 'asc');

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

        $medicines = $query->paginate(20)->withQueryString();

        if ($lowStockOnly) {
            // Filter collections in memory or through subquery
            $lowStockMedicines = $medicines->filter->isLowStock();
        }

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
     * Store a new medicine catalogue entry.
     */
    public function storeMedicine(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized. Creating medication catalogue entries is restricted to pharmacy staff.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:medicines,code'],
            'brand_name' => ['required', 'string', 'max:150'],
            'generic_name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'dosage_form' => ['required', 'in:tablet,capsule,syrup,suspension,injection,cream,ointment,drops,inhaler,suppository'],
            'strength' => ['required', 'string', 'max:100'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $medicine = Medicine::create(array_merge($validated, [
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
    public function storeBatch(Request $request, Medicine $medicine): RedirectResponse
    {
        /** @var \App\Models\User $user */
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

        $batch = DB::transaction(function () use ($medicine, $validated, $user) {
            $qty = (int) $validated['quantity_initial'];

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->id,
                'batch_number' => strtoupper(trim($validated['batch_number'])),
                'expiry_date' => $validated['expiry_date'],
                'quantity_initial' => $qty,
                'quantity_current' => $qty,
                'unit_purchase_cost' => $validated['unit_purchase_cost'] ?? 0.00,
                'unit_dispensing_price' => $validated['unit_dispensing_price'] ?? 0.00,
                'is_active' => true,
            ]);

            StockMovement::create([
                'medicine_id' => $medicine->id,
                'medicine_batch_id' => $batch->id,
                'movement_type' => 'initial_stock',
                'quantity' => $qty,
                'quantity_before' => 0,
                'quantity_after' => $qty,
                'reference_type' => 'medicine_batch',
                'reference_id' => $batch->id,
                'performed_by' => $user->id,
                'reason' => 'Stock intake',
                'notes' => "Received new Batch #{$batch->batch_number} (Expires {$batch->expiry_date->format('Y-m-d')})",
                'created_at' => now(),
            ]);

            AuditService::record(
                action: 'inventory.batch_received',
                resourceType: 'medicine_batch',
                resourceId: (string) $batch->id,
                details: [
                    'medicine_id' => $medicine->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $qty,
                ],
                outcome: 'success',
                actor: $user
            );

            return $batch;
        });

        return redirect()->route('admin.inventory.medicines.show', $medicine->id)
            ->with('status', "Batch {$batch->batch_number} with {$batch->quantity_initial} units received successfully.");
    }

    /**
     * Perform an auditable manual stock adjustment on a medicine batch.
     */
    public function adjustStock(Request $request, MedicineBatch $batch): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasRole(['pharmacist', 'super_admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'adjustment_type' => ['required', 'in:adjustment_add,adjustment_deduct'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $quantity = (int) $validated['quantity'];
        $type = $validated['adjustment_type'];

        $result = DB::transaction(function () use ($batch, $quantity, $type, $validated, $user) {
            /** @var MedicineBatch $lockedBatch */
            $lockedBatch = MedicineBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();

            if ($type === 'adjustment_deduct' && $quantity > $lockedBatch->quantity_current) {
                return [
                    'success' => false,
                    'error' => "Cannot deduct {$quantity} units. Batch #{$lockedBatch->batch_number} current stock is only {$lockedBatch->quantity_current} units.",
                ];
            }

            $qtyBefore = $lockedBatch->quantity_current;

            if ($type === 'adjustment_add') {
                $lockedBatch->increment('quantity_current', $quantity);
                $qtyAfter = $qtyBefore + $quantity;
            } else {
                $lockedBatch->decrement('quantity_current', $quantity);
                $qtyAfter = $qtyBefore - $quantity;
            }

            StockMovement::create([
                'medicine_id' => $lockedBatch->medicine_id,
                'medicine_batch_id' => $lockedBatch->id,
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'reference_type' => 'manual_adjustment',
                'reference_id' => $lockedBatch->id,
                'performed_by' => $user->id,
                'reason' => $validated['reason'],
                'notes' => "Manual adjustment: {$type} of {$quantity} units",
                'created_at' => now(),
            ]);

            AuditService::record(
                action: 'inventory.stock_adjusted',
                resourceType: 'medicine_batch',
                resourceId: (string) $lockedBatch->id,
                details: [
                    'batch_number' => $lockedBatch->batch_number,
                    'adjustment_type' => $type,
                    'quantity' => $quantity,
                    'reason' => $validated['reason'],
                ],
                outcome: 'success',
                actor: $user
            );

            return [
                'success' => true,
                'message' => "Stock adjustment of {$quantity} units applied to Batch #{$lockedBatch->batch_number}.",
            ];
        });

        if (! $result['success']) {
            return redirect()->back()->withErrors(['adjustment' => $result['error']]);
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
