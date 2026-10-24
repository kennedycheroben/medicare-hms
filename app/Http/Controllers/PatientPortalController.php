<?php

namespace App\Http\Controllers;

use App\Mail\PortalLinkMail;
use App\Models\PatientAccount;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Patient portal: login, one-time activation/reset links, forced password
 * change, and a dashboard limited to the signed-in patient's own records.
 */
class PatientPortalController extends Controller
{
    private const GENERIC_LOGIN_ERROR = 'We could not sign you in with those details. Check your MRN or email and password, or use "Forgot password".';

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('patient')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('pages.portal-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identifier = Str::lower(trim($data['identifier']));
        $throttleKey = 'portal-login:'.sha1($identifier.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => "Too many sign-in attempts. Please wait {$minutes} minute(s) and try again."]);
        }

        $account = PatientAccount::query()
            ->where(function ($q) use ($identifier) {
                $q->whereHas('patient', fn ($p) => $p->whereRaw('LOWER(mrn) = ?', [$identifier]))
                    ->orWhereRaw('LOWER(email) = ?', [$identifier]);
            })
            ->with('patient')
            ->first();

        // Always perform a hash comparison so response time does not reveal
        // whether an account exists.
        $hash = $account?->password ?? '$2y$12$QJvhjje.cjspct8SdsGnf.EOaExRS3EDxCQyv8y1aCZKveEkTGv72';
        $valid = Hash::check($data['password'], $hash);

        if (! $account || ! $valid || ! $account->isActivated() || ! $account->patient?->is_active) {
            RateLimiter::hit($throttleKey, 900);
            AuditService::record('portal.login_failed', 'patient_account', $account?->id, [], 'failure');

            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => self::GENERIC_LOGIN_ERROR]);
        }

        RateLimiter::clear($throttleKey);
        Auth::guard('patient')->login($account, $request->boolean('remember'));
        $request->session()->regenerate();
        $account->forceFill(['last_login_at' => now()])->save();

        AuditService::record('portal.login', 'patient_account', $account->id, [], 'success');

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('status', 'You have been signed out.');
    }

    public function dashboard(): View
    {
        $patient = Auth::guard('patient')->user()->patient;

        return view('pages.portal-dashboard', [
            'patient' => $patient,
            'appointments' => $patient->appointments()
                ->with(['department', 'doctor'])
                ->whereDate('appointment_date', '>=', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('appointment_date')->limit(10)->get(),
            'visits' => $patient->visits()
                ->with(['department', 'doctor'])
                ->latest('id')->limit(10)->get(),
            'prescriptions' => $patient->prescriptions()
                ->with('items')
                ->whereNotIn('status', ['draft'])
                ->latest('id')->limit(10)->get(),
        ]);
    }

    /** Open an emailed link: shows the "choose a password" form. */
    public function showActivate(string $token): View
    {
        $account = PatientAccount::findByToken($token);

        return view('pages.portal-activate', [
            'token' => $token,
            'valid' => (bool) $account,
            'isReset' => $account?->isActivated() ?? false,
        ]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $account = PatientAccount::findByToken($token);

        if (! $account) {
            return redirect()->route('portal.activate', $token);
        }

        $account->forceFill([
            'password' => $data['password'],
            'activated_at' => $account->activated_at ?? now(),
            'must_change_password' => false,
            'token_hash' => null,      // single use
            'token_expires_at' => null,
        ])->save();

        AuditService::record('portal.password_set', 'patient_account', $account->id, [], 'success');

        return redirect()->route('portal.login')
            ->with('status', 'Your password is saved. You can now sign in.');
    }

    public function showForgot(): View
    {
        return view('pages.portal-forgot');
    }

    /** Always answers the same way so it cannot be used to find who has an account. */
    public function sendReset(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $key = 'portal-reset:'.sha1(Str::lower($data['email']).'|'.$request->ip());
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 900);

            $account = PatientAccount::with('patient')
                ->whereRaw('LOWER(email) = ?', [Str::lower($data['email'])])
                ->first();

            if ($account && $account->isActivated() && $account->patient?->is_active) {
                $token = $account->issueToken(PatientAccount::RESET_TTL_MINUTES);
                Mail::to($account->email)->send(new PortalLinkMail($account, $token, true, PatientAccount::RESET_TTL_MINUTES));
                AuditService::record('portal.reset_requested', 'patient_account', $account->id);
            }
        }

        return back()->with('status', 'If that email belongs to a portal account, we have sent a link to reset the password. Check your inbox.');
    }

    public function editPassword(): View
    {
        return view('pages.portal-change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $account = Auth::guard('patient')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $account->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not correct.']);
        }

        $account->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        $request->session()->regenerate();
        AuditService::record('portal.password_changed', 'patient_account', $account->id);

        return redirect()->route('portal.dashboard')->with('status', 'Your password has been changed.');
    }
}
