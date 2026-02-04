<?php

namespace App\Models;

// 1. GANTI import Model biasa dengan Authenticatable
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import



// 2. GANTI 'extends Model' menjadi 'extends Authenticatable'
class Affiliate extends Authenticatable
{
    use HasFactory, Notifiable;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $table = 'affiliates';

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'whatsapp',
        'bank_name',
        'bank_account_number',
        'coupon_code',
        'is_active',
        'balance',
        'dana_merchant_balance',
        'dana_user_balance',    // Tambahkan untuk Saldo Riil DANA    // Saldo DANA Merchant (Pusat)
        'dana_access_token',       // Token DANA
        'dana_auth_code',          // Auth Code DANA
        'pin'
    ];

    protected $hidden = [
        'pin',
        'remember_token', // Tambahkan ini juga
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'balance' => 'decimal:2' // Casting saldo agar presisi
    ];

    // --- BAGIAN PENTING UNTUK LOGIN ---

    /**
     * Memberitahu Laravel bahwa field password kita bernama 'pin'
     * Tanpa ini, login akan gagal karena mencari kolom 'password'
     */
    public function getAuthPassword()
    {
        return $this->pin;
    }

    /**
     * (Opsional) Jika ingin login pakai 'whatsapp', bukan 'email'
     * Berguna untuk reset password/notifikasi
     */
    public function routeNotificationForWhatsapp()
    {
        return $this->whatsapp;
    }

    // --- RELASI & ACCESSOR (Sudah Benar) ---

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'code', 'coupon_code');
    }

    public function getWhatsappLinkAttribute()
    {
        $number = $this->whatsapp;
        if (substr($number, 0, 1) == '0') {
            $number = '62' . substr($number, 1);
        }
        return "https://wa.me/{$number}";
    }
}
