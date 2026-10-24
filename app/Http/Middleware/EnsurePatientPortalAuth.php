<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards patient portal pages: requires the separate `patient` guard, an active
 * patient record, and a completed password change.
 */
class EnsurePatientPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('patient');
        $account = $guard->user();

        if (! $account || ! $account->patient || ! $account->patient->is_active) {
            $guard->logout();

            return redirect()->route('portal.login');
        }

        if ($account->must_change_password && ! $request->routeIs('portal.password.*', 'portal.logout')) {
            return redirect()->route('portal.password.edit');
        }

        return $next($request);
    }
}
