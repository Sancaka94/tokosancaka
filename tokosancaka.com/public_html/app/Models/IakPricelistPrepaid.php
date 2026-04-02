<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IakPricelistPrepaid extends Model
{
    protected $table = 'iak_pricelist_prepaid';

    protected $fillable = [
        'operator', 'code', 'description', 'price', 'status', 'type', 'icon_url'
    ];
    // LOG LOG
}
