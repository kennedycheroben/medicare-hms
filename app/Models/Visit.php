<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Visit extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'visit_number',
        'patient_id',
        'appointment_id',
        'department_id',
        'doctor_id',
        'visit_type',
        'reason_for_visit',
        'priority',
        'status',
        'checked_in_by',
        'checked_in_at',
        'completed_at',
        'notes',
    ];

    /**
     * Cast attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Generate unique clinical encounter / visit reference number.
     * Format: VST-YYYYMMDD-XXXXX
     */
    public static function generateUniqueVisitNumber(): string
    {
        $date = date('Ymd');

        do {
            $randomCode = strtoupper(Str::random(5));
            $visitNumber = "VST-{$date}-{$randomCode}";
        } while (static::where('visit_number', $visitNumber)->exists());

        return $visitNumber;
    }

    /**
     * The patient for this visit.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Associated scheduled appointment (if this visit originated from one).
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Clinical department attending to this visit.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Assigned attending physician (if pre-selected or assigned).
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Receptionist / Staff member who executed check-in.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Associated nursing triage vital signs and assessment.
     */
    public function triageRecord(): HasOne
    {
        return $this->hasOne(TriageRecord::class);
    }

    /**
     * Associated medical consultation / clinical encounter record.
     */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }
}
