<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IakPricelistPostpaid extends Model
{
    protected $table = 'iak_pricelist_postpaid';

    protected $fillable = [
        'code', 'name', 'status', 'fee', 'komisi', 'type', 'category', 'province'
    ];

    // LOG LOG
}
