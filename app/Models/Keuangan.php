<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keuangan extends Model
{
    use HasFactory;

    protected $table = 'keuangans';
    protected $fillable = [
        'kode_akun',    // <--- Tambahkan ini
        'unit_usaha',   // <--- Tambahkan ini
        'tanggal',
        'jenis',
        'kategori',
        'nomor_invoice',
        'keterangan',
        'jumlah',
    ];
}
