<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ContactInquiry;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Department::exists()) {
            $this->seed(\Database\Seeders\HospitalDataSeeder::class);
        }
    }
    /**
     * Test that all 12 primary public pages return a successful 200 response.
     */
    public function test_all_twelve_public_website_pages_render_successfully(): void
    {
        // 1. Home
        $this->get(route('home'))
            ->assertStatus(200)
            ->assertSee('MediCare')
            ->assertSee('Clinical Departments');

        // 2. About
        $this->get(route('about'))
            ->assertStatus(200)
            ->assertSee('About MediCare Hospital');

        // 3. Departments
        $this->get(route('departments'))
            ->assertStatus(200)
            ->assertSee('Clinical Departments');

        // 4. Department Details (using seeded cardiology slug)
        $department = Department::where('slug', 'cardiology')->first();
        if ($department) {
            $this->get(route('departments.show', $department->slug))
                ->assertStatus(200)
                ->assertSee($department->name);
        }

        // 5. Doctors Directory
        $this->get(route('doctors'))
            ->assertStatus(200)
            ->assertSee('Find a Doctor');

        // 6. Doctor Profile (using seeded doctor)
        $doctor = Doctor::first();
        if ($doctor) {
            $this->get(route('doctors.show', $doctor->id))
                ->assertStatus(200)
                ->assertSee($doctor->name);
        }

        // 7. Services
        $this->get(route('services'))
            ->assertStatus(200)
            ->assertSee('Hospital Services & Clinical Facilities');

        // 8. Patient Information
        $this->get(route('patient-info'))
            ->assertStatus(200)
            ->assertSee('Patient Information & Visiting Guide');

        // 9. News & Advisories
        $this->get(route('news'))
            ->assertStatus(200)
            ->assertSee('News & Health Advisories');

        // 10. Book Appointment
        $this->get(route('appointments.create'))
            ->assertStatus(200)
            ->assertSee('Schedule An Appointment')
            ->assertSee(config('hospital.is_demo') ? 'Demonstration Workflow:' : 'Important Appointment Policy:');

        // 11. Contact
        $this->get(route('contact'))
            ->assertStatus(200)
            ->assertSee('Contact Us & General Enquiries');

        // 12. Patient Portal Entry
        $this->get(route('portal.login'))
            ->assertStatus(200)
            ->assertSee('Patient Portal Gateway')
            ->assertSee('Medical Privacy Commitment');

        // 13. Privacy Policy (KDPA 2019)
        $this->get(route('privacy'))
            ->assertStatus(200)
            ->assertSee('Privacy Policy & Data Protection')
            ->assertSee('Kenya Data Protection Act, 2019');

        // 14. Terms of Service
        $this->get(route('terms'))
            ->assertStatus(200)
            ->assertSee('Terms of Service')
            ->assertSee('Emergency Care Notice');

        // 15. User Workbook & Guide
        $this->get(route('guide'))
            ->assertStatus(200)
            ->assertSee('User Workbook')
            ->assertSee('Who is MediCare HMS For?');
    }

    /**
     * Test unknown department slug returns 404.
     */
    public function test_unknown_department_slug_returns_404(): void
    {
        $this->get('/departments/non-existent-department-slug-xyz')
            ->assertStatus(404);
    }

    /**
     * Test unknown doctor profile returns 404.
     */
    public function test_unknown_doctor_id_returns_404(): void
    {
        $this->get('/doctors/999999')
            ->assertStatus(404);
    }

    /**
     * Test department listings display database-backed records.
     */
    public function test_department_directory_displays_database_records(): void
    {
        $department = Department::where('is_active', true)->first();

        if ($department) {
            $this->get(route('departments'))
                ->assertStatus(200)
                ->assertSee($department->name);
        }
    }

    /**
     * Test doctor directory displays database-backed records and handles filtering.
     */
    public function test_doctor_directory_displays_database_records_and_filters(): void
    {
        $doctor = Doctor::where('is_available', true)->first();

        if ($doctor) {
            $this->get(route('doctors', ['search' => $doctor->name]))
                ->assertStatus(200)
                ->assertSee($doctor->name);
        }
    }

    /**
     * Test valid appointment request submission persists to database and redirects to confirmation.
     * Also verifies that the public confirmation page does NOT expose patient PII.
     */
    public function test_valid_appointment_request_persists_and_confirmation_does_not_expose_pii(): void
    {
        $department = Department::first();
        $doctor = Doctor::where('department_id', $department->id)->first();

        $payload = [
            'patient_name' => 'Alexander Hayes',
            'patient_email' => 'alex.hayes@example.org',
            'patient_phone' => '+15559876543',
            'department_id' => $department ? $department->id : 1,
            'doctor_id' => $doctor ? $doctor->id : null,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '10:00 AM - 11:00 AM',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_name' => 'Alexander Hayes',
            'patient_email' => 'alex.hayes@example.org',
            'status' => 'pending',
            'symptoms' => null, // Confirms symptoms are not collected on public form
        ]);

        $appointment = Appointment::where('patient_email', 'alex.hayes@example.org')->latest()->first();
        $this->assertNotNull($appointment);
        $this->assertStringStartsWith('MC-', $appointment->reference_code);

        // Access the public confirmation screen
        $confirmResponse = $this->get(route('appointments.confirmation', $appointment->reference_code));
        $confirmResponse->assertStatus(200);

        // Must display reference number and confirmation disclaimer
        $confirmResponse->assertSee($appointment->reference_code);
        $confirmResponse->assertSee('does not constitute a confirmed appointment');

        // MUST NOT display patient PII (Name, Phone, Email) to prevent unauthorized record retrieval
        $confirmResponse->assertDontSee('Alexander Hayes');
        $confirmResponse->assertDontSee('alex.hayes@example.org');
        $confirmResponse->assertDontSee('+15559876543');
    }

    /**
     * Test that an arbitrary visitor with a reference code cannot harvest another patient's data.
     */
    public function test_unauthorized_visitor_with_reference_cannot_retrieve_patient_personal_data(): void
    {
        $uniqueRef = 'MC-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $department = Department::first();

        $appointment = Appointment::create([
            'reference_code' => $uniqueRef,
            'patient_name' => 'Confidential Patient Target',
            'patient_email' => 'private.patient@confidential-health.test',
            'patient_phone' => '+15550009999',
            'department_id' => $department?->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d'),
            'appointment_time' => '02:00 PM - 03:00 PM',
            'status' => 'pending',
        ]);

        $response = $this->get(route('appointments.confirmation', $uniqueRef));

        $response->assertStatus(200);
        $response->assertSee($uniqueRef);
        $response->assertSee('Appointment Request Registered');

        // Verify zero leakage of personal data
        $response->assertDontSee('Confidential Patient Target');
        $response->assertDontSee('private.patient@confidential-health.test');
        $response->assertDontSee('+15550009999');
    }

    /**
     * Test that invalid or malformed reference codes return 404 without internal error disclosure.
     */
    public function test_invalid_reference_code_returns_clean_404(): void
    {
        // Malformed format
        $this->get('/appointments/confirmation/invalid-reference-format')
            ->assertStatus(404);

        // Path traversal / SQL injection probe
        $this->get('/appointments/confirmation/MC-20261020-NONEXISTENT')
            ->assertStatus(404);
    }

    /**
     * Test invalid appointment submission fails validation.
     */
    public function test_invalid_appointment_request_is_rejected(): void
    {
        $payload = [
            'patient_name' => '', // missing
            'patient_email' => 'invalid-email',
            'patient_phone' => '',
            'department_id' => 99999, // invalid department
            'appointment_date' => now()->subDays(5)->format('Y-m-d'), // past date
            'appointment_time' => '',
        ];

        $response = $this->post(route('appointments.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'patient_name',
            'patient_email',
            'patient_phone',
            'department_id',
            'appointment_date',
            'appointment_time',
        ]);
    }

    /**
     * Test valid contact enquiry submission persists and redirects with flash message.
     */
    public function test_valid_contact_inquiry_persists_and_redirects(): void
    {
        $payload = [
            'name' => 'Beatrice Vance',
            'email' => 'beatrice@example.org',
            'phone' => '+15553334444',
            'department' => 'Cardiology',
            'subject' => 'Inquiry regarding cardiology consultation hours',
            'message' => 'Hello, I would like to inquire about the consultation schedule for outpatient cardiology checkups.',
        ];

        $response = $this->post(route('contact.store'), $payload);

        $response->assertStatus(302);
        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_inquiries', [
            'name' => 'Beatrice Vance',
            'email' => 'beatrice@example.org',
            'subject' => 'Inquiry regarding cardiology consultation hours',
            'status' => 'new',
        ]);
    }

    /**
     * Test invalid contact enquiry is rejected with validation errors.
     */
    public function test_invalid_contact_inquiry_is_rejected(): void
    {
        $payload = [
            'name' => 'A', // too short
            'email' => 'bad-email',
            'subject' => '',
            'message' => 'Short', // too short
        ];

        $response = $this->post(route('contact.store'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    /**
     * Test that rate limiting throttles excessive appointment submissions.
     */
    public function test_rate_limiting_is_enforced_on_appointment_submissions(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('appointments.store'), []);
        }

        // 11th request should exceed the 10 per minute throttle limit
        $response = $this->post(route('appointments.store'), []);
        $response->assertStatus(429);
    }

    /**
     * Test that rate limiting throttles excessive contact submissions.
     */
    public function test_rate_limiting_is_enforced_on_contact_submissions(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('contact.store'), []);
        }

        // 11th request should exceed the 10 per minute throttle limit
        $response = $this->post(route('contact.store'), []);
        $response->assertStatus(429);
    }

    /**
     * Test shared navigation links and footer branding are rendered consistently.
     */
    public function test_shared_navigation_and_footer_are_rendered(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200)
            ->assertSee('MediCare')
            ->assertSee('Book Appointment')
            ->assertSee(config('hospital.is_demo') ? 'Emergency Care' : 'Emergency Line')
            ->assertSee('Quick Links');
    }

    /**
     * Regression test: Verify public layout contains progressive enhancement classes and fail-safe JS detection.
     */
    public function test_public_layout_includes_progressive_enhancement_and_failsafe_classes(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Verify HTML element begins with no-js class for graceful degradation
        $response->assertSee('class="no-js"', false);

        // Verify inline script immediately activates .js class
        $response->assertSee("document.documentElement.classList.remove('no-js')", false);
        $response->assertSee("document.documentElement.classList.add('js')", false);
    }

    /**
     * Regression test: Verify main.css ensures all [data-aos] content is visible by default.
     */
    public function test_public_website_main_css_contains_failsafe_aos_visibility_rules(): void
    {
        $cssPath = public_path('assets/css/main.css');
        $this->assertFileExists($cssPath);

        $cssContent = file_get_contents($cssPath);

        // Ensures [data-aos] is forced visible by default
        $this->assertStringContainsString('[data-aos]', $cssContent);
        $this->assertStringContainsString('opacity: 1 !important;', $cssContent);
        $this->assertStringContainsString('html.aos-initialized', $cssContent);
    }
}
