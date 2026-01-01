<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'price_at_order', 'quantity', 'subtotal'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}