<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * Aktifkan timestamps agar created_at & updated_at terisi otomatis.
     * (Di view 'show' kita menampilkan tanggal update terakhir).
     */
    public $timestamps = true; 

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'category_id',  // Opsional (jika ada fitur kategori)
        'name',
        'base_price',   // Harga Modal
        'sell_price',   // Harga Jual (Baru)
        'unit',
        'image',
        'stock',        // Sisa Stok (Baru)
        'sold',         // Jumlah Terjual (Baru)
        'supplier',     // Nama Supplier (Baru)
        'stock_status'  // Status (available/unavailable)
    ];

    /**
     * Casting tipe data untuk memastikan format angka benar.
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'stock'      => 'integer',
        'sold'       => 'integer',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Relasi ke Kategori (Opsional)
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke OrderDetail (Untuk melihat riwayat transaksi produk ini)
     */
    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    // --- ACCESSOR (Tambahan Fitur) ---

    /**
     * Hitung profit otomatis.
     * Cara panggil di blade: $product->profit
     */
    public function getProfitAttribute()
    {
        return $this->sell_price - $this->base_price;
    }
}