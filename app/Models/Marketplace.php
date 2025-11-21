<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Impor BelongsTo untuk relasi

class Marketplace extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplaces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category_id',      // Penting untuk relasi kategori
        'store_id',         // Penting untuk relasi toko
        'price',
        'original_price',
        'stock',
        'status',
        'sold_count',
        'description',
        'image_url',
        'is_flash_sale',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_flash_sale' => 'boolean',
        'price' => 'float',
        'original_price' => 'float',
    ];

    /**
     * Mendefinisikan relasi ke model Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Mendefinisikan relasi ke model Store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Accessor untuk menghitung persentase diskon secara dinamis.
     *
     * @return int
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->original_price > 0 && $this->price < $this->original_price) {
            return round((($this->original_price - $this->price) / $this->original_price) * 100);
        }
        return 0;
    }

    /**
     * PERBAIKAN: Memberitahu Laravel untuk menggunakan 'slug' saat membuat URL.
     * Ini akan secara otomatis menyelesaikan masalah "Missing parameter".
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}

