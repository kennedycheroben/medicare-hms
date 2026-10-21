<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TriageRecord;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationAndTriageTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $receptionist;
    protected User $nurse;
    protected User $doctorUser;
    protected Department $department;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->department = Department::first();
        $this->doctor = Doctor::first();

        $this->superAdmin = User::create([
            'name' => 'System SuperAdmin',
            'email' => 'admin@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Front Desk Sarah',
            'email' => 'sarah.reception@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->nurse = User::create([
            'name' => 'Nurse Jackie',
            'email' => 'jackie.nurse@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::NURSE,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'Dr. James Wilson',
            'email' => 'wilson.doctor@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'is_active' => true,
        ]);
    }

    public function test_patient_directory_is_accessible_to_authorized_roles(): void
    {
        $responseReceptionist = $this->actingAs($this->receptionist)->get('/admin/patients');
        $responseReceptionist->assertStatus(200);
        $responseReceptionist->assertSee('Patient Master Index');

        $responseNurse = $this->actingAs($this->nurse)->get('/admin/patients');
        $responseNurse->assertStatus(200);

        $responseDoctor = $this->actingAs($this->doctorUser)->get('/admin/patients');
        $responseDoctor->assertStatus(200);

        \Illuminate\Support\Facades\Auth::guard('web')->forgetUser();
        $responseGuest = $this->get('/admin/patients');
        $responseGuest->assertRedirect('/admin/login');
    }

    public function test_unique_mrn_format_and_uniqueness(): void
    {
        $mrn1 = Patient::generateUniqueMrn();
        $mrn2 = Patient::generateUniqueMrn();

        $year = date('Y');
        $this->assertMatchesRegularExpression("/^MRN-{$year}-[A-Z0-9]{6}$/", $mrn1);
        $this->assertMatchesRegularExpression("/^MRN-{$year}-[A-Z0-9]{6}$/", $mrn2);
        $this->assertNotEquals($mrn1, $mrn2);
    }

    public function test_patient_registration_validation_rules(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->from('/admin/patients/create')
            ->post('/admin/patients', [
                // missing required first_name, last_name, date_of_birth, gender, phone
            ]);

        $response->assertRedirect('/admin/patients/create');
        $response->assertSessionHasErrors(['first_name', 'last_name', 'date_of_birth', 'gender', 'phone']);
    }

    public function test_receptionist_can_register_new_patient_transactionally(): void
    {
        $response = $this->actingAs($this->receptionist)->post('/admin/patients', [
            'first_name' => 'Alice',
            'last_name' => 'Wambui',
            'date_of_birth' => '1992-05-14',
            'gender' => 'female',
            'phone' => '+254711223344',
            'email' => 'alice.wambui@example.com',
            'national_id' => '28934501',
            'address' => '124 Ngong Road, Kilimani',
            'city' => 'Nairobi',
            'blood_group' => 'O+',
            'allergies' => 'Penicillin, Peanuts',
            'emergency_contact_name' => 'John Wambui',
            'emergency_contact_phone' => '+254722334455',
            'emergency_contact_relationship' => 'Spouse',
        ]);

        $patient = Patient::where('phone', '+254711223344')->first();
        $this->assertNotNull($patient);
        $this->assertEquals('Alice', $patient->first_name);
        $this->assertEquals('Wambui', $patient->last_name);
        $this->assertEquals('Alice Wambui', $patient->full_name);
        $this->assertNotNull($patient->mrn);
        $this->assertEquals($this->receptionist->id, $patient->registered_by);

        $response->assertRedirect('/admin/patients/' . $patient->id);
        $response->assertSessionHas('status');

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->receptionist->id,
            'action' => 'patient.registered',
            'outcome' => 'success',
        ]);
    }

    public function test_duplicate_patient_detection_and_explicit_override(): void
    {
        // Create initial patient
        Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Robert',
            'last_name' => 'Ochieng',
            'date_of_birth' => '1985-11-20',
            'gender' => 'male',
            'phone' => '+254733445566',
            'national_id' => '20192834',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        // Attempting to register another patient with the exact same phone and name without override
        $attemptResponse = $this->actingAs($this->receptionist)
            ->post('/admin/patients', [
                'first_name' => 'Robert',
                'last_name' => 'Ochieng',
                'date_of_birth' => '1985-11-20',
                'gender' => 'male',
                'phone' => '+254733445566',
                // force_duplicate_override not set
            ]);

        // Form renders duplicate warning view
        $attemptResponse->assertStatus(200);
        $attemptResponse->assertSee('Potential Duplicate Patient Records Found');
        $attemptResponse->assertSee('confirm this patient is distinct');

        // Verify second patient was NOT created yet
        $this->assertEquals(1, Patient::where('phone', '+254733445566')->count());

        // Now register WITH explicit force_duplicate_override
        $overrideResponse = $this->actingAs($this->receptionist)
            ->post('/admin/patients', [
                'first_name' => 'Robert',
                'last_name' => 'Ochieng',
                'date_of_birth' => '1985-11-20',
                'gender' => 'male',
                'phone' => '+254733445566',
                'force_duplicate_override' => '1',
            ]);

        $this->assertEquals(2, Patient::where('phone', '+254733445566')->count());
        $overrideResponse->assertSessionHas('status');

        // Verify duplicate override audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'patient.duplicate_overridden',
            'outcome' => 'success',
        ]);
    }

    public function test_patient_profile_renders_demographic_summary(): void
    {
        $patient = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Grace',
            'last_name' => 'Mutua',
            'date_of_birth' => Carbon::now()->subYears(30)->format('Y-m-d'),
            'gender' => 'female',
            'phone' => '+254799887766',
            'allergies' => 'Sulfa drugs',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $response = $this->actingAs($this->receptionist)->get('/admin/patients/' . $patient->id);

        $response->assertStatus(200);
        $response->assertSee($patient->mrn);
        $response->assertSee('Grace Mutua');
        $response->assertSee('Sulfa drugs');
        $response->assertSee('30 years');
    }

    public function test_receptionist_can_check_in_walk_in_patient(): void
    {
        $patient = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'David',
            'last_name' => 'Kiprono',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'phone' => '+254755112233',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $response = $this->actingAs($this->receptionist)->post('/admin/intake/walk-in', [
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'priority' => 'standard',
            'reason_for_visit' => 'Acute severe headache for 2 days',
        ]);

        $response->assertRedirect('/admin/intake');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'walk_in',
            'priority' => 'standard',
            'status' => 'waiting_triage',
            'reason_for_visit' => 'Acute severe headache for 2 days',
            'checked_in_by' => $this->receptionist->id,
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->receptionist->id,
            'action' => 'visit.walk_in',
            'outcome' => 'success',
        ]);
    }

    public function test_duplicate_check_in_prevention_on_same_day(): void
    {
        $patient = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Mary',
            'last_name' => 'Njeri',
            'date_of_birth' => '1988-08-08',
            'gender' => 'female',
            'phone' => '+254711990088',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        // First check in
        $this->actingAs($this->receptionist)->post('/admin/intake/walk-in', [
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'priority' => 'standard',
            'reason_for_visit' => 'Routine follow-up',
        ]);

        $this->assertEquals(1, Visit::where('patient_id', $patient->id)->count());

        // Attempt second check in while first visit is still active today
        $response = $this->actingAs($this->receptionist)->post('/admin/intake/walk-in', [
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'priority' => 'standard',
            'reason_for_visit' => 'Second attempt same day',
        ]);

        $response->assertSessionHasErrors(['patient_id']);
        $this->assertEquals(1, Visit::where('patient_id', $patient->id)->count());
    }

    public function test_receptionist_can_reconcile_and_check_in_public_appointment(): void
    {
        $patient = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Samuel',
            'last_name' => 'Mwangi',
            'date_of_birth' => '1979-03-21',
            'gender' => 'male',
            'phone' => '+254712345678',
            'email' => 'samuel.mwangi@example.com',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        // Public appointment submitted without patient account
        $appointment = Appointment::create([
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'patient_name' => 'Samuel Mwangi',
            'patient_email' => 'samuel.mwangi@example.com',
            'patient_phone' => '+254712345678',
            'appointment_date' => Carbon::today()->format('Y-m-d'),
            'appointment_time' => '10:00:00',
            'symptoms' => 'Consultation for recurring knee pain',
            'status' => 'pending',
            'reference_code' => 'APT-TEST-001',
        ]);

        $this->assertNull($appointment->patient_id);

        // Reconcile and check in appointment at front desk
        $response = $this->actingAs($this->receptionist)->post('/admin/intake/check-in', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'priority' => 'standard',
        ]);

        $response->assertRedirect('/admin/intake');
        $response->assertSessionHas('status');

        $appointment->refresh();
        $this->assertEquals($patient->id, $appointment->patient_id);
        $this->assertEquals('confirmed', $appointment->status);

        $this->assertDatabaseHas('visits', [
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'visit_type' => 'appointment',
            'status' => 'waiting_triage',
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->receptionist->id,
            'action' => 'visit.checked_in',
            'outcome' => 'success',
        ]);
    }

    public function test_nursing_triage_queue_and_emergency_escalation_visibility(): void
    {
        $patientNormal = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1995-02-10',
            'gender' => 'male',
            'phone' => '+254700000001',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $patientEmergency = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Emergency',
            'last_name' => 'Patient',
            'date_of_birth' => '1980-04-05',
            'gender' => 'female',
            'phone' => '+254700000002',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        Visit::create([
            'visit_number' => Visit::generateUniqueVisitNumber(),
            'patient_id' => $patientNormal->id,
            'department_id' => $this->department->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Mild rash',
            'priority' => 'standard',
            'status' => 'waiting_triage',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => Carbon::now()->subMinutes(30),
        ]);

        Visit::create([
            'visit_number' => Visit::generateUniqueVisitNumber(),
            'patient_id' => $patientEmergency->id,
            'department_id' => $this->department->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Chest pain with shortness of breath',
            'priority' => 'emergency',
            'status' => 'waiting_triage',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->nurse)->get('/admin/triage');
        $response->assertStatus(200);
        $response->assertSee('Active Triage Queue');
        $response->assertSee('Emergency Cases');
        $response->assertSee('Chest pain with shortness of breath');
        $response->assertSee('Mild rash');
    }

    public function test_nurse_can_record_triage_vital_signs_and_category(): void
    {
        $patient = Patient::create([
            'mrn' => Patient::generateUniqueMrn(),
            'first_name' => 'Esther',
            'last_name' => 'Akinyi',
            'date_of_birth' => '1988-12-12',
            'gender' => 'female',
            'phone' => '+254711335577',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $visit = Visit::create([
            'visit_number' => Visit::generateUniqueVisitNumber(),
            'patient_id' => $patient->id,
            'department_id' => $this->department->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Persistent high fever and chills',
            'priority' => 'urgent',
            'status' => 'waiting_triage',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => Carbon::now(),
        ]);

        // Submit triage assessment
        $response = $this->actingAs($this->nurse)->post('/admin/triage/' . $visit->id . '/assess', [
            'systolic_bp' => 125,
            'diastolic_bp' => 82,
            'pulse_rate' => 98,
            'respiratory_rate' => 18,
            'temperature' => 38.6,
            'oxygen_saturation' => 97,
            'pain_score' => 4,
            'weight_kg' => 65.0,
            'height_cm' => 168.0,
            'blood_glucose' => 5.4,
            'triage_category' => 'very_urgent',
            'presenting_complaint' => 'Fever, rigors, body malaise x 3 days',
            'nursing_notes' => 'Patient alert, flushed. Paracetamol 1g given per doctor protocol.',
            'referred_department_id' => $this->department->id,
            'referred_doctor_id' => $this->doctor->id,
        ]);

        $response->assertRedirect('/admin/triage');
        $response->assertSessionHas('status');

        // Check triage record created
        $triage = TriageRecord::where('visit_id', $visit->id)->first();
        $this->assertNotNull($triage);
        $this->assertEquals(125, $triage->systolic_bp);
        $this->assertEquals(82, $triage->diastolic_bp);
        $this->assertEquals('125/82', $triage->blood_pressure);
        $this->assertEquals(38.6, (float)$triage->temperature);
        $this->assertEquals('very_urgent', $triage->triage_category);
        $this->assertEquals($this->nurse->id, $triage->triage_nurse_id);

        // Verify BMI calculation: 65 / (1.68^2) = 23.03 -> rounded to 23.0
        $this->assertEquals(23.0, (float)$triage->bmi);

        // Verify visit status updated
        $visit->refresh();
        $this->assertEquals('triaged', $visit->status);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->nurse->id,
            'action' => 'triage.recorded',
            'outcome' => 'success',
        ]);
    }
}
