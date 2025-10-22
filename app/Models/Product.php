<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'category_id', // PERBAIKAN: Seharusnya 'category_id' bukan 'category'
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
        'attributes_data',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data asli.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array', // Memberitahu Laravel bahwa kolom 'tags' adalah JSON/array
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
    ];
    
    /**
     * Mendefinisikan relasi bahwa produk ini dimiliki oleh satu toko.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    /**
     * PERBAIKAN: Memindahkan relasi category ke dalam class.
     * Mendefinisikan relasi bahwa produk ini dimiliki oleh satu kategori.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Menggunakan 'slug' untuk route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}

