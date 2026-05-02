<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    // LOG LOG - Model konfigurasi
    protected $table = 'cities';
    protected $fillable = ['nama_kota', 'keterangan'];
}