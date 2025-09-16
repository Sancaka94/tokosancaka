<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     * Sesuaikan jika nama tabel Anda berbeda.
     *
     * @var string
     */
    protected $table = 'toko'; // Asumsi nama tabel adalah 'toko'

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'id_toko';

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'id_pengguna_pemilik', // Foreign key ke tabel Pengguna
        'nama_toko',
        'slug_toko',
        'deskripsi',
        'alamat_toko',
        'logo_path',
        'is_active',
    ];

    /**
     * Mendefinisikan relasi "dimiliki oleh" (belongsTo) ke model User (sebagai pemilik).
     * Satu toko hanya dimiliki oleh satu pengguna.
     */
    public function pemilik()
    {
        return $this->belongsTo(User::class, 'id_pengguna_pemilik', 'id_pengguna');
    }

    /**
     * Mendefinisikan relasi "memiliki banyak" (hasMany) ke model Pesanan.
     * Satu toko bisa memiliki banyak pesanan.
     */
    public function pesanans()
    {
        return $this->hasMany(Pesanan::class, 'id_toko', 'id_toko');
    }

    /**
     * Mendefinisikan relasi "memiliki banyak" (hasMany) ke model Product.
     * Satu toko bisa memiliki banyak produk.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'id_toko', 'id_toko');
    }
}
