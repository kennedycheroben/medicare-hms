<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingWorkstreamTest extends TestCase
{
    use RefreshDatabase;

    protected Department $activeDept1;

    protected Department $activeDept2;

    protected Department $inactiveDept;

    protected Doctor $publishedDoctor1;

    protected Doctor $publishedDoctor2;

    protected Doctor $unavailableDoctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeDept1 = Department::create([
            'name' => 'Cardiology',
            'slug' => 'cardiology',
            'summary' => 'Heart care',
            'description' => 'Heart care',
            'is_active' => true,
        ]);

        $this->activeDept2 = Department::create([
            'name' => 'Neurology',
            'slug' => 'neurology',
            'summary' => 'Brain care',
            'description' => 'Brain care',
            'is_active' => true,
        ]);

        $this->inactiveDept = Department::create([
            'name' => 'Renovation Ward',
            'slug' => 'renovation-ward',
            'summary' => 'Closed',
            'description' => 'Closed',
            'is_active' => false,
        ]);

        $this->publishedDoctor1 = Doctor::create([
            'department_id' => $this->activeDept1->id,
            'name' => 'Dr. Cardiology Specialist',
            'slug' => 'dr-cardiology-specialist',
            'specialty' => 'Cardiologist',
            'qualifications' => 'MD',
            'experience_years' => 10,
            'bio' => 'Cardiologist bio',
            'is_available' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->publishedDoctor2 = Doctor::create([
            'department_id' => $this->activeDept2->id,
            'name' => 'Dr. Neurology Specialist',
            'slug' => 'dr-neurology-specialist',
            'specialty' => 'Neurologist',
            'qualifications' => 'MD',
            'experience_years' => 8,
            'bio' => 'Neurologist bio',
            'is_available' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->unavailableDoctor = Doctor::create([
            'department_id' => $this->activeDept1->id,
            'name' => 'Dr. On Leave Physician',
            'slug' => 'dr-on-leave-physician',
            'specialty' => 'Cardiologist',
            'qualifications' => 'MD',
            'experience_years' => 5,
            'bio' => 'On leave',
            'is_available' => false,
            'is_published' => true,
        ]);
    }

    public function test_scenario_1_booking_with_valid_active_department_succeeds(): void
    {
        $payload = [
            'patient_name' => 'Wanjiku Kamau',
            'patient_email' => 'wanjiku.kamau@example.co.ke',
            'patient_phone' => '+254712345678',
            'department_id' => $this->activeDept1->id,
            'doctor_id' => null,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '10:00 AM - 11:00 AM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_name' => 'Wanjiku Kamau',
            'department_id' => $this->activeDept1->id,
            'doctor_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_scenario_2_booking_with_doctor_auto_infers_department(): void
    {
        $payload = [
            'patient_name' => 'David Omondi',
            'patient_email' => 'david.omondi@example.co.ke',
            'patient_phone' => '0722000111',
            'department_id' => null, // Left empty by patient
            'doctor_id' => $this->publishedDoctor1->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d'),
            'appointment_time' => '02:00 PM - 03:00 PM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_name' => 'David Omondi',
            'department_id' => $this->activeDept1->id, // Inferred from doctor
            'doctor_id' => $this->publishedDoctor1->id,
            'status' => 'pending',
        ]);
    }

    public function test_scenario_3_general_intake_booking_without_department_or_doctor_succeeds(): void
    {
        $payload = [
            'patient_name' => 'Fatuma Hassan',
            'patient_email' => 'fatuma.hassan@example.co.ke',
            'patient_phone' => '+254733445566',
            'department_id' => null,
            'doctor_id' => null,
            'appointment_date' => now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '09:00 AM - 10:00 AM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $appointment = Appointment::where('patient_email', 'fatuma.hassan@example.co.ke')->first();
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->department_id);
        $this->assertNull($appointment->doctor_id);
        $this->assertEquals('pending', $appointment->status);
        $this->assertStringContainsString('General Intake', (string) $appointment->triage_notes);
    }

    public function test_mismatched_doctor_and_department_is_rejected(): void
    {
        $payload = [
            'patient_name' => 'Brian Kiprop',
            'patient_email' => 'brian@example.co.ke',
            'patient_phone' => '0711223344',
            'department_id' => $this->activeDept2->id, // Neurology
            'doctor_id' => $this->publishedDoctor1->id, // Doctor is in Cardiology
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '11:00 AM - 12:00 PM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['doctor_id']);
        $this->assertDatabaseMissing('appointments', ['patient_name' => 'Brian Kiprop']);
    }

    public function test_inactive_department_selection_is_rejected(): void
    {
        $payload = [
            'patient_name' => 'Grace Muthoni',
            'patient_email' => 'grace@example.co.ke',
            'patient_phone' => '0711223344',
            'department_id' => $this->inactiveDept->id,
            'doctor_id' => null,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '11:00 AM - 12:00 PM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['department_id']);
    }

    public function test_unavailable_or_unpublished_doctor_is_rejected(): void
    {
        $payload = [
            'patient_name' => 'Samuel Koech',
            'patient_email' => 'samuel@example.co.ke',
            'patient_phone' => '0711223344',
            'department_id' => $this->activeDept1->id,
            'doctor_id' => $this->unavailableDoctor->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '11:00 AM - 12:00 PM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['doctor_id']);
    }
}
