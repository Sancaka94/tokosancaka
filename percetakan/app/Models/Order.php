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

    // Accessor untuk memanggil $order->profit
    public function getProfitAttribute()
    {
        $totalModal = 0;

        foreach ($this->details as $item) {
            // Ambil harga modal dari produk terkait
            // Pastikan tabel products punya kolom 'buy_price' (harga beli/modal)
            $modalPerItem = $item->product->buy_price ?? 0; 
            $totalModal += ($modalPerItem * $item->quantity);
        }

        // Profit = Harga Jual Akhir - Total Modal
        return $this->final_price - $totalModal;
    }
}