<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // [WAJIB] Import Trait Tenant

class TopUp extends Model
{
    use HasFactory;
    use BelongsToTenant; // [WAJIB] Aktifkan Filter Tenant Otomatis

    protected $table = 'top_ups';

    // Konstanta Status (Agar koding lebih rapi & konsisten)
    const STATUS_PENDING = 'PENDING';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_PAID    = 'PAID';    // Alias untuk SUCCESS
    const STATUS_FAILED  = 'FAILED';
    const STATUS_EXPIRED = 'EXPIRED';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'tenant_id',        // Wajib ada di database & fillable
        'affiliate_id',     // ID Member / User yang melakukan TopUp
        'reference_no',     // Invoice (misal: POSTOPUP-...)
        'amount',           // Nominal TopUp
        'unique_code',      // Kode Unik (jika transfer manual)
        'total_amount',     // Nominal + Kode Unik
        'status',           // Status Transaksi
        'payment_method',   // DOKU, BANK_TRANSFER, dll
        'response_payload'  // JSON data dari Payment Gateway
    ];

    // Casting Tipe Data
    protected $casts = [
        'amount'           => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'unique_code'      => 'integer',
        'response_payload' => 'array', // Otomatis convert JSON DB ke Array PHP
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // =================================================================
    // RELASI (RELATIONSHIPS)
    // =================================================================

    /**
     * Relasi ke Member Affiliate (User)
     * Menghubungkan kolom 'affiliate_id' di tabel top_ups ke tabel 'users'
     */
    public function affiliate()
    {
        // Pastikan 'User' adalah model untuk member Anda
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    /**
     * Relasi ke Tenant (Toko)
     * Digunakan oleh Trait BelongsToTenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    // =================================================================
    // HELPER ATTRIBUTES
    // =================================================================

    /**
     * Helper cek apakah sudah lunas
     * Penggunaan: if ($topUp->is_paid) { ... }
     */
    public function getIsPaidAttribute()
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_PAID]);
    }

    /**
     * Helper format rupiah
     * Penggunaan: $topUp->formatted_amount
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }
}
