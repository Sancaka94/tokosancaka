<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrasiDriverSancaka extends Model
{
    use HasFactory;

    protected $table = 'registrasi_driver_sancaka';
    protected $guarded = ['id']; // Membuka semua field agar bisa diisi (Mass Assignment)

    // Relasi ke tabel Pengguna
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}