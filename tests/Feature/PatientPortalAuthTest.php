<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\PortalLinkMail;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PatientPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Portal-Pass-2026';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('x');
    }

    private function staff(UserRole $role): User
    {
        return User::create([
            'name' => 'S '.$role->value,
            'email' => $role->value.'@portal.test',
            'password' => 'SecurePass123!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function patient(string $mrn = 'MRN-1001', ?string $email = 'jane@example.test'): Patient
    {
        return Patient::create([
            'mrn' => $mrn,
            'first_name' => 'Jane',
            'last_name' => 'Wanjiru',
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
            'phone' => '+254712345678',
            'email' => $email,
            'city' => 'Nairobi',
        ]);
    }

    /** Issue access as reception and return the plain token from the sent mail. */
    private function issueToken(Patient $patient): string
    {
        $this->actingAs($this->staff(UserRole::RECEPTIONIST))
            ->post(route('admin.patients.portal-access', $patient))
            ->assertRedirect();

        $token = null;
        Mail::assertSent(PortalLinkMail::class, function ($m) use (&$token) {
            $token = $m->token;

            return true;
        });
        Auth::guard('web')->logout();

        return $token;
    }

    private function activate(string $token, string $password = self::PASSWORD)
    {
        return $this->post(route('portal.activate.store', $token), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_activation_link_is_single_use_hashed_and_sets_password(): void
    {
        $patient = $this->patient();
        $token = $this->issueToken($patient);

        $account = PatientAccount::firstOrFail();
        $this->assertNotSame($token, $account->token_hash);
        $this->assertSame(hash('sha256', $token), $account->token_hash);
        $this->assertNull($account->password);

        $this->get(route('portal.activate', $token))->assertOk()->assertSee('Create your password');
        $this->activate($token)->assertRedirect(route('portal.login'));

        $account->refresh();
        $this->assertTrue(Hash::check(self::PASSWORD, $account->password));
        $this->assertNull($account->token_hash);
        $this->assertNotNull($account->activated_at);

        // Replay fails.
        $this->get(route('portal.activate', $token))->assertSee('cannot be used');
        $this->activate($token, 'Another-Pass-9999')->assertRedirect(route('portal.activate', $token));
        $this->assertTrue(Hash::check(self::PASSWORD, $account->fresh()->password));
    }

    public function test_expired_link_and_weak_password_are_rejected(): void
    {
        $token = $this->issueToken($this->patient());

        $this->activate($token, 'short1')->assertSessionHasErrors('password');
        $this->activate($token, 'onlyletterslong')->assertSessionHasErrors('password');
        $this->assertNull(PatientAccount::first()->password);

        PatientAccount::query()->update(['token_expires_at' => now()->subMinute()]);
        $this->get(route('portal.activate', $token))->assertSee('cannot be used');
        $this->activate($token)->assertRedirect(route('portal.activate', $token));
        $this->assertNull(PatientAccount::first()->password);
    }

    public function test_login_by_mrn_and_email_then_dashboard_shows_only_own_data(): void
    {
        $a = $this->patient('MRN-1001', 'jane@example.test');
        $b = Patient::create([
            'mrn' => 'MRN-2002', 'first_name' => 'Other', 'last_name' => 'Person',
            'date_of_birth' => '1985-05-05', 'gender' => 'male', 'phone' => '+254700000001',
            'email' => 'other@example.test', 'city' => 'Nairobi',
        ]);
        $this->activate($this->issueToken($a));

        $this->post(route('portal.login.store'), ['identifier' => 'mrn-1001', 'password' => self::PASSWORD])
            ->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Hello, Jane')->assertSee('MRN-1001')
            ->assertDontSee('MRN-2002')->assertDontSee('Other');

        $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));

        $this->post(route('portal.login.store'), ['identifier' => 'JANE@example.test', 'password' => self::PASSWORD])
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_login_errors_are_generic_and_inactive_accounts_cannot_sign_in(): void
    {
        $this->activate($this->issueToken($this->patient()));

        $wrongPass = $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => 'Wrong-Pass-1234']);
        $wrongPass->assertSessionHasErrors('identifier');
        $wrongMsg = session('errors')->first('identifier');

        $unknown = $this->post(route('portal.login.store'), ['identifier' => 'MRN-9999', 'password' => 'Wrong-Pass-1234']);
        $unknown->assertSessionHasErrors('identifier');
        $this->assertSame($wrongMsg, session('errors')->first('identifier'));
        $this->assertGuest('patient');

        Patient::query()->update(['is_active' => false]);
        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest('patient');
    }

    public function test_unactivated_account_cannot_log_in(): void
    {
        $this->issueToken($this->patient());

        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => 'anything-long-1'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest('patient');
    }

    public function test_login_is_rate_limited(): void
    {
        $this->activate($this->issueToken($this->patient()));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => 'Wrong-Pass-1234']);
        }
        // Even the correct password is refused while locked out.
        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest('patient');
    }

    public function test_forced_password_change_blocks_dashboard_until_done(): void
    {
        $this->activate($this->issueToken($this->patient()));
        PatientAccount::query()->update(['must_change_password' => true]);

        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => self::PASSWORD]);
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.password.edit'));
        $this->get(route('portal.password.edit'))->assertOk();

        $this->put(route('portal.password.update'), [
            'current_password' => 'Wrong-Pass-1234',
            'password' => 'Brand-New-Pass-77', 'password_confirmation' => 'Brand-New-Pass-77',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('portal.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ])->assertSessionHasErrors('password');

        $this->put(route('portal.password.update'), [
            'current_password' => self::PASSWORD,
            'password' => 'Brand-New-Pass-77', 'password_confirmation' => 'Brand-New-Pass-77',
        ])->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.dashboard'))->assertOk();
    }

    public function test_forgot_password_is_generic_and_reset_link_works_once(): void
    {
        $this->activate($this->issueToken($this->patient()));
        Mail::fake(); // reset sent-mail tracking

        $known = $this->post(route('portal.forgot.send'), ['email' => 'jane@example.test']);
        $unknown = $this->post(route('portal.forgot.send'), ['email' => 'nobody@example.test']);
        $this->assertSame(session('status'), $unknown->baseResponse->getSession()->get('status'));
        Mail::assertSent(PortalLinkMail::class, 1);

        $token = null;
        Mail::assertSent(PortalLinkMail::class, function ($m) use (&$token) {
            $token = $m->token;

            return $m->isReset;
        });

        $this->post(route('portal.activate.store', $token), [
            'password' => 'Reset-Pass-5555', 'password_confirmation' => 'Reset-Pass-5555',
        ])->assertRedirect(route('portal.login'));
        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => 'Reset-Pass-5555'])
            ->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.activate', $token))->assertSee('cannot be used');
    }

    public function test_staff_issue_requires_email_and_proper_role_and_never_exposes_link(): void
    {
        $noEmail = $this->patient('MRN-3003', null);

        $this->actingAs($this->staff(UserRole::RECEPTIONIST))
            ->post(route('admin.patients.portal-access', $noEmail))
            ->assertSessionHasErrors('portal');
        Mail::assertNothingSent();

        foreach ([UserRole::NURSE, UserRole::PHARMACIST, UserRole::DOCTOR] as $role) {
            $this->actingAs($this->staff($role))
                ->post(route('admin.patients.portal-access', $noEmail))->assertForbidden();
        }

        auth()->guard('web')->logout();
        $this->post(route('admin.patients.portal-access', $noEmail))->assertRedirect();

        $with = $this->patient('MRN-4004', 'x@example.test');
        $resp = $this->actingAs($this->staff(UserRole::SUPER_ADMIN))
            ->followingRedirects()->post(route('admin.patients.portal-access', $with));
        $resp->assertDontSee(PatientAccount::first()->token_hash);
        Mail::assertSent(PortalLinkMail::class, fn ($m) => $m->hasTo('x@example.test'));
    }

    public function test_patient_session_cannot_reach_staff_and_staff_session_is_not_patient(): void
    {
        $patient = $this->patient();
        $this->activate($this->issueToken($patient));
        $this->post(route('portal.login.store'), ['identifier' => 'MRN-1001', 'password' => self::PASSWORD]);
        $this->assertAuthenticated('patient');

        $this->get(route('admin.patients.index'))->assertRedirect('/admin/login');
        $this->get(route('admin.patients.show', $patient))->assertRedirect('/admin/login');
        $this->post(route('admin.patients.portal-access', $patient))->assertRedirect('/admin/login');

        Auth::guard('patient')->logout();
        $this->actingAs($this->staff(UserRole::SUPER_ADMIN));
        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    }
}
