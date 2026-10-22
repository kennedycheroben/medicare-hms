<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'brand_name',
        'generic_name',
        'category',
        'dosage_form',
        'strength',
        'unit_of_measure',
        'reorder_level',
        'is_active',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reorder_level' => 'integer',
        ];
    }

    /**
     * Total available non-expired stock across all active batches.
     */
    public function getAvailableStockAttribute(): int
    {
        if (array_key_exists('available_stock', $this->attributes)) {
            return (int) ($this->attributes['available_stock'] ?? 0);
        }

        return (int) $this->batches()
            ->where('is_active', true)
            ->where('expiry_date', '>=', today())
            ->sum('quantity_current');
    }

    /**
     * Check if available stock is at or below reorder threshold.
     */
    public function isLowStock(): bool
    {
        return $this->available_stock <= $this->reorder_level;
    }

    /**
     * Scope query to medicines whose eligible non-expired active stock is at or below reorder level.
     */
    public function scopeLowStock($query)
    {
        $today = today()->toDateString();

        return $query->whereRaw(
            '(SELECT COALESCE(SUM(quantity_current), 0) FROM medicine_batches WHERE medicine_batches.medicine_id = medicines.id AND medicine_batches.is_active = 1 AND medicine_batches.expiry_date >= ?) <= medicines.reorder_level',
            [$today]
        );
    }

    /**
     * Generate a unique, server-side collision-safe medicine product code.
     * Format: MED-XXXXXX
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = 'MED-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Associated stock batches.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    /**
     * Associated prescription items.
     */
    public function prescriptionItems(): HasMany
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

    /**
     * Historical inventory movements.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
