<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pesanan; // Pastikan model Pesanan di-import

class Kontak extends Model
{
    use HasFactory;

    // Daftar kolom yang diizinkan untuk diisi secara massal (Mass Assignment)
    protected $fillable = [
        'nama',
        'no_hp',
        'alamat',
        'province',
        'regency',
        'district',
        'village',
        'postal_code',
        'id_pengguna',
        'tipe',
        'user_id',

        // Kordinat & ID Wilayah KiriminAja
        'lat',
        'lng',
        'district_id',
        'subdistrict_id',
    ];

    /**
     * Relasi Pengiriman (Sebagai Pengirim/Customer)
     * Mengaitkan 'no_hp' di tabel kontak dengan 'sender_phone' di tabel pesanan.
     * Ini digunakan untuk menghitung total order dan omzet pada halaman pelanggan.
     */
    public function pengiriman()
    {
        return $this->hasMany(Pesanan::class, 'sender_phone', 'no_hp');
    }

    /**
     * Relasi Penerimaan (Sebagai Penerima)
     * Mengaitkan 'no_hp' di tabel kontak dengan 'receiver_phone' di tabel pesanan.
     * Opsional: Bisa digunakan jika kedepannya Anda ingin melihat riwayat paket yang diterima seseorang.
     */
    public function penerimaan()
    {
        return $this->hasMany(Pesanan::class, 'receiver_phone', 'no_hp');
    }
}
