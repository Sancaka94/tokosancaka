<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    // Nama tabel (opsional jika sesuai standar plural, tapi baik untuk ketegasan)
    protected $table = 'product_images';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'product_id',
        'path',
        'sort_order', // Pastikan kolom ini ada di database Anda
    ];

    /**
     * Relasi kebalikan: Gambar milik satu Produk
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}