<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/**
 * Portal login for a patient. Deliberately separate from staff users so a
 * patient session can never be treated as a staff session (distinct guard).
 */
class PatientAccount extends Authenticatable
{
    public const ACTIVATION_TTL_HOURS = 48;

    public const RESET_TTL_MINUTES = 60;

    protected $fillable = ['patient_id', 'email', 'issued_by'];

    protected $hidden = ['password', 'token_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'token_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null && $this->password !== null;
    }

    /**
     * Create a fresh single-use token. Returns the plain token (to be emailed
     * once); only its hash is persisted.
     */
    public function issueToken(int $minutes): string
    {
        $plain = Str::random(64);
        $this->forceFill([
            'token_hash' => hash('sha256', $plain),
            'token_expires_at' => now()->addMinutes($minutes),
        ])->save();

        return $plain;
    }

    public static function findByToken(string $plain): ?self
    {
        if (strlen($plain) !== 64) {
            return null;
        }

        $account = static::where('token_hash', hash('sha256', $plain))->first();

        if (! $account || ! $account->token_expires_at || $account->token_expires_at->isPast()) {
            return null;
        }

        return $account;
    }
}
