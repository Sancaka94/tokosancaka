<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'folder',
        'from_name',
        'from_address',
        'to_address',
        'cc_address',
        'bcc_address',
        'subject',
        'body',
        'read_at',
        'is_starred',
        'is_spam',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'is_starred' => 'boolean',
        'is_spam' => 'boolean',
        'to_address' => 'array', // Jika Anda ingin menyimpan beberapa penerima
        'cc_address' => 'array',
        'bcc_address' => 'array',
    ];

    /**
     * Mendapatkan pengguna yang memiliki email ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
