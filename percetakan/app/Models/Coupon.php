<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Konfigurasi Timestamp
     * Set ke 'false' jika tabel Anda TIDAK punya kolom 'updated_at'.
     * Jika tabel Anda punya 'created_at' DAN 'updated_at', hapus baris ini.
     */
    public $timestamps = false; 

    /**
     * Daftar kolom yang boleh diisi (Mass Assignment).
     * Sesuai dengan struktur tabel di Database Anda.
     */
    protected $fillable = [
        'code',
        'type',                 // 'percent' atau 'fixed'
        'value',                // Nilai diskon
        'min_order_amount',     // Minimal belanja (opsional)
        'max_discount_amount',  // Maksimal potongan (opsional)
        'start_date',
        'expiry_date',          // <--- NAMA YANG BENAR (Sesuai DB)
        'usage_limit',          // Batas penggunaan kupon
        'used_count',           // Jumlah kupon terpakai
        'is_active'
    ];

    /**
     * CASTING: Mengubah tipe data otomatis saat diambil dari DB.
     * PENTING: Agar start_date & expiry_date dianggap sebagai Tanggal (Carbon), bukan Teks.
     */
    protected $casts = [
        'start_date' => 'datetime',
        'expiry_date' => 'datetime',
        'is_active' => 'boolean',
        'value' => 'integer', // atau 'decimal:2' jika butuh koma
        'min_order_amount' => 'integer',
        'used_count' => 'integer',
        'usage_limit' => 'integer',
    ];

    /**
     * Fungsi Cek Validitas Kupon
     * Memastikan kupon aktif, belum expired, dan memenuhi syarat belanja.
     * * @param int $totalOrder Total belanja saat checkout
     * @return bool
     */
    public function isValid($totalOrder = 0)
    {
        $now = now(); // Waktu sekarang

        // 1. Cek Status Aktif
        if (!$this->is_active) {
            return false;
        }

        // 2. Cek Tanggal Mulai (Jika diatur)
        if ($this->start_date && $now->lt($this->start_date)) {
            return false; // Belum mulai
        }

        // 3. Cek Tanggal Kadaluarsa (Jika diatur)
        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false; // Sudah expired
        }

        // 4. Cek Batas Penggunaan (Kuota)
        // Jika usage_limit diisi (>0) DAN used_count sudah >= limit
        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false; // Kuota habis
        }

        // 5. Cek Minimal Belanja
        if ($this->min_order_amount > 0 && $totalOrder < $this->min_order_amount) {
            return false; // Belum mencapai minimal belanja
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