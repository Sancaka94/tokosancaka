<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ekspedisi extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'Ekspedisi'; // Pastikan nama tabel ini sesuai dengan database Anda

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'logo',
    ];

    /**
     * Mendefinisikan relasi ke model Pesanan.
     * Satu ekspedisi bisa memiliki banyak pesanan.
     */
    public function pesanans()
    {
        return $this->hasMany(Pesanan::class, 'ekspedisi_id');
    }
}
