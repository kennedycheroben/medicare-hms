<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorPublicationAndReviewsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $receptionist;
    private Department $department;
    private Doctor $doctor;
    private Patient $patient;
    private Visit $completedVisit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HospitalDataSeeder::class);

        $this->superAdmin = User::create([
            'name' => 'Dr. Administrator',
            'email' => 'admin.doc@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Receptionist Alice',
            'email' => 'alice.rec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->department = Department::first();

        $this->doctor = Doctor::create([
            'department_id' => $this->department->id,
            'name' => 'Dr. Jane Muthoni, MD',
            'slug' => 'dr-jane-muthoni',
            'specialty' => 'Consultant Pediatrician',
            'qualifications' => 'MBChB, MMed (Pediatrics)',
            'experience_years' => 12,
            'bio' => 'Experienced pediatrician with extensive experience in pediatric critical care.',
            'is_available' => true,
            'is_published' => false, // Initial state: unapproved draft
            'rating' => 0.0,
            'review_count' => 0,
        ]);

        $this->patient = Patient::create([
            'mrn' => 'MRN-REV-001',
            'first_name' => 'Faith',
            'last_name' => 'Wambui',
            'date_of_birth' => '1995-04-12',
            'gender' => 'female',
            'phone' => '+254711223344',
            'is_active' => true,
            'registered_by' => $this->receptionist->id,
        ]);

        $this->completedVisit = Visit::create([
            'visit_number' => 'VST-REV-9001',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'appointment',
            'reason_for_visit' => 'Child routine checkup',
            'priority' => 'standard',
            'status' => 'completed',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subDays(2),
        ]);
    }

    public function test_unpublished_doctor_is_hidden_from_public_directory_and_direct_url_returns_404(): void
    {
        // 1. Directory does not list unpublished doctor
        $dirResponse = $this->get(route('doctors'));
        $dirResponse->assertOk();
        $dirResponse->assertDontSee($this->doctor->name);

        // 2. Direct URL access to unpublished doctor returns 404
        $profileResponse = $this->get(route('doctors.show', $this->doctor->id));
        $profileResponse->assertNotFound();
    }

    public function test_super_admin_can_publish_and_unpublish_doctor_profile(): void
    {
        // 1. SuperAdmin approves and publishes doctor profile
        $publishResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.publish', $this->doctor->id), [
                'publication_notes' => 'Medical board credentials verified',
            ]);

        $publishResponse->assertRedirect();
        $this->assertTrue($this->doctor->fresh()->is_published);

        // 2. Doctor now appears on public directory and profile page
        $publicProfileResponse = $this->get(route('doctors.show', $this->doctor->id));
        $publicProfileResponse->assertOk();
        $publicProfileResponse->assertSee($this->doctor->name);

        // 3. SuperAdmin can unpublish profile
        $unpublishResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.unpublish', $this->doctor->id));

        $unpublishResponse->assertRedirect();
        $this->assertFalse($this->doctor->fresh()->is_published);

        // 4. Doctor is once again 404 to the public
        $this->get(route('doctors.show', $this->doctor->id))->assertNotFound();
    }

    public function test_unauthorized_staff_cannot_publish_doctor_profile(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->post(route('admin.doctors.publish', $this->doctor->id));

        $response->assertForbidden();
        $this->assertFalse($this->doctor->fresh()->is_published);
    }

    public function test_verified_patient_with_completed_visit_submits_review_and_duplicate_is_prevented(): void
    {
        // Publish doctor first
        $this->doctor->update(['is_published' => true]);

        // 1. Submit valid review with anonymous preference
        $reviewData = [
            'mrn' => $this->patient->mrn,
            'phone' => '0711223344', // Local Kenyan format, normalized by rule
            'visit_number' => $this->completedVisit->visit_number,
            'rating' => 5,
            'comment' => 'Dr. Muthoni was extremely compassionate, patient, and detailed in her clinical explanation.',
            'display_name_type' => 'anonymous',
            'public_consent' => 1,
        ];

        $response = $this->post(route('doctors.reviews.store', $this->doctor->id), $reviewData);
        $response->assertRedirect();
        $response->assertSessionHas('review_status');

        $this->assertDatabaseHas('doctor_reviews', [
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->completedVisit->id,
            'rating' => 5,
            'status' => 'pending_moderation',
            'display_name_type' => 'anonymous',
        ]);

        /** @var DoctorReview $review */
        $review = DoctorReview::where('visit_id', $this->completedVisit->id)->first();
        $this->assertEquals('Verified Patient (Anonymous)', $review->public_author_name);

        // 2. Duplicate submission for the same clinical visit encounter is rejected
        $dupResponse = $this->post(route('doctors.reviews.store', $this->doctor->id), $reviewData);
        $dupResponse->assertRedirect();
        $dupResponse->assertSessionHasErrors(['visit_number']);

        $this->assertEquals(1, DoctorReview::where('visit_id', $this->completedVisit->id)->count());
    }

    public function test_review_submission_rejects_uncompleted_visit_or_mismatched_identity(): void
    {
        $this->doctor->update(['is_published' => true]);

        $waitingVisit = Visit::create([
            'visit_number' => 'VST-WAIT-101',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Fever',
            'priority' => 'standard',
            'status' => 'waiting_triage', // NOT completed
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now(),
        ]);

        $response = $this->post(route('doctors.reviews.store', $this->doctor->id), [
            'mrn' => $this->patient->mrn,
            'phone' => '+254711223344',
            'visit_number' => $waitingVisit->visit_number,
            'rating' => 5,
            'comment' => 'Great initial checkup experience.',
            'display_name_type' => 'anonymous',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['visit_number']);
        $this->assertDatabaseMissing('doctor_reviews', ['visit_id' => $waitingVisit->id]);
    }

    public function test_consent_policy_determines_author_name_display(): void
    {
        // Review with explicit consent and full_name preference
        $consentReview = DoctorReview::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->completedVisit->id,
            'rating' => 5,
            'comment' => 'Excellent doctor.',
            'public_consent' => true,
            'display_name_type' => 'full_name',
            'status' => 'approved',
        ]);

        $this->assertEquals('Faith Wambui', $consentReview->public_author_name);

        $visit2 = Visit::create([
            'visit_number' => 'VST-REV-9002',
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'appointment',
            'reason_for_visit' => 'Followup',
            'priority' => 'standard',
            'status' => 'completed',
            'checked_in_by' => $this->receptionist->id,
            'checked_in_at' => now()->subDay(),
        ]);

        // Without consent, full_name must NOT be displayed
        $noConsentReview = DoctorReview::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $visit2->id,
            'rating' => 4,
            'comment' => 'Good consultation.',
            'public_consent' => false,
            'display_name_type' => 'full_name',
            'status' => 'approved',
        ]);

        $this->assertEquals('Verified Patient (Anonymous)', $noConsentReview->public_author_name);
    }

    public function test_admin_approves_review_and_official_rating_is_recalculated(): void
    {
        $this->doctor->update(['is_published' => true]);

        $review = DoctorReview::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->completedVisit->id,
            'rating' => 5,
            'comment' => 'Outstanding clinical empathy and thoroughness.',
            'public_consent' => true,
            'display_name_type' => 'full_name',
            'status' => 'pending_moderation',
        ]);

        // Pending review does not affect official rating
        $this->assertEquals(0, $this->doctor->fresh()->review_count);

        // Approve review
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.doctors.reviews.approve', $review->id));

        $response->assertRedirect();
        $this->assertEquals('approved', $review->fresh()->status);
        $this->assertEquals(1, $this->doctor->fresh()->review_count);
        $this->assertEquals(5.0, $this->doctor->fresh()->rating);

        // Public profile displays approved review
        $profileResponse = $this->get(route('doctors.show', $this->doctor->id));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Faith Wambui');
        $profileResponse->assertSee('Outstanding clinical empathy and thoroughness.');
    }
}
