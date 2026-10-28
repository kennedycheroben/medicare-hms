<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Dispensing;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PharmacyDispensingAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $pharmacist;

    protected User $doctorUser;

    protected User $receptionist;

    protected Patient $patient;

    protected Visit $visit;

    protected Consultation $consultation;

    protected Medicine $amoxicillin;

    protected MedicineBatch $batch1;

    protected MedicineBatch $batch2;

    protected MedicineBatch $expiredBatch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $department = Department::first();
        $doctorProfile = Doctor::first();

        $this->superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.pharm@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->pharmacist = User::create([
            'name' => 'Pharm. Paul',
            'email' => 'paul.pharm@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::PHARMACIST,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane.doc@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $doctorProfile->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Receptionist Tom',
            'email' => 'tom.rec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'mrn' => 'MRN-TEST-50001',
            'first_name' => 'Brian',
            'last_name' => 'Ochieng',
            'date_of_birth' => '1990-11-12',
            'gender' => 'male',
            'phone' => '+254733445566',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $this->visit = Visit::create([
            'visit_number' => 'VST-TEST-50001',
            'patient_id' => $this->patient->id,
            'department_id' => $department->id,
            'doctor_id' => $doctorProfile->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Ear infection',
            'priority' => 'standard',
            'status' => 'completed',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subHours(2),
        ]);

        $this->consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctorProfile->id,
            'clinician_id' => $this->doctorUser->id,
            'status' => 'signed',
            'signed_at' => now()->subHour(),
            'signed_by' => $this->doctorUser->id,
            'chief_complaint' => 'Bacterial infection',
            'primary_diagnosis' => 'Acute Otitis Media',
            'treatment_plan' => 'Antibiotics course',
        ]);

        $this->amoxicillin = Medicine::create([
            'code' => 'MED-AMX-250',
            'brand_name' => 'Amoxil Suspension',
            'generic_name' => 'Amoxicillin',
            'category' => 'Antibiotics',
            'dosage_form' => 'suspension',
            'strength' => '250mg/5ml',
            'unit_of_measure' => 'bottles',
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        // Batch 1: Expires earlier (in 2 months), 10 bottles available
        $this->batch1 = MedicineBatch::create([
            'medicine_id' => $this->amoxicillin->id,
            'batch_number' => 'BTH-AMX-EARLY',
            'expiry_date' => now()->addMonths(2)->format('Y-m-d'),
            'quantity_initial' => 10,
            'quantity_current' => 10,
            'unit_purchase_cost' => 150.00,
            'unit_dispensing_price' => 250.00,
            'is_active' => true,
        ]);

        // Batch 2: Expires later (in 8 months), 20 bottles available
        $this->batch2 = MedicineBatch::create([
            'medicine_id' => $this->amoxicillin->id,
            'batch_number' => 'BTH-AMX-LATER',
            'expiry_date' => now()->addMonths(8)->format('Y-m-d'),
            'quantity_initial' => 20,
            'quantity_current' => 20,
            'unit_purchase_cost' => 150.00,
            'unit_dispensing_price' => 250.00,
            'is_active' => true,
        ]);

        // Expired batch: Expired 1 month ago, 15 bottles
        $this->expiredBatch = MedicineBatch::create([
            'medicine_id' => $this->amoxicillin->id,
            'batch_number' => 'BTH-AMX-EXPIRED',
            'expiry_date' => now()->subMonth()->format('Y-m-d'),
            'quantity_initial' => 15,
            'quantity_current' => 15,
            'unit_purchase_cost' => 150.00,
            'unit_dispensing_price' => 250.00,
            'is_active' => true,
        ]);
    }

    public function test_only_pharmacist_can_access_dispensary_queue(): void
    {
        // Pharmacist allowed
        $this->actingAs($this->pharmacist)
            ->get(route('admin.pharmacy.index'))
            ->assertOk()
            ->assertSee('Pharmacy Dispensary Queue');

        Auth::guard('web')->logout();

        // Doctor denied
        $this->actingAs($this->doctorUser)
            ->get(route('admin.pharmacy.index'))
            ->assertForbidden();

        Auth::guard('web')->logout();

        // Receptionist denied
        $this->actingAs($this->receptionist)
            ->get(route('admin.pharmacy.index'))
            ->assertForbidden();
    }

    public function test_pharmacist_can_dispense_with_fefo_batch_deduction_and_stock_movement(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-DISP-001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxil Suspension 250mg/5ml',
            'dosage' => '5ml',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '7 days',
            'quantity_prescribed' => 15, // Requires 15 bottles: 10 from batch1 (expires first), 5 from batch2
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        $idempotencyKey = 'dispense-key-001';

        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.dispense', $item->id), [
                'quantity_to_dispense' => 15,
                'idempotency_key' => $idempotencyKey,
                'pharmacist_notes' => 'Counseled mother on shaking bottle before use',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Check FEFO batch allocation:
        // Batch 1 (expires in 2 months) should have 10 - 10 = 0
        $this->assertEquals(0, $this->batch1->fresh()->quantity_current);

        // Batch 2 (expires in 8 months) should have 20 - 5 = 15
        $this->assertEquals(15, $this->batch2->fresh()->quantity_current);

        // Expired batch should NOT have been touched (still 15)
        $this->assertEquals(15, $this->expiredBatch->fresh()->quantity_current);

        // Item status updated to dispensed
        $item->refresh();
        $this->assertEquals(15, $item->quantity_dispensed);
        $this->assertEquals('dispensed', $item->dispense_status);

        // Parent prescription updated to dispensed
        $prescription->refresh();
        $this->assertEquals('dispensed', $prescription->status);

        // Check dispensing records: 2 rows created (1 for batch1, 1 for batch2)
        $this->assertEquals(2, Dispensing::where('prescription_item_id', $item->id)->count());

        // Check stock movements created
        $this->assertEquals(2, StockMovement::where('movement_type', 'dispensed')->where('reference_type', 'dispensing')->count());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dispensing.recorded',
            'resource_id' => (string) $prescription->id,
        ]);
    }

    public function test_partial_dispensing_updates_status_appropriately(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-PARTIAL-001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxil Suspension 250mg/5ml',
            'dosage' => '5ml',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '7 days',
            'quantity_prescribed' => 10,
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        // Dispense only 4 bottles
        $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.dispense', $item->id), [
                'quantity_to_dispense' => 4,
                'idempotency_key' => 'partial-key-1',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(4, $item->quantity_dispensed);
        $this->assertEquals(6, $item->remaining_quantity);
        $this->assertEquals('partially_dispensed', $item->dispense_status);

        $prescription->refresh();
        $this->assertEquals('partially_dispensed', $prescription->status);

        // Batch 1 should have 10 - 4 = 6 left
        $this->assertEquals(6, $this->batch1->fresh()->quantity_current);
    }

    public function test_idempotent_dispensing_prevents_duplicate_deduction(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-IDEM-001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxil Suspension',
            'dosage' => '5ml',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '3 days',
            'quantity_prescribed' => 3,
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        $idempotencyKey = 'unique-key-xyz';

        // First attempt succeeds
        $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.dispense', $item->id), [
                'quantity_to_dispense' => 3,
                'idempotency_key' => $idempotencyKey,
            ])
            ->assertRedirect();

        $this->assertEquals(7, $this->batch1->fresh()->quantity_current); // 10 - 3 = 7

        // Second attempt with exact same idempotency_key is recognized as duplicate and doesn't double-deduct
        $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.dispense', $item->id), [
                'quantity_to_dispense' => 3,
                'idempotency_key' => $idempotencyKey,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Duplicate submission detected: this dispensing operation was already processed.');

        $this->assertEquals(7, $this->batch1->fresh()->quantity_current); // Still 7, not 4!
    }

    public function test_insufficient_stock_rejects_dispensing_without_deduction(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-INSUFF-001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxil Suspension',
            'dosage' => '5ml',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '30 days',
            'quantity_prescribed' => 50, // Total valid stock is 10 + 20 = 30; 50 is impossible
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        // Attempting to dispense 50 bottles
        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.dispense', $item->id), [
                'quantity_to_dispense' => 50,
                'idempotency_key' => 'insuff-key-1',
            ]);

        $response->assertSessionHasErrors(['dispense']);

        // Stock must remain unchanged
        $this->assertEquals(10, $this->batch1->fresh()->quantity_current);
        $this->assertEquals(20, $this->batch2->fresh()->quantity_current);
        $this->assertEquals(0, $item->fresh()->quantity_dispensed);
        $this->assertEquals('pending', $item->fresh()->dispense_status);
    }

    public function test_pharmacist_can_decline_prescription_item_with_mandatory_reason(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-DECLINE-001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxil Suspension',
            'dosage' => '5ml',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '7 days',
            'quantity_prescribed' => 5,
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        $this->actingAs($this->pharmacist)
            ->post(route('admin.pharmacy.decline', $item->id), [
                'non_dispense_reason' => 'Severe drug allergy identified upon counseling patient guardian',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals('cancelled', $item->dispense_status);
        $this->assertEquals('Severe drug allergy identified upon counseling patient guardian', $item->non_dispense_reason);

        // Since all items closed and none dispensed, prescription updates to cancelled
        $prescription->refresh();
        $this->assertEquals('cancelled', $prescription->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dispensing.item_declined',
        ]);
    }

    public function test_pharmacist_can_receive_new_batch_and_adjust_stock_with_audit_trail(): void
    {
        // 1. Receive new batch
        $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.store', $this->amoxicillin->id), [
                'batch_number' => 'BTH-NEW-999',
                'expiry_date' => now()->addYear()->format('Y-m-d'),
                'quantity_initial' => 100,
                'unit_purchase_cost' => 140.00,
                'unit_dispensing_price' => 240.00,
            ])
            ->assertRedirect(route('admin.inventory.medicines.show', $this->amoxicillin->id));

        $newBatch = MedicineBatch::where('batch_number', 'BTH-NEW-999')->first();
        $this->assertNotNull($newBatch);
        $this->assertEquals(100, $newBatch->quantity_current);

        // Check stock movement for intake
        $this->assertDatabaseHas('stock_movements', [
            'medicine_batch_id' => $newBatch->id,
            'movement_type' => 'initial_stock',
            'quantity' => 100,
            'quantity_after' => 100,
        ]);

        // 2. Perform manual stock adjustment (deduct 5 for damage)
        $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.adjust', $newBatch->id), [
                'adjustment_type' => 'adjustment_deduct',
                'quantity' => 5,
                'reason' => 'Bottles dropped and broken during warehouse transit',
            ])
            ->assertRedirect();

        $this->assertEquals(95, $newBatch->fresh()->quantity_current);

        // Check stock movement for deduction
        $this->assertDatabaseHas('stock_movements', [
            'medicine_batch_id' => $newBatch->id,
            'movement_type' => 'adjustment_deduct',
            'quantity' => 5,
            'quantity_before' => 100,
            'quantity_after' => 95,
            'reason' => 'Bottles dropped and broken during warehouse transit',
        ]);

        // 3. Attempting to deduct more than in stock fails
        $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.batches.adjust', $newBatch->id), [
                'adjustment_type' => 'adjustment_deduct',
                'quantity' => 200,
                'reason' => 'Impossible deduction',
            ])
            ->assertSessionHasErrors(['adjustment']);

        $this->assertEquals(95, $newBatch->fresh()->quantity_current);
    }

    public function test_inventory_low_stock_filter_executes_at_database_level_and_returns_ok(): void
    {
        // 1. Medicine with NO batches (0 stock) - low stock
        $zeroStockMed = Medicine::create([
            'code' => 'MED-ZERO-001',
            'brand_name' => 'ZeroStock Brand',
            'generic_name' => 'ZeroStock Generic',
            'category' => 'Antibiotics',
            'dosage_form' => 'tablet',
            'strength' => '250mg',
            'unit_of_measure' => 'tablets',
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        // 2. Medicine with only EXPIRED batch (eligible stock = 0) - low stock
        $expiredMed = Medicine::create([
            'code' => 'MED-EXP-002',
            'brand_name' => 'ExpiredMed Brand',
            'generic_name' => 'ExpiredMed Generic',
            'category' => 'Cardiology',
            'dosage_form' => 'tablet',
            'strength' => '10mg',
            'unit_of_measure' => 'tablets',
            'reorder_level' => 15,
            'is_active' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $expiredMed->id,
            'batch_number' => 'BTH-OLD-001',
            'expiry_date' => now()->subMonth()->format('Y-m-d'),
            'quantity_initial' => 50,
            'quantity_current' => 50,
            'is_active' => true,
        ]);

        // 3. Medicine with only INACTIVE batch (eligible stock = 0) - low stock
        $inactiveBatchMed = Medicine::create([
            'code' => 'MED-INA-003',
            'brand_name' => 'InactiveBatch Brand',
            'generic_name' => 'InactiveBatch Generic',
            'category' => 'Dermatology',
            'dosage_form' => 'cream',
            'strength' => '1%',
            'unit_of_measure' => 'tubes',
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $inactiveBatchMed->id,
            'batch_number' => 'BTH-DIS-001',
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'quantity_initial' => 40,
            'quantity_current' => 40,
            'is_active' => false, // Inactive
        ]);

        // 4. Medicine with active stock below threshold (5 <= 10) - low stock
        $lowStockMed = Medicine::create([
            'code' => 'MED-LOW-004',
            'brand_name' => 'LowStock Brand',
            'generic_name' => 'LowStock Generic',
            'category' => 'Antibiotics',
            'dosage_form' => 'capsule',
            'strength' => '500mg',
            'unit_of_measure' => 'capsules',
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $lowStockMed->id,
            'batch_number' => 'BTH-LOW-001',
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'quantity_initial' => 5,
            'quantity_current' => 5,
            'is_active' => true,
        ]);

        // 5. Medicine with high ample stock (100 > 10) - NOT low stock
        $highStockMed = Medicine::create([
            'code' => 'MED-HIGH-005',
            'brand_name' => 'HighStock Brand',
            'generic_name' => 'HighStock Generic',
            'category' => 'Antibiotics',
            'dosage_form' => 'syrup',
            'strength' => '125mg/5ml',
            'unit_of_measure' => 'bottles',
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $highStockMed->id,
            'batch_number' => 'BTH-HIGH-001',
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'quantity_initial' => 100,
            'quantity_current' => 100,
            'is_active' => true,
        ]);

        // Test the exact original failing URL: /admin/inventory?category=&low_stock=1&search=
        $response = $this->actingAs($this->pharmacist)
            ->get('/admin/inventory?category=&low_stock=1&search=');

        $response->assertOk();
        $response->assertViewHas('medicines');
        $response->assertSee('ZeroStock Brand');
        $response->assertSee('ExpiredMed Brand');
        $response->assertSee('InactiveBatch Brand');
        $response->assertSee('LowStock Brand');
        $response->assertDontSee('HighStock Brand');

        // Test combined filter: category=Antibiotics & low_stock=1
        $combinedResponse = $this->actingAs($this->pharmacist)
            ->get('/admin/inventory?category=Antibiotics&low_stock=1&search=');

        $combinedResponse->assertOk();
        $combinedResponse->assertSee('ZeroStock Brand');
        $combinedResponse->assertSee('LowStock Brand');
        $combinedResponse->assertDontSee('ExpiredMed Brand'); // Cardiology
        $combinedResponse->assertDontSee('InactiveBatch Brand'); // Dermatology
        $combinedResponse->assertDontSee('HighStock Brand'); // Stock is high

        // Test search filter: search=ZeroStock & low_stock=1
        $searchResponse = $this->actingAs($this->pharmacist)
            ->get('/admin/inventory?category=&low_stock=1&search=ZeroStock');

        $searchResponse->assertOk();
        $searchResponse->assertSee('ZeroStock Brand');
        $searchResponse->assertDontSee('LowStock Brand');
    }

    public function test_pharmacist_creates_medicine_with_automatic_server_generated_code(): void
    {
        $response = $this->actingAs($this->pharmacist)
            ->post(route('admin.inventory.medicines.store'), [
                'brand_name' => 'Ciprobay 500',
                'generic_name' => 'Ciprofloxacin Hydrochloride',
                'category' => 'Antibiotics',
                'dosage_form' => 'tablet',
                'strength' => '500mg',
                'unit_of_measure' => 'tablets',
                'reorder_level' => 30,
            ]);

        $response->assertRedirect(route('admin.inventory.index'));
        $response->assertSessionHas('status');

        $created = Medicine::where('brand_name', 'Ciprobay 500')->first();
        $this->assertNotNull($created);
        $this->assertStringStartsWith('MED-', $created->code);
        $this->assertMatchesRegularExpression('/^MED-[A-Z0-9]{6}$/', $created->code);
    }
}
