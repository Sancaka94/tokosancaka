<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpxScan extends Model
{
    use HasFactory;

    protected $table = 'spx_scans';

    protected $fillable = [
        'kontak_id',
        'surat_jalan_id',
        'resi',
        'status',
    ];

    public function kontak()
    {
        return $this->belongsTo(Kontak::class);
    }

    public function suratJalan()
    {
        return $this->belongsTo(SuratJalan::class);
    }

    /**
     * PENAMBAHAN: Mendefinisikan relasi ke ScanHistory.
     * Mengurutkan dari yang paling baru.
     */
    public function scanHistories()
    {
        return $this->hasMany(ScanHistory::class)->latest();
    }
}
