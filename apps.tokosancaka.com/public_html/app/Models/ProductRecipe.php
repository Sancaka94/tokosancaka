<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $guarded = [];

    public function childProduct()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
