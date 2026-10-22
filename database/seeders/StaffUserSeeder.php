<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('StaffUserSeeder skipped: Demonstration staff credentials cannot be seeded in production.');

            return;
        }

        $envPassword = env('DEMO_STAFF_PASSWORD');
        $demoPasswordOverride = is_string($envPassword) && trim($envPassword) !== '' ? trim($envPassword) : null;

        $firstDepartment = Department::first();
        $firstDoctor = Doctor::first();

        $staffAccounts = [
            [
                'name' => 'Dr. Eleanor Vance (Super Admin)',
                'email' => 'admin@medicare.local',
                'password' => $demoPasswordOverride ?: 'Admin@Medicare2026!',
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Dr. Arthur Pendelton',
                'email' => 'director@medicare.local',
                'password' => $demoPasswordOverride ?: 'Director@Medicare2026!',
                'role' => UserRole::HOSPITAL_DIRECTOR,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Dr. Sarah Mwangi',
                'email' => 'doctor@medicare.local',
                'password' => $demoPasswordOverride ?: 'Doctor@Medicare2026!',
                'role' => UserRole::DOCTOR,
                'is_active' => true,
                'department_id' => $firstDoctor?->department_id ?? $firstDepartment?->id,
                'doctor_id' => $firstDoctor?->id,
            ],
            [
                'name' => 'Nurse Mary Wanjiku',
                'email' => 'nurse@medicare.local',
                'password' => $demoPasswordOverride ?: 'Nurse@Medicare2026!',
                'role' => UserRole::NURSE,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Pharm. David Kipchoge',
                'email' => 'pharmacist@medicare.local',
                'password' => $demoPasswordOverride ?: 'Pharmacist@Medicare2026!',
                'role' => UserRole::PHARMACIST,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Tech. Kevin Otieno',
                'email' => 'lab@medicare.local',
                'password' => $demoPasswordOverride ?: 'Lab@Medicare2026!',
                'role' => UserRole::LAB_TECHNICIAN,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Faith Chebet (Cashier)',
                'email' => 'cashier@medicare.local',
                'password' => $demoPasswordOverride ?: 'Cashier@Medicare2026!',
                'role' => UserRole::CASHIER,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Brian Mutua (Reception Desk)',
                'email' => 'receptionist@medicare.local',
                'password' => $demoPasswordOverride ?: 'Receptionist@Medicare2026!',
                'role' => UserRole::RECEPTIONIST,
                'is_active' => true,
                'department_id' => $firstDepartment?->id,
            ],
            [
                'name' => 'Disabled Staff Member',
                'email' => 'inactive@medicare.local',
                'password' => $demoPasswordOverride ?: 'Inactive@Medicare2026!',
                'role' => UserRole::RECEPTIONIST,
                'is_active' => false,
                'department_id' => $firstDepartment?->id,
            ],
        ];

        foreach ($staffAccounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                    'is_active' => $account['is_active'],
                    'department_id' => $account['department_id'] ?? null,
                    'doctor_id' => $account['doctor_id'] ?? null,
                ]
            );
        }
    }
}
