<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',          // Baru (ID Customer Registered)
        'customer_name',    // Nama Tamu (Guest) atau Nama User
        'customer_phone',
        'coupon_id',
        'total_price',
        'discount_amount',
        'final_price',
        'payment_method',   // Baru
        'payment_url',      // Baru
        'status',
        'base_price_at_order',
        'payment_status',
        'note'
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function attachments()
    {
        return $this->hasMany(OrderAttachment::class);
    }

    // Relasi ke Customer (User)
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Tambahkan ke app/Models/Order.php

  public function getProfitAttribute()
{
    // Ambil semua detail barang
    $grossProfit = $this->details->sum(function ($item) {
        // PENTING: Jika base_price_at_order 0, kita coba ambil dari master product sebagai cadangan (fallback)
        // Ini trik biar data lama gak error
        $modal = $item->base_price_at_order > 0 
                 ? $item->base_price_at_order 
                 : ($item->product->base_price ?? 0); 
                 
        return ($item->price_at_order - $modal) * $item->quantity;
    });

    return $grossProfit - ($this->discount_amount ?? 0);
}
}