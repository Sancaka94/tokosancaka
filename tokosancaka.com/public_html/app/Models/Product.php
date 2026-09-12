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
use App\Models\ProductReview;
use App\Models\PpobProduct;

class Product extends Model
{
    use HasFactory;

    /**
     * Atribut yang TIDAK BOLEH diisi secara massal.
     * Menggunakan guarded jauh lebih ringkas daripada fillable.
     */
    protected $guarded = ['id'];

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
        
        // Casting PPOB
        'is_digital' => 'boolean',
        'seller_product_status' => 'boolean',
        'multi' => 'boolean',
        'unlimited_stock' => 'boolean',
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
    public function categoryRelation(): BelongsTo 
    {
        return $this->belongsTo(Category::class, 'category_id'); 
    }
    
    /**
     * Dapatkan semua ulasan untuk produk ini.
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
     * Relasi ke Item Order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'product_id');
    }

    /**
     * Menggunakan 'slug' untuk route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}