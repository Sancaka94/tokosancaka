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
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        // --- ATRIBUT PRODUK UMUM (FISIK) ---
        'name',
        'slug',
        'store_id',
        'sku',
        'category_id',
        'category', // Nama kategori (string)
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
        'jenis_barang', // 1=Fisik, 2=Dokumen, 3=Mudah Pecah, dll
        'is_promo',             
        'is_shipping_discount', 
        'is_free_shipping',     

        // --- ATRIBUT PPOB (DIGITAL) ---
        'is_digital',           // Boolean: 1 = PPOB/Digital, 0 = Fisik
        'buyer_sku_code',       // Kode SKU dari Provider PPOB (Digiflazz/Tripay)
        'brand',                // Nama Operator (Telkomsel, PLN, dll)
        'type',                 // Tipe PPOB (Pulsa, Data, E-Money, Game)
        'start_cut_off',        // Jam mulai gangguan (00:00)
        'end_cut_off',          // Jam selesai gangguan
        'seller_product_status', // Status dari Provider (1=Aktif, 0=Gangguan)
        'multi',                // Boolean: Bisa transaksi ganda/tidak
        'unlimited_stock'       // Boolean: Stok tak terbatas (untuk digital)
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
    public function categoryRelation(): BelongsTo // Nama func diganti agar tidak bentrok dengan kolom 'category'
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