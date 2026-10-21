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

    public function test_unlinked_doctor_is_denied_all_clinical_consultation_access_and_fails_closed(): void
    {
        $unlinkedDoctor = User::create([
            'name' => 'Dr. Unlinked Practitioner',
            'email' => 'unlinked.doctor@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => null,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
            'chief_complaint' => 'Draft complaint',
        ]);

        // 1. Consultation queue access denied
        $this->actingAs($unlinkedDoctor)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();

        // 2. Starting visit denied
        $this->actingAs($unlinkedDoctor)
            ->post(route('admin.consultations.start', $this->visit->id))
            ->assertForbidden();

        // 3. Viewing draft consultation denied
        $this->actingAs($unlinkedDoctor)
            ->get(route('admin.consultations.show', $consultation->id))
            ->assertForbidden();

        // 4. Updating draft consultation denied
        $this->actingAs($unlinkedDoctor)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Attempted unlinked edit',
            ])
            ->assertForbidden();

        // 5. Signing consultation denied
        $this->actingAs($unlinkedDoctor)
            ->post(route('admin.consultations.sign', $consultation->id), [
                'chief_complaint' => 'Attempted unlinked sign',
                'history_present_illness' => 'Narrative',
                'examination_findings' => 'Findings',
                'assessment' => 'Assessment',
                'diagnosis_type' => 'confirmed',
                'primary_diagnosis' => 'Diagnosis',
                'treatment_plan' => 'Plan',
            ])
            ->assertForbidden();

        // 6. Viewing signed consultation denied for unlinked doctor
        $signedVisit = Visit::create([
            'visit_number' => 'VST-TEST-SIGNED-999',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctorProfile1->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Follow-up clinical assessment',
            'priority' => 'standard',
            'status' => 'completed',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subDay(),
        ]);

        $signedConsultation = Consultation::create([
            'visit_id' => $signedVisit->id,
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

        $this->actingAs($unlinkedDoctor)
            ->get(route('admin.consultations.show', $signedConsultation->id))
            ->assertForbidden();

        // 7. Creating addendum denied for unlinked doctor
        $this->actingAs($unlinkedDoctor)
            ->post(route('admin.consultations.addenda.store', $signedConsultation->id), [
                'reason_for_amendment' => 'Unauthorized amendment attempt',
                'addendum_notes' => 'Unauthorized notes',
            ])
            ->assertForbidden();

        // Verify audit trail captures the fail-closed rejection
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.unlinked_doctor_access_attempt',
        ]);
    }

    public function test_queue_excludes_visits_assigned_to_other_doctors_even_in_same_department(): void
    {
        // Visit 1 assigned to doctorProfile1 (Dr. Jane Smith)
        // Create Visit 2 assigned to doctorProfile2 (Dr. Mark Davis) in SAME department
        $visit2 = Visit::create([
            'visit_number' => 'VST-TEST-10002',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctorProfile2->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Migraine headache',
            'priority' => 'urgent',
            'status' => 'triaged',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subMinutes(15),
        ]);

        // Create Visit 3 unassigned (First Available) in SAME department
        $visit3 = Visit::create([
            'visit_number' => 'VST-TEST-10003',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => null,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'General health check',
            'priority' => 'standard',
            'status' => 'triaged',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subMinutes(10),
        ]);

        // Dr. Jane Smith views her queue
        $response1 = $this->actingAs($this->doctorUser1)
            ->get(route('admin.consultations.index'))
            ->assertOk();

        // Dr. Jane Smith sees her own visit and the unassigned visit
        $response1->assertSee($this->visit->visit_number);
        $response1->assertSee($visit3->visit_number);
        // Dr. Jane Smith MUST NOT see Dr. Mark Davis's assigned visit
        $response1->assertDontSee($visit2->visit_number);

        Auth::guard('web')->forgetUser();

        // Dr. Mark Davis views his queue
        $response2 = $this->actingAs($this->doctorUser2)
            ->get(route('admin.consultations.index'))
            ->assertOk();

        // Dr. Mark Davis sees his own visit and the unassigned visit
        $response2->assertSee($visit2->visit_number);
        $response2->assertSee($visit3->visit_number);
        // Dr. Mark Davis MUST NOT see Dr. Jane Smith's assigned visit
        $response2->assertDontSee($this->visit->visit_number);
    }

    public function test_doctor_cannot_take_over_in_progress_consultation_initiated_by_another_clinician(): void
    {
        // Create an unassigned visit
        $unassignedVisit = Visit::create([
            'visit_number' => 'VST-TEST-UNASSIGNED',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => null,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Acute cough',
            'priority' => 'urgent',
            'status' => 'triaged',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subMinutes(20),
        ]);

        // Dr. Jane Smith starts consultation on this visit
        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.start', $unassignedVisit->id))
            ->assertRedirect();

        $this->assertEquals('in_consultation', $unassignedVisit->fresh()->status);
        $consultation = Consultation::where('visit_id', $unassignedVisit->id)->first();
        $this->assertNotNull($consultation);
        $this->assertEquals($this->doctorUser1->id, $consultation->clinician_id);

        Auth::guard('web')->forgetUser();

        // Dr. Mark Davis attempts to take over and start this same in-progress visit
        $this->actingAs($this->doctorUser2)
            ->post(route('admin.consultations.start', $unassignedVisit->id))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.unauthorized_takeover_attempt',
        ]);
    }

    public function test_direct_url_access_to_unauthorized_draft_is_blocked_and_audited(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'draft',
            'chief_complaint' => 'Highly confidential psychiatric assessment notes',
        ]);

        // Dr. Mark Davis attempts direct URL GET
        $this->actingAs($this->doctorUser2)
            ->get(route('admin.consultations.show', $consultation->id))
            ->assertForbidden();

        // Dr. Mark Davis attempts direct URL PUT
        $this->actingAs($this->doctorUser2)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Tampered text',
            ])
            ->assertForbidden();

        // Dr. Mark Davis attempts direct URL POST sign
        $this->actingAs($this->doctorUser2)
            ->post(route('admin.consultations.sign', $consultation->id), [
                'chief_complaint' => 'Tampered text',
                'history_present_illness' => 'Illness narrative',
                'examination_findings' => 'Findings',
                'assessment' => 'Assessment',
                'diagnosis_type' => 'confirmed',
                'primary_diagnosis' => 'Diagnosis',
                'treatment_plan' => 'Plan',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.unauthorized_draft_access',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.unauthorized_edit_attempt',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'consultation.unauthorized_sign_attempt',
        ]);
    }

    public function test_signed_record_is_immutable_against_author_and_other_doctors(): void
    {
        $consultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile1->id,
            'clinician_id' => $this->doctorUser1->id,
            'status' => 'signed',
            'signed_at' => now()->subDay(),
            'signed_by' => $this->doctorUser1->id,
            'chief_complaint' => 'Original immutable notes',
            'history_present_illness' => 'Original illness history',
            'examination_findings' => 'Original examination',
            'assessment' => 'Original assessment',
            'diagnosis_type' => 'confirmed',
            'primary_diagnosis' => 'Original Primary Diagnosis',
            'treatment_plan' => 'Original plan',
        ]);

        // 1. Author doctor cannot update signed record
        $this->actingAs($this->doctorUser1)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Attempted author modification',
            ])
            ->assertForbidden();

        // 2. Author doctor cannot re-sign signed record
        $this->actingAs($this->doctorUser1)
            ->post(route('admin.consultations.sign', $consultation->id), [
                'chief_complaint' => 'Attempted re-sign',
                'history_present_illness' => 'Illness narrative',
                'examination_findings' => 'Findings',
                'assessment' => 'Assessment',
                'diagnosis_type' => 'confirmed',
                'primary_diagnosis' => 'Diagnosis',
                'treatment_plan' => 'Plan',
            ])
            ->assertForbidden();

        // 3. Another doctor cannot update signed record
        $this->actingAs($this->doctorUser2)
            ->put(route('admin.consultations.update', $consultation->id), [
                'chief_complaint' => 'Attempted other doctor modification',
            ])
            ->assertForbidden();

        // Verify database content was strictly preserved
        $consultation->refresh();
        $this->assertEquals('Original immutable notes', $consultation->chief_complaint);
        $this->assertEquals('Original Primary Diagnosis', $consultation->primary_diagnosis);
        $this->assertEquals('signed', $consultation->status);
    }
}
