<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpobProduct extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'ppob_products';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'buyer_sku_code',
        'product_name',
        'category',
        'brand',
        'type',
        'seller_name',
        'price',
        'sell_price',
        'buyer_product_status',
        'seller_product_status',
        'unlimited_stock',
        'stock',
        'multi',
        'start_cut_off',
        'end_cut_off',
        'desc',
        'admin_fee',
        'commission',
        'max_price',
        'max_buy_price',
    ];

    /**
     * Konversi tipe data otomatis.
     * Mengubah TINYINT(1) menjadi boolean (true/false) dan harga menjadi float/integer.
     */
    protected $casts = [
        'price' => 'float',
        'max_price' => 'float', // <--- Tambahkan ini agar saat dipanggil jadi angka, bukan string
        'sell_price' => 'float',
        'admin_fee' => 'float',
        'commission' => 'float',
        'buyer_product_status' => 'boolean',
        'seller_product_status' => 'boolean',
        'unlimited_stock' => 'boolean',
        'multi' => 'boolean',
    ];
}