<?php

namespace App\Services;

use App\Models\Dispensing;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Intake and receive a new stock batch for a medicine.
     *
     * @param  array{batch_number: string, expiry_date: string, quantity_initial: int, unit_purchase_cost?: float|null, unit_dispensing_price?: float|null}  $data
     */
    public function receiveBatch(Medicine $medicine, array $data, User $performer): MedicineBatch
    {
        return DB::transaction(function () use ($medicine, $data, $performer) {
            $qty = (int) $data['quantity_initial'];
            if ($qty <= 0) {
                throw new InvalidArgumentException('Initial batch quantity must be at least 1 unit.');
            }

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->id,
                'batch_number' => strtoupper(trim($data['batch_number'])),
                'expiry_date' => $data['expiry_date'],
                'quantity_initial' => $qty,
                'quantity_current' => $qty,
                'unit_purchase_cost' => $data['unit_purchase_cost'] ?? 0.00,
                'unit_dispensing_price' => $data['unit_dispensing_price'] ?? 0.00,
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
                'reference_id' => (string) $batch->id,
                'performed_by' => $performer->id,
                'reason' => 'Stock intake / purchase',
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
                actor: $performer
            );

            return $batch;
        });
    }

    /**
     * Concurrency-safe FEFO dispensing of a prescription item.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function dispensePrescriptionItem(
        PrescriptionItem $item,
        int $quantityToDispense,
        string $idempotencyKey,
        ?string $pharmacistNotes,
        User $performer
    ): array {
        return DB::transaction(function () use ($item, $quantityToDispense, $idempotencyKey, $pharmacistNotes, $performer) {
            // Check for previous execution with identical idempotency key to prevent double deductions
            if (Dispensing::where('idempotency_key', $idempotencyKey)->exists()) {
                return [
                    'success' => true,
                    'message' => 'Duplicate submission detected: this dispensing operation was already processed.',
                ];
            }

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

            // Lock eligible non-expired active batches in FEFO order
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

            $remainingNeeded = $quantityToDispense;

            foreach ($batches as $batch) {
                if ($remainingNeeded <= 0) {
                    break;
                }

                $takeFromBatch = min($batch->quantity_current, $remainingNeeded);
                $qtyBefore = $batch->quantity_current;

                $batch->decrement('quantity_current', $takeFromBatch);
                $qtyAfter = $qtyBefore - $takeFromBatch;

                $dispensing = Dispensing::create([
                    'dispensation_number' => Dispensing::generateUniqueDispensationNumber(),
                    'idempotency_key' => $idempotencyKey,
                    'prescription_id' => $prescription->id,
                    'prescription_item_id' => $lockedItem->id,
                    'medicine_id' => $lockedItem->medicine_id,
                    'medicine_batch_id' => $batch->id,
                    'quantity_dispensed' => $takeFromBatch,
                    'dispensed_by' => $performer->id,
                    'pharmacist_notes' => $pharmacistNotes,
                    'dispensed_at' => now(),
                ]);

                StockMovement::create([
                    'medicine_id' => $lockedItem->medicine_id,
                    'medicine_batch_id' => $batch->id,
                    'movement_type' => 'dispensed',
                    'quantity' => $takeFromBatch,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'reference_type' => 'dispensing',
                    'reference_id' => (string) $dispensing->id,
                    'performed_by' => $performer->id,
                    'reason' => 'Prescription fulfillment',
                    'notes' => "Dispensed for Prescription #{$prescription->prescription_number} (Batch {$batch->batch_number})",
                    'created_at' => now(),
                ]);

                $remainingNeeded -= $takeFromBatch;
            }

            $newQuantityDispensed = $lockedItem->quantity_dispensed + $quantityToDispense;
            $newStatus = $newQuantityDispensed >= $lockedItem->quantity_prescribed ? 'dispensed' : 'partially_dispensed';

            $lockedItem->update([
                'quantity_dispensed' => $newQuantityDispensed,
                'dispense_status' => $newStatus,
            ]);

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
                actor: $performer
            );

            $medicine = $lockedItem->medicine ?? Medicine::find($lockedItem->medicine_id);
            if ($medicine) {
                $this->checkAndNotifyLowStock($medicine);
            }

            return [
                'success' => true,
                'message' => "Successfully dispensed {$quantityToDispense} units of {$lockedItem->medication_name}.",
            ];
        });
    }

    /**
     * Process an Over-the-Counter (OTC) medication sale without a prescription.
     * Enforces FEFO batch allocation, negative stock prevention, and idempotency.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function otcSale(
        Medicine $medicine,
        int $quantity,
        string $idempotencyKey,
        string $customerName,
        ?string $paymentReference,
        ?string $notes,
        User $performer
    ): array {
        return DB::transaction(function () use ($medicine, $quantity, $idempotencyKey, $customerName, $paymentReference, $notes, $performer) {
            // Check for duplicate transaction
            $existingMovement = StockMovement::where('movement_type', 'otc_sale')
                ->where('reference_id', $idempotencyKey)
                ->first();

            if ($existingMovement) {
                return [
                    'success' => true,
                    'message' => 'Duplicate submission detected: OTC sale with this reference was already completed.',
                ];
            }

            $batches = MedicineBatch::where('medicine_id', $medicine->id)
                ->where('is_active', true)
                ->where('expiry_date', '>=', today())
                ->where('quantity_current', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->lockForUpdate()
                ->get();

            $totalStock = $batches->sum('quantity_current');
            if ($totalStock < $quantity) {
                return [
                    'success' => false,
                    'error' => "Insufficient stock for OTC sale. Available non-expired stock: {$totalStock}, Requested: {$quantity}.",
                ];
            }

            $remaining = $quantity;
            $batchesUsed = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($batch->quantity_current, $remaining);
                $qtyBefore = $batch->quantity_current;
                $batch->decrement('quantity_current', $take);
                $qtyAfter = $qtyBefore - $take;

                StockMovement::create([
                    'medicine_id' => $medicine->id,
                    'medicine_batch_id' => $batch->id,
                    'movement_type' => 'otc_sale',
                    'quantity' => $take,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'reference_type' => 'otc_sale',
                    'reference_id' => $idempotencyKey,
                    'performed_by' => $performer->id,
                    'reason' => "OTC Sale: {$customerName}".($paymentReference ? " (Ref: {$paymentReference})" : ''),
                    'notes' => $notes ?: "Direct OTC dispensary sale to {$customerName}",
                    'created_at' => now(),
                ]);

                $batchesUsed[] = "Batch {$batch->batch_number} ({$take} units)";
                $remaining -= $take;
            }

            AuditService::record(
                action: 'inventory.otc_sale',
                resourceType: 'medicine',
                resourceId: (string) $medicine->id,
                details: [
                    'medicine_code' => $medicine->code,
                    'quantity' => $quantity,
                    'customer' => $customerName,
                    'payment_ref' => $paymentReference,
                    'batches' => $batchesUsed,
                ],
                outcome: 'success',
                actor: $performer
            );

            $this->checkAndNotifyLowStock($medicine);

            return [
                'success' => true,
                'message' => "Successfully processed OTC sale of {$quantity} units to {$customerName}.",
            ];
        });
    }

    /**
     * Issue medication stock for internal hospital department consumption.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function internalUse(
        MedicineBatch $batch,
        int $quantity,
        string $department,
        string $reason,
        User $performer
    ): array {
        return DB::transaction(function () use ($batch, $quantity, $department, $reason, $performer) {
            /** @var MedicineBatch $lockedBatch */
            $lockedBatch = MedicineBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();

            if ($quantity > $lockedBatch->quantity_current) {
                return [
                    'success' => false,
                    'error' => "Cannot issue {$quantity} units for internal use. Batch #{$lockedBatch->batch_number} only has {$lockedBatch->quantity_current} units available.",
                ];
            }

            $qtyBefore = $lockedBatch->quantity_current;
            $lockedBatch->decrement('quantity_current', $quantity);
            $qtyAfter = $qtyBefore - $quantity;

            StockMovement::create([
                'medicine_id' => $lockedBatch->medicine_id,
                'medicine_batch_id' => $lockedBatch->id,
                'movement_type' => 'internal_use',
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'reference_type' => 'department',
                'reference_id' => $department,
                'performed_by' => $performer->id,
                'reason' => "Internal hospital use: {$department} - {$reason}",
                'notes' => "Issued {$quantity} units to {$department}",
                'created_at' => now(),
            ]);

            AuditService::record(
                action: 'inventory.internal_use',
                resourceType: 'medicine_batch',
                resourceId: (string) $lockedBatch->id,
                details: [
                    'batch_number' => $lockedBatch->batch_number,
                    'department' => $department,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ],
                outcome: 'success',
                actor: $performer
            );

            return [
                'success' => true,
                'message' => "Successfully issued {$quantity} units to {$department}.",
            ];
        });
    }

    /**
     * Record inventory wastage or disposal of expired/damaged medication.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function recordWastage(
        MedicineBatch $batch,
        int $quantity,
        string $reason,
        User $performer
    ): array {
        return DB::transaction(function () use ($batch, $quantity, $reason, $performer) {
            /** @var MedicineBatch $lockedBatch */
            $lockedBatch = MedicineBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();

            if ($quantity > $lockedBatch->quantity_current) {
                return [
                    'success' => false,
                    'error' => "Cannot write off {$quantity} units. Batch #{$lockedBatch->batch_number} has {$lockedBatch->quantity_current} units.",
                ];
            }

            $qtyBefore = $lockedBatch->quantity_current;
            $lockedBatch->decrement('quantity_current', $quantity);
            $qtyAfter = $qtyBefore - $quantity;

            $movementType = $lockedBatch->isExpired() ? 'expired' : 'wastage';

            StockMovement::create([
                'medicine_id' => $lockedBatch->medicine_id,
                'medicine_batch_id' => $lockedBatch->id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'reference_type' => 'disposal_writeoff',
                'reference_id' => (string) $lockedBatch->id,
                'performed_by' => $performer->id,
                'reason' => $reason,
                'notes' => "Stock write-off: {$movementType} ({$reason})",
                'created_at' => now(),
            ]);

            AuditService::record(
                action: 'inventory.wastage_recorded',
                resourceType: 'medicine_batch',
                resourceId: (string) $lockedBatch->id,
                details: [
                    'batch_number' => $lockedBatch->batch_number,
                    'movement_type' => $movementType,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ],
                outcome: 'success',
                actor: $performer
            );

            return [
                'success' => true,
                'message' => "Logged {$quantity} units of {$movementType} for Batch #{$lockedBatch->batch_number}.",
            ];
        });
    }

    /**
     * Restore stock to a batch due to an authorized return (e.g., patient discharge return, ward return).
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function recordReturn(
        MedicineBatch $batch,
        int $quantity,
        string $reason,
        ?string $referenceType,
        ?string $referenceId,
        User $performer
    ): array {
        return DB::transaction(function () use ($batch, $quantity, $reason, $referenceType, $referenceId, $performer) {
            /** @var MedicineBatch $lockedBatch */
            $lockedBatch = MedicineBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();

            if ($lockedBatch->isExpired()) {
                return [
                    'success' => false,
                    'error' => "Cannot return stock into expired Batch #{$lockedBatch->batch_number} (Expired {$lockedBatch->expiry_date->format('Y-m-d')}).",
                ];
            }

            $qtyBefore = $lockedBatch->quantity_current;
            $lockedBatch->increment('quantity_current', $quantity);
            $qtyAfter = $qtyBefore + $quantity;

            StockMovement::create([
                'medicine_id' => $lockedBatch->medicine_id,
                'medicine_batch_id' => $lockedBatch->id,
                'movement_type' => 'return',
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'reference_type' => $referenceType ?: 'return',
                'reference_id' => $referenceId ?: (string) $lockedBatch->id,
                'performed_by' => $performer->id,
                'reason' => $reason,
                'notes' => "Stock returned to inventory: {$reason}",
                'created_at' => now(),
            ]);

            AuditService::record(
                action: 'inventory.return_recorded',
                resourceType: 'medicine_batch',
                resourceId: (string) $lockedBatch->id,
                details: [
                    'batch_number' => $lockedBatch->batch_number,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ],
                outcome: 'success',
                actor: $performer
            );

            return [
                'success' => true,
                'message' => "Successfully returned {$quantity} units into Batch #{$lockedBatch->batch_number}.",
            ];
        });
    }

    /**
     * Perform an auditable manual stock adjustment on a medicine batch.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function adjustBatch(
        MedicineBatch $batch,
        string $type,
        int $quantity,
        string $reason,
        User $performer
    ): array {
        return DB::transaction(function () use ($batch, $type, $quantity, $reason, $performer) {
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
                'reference_id' => (string) $lockedBatch->id,
                'performed_by' => $performer->id,
                'reason' => $reason,
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
                    'reason' => $reason,
                ],
                outcome: 'success',
                actor: $performer
            );

            if ($lockedBatch->medicine) {
                $this->checkAndNotifyLowStock($lockedBatch->medicine);
            }

            return [
                'success' => true,
                'message' => "Stock adjustment of {$quantity} units applied to Batch #{$lockedBatch->batch_number}.",
            ];
        });
    }

    /**
     * Check if total available medicine stock is at or below reorder threshold and notify pharmacy staff.
     */
    protected function checkAndNotifyLowStock(Medicine $medicine): void
    {
        $currentStock = (int) $medicine->batches()
            ->where('is_active', true)
            ->where('expiry_date', '>=', today())
            ->sum('quantity_current');

        if ($currentStock <= $medicine->reorder_level) {
            NotificationService::notifyPharmacyLowStock($medicine, $currentStock, $medicine->reorder_level);
        }
    }
}
