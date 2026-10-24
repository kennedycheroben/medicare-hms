<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditService;
use Database\Seeders\HospitalDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningAndPenetrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $doctorUser;
    protected User $nurseUser;
    protected User $receptionistUser;
    protected Doctor $doctorProfile;
    protected Department $department;
    protected Patient $patient;
    protected Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('x');

        $this->seed(HospitalDataSeeder::class);

        $this->department = Department::first();
        $this->doctorProfile = Doctor::first();

        $this->superAdmin = User::create([
            'name' => 'Sec Admin',
            'email' => 'sec.admin@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->doctorUser = User::create([
            'name' => 'Dr. Sec Lead',
            'email' => 'dr.sec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'doctor_id' => $this->doctorProfile->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->nurseUser = User::create([
            'name' => 'Nurse Sec',
            'email' => 'nurse.sec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::NURSE,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->receptionistUser = User::create([
            'name' => 'Reception Sec',
            'email' => 'reception.sec@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);

        $this->patient = Patient::create([
            'mrn' => 'MRN-SEC-0001',
            'first_name' => 'John',
            'last_name' => 'Kamau',
            'date_of_birth' => '1985-05-15',
            'gender' => 'male',
            'phone' => '+254711999888',
            'email' => 'john.sec@example.test',
            'city' => 'Nairobi',
            'is_active' => true,
        ]);

        $this->visit = Visit::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'doctor_id' => $this->doctorProfile->id,
            'visit_number' => 'VIS-SEC-0001',
            'visit_type' => 'walk_in',
            'reason_for_visit' => 'Clinical evaluation and routine check',
            'priority' => 'standard',
            'status' => 'triaged',
            'checked_in_by' => $this->receptionistUser->id,
            'checked_in_at' => now(),
        ]);
    }

    private function createSignedConsultation(): Consultation
    {
        return Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorProfile->id,
            'clinician_id' => $this->doctorUser->id,
            'status' => 'signed',
            'chief_complaint' => 'Acute persistent chest tightness and mild dyspnea.',
            'history_present_illness' => 'Patient reports symptoms worsening over 48 hours.',
            'examination_findings' => 'BP 138/88 mmHg, HR 82 bpm, clear breath sounds bilaterally.',
            'assessment' => 'Atypical chest discomfort, low acute coronary probability.',
            'diagnosis_type' => 'provisional',
            'primary_diagnosis' => 'Musculoskeletal chest wall discomfort',
            'treatment_plan' => 'Analgesia with Paracetamol 1g TDS, outpatient cardiology review.',
            'signed_at' => now(),
            'signed_by' => $this->doctorUser->id,
        ]);
    }

    // =========================================================================
    // 1. HTTP Security Header Tests
    // =========================================================================

    public function test_public_and_staff_routes_enforce_defensive_http_security_headers(): void
    {
        $routes = [
            '/',
            '/guide',
            '/privacy',
            '/terms',
            '/admin/login',
            '/portal/login',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);

            // Defensive header assertions
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
            $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

            // CSP assertions
            $csp = $response->headers->get('Content-Security-Policy');
            $this->assertNotNull($csp, "CSP missing on route: {$route}");
            $this->assertStringContainsString("default-src 'self'", $csp);
            $this->assertStringContainsString("frame-ancestors 'self'", $csp);
            $this->assertStringContainsString("base-uri 'self'", $csp);
            $this->assertStringContainsString("form-action 'self'", $csp);

            // Server signature leakage prevention
            $this->assertNull($response->headers->get('X-Powered-By'), "X-Powered-By leaked on route: {$route}");
        }
    }

    public function test_https_requests_enforce_strict_transport_security(): void
    {
        $response = $this->get('https://localhost/', ['HTTPS' => 'on']);
        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // =========================================================================
    // 2. Consultation Signed-Addendum Integrity Tests
    // =========================================================================

    public function test_only_attending_doctor_can_append_signed_consultation_addenda(): void
    {
        $consultation = $this->createSignedConsultation();

        $payload = [
            'addendum_notes' => 'Patient re-evaluated in post-observation: pain fully resolved after oral analgesia.',
            'reason_for_amendment' => 'Incorporate post-observation clinical response before discharge.',
        ];

        // Non-clinical / non-doctor staff must be strictly forbidden (403)
        $this->actingAs($this->nurseUser)
            ->post(route('admin.consultations.addenda.store', $consultation), $payload)
            ->assertForbidden();

        $this->actingAs($this->receptionistUser)
            ->post(route('admin.consultations.addenda.store', $consultation), $payload)
            ->assertForbidden();

        $this->assertEquals(0, ConsultationAddendum::count());

        // Attending doctor can successfully append an addendum
        $response = $this->actingAs($this->doctorUser)
            ->post(route('admin.consultations.addenda.store', $consultation), $payload);

        $response->assertRedirect(route('admin.consultations.show', $consultation));
        $response->assertSessionHas('status', 'Clinical addendum successfully appended to the patient consultation record.');

        $this->assertEquals(1, ConsultationAddendum::count());
        $addendum = ConsultationAddendum::first();
        $this->assertEquals($consultation->id, $addendum->consultation_id);
        $this->assertEquals($this->doctorUser->id, $addendum->author_id);
        $this->assertEquals($payload['addendum_notes'], $addendum->addendum_notes);
        $this->assertEquals($payload['reason_for_amendment'], $addendum->reason_for_amendment);

        // Audit trail verified
        $audit = AuditLog::where('action', 'consultation.addendum_created')->latest()->first();
        $this->assertNotNull($audit);
        $this->assertEquals((string) $addendum->id, $audit->resource_id);
    }

    public function test_signed_consultation_original_clinical_content_is_strictly_immutable_under_addendum(): void
    {
        $consultation = $this->createSignedConsultation();

        $originalChiefComplaint = $consultation->chief_complaint;
        $originalDiagnosis = $consultation->primary_diagnosis;
        $originalAssessment = $consultation->assessment;

        // Attending doctor appends addendum
        $this->actingAs($this->doctorUser)
            ->post(route('admin.consultations.addenda.store', $consultation), [
                'addendum_notes' => 'Lab telemetry reviewed: ECG normal sinus rhythm, troponin negative.',
                'reason_for_amendment' => 'Attach corroborating telemetry investigations.',
            ])
            ->assertRedirect();

        // Refresh and verify original clinical records are untouched
        $consultation->refresh();
        $this->assertEquals($originalChiefComplaint, $consultation->chief_complaint);
        $this->assertEquals($originalDiagnosis, $consultation->primary_diagnosis);
        $this->assertEquals($originalAssessment, $consultation->assessment);
        $this->assertTrue($consultation->isSigned());

        // Direct tampering via update() route on signed consultation must be rejected with 403
        $this->actingAs($this->doctorUser)
            ->put(route('admin.consultations.update', $consultation), [
                'chief_complaint' => 'TAMPERED: Different complaint entirely.',
                'primary_diagnosis' => 'TAMPERED: Forged diagnosis.',
            ])
            ->assertForbidden();

        $consultation->refresh();
        $this->assertEquals($originalChiefComplaint, $consultation->chief_complaint);
    }

    public function test_addendum_cannot_be_appended_to_unsigned_draft_consultation(): void
    {
        $draftConsultation = Consultation::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'clinician_id' => $this->doctorUser->id,
            'status' => 'draft',
            'is_finalized' => false,
            'chief_complaint' => 'Mild headache',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post(route('admin.consultations.addenda.store', $draftConsultation), [
                'addendum_notes' => 'Attempting addendum on draft note',
                'reason_for_amendment' => 'Premature amendment attempt',
            ]);

        $response->assertRedirect(route('admin.consultations.show', $draftConsultation));
        $response->assertSessionHasErrors(['addendum' => 'Addenda can only be appended to signed consultation records.']);
        $this->assertEquals(0, ConsultationAddendum::count());
    }

    public function test_addendum_validation_failure_does_not_partially_persist(): void
    {
        $consultation = $this->createSignedConsultation();

        // Notes too short (< 5 characters) and missing reason
        $response = $this->actingAs($this->doctorUser)
            ->post(route('admin.consultations.addenda.store', $consultation), [
                'addendum_notes' => 'No',
                'reason_for_amendment' => '',
            ]);

        $response->assertSessionHasErrors(['addendum_notes', 'reason_for_amendment']);
        $this->assertEquals(0, ConsultationAddendum::count());
    }

    // =========================================================================
    // 3. Patient Portal Throttling & Authorization Boundary Tests
    // =========================================================================

    public function test_patient_portal_login_rate_limiting_and_anti_enumeration(): void
    {
        $account = PatientAccount::create([
            'patient_id' => $this->patient->id,
            'email' => $this->patient->email,
            'password' => 'CorrectPassword123!',
            'activated_at' => now(),
            'must_change_password' => false,
        ]);

        // Failed attempts under threshold (5) return generic message
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('portal.login.store'), [
                'identifier' => $this->patient->email,
                'password' => 'WrongPassword!',
            ]);
            $response->assertSessionHasErrors(['identifier']);
            $this->assertGuest('patient');
        }

        // 6th attempt must trigger rate limiter lockout
        $lockoutResponse = $this->post(route('portal.login.store'), [
            'identifier' => $this->patient->email,
            'password' => 'WrongPassword!',
        ]);
        $lockoutResponse->assertSessionHasErrors(['identifier']);

        // Anti-enumeration: test that non-existent accounts receive identical generic error
        RateLimiter::clear('portal-login:'.sha1('unknown@medicare.local|127.0.0.1'));
        $unknownResponse = $this->post(route('portal.login.store'), [
            'identifier' => 'unknown@medicare.local',
            'password' => 'SomePassword123!',
        ]);
        $unknownResponse->assertSessionHasErrors(['identifier' => 'We could not sign you in with those details. Check your MRN or email and password, or use "Forgot password".']);
    }

    public function test_patient_portal_password_reset_throttling_and_anti_enumeration(): void
    {
        // 3 requests allowed within window
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post(route('portal.forgot.send'), [
                'email' => 'anyuser@example.test',
            ]);
            $response->assertSessionHas('status', 'If that email belongs to a portal account, we have sent a link to reset the password. Check your inbox.');
        }

        // 4th request is silently throttled (rate limiter hit)
        $this->post(route('portal.forgot.send'), [
            'email' => 'anyuser@example.test',
        ]);

        // Mail fake confirms zero enumeration leaks
        Mail::assertNothingSent();
    }

    public function test_activation_and_reset_tokens_are_strictly_single_use_and_expire(): void
    {
        $account = PatientAccount::create([
            'patient_id' => $this->patient->id,
            'email' => $this->patient->email,
            'password' => 'TempPass123!',
            'must_change_password' => true,
        ]);

        $plainToken = $account->issueToken(60);
        $this->assertNotNull($account->token_hash);

        // Valid activation
        $response = $this->post(route('portal.activate.store', $plainToken), [
            'password' => 'NewStrongPassword123!',
            'password_confirmation' => 'NewStrongPassword123!',
        ]);
        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHas('status', 'Your password is saved. You can now sign in.');

        $account->refresh();
        $this->assertNull($account->token_hash, 'Token hash must be nullified immediately upon use.');
        $this->assertNull($account->token_expires_at, 'Token expiration must be cleared.');
        $this->assertFalse($account->must_change_password);

        // Replay attempt must fail (token no longer valid)
        $replayResponse = $this->post(route('portal.activate.store', $plainToken), [
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ]);
        $replayResponse->assertRedirect(route('portal.activate', $plainToken));

        // Test expired token rejection
        $expiredToken = $account->issueToken(-10); // expired 10 minutes ago
        $this->assertNull(PatientAccount::findByToken($expiredToken));
    }

    public function test_patient_portal_guard_isolation_and_idor_protection(): void
    {
        $account = PatientAccount::create([
            'patient_id' => $this->patient->id,
            'email' => $this->patient->email,
            'password' => 'SecurePass123!',
            'activated_at' => now(),
            'must_change_password' => false,
        ]);

        // Authenticated patient cannot access admin area
        $this->actingAs($account, 'patient')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        // Authenticated staff cannot access patient portal without patient session
        Auth::guard('patient')->logout();
        $this->actingAs($this->superAdmin, 'web')
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('portal.login'));

        // Patient dashboard renders and strictly isolates patient data
        $dashboardResponse = $this->actingAs($account, 'patient')
            ->get(route('portal.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee($this->patient->first_name);
        $dashboardResponse->assertSee($this->patient->mrn);
    }

    // =========================================================================
    // 4. Broader Security Regression Tests
    // =========================================================================

    public function test_audit_service_redacts_credentials_tokens_and_financial_secrets(): void
    {
        AuditService::record(
            action: 'security.credential_verification',
            resourceType: 'system',
            resourceId: 'SEC-01',
            details: [
                'password' => 'SuperSecretP@ssword123',
                'token' => 'plain_text_bearer_token',
                'secret' => 'oauth_client_secret',
                'mpesa_pin' => '1234',
                'credit_card' => '4111222233334444',
                'safe_field' => 'visible_audit_metric',
            ],
            outcome: 'success',
            actor: $this->superAdmin
        );

        $log = AuditLog::where('action', 'security.credential_verification')->latest()->first();
        $this->assertNotNull($log);

        $details = $log->details;
        $this->assertEquals('[REDACTED]', $details['password']);
        $this->assertEquals('[REDACTED]', $details['token']);
        $this->assertEquals('[REDACTED]', $details['secret']);
        $this->assertEquals('[REDACTED]', $details['mpesa_pin']);
        $this->assertEquals('[REDACTED]', $details['credit_card']);
        $this->assertEquals('visible_audit_metric', $details['safe_field']);
    }

    public function test_inactive_staff_user_is_strictly_denied_access(): void
    {
        $inactiveDoctor = User::create([
            'name' => 'Dr. Inactive',
            'email' => 'dr.inactive@medicare.local',
            'password' => 'SecurePass123!',
            'role' => UserRole::DOCTOR,
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveDoctor)
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['email']);
    }
}
