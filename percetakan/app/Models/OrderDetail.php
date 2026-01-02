<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    public $timestamps = true; // Sekarang tabel sudah punya kolom waktu

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',   // Kolom baru
        'price_at_order', // SESUAI DATABASE ANDA (bukan 'price')
        'quantity',       // SESUAI DATABASE ANDA (bukan 'qty', lihat gambar image_d0c72b.png)
        'subtotal',
        'file_design',    // Sesuai gambar database
        'width',
        'height'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}