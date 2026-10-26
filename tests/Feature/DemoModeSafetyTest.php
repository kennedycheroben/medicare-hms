<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoModeSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Department::exists()) {
            $this->seed(\Database\Seeders\HospitalDataSeeder::class);
        }
    }

    /**
     * Test that demo mode displays prominent software demonstration disclaimers across public pages.
     */
    public function test_demo_mode_displays_prominent_software_demonstration_disclaimers(): void
    {
        Config::set('hospital.is_demo', true);

        // Home page
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Software Demonstration Notice');
        $homeResponse->assertSee('HMS Demonstration Platform');

        // Contact page
        $contactResponse = $this->get(route('contact'));
        $contactResponse->assertStatus(200);
        $contactResponse->assertSee('Software Demonstration Notice');
        $contactResponse->assertSee('Sample Switchboard');

        // Appointment booking page
        $appointmentResponse = $this->get(route('appointments.create'));
        $appointmentResponse->assertStatus(200);
        $appointmentResponse->assertSee('Demonstration Booking Notice');
        $appointmentResponse->assertSee('Simulated Appointment Request');
        $appointmentResponse->assertSee('Demonstration Workflow');

        // Departments page
        $departmentsResponse = $this->get(route('departments'));
        $departmentsResponse->assertStatus(200);
        $departmentsResponse->assertSee('Demonstration Departments Notice');

        // Services page
        $servicesResponse = $this->get(route('services'));
        $servicesResponse->assertStatus(200);
        $servicesResponse->assertSee('Demonstration Services Notice');

        // Doctors page
        $doctorsResponse = $this->get(route('doctors'));
        $doctorsResponse->assertStatus(200);
        $doctorsResponse->assertSee('Demonstration Roster Notice');

        // Patient Info page
        $patientInfoResponse = $this->get(route('patient-info'));
        $patientInfoResponse->assertStatus(200);
        $patientInfoResponse->assertSee('Demonstration Notice');

        // Terms of Service page
        $termsResponse = $this->get(route('terms'));
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('Demonstration Terms Notice');

        // Privacy Policy page
        $privacyResponse = $this->get(route('privacy'));
        $privacyResponse->assertStatus(200);
        $privacyResponse->assertSee('Demonstration Privacy Notice');

        // News page
        $newsResponse = $this->get(route('news'));
        $newsResponse->assertStatus(200);
        $newsResponse->assertSee('Demonstration Notice');
    }

    /**
     * Test that demo mode suppresses dialable emergency telephone links for fictional hotline numbers.
     */
    public function test_demo_mode_suppresses_clickable_fictional_emergency_telephone_links(): void
    {
        Config::set('hospital.is_demo', true);
        $emergencyPhone = config('hospital.emergency_phone');
        $emergencyTelAttr = 'href="tel:'.$emergencyPhone.'"';

        $pagesToCheck = [
            route('home'),
            route('contact'),
            route('appointments.create'),
            route('departments'),
        ];

        foreach ($pagesToCheck as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertDontSee($emergencyTelAttr, false);
        }
    }

    /**
     * Test that demo mode provides real-world national emergency dispatch guidance (999 / 112).
     */
    public function test_demo_mode_provides_national_emergency_dispatch_guidance(): void
    {
        Config::set('hospital.is_demo', true);

        // Home page
        $this->get(route('home'))
            ->assertStatus(200)
            ->assertSee('999 / 112')
            ->assertSee('Emergency Dispatch');

        // Contact page
        $this->get(route('contact'))
            ->assertStatus(200)
            ->assertSee('Call 999 / 112 (National)');

        // Appointment booking page
        $this->get(route('appointments.create'))
            ->assertStatus(200)
            ->assertSee('999 / 112');

        // Terms page
        $this->get(route('terms'))
            ->assertStatus(200)
            ->assertSee('999 / 112');
    }

    /**
     * Test that demo mode suppresses false accreditation and regulatory licensing claims.
     */
    public function test_demo_mode_suppresses_false_accreditation_claims(): void
    {
        Config::set('hospital.is_demo', true);

        // Services page should show simulated service instead of Level 1 Accredited or ISO 15189 Accredited
        $servicesResponse = $this->get(route('services'));
        $servicesResponse->assertStatus(200);
        $servicesResponse->assertDontSee('Level 1 Accredited');
        $servicesResponse->assertDontSee('ISO 15189 Accredited');
        $servicesResponse->assertSee('Simulated Emergency Service');
        $servicesResponse->assertSee('Diagnostic Laboratory');

        // Appointment booking page should not claim "Board-Certified" doctor
        $appointmentResponse = $this->get(route('appointments.create'));
        $appointmentResponse->assertStatus(200);
        $appointmentResponse->assertDontSee('Any Available Board-Certified Doctor');
        $appointmentResponse->assertSee('Any Available Specialist (Simulated)');

        // Home page should show Trauma Simulation Facility instead of Accredited Trauma Facility
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('Accredited Trauma Facility');
        $homeResponse->assertSee('Trauma Simulation Facility');
    }

    /**
     * Test that the deployment runbook contains explicit defensive backup, staging, and symlink safeguards.
     */
    public function test_deployment_runbook_contains_defensive_deployment_safeguards(): void
    {
        $runbookPath = base_path('DEPLOYMENT_RUNBOOK.md');
        $this->assertFileExists($runbookPath);
        $runbookContent = file_get_contents($runbookPath);

        // 1. Must NOT contain raw blind copy command directly into public web root
        $this->assertStringNotContainsString(
            'cp -r /home/xqtrqexj/medicare-hms-app/public/admin-assets /home/xqtrqexj/medicare.kennedycheroben.co.ke/',
            $runbookContent,
            'Runbook must not instruct blind cp -r directly into web root.'
        );

        // 2. Must contain backup procedure before synchronizing web root
        $this->assertStringContainsString('BACKUP_DIR=', $runbookContent);
        $this->assertStringContainsString('STAGING_DIR=', $runbookContent);
        $this->assertStringContainsString('cp -a "${WEB_ROOT}" "${BACKUP_DIR}"', $runbookContent);

        // 3. Must preserve existing .htaccess and cPanel handlers
        $this->assertStringContainsString('cPanel-generated handler', $runbookContent);
        $this->assertStringContainsString('RewriteEngine On', $runbookContent);

        // 4. Must validate storage symlink and prevent silent directory clobbering
        $this->assertStringContainsString('readlink', $runbookContent);
        $this->assertStringContainsString('_dir_backup_', $runbookContent);
        $this->assertStringContainsString('human review', strtolower($runbookContent));
    }
}
