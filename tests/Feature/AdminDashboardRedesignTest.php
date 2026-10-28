<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Department $cardiology;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cardiology = Department::create([
            'name' => 'Cardiology & Vascular Medicine',
            'slug' => 'cardiology',
            'summary' => 'Comprehensive heart care',
            'description' => 'Heart center services',
            'head_of_department' => 'Dr. Heart',
            'phone_extension' => '101',
            'emergency_contact' => '0711000001',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->doctor = Doctor::create([
            'name' => 'Dr. Jane Smith',
            'slug' => 'dr-jane-smith',
            'title' => 'Chief Cardiologist',
            'specialty' => 'Cardiology',
            'qualifications' => 'MBChB, MMed',
            'bio' => 'Experienced cardiologist and clinical researcher.',
            'department_id' => $this->cardiology->id,
            'email' => 'dr.smith@medicare.local',
            'phone' => '0711000002',
            'is_available' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'System SuperAdmin',
            'email' => 'superadmin@medicare.local',
            'password' => bcrypt('AdminPassword123!'),
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_unauthenticated_guest_is_redirected_to_staff_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_inactive_staff_member_is_denied_access(): void
    {
        $inactive = User::create([
            'name' => 'Inactive Nurse',
            'email' => 'inactive.nurse@medicare.local',
            'password' => bcrypt('Secret123!'),
            'role' => UserRole::NURSE,
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactive)->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_renders_successfully_for_all_eight_staff_roles(): void
    {
        $roles = [
            UserRole::SUPER_ADMIN,
            UserRole::HOSPITAL_DIRECTOR,
            UserRole::DOCTOR,
            UserRole::NURSE,
            UserRole::PHARMACIST,
            UserRole::LAB_TECHNICIAN,
            UserRole::CASHIER,
            UserRole::RECEPTIONIST,
        ];

        foreach ($roles as $role) {
            $user = User::create([
                'name' => 'Staff Member '.$role->value,
                'email' => 'staff.'.$role->value.'@medicare.local',
                'password' => bcrypt('SecretPassword123!'),
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $response = $this->actingAs($user)->get(route('admin.dashboard'));

            $response->assertOk();
            $response->assertSee('Hospital Operations & Clinical Dashboard');
            $response->assertSee($user->name);
            $response->assertSee($role->label());
            $response->assertSee('Role Profile & Permissions');
            $response->assertSee('Authorized to View:');
            $response->assertSee('Authorized to Create & Change:');
            $response->assertSee('Operational & Clinical Restrictions:');
        }
    }

    public function test_dashboard_displays_database_backed_metrics_correctly(): void
    {
        // 1. Create Patient
        $patient = Patient::create([
            'mrn' => 'MRN-2026-00001',
            'first_name' => 'Alice',
            'last_name' => 'Kariuki',
            'date_of_birth' => '1990-05-15',
            'gender' => 'female',
            'phone' => '0712345678',
            'email' => 'alice@example.com',
            'blood_group' => 'O+',
        ]);

        // 2. Create Today's Appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'reference_code' => 'MC-20261024-TEST1',
            'patient_name' => $patient->full_name,
            'patient_email' => $patient->email,
            'patient_phone' => $patient->phone,
            'department_id' => $this->cardiology->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => today(),
            'appointment_time' => '10:00:00',
            'symptoms' => 'Chest pain evaluation',
            'status' => 'confirmed',
        ]);

        // 3. Create Visit & Signed Consultation
        $visit = Visit::create([
            'visit_number' => 'VST-20261024-TEST1',
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'department_id' => $this->cardiology->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'appointment',
            'reason_for_visit' => 'Chest pain',
            'priority' => 'urgent',
            'status' => 'completed',
            'checked_in_by' => $this->superAdmin->id,
            'checked_in_at' => now(),
            'completed_at' => now(),
        ]);

        Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'clinician_id' => $this->superAdmin->id,
            'chief_complaint' => 'Chest discomfort on exertion',
            'primary_diagnosis' => 'Angina Pectoris',
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => $this->superAdmin->id,
        ]);

        // 4. Create Low Stock Medicine
        $medicine = Medicine::create([
            'code' => 'MED-TEST01',
            'brand_name' => 'CardioAspirin',
            'generic_name' => 'Aspirin',
            'category' => 'analgesics',
            'dosage_form' => 'tablet',
            'strength' => '75mg',
            'unit_of_measure' => 'strip',
            'reorder_level' => 100,
            'is_active' => true,
        ]);

        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-TEST-01',
            'expiry_date' => today()->addMonths(6),
            'quantity_initial' => 50,
            'quantity_current' => 30, // below 100 reorder level
            'unit_purchase_cost' => 10.0,
            'unit_dispensing_price' => 15.0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('CardioAspirin');
        $response->assertSee('Low Stock');
        $response->assertSee('MC-20261024-TEST1');
        $response->assertSee('Alice Kariuki');
        $response->assertSee('Appointments Today');
        $response->assertSee('Registered Patients');
        $response->assertSee('Active Encounters');
        $response->assertSee('Pharmacy Alerts');
    }

    public function test_dashboard_renders_with_zero_data_cleanly(): void
    {
        // When there are no patients, appointments or medicines
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Hospital Operations &amp; Clinical Dashboard', false);
        $response->assertSee('No appointment bookings recorded yet.');
        $response->assertSee('No medicines catalogued in inventory yet.');
        $response->assertSee('No appointments recorded yet.');
    }

    public function test_security_audit_stream_is_strictly_isolated_to_directors_and_super_admins(): void
    {
        AuditLog::create([
            'action' => 'security.role_modification',
            'resource_type' => 'user',
            'resource_id' => '1',
            'actor_id' => $this->superAdmin->id,
            'actor_email' => $this->superAdmin->email,
            'actor_role' => 'super_admin',
            'ip_address' => '127.0.0.1',
            'outcome' => 'success',
            'details' => ['role' => 'nurse'],
        ]);

        // SuperAdmin can view audit events
        $superAdminResponse = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));
        $superAdminResponse->assertOk();
        $superAdminResponse->assertSee('security.role_modification');

        // Hospital Director can view audit events
        $director = User::create([
            'name' => 'Director Dan',
            'email' => 'director.dan@medicare.local',
            'password' => bcrypt('Secret123!'),
            'role' => UserRole::HOSPITAL_DIRECTOR,
            'is_active' => true,
        ]);
        $directorResponse = $this->actingAs($director)->get(route('admin.dashboard'));
        $directorResponse->assertOk();
        $directorResponse->assertSee('security.role_modification');

        // Receptionist cannot view audit events
        $receptionist = User::create([
            'name' => 'Receptionist Rita',
            'email' => 'rita@medicare.local',
            'password' => bcrypt('Secret123!'),
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);
        $receptionistResponse = $this->actingAs($receptionist)->get(route('admin.dashboard'));
        $receptionistResponse->assertOk();
        $receptionistResponse->assertDontSee('security.role_modification');
        $receptionistResponse->assertSee('Audit Stream Protected');
    }

    public function test_dashboard_includes_modern_admin_layout_and_chart_containers(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('statusDistributionChart');
        $response->assertSee('weeklyFlowChart');
        $response->assertSee('monthlyTrendChart');
        $response->assertSee('currentDate');
        $response->assertSee('currentTime');
        $response->assertSee('admin-assets/css/main.css');
        $response->assertSee('admin-assets/vendor/apexcharts/apexcharts.min.js');
        $response->assertSee('admin-assets/js/theme.js');
        $response->assertSee('admin-assets/js/main.js');
    }
}
