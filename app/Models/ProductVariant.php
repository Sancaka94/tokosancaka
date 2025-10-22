<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name'];

    /**
     * Dapatkan produk yang memiliki varian ini.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Dapatkan semua opsi untuk varian ini (misal: "Merah", "Biru").
     */
    public function options()
    {
        return $this->hasMany(ProductVariantOption::class);
    }
}
