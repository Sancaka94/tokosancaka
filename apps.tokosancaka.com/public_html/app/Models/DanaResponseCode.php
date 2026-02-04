<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanaResponseCode extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model ini.
     */
    protected $table = 'dana_response_codes';

    /**
     * Kolom yang dapat diisi secara massal (Mass Assignment).
     * Sesuai dengan input form dan kolom database Anda.
     */
    protected $fillable = [
        'response_code', // Varchar (e.g., 2003700)
        'category',      // Varchar (INQUIRY, TOPUP, GENERAL)
        'message_title', // Varchar
        'description',   // Text
        'solution',      // Text
        'is_success',    // Tinyint/Boolean (1 atau 0)
    ];

    /**
     * Atribut yang harus dikonversi ke tipe data asli.
     * is_success di database (1/0) akan otomatis jadi true/false di PHP.
     */
    protected $casts = [
        'is_success' => 'boolean',
    ];
}