<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'slug',
        'specialty',
        'qualifications',
        'experience_years',
        'bio',
        'languages',
        'consultation_days',
        'consultation_hours',
        'room_number',
        'rating',
        'review_count',
        'image',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'rating' => 'float',
        'experience_years' => 'integer',
        'review_count' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
