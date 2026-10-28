<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request and ensure the user possesses one of the required roles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('admin.login'));
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            AuditService::record(
                action: 'auth.deactivated_access_attempt',
                resourceType: 'route',
                resourceId: $request->path(),
                details: ['reason' => 'Account is inactive'],
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

        // If no specific roles required, allow active authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the allowed roles
        if (! $user->hasRole($roles)) {
            AuditService::record(
                action: 'rbac.access_denied',
                resourceType: 'route',
                resourceId: $request->path(),
                details: [
                    'required_roles' => $roles,
                    'user_role' => $user->role instanceof UserRole ? $user->role->value : (string) $user->role,
                ],
                outcome: 'denied',
                actor: $user
            );

            abort(403, 'Unauthorized. Your role does not have permission to access this administrative resource.');
        }

        return $next($request);
    }
}
