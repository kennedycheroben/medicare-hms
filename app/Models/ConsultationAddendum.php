<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationAddendum extends Model
{
    use HasFactory;

    /**
     * Explicit table name (irregular plural).
     *
     * @var string
     */
    protected $table = 'consultation_addenda';

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consultation_id',
        'author_id',
        'addendum_notes',
        'reason_for_amendment',
    ];

    /**
     * Parent consultation.
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    /**
     * Authoring clinician.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
