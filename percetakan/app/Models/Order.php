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

    // RELASI KE DETAIL ORDER
    public function details()
    {
        // SALAH: return hasMany(OrderDetail::class);
        // BENAR: Pakai $this->
        return $this->hasMany(OrderDetail::class);
    }

    // RELASI KE FILE LAMPIRAN
    public function attachments()
    {
        // BENAR: Pakai $   this->
        return $this->hasMany(OrderAttachment::class);
    }
}