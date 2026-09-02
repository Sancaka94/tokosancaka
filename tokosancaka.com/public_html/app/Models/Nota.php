<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara spesifik
    protected $table = 'notas';

    // Menentukan primary key
    protected $primaryKey = 'id';

    // Kolom-kolom yang diizinkan untuk diisi secara massal (Mass Assignment)
    protected $fillable = [
        'no_nota',
        'kepada',
        'tanggal',
        'nama_pembeli',
        'no_hp_pembeli',   // <-- TAMBAHAN BARU
        'nama_penjual',
        'ttd_pembeli',
        'ttd_penjual',
        'total_harga',
        'payment_method',  // <-- TAMBAHAN BARU
        'payment_url',     // <-- TAMBAHAN BARU
        'status',          // <-- TAMBAHAN BARU
    ];

    /**
     * Relasi One-to-Many ke tabel nota_items
     * Satu Nota memiliki banyak Item/Barang
     */
    public function items()
    {
        return $this->hasMany(NotaItem::class, 'nota_id', 'id');
    }
}
