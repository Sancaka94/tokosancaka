<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Nonaktifkan timestamps karena tabel order_items tidak memilikinya.
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal.
     * ✅ DIPERBAIKI: Memastikan semua kolom yang dibutuhkan ada di sini.
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    /**
     * Mendefinisikan relasi bahwa setiap item pesanan 'milik' satu pesanan.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
