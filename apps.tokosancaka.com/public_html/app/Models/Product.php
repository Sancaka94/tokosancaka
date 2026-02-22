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
        'weight',       // <-- BERAT (gram)
        'stock',        // Sisa Stok
        'sku',          // <--- WAJIB TAMBAHKAN INI (JANGAN LUPA)
        'sold',         // Jumlah Terjual
        'unit',         // Satuan (pcs, kg, dll)
        'supplier',     // Nama Supplier
        'image',        // Path Gambar
        'stock_status', // available / out_of_stock
        'is_active',    // Status aktif/non-aktif
        'type',         // physical / service
        'has_variant',  // Penanda jika produk punya varian
        'barcode', // <--- TAMBAHKAN INI
        'is_best_seller', // <-- BADGE
        'is_terlaris',    // <-- BADGE
        'is_new_arrival', // <-- BADGE
        'is_flash_sale',  // <-- BADGE
    ];

    /**
     * Casting tipe data agar formatnya sesuai saat diambil.
     */
    protected $casts = [
        'base_price'     => 'decimal:2',
        'sell_price'     => 'decimal:2',
        'stock'          => 'integer',
        'sold'           => 'integer',
        'weight'         => 'integer',
        'is_active'      => 'boolean',
        'has_variant'    => 'boolean',
        'is_best_seller' => 'boolean',
        'is_terlaris'    => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_flash_sale'  => 'boolean',
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
     * Relasi ke Sub Varian (Has Many Through)
     * Mengambil semua sub varian milik produk ini (melewati tabel varian)
     */
    public function subVariants()
    {
        return $this->hasManyThrough(
            ProductSubVariant::class, // Model Target (Sub Varian)
            ProductVariant::class,    // Model Perantara (Varian)
            'product_id',             // Foreign key di tabel perantara (product_variants)
            'product_variant_id',     // Foreign key di tabel target (product_sub_variants)
            'id',                     // Local key di tabel ini (products)
            'id'                      // Local key di tabel perantara (product_variants)
        );
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

    // Relasi ke Resep (Bahan-bahan penyusun produk ini)
    public function recipeItems()
    {
        return $this->hasMany(ProductRecipe::class, 'parent_product_id');
    }

    // [FITUR UTAMA] Hitung HPP Realtime
    public function getCalculatedHppAttribute()
    {
        // 1. Jika ini barang dagang murni / bahan baku (tidak punya resep)
        if ($this->recipeItems->count() == 0) {
            return $this->base_price; // Ambil dari harga beli terakhir
        }

        // 2. Jika punya resep (Manufaktur / Jasa), hitung total biaya komponennya
        $totalHpp = 0;
        foreach ($this->recipeItems as $item) {
            if ($item->child_product_id) {
                // Ambil harga beli (base_price) dari bahan baku saat ini
                $bahanBaku = $item->childProduct;
                $hargaBahan = $bahanBaku ? $bahanBaku->base_price : 0;
                $totalHpp += ($hargaBahan * $item->quantity);
            } else {
                // Biaya custom (Tenaga kerja, Listrik, Air)
                $totalHpp += ($item->cost_per_unit * $item->quantity);
            }
        }

        return $totalHpp;
    }

}
