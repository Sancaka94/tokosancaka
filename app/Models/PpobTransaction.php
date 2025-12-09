<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Import Model Terkait
use App\Models\User; 
use App\Models\PpobProduct;

class PpobTransaction extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'ppob_transactions';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     * Saya tambahkan 'payment_method' & 'payment_url' agar controller tidak error.
     */
    protected $fillable = [
        'user_id', 
        'order_id',
        'customer_wa',
        'buyer_sku_code', 
        'customer_no',
        'price',          // Harga Beli (Modal)
        'selling_price',  // Harga Jual
        'profit',         // Keuntungan
        'status',         // Pending, Processing, Success, Failed
        'sn',             // Serial Number / Token Listrik
        'message',        // Pesan dari Provider
        'payment_method', // Cth: saldo, DOKU_VA, TRIPAY_QRIS
        'desc',
        'payment_url'     // Link pembayaran (jika ada)
    ];

    /**
     * Casting tipe data otomatis.
     * Memastikan harga diperlakukan sebagai angka, bukan string.
     */
    protected $casts = [
        'price' => 'integer',
        'desc' => 'array',
        'selling_price' => 'integer',
        'profit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (Pembeli).
     * Foreign Key: user_id
     * Owner Key (di tabel users): id_pengguna
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    /**
     * Relasi ke Produk PPOB (Master Data).
     * Berguna untuk mengambil nama produk, kategori, atau brand.
     * Dihubungkan via: buyer_sku_code
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(PpobProduct::class, 'buyer_sku_code', 'buyer_sku_code');
    }
    
    /**
     * Accessor untuk Badge Status (Opsional, untuk View di Dashboard).
     * Cara pakai di blade: {!! $transaction->status_badge !!}
     */
    public function getStatusBadgeAttribute()
    {
        $status = strtolower($this->status);
        $color = 'secondary';
        
        if ($status == 'success') $color = 'success';
        elseif ($status == 'pending') $color = 'warning';
        elseif ($status == 'failed') $color = 'danger';
        elseif ($status == 'processing') $color = 'info';

        return "<span class='badge bg-{$color}'>{$this->status}</span>";
    }
}