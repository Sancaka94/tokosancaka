<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramGroup extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit (opsional jika nama tabel sudah jamak)
    protected $table = 'telegram_groups';

    // Mengizinkan kolom ini diisi secara massal (mass assignment)
    protected $fillable = [
        'nama',
        'link',
    ];
}
