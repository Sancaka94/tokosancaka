<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory;

    // 1. Definisikan nama tabel yang baru saja mas buat
    protected $table = 'escrows';

    // 2. Izinkan semua kolom untuk diisi (mass assignment)
    protected $guarded = [];

    // 3. (Opsional tapi bagus) Ubah tipe data saat diambil dari database
    protected $casts = [
        'nominal_ditahan' => 'decimal:2',
        'nominal_ongkir'  => 'decimal:2',
        'dicairkan_pada'  => 'datetime',
    ];

    /**
     * ==========================================================
     * RELASI ANTAR TABEL
     * ==========================================================
     */

    // Relasi ke tabel orders (Untuk ambil data status pesanan, kurir, dll)
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    // Relasi ke tabel stores (Untuk ambil data nama toko, nomor rekening toko, dll)
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    // Relasi ke tabel Pengguna (Untuk ambil data pembeli, nomor WA pembeli, dll)
    // Ingat, primary key di tabel Pengguna adalah 'id_pengguna'
    public function buyer()
    {
        return $this->belongsTo(Pengguna::class, 'user_id', 'id_pengguna');
    }
}
