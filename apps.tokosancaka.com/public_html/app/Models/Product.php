<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Wajib import ini untuk Slug

class Product extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $table = 'products';

    /**
     * Kolom yang boleh diisi (Mass Assignment).
     * Sudah disesuaikan dengan fitur Varian & Kategori.
     */
    protected $fillable = [
        'tenant_id',    // <--- TAMBAHKAN INI DI BARIS PALING ATAS
        'category_id',
        'name',
        'slug',         // URL ramah SEO
        'description',  // Deskripsi produk
        'base_price',   // Harga Modal
        'sell_price',   // Harga Jual
        'stock',        // Sisa Stok
        'sold',         // Jumlah Terjual
        'unit',         // Satuan (pcs, kg, dll)
        'supplier',     // Nama Supplier
        'image',        // Path Gambar
        'stock_status', // available / out_of_stock
        'is_active',    // Status aktif/non-aktif
        'type',         // physical / service
        'has_variant',  // Penanda jika produk punya varian
        'barcode', // <--- TAMBAHKAN INI
    ];

    /**
     * Casting tipe data agar formatnya sesuai saat diambil.
     */
    protected $casts = [
        'base_price'  => 'decimal:2',
        'sell_price'  => 'decimal:2',
        'stock'       => 'integer',
        'sold'        => 'integer',
        'is_active'   => 'boolean', // Ubah 1/0 jadi true/false
        'has_variant' => 'boolean', // Ubah 1/0 jadi true/false
    ];

    // --- RELATIONSHIPS ---

    /**
     * Relasi ke Kategori (Inverse One-to-Many)
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke Varian (One-to-Many)
     * Produk Induk bisa memiliki banyak Varian.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Relasi ke OrderDetail (History Transaksi)
     */
    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    // --- ACCESSORS ---

    /**
     * Hitung profit otomatis (Margin).
     * Panggil: $product->profit
     */
    public function getProfitAttribute()
    {
        return $this->sell_price - $this->base_price;
    }

    // --- BOOT METHODS ---

    /**
     * Otomatisasi Slug saat Create & Update
     */
    protected static function boot()
    {
        parent::boot();

        // Saat Membuat (Creating)
        static::creating(function ($product) {
            if (empty($product->slug)) {
                // Buat slug unik: nama-produk-acak5karakter
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });

        // Saat Mengupdate (Updating)
        static::updating(function ($product) {
            // Jika nama berubah, update slug juga
            if ($product->isDirty('name') && !$product->isDirty('slug')) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });
    }


}
