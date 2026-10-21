<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Dispensing extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'dispensation_number',
        'prescription_id',
        'prescription_item_id',
        'medicine_id',
        'medicine_batch_id',
        'quantity_dispensed',
        'dispensed_by',
        'pharmacist_notes',
        'dispensed_at',
        'idempotency_key',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_dispensed' => 'integer',
            'dispensed_at' => 'datetime',
        ];
    }

    /**
     * Generate unique Dispensation Number (Format: DSP-YYYYMMDD-XXXXX).
     */
    public static function generateUniqueDispensationNumber(): string
    {
        $datePrefix = date('Ymd');

        do {
            $randomCode = strtoupper(Str::random(5));
            $number = "DSP-{$datePrefix}-{$randomCode}";
        } while (static::where('dispensation_number', $number)->exists());

        return $number;
    }

    /**
     * Parent prescription.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Parent prescription item.
     */
    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    /**
     * Dispensed medicine.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Stock batch used.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }

    /**
     * Pharmacist who performed the dispensing.
     */
    public function dispensedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}
