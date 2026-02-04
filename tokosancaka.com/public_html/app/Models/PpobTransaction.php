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
     * Kolom yang boleh diisi secara massal.
     * Sudah dilengkapi dengan kolom untuk Bot Telegram dan field teknis lainnya.
     */
    protected $fillable = [
        'idempotency_key',
        'user_id',
        'telegram_chat_id', // PENTING: Untuk menyimpan ID Chat user dari Telegram
        'order_id',
        'group_order_id',   // Ada di database Anda
        'buyer_sku_code',
        'customer_no',
        'customer_wa',
        'price',            // Harga Beli (Modal)
        'selling_price',    // Harga Jual
        'profit',           // Keuntungan
        'status',           // Pending, Processing, Success, Failed
        'rc',               // Response Code (cth: 00)
        'sn',               // Serial Number / Token Listrik
        'message',          // Pesan dari Provider
        'payment_method',   // Cth: SALDO_AGEN, DOKU, dll
        'desc',             // JSON deskripsi tambahan
        'payment_url',
    ];

    /**
     * Casting tipe data otomatis.
     */
    protected $casts = [
        'desc' => 'array',            // Otomatis decode JSON
        'price' => 'decimal:2',       // Menggunakan decimal agar presisi harga aman
        'selling_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'telegram_chat_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (Pembeli).
     */
    public function user(): BelongsTo
    {
        // Pastikan 'id_pengguna' adalah Primary Key di tabel users Anda.
        // Jika PK standar laravel adalah 'id', ganti 'id_pengguna' jadi 'id'.
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }

    /**
     * Relasi ke Produk PPOB (Master Data).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(PpobProduct::class, 'buyer_sku_code', 'buyer_sku_code');
    }

    /* -------------------------------------------------------------------------- */
    /* ACCESSOR & HELPER                           */
    /* -------------------------------------------------------------------------- */

    /**
     * Helper untuk menampilkan badge status HTML di Blade
     * Penggunaan: {!! $transaction->status_badge !!}
     */
    public function getStatusBadgeAttribute()
    {
        $status = strtolower($this->status);
        
        return match($status) {
            'success'    => '<span class="badge bg-success">Sukses</span>',
            'pending'    => '<span class="badge bg-warning text-dark">Pending</span>',
            'processing' => '<span class="badge bg-info text-dark">Proses</span>',
            'failed'     => '<span class="badge bg-danger">Gagal</span>',
            default      => '<span class="badge bg-secondary">' . $this->status . '</span>',
        };
    }

    /**
     * Helper format Rupiah
     * Penggunaan: $transaction->formatted_price
     */
    public function getFormattedSellingPriceAttribute()
    {
        return 'Rp ' . number_format($this->selling_price, 0, ',', '.');
    }

    /* -------------------------------------------------------------------------- */
    /* SCOPES (Query)                              */
    /* -------------------------------------------------------------------------- */

    /**
     * Filter hanya transaksi sukses
     * Penggunaan: PpobTransaction::success()->get();
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'Success');
    }

    /**
     * Filter transaksi milik User tertentu via Telegram
     * Penggunaan: PpobTransaction::telegram($chatId)->get();
     */
    public function scopeTelegram($query, $chatId)
    {
        return $query->where('telegram_chat_id', $chatId);
    }
}