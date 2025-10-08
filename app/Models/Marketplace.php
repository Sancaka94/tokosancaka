<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'price',
        'original_price', // Ditambahkan
        'stock',
        'sold_count',     // Ditambahkan
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
        'original_price' => 'float', // Ditambahkan
    ];

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
}

