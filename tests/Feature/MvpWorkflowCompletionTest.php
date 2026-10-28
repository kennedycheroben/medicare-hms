<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\StaffTask;
use App\Models\User;
use App\Notifications\HospitalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MvpWorkflowCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected Department $cardiology;

    protected Department $neurology;

    protected Doctor $doctorCardio;

    protected Doctor $doctorNeuro;

    protected User $superAdmin;

    protected User $receptionist;

    protected User $doctorUserCardio;

    protected User $doctorUserNeuro;

    protected User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cardiology = Department::updateOrCreate(
            ['slug' => 'cardiology'],
            [
                'name' => 'Cardiology',
                'summary' => 'Comprehensive cardiac care',
                'description' => 'Cardiovascular diagnoses and therapies',
                'is_active' => true,
            ]
        );

        $this->neurology = Department::updateOrCreate(
            ['slug' => 'neurology'],
            [
                'name' => 'Neurology',
                'summary' => 'Neurological care',
                'description' => 'Brain and nervous system consultations',
                'is_active' => true,
            ]
        );

        $this->doctorCardio = Doctor::updateOrCreate(
            ['slug' => 'dr-alice-cardiologist'],
            [
                'department_id' => $this->cardiology->id,
                'name' => 'Alice Cardiologist',
                'specialty' => 'Interventional Cardiology',
                'qualifications' => 'MBChB, MMed',
                'bio' => 'Senior interventional cardiologist with 12 years clinical experience.',
                'experience_years' => 12,
                'is_available' => true,
                'status' => 'approved',
                'is_published' => true,
            ]
        );

        $this->doctorNeuro = Doctor::updateOrCreate(
            ['slug' => 'dr-bob-neurologist'],
            [
                'department_id' => $this->neurology->id,
                'name' => 'Bob Neurologist',
                'specialty' => 'Clinical Neurology',
                'qualifications' => 'MBChB, MD',
                'bio' => 'Consultant neurologist specializing in neurovascular care.',
                'experience_years' => 10,
                'is_available' => true,
                'status' => 'approved',
                'is_published' => true,
            ]
        );

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => true,
        ]);

        $this->receptionist = User::factory()->create([
            'role' => UserRole::RECEPTIONIST->value,
            'is_active' => true,
        ]);

        $this->doctorUserCardio = User::factory()->create([
            'role' => UserRole::DOCTOR->value,
            'doctor_id' => $this->doctorCardio->id,
            'department_id' => $this->cardiology->id,
            'is_active' => true,
        ]);

        $this->doctorUserNeuro = User::factory()->create([
            'role' => UserRole::DOCTOR->value,
            'doctor_id' => $this->doctorNeuro->id,
            'department_id' => $this->neurology->id,
            'is_active' => true,
        ]);

        $this->nurse = User::factory()->create([
            'role' => UserRole::NURSE->value,
            'is_active' => true,
        ]);
    }

    public function test_public_departments_page_renders_active_departments_and_detail_route(): void
    {
        $response = $this->get(route('departments'));

        $response->assertOk();
        $response->assertSee('Cardiology');
        $response->assertSee('Neurology');
        $response->assertSee(route('departments.show', 'cardiology'));
    }

    public function test_public_departments_page_empty_state_renders_fallback_banner(): void
    {
        Department::query()->update(['is_active' => false]);

        $response = $this->get(route('departments'));

        $response->assertOk();
        $response->assertSee('Clinical Departments Under Directory Update');
        $response->assertSee('Book General Intake Consultation');
        $response->assertSee(route('appointments.create'));
    }

    public function test_public_appointment_booking_allows_general_intake_without_department_or_doctor(): void
    {
        Notification::fake();

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post(route('appointments.store'), [
            'patient_name' => 'Grace Wanjiru',
            'patient_email' => 'grace@example.com',
            'patient_phone' => '+254712345678',
            'appointment_date' => $tomorrow,
            'appointment_time' => '10:00 AM - 11:00 AM',
            'department_id' => '',
            'doctor_id' => '',
            'symptoms' => 'General health consultation and check-up request.',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'patient_name' => 'Grace Wanjiru',
            'department_id' => null,
            'doctor_id' => null,
            'preferred_doctor_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_public_appointment_booking_preserves_patient_preferred_doctor(): void
    {
        Notification::fake();

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->post(route('appointments.store'), [
            'patient_name' => 'David Kiprono',
            'patient_email' => 'david@example.com',
            'patient_phone' => '+254722334455',
            'appointment_date' => $tomorrow,
            'appointment_time' => '11:00 AM - 12:00 PM',
            'department_id' => $this->cardiology->id,
            'doctor_id' => $this->doctorCardio->id,
            'symptoms' => 'Mild palpitations during cardio exercises.',
            'terms' => '1',
        ]);

        $response->assertRedirect();

        $appointment = Appointment::where('patient_email', 'david@example.com')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->doctorCardio->id, $appointment->preferred_doctor_id);
    }

    public function test_receptionist_can_assign_doctor_and_notifies_assigned_doctor(): void
    {
        Notification::fake();

        $appointment = Appointment::create([
            'reference_code' => 'APT-'.uniqid(),
            'patient_name' => 'James Mutua',
            'patient_email' => 'james@example.com',
            'patient_phone' => '+254799887766',
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '09:00 AM - 10:00 AM',
            'status' => 'pending',
            'department_id' => $this->cardiology->id,
        ]);

        $response = $this->actingAs($this->receptionist)->post(
            route('admin.appointments.assign', $appointment->id),
            [
                'doctor_id' => $this->doctorCardio->id,
                'triage_notes' => 'Patient has cardiac family history, assigned to senior cardiologist.',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $appointment->refresh();
        $this->assertEquals($this->doctorCardio->id, $appointment->doctor_id);
        $this->assertEquals($this->receptionist->id, $appointment->assigned_by);
        $this->assertNotNull($appointment->assigned_at);

        Notification::assertSentTo(
            $this->doctorUserCardio,
            HospitalNotification::class,
            function ($notification) {
                return $notification->eventType === 'appointment.assigned';
            }
        );
    }

    public function test_receptionist_cannot_assign_doctor_with_time_slot_conflict(): void
    {
        Notification::fake();

        $date = now()->addDays(3)->format('Y-m-d');
        $slot = '02:00 PM - 03:00 PM';

        // Existing confirmed appointment for Alice
        Appointment::create([
            'reference_code' => 'APT-EXISTING-1',
            'doctor_id' => $this->doctorCardio->id,
            'patient_name' => 'First Patient',
            'patient_email' => 'first@example.com',
            'patient_phone' => '+254700000001',
            'appointment_date' => $date,
            'appointment_time' => $slot,
            'status' => 'confirmed',
        ]);

        // Second appointment trying to book same doctor, date and slot
        $appointment2 = Appointment::create([
            'reference_code' => 'APT-CONFLICT-2',
            'patient_name' => 'Second Patient',
            'patient_email' => 'second@example.com',
            'patient_phone' => '+254700000002',
            'appointment_date' => $date,
            'appointment_time' => $slot,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->receptionist)->post(
            route('admin.appointments.assign', $appointment2->id),
            [
                'doctor_id' => $this->doctorCardio->id,
                'triage_notes' => 'Attempting double booking',
            ]
        );

        $response->assertSessionHasErrors(['doctor_id']);
        $appointment2->refresh();
        $this->assertNull($appointment2->doctor_id);
    }

    public function test_reassigning_doctor_notifies_both_new_doctor_and_former_doctor(): void
    {
        Notification::fake();

        $appointment = Appointment::create([
            'reference_code' => 'APT-REASSIGN-1',
            'patient_name' => 'Sarah Otieno',
            'patient_email' => 'sarah@example.com',
            'patient_phone' => '+254711223344',
            'appointment_date' => now()->addDays(4)->format('Y-m-d'),
            'appointment_time' => '10:00 AM - 11:00 AM',
            'status' => 'pending',
            'doctor_id' => $this->doctorCardio->id,
            'department_id' => $this->cardiology->id,
        ]);

        $response = $this->actingAs($this->receptionist)->post(
            route('admin.appointments.assign', $appointment->id),
            [
                'doctor_id' => $this->doctorNeuro->id,
                'triage_notes' => 'Patient requested neurological evaluation instead.',
            ]
        );

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals($this->doctorNeuro->id, $appointment->doctor_id);

        // New doctor received assignment notice
        Notification::assertSentTo(
            $this->doctorUserNeuro,
            HospitalNotification::class,
            fn ($n) => $n->eventType === 'appointment.assigned'
        );

        // Former doctor received reassignment notice
        Notification::assertSentTo(
            $this->doctorUserCardio,
            HospitalNotification::class,
            fn ($n) => $n->eventType === 'appointment.reassigned'
        );
    }

    public function test_doctor_cannot_view_appointment_assigned_to_different_doctor_and_department(): void
    {
        $appointment = Appointment::create([
            'reference_code' => 'APT-ISOLATION-1',
            'patient_name' => 'Private Patient',
            'patient_email' => 'private@example.com',
            'patient_phone' => '+254700000099',
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '09:00 AM - 10:00 AM',
            'status' => 'pending',
            'doctor_id' => $this->doctorNeuro->id,
            'department_id' => $this->neurology->id,
        ]);

        // Dr Alice (Cardiology) attempts to view Dr Bob's (Neurology) appointment
        $response = $this->actingAs($this->doctorUserCardio)->get(
            route('admin.appointments.show', $appointment->id)
        );

        $response->assertForbidden();
    }

    public function test_contact_inquiries_workflow_and_rbac(): void
    {
        $inquiry = ContactInquiry::create([
            'ticket_number' => 'TKT-2026-TEST01',
            'name' => 'Mercy Chebet',
            'email' => 'mercy@example.com',
            'phone' => '+254733445566',
            'department' => 'Billing & Insurance',
            'subject' => 'NHIF Rebate Clarification',
            'message' => 'Kindly clarify if SHA covers minor outpatient procedures at your clinic.',
            'status' => 'new',
        ]);

        // Receptionist can view index and show
        $indexResponse = $this->actingAs($this->receptionist)->get(route('admin.inquiries.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('TKT-2026-TEST01');
        $indexResponse->assertSee('Mercy Chebet');

        $showResponse = $this->actingAs($this->receptionist)->get(route('admin.inquiries.show', $inquiry->id));
        $showResponse->assertOk();
        $showResponse->assertSee('NHIF Rebate Clarification');
        $showResponse->assertSee('Kindly clarify if SHA covers minor outpatient procedures');

        // Receptionist can update status to in_review
        $updateResponse = $this->actingAs($this->receptionist)->post(
            route('admin.inquiries.status', $inquiry->id),
            [
                'status' => 'in_review',
                'notes' => 'Called patient to explain insurance coverage.',
            ]
        );

        $updateResponse->assertRedirect();
        $inquiry->refresh();
        $this->assertEquals('in_review', $inquiry->status);

        // Doctor is unauthorized to access contact inquiries
        $unauthResponse = $this->actingAs($this->doctorUserCardio)->get(route('admin.inquiries.index'));
        $unauthResponse->assertForbidden();
    }

    public function test_staff_tasks_workflow_creation_notification_and_reassignment(): void
    {
        Notification::fake();

        // 1. SuperAdmin creates and delegates task to nurse
        $createResponse = $this->actingAs($this->superAdmin)->post(
            route('admin.tasks.store'),
            [
                'title' => 'Prepare Crash Cart in Triage Room 3',
                'description' => 'Check expiration dates on atropine and adrenaline vials.',
                'priority' => 'urgent',
                'assigned_to' => $this->nurse->id,
                'due_date' => now()->addDay()->format('Y-m-d'),
                'related_type' => 'clinical',
            ]
        );

        $createResponse->assertRedirect();

        $task = StaffTask::where('title', 'Prepare Crash Cart in Triage Room 3')->first();
        $this->assertNotNull($task);
        $this->assertEquals($this->nurse->id, $task->assigned_to);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('urgent', $task->priority);

        // Nurse was notified of the assignment
        Notification::assertSentTo(
            $this->nurse,
            HospitalNotification::class,
            fn ($n) => $n->eventType === 'task.assigned'
        );

        // 2. Nurse views their assigned task
        $nurseViewResponse = $this->actingAs($this->nurse)->get(route('admin.tasks.show', $task->id));
        $nurseViewResponse->assertOk();
        $nurseViewResponse->assertSee('Prepare Crash Cart in Triage Room 3');

        // 3. Nurse marks task as in_progress
        $statusResponse = $this->actingAs($this->nurse)->put(
            route('admin.tasks.update', $task->id),
            ['status' => 'in_progress']
        );
        $statusResponse->assertRedirect();
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);

        // 4. SuperAdmin reassigns task to Dr Alice
        $reassignResponse = $this->actingAs($this->superAdmin)->put(
            route('admin.tasks.update', $task->id),
            [
                'title' => 'Prepare Crash Cart in Triage Room 3',
                'description' => 'Check expiration dates on atropine and adrenaline vials.',
                'priority' => 'high',
                'status' => 'in_progress',
                'assigned_to' => $this->doctorUserCardio->id,
                'due_date' => now()->addDay()->format('Y-m-d'),
            ]
        );
        $reassignResponse->assertRedirect();
        $task->refresh();
        $this->assertEquals($this->doctorUserCardio->id, $task->assigned_to);

        // Doctor received reassignment notice and former assignee (nurse) received update notice
        Notification::assertSentTo(
            $this->doctorUserCardio,
            HospitalNotification::class,
            fn ($n) => $n->eventType === 'task.assigned'
        );

        Notification::assertSentTo(
            $this->nurse,
            HospitalNotification::class,
            fn ($n) => $n->eventType === 'task.reassigned'
        );

        // 5. Unrelated staff member cannot view task
        $otherStaff = User::factory()->create(['role' => UserRole::PHARMACIST->value, 'is_active' => true]);
        $forbiddenResponse = $this->actingAs($otherStaff)->get(route('admin.tasks.show', $task->id));
        $forbiddenResponse->assertForbidden();
    }
}
