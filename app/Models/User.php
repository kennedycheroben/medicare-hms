<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'is_active',
    'department_id',
    'doctor_id',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  UserRole|string|array<UserRole|string>  $roles
     */
    public function hasRole(UserRole|string|array $roles): bool
    {
        $roleValues = is_array($roles) ? $roles : [$roles];

        $currentValue = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        foreach ($roleValues as $role) {
            $expectedValue = $role instanceof UserRole ? $role->value : (string) $role;
            if ($currentValue === $expectedValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has a specific RBAC capability.
     */
    public function hasCapability(string $capability): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (! $this->role instanceof UserRole) {
            return false;
        }

        return $this->role->can($capability);
    }

    /**
     * Check if the user is an active doctor with a valid linked doctor profile record.
     */
    public function hasValidDoctorProfile(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (! $this->hasRole(UserRole::DOCTOR)) {
            return false;
        }

        if (! $this->doctor_id) {
            return false;
        }

        if ($this->relationLoaded('doctor')) {
            return $this->doctor !== null;
        }

        return $this->doctor()->exists();
    }

    /**
     * User's assigned department (if applicable).
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * User's linked doctor profile (if role is doctor).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the doctor's clinical department ID.
     * Resolves from the user record or falls back to the linked doctor profile.
     */
    public function getClinicalDepartmentId(): ?int
    {
        if ($this->department_id) {
            return (int) $this->department_id;
        }

        if ($this->doctor_id && $this->doctor) {
            return $this->doctor->department_id ? (int) $this->doctor->department_id : null;
        }

        return null;
    }

    /**
     * Audit log entries where this user was the actor.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Patients registered by this user.
     */
    public function registeredPatients(): HasMany
    {
        return $this->hasMany(Patient::class, 'registered_by');
    }

    /**
     * Patient encounters / visits checked in by this receptionist / user.
     */
    public function conductedVisits(): HasMany
    {
        return $this->hasMany(Visit::class, 'checked_in_by');
    }

    /**
     * Triage records completed by this nurse.
     */
    public function triageAssessments(): HasMany
    {
        return $this->hasMany(TriageRecord::class, 'triage_nurse_id');
    }
}

