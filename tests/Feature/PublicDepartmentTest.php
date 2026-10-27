<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_departments_overview_displays_active_departments_and_excludes_inactive(): void
    {
        $activeDept = Department::create([
            'name' => 'Cardiology Center',
            'slug' => 'cardiology-center',
            'summary' => 'Comprehensive heart care and diagnostics.',
            'description' => 'Detailed clinical cardiology description.',
            'services' => ['Echocardiogram', 'ECG'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactiveDept = Department::create([
            'name' => 'Decommissioned Unit',
            'slug' => 'decommissioned-unit',
            'summary' => 'This unit is currently closed.',
            'description' => 'No clinical services currently.',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $response = $this->get(route('departments'));

        $response->assertStatus(200);
        $response->assertSee('Cardiology Center');
        $response->assertSee('Comprehensive heart care and diagnostics.');
        $response->assertDontSee('Decommissioned Unit');
    }

    public function test_department_detail_resolves_valid_active_slug(): void
    {
        $dept = Department::create([
            'name' => 'Neurology Care',
            'slug' => 'neurology-care',
            'summary' => 'Specialized brain care.',
            'description' => 'Comprehensive neurological evaluations and stroke interventions.',
            'services' => ['EEG', 'MRI Screening'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('departments.show', $dept->slug));

        $response->assertStatus(200);
        $response->assertSee('Neurology Care');
        $response->assertSee('Comprehensive neurological evaluations');
        $response->assertSee('EEG');
    }

    public function test_inactive_or_invalid_department_slug_returns_404(): void
    {
        Department::create([
            'name' => 'Closed Department',
            'slug' => 'closed-department',
            'summary' => 'Closed',
            'description' => 'Closed',
            'is_active' => false,
        ]);

        // Inactive department must 404
        $this->get(route('departments.show', 'closed-department'))
            ->assertStatus(404);

        // Non-existent slug must 404
        $this->get(route('departments.show', 'completely-non-existent-slug'))
            ->assertStatus(404);
    }

    public function test_department_detail_only_displays_published_doctors(): void
    {
        $dept = Department::create([
            'name' => 'Pediatrics Wing',
            'slug' => 'pediatrics-wing',
            'summary' => 'Child health services.',
            'description' => 'Neonatal and pediatric care.',
            'is_active' => true,
        ]);

        $publishedDoctor = Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Approved Pediatrician',
            'slug' => 'dr-approved-pediatrician',
            'specialty' => 'Pediatric Medicine',
            'qualifications' => 'MBChB, MMed',
            'experience_years' => 10,
            'bio' => 'Experienced pediatrician.',
            'is_available' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $unpublishedDoctor = Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Secret Draft Doctor',
            'slug' => 'dr-secret-draft-doctor',
            'specialty' => 'Pediatric Surgery',
            'qualifications' => 'MBChB',
            'experience_years' => 2,
            'bio' => 'Draft bio pending approval.',
            'is_available' => true,
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->get(route('departments.show', $dept->slug));

        $response->assertStatus(200);
        $response->assertSee('Dr. Approved Pediatrician');
        $response->assertDontSee('Dr. Secret Draft Doctor');
    }

    public function test_department_overview_doctor_count_excludes_unpublished_doctors(): void
    {
        $dept = Department::create([
            'name' => 'Orthopedic Ward',
            'slug' => 'orthopedic-ward',
            'summary' => 'Joints and bones.',
            'description' => 'Orthopedic clinic.',
            'is_active' => true,
        ]);

        Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Verified Surgeon',
            'slug' => 'dr-verified-surgeon',
            'specialty' => 'Orthopedics',
            'qualifications' => 'MBChB, FCS',
            'experience_years' => 12,
            'bio' => 'Orthopedic surgeon.',
            'is_available' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Unverified Resident',
            'slug' => 'dr-unverified-resident',
            'specialty' => 'Orthopedics',
            'qualifications' => 'MBChB',
            'experience_years' => 1,
            'bio' => 'Resident.',
            'is_available' => true,
            'is_published' => false,
        ]);

        $response = $this->get(route('departments'));

        $response->assertStatus(200);
        $response->assertSee('1 Doctor');
        $response->assertDontSee('2 Doctors');
    }
}
