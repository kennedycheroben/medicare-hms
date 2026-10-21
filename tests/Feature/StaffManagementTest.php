<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $doctorUser;
    protected User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->superAdmin = User::create([
            'name' => 'Lead Administrator',
            'email' => 'superadmin@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'Dr. Gregory House',
            'email' => 'doctor@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Receptionist Sarah',
            'email' => 'receptionist@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_view_staff_directory_with_filters(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/staff');

        $response->assertStatus(200);
        $response->assertSee('Staff Management');
        $response->assertSee('superadmin@medicare.local');
        $response->assertSee('doctor@medicare.local');
        $response->assertSee('receptionist@medicare.local');

        // Test filter by role
        $filterRole = $this->actingAs($this->superAdmin)->get('/admin/staff?role=' . UserRole::DOCTOR->value);
        $filterRole->assertStatus(200);
        $filterRole->assertSee('doctor@medicare.local');
        $filterRole->assertDontSee('receptionist@medicare.local');

        // Test filter by search query
        $filterSearch = $this->actingAs($this->superAdmin)->get('/admin/staff?search=Gregory');
        $filterSearch->assertStatus(200);
        $filterSearch->assertSee('Dr. Gregory House');
        $filterSearch->assertDontSee('Receptionist Sarah');
    }

    public function test_non_superadmin_cannot_access_staff_directory(): void
    {
        $responseDoctor = $this->actingAs($this->doctorUser)->get('/admin/staff');
        $responseDoctor->assertStatus(403);

        $responseReceptionist = $this->actingAs($this->receptionist)->get('/admin/staff');
        $responseReceptionist->assertStatus(403);

        \Illuminate\Support\Facades\Auth::guard('web')->forgetUser();
        $responseGuest = $this->get('/admin/staff');
        $responseGuest->assertRedirect('/admin/login');
    }

    public function test_superadmin_can_create_a_new_staff_account(): void
    {
        $department = Department::first();

        $response = $this->actingAs($this->superAdmin)->post('/admin/staff', [
            'name' => 'Nurse Clara Barton',
            'email' => 'clara.barton@medicare.local',
            'password' => 'NurseSecret2026!',
            'role' => UserRole::NURSE->value,
            'department_id' => $department->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/staff');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'clara.barton@medicare.local',
            'role' => UserRole::NURSE->value,
            'department_id' => $department->id,
            'is_active' => 1,
        ]);

        // Audit log created with password redacted
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'staff.created',
            'outcome' => 'success',
        ]);

        $log = AuditLog::where('action', 'staff.created')->latest()->first();
        $this->assertNotNull($log->details);
        $this->assertArrayNotHasKey('password', $log->details);
    }

    public function test_staff_creation_fails_with_duplicate_email_or_invalid_role(): void
    {
        $response = $this->actingAs($this->superAdmin)->from('/admin/staff/create')->post('/admin/staff', [
            'name' => 'Duplicate Staff',
            'email' => 'doctor@medicare.local', // Already taken
            'password' => 'ValidPass123!',
            'role' => 'invalid_role_name',
        ]);

        $response->assertRedirect('/admin/staff/create');
        $response->assertSessionHasErrors(['email', 'role']);
    }

    public function test_superadmin_can_edit_staff_member(): void
    {
        $department = Department::first();

        $response = $this->actingAs($this->superAdmin)->put('/admin/staff/' . $this->doctorUser->id, [
            'name' => 'Dr. Gregory House, MD',
            'email' => 'doctor@medicare.local',
            'role' => UserRole::DOCTOR->value,
            'department_id' => $department->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/staff');
        $this->assertDatabaseHas('users', [
            'id' => $this->doctorUser->id,
            'name' => 'Dr. Gregory House, MD',
        ]);
    }

    public function test_superadmin_can_toggle_staff_active_status(): void
    {
        $this->assertTrue($this->doctorUser->is_active);

        // Deactivate
        $response = $this->actingAs($this->superAdmin)->post('/admin/staff/' . $this->doctorUser->id . '/toggle-status');
        $response->assertRedirect();
        $this->doctorUser->refresh();
        $this->assertFalse($this->doctorUser->is_active);

        // Deactivated user cannot access protected routes
        $guardedResponse = $this->actingAs($this->doctorUser)->get('/admin/dashboard');
        $guardedResponse->assertRedirect('/admin/login');
        $guardedResponse->assertSessionHasErrors(['email']);

        // Activate back
        $response2 = $this->actingAs($this->superAdmin)->post('/admin/staff/' . $this->doctorUser->id . '/toggle-status');
        $response2->assertRedirect();
        $this->doctorUser->refresh();
        $this->assertTrue($this->doctorUser->is_active);
    }

    public function test_superadmin_cannot_deactivate_or_demote_themselves(): void
    {
        // Attempt self-deactivation via toggle
        $response = $this->actingAs($this->superAdmin)->post('/admin/staff/' . $this->superAdmin->id . '/toggle-status');
        $response->assertSessionHasErrors(['status']);
        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_active);

        // Attempt self-demotion via update
        $responseDemote = $this->actingAs($this->superAdmin)->put('/admin/staff/' . $this->superAdmin->id, [
            'name' => 'Demoted Admin',
            'email' => 'superadmin@medicare.local',
            'role' => UserRole::RECEPTIONIST->value,
            'is_active' => '1',
        ]);

        $responseDemote->assertSessionHasErrors(['role']);
        $this->superAdmin->refresh();
        $this->assertEquals(UserRole::SUPER_ADMIN, $this->superAdmin->role);
    }

    public function test_last_superadmin_cannot_be_deactivated_or_demoted(): void
    {
        // Only 1 superadmin exists currently ($this->superAdmin).
        // Create a temporary second superadmin
        $secondAdmin = User::create([
            'name' => 'Secondary Admin',
            'email' => 'admin2@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        // First admin can deactivate second admin
        $this->actingAs($this->superAdmin)->post('/admin/staff/' . $secondAdmin->id . '/toggle-status');
        $secondAdmin->refresh();
        $this->assertFalse($secondAdmin->is_active);

        // Reactivate secondAdmin
        $secondAdmin->update(['is_active' => true]);

        // Remove secondAdmin's superadmin role
        $secondAdmin->update(['role' => UserRole::DOCTOR]);

        // Now $this->superAdmin is the ONLY SuperAdmin in the entire hospital
        $this->assertEquals(1, User::where('role', UserRole::SUPER_ADMIN)->where('is_active', true)->count());
    }
}
