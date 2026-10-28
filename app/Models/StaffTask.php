<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'assigned_by',
        'due_date',
        'related_type',
        'related_id',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isCompleted();
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'hospital_director'])) {
            return true;
        }

        return (int) $this->assigned_by === (int) $user->id;
    }

    public function canBeUpdatedBy(User $user): bool
    {
        if ($this->canBeManagedBy($user)) {
            return true;
        }

        return (int) $this->assigned_to === (int) $user->id;
    }
}
