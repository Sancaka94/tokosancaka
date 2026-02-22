<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class Order extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $fillable = [
        'tenant_id',
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
        'note',
        'shipping_cost',
        'customer_note', // <--- GANTI JADI INI
        'destination_address', // <--- TAMBAHKAN INI
        'courier_service',
        'shipping_ref',
        'is_escrow',
        'escrow_status',
    ];

    // --- TAMBAHKAN RELASI INI ---

    /**
     * Relasi ke Detail Barang (Order Items)
     */
    public function items()
    {
        // Pastikan Anda punya model OrderDetail
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    /**
     * Relasi ke Kupon (Jika ada)
     */
    public function coupon()
    {
        // Pastikan Anda punya model Coupon (App\Models\Coupon)
        // 'coupon_id' adalah nama kolom foreign key di tabel orders
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

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
