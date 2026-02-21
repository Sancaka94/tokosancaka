<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $table = 'tenants';

    /**
     * Tambahkan kolom baru ke fillable agar bisa disimpan
     * saat pendaftaran dan aktivasi webhook.
     */
    protected $fillable = [
        'name',
        'subdomain',
        'whatsapp',   // <--- Tambahkan ini (Penting untuk Notifikasi)
        'category',
        'package',    // <--- Tambahkan ini (monthly/yearly)
        'logo',
        'status',     // active/inactive
        'expired_at', // <--- Tambahkan ini (Untuk Masa Aktif)
    ];

    /**
     * Casting expired_at menjadi objek Carbon (Tanggal)
     * agar Bapak bisa pakai fungsi seperti ->diffInDays() atau ->isPast()
     */
    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * Relasi: Satu Tenant memiliki banyak User
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Helper untuk mengecek apakah tenant masih aktif atau sudah expired
     * Cara panggil: if($tenant->is_active_status)
     */
    public function getIsActiveStatusAttribute()
    {
        if ($this->status !== 'active') return false;
        if (!$this->expired_at) return true; // Jika tidak ada expired berarti permanen
        return now()->isBefore($this->expired_at);
    }

    /**
     * Helper untuk mendapatkan URL lengkap subdomain
     */
    public function getFullUrlAttribute()
    {
        $protocol = request()->secure() ? 'https://' : 'http://';
        return $protocol . $this->subdomain . '.tokosancaka.com';
    }

    /**
     * Helper untuk sisa hari masa aktif
     */
    public function getRemainingDaysAttribute()
    {
        if (!$this->expired_at) return 0;
        return now()->diffInDays($this->expired_at, false);
    }

    // Tambahkan di dalam class Tenant
    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function devices()
    {
        return $this->hasMany(TenantDevice::class);
    }
}
