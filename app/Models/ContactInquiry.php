<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'name',
        'email',
        'phone',
        'department',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];
}
