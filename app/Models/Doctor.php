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
        'is_published',
        'published_at',
        'approved_by',
        'publication_notes',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class)->where('status', 'approved');
    }

    /**
     * Scope for published doctor profiles visible to the public.
     */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_published', true)->where('is_available', true);
    }

    /**
     * Recalculate official doctor rating strictly from verified, approved patient reviews.
     */
    public function recalculateRating(): void
    {
        $approvedCount = $this->reviews()->where('status', 'approved')->count();
        $avg = $approvedCount > 0 ? (float) $this->reviews()->where('status', 'approved')->avg('rating') : 0.0;

        $this->update([
            'rating' => round($avg, 1),
            'review_count' => $approvedCount,
        ]);
    }
}
