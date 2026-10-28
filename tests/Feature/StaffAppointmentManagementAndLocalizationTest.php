<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Rules\ValidPhoneNumber;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAppointmentManagementAndLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $receptionist;

    protected User $doctorUser;

    protected Department $cardiology;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->cardiology = Department::where('slug', 'cardiology')->first() ?? Department::first();
        $this->doctor = Doctor::first();

        $this->superAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.apt@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Front Desk Faith',
            'email' => 'faith.rec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'Dr. Jane Specialist',
            'email' => 'jane.doc@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $this->doctor->id,
            'department_id' => $this->cardiology->id,
            'is_active' => true,
        ]);
    }

    public function test_complete_appointment_lifecycle_from_public_booking_to_triage(): void
    {
        // 1. Public booking with valid Kenyan telephone number
        $bookingData = [
            'patient_name' => 'Kipchoge Keino',
            'patient_email' => 'kipchoge@example.co.ke',
            'patient_phone' => '0722123456', // Local Kenyan format
            'department_id' => $this->cardiology->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '10:00 AM - 11:00 AM',
        ];

        $postResponse = $this->post(route('appointments.store'), $bookingData);
        $postResponse->assertRedirect();

        $appointment = Appointment::where('patient_email', 'kipchoge@example.co.ke')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals('+254722123456', $appointment->patient_phone); // Normalized to +254

        // 2. Staff views appointment in incoming queue
        $listResponse = $this->actingAs($this->receptionist)
            ->get(route('admin.appointments.index', ['status' => 'pending']));
        $listResponse->assertOk();
        $listResponse->assertSee($appointment->reference_code);
        $listResponse->assertSee('Kipchoge Keino');

        // 3. Staff reviews detailed appointment screen
        $detailResponse = $this->actingAs($this->receptionist)
            ->get(route('admin.appointments.show', $appointment->id));
        $detailResponse->assertOk();
        $detailResponse->assertSee($appointment->reference_code);
        $detailResponse->assertSee('Unreconciled Online Booking');

        // 4. Staff confirms the appointment
        $confirmResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.confirm', $appointment->id), [
                'department_id' => $this->cardiology->id,
                'doctor_id' => $this->doctor->id,
                'triage_notes' => 'Patient contacted via telephone and slot verified.',
            ]);
        $confirmResponse->assertRedirect(route('admin.appointments.show', $appointment->id));

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertNotNull($appointment->confirmed_at);
        $this->assertEquals($this->receptionist->id, $appointment->confirmed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'appointment.confirmed',
            'resource_id' => (string) $appointment->id,
        ]);

        // 5. Staff reschedules appointment with mandatory reason
        $newDate = now()->addDays(5)->format('Y-m-d');
        $rescheduleResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.reschedule', $appointment->id), [
                'appointment_date' => $newDate,
                'appointment_time' => '02:00 PM - 03:00 PM',
                'reschedule_reason' => 'Patient requested afternoon appointment due to travel schedule.',
            ]);
        $rescheduleResponse->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('rescheduled', $appointment->status);
        $this->assertEquals($newDate, $appointment->appointment_date->format('Y-m-d'));
        $this->assertEquals('02:00 PM - 03:00 PM', $appointment->appointment_time);
        $this->assertEquals('Patient requested afternoon appointment due to travel schedule.', $appointment->reschedule_reason);

        // 6. Reconcile appointment with registered patient
        $patient = Patient::create([
            'mrn' => 'MRN-2026-KEN999',
            'first_name' => 'Kipchoge',
            'last_name' => 'Keino',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male',
            'phone' => '+254722123456',
            'email' => 'kipchoge@example.co.ke',
            'city' => 'Nairobi',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $reconcileResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.reconcile', $appointment->id), [
                'patient_id' => $patient->id,
            ]);
        $reconcileResponse->assertRedirect();

        $appointment->refresh();
        $this->assertEquals($patient->id, $appointment->patient_id);

        // 7. Check-in patient directly from appointment -> generates Visit in Triage Queue
        $checkInResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.check-in', $appointment->id), [
                'patient_id' => $patient->id,
                'priority' => 'standard',
                'reason_for_visit' => 'Follow up on routine cardiac check',
            ]);
        $checkInResponse->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
        $this->assertTrue($appointment->visit()->exists());

        $visit = $appointment->visit;
        $this->assertNotNull($visit);
        $this->assertEquals('waiting_triage', $visit->status);
        $this->assertEquals($patient->id, $visit->patient_id);
        $this->assertEquals('appointment', $visit->visit_type);

        // 8. Attempting duplicate check-in today for same active patient is blocked
        $secondAppointment = Appointment::create([
            'reference_code' => 'MC-20261022-SEC99',
            'patient_id' => $patient->id,
            'patient_name' => 'Kipchoge Keino',
            'patient_email' => 'kipchoge@example.co.ke',
            'patient_phone' => '+254722123456',
            'department_id' => $this->cardiology->id,
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '04:00 PM - 05:00 PM',
            'status' => 'confirmed',
        ]);

        $duplicateCheckInResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.check-in', $secondAppointment->id), [
                'patient_id' => $patient->id,
                'priority' => 'standard',
            ]);
        $duplicateCheckInResponse->assertSessionHasErrors(['patient_id']);
    }

    public function test_staff_can_cancel_appointment_with_mandatory_reason_and_audit(): void
    {
        $appointment = Appointment::create([
            'reference_code' => 'MC-20261022-CAN01',
            'patient_name' => 'Grace Wanjiku',
            'patient_email' => 'grace@example.co.ke',
            'patient_phone' => '+254711223344',
            'department_id' => $this->cardiology->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d'),
            'appointment_time' => '11:00 AM - 12:00 PM',
            'status' => 'pending',
        ]);

        // Attempt without cancellation reason fails validation
        $failResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.cancel', $appointment->id), [
                'cancellation_reason' => 'bad', // less than min:5
            ]);
        $failResponse->assertSessionHasErrors(['cancellation_reason']);

        // Success cancellation
        $successResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.cancel', $appointment->id), [
                'cancellation_reason' => 'Patient called to cancel due to relocation outside Nairobi.',
            ]);
        $successResponse->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('Patient called to cancel due to relocation outside Nairobi.', $appointment->cancellation_reason);
        $this->assertNotNull($appointment->cancelled_at);

        // Cancelled appointment cannot transition to confirmed
        $invalidTransition = $this->actingAs($this->receptionist)
            ->post(route('admin.appointments.confirm', $appointment->id), []);
        $invalidTransition->assertSessionHasErrors(['status']);
    }

    public function test_kenyan_phone_validation_rule(): void
    {
        $rule = new ValidPhoneNumber;

        // Valid Kenyan mobile numbers
        $validNumbers = [
            '+254712345678',
            '0712345678',
            '0722000000',
            '0110123456', // 01X Safaricom/Airtel prefix
            '+254110123456',
            '254712345678',
            '0207900000', // Kenyan landline
            '+254207900000',
            '+14155552671', // Valid international E.164 with country code
            '+442071838750', // Valid UK number
        ];

        foreach ($validNumbers as $num) {
            $failed = false;
            $rule->validate('phone', $num, function () use (&$failed) {
                $failed = true;
            });
            $this->assertFalse($failed, "Failed for valid number: {$num}");
        }

        // Invalid phone numbers
        $invalidNumbers = [
            '12345',
            'not-a-number',
            '07123', // Too short
            '0712345678999', // Too long for Kenyan local
            '555-010-0200', // Missing international code
            '',
        ];

        foreach ($invalidNumbers as $num) {
            $failed = false;
            $rule->validate('phone', $num, function () use (&$failed) {
                $failed = true;
            });
            $this->assertTrue($failed, "Expected to fail for invalid number: {$num}");
        }

        // Normalization
        $this->assertEquals('+254712345678', ValidPhoneNumber::normalize('0712 345 678'));
        $this->assertEquals('+254112345678', ValidPhoneNumber::normalize('0112-345-678'));
        $this->assertEquals('+254712345678', ValidPhoneNumber::normalize('254712345678'));
        $this->assertEquals('+14155552671', ValidPhoneNumber::normalize('+1 415 555 2671'));
    }

    public function test_kenyan_localization_defaults_are_displayed(): void
    {
        // 1. Config defaults
        $this->assertEquals('Nairobi', config('hospital.city'));
        $this->assertEquals('Kenya', config('hospital.country'));
        $this->assertStringContainsString('+254', config('hospital.phone'));
        $this->assertStringContainsString('+254', config('hospital.emergency_phone'));

        // 2. Public homepage displays Nairobi and Kenyan contact details
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee(config('hospital.phone'));
        if (config('hospital.is_demo')) {
            $homeResponse->assertSee('999 / 112');
        } else {
            $homeResponse->assertSee(config('hospital.emergency_phone'));
        }
        $homeResponse->assertSee('Nairobi, Kenya');

        // 3. Admin initial assessment heading displays updated clinical terminology
        $triageResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.triage.index'));
        $triageResponse->assertOk();
        $triageResponse->assertSee('Initial Assessment & Vital Signs');

        // 4. Admin dashboard displays plain-language role descriptions
        $dashResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Role Profile & Permissions');
        $dashResponse->assertSee('Authorized to View:');
        $dashResponse->assertSee('Authorized to Create & Change:');
        $dashResponse->assertSee('Operational & Clinical Restrictions:');
    }
}
