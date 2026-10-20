<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'summary',
        'description',
        'services',
        'head_of_department',
        'phone_extension',
        'emergency_contact',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'services' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
