<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'reference_code',
        'patient_name',
        'patient_email',
        'patient_phone',
        'department_id',
        'doctor_id',
        'preferred_doctor_id',
        'assigned_by',
        'assigned_at',
        'appointment_date',
        'appointment_time',
        'symptoms',
        'status',
        'cancellation_reason',
        'reschedule_reason',
        'confirmed_at',
        'confirmed_by',
        'cancelled_at',
        'cancelled_by',
        'rescheduled_at',
        'rescheduled_by',
        'triage_notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rescheduled_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function preferredDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'preferred_doctor_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isAssigned(): bool
    {
        return ! empty($this->doctor_id) && ! empty($this->assigned_at);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }

    /**
     * Determine if this appointment can transition to confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return in_array($this->status, ['pending', 'rescheduled'], true) && ! $this->visit()->exists();
    }

    /**
     * Determine if this appointment can be rescheduled.
     */
    public function canBeRescheduled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'rescheduled'], true) && ! $this->visit()->exists();
    }

    /**
     * Determine if this appointment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'rescheduled'], true) && ! $this->visit()->exists();
    }

    /**
     * Determine if this appointment can be checked in to visit queue.
     */
    public function canBeCheckedIn(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'rescheduled'], true) && ! $this->visit()->exists();
    }
}
