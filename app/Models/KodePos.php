<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodePos extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model ini.
     *
     * @var string
     */
    protected $table = 'kode_pos';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'provinsi',
        'kota_kabupaten',
        'kecamatan',
        'kelurahan_desa',
        'kode_pos',
    ];
}
