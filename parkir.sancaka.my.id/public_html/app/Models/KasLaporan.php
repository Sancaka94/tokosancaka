<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasLaporan extends Model
{
    protected $table = 'kas_laporans';
    protected $guarded = []; // Mengizinkan mass assignment untuk semua kolom

    // Relasi One-to-Many ke rincian pengeluaran
    public function pengeluaran()
    {
        return $this->hasMany(KasPengeluaran::class, 'kas_laporan_id');
    }
}