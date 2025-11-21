<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Store; // <-- Pastikan ini ada
use App\Models\Category; // (Opsional, jika ada relasi ke kategori)

class Marketplace extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     * @var string
     */
    protected $table = 'marketplaces';

    /**
     * Atribut yang boleh diisi secara massal.
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'sku',
        'category_id',
        'category',
        'price',
        'original_price',
        'discount_percentage',
        'stock',
        'status',
        'is_new',
        'is_bestseller',
        'sold_count',
        'description',
        'tags',
        'attributes_data',
        'image_url',
        'store_name',
        'seller_name',
        'seller_city',
        'seller_logo',
        'seller_wa',
        'is_flash_sale',
        'weight',
        'length',
        'width',
        'height',
        'jenis_barang',
        'rating',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     * @var array<string, string>
     */
    protected $casts = [
        'is_flash_sale' => 'boolean',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        'price' => 'float',
        'original_price' => 'float',
        'rating' => 'float',
        'weight' => 'integer',
        'length' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'stock' => 'integer',
        'sold_count' => 'integer',
    ];

    /**
     * ==========================================================
     * PERBAIKAN ROUTING (INI YANG PALING PENTING)
     * ==========================================================
     *
     * Memberitahu Laravel untuk menggunakan kolom 'slug'
     * saat mencari model di URL (Route Model Binding),
     * BUKAN 'id'.
     *
     * Ini akan memperbaiki error 404 "No query results for model".
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Mendefinisikan relasi ke model Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

   /**
     * ==========================================================
     * PERBAIKAN WAJIB 6: Definisikan relasi ke Store
     * ==========================================================
     */
    public function store()
    {
        // Ini menghubungkan 'store_id' di 'marketplaces' ke 'id' di 'stores'
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}