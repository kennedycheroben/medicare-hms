<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show staff administrative login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Authenticate staff user with rate limiting and session regeneration.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['email']).'|'.$request->ip());

        // Max 5 attempts per 60 seconds
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            AuditService::record(
                action: 'auth.login_throttled',
                resourceType: 'auth',
                resourceId: $validated['email'],
                details: ['retry_after_seconds' => $seconds],
                outcome: 'failure',
                actorEmail: $validated['email']
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            RateLimiter::hit($throttleKey, 60);

            AuditService::record(
                action: 'auth.failed_login',
                resourceType: 'auth',
                resourceId: $validated['email'],
                details: ['reason' => 'Invalid credentials'],
                outcome: 'failure',
                actorEmail: $validated['email']
            );

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if account is active
        if (! $user->isActive()) {
            AuditService::record(
                action: 'auth.deactivated_login_rejected',
                resourceType: 'user',
                resourceId: (string) $user->id,
                details: ['reason' => 'Account deactivated'],
                outcome: 'denied',
                actor: $user
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your staff account has been deactivated. Please contact your system administrator.',
            ]);
        }

        // Authentication succeeded: clear rate limiter, regenerate session ID
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        // Update login tracking metadata
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AuditService::record(
            action: 'auth.login',
            resourceType: 'user',
            resourceId: (string) $user->id,
            details: [
                'role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                'ip' => $request->ip(),
            ],
            outcome: 'success',
            actor: $user
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Log staff member out and invalidate session.
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            AuditService::record(
                action: 'auth.logout',
                resourceType: 'user',
                resourceId: (string) $user->id,
                outcome: 'success',
                actor: $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been logged out securely.');
    }
}
