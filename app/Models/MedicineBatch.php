<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineBatch extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'medicine_id',
        'batch_number',
        'expiry_date',
        'quantity_initial',
        'quantity_current',
        'unit_purchase_cost',
        'unit_dispensing_price',
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
            'expiry_date' => 'date',
            'quantity_initial' => 'integer',
            'quantity_current' => 'integer',
            'unit_purchase_cost' => 'float',
            'unit_dispensing_price' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if this batch is expired based on calendar date.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Check if batch stock has been fully depleted.
     */
    public function isDepleted(): bool
    {
        return $this->quantity_current <= 0;
    }

    /**
     * Parent medicine catalogue entry.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Dispensings serviced from this batch.
     */
    public function dispensings(): HasMany
    {
        return $this->hasMany(Dispensing::class);
    }

    /**
     * Stock movements involving this batch.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
