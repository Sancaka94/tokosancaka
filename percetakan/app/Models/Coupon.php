<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class Coupon extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    /**
     * Konfigurasi Timestamp
     * Set ke 'false' jika tabel Anda TIDAK punya kolom 'updated_at'.
     */
    public $timestamps = false;

    /**
     * Daftar kolom yang boleh diisi (Mass Assignment).
     */
    protected $fillable = [
        'tenant_id',
        'user_id',          // <--- PENTING: ID Pemilik Kupon (Affiliator)
        'code',
        'type',             // 'percent' atau 'fixed'
        'value',            // Nilai diskon
        'min_order_amount', // Minimal belanja (opsional)
        'max_discount_amount', // Maksimal potongan (opsional)
        'start_date',
        'expiry_date',
        'usage_limit',      // Batas penggunaan kupon
        'used_count',       // Jumlah kupon terpakai
        'is_active'
    ];

    /**
     * CASTING: Mengubah tipe data otomatis.
     */
    protected $casts = [
        'start_date' => 'datetime',
        'expiry_date' => 'datetime',
        'is_active' => 'boolean',
        'value' => 'integer',
        'min_order_amount' => 'integer',
        'used_count' => 'integer',
        'usage_limit' => 'integer',
    ];

    /**
     * RELASI KE USER (PEMILIK KUPON)
     * Ini wajib ada agar Controller bisa mengambil No HP pemilik kupon.
     */
    public function user()
    {
        // Pastikan nama kolom di database 'coupons' adalah 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke tabel orders (Untuk menghitung total omzet yg dihasilkan kupon ini)
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    /**
     * Fungsi Cek Validitas Kupon
     */
    public function isValid($totalOrder = 0)
    {
        $now = now();

        // 1. Cek Status Aktif
        if (!$this->is_active) {
            return false;
        }

        // 2. Cek Tanggal Mulai (Jika diatur)
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        // 3. Cek Tanggal Kadaluarsa (Jika diatur)
        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        // 4. Cek Batas Penggunaan (Kuota)
        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false;
        }

        // 5. Cek Minimal Belanja
        if ($this->min_order_amount > 0 && $totalOrder < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Helper untuk menghitung nominal potongan
     */
    public function calculateDiscount($totalOrder)
    {
        if ($this->type == 'percent') {
            $discount = ($this->value / 100) * $totalOrder;

            // Cek jika ada batas maksimal diskon (cap)
            if ($this->max_discount_amount > 0 && $discount > $this->max_discount_amount) {
                return $this->max_discount_amount;
            }

            return $discount;
        }

        // Jika tipe 'fixed' (potongan nominal langsung)
        return $this->value;
    }
}
