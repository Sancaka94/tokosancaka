<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'coupon_id',
        'referral_id',
        'total_price',
        'discount_amount',
        'final_price',
        'status',
        'payment_status',
        'note'
    ];

    // Relasi ke detail pesanan (One to Many)
    public function details()
    {
        return $this.hasMany(OrderDetail::class);
    }

    // Relasi ke kupon
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}