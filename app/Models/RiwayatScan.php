<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model RiwayatScan
 *
 * Merepresentasikan tabel 'riwayat_scans' di database.
 */
class RiwayatScan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'riwayat_scans';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pesanan_id',
        'status',
        'lokasi',
        'keterangan',
        'user_id', // ID pengguna yang melakukan scan
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model Pesanan.
     * Setiap riwayat scan dimiliki oleh satu pesanan.
     */
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Mendefinisikan relasi "belongsTo" ke model User.
     * Setiap riwayat scan dicatat oleh satu pengguna.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
