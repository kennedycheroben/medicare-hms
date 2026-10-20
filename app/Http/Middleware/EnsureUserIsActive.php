<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request to verify that the authenticated staff account remains active.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if (! $user->isActive()) {
                AuditService::record(
                    action: 'auth.deactivated_session_revoked',
                    resourceType: 'session',
                    resourceId: $request->session()->getId(),
                    details: ['reason' => 'User account is marked inactive'],
                    outcome: 'denied',
                    actor: $user
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')->withErrors([
                    'email' => 'Your staff account has been deactivated. Please contact your system administrator.',
                ]);
            }
        }

        return $next($request);
    }
}
