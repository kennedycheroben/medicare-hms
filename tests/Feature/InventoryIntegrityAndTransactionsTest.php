<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryIntegrityAndTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private User $pharmacist;
    private User $doctor;
    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pharmacist = User::factory()->create([
            'role' => UserRole::PHARMACIST,
            'is_active' => true,
        ]);

        $this->doctor = User::factory()->create([
            'role' => UserRole::DOCTOR,
            'is_active' => true,
        ]);

        $this->inventoryService = app(InventoryService::class);
    }

    private function createMedicine(array $attributes = []): Medicine
    {
        static $counter = 100;
        $counter++;

        return Medicine::create(array_merge([
            'code' => 'MED-TEST-' . $counter,
            'brand_name' => 'Test Medicine ' . $counter,
            'generic_name' => 'Generic ' . $counter,
            'category' => 'General',
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'unit_of_measure' => 'tablets',
            'reorder_level' => 10,
            'is_active' => true,
        ], $attributes));
    }

    private function createBatch(Medicine $medicine, array $attributes = []): MedicineBatch
    {
        static $batchCounter = 100;
        $batchCounter++;

        return MedicineBatch::create(array_merge([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BTH-TEST-' . $batchCounter,
            'expiry_date' => now()->addMonths(12)->format('Y-m-d'),
            'quantity_initial' => 100,
            'quantity_current' => 100,
            'unit_purchase_cost' => 10.00,
            'unit_dispensing_price' => 20.00,
            'is_active' => true,
        ], $attributes));
    }

    public function test_receiving_batch_creates_batch_and_initial_stock_movement(): void
    {
        $medicine = $this->createMedicine([
            'brand_name' => 'Amoxicillin Trihydrate',
        ]);

        $batch = $this->inventoryService->receiveBatch(
            medicine: $medicine,
            data: [
                'batch_number' => 'AMX-2026-001',
                'expiry_date' => now()->addMonths(12)->format('Y-m-d'),
                'quantity_initial' => 500,
                'unit_purchase_cost' => 15.50,
                'unit_dispensing_price' => 25.00,
            ],
            performer: $this->pharmacist
        );

        $this->assertDatabaseHas('medicine_batches', [
            'id' => $batch->id,
            'batch_number' => 'AMX-2026-001',
            'quantity_current' => 500,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch->id,
            'movement_type' => 'initial_stock',
            'quantity' => 500,
            'quantity_before' => 0,
            'quantity_after' => 500,
            'performed_by' => $this->pharmacist->id,
        ]);
    }

    public function test_otc_sale_allocates_fefo_batches_and_prevents_duplicate_deduction(): void
    {
        $medicine = $this->createMedicine(['brand_name' => 'Paracetamol 500mg']);

        // Batch 1: expires in 2 months (FEFO first)
        $batch1 = $this->createBatch($medicine, [
            'batch_number' => 'PARA-B1',
            'expiry_date' => now()->addMonths(2)->format('Y-m-d'),
            'quantity_initial' => 20,
            'quantity_current' => 20,
        ]);

        // Batch 2: expires in 8 months (FEFO second)
        $batch2 = $this->createBatch($medicine, [
            'batch_number' => 'PARA-B2',
            'expiry_date' => now()->addMonths(8)->format('Y-m-d'),
            'quantity_initial' => 50,
            'quantity_current' => 50,
        ]);

        $idempotencyKey = (string) Str::uuid();

        // 1. Process OTC sale of 35 units (20 from batch1, 15 from batch2)
        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.medicines.otc-sale', $medicine->id), [
                'quantity' => 35,
                'customer_name' => 'Kamau Njoroge',
                'payment_reference' => 'MPESA-TX-998811',
                'notes' => 'Headache and fever walk-in',
                'idempotency_key' => $idempotencyKey,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(0, $batch1->fresh()->quantity_current);
        $this->assertEquals(35, $batch2->fresh()->quantity_current);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch1->id,
            'movement_type' => 'otc_sale',
            'quantity' => 20,
            'quantity_before' => 20,
            'quantity_after' => 0,
            'reference_id' => $idempotencyKey,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch2->id,
            'movement_type' => 'otc_sale',
            'quantity' => 15,
            'quantity_before' => 50,
            'quantity_after' => 35,
            'reference_id' => $idempotencyKey,
        ]);

        // 2. Duplicate submission with identical idempotency key does NOT deduct again
        $repeatResponse = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.medicines.otc-sale', $medicine->id), [
                'quantity' => 35,
                'customer_name' => 'Kamau Njoroge',
                'payment_reference' => 'MPESA-TX-998811',
                'notes' => 'Headache and fever walk-in',
                'idempotency_key' => $idempotencyKey,
            ]);

        $repeatResponse->assertRedirect();
        $this->assertEquals(0, $batch1->fresh()->quantity_current);
        $this->assertEquals(35, $batch2->fresh()->quantity_current);
    }

    public function test_internal_use_deducts_batch_stock_with_department_attribution(): void
    {
        $medicine = $this->createMedicine();
        $batch = $this->createBatch($medicine, [
            'quantity_initial' => 100,
            'quantity_current' => 100,
        ]);

        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.internal-use', $batch->id), [
                'quantity' => 15,
                'department' => 'Accident & Emergency',
                'reason' => 'Emergency resuscitation crash cart replenishment',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(85, $batch->fresh()->quantity_current);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch->id,
            'movement_type' => 'internal_use',
            'quantity' => 15,
            'quantity_before' => 100,
            'quantity_after' => 85,
            'reference_id' => 'Accident & Emergency',
        ]);
    }

    public function test_wastage_and_disposal_deducts_stock_safely(): void
    {
        $medicine = $this->createMedicine();
        $batch = $this->createBatch($medicine, [
            'quantity_initial' => 50,
            'quantity_current' => 50,
            'expiry_date' => now()->subDay()->format('Y-m-d'), // Expired
        ]);

        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.wastage', $batch->id), [
                'quantity' => 20,
                'reason' => 'Safe incineration of expired medication',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(30, $batch->fresh()->quantity_current);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch->id,
            'movement_type' => 'expired',
            'quantity' => 20,
            'quantity_before' => 50,
            'quantity_after' => 30,
        ]);
    }

    public function test_stock_return_restores_inventory_to_active_batch(): void
    {
        $medicine = $this->createMedicine();
        $batch = $this->createBatch($medicine, [
            'quantity_initial' => 100,
            'quantity_current' => 60,
            'expiry_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.return', $batch->id), [
                'quantity' => 10,
                'reference_type' => 'Ward Return',
                'reference_id' => 'WRD-2026-90',
                'reason' => 'Patient discharged early, unused intact blisters returned',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(70, $batch->fresh()->quantity_current);

        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'medicine_batch_id' => $batch->id,
            'movement_type' => 'return',
            'quantity' => 10,
            'quantity_before' => 60,
            'quantity_after' => 70,
        ]);
    }

    public function test_cannot_deduct_more_stock_than_available_in_batch(): void
    {
        $medicine = $this->createMedicine();
        $batch = $this->createBatch($medicine, [
            'quantity_current' => 5,
        ]);

        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.adjust', $batch->id), [
                'adjustment_type' => 'adjustment_deduct',
                'quantity' => 10,
                'reason' => 'Physical count variance',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['adjustment']);

        $this->assertEquals(5, $batch->fresh()->quantity_current);
    }
}
