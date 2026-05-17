<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IakPricelistPrepaid extends Model
{
    protected $table = 'iak_pricelist_prepaid';

    protected $fillable = [
        'operator', 'code', 'description', 'price', 'status', 'type', 'icon_url'
    ];
    
    // Fungsi untuk menambahkan +50 ke setiap harga
    public function getPriceAttribute($value)
    {
        return $value + 50;
    }
}
