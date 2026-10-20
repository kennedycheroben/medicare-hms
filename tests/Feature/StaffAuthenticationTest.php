<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Staff Portal');
        $response->assertSee('Staff Email Address');
        $response->assertSee('Role-Based Access Controlled');
    }

    public function test_active_staff_can_authenticate_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Dr. Eleanor Vance',
            'email' => 'admin@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@medicare.local',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);

        // Verify last login metadata updated
        $user->refresh();
        $this->assertNotNull($user->last_login_at);

        // Verify audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'actor_email' => 'admin@medicare.local',
            'action' => 'auth.login',
            'outcome' => 'success',
        ]);
    }

    public function test_invalid_credentials_are_rejected_with_generic_error(): void
    {
        User::create([
            'name' => 'Dr. Eleanor Vance',
            'email' => 'admin@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@medicare.local',
            'password' => 'IncorrectPassword!',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Audit log records failure
        $this->assertDatabaseHas('audit_logs', [
            'actor_email' => 'admin@medicare.local',
            'action' => 'auth.failed_login',
            'outcome' => 'failure',
        ]);
    }

    public function test_inactive_staff_member_is_rejected_and_session_cleared(): void
    {
        User::create([
            'name' => 'Disabled Staff',
            'email' => 'disabled@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => false,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'disabled@medicare.local',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Audit log records deactivation rejection
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.deactivated_login_rejected',
            'outcome' => 'denied',
        ]);
    }

    public function test_login_attempts_are_rate_limited_after_five_failed_attempts(): void
    {
        User::create([
            'name' => 'Target User',
            'email' => 'target@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/admin/login')->post('/admin/login', [
                'email' => 'target@medicare.local',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should trigger rate limiting
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'target@medicare.local',
            'password' => 'WrongPassword',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_throttled',
            'outcome' => 'failure',
        ]);
    }

    public function test_authenticated_staff_can_logout_securely(): void
    {
        $user = User::create([
            'name' => 'Doctor Active',
            'email' => 'doctor@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::DOCTOR,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
            'outcome' => 'success',
        ]);
    }

    public function test_unauthenticated_users_cannot_access_protected_admin_routes(): void
    {
        $protectedRoutes = [
            '/admin/dashboard',
            '/admin/staff',
            '/admin/reports',
            '/admin/clinical',
            '/admin/triage',
            '/admin/pharmacy',
            '/admin/laboratory',
            '/admin/billing',
            '/admin/audit-logs',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/admin/login');
        }
    }

    public function test_super_admin_can_access_all_modules(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $allModules = [
            '/admin/dashboard',
            '/admin/staff',
            '/admin/reports',
            '/admin/clinical',
            '/admin/triage',
            '/admin/pharmacy',
            '/admin/laboratory',
            '/admin/billing',
            '/admin/audit-logs',
        ];

        foreach ($allModules as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_doctor_least_privilege_enforcement(): void
    {
        $doctor = User::create([
            'name' => 'Dr. Chen',
            'email' => 'doc@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::DOCTOR,
            'is_active' => true,
        ]);

        $this->actingAs($doctor);

        // Allowed
        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/clinical')->assertStatus(200);

        // Forbidden
        $this->get('/admin/staff')->assertStatus(403);
        $this->get('/admin/billing')->assertStatus(403);
        $this->get('/admin/audit-logs')->assertStatus(403);

        // Verify RBAC access denial was recorded in audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $doctor->id,
            'action' => 'rbac.access_denied',
            'outcome' => 'denied',
        ]);
    }

    public function test_pharmacist_least_privilege_enforcement(): void
    {
        $pharmacist = User::create([
            'name' => 'Pharm. Jane',
            'email' => 'pharm@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::PHARMACIST,
            'is_active' => true,
        ]);

        $this->actingAs($pharmacist);

        // Allowed
        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/pharmacy')->assertStatus(200);

        // Forbidden
        $this->get('/admin/clinical')->assertStatus(403);
        $this->get('/admin/billing')->assertStatus(403);
    }

    public function test_cashier_least_privilege_enforcement(): void
    {
        $cashier = User::create([
            'name' => 'Cashier Faith',
            'email' => 'cashier@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // Allowed
        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/billing')->assertStatus(200);

        // Forbidden
        $this->get('/admin/clinical')->assertStatus(403);
        $this->get('/admin/pharmacy')->assertStatus(403);
        $this->get('/admin/staff')->assertStatus(403);
    }

    public function test_receptionist_least_privilege_enforcement(): void
    {
        $receptionist = User::create([
            'name' => 'Receptionist Brian',
            'email' => 'receptionist@medicare.local',
            'password' => 'ValidPassword123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->actingAs($receptionist);

        // Allowed
        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/triage')->assertStatus(200);

        // Forbidden
        $this->get('/admin/reports')->assertStatus(403);
        $this->get('/admin/staff')->assertStatus(403);
        $this->get('/admin/audit-logs')->assertStatus(403);
    }

    public function test_audit_service_redacts_passwords_and_secrets(): void
    {
        \App\Services\AuditService::record(
            action: 'test.sensitive_action',
            resourceType: 'test',
            resourceId: '1',
            details: [
                'password' => 'SuperSecret123',
                'token' => 'jwt_secret_token',
                'public_data' => 'allowed_value',
            ],
            outcome: 'success'
        );

        $log = AuditLog::where('action', 'test.sensitive_action')->first();

        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->details['password']);
        $this->assertEquals('[REDACTED]', $log->details['token']);
        $this->assertEquals('allowed_value', $log->details['public_data']);
    }

    public function test_cli_command_provisions_super_admin(): void
    {
        $this->artisan('hms:create-admin', [
            '--name' => 'Command Admin',
            '--email' => 'cli-admin@medicare.local',
            '--password' => 'SecurePass2026!',
        ])->assertSuccessful();

        $user = User::where('email', 'cli-admin@medicare.local')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::SUPER_ADMIN, $user->role);
        $this->assertTrue($user->isActive());
        $this->assertTrue(Hash::check('SecurePass2026!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.admin_provisioned_cli',
            'resource_id' => (string) $user->id,
            'outcome' => 'success',
        ]);
    }
}
