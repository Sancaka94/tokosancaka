<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPpobDana extends Model
{
    use HasFactory;

    protected $table = 'orders_ppob_dana';
    protected $primaryKey = 'order_id';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'request_id',
        'product_id',
        'primary_param',
        'secondary_param',
        'dana_price_value',
        'dana_price_currency',
        'status_code',
        'status_status',
        'status_message',
        'serial_number',
        'token',
    ];

    public function product()
    {
        return $this->belongsTo(ProductDanaPpob::class, 'product_id', 'product_id');
    }
}