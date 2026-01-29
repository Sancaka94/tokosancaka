<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class ProductVariant extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    /**
     * Nama tabel di database.
     */
    protected $table = 'product_variants';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'tenant_id',
        'product_id', // ID Produk Induk
        'name',       // Nama Varian (Contoh: "Merah / XL")
        'price',      // Harga khusus varian
        'stock',      // Stok khusus varian
        'sku',        // Kode unik varian (Opsional)
        'barcode', // <--- TAMBAHKAN INI
    ];

    /**
     * Casting tipe data agar outputnya sesuai saat dipanggil.
     */
    protected $casts = [
        'price' => 'integer', // Atau 'decimal:2' jika butuh sen
        'stock' => 'integer',
    ];

    /**
     * Relasi ke Produk Induk (Inverse One-to-Many).
     * Satu varian pasti milik satu produk.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
