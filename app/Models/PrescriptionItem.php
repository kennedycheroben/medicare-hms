<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionItem extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'medication_name',
        'dosage',
        'route',
        'frequency',
        'duration',
        'quantity_prescribed',
        'quantity_dispensed',
        'instructions',
        'dispense_status',
        'non_dispense_reason',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_prescribed' => 'integer',
            'quantity_dispensed' => 'integer',
        ];
    }

    /**
     * Calculate remaining quantity left to dispense.
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity_prescribed - $this->quantity_dispensed);
    }

    /**
     * Check if this item is completely fulfilled.
     */
    public function isFullyDispensed(): bool
    {
        return $this->quantity_dispensed >= $this->quantity_prescribed;
    }

    /**
     * Parent prescription.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Linked catalogue medicine (if any).
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Dispensings for this prescription item.
     */
    public function dispensings(): HasMany
    {
        return $this->hasMany(Dispensing::class);
    }
}
