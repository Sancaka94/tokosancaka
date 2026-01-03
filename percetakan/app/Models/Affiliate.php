<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model ini.
     * (Opsional jika nama tabel sudah jamak dari nama model: 'affiliates')
     */
    protected $table = 'affiliates';

    /**
     * Kolom yang boleh diisi secara massal (Mass Assignment).
     * Wajib diisi agar perintah Affiliate::create() di controller berfungsi.
     */
    protected $fillable = [
        'name',
        'address',
        'whatsapp',
        'bank_name',
        'bank_account_number',
        'coupon_code', // Kode unik afiliasi
        'is_active',   // Status aktif/tidak
        'balance', // <--- Tambahkan ini (Saldo Profit)
        'pin',
    ];

    // Sembunyikan PIN agar tidak terekspos di JSON response (Security)
    protected $hidden = [
        'pin',
    ];

    /**
     * Konversi tipe data otomatis.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Model Coupon.
     * Menghubungkan Affiliate dengan Coupon berdasarkan kode.
     * Berguna jika Anda ingin melihat detail diskon atau statistik penggunaan kupon ini.
     */
    public function coupon()
    {
        // Menghubungkan kolom 'coupon_code' di tabel affiliates 
        // dengan kolom 'code' di tabel coupons
        return $this->hasOne(Coupon::class, 'code', 'coupon_code');
    }
    
    /**
     * Accessor untuk memformat nomor WA (Opsional).
     * Contoh: Jika ingin selalu menampilkan format 62 di depan.
     */
    public function getWhatsappLinkAttribute()
    {
        // Ubah 08xx jadi 628xx untuk link WA
        $number = $this->whatsapp;
        if (substr($number, 0, 1) == '0') {
            $number = '62' . substr($number, 1);
        }
        return "https://wa.me/{$number}";
    }
}