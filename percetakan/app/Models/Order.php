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

   /**
     * ACCESSOR: Menghitung Profit Bersih secara Dinamis
     * Cara panggil di Controller: $order->profit
     */
    public function getProfitAttribute()
    {
        // 1. Hitung Gross Profit dari setiap item
        // Rumus: (Harga Jual saat itu - Modal saat itu) * Qty
        $grossProfit = $this->details->sum(function ($item) {
            // Pastikan kolom base_price_at_order ada di tabel order_details
            // Jika null, anggap 0 (safety)
            $modal = $item->base_price_at_order ?? 0; 
            return ($item->price_at_order - $modal) * $item->quantity;
        });

        // 2. Kurangi dengan Diskon Global (jika ada)
        // Profit Bersih = Gross Profit - Diskon Nota
        return $grossProfit - ($this->discount_amount ?? 0);
    }
}