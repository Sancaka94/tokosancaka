<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasPengeluaran extends Model
{
    protected $table = 'kas_pengeluarans';
    protected $guarded = [];

    // Relasi ke Laporan Induk
    public function laporan()
    {
        return $this->belongsTo(KasLaporan::class, 'kas_laporan_id');
    }
}