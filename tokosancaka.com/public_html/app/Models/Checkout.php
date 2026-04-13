<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkout extends Model
{
    use HasFactory, SoftDeletes;

    // Spesifikasikan nama tabel
    protected $table = 'checkout';

    // Mengizinkan mass-assignment untuk semua kolom kecuali ID
    protected $guarded = ['id'];

    // Cast kolom tanggal menjadi instance Carbon agar mudah dimanipulasi
    protected $casts = [
        'tanggal_pesanan' => 'datetime',
        'shipped_at'      => 'datetime',
        'finished_at'     => 'datetime',
        'rejected_at'     => 'datetime',
        'returned_at'     => 'datetime',
        'weight'          => 'float',
        'length'          => 'float',
        'width'           => 'float',
        'height'          => 'float',
    ];

    /**
     * ==========================================
     * RELASI DATABASE
     * ==========================================
     */

    // Relasi ke tabel pengguna (pembeli)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    // Relasi ke tabel toko (penjual)
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
