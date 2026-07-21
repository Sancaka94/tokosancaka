<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananAutokirim extends Model
{
    protected $table = 'pesanan_autokirim';

    // Sesuai permintaan Anda, hanya menggunakan guarded
    protected $guarded = [];

    // Relasi ke User/Pelanggan (Opsional, untuk riwayat)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
}
