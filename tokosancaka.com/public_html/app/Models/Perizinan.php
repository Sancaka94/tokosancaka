<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perizinan extends Model
{
    use HasFactory;

    protected $table = 'perizinans';

    protected $fillable = [
        'nama_pelanggan',
        'no_wa',
        'lebar',
        'panjang',
        'status_bangunan',
        'jenis_bangunan',
        'lokasi',
        'jumlah_lantai',
        'fungsi_bangunan',
        'legalitas_saat_ini',
        'status_krk',
    ];
}
