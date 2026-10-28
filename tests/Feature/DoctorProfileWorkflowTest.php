<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorProfileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected User $superAdmin;

    protected User $doctorUser;

    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Internal Medicine',
            'slug' => 'internal-medicine',
            'summary' => 'Comprehensive internal medicine diagnosis and care.',
            'description' => 'Clinical diagnosis, management and non-surgical treatment of unusual or serious diseases.',
            'services' => ['Preventive Health', 'Chronic Disease Management'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->doctor = Doctor::create([
            'department_id' => $this->department->id,
            'name' => 'Dr. Jane Muthoni',
            'slug' => 'dr-jane-muthoni',
            'specialty' => 'Internal Medicine Specialist',
            'qualifications' => 'MBChB, MMed',
            'experience_years' => 8,
            'bio' => 'Experienced clinician specializing in chronic care.',
            'is_available' => true,
            'is_published' => false,
            'status' => 'draft',
        ]);

        $this->doctorUser = User::factory()->create([
            'name' => 'Dr. Jane Muthoni',
            'email' => 'jane.muthoni@medicare.local',
            'role' => UserRole::DOCTOR,
            'is_active' => true,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Admin Cheroben',
            'email' => 'admin@medicare.local',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_doctor_can_view_their_own_profile_editor(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get(route('doctor.profile.edit'));

        $response->assertOk();
        $response->assertSee('Doctor Profile Management');
        $response->assertSee('Dr. Jane Muthoni');
        $response->assertSee('Internal Medicine Specialist');
    }

    public function test_non_doctor_cannot_access_doctor_profile_editor(): void
    {
        $receptionist = User::factory()->create([
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $response = $this->actingAs($receptionist)
            ->get(route('doctor.profile.edit'));

        $response->assertForbidden();
    }

    public function test_doctor_can_save_draft_profile_and_remains_unpublished(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr. Jane Muthoni',
                'department_id' => $this->department->id,
                'specialty' => 'Consultant Internist',
                'qualifications' => 'MBChB, MMed (UoN), FCP',
                'experience_years' => 9,
                'bio' => 'Consultant Physician with passion for holistic patient-centered care.',
                'languages' => 'English, Swahili',
                'consultation_days' => 'Mon, Wed, Fri',
                'consultation_hours' => '09:00 - 16:00',
                'room_number' => 'Suite 101',
                'is_available' => 1,
                'action' => 'save_draft',
            ]);

        $response->assertRedirect(route('doctor.profile.edit'));
        $this->doctor->refresh();

        $this->assertEquals('draft', $this->doctor->status);
        $this->assertFalse((bool) $this->doctor->is_published);
        $this->assertEquals('Consultant Internist', $this->doctor->specialty);
    }

    public function test_doctor_submitting_for_review_sets_status_to_pending_review_and_prevents_self_publishing(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr. Jane Muthoni',
                'department_id' => $this->department->id,
                'specialty' => 'Consultant Internist',
                'qualifications' => 'MBChB, MMed (UoN), FCP',
                'experience_years' => 9,
                'bio' => 'Consultant Physician with passion for holistic patient-centered care.',
                'languages' => 'English, Swahili',
                'is_available' => 1,
                'action' => 'submit_review',
                // Malicious payload attempt to self-publish
                'is_published' => 1,
                'status' => 'approved',
                'approved_by' => $this->doctorUser->id,
            ]);

        $response->assertRedirect(route('doctor.profile.edit'));
        $this->doctor->refresh();

        $this->assertEquals('pending_review', $this->doctor->status);
        $this->assertFalse((bool) $this->doctor->is_published);
        $this->assertNull($this->doctor->approved_by);

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'doctor.profile_submitted',
            'resource_id' => (string) $this->doctor->id,
            'user_id' => $this->doctorUser->id,
        ]);
    }

    public function test_super_admin_can_view_pending_profiles_in_review_queue(): void
    {
        $this->doctor->update([
            'status' => 'pending_review',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.doctors.index', ['status' => 'pending_review']));

        $response->assertOk();
        $response->assertSee('Dr. Jane Muthoni');
        $response->assertSee('Pending Review');
    }

    public function test_super_admin_can_approve_profile_for_public_listing(): void
    {
        $this->doctor->update([
            'status' => 'pending_review',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.approve', $this->doctor->id), [
                'publication_notes' => 'Credentials verified by Medical Council. Approved.',
            ]);

        $response->assertRedirect();
        $this->doctor->refresh();

        $this->assertEquals('approved', $this->doctor->status);
        $this->assertTrue((bool) $this->doctor->is_published);
        $this->assertEquals($this->superAdmin->id, $this->doctor->approved_by);
        $this->assertNotNull($this->doctor->published_at);

        // Doctor is now visible on public doctors listing
        $publicListing = $this->get(route('doctors'));
        $publicListing->assertOk();
        $publicListing->assertSee('Dr. Jane Muthoni');

        // Doctor is visible on public detail page
        $publicDetail = $this->get(route('doctors.show', $this->doctor->id));
        $publicDetail->assertOk();
        $publicDetail->assertSee('Dr. Jane Muthoni');
    }

    public function test_super_admin_cannot_approve_incomplete_profile(): void
    {
        $incompleteDoctor = Doctor::create([
            'name' => 'Incomplete Doctor',
            'slug' => 'incomplete-doctor',
            'department_id' => $this->department->id,
            'specialty' => '',
            'qualifications' => '',
            'bio' => '',
            'is_available' => true,
            'is_published' => false,
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.approve', $incompleteDoctor->id));

        $response->assertSessionHasErrors(['publication']);
        $incompleteDoctor->refresh();

        $this->assertFalse((bool) $incompleteDoctor->is_published);
        $this->assertEquals('pending_review', $incompleteDoctor->status);
    }

    public function test_super_admin_can_return_profile_for_correction_with_feedback(): void
    {
        $this->doctor->update([
            'status' => 'pending_review',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.return', $this->doctor->id), [
                'publication_notes' => 'Please provide full certification year and medical board registration number.',
            ]);

        $response->assertRedirect();
        $this->doctor->refresh();

        $this->assertEquals('needs_revision', $this->doctor->status);
        $this->assertFalse((bool) $this->doctor->is_published);
        $this->assertEquals('Please provide full certification year and medical board registration number.', $this->doctor->publication_notes);

        // Doctor views feedback in editor
        $doctorEditor = $this->actingAs($this->doctorUser)->get(route('doctor.profile.edit'));
        $doctorEditor->assertOk();
        $doctorEditor->assertSee('Reviewer Feedback');
        $doctorEditor->assertSee('Please provide full certification year');

        // Unpublished doctor returns 404 on public detail route
        $publicDetail = $this->get(route('doctors.show', $this->doctor->id));
        $publicDetail->assertNotFound();
    }

    public function test_editing_already_published_profile_triggers_re_review_and_unpublishes(): void
    {
        $this->doctor->update([
            'status' => 'approved',
            'is_published' => true,
            'published_at' => now(),
            'approved_by' => $this->superAdmin->id,
        ]);

        // Verify publicly visible before edit
        $this->get(route('doctors.show', $this->doctor->id))->assertOk();

        // Doctor makes a material update
        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr. Jane Muthoni, MD',
                'department_id' => $this->department->id,
                'specialty' => 'Senior Consultant Internist',
                'qualifications' => 'MBChB, MMed, Fellowship in Endocrinology',
                'experience_years' => 10,
                'bio' => 'Updated professional biography with new subspecialty.',
                'is_available' => 1,
                'action' => 'submit_review',
            ]);

        $response->assertRedirect(route('doctor.profile.edit'));
        $this->doctor->refresh();

        $this->assertEquals('pending_review', $this->doctor->status);
        $this->assertFalse((bool) $this->doctor->is_published);

        // Must now be hidden from public profile route until re-approved
        $this->get(route('doctors.show', $this->doctor->id))->assertNotFound();
    }

    public function test_doctor_can_upload_jpg_and_png_photos(): void
    {
        Storage::fake('public');

        $jpgFile = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr. Jane Muthoni',
                'department_id' => $this->department->id,
                'specialty' => 'Internal Medicine Specialist',
                'qualifications' => 'MBChB, MMed',
                'experience_years' => 8,
                'bio' => 'Experienced clinician specializing in chronic care.',
                'is_available' => 1,
                'image_file' => $jpgFile,
                'action' => 'save_draft',
            ]);

        $response->assertSessionHasNoErrors();
        $this->doctor->refresh();

        $this->assertNotNull($this->doctor->image);
        $this->assertStringEndsWith('.jpg', $this->doctor->image);
        Storage::disk('public')->assertExists($this->doctor->image);
        $this->assertStringContainsString('storage/'.$this->doctor->image, $this->doctor->imageUrl());
    }

    public function test_system_rejects_unsupported_non_image_file_uploads(): void
    {
        Storage::fake('public');

        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->doctorUser)
            ->put(route('doctor.profile.update'), [
                'name' => 'Dr. Jane Muthoni',
                'department_id' => $this->department->id,
                'specialty' => 'Internal Medicine Specialist',
                'qualifications' => 'MBChB, MMed',
                'experience_years' => 8,
                'bio' => 'Experienced clinician specializing in chronic care.',
                'is_available' => 1,
                'image_file' => $fakePdf,
                'action' => 'save_draft',
            ]);

        $response->assertSessionHasErrors('image_file');
    }
}
