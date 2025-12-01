<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Import model-model yang direlasikan
use App\Models\Store;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductVariantType;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\ProductReview; // Pastikan ini di-import

class Product extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'slug',
        'store_id',
        'sku',
        'category_id',
        'category',
        'tags',
        'description',
        'image_url', 
        'store_name',
        'seller_name',
        'seller_city',
        'seller_logo',
        'seller_wa',
        'price',
        'original_price',
        'discount_percentage',
        'stock',
        'weight',
        'status',
        'is_new',
        'is_bestseller',
        'rating',
        'sold_count',
        'width',
        'height',
        'length',
        'jenis_barang', 
        'is_promo',             
        'is_shipping_discount', 
        'is_free_shipping',     
    ];

    /**
     * Casting tipe data.
     */
    protected $casts = [
        'tags' => 'array',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_promo' => 'boolean',
        'is_shipping_discount' => 'boolean',
        'is_free_shipping' => 'boolean',
    ];

    /**
     * Relasi ke Store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    /**
     * Relasi ke Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id'); 
    }
    
    // --- PERBAIKAN DI SINI: HANYA ADA SATU FUNGSI REVIEWS ---
    /**
     * Dapatkan semua ulasan untuk produk ini.
     * Menggunakan model ProductReview.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    // --- ACCESSOR RATA-RATA RATING ---
    public function getAverageRatingAttribute()
    {
        return (float) $this->reviews()->avg('rating') ?: 0;
    }

    /**
     * Relasi ke Atribut Produk.
     */
    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /**
     * Relasi ke Tipe Varian.
     */
    public function productVariantTypes(): HasMany
    {
        return $this->hasMany(ProductVariantType::class);
    }

    /**
     * Relasi ke Kombinasi Varian.
     */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Relasi ke Gambar Produk (Multi Image).
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /**
     * Relasi ke Item Order (Opsional, jika ada order).
     */
    public function orderItems(): HasMany
    {
        // Pastikan model OrderItem ada jika ingin menggunakan ini, 
        // jika belum ada biarkan dikomentari atau hapus.
        // return $this->hasMany(OrderItem::class, 'product_id', 'id');
        return $this->hasMany(\App\Models\OrderItem::class, 'product_id');
    }

    /**
     * Menggunakan 'slug' untuk route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}