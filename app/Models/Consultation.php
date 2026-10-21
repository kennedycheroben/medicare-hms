<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'visit_id',
        'patient_id',
        'doctor_id',
        'clinician_id',
        'chief_complaint',
        'history_present_illness',
        'medical_history',
        'examination_findings',
        'assessment',
        'diagnosis_type',
        'primary_diagnosis',
        'secondary_diagnosis',
        'icd11_code',
        'treatment_plan',
        'follow_up_instructions',
        'follow_up_date',
        'status',
        'signed_at',
        'signed_by',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'follow_up_date' => 'date',
        ];
    }

    /**
     * Check if the clinical consultation has been finalized and signed.
     */
    public function isSigned(): bool
    {
        return $this->status === 'signed' && $this->signed_at !== null;
    }

    /**
     * Check if the consultation is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the given user can edit this consultation note.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($this->isSigned()) {
            return false;
        }

        return $this->clinician_id === $user->id;
    }

    /**
     * Associated hospital encounter / visit.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Associated patient record.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Associated doctor profile (if any).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * The authenticated staff clinician who documented the consultation.
     */
    public function clinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinician_id');
    }

    /**
     * The authenticated staff clinician who signed off on the encounter.
     */
    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Post-signature clinical addenda and amendments.
     */
    public function addenda(): HasMany
    {
        return $this->hasMany(ConsultationAddendum::class)->orderBy('created_at', 'asc');
    }

    /**
     * Prescriptions generated during this clinical consultation.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
