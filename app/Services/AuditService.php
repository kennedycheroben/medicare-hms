<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Keys that should never be persisted in audit log details for security and privacy.
     */
    protected const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'remember_token',
        'secret',
        'api_key',
        'authorization',
        'credit_card',
        'cvv',
        'pin',
        'mpesa_pin',
    ];

    /**
     * Record an immutable security or operational audit event.
     *
     * @param  string  $action  Identifier such as 'auth.login', 'auth.failed_login', 'auth.logout', 'rbac.access_denied'
     * @param  string|null  $resourceType  e.g. 'user', 'appointment', 'department'
     * @param  string|null  $resourceId  ID or reference string of the touched resource
     * @param  array<string, mixed>  $details  Contextual metadata (credentials and sensitive keys are auto-redacted)
     * @param  string  $outcome  'success', 'failure', or 'denied'
     * @param  User|null  $actor  Specific user actor; defaults to Auth::user()
     * @param  string|null  $actorEmail  Fallback email if actor is not yet authenticated (e.g. failed login attempt)
     */
    public static function record(
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $details = [],
        string $outcome = 'success',
        ?User $actor = null,
        ?string $actorEmail = null
    ): ?AuditLog {
        try {
            $user = $actor ?? Auth::user();

            $email = $actorEmail ?? ($user ? $user->email : null);
            $role = $user && $user->role ? (is_string($user->role) ? $user->role : $user->role->value) : null;

            $sanitizedDetails = self::sanitizeDetails($details);

            return AuditLog::create([
                'user_id' => $user?->id,
                'actor_email' => $email ? substr($email, 0, 255) : null,
                'actor_role' => $role,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId !== null ? (string) $resourceId : null,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => substr(Request::userAgent() ?? 'CLI/Internal', 0, 1000),
                'details' => empty($sanitizedDetails) ? null : $sanitizedDetails,
                'outcome' => $outcome,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write audit log entry', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Recursively remove sensitive keys from details array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function sanitizeDetails(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeDetails($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
