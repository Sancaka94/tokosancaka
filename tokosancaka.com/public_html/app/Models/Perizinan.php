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
        'jumlah_penghuni',      // <-- TAMBAHAN BARU
        'memiliki_basement',    // <-- TAMBAHAN BARU
        'fungsi_bangunan',
        'legalitas_saat_ini',
        'status_tanah',         // <-- TAMBAHAN BARU
        'status_krk',
        'rekom_dishub',         // <-- TAMBAHAN BARU
        'rekom_damkar',         // <-- TAMBAHAN BARU
        'andalalin',            // <-- TAMBAHAN BARU
        'lingkungan',           // <-- TAMBAHAN BARU
        'nib',                  // <-- TAMBAHAN BARU
        'siup',                 // <-- TAMBAHAN BARU
        'perizinan_lain',       // <-- TAMBAHAN BARU
    ];

}
