<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PrescriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected User $pharmacist;
    protected User $receptionist;
    protected Doctor $doctorProfile;
    protected Department $department;
    protected Patient $patient;
    protected Visit $visit;
    protected Consultation $consultation;
    protected Medicine $amoxicillin;
    protected Medicine $paracetamol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->department = Department::first();
        $this->doctorProfile = Doctor::first();

        $this->doctorUser = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane.smith@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $this->doctorProfile->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->pharmacist = User::create([
            'name' => 'Pharm. Paul',
            'email' => 'paul.pharm@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::PHARMACIST,
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
            'mrn' => 'MRN-TEST-20001',
            'first_name' => 'Mercy',
            'last_name' => 'Chebet',
            'date_of_birth' => '1995-03-20',
            'gender' => 'female',
            'phone' => '+254722334455',
            'allergies' => 'Sulfa drugs',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $this->visit = Visit::create([
            'visit_number' => 'VST-TEST-20001',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctorProfile->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Dental pain and fever',
            'priority' => 'standard',
            'status' => 'in_consultation',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subHour(),
        ]);

        $this->consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile->id,
            'clinician_id' => $this->doctorUser->id,
            'status' => 'draft',
            'chief_complaint' => 'Moderate toothache and fever',
            'primary_diagnosis' => 'Dental Abscess',
        ]);

        $this->amoxicillin = Medicine::create([
            'code' => 'MED-AMX-500',
            'brand_name' => 'Amoxil',
            'generic_name' => 'Amoxicillin Trihydrate',
            'category' => 'Antibiotics',
            'dosage_form' => 'capsule',
            'strength' => '500mg',
            'unit_of_measure' => 'capsules',
            'reorder_level' => 100,
            'is_active' => true,
        ]);

        $this->paracetamol = Medicine::create([
            'code' => 'MED-PCM-500',
            'brand_name' => 'Panadol',
            'generic_name' => 'Paracetamol',
            'category' => 'Analgesics',
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'unit_of_measure' => 'tablets',
            'reorder_level' => 200,
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_create_and_finalize_multi_item_prescription(): void
    {
        $token = 'uuid-test-token-1234';

        $response = $this->actingAs($this->doctorUser)
            ->post(route('admin.prescriptions.store', $this->consultation->id), [
                'submission_token' => $token,
                'clinical_notes' => 'Complete full 5-day antibiotic regimen',
                'action_type' => 'finalize',
                'items' => [
                    [
                        'medicine_id' => $this->amoxicillin->id,
                        'dosage' => '500mg',
                        'route' => 'oral',
                        'frequency' => 'TDS (8-hourly)',
                        'duration' => '5 days',
                        'quantity_prescribed' => 15,
                        'instructions' => 'Take with water after meals',
                    ],
                    [
                        'medicine_id' => $this->paracetamol->id,
                        'dosage' => '1g (2 tablets)',
                        'route' => 'oral',
                        'frequency' => 'QDS PRN',
                        'duration' => '3 days',
                        'quantity_prescribed' => 12,
                        'instructions' => 'Take for pain; do not exceed 4g in 24 hours',
                    ],
                ],
            ]);

        $prescription = Prescription::where('consultation_id', $this->consultation->id)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('finalized', $prescription->status);
        $this->assertNotNull($prescription->finalized_at);
        $this->assertEquals($this->patient->id, $prescription->patient_id);
        $this->assertEquals($this->visit->id, $prescription->visit_id);
        $this->assertEquals($this->doctorUser->id, $prescription->prescriber_id);
        $this->assertEquals(2, $prescription->items()->count());

        $response->assertRedirect(route('admin.prescriptions.show', $prescription->id));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'prescription.created',
            'resource_id' => (string) $prescription->id,
        ]);
    }

    public function test_duplicate_submission_token_prevents_duplicate_prescription(): void
    {
        $token = 'idempotent-submission-token-999';

        $payload = [
            'submission_token' => $token,
            'action_type' => 'finalize',
            'items' => [
                [
                    'medicine_id' => $this->amoxicillin->id,
                    'dosage' => '500mg',
                    'route' => 'oral',
                    'frequency' => 'TDS',
                    'duration' => '5 days',
                    'quantity_prescribed' => 15,
                ],
            ],
        ];

        // First submission creates prescription
        $this->actingAs($this->doctorUser)
            ->post(route('admin.prescriptions.store', $this->consultation->id), $payload)
            ->assertRedirect();

        $this->assertEquals(1, Prescription::where('submission_token', $token)->count());

        // Repeated submission with same token redirects to existing without creating duplicate
        $this->actingAs($this->doctorUser)
            ->post(route('admin.prescriptions.store', $this->consultation->id), $payload)
            ->assertRedirect()
            ->assertSessionHas('status', 'Duplicate submission prevented. Displaying previously created prescription.');

        $this->assertEquals(1, Prescription::where('submission_token', $token)->count());
    }

    public function test_doctor_can_save_draft_prescription_and_finalize_later(): void
    {
        $this->actingAs($this->doctorUser)
            ->post(route('admin.prescriptions.store', $this->consultation->id), [
                'action_type' => 'draft',
                'items' => [
                    [
                        'medicine_id' => $this->paracetamol->id,
                        'dosage' => '500mg',
                        'route' => 'oral',
                        'frequency' => 'PRN',
                        'duration' => '2 days',
                        'quantity_prescribed' => 6,
                    ],
                ],
            ]);

        $prescription = Prescription::where('consultation_id', $this->consultation->id)->first();
        $this->assertEquals('draft', $prescription->status);
        $this->assertNull($prescription->finalized_at);

        // Finalize draft
        $this->actingAs($this->doctorUser)
            ->post(route('admin.prescriptions.finalize', $prescription->id))
            ->assertRedirect(route('admin.prescriptions.show', $prescription->id));

        $this->assertEquals('finalized', $prescription->fresh()->status);
        $this->assertNotNull($prescription->fresh()->finalized_at);
    }

    public function test_prescriber_or_pharmacist_can_cancel_unfilled_prescription_with_reason(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-30001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->amoxicillin->id,
            'medication_name' => 'Amoxicillin 500mg',
            'dosage' => '500mg',
            'route' => 'oral',
            'frequency' => 'TDS',
            'duration' => '5 days',
            'quantity_prescribed' => 15,
            'quantity_dispensed' => 0,
            'dispense_status' => 'pending',
        ]);

        // Pharmacist cancels with documented reason
        $this->actingAs($this->pharmacist)
            ->post(route('admin.prescriptions.cancel', $prescription->id), [
                'cancellation_reason' => 'Patient informed pharmacy of severe Penicillin allergy confirmed by family physician',
            ])
            ->assertRedirect(route('admin.prescriptions.show', $prescription->id));

        $prescription->refresh();
        $this->assertEquals('cancelled', $prescription->status);
        $this->assertEquals($this->pharmacist->id, $prescription->cancelled_by);
        $this->assertNotNull($prescription->cancelled_at);

        // Child item marked cancelled
        $item = $prescription->items()->first();
        $this->assertEquals('cancelled', $item->dispense_status);
        $this->assertStringContainsString('Penicillin allergy', $item->non_dispense_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'prescription.cancelled',
            'resource_id' => (string) $prescription->id,
        ]);
    }

    public function test_unauthorized_staff_cannot_view_prescriptions(): void
    {
        $prescription = Prescription::create([
            'prescription_number' => 'RX-TEST-40001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'consultation_id' => $this->consultation->id,
            'prescriber_id' => $this->doctorUser->id,
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        // Receptionist denied
        $this->actingAs($this->receptionist)
            ->get(route('admin.prescriptions.show', $prescription->id))
            ->assertForbidden();

        Auth::guard('web')->forgetUser();

        // Doctor allowed
        $this->actingAs($this->doctorUser)
            ->get(route('admin.prescriptions.show', $prescription->id))
            ->assertOk();

        Auth::guard('web')->forgetUser();

        // Pharmacist allowed
        $this->actingAs($this->pharmacist)
            ->get(route('admin.prescriptions.show', $prescription->id))
            ->assertOk();
    }
}
