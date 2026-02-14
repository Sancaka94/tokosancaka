<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeminarParticipant extends Model
{
    use HasFactory;

    protected $table = 'seminar_participants';

    protected $fillable = [
        'ticket_number',
        'nama',
        'email',
        'instansi',
        'no_wa',
        'is_checked_in',
        'check_in_at',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'is_checked_in' => 'boolean',
    ];
}
