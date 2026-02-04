<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunKeuangan extends Model
{
    use HasFactory;

    protected $table = 'akun_keuangan';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori',
        'jenis_laporan', // Neraca / Laba Rugi
        'tipe_arus',     // Pemasukan / Pengeluaran / Netral
        'unit_usaha',    // Ekspedisi / Percetakan
    ];
}