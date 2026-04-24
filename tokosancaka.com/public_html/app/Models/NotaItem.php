<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaItem extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara spesifik
    protected $table = 'nota_items';

    // Menentukan primary key
    protected $primaryKey = 'id';

    // Kolom-kolom yang diizinkan untuk diisi secara massal
    protected $fillable = [
        'nota_id',
        'nama_barang',
        'banyaknya',
        'harga',
        'jumlah',
    ];

    /**
     * Relasi Inverse One-to-Many ke tabel notas
     * Setiap Item dimiliki oleh satu Nota
     */
    public function nota()
    {
        // belongsTo(NamaModelParent, 'foreign_key', 'owner_key')
        return $this->belongsTo(Nota::class, 'nota_id', 'id');
    }
}