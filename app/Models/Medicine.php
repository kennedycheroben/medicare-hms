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
