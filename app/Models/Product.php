<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Pastikan HasMany di-import

// Import model-model yang direlasikan
use App\Models\Store;
use App\Models\Category;
use App\Models\Review; // Jika Anda menggunakan Review
use App\Models\ProductAttribute; // Tambahkan ini
use App\Models\ProductVariantType; // Tambahkan ini
use App\Models\ProductVariant; // Tambahkan ini


class Product extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'store_id',
        'sku',
        'category_id', // Pastikan ini ada
        //'category', // Hapus kolom 'category' jika tidak ada di DB
        'tags',
        'description',
        'image_url', // Ganti image_url ke image jika nama kolom di DB adalah image
        'store_name',
        'seller_name',
        'seller_city',
        'seller_logo',
        'seller_wa',
        'price',
        'original_price',
        'discount_percentage', // Periksa apakah kolom ini ada
        'stock',
        'weight',
        'status',
        'is_new',
        'is_bestseller',
        'rating', // Periksa apakah kolom ini ada
        'sold_count', // Periksa apakah kolom ini ada
        'width',
        'height',
        'length',
        'image_url',
        'jenis_barang', // <-- DITAMBAHKAN: Pastikan kolom ini ada di DB Anda
        // 'attributes_data', // Hapus jika Anda beralih ke tabel relasi
    ];

    /**
     * Atribut yang harus di-cast ke tipe data asli.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        // 'attributes_data' => 'array', // Hapus jika tidak digunakan lagi
    ];

    /**
     * Mendefinisikan relasi bahwa produk ini dimiliki oleh satu toko.
     */
    public function store(): BelongsTo
    {
        // Pastikan foreign key 'store_id' ada di tabel products
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Mendefinisikan relasi bahwa produk ini dimiliki oleh satu kategori.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Dapatkan semua ulasan untuk produk ini.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // --- RELASI BARU YANG DITAMBAHKAN ---

    /**
     * Mendapatkan semua atribut yang terkait dengan produk ini.
     */
    public function productAttributes(): HasMany
    {
        // Nama relasi harus sama persis dengan yang dipanggil di Controller ('productAttributes')
        // Foreign key defaultnya adalah 'product_id'
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Mendapatkan semua tipe varian yang dimiliki produk ini (misal: Warna, Ukuran).
     */
    public function productVariantTypes(): HasMany
    {
        // Nama relasi harus sama persis ('productVariantTypes')
        return $this->hasMany(ProductVariantType::class);
    }

    /**
     * Mendapatkan semua kombinasi varian (SKU) yang dimiliki produk ini.
     */
    public function productVariants(): HasMany
    {
         // Nama relasi harus sama persis ('productVariants')
        return $this->hasMany(ProductVariant::class);
    }

    // --- AKHIR RELASI BARU ---


    /**
     * Menggunakan 'slug' untuk route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // OPSIONAL: Accessor untuk image_url jika kolom Anda adalah 'image'
    // public function getImageUrlAttribute()
    // {
    //     return $this->image;
    // }
}