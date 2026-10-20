<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageRecord extends Model
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
        'triage_nurse_id',
        'systolic_bp',
        'diastolic_bp',
        'pulse_rate',
        'respiratory_rate',
        'temperature',
        'oxygen_saturation',
        'pain_score',
        'weight_kg',
        'height_cm',
        'bmi',
        'blood_glucose',
        'triage_category',
        'presenting_complaint',
        'nursing_notes',
        'referred_department_id',
        'referred_doctor_id',
        'triaged_at',
    ];

    /**
     * Cast attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'triaged_at' => 'datetime',
            'systolic_bp' => 'integer',
            'diastolic_bp' => 'integer',
            'pulse_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'temperature' => 'float',
            'oxygen_saturation' => 'integer',
            'pain_score' => 'integer',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'bmi' => 'float',
            'blood_glucose' => 'float',
        ];
    }

    /**
     * Helper to compute Body Mass Index (BMI).
     */
    public static function computeBmi(?float $weightKg, ?float $heightCm): ?float
    {
        if ($weightKg && $heightCm && $heightCm > 0) {
            $heightMeters = $heightCm / 100;
            return round($weightKg / ($heightMeters * $heightMeters), 1);
        }

        return null;
    }

    /**
     * Human-readable triage priority label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->triage_category) {
            'emergency' => 'Category 1: Emergency (Immediate Resuscitation)',
            'very_urgent' => 'Category 2: Very Urgent (High Clinical Risk)',
            'urgent' => 'Category 3: Urgent (Acute Illness)',
            'standard' => 'Category 4: Standard (Routine Outpatient)',
            'non_urgent' => 'Category 5: Non-Urgent (Minor / Chronic)',
            default => ucfirst(str_replace('_', ' ', (string) $this->triage_category)),
        };
    }

    /**
     * Badge CSS class for triage category visualization.
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->triage_category) {
            'emergency' => 'bg-danger text-white',
            'very_urgent' => 'bg-warning text-dark',
            'urgent' => 'bg-info text-white',
            'standard' => 'bg-success text-white',
            'non_urgent' => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }

    /**
     * Formatted blood pressure reading.
     */
    public function getBloodPressureAttribute(): ?string
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return "{$this->systolic_bp}/{$this->diastolic_bp}";
        }

        return null;
    }

    /**
     * The associated visit / encounter.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * The patient examined.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Triage Nursing Officer who conducted this assessment.
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triage_nurse_id');
    }

    /**
     * Destination clinic / department.
     */
    public function referredDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'referred_department_id');
    }

    /**
     * Destination physician.
     */
    public function referredDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'referred_doctor_id');
    }
}
