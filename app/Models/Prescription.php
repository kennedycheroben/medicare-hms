<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Prescription extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_number',
        'submission_token',
        'patient_id',
        'visit_id',
        'consultation_id',
        'prescriber_id',
        'clinical_notes',
        'status',
        'finalized_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Generate collision-resistant Prescription Number (Format: RX-YYYYMMDD-XXXXX).
     */
    public static function generateUniquePrescriptionNumber(): string
    {
        $datePrefix = date('Ymd');

        do {
            $randomCode = strtoupper(Str::random(5));
            $prescriptionNumber = "RX-{$datePrefix}-{$randomCode}";
        } while (static::where('prescription_number', $prescriptionNumber)->exists());

        return $prescriptionNumber;
    }

    /**
     * Check if prescription has been signed and finalized by prescribing clinician.
     */
    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'partially_dispensed', 'dispensed'], true);
    }

    /**
     * Check if prescription is still in draft state.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if prescription was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Re-calculate and update status based on item dispense statuses.
     */
    public function refreshDispenseStatus(): void
    {
        if ($this->isCancelled() || $this->isDraft()) {
            return;
        }

        $items = $this->items;

        if ($items->isEmpty()) {
            return;
        }

        $allClosed = $items->every(fn (PrescriptionItem $item) => in_array($item->dispense_status, ['dispensed', 'cancelled'], true));
        $anyDispensed = $items->some(fn (PrescriptionItem $item) => $item->quantity_dispensed > 0);

        if ($allClosed && $anyDispensed) {
            // Every item is either fully dispensed or closed with a documented reason.
            $this->update(['status' => 'dispensed']);
        } elseif ($allClosed) {
            // Nothing was dispensed and every item was declined with a documented reason.
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'All items closed by pharmacy without dispensing.',
            ]);
        } elseif ($anyDispensed) {
            $this->update(['status' => 'partially_dispensed']);
        } else {
            $this->update(['status' => 'finalized']);
        }
    }

    /**
     * Associated patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Associated visit / encounter.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Associated doctor consultation.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Prescribing clinician.
     */
    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescriber_id');
    }

    /**
     * Cancelling clinician/pharmacist.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Individual medication line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Associated dispensing records.
     */
    public function dispensings(): HasMany
    {
        return $this->hasMany(Dispensing::class);
    }
}
