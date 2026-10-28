<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\User;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReconciliationVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Doctor::query()->delete();
        Department::query()->delete();
    }

    /**
     * Test that public department listings strictly require approved and published doctors.
     */
    public function test_public_departments_and_doctor_counts_strictly_require_approved_status(): void
    {
        $dept = Department::create([
            'name' => 'Cardiology Center',
            'slug' => 'cardiology-center',
            'summary' => 'Heart care',
            'description' => 'Heart care services',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Doctor 1: Approved, Published, Available -> MUST be visible
        Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Approved Visible',
            'slug' => 'dr-approved-visible',
            'specialty' => 'Cardiologist',
            'qualifications' => 'MBChB, MMed',
            'experience_years' => 10,
            'bio' => 'Senior cardiologist',
            'is_available' => true,
            'is_published' => true,
            'status' => 'approved',
            'published_at' => now(),
        ]);

        // Doctor 2: Pending Review -> MUST NOT be visible even if is_published=true accidentally
        Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Pending In Review',
            'slug' => 'dr-pending-in-review',
            'specialty' => 'Cardiologist',
            'qualifications' => 'MBChB',
            'experience_years' => 3,
            'bio' => 'New doctor in review',
            'is_available' => true,
            'is_published' => true,
            'status' => 'pending_review',
        ]);

        // Doctor 3: Draft -> MUST NOT be visible
        Doctor::create([
            'department_id' => $dept->id,
            'name' => 'Dr. Draft Doctor',
            'slug' => 'dr-draft-doctor',
            'specialty' => 'Cardiologist',
            'qualifications' => 'MBChB',
            'experience_years' => 2,
            'bio' => 'Draft bio',
            'is_available' => true,
            'is_published' => false,
            'status' => 'draft',
        ]);

        // Verify scope published returns only 1 doctor
        $this->assertEquals(1, Doctor::published()->count());

        // Verify departments listing displays count of 1 Doctor
        $response = $this->get(route('departments'));
        $response->assertOk();
        $response->assertSee('Cardiology Center');
        $response->assertSee('1 Doctor');
        $response->assertDontSee('3 Doctors');

        // Verify department detail displays only the approved doctor
        $detail = $this->get(route('departments.show', $dept->slug));
        $detail->assertOk();
        $detail->assertSee('Dr. Approved Visible');
        $detail->assertDontSee('Dr. Pending In Review');
        $detail->assertDontSee('Dr. Draft Doctor');
    }

    /**
     * Test that general intake bookings without department persist cleanly and dispatch in-app notification.
     */
    public function test_appointment_booking_without_department_stores_general_intake_and_dispatches_notification(): void
    {
        $receptionist = User::factory()->create([
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $payload = [
            'patient_name' => 'Hassan Mohammed',
            'patient_email' => 'hassan@example.com',
            'patient_phone' => '+254712345678',
            'department_id' => null,
            'doctor_id' => null,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '09:00 AM - 10:00 AM',
        ];

        $response = $this->post(route('appointments.store'), $payload);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify appointment recorded with NULL department and General Intake triage note
        $appointment = Appointment::where('patient_email', 'hassan@example.com')->first();
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->department_id);
        $this->assertNull($appointment->doctor_id);
        $this->assertStringContainsString('General Intake', (string) $appointment->triage_notes);

        // Verify database notification persisted for receptionist
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $receptionist->id,
            'notifiable_type' => User::class,
        ]);

        $notificationRow = DB::table('notifications')->where('notifiable_id', $receptionist->id)->first();
        $this->assertNotNull($notificationRow);
        $data = json_decode((string) $notificationRow->data, true);
        $this->assertEquals('appointment', $data['category']);
        $this->assertEquals('appointment.created', $data['event_type']);
        $this->assertStringContainsString('General Intake', $data['message']);
    }

    /**
     * Test that seeding hospital data creates exactly 6 departments and 6 approved published doctors.
     */
    public function test_seeding_hospital_data_populates_six_departments_and_six_published_doctors(): void
    {
        $this->seed(HospitalDataSeeder::class);

        $this->assertEquals(6, Department::where('is_active', true)->count());
        $this->assertEquals(6, Doctor::count());

        // Check each doctor has is_published = true, is_available = true, status = approved
        foreach (Doctor::all() as $doctor) {
            $this->assertTrue((bool) $doctor->is_available, "Doctor {$doctor->name} should be available");
            $this->assertTrue((bool) $doctor->is_published, "Doctor {$doctor->name} should be published");
            $this->assertEquals('approved', $doctor->status, "Doctor {$doctor->name} should be approved");
        }

        $this->assertEquals(6, Doctor::published()->count());

        // Verify public pages render them
        $deptResponse = $this->get(route('departments'));
        $deptResponse->assertOk();
        $deptResponse->assertSee('Cardiology & Vascular Medicine');
        $deptResponse->assertSee('Neurology & Brain Sciences');
        $deptResponse->assertSee('Pediatrics & Neonatal Care');
        $deptResponse->assertSee('Orthopedics & Joint Reconstruction');
        $deptResponse->assertSee('Oncology & Cancer Center');
        $deptResponse->assertSee('General Surgery & Minimally Invasive Procedures');

        $docResponse = $this->get(route('doctors'));
        $docResponse->assertOk();
        $docResponse->assertSee('Dr. Marcus Vance');
        $docResponse->assertSee('Dr. Amina Patel');
        $docResponse->assertSee('Dr. Elizabeth Moraa');
    }

    /**
     * Test that HospitalDataSeeder preserves existing users and articles without mutation.
     */
    public function test_seeding_hospital_data_preserves_existing_users_and_articles_without_mutation(): void
    {
        $preExistingUser = User::factory()->create([
            'email' => 'persisted-director@medicare.local',
            'name' => 'Dr. Persisted Director',
            'role' => UserRole::HOSPITAL_DIRECTOR,
            'is_active' => true,
        ]);

        $preExistingArticle = Article::create([
            'title' => 'Live Hospital Update Article',
            'slug' => 'live-hospital-update-article',
            'summary' => 'Summary of live announcement.',
            'body' => 'Full body text of live announcement.',
            'category' => 'Announcement',
            'author_name' => 'Communications Desk',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_by' => $preExistingUser->id,
            'published_by' => $preExistingUser->id,
        ]);

        $userCountBefore = User::count();
        $articleCountBefore = Article::count();

        // Run seeder
        $this->seed(HospitalDataSeeder::class);

        // Assert user and article counts are 100% unchanged
        $this->assertEquals($userCountBefore, User::count());
        $this->assertEquals($articleCountBefore, Article::count());

        // Assert existing records were not mutated
        $userAfter = User::find($preExistingUser->id);
        $this->assertEquals('persisted-director@medicare.local', $userAfter->email);
        $this->assertEquals(UserRole::HOSPITAL_DIRECTOR, $userAfter->role);

        $articleAfter = Article::find($preExistingArticle->id);
        $this->assertEquals('Live Hospital Update Article', $articleAfter->title);
        $this->assertEquals('published', $articleAfter->status);
    }

    /**
     * Test that database check constraint rejects negative stock in medicine_batches.
     */
    public function test_medicine_batches_check_constraint_rejects_negative_stock_quantity(): void
    {
        $medicine = Medicine::create([
            'code' => 'MED-TEST-CHK',
            'brand_name' => 'Amoxicillin 500',
            'generic_name' => 'Amoxicillin',
            'category' => 'Antibiotic',
            'dosage_form' => 'Capsule',
            'strength' => '500mg',
            'unit_of_measure' => 'capsules',
            'reorder_level' => 30,
            'is_active' => true,
        ]);

        // Negative quantity_current must throw QueryException due to CHECK constraint
        $this->expectException(QueryException::class);

        DB::table('medicine_batches')->insert([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-FAIL-NEG',
            'expiry_date' => '2027-12-31',
            'quantity_initial' => 100,
            'quantity_current' => -10, // Invalid: violates CHECK (quantity_current >= 0)
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test that article cover URL resolves public storage and degrades gracefully when file is absent.
     */
    public function test_article_cover_image_url_and_fallback_behavior(): void
    {
        Storage::fake('public');

        // 1. Valid storage file
        Storage::disk('public')->put('news-covers/valid-sample.jpg', 'fake-image-binary');

        $articleWithValidImage = Article::create([
            'title' => 'Article With Valid Image',
            'slug' => 'article-with-valid-image',
            'summary' => 'Summary text',
            'body' => 'Body text',
            'category' => 'Events',
            'author_name' => 'Staff Author',
            'cover_image_path' => 'news-covers/valid-sample.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertNotNull($articleWithValidImage->coverUrl());
        $this->assertStringContainsString('storage/news-covers/valid-sample.jpg', $articleWithValidImage->coverUrl());

        // 2. Article referencing missing file on disk degrades gracefully without throwing
        $articleWithMissingImage = Article::create([
            'title' => 'Article With Missing Image',
            'slug' => 'article-with-missing-image',
            'summary' => 'Summary text',
            'body' => 'Body text',
            'category' => 'Events',
            'author_name' => 'Staff Author',
            'cover_image_path' => 'news-covers/does-not-exist-on-disk.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // coverUrl returns null, which triggers Blade placeholder icon fallback
        $this->assertNull($articleWithMissingImage->coverUrl());
    }
}
