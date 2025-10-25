<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- Pastikan ini di-import

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Nama tabel database yang terkait dengan model ini.
     * Opsional jika nama tabel Anda adalah 'order_items' (plural dari nama model).
     *
     * @var string
     */
    // protected $table = 'order_items';

    /**
     * Nonaktifkan timestamps (created_at, updated_at) jika tabel Anda tidak memilikinya.
     * Jika tabel Anda punya, hapus atau set ke true.
     *
     * @var bool
     */
    public $timestamps = false; // Sesuaikan jika tabel Anda punya timestamps

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * ✅ DIPERBAIKI: Menambahkan 'product_variant_id'.
     */
    protected $fillable = [
        'order_id',           // Foreign key ke tabel 'orders'
        'product_id',         // Foreign key ke tabel 'products'
        'product_variant_id', // Foreign key ke tabel 'product_variants' (BISA NULL)
        'quantity',           // Jumlah item
        'price',              // Harga satuan item pada saat checkout
        // Tambahkan kolom lain yang relevan jika ada (misal: 'item_name', 'discount')
    ];

    /**
     * Mendefinisikan relasi bahwa setiap item pesanan 'milik' satu pesanan (Order).
     * Relasi inverse dari Order->hasMany(OrderItem::class).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order(): BelongsTo
    {
        // Eloquent akan otomatis mencari foreign key 'order_id'
        return $this->belongsTo(Order::class);
    }

    /**
     * Mendefinisikan relasi bahwa setiap item pesanan terkait dengan satu Product (produk utama).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        // Eloquent akan otomatis mencari foreign key 'product_id'
        return $this->belongsTo(Product::class);
    }

    /**
     * ✅ BARU: Mendefinisikan relasi bahwa item pesanan INI (mungkin) terkait dengan satu ProductVariant.
     * Relasi ini bersifat opsional karena item mungkin tidak memiliki varian.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant(): BelongsTo
    {
        // Nama method harus 'variant' agar cocok dengan pemanggilan di controller ('items.variant')
        // Parameter kedua adalah foreign key di tabel 'order_items' (tabel model ini)
        // Parameter ketiga adalah owner key (primary key) di tabel 'product_variants' (defaultnya 'id')
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    // Anda bisa menambahkan method atau accessor/mutator lain di sini jika perlu
    // Contoh accessor untuk mendapatkan nama item (produk + varian)
    // public function getItemNameAttribute(): string
    // {
    //     $productName = $this->product->name ?? 'Produk Tidak Ada';
    //     $variantName = '';
    //     if ($this->variant) {
    //         $comboString = $this->variant->combination_string ? str_replace(';', ', ', $this->variant->combination_string) : $this->variant->sku_code;
    //         $variantName = ' (' . ($comboString ?: 'Varian N/A') . ')';
    //     }
    //     return $productName . $variantName;
    // }
}
