<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\DatabaseNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

    /**
     * Konfigurasi Tabel dan Primary Key
     */
    protected $table = 'Pengguna';
    protected $primaryKey = 'id_pengguna';
    public $incrementing = true; // Set true jika id_pengguna adalah auto-increment
    protected $keyType = 'int';  // Sesuaikan dengan tipe data id_pengguna (int/string)

    /**
     * Nonaktifkan updated_at sesuai permintaan
     */
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    /**
     * Atribut yang dapat diisi massal
     */
    protected $fillable = [
        'nomor_rm', 'nama_lengkap', 'email', 'password', 'no_wa',
        'store_name', 'province', 'regency', 'district', 'village',
        'postal_code', 'address_detail', 'bank_name', 'bank_account_name',
        'bank_account_number', 'store_logo_path', 'setup_token',
        'profile_setup_at', 'role', 'saldo', 'balance_iak', 'status',
        'is_verified', 'reset_token', 'token_expiry', 'ip_address',
        'user_agent', 'latitude', 'longitude', 'last_seen_at',
        'last_seen', 'expo_token', 'dana_access_token', 'dana_auth_code',
        'dana_user_name', 'dana_user_balance', 'fcm_token', fcm_token_debug
    ];

    /**
     * Atribut yang disembunyikan
     */
    protected $hidden = [
        'password_hash', 'remember_token', 'setup_token', 'reset_token', 'password'
    ];

    /**
     * Casting data
     */
    protected function casts(): array
    {
        return [
            'profile_setup_at' => 'datetime',
            'token_expiry'     => 'datetime',
            'last_seen_at'     => 'datetime',
            'last_seen'        => 'datetime',
            'is_verified'      => 'boolean',
            'saldo'            => 'decimal:2',
            'created_at'       => 'datetime',
            'deleted_at'       => 'datetime',
        ];
    }

    // --- Otentikasi Kustom ---

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password_hash'] = Hash::make($value);
        }
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // --- Relasi Penting ---

    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'user_id', 'id_pengguna');
    }

    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_pengguna_pembeli', 'id_pengguna');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id_pengguna');
    }

    // --- Method Notifikasi Broadcast ---

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Models.User.' . $this->getKey();
    }
}
