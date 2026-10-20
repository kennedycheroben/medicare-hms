<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_code',
        'patient_name',
        'patient_email',
        'patient_phone',
        'department_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'symptoms',
        'status',
        'triage_notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
