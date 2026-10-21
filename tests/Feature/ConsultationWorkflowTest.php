<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TriageRecord;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ConsultationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $doctorUser1;
    protected User $doctorUser2;
    protected User $nurse;
    protected User $receptionist;
    protected Doctor $doctorProfile1;
    protected Doctor $doctorProfile2;
    protected Department $department;
    protected Patient $patient;
    protected Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->department = Department::first();
        $doctors = Doctor::take(2)->get();
        $this->doctorProfile1 = $doctors[0];
        $this->doctorProfile2 = $doctors[1];

        $this->superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.test@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->doctorUser1 = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane.smith@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $this->doctorProfile1->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->doctorUser2 = User::create([
            'name' => 'Dr. Mark Davis',
            'email' => 'mark.davis@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $this->doctorProfile2->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->nurse = User::create([
            'name' => 'Nurse Grace',
            'email' => 'grace.nurse@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::NURSE,
            'department_id' => $this->department->id,
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
            'mrn' => 'MRN-TEST-10001',
            'first_name' => 'Samuel',
            'last_name' => 'Kiptoo',
            'date_of_birth' => '1988-06-15',
            'gender' => 'male',
            'phone' => '+254711999888',
            'allergies' => 'Penicillin hypersensitivity',
            'blood_group' => 'O+',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $this->visit = Visit::create([
            'visit_number' => 'VST-TEST-10001',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctorProfile1->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Sore throat and fever',
            'priority' => 'urgent',
            'status' => 'triaged',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subMinutes(30),
        ]);

        TriageRecord::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'triage_nurse_id' => $this->nurse->id,
            'temperature' => 38.5,
            'systolic_bp' => 130,
            'diastolic_bp' => 85,
            'pulse_rate' => 96,
            'respiratory_rate' => 18,
            'oxygen_saturation' => 98,
            'pain_score' => 4,
            'triage_category' => 'urgent',
            'presenting_complaint' => 'Severe sore throat and fever for 3 days',
            'triaged_at' => now()->subMinutes(25),
        ]);
    }

    public function test_only_doctors_can_access_consultation_queue(): void
    {
        // Doctor can access
        $this->actingAs($this->doctorUser1)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSee('Doctor Consultation Queue')
            ->assertSee('Samuel Kiptoo');

        Auth::guard('web')->forgetUser();

        // Nurse denied
        $this->actingAs($this->nurse)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();

        Auth::guard('web')->forgetUser();

        // Receptionist denied
        $this->actingAs($this->receptionist)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();

        Auth::guard('web')->forgetUser();

        // SuperAdmin denied directly to preserve clinical confidentiality
        $this->actingAs($this->superAdmin)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();
    }

    public function test_doctor_can_start_consultation_and_transition_visit_to_in_consultation(): void
    {
        $response = $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.start', $this->visit->id));

        $consultation = Consultation::where('visit_id', $this->visit->id)->first();
        $this->assertNotNull($consultation);
        $this->assertEquals('draft', $consultation->status);
        $this->assertEquals($this->doctorUser1->id, $consultation->clinician_id);

        $response->assertRedirect(route('admin.consultations.show', $consultation->id));

        $this->assertEquals('in_consultation', $this->visit->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.started',
            'resource_id' => (string) $consultation->id,
        ]);
    }

    public function test_starting_consultation_assigned_to_another_doctor_is_forbidden(): void
    {
        // Visit is assigned to doctorProfile1 (Jane Smith)
        // Dr. Mark Davis attempts to start it
        $this->actingAs($this->doctorUser2)
            ->post(route('admin.consultations.start', $this->visit->id))
            ->assertForbidden();

        $this->assertEquals('triaged', $this->visit->fresh()->status);
        $this->assertDatabaseMissing('consultations', ['visit_id' => $this->visit->id]);
    }

    public function test_doctor_can_save_draft_consultation_notes(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser1)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Severe odynophagia and high grade fever',
                'history_present_illness' => 'Patient reports 3-day history of worsening throat pain with dysphagia.',
                'examination_findings' => 'Bilateral tonsillar hypertrophy with exudates; anterior cervical lymphadenopathy.',
                'assessment' => 'Clinical presentation highly suggestive of acute bacterial pharyngotonsillitis.',
                'diagnosis_type' => 'provisional',
                'primary_diagnosis' => 'Acute Bacterial Tonsillitis',
                'treatment_plan' => 'Initiate oral antimicrobial therapy; analgesia; oral rehydration.',
            ])
            ->assertRedirect(route('admin.consultations.show', $consultation->id))
            ->assertSessionHas('status');

        $consultation->refresh();
        $this->assertEquals('Acute Bacterial Tonsillitis', $consultation->primary_diagnosis);
        $this->assertEquals('draft', $consultation->status);
    }

    public function test_other_doctor_cannot_view_or_edit_draft_consultation_notes(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
            'chief_complaint' => 'Confidential initial draft notes',
        ]);

        // Dr. Mark Davis cannot view Dr. Jane Smith's draft
        $this->actingAs($this->doctorUser2)
            ->get(route('admin.consultations.show', $consultation->id))
            ->assertForbidden();

        // Dr. Mark Davis cannot edit Dr. Jane Smith's draft
        $this->actingAs($this->doctorUser2)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Unauthorized alteration',
            ])
            ->assertForbidden();
    }

    public function test_doctor_can_finalize_and_sign_consultation_locking_record(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.sign', $consultation->id), [
                'chief_complaint' => 'Severe odynophagia and fever',
                'history_present_illness' => '3-day duration of progressive throat pain and difficulty swallowing solids.',
                'examination_findings' => 'Tonsillar erythema and exudates. Temperature 38.3C.',
                'assessment' => 'Confirmed bacterial tonsillitis.',
                'diagnosis_type' => 'confirmed',
                'primary_diagnosis' => 'Acute Tonsillitis, Bacterial',
                'icd11_code' => 'CA03',
                'treatment_plan' => 'Course of Amoxicillin 500mg TDS for 5 days. Paracetamol 1g PRN.',
                'follow_up_instructions' => 'Return if high fever persists after 48h.',
            ])
            ->assertRedirect(route('admin.consultations.show', $consultation->id))
            ->assertSessionHas('status');

        $consultation->refresh();
        $this->assertEquals('signed', $consultation->status);
        $this->assertNotNull($consultation->signed_at);
        $this->assertEquals($this->doctorUser1->id, $consultation->signed_by);
        $this->assertTrue($consultation->isSigned());

        // Linked visit marked completed
        $this->assertEquals('completed', $this->visit->fresh()->status);
        $this->assertNotNull($this->visit->fresh()->completed_at);

        // Record is locked against standard draft update
        $this->actingAs($this->doctorUser1)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Attempted post-signature overwrite',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.signed',
            'resource_id' => (string) $consultation->id,
        ]);
    }

    public function test_signing_endpoint_strictly_enforces_post_and_rejects_put_with_405(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
        ]);

        $payload = [
            'chief_complaint' => 'Severe odynophagia and fever',
            'history_present_illness' => '3-day duration of progressive throat pain and difficulty swallowing solids.',
            'examination_findings' => 'Tonsillar erythema and exudates. Temperature 38.3C.',
            'assessment' => 'Confirmed bacterial tonsillitis.',
            'diagnosis_type' => 'confirmed',
            'primary_diagnosis' => 'Acute Tonsillitis, Bacterial',
            'icd11_code' => 'CA03',
            'treatment_plan' => 'Course of Amoxicillin 500mg TDS for 5 days. Paracetamol 1g PRN.',
            'follow_up_instructions' => 'Return if high fever persists after 48h.',
        ];

        // 1. Direct PUT signing must be rejected with HTTP 405 Method Not Allowed
        $this->actingAs($this->doctorUser1)
            ->put(route('admin.consultations.sign', $consultation->id), $payload)
            ->assertStatus(405);

        $this->assertEquals('draft', $consultation->fresh()->status);

        // 2. POST carrying _method=PUT must be intercepted by Laravel and rejected with HTTP 405
        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.sign', $consultation->id), array_merge($payload, ['_method' => 'PUT']))
            ->assertStatus(405);

        $this->assertEquals('draft', $consultation->fresh()->status);

        // 3. Normal clean POST request succeeds
        $response = $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.sign', $consultation->id), $payload);

        $response->assertRedirect(route('admin.consultations.show', $consultation->id));
        $response->assertSessionHas('status');

        $consultation->refresh();
        $this->assertEquals('signed', $consultation->status);
        $this->assertTrue($consultation->isSigned());

        // 4. Attempting to sign an already signed consultation must be rejected with 403 Forbidden
        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.sign', $consultation->id), $payload)
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_sign_consultation(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
        ]);

        $payload = [
            'chief_complaint' => 'Severe odynophagia and fever',
            'history_present_illness' => '3-day duration of progressive throat pain.',
            'examination_findings' => 'Tonsillar erythema.',
            'assessment' => 'Tonsillitis.',
            'diagnosis_type' => 'confirmed',
            'primary_diagnosis' => 'Tonsillitis',
            'treatment_plan' => 'Antibiotics',
        ];

        // Another doctor cannot sign Dr. Jane Smith's consultation
        $this->actingAs($this->doctorUser2)
            ->post(route('admin.consultations.sign', $consultation->id), $payload)
            ->assertForbidden();

        // Non-doctor (nurse) cannot sign
        $this->actingAs($this->nurse)
            ->post(route('admin.consultations.sign', $consultation->id), $payload)
            ->assertForbidden();

        $this->assertEquals('draft', $consultation->fresh()->status);
    }

    public function test_doctor_can_append_post_signature_clinical_addendum(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => $this->doctorUser1->id,
            'chief_complaint' => 'Fever and cough',
            'history_present_illness' => 'Illness narrative',
            'examination_findings' => 'Normal chest',
            'assessment' => 'Viral URTI',
            'diagnosis_type' => 'provisional',
            'primary_diagnosis' => 'Viral URTI',
            'treatment_plan' => 'Hydration and rest',
        ]);

        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.addenda.store', $consultation->id), [
                'reason_for_amendment' => 'Throat swab culture results reviewed',
                'addendum_notes' => 'Throat swab culture grew Group A Streptococcus. Patient contacted to initiate targeted antimicrobial therapy.',
            ])
            ->assertRedirect(route('admin.consultations.show', $consultation->id))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('consultation_addenda', [
            'consultation_id' => $consultation->id,
            'author_id' => $this->doctorUser1->id,
            'reason_for_amendment' => 'Throat swab culture results reviewed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.addendum_created',
        ]);
    }

    public function test_other_doctor_can_view_signed_consultation_and_generates_cross_clinician_audit(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => $this->doctorUser1->id,
            'chief_complaint' => 'Signed clinical notes',
            'history_present_illness' => 'Illness description',
            'examination_findings' => 'Findings',
            'assessment' => 'Assessment',
            'diagnosis_type' => 'confirmed',
            'primary_diagnosis' => 'Acute Pharyngitis',
            'treatment_plan' => 'Therapy',
        ]);

        // Dr. Mark Davis views Dr. Jane Smith's finalized note
        $this->actingAs($this->doctorUser2)
            ->get(route('admin.consultations.show', $consultation->id))
            ->assertOk()
            ->assertSee('Signed clinical notes')
            ->assertSee('Acute Pharyngitis');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.cross_clinician_view',
            'resource_id' => (string) $consultation->id,
        ]);
    }
}
