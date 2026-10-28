<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HospitalDataSeeder;
use Database\Seeders\StaffUserSeeder;
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
        AuditService::record(
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

    public function test_staff_user_seeder_is_blocked_in_production(): void
    {
        $originalEnv = $this->app->environment();
        $originalConfigEnv = config('app.env');

        $this->app->detectEnvironment(fn () => 'production');
        config(['app.env' => 'production']);
        $this->assertTrue($this->app->isProduction());

        try {
            (new StaffUserSeeder)->run();

            $this->assertSame(0, User::count());
            $this->assertEquals(0, User::where('email', 'like', '%@medicare.local')->count());

            $demoEmails = [
                'admin@medicare.local',
                'director@medicare.local',
                'doctor@medicare.local',
                'nurse@medicare.local',
                'pharmacist@medicare.local',
                'lab@medicare.local',
                'cashier@medicare.local',
                'receptionist@medicare.local',
                'inactive@medicare.local',
            ];

            foreach ($demoEmails as $email) {
                $this->assertDatabaseMissing('users', ['email' => $email]);
            }
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnv);
            config(['app.env' => $originalConfigEnv]);
        }
    }

    public function test_database_seeder_excludes_staff_users_in_production_while_preserving_structural_data(): void
    {
        // Isolate test database from setUp() seed state to prove DatabaseSeeder actively populates structural data
        Doctor::query()->delete();
        Department::query()->delete();
        User::query()->delete();

        $this->assertSame(0, Department::count());
        $this->assertSame(0, Doctor::count());
        $this->assertSame(0, User::count());

        $originalEnv = $this->app->environment();
        $originalConfigEnv = config('app.env');

        $this->app->detectEnvironment(fn () => 'production');
        config(['app.env' => 'production']);
        $this->assertTrue($this->app->isProduction());

        try {
            (new DatabaseSeeder)->run();

            // Structural reference data (departments and doctors) is fully populated
            $this->assertSame(6, Department::count());
            $this->assertSame(6, Doctor::count());

            $this->assertDatabaseHas('departments', [
                'slug' => 'cardiology',
                'name' => 'Cardiology & Vascular Medicine',
            ]);
            $this->assertDatabaseHas('departments', [
                'slug' => 'neurology',
                'name' => 'Neurology & Brain Sciences',
            ]);
            $this->assertDatabaseHas('doctors', [
                'slug' => 'dr-marcus-vance',
            ]);

            // Zero demo staff accounts are seeded
            $this->assertSame(0, User::count());
            $this->assertEquals(0, User::where('email', 'like', '%@medicare.local')->count());
            $this->assertDatabaseMissing('users', ['email' => 'admin@medicare.local']);
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnv);
            config(['app.env' => $originalConfigEnv]);
        }
    }

    public function test_database_seeder_runs_both_structural_and_staff_seeders_in_non_production(): void
    {
        Doctor::query()->delete();
        Department::query()->delete();
        User::query()->delete();

        $this->assertFalse($this->app->isProduction());

        (new DatabaseSeeder)->run();

        $this->assertSame(6, Department::count());
        $this->assertSame(6, Doctor::count());
        $this->assertSame(9, User::count());
        $this->assertEquals(9, User::where('email', 'like', '%@medicare.local')->count());
    }

    public function test_staff_user_seeder_populates_all_roles_with_hashed_passwords_in_local_or_testing(): void
    {
        (new StaffUserSeeder)->run();

        $this->assertEquals(9, User::where('email', 'like', '%@medicare.local')->count());

        $expectedAccounts = [
            'admin@medicare.local' => ['role' => UserRole::SUPER_ADMIN, 'active' => true, 'password' => 'Admin@Medicare2026!'],
            'director@medicare.local' => ['role' => UserRole::HOSPITAL_DIRECTOR, 'active' => true, 'password' => 'Director@Medicare2026!'],
            'doctor@medicare.local' => ['role' => UserRole::DOCTOR, 'active' => true, 'password' => 'Doctor@Medicare2026!'],
            'nurse@medicare.local' => ['role' => UserRole::NURSE, 'active' => true, 'password' => 'Nurse@Medicare2026!'],
            'pharmacist@medicare.local' => ['role' => UserRole::PHARMACIST, 'active' => true, 'password' => 'Pharmacist@Medicare2026!'],
            'lab@medicare.local' => ['role' => UserRole::LAB_TECHNICIAN, 'active' => true, 'password' => 'Lab@Medicare2026!'],
            'cashier@medicare.local' => ['role' => UserRole::CASHIER, 'active' => true, 'password' => 'Cashier@Medicare2026!'],
            'receptionist@medicare.local' => ['role' => UserRole::RECEPTIONIST, 'active' => true, 'password' => 'Receptionist@Medicare2026!'],
            'inactive@medicare.local' => ['role' => UserRole::RECEPTIONIST, 'active' => false, 'password' => 'Inactive@Medicare2026!'],
        ];

        foreach ($expectedAccounts as $email => $expected) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "Demo account for {$email} was not found.");
            $this->assertEquals($expected['role'], $user->role, "Role mismatch for {$email}.");
            $this->assertSame($expected['active'], $user->isActive(), "Active status mismatch for {$email}.");
            $this->assertTrue(
                Hash::check($expected['password'], $user->password),
                "Password was not properly hashed with the expected default for {$email}."
            );
        }

        $doctorUser = User::where('email', 'doctor@medicare.local')->first();
        $this->assertNotNull($doctorUser->doctor_id);
    }

    public function test_staff_user_seeder_respects_custom_environment_password_override(): void
    {
        $previousEnv = getenv('DEMO_STAFF_PASSWORD');
        $previousServer = $_SERVER['DEMO_STAFF_PASSWORD'] ?? null;
        $previousGlobalEnv = $_ENV['DEMO_STAFF_PASSWORD'] ?? null;

        $customPassword = 'CustomStrongSeedPass2026!';
        putenv("DEMO_STAFF_PASSWORD={$customPassword}");
        $_ENV['DEMO_STAFF_PASSWORD'] = $customPassword;
        $_SERVER['DEMO_STAFF_PASSWORD'] = $customPassword;

        try {
            (new StaffUserSeeder)->run();

            $users = User::where('email', 'like', '%@medicare.local')->get();
            $this->assertCount(9, $users);

            foreach ($users as $user) {
                $this->assertTrue(
                    Hash::check($customPassword, $user->password),
                    "Expected demo account {$user->email} to have custom overridden password."
                );
            }
        } finally {
            if ($previousEnv !== false) {
                putenv("DEMO_STAFF_PASSWORD={$previousEnv}");
            } else {
                putenv('DEMO_STAFF_PASSWORD');
            }

            if ($previousGlobalEnv !== null) {
                $_ENV['DEMO_STAFF_PASSWORD'] = $previousGlobalEnv;
            } else {
                unset($_ENV['DEMO_STAFF_PASSWORD']);
            }

            if ($previousServer !== null) {
                $_SERVER['DEMO_STAFF_PASSWORD'] = $previousServer;
            } else {
                unset($_SERVER['DEMO_STAFF_PASSWORD']);
            }
        }
    }

    public function test_staff_user_seeder_falls_back_to_defaults_when_custom_password_is_empty_or_whitespace(): void
    {
        $previousEnv = getenv('DEMO_STAFF_PASSWORD');
        $previousServer = $_SERVER['DEMO_STAFF_PASSWORD'] ?? null;
        $previousGlobalEnv = $_ENV['DEMO_STAFF_PASSWORD'] ?? null;

        putenv('DEMO_STAFF_PASSWORD=   ');
        $_ENV['DEMO_STAFF_PASSWORD'] = '   ';
        $_SERVER['DEMO_STAFF_PASSWORD'] = '   ';

        try {
            (new StaffUserSeeder)->run();

            $admin = User::where('email', 'admin@medicare.local')->first();
            $this->assertNotNull($admin);
            $this->assertTrue(Hash::check('Admin@Medicare2026!', $admin->password));

            $doctor = User::where('email', 'doctor@medicare.local')->first();
            $this->assertNotNull($doctor);
            $this->assertTrue(Hash::check('Doctor@Medicare2026!', $doctor->password));
        } finally {
            if ($previousEnv !== false) {
                putenv("DEMO_STAFF_PASSWORD={$previousEnv}");
            } else {
                putenv('DEMO_STAFF_PASSWORD');
            }

            if ($previousGlobalEnv !== null) {
                $_ENV['DEMO_STAFF_PASSWORD'] = $previousGlobalEnv;
            } else {
                unset($_ENV['DEMO_STAFF_PASSWORD']);
            }

            if ($previousServer !== null) {
                $_SERVER['DEMO_STAFF_PASSWORD'] = $previousServer;
            } else {
                unset($_SERVER['DEMO_STAFF_PASSWORD']);
            }
        }
    }
}
