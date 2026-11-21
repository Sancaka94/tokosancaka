<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $table = 'emails';

    protected $fillable = [
        'user_id',
        'folder',
        'from_name',
        'from_address',
        'to_address',
        'subject',
        'body',
        'is_starred',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_starred' => 'boolean',
            'read_at'    => 'datetime',
        ];
    }

    /**
     * Relasi ke pemilik email pada tabel Pengguna
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}
