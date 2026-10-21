<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Patient extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mrn',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'national_id',
        'address',
        'city',
        'blood_group',
        'allergies',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'is_active',
        'registered_by',
    ];

    /**
     * Cast attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get patient's full legal name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Calculate patient's current chronological age in years.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Generate a unique, cryptographically random Medical Record Number (MRN).
     * Format: MRN-YYYY-XXXXXX (e.g. MRN-2026-F8K3M9)
     */
    public static function generateUniqueMrn(): string
    {
        $year = date('Y');

        do {
            $randomCode = strtoupper(Str::random(6));
            $mrn = "MRN-{$year}-{$randomCode}";
        } while (static::where('mrn', $mrn)->exists());

        return $mrn;
    }

    /**
     * Find potential duplicate patient records matching core demographic identifiers.
     *
     * @return Collection<int, Patient>
     */
    public static function findPotentialDuplicates(
        string $firstName,
        string $lastName,
        string $dob,
        string $phone,
        ?string $email = null
    ): Collection {
        return static::query()
            ->where(function (Builder $query) use ($phone, $email, $firstName, $lastName, $dob) {
                // Match phone number
                $query->where('phone', $phone);

                // Match email if provided
                if (! empty($email)) {
                    $query->orWhere('email', $email);
                }

                // Match exact name and DOB combination
                $query->orWhere(function (Builder $sub) use ($firstName, $lastName, $dob) {
                    $sub->where('first_name', 'like', $firstName)
                        ->where('last_name', 'like', $lastName)
                        ->whereDate('date_of_birth', $dob);
                });
            })
            ->limit(5)
            ->get();
    }

    /**
     * All visits and encounters for this patient.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Appointments scheduled for this patient.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Staff member who registered this patient.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * All doctor consultations for this patient.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class)->orderBy('created_at', 'desc');
    }

    /**
     * All prescriptions issued to this patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->orderBy('created_at', 'desc');
    }

    /**
     * All triage assessments for this patient.
     */
    public function triageRecords(): HasMany
    {
        return $this->hasMany(TriageRecord::class)->orderBy('triaged_at', 'desc');
    }
}
