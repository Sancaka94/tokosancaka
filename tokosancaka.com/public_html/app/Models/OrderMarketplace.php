<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\OrderItemMarketplace; 

class OrderMarketplace extends Model
{
    use HasFactory;

    /**
     * ==========================================================
     * PERBAIKAN FATAL (ERROR ANDA DARI SINI)
     * ==========================================================
     * Ganti 'NAMA_TABEL_PESANAN_ANDA' dengan nama asli di database.
     * JANGAN 'marketplaces'. Mungkin 'order_marketplace'?
     */
    protected $table = 'order_marketplace'; // <-- GANTI INI JIKA NAMA TABELNYA BEDA

    protected $fillable = [
        'store_id', 'user_id', 'invoice_number', 'subtotal', 'shipping_cost',
        'insurance_cost', 'cod_fee', 'total_amount', 'shipping_method',
        'payment_method', 'status', 'shipping_address', 'payment_url', 'shipping_resi'
    ];

    // Relasi ke User (Pembeli)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    // Relasi ke Store (Toko Penjual)
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    // Relasi ke Item-item pesanannya
    public function items(): HasMany
    {
        return $this->hasMany(OrderItemMerketplace::class, 'order_id');
    }
}