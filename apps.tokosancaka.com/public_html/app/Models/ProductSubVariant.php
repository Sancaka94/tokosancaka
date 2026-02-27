<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan trait multi-tenant lu aktif

class ProductSubVariant extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Filter otomatis per-toko

    protected $table = 'product_sub_variants';

    /**
     * Kolom yang boleh diisi (Mass Assignment).
     */
    protected $fillable = [
        'tenant_id',
        'product_variant_id',
        'name',
        'price',
        'stock',
        'sku',
        'barcode',
        'weight',
        'image',
        'is_active',
        'discount_type',  // <--- TAMBAHAN UNTUK DISKON (percent / nominal)
        'discount_value', // <--- TAMBAHAN UNTUK DISKON (Nominal/Persentase)
    ];

    /**
     * Casting tipe data agar formatnya sesuai saat dipanggil di API/View.
     */
    protected $casts = [
        'price'          => 'decimal:2',
        'stock'          => 'integer',
        'weight'         => 'integer',
        'is_active'      => 'boolean', // Ubah 1/0 di database jadi true/false
        'discount_value' => 'decimal:2', // Pastikan diskon terbaca sebagai angka desimal
    ];

    // --- RELATIONSHIPS ---

    /**
     * Relasi balik (Belongs To) ke Varian Induk
     * Contoh: Sub Varian "Kertas HVS" ini milik Varian "Berwarna"
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Relasi Pintas (Has One Through) ke Produk Utama (Paling Atas)
     * Ini kepake banget misal lu mau tau Sub Varian ini tuh punyanya Produk apa
     * tanpa harus query manual yang panjang.
     */
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,           // Model Tujuan Akhir (Produk Utama)
            ProductVariant::class,    // Model Perantara (Varian)
            'id',                     // Foreign key di tabel perantara (product_variants.id)
            'id',                     // Foreign key di tabel tujuan (products.id)
            'product_variant_id',     // Local key di tabel ini (product_sub_variants.product_variant_id)
            'product_id'              // Local key di tabel perantara (product_variants.product_id)
        );
    }
}
