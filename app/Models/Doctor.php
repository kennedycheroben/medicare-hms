<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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
        'status',
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

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

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
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('is_available', true)
            ->where('status', 'approved');
    }

    /**
     * Get the resolved public URL for the doctor's profile photo or fallback.
     */
    public function imageUrl(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->url($this->image);
        }

        if ($this->image && (str_starts_with($this->image, 'http') || str_starts_with($this->image, 'assets/'))) {
            return asset($this->image);
        }

        return asset('assets/img/health/staff-1.webp');
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
