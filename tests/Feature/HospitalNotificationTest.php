<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $receptionist;

    protected User $doctorUser;

    protected User $pharmacist;

    protected Doctor $doctor;

    protected Department $department;

    protected Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::firstOrCreate(
            ['slug' => 'cardiology'],
            [
                'name' => 'Cardiology',
                'summary' => 'Comprehensive cardiac care.',
                'description' => 'Clinical cardiology services.',
                'services' => ['Echocardiogram'],
                'is_active' => true,
            ]
        );

        $this->doctor = Doctor::firstOrCreate(
            ['slug' => 'dr-patrick-omondi'],
            [
                'department_id' => $this->department->id,
                'name' => 'Dr. Patrick Omondi',
                'specialty' => 'Cardiologist',
                'qualifications' => 'MBChB, MMed',
                'experience_years' => 12,
                'bio' => 'Senior cardiologist.',
                'is_available' => true,
                'is_published' => true,
                'status' => 'approved',
            ]
        );

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'admin-notif@medicare.local'],
            [
                'name' => 'Admin Boss',
                'password' => bcrypt('password'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $this->receptionist = User::firstOrCreate(
            ['email' => 'mercy-notif@medicare.local'],
            [
                'name' => 'Receptionist Mercy',
                'password' => bcrypt('password'),
                'role' => UserRole::RECEPTIONIST,
                'is_active' => true,
            ]
        );

        $this->doctorUser = User::firstOrCreate(
            ['email' => 'patrick-notif@medicare.local'],
            [
                'name' => 'Dr. Patrick Omondi',
                'password' => bcrypt('password'),
                'role' => UserRole::DOCTOR,
                'is_active' => true,
                'doctor_id' => $this->doctor->id,
                'department_id' => $this->department->id,
            ]
        );

        $this->pharmacist = User::firstOrCreate(
            ['email' => 'paul-notif@medicare.local'],
            [
                'name' => 'Pharm. Paul',
                'password' => bcrypt('password'),
                'role' => UserRole::PHARMACIST,
                'is_active' => true,
            ]
        );

        $this->medicine = Medicine::firstOrCreate(
            ['code' => 'MED-CARD-001'],
            [
                'brand_name' => 'Amlodipine 5mg',
                'generic_name' => 'Amlodipine Besylate',
                'category' => 'Cardiovascular',
                'dosage_form' => 'Tablet',
                'strength' => '5mg',
                'unit_of_measure' => 'tablets',
                'reorder_level' => 50,
                'is_active' => true,
            ]
        );
    }

    public function test_appointment_booking_notifies_receptionist_and_super_admin_not_pharmacist(): void
    {
        $refCode = 'MC-'.uniqid();
        $appointment = Appointment::create([
            'reference_code' => $refCode,
            'patient_name' => 'Patient Onyango',
            'patient_email' => 'onyango@example.com',
            'patient_phone' => '+254712345678',
            'department_id' => $this->department->id,
            'appointment_date' => '2026-11-05',
            'appointment_time' => '10:00:00',
            'status' => 'pending',
        ]);

        NotificationService::notifyAppointmentCreated($appointment);

        // Receptionist receives notification
        $this->assertCount(1, $this->receptionist->notifications);
        $this->assertEquals('appointment', $this->receptionist->notifications->first()->data['category']);
        $this->assertStringContainsString($refCode, $this->receptionist->notifications->first()->data['title']);

        // Super Admin receives notification for oversight
        $this->assertCount(1, $this->superAdmin->notifications);

        // Pharmacist should NOT receive appointment booking notifications
        $this->assertCount(0, $this->pharmacist->notifications);
    }

    public function test_doctor_profile_review_notifications_target_correct_recipients(): void
    {
        // 1. Doctor submits profile -> Admin notified
        NotificationService::notifyDoctorProfileSubmitted($this->doctor);
        $this->assertCount(1, $this->superAdmin->notifications);
        $this->assertEquals('doctor_profile.submitted', $this->superAdmin->notifications->first()->data['event_type']);
        $this->assertCount(0, $this->doctorUser->notifications);

        // 2. Admin approves profile -> Specific Doctor notified
        NotificationService::notifyDoctorProfileApproved($this->doctor);
        $this->doctorUser->refresh();
        $this->assertCount(1, $this->doctorUser->notifications);
        $this->assertEquals('doctor_profile.approved', $this->doctorUser->notifications->first()->data['event_type']);

        // 3. Admin returns profile -> Specific Doctor notified with feedback
        NotificationService::notifyDoctorProfileReturned($this->doctor, 'Please attach valid registration ID.');
        $this->doctorUser->refresh();
        $this->assertCount(2, $this->doctorUser->notifications);
        $returnedNotice = $this->doctorUser->notifications->firstWhere('data.event_type', 'doctor_profile.returned');
        $this->assertNotNull($returnedNotice);
        $this->assertStringContainsString('valid registration ID', $returnedNotice->data['message']);
    }

    public function test_pharmacy_low_stock_notification_and_24h_deduplication(): void
    {
        // First low stock event dispatches notification
        $dispatched = NotificationService::notifyPharmacyLowStock($this->medicine, 20, 50);
        $this->assertTrue($dispatched);

        // Pharmacist and SuperAdmin receive notification
        $this->assertCount(1, $this->pharmacist->notifications);
        $this->assertEquals('pharmacy.low_stock', $this->pharmacist->notifications->first()->data['event_type']);
        $this->assertCount(1, $this->superAdmin->notifications);

        // Doctor should NOT receive pharmacy stock alerts
        $this->assertCount(0, $this->doctorUser->notifications);

        // Second call within 24 hours is deduplicated and returns false without adding new records
        $secondAttempt = NotificationService::notifyPharmacyLowStock($this->medicine, 18, 50);
        $this->assertFalse($secondAttempt);

        $this->pharmacist->refresh();
        $this->assertCount(1, $this->pharmacist->notifications);
    }

    public function test_contact_enquiry_notifies_reception_and_super_admin(): void
    {
        $contact = ContactInquiry::create([
            'ticket_number' => 'INQ-'.uniqid(),
            'name' => 'Wanjiku Kamau',
            'email' => 'wanjiku@example.com',
            'subject' => 'Maternity Ward Tour',
            'message' => 'Inquiry regarding prenatal consultation packages.',
            'status' => 'new',
        ]);

        NotificationService::notifyContactEnquiry($contact);

        $this->assertCount(1, $this->receptionist->notifications);
        $this->assertEquals('contact.enquiry', $this->receptionist->notifications->first()->data['event_type']);
        $this->assertCount(1, $this->superAdmin->notifications);
    }

    public function test_staff_can_view_inbox_and_mark_own_notification_read(): void
    {
        $inboxCode = 'MC-INBOX-'.uniqid();
        $appointment = Appointment::create([
            'reference_code' => $inboxCode,
            'patient_name' => 'Test Patient',
            'patient_email' => 'patient.inbox@example.com',
            'patient_phone' => '+254712345678',
            'appointment_date' => '2026-11-10',
            'appointment_time' => '10:00:00',
            'status' => 'pending',
        ]);

        NotificationService::notifyAppointmentCreated($appointment);

        // Receptionist opens notifications inbox
        $response = $this->actingAs($this->receptionist)
            ->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Notification Inbox');
        $response->assertSee($inboxCode);

        $notificationId = $this->receptionist->notifications->first()->id;

        // Mark as read
        $markResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.notifications.read', $notificationId));

        $markResponse->assertRedirect();
        $this->assertCount(0, $this->receptionist->fresh()->unreadNotifications);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $appointment = Appointment::create([
            'reference_code' => 'MC-ISOLATE-'.uniqid(),
            'patient_name' => 'Test Patient',
            'patient_email' => 'patient.isolate@example.com',
            'patient_phone' => '+254712345678',
            'appointment_date' => '2026-11-10',
            'appointment_time' => '10:00:00',
            'status' => 'pending',
        ]);

        NotificationService::notifyAppointmentCreated($appointment);

        $superAdminNoticeId = $this->superAdmin->notifications->first()->id;

        // Receptionist attempts to mark Super Admin's notification as read
        $response = $this->actingAs($this->receptionist)
            ->post(route('admin.notifications.read', $superAdminNoticeId));

        $response->assertNotFound();
    }

    public function test_super_admin_can_access_oversight_and_non_admin_is_forbidden(): void
    {
        // Super admin can view oversight
        $adminResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.notifications.oversight'));

        $adminResponse->assertOk();
        $adminResponse->assertSee('Hospital Notification Oversight');

        // Receptionist cannot access oversight
        $receptionistResponse = $this->actingAs($this->receptionist)
            ->get(route('admin.notifications.oversight'));

        $receptionistResponse->assertForbidden();

        // Doctor cannot access oversight
        $doctorResponse = $this->actingAs($this->doctorUser)
            ->get(route('admin.notifications.oversight'));

        $doctorResponse->assertForbidden();
    }
}
