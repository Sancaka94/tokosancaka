<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara spesifik (sangat disarankan jika buat tabel manual)
    protected $table = 'notas';

    // Menentukan primary key (opsional jika namanya 'id')
    protected $primaryKey = 'id';

    // Kolom-kolom yang diizinkan untuk diisi secara massal (Mass Assignment)
    protected $fillable = [
        'no_nota',
        'kepada',
        'tanggal',
        'nama_pembeli',
        'nama_penjual',
        'ttd_pembeli',
        'ttd_penjual',
        'total_harga',
    ];

    /**
     * Relasi One-to-Many ke tabel nota_items
     * Satu Nota memiliki banyak Item/Barang
     */
    public function items()
    {
        // hasMany(NamaModelTarget, 'foreign_key_di_tabel_target', 'local_key')
        return $this->hasMany(NotaItem::class, 'nota_id', 'id');
    }
}