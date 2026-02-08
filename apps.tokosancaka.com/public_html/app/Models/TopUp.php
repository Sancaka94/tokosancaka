<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class TopUp extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

 protected $table = 'top_ups';

    // 1. Konstanta Status (Best Practice agar tidak Typo)
    const STATUS_PENDING = 'PENDING';
    const STATUS_SUCCESS = 'SUCCESS'; // Sesuaikan dengan DB jika pakai 'PAID'
    const STATUS_PAID    = 'PAID';    // Alias untuk SUCCESS jika DB pakai ini
    const STATUS_FAILED  = 'FAILED';
    const STATUS_EXPIRED = 'EXPIRED';

    // 2. Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'tenant_id',
        'affiliate_id',   // Relasi ke User/Member
        'reference_no',   // ID Transaksi (Invoice)
        'amount',         // Nominal Request
        'unique_code',    // Kode Unik (jika transfer bank manual)
        'total_amount',   // Nominal + Kode Unik
        'status',         // PENDING, SUCCESS, FAILED
        'payment_method', // DOKU, BANK_TRANSFER, dll
        'response_payload' // JSON response dari Payment Gateway
    ];

    // 3. Casting Tipe Data Otomatis
    protected $casts = [
        'amount'           => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'unique_code'      => 'integer',
        'response_payload' => 'array', // [PENTING] Agar JSON di DB otomatis jadi Array di PHP
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // =================================================================
    // RELASI (RELATIONSHIPS)
    // =================================================================

    /**
     * Relasi ke Affiliate (Member/User yang Topup)
     */
    public function affiliate()
    {
        // Sesuaikan 'App\Models\Affiliate' jika model user Anda bernama 'User'
        // Jika affiliate_id menyimpan ID dari tabel users, ganti ke User::class
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    /**
     * Relasi ke Tenant (Pemilik Toko/Subdomain)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // =================================================================
    // ACCESSOR (ATTRIBUTE TAMBAHAN)
    // =================================================================

    /**
     * Helper untuk menampilkan rupiah dengan mudah
     * Cara pakai: $topup->formatted_total
     */
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Helper untuk cek apakah pembayaran sukses
     */
    public function isPaid()
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_PAID]);
    }
}
