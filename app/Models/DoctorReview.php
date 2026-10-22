<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'visit_id',
        'rating',
        'comment',
        'public_consent',
        'display_name_type',
        'status',
        'moderated_by',
        'moderated_at',
        'moderation_notes',
    ];

    protected $casts = [
        'rating' => 'integer',
        'public_consent' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    /**
     * Reviewed doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Reviewing patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Qualifying completed clinical visit.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Staff member who moderated this review.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Scope for approved reviews that are permitted to appear publicly.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for reviews awaiting staff moderation.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_moderation');
    }

    /**
     * Privacy-preserving public display name based on patient consent.
     */
    public function getPublicAuthorNameAttribute(): string
    {
        if (! $this->patient) {
            return 'Verified Patient';
        }

        // Full name may only be displayed when explicit consent is granted
        if ($this->public_consent && $this->display_name_type === 'full_name') {
            return trim($this->patient->first_name . ' ' . $this->patient->last_name);
        }

        // Initials option
        if ($this->display_name_type === 'initials') {
            $firstInitial = mb_substr($this->patient->first_name, 0, 1);
            $lastInitial = mb_substr($this->patient->last_name, 0, 1);
            return strtoupper("{$firstInitial}. {$lastInitial}.");
        }

        return 'Verified Patient (Anonymous)';
    }
}
