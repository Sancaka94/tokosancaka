<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'Pengguna';
    protected $primaryKey = 'id_pengguna';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Tabel Pengguna memiliki created_at & deleted_at, namun tidak memiliki updated_at
     */
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = null;

    /**
     * Kolom password khusus pada tabel Pengguna (Laravel 11/12/13)
     */
    protected $authPasswordName = 'password_hash';

    protected $fillable = [
        'parent_id',
        'nama_lengkap',
        'nomor_rm',
        'email',
        'jenis_kelamin',
        'instansi_perusahaan',
        'no_wa',
        'store_name',
        'store_logo_path',
        'province',
        'regency',
        'district',
        'village',
        'postal_code',
        'address_detail',
        'pickup_point_code',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'password_hash',
        'remember_token',
        'setup_token',
        'profile_setup_at',
        'role',
        'akses_menu',
        'role_operasional',
        'fee_autokirim',
        'saldo',
        'balance_iak',
        'status',
        'is_whitelisted',
        'dana_access_token',
        'dana_auth_code',
        'dana_user_name',
        'dana_user_balance',
        'expo_token',
        'is_verified',
        'reset_token',
        'token_expiry',
        'last_seen_at',
        'ip_address',
        'user_agent',
        'latitude',
        'longitude',
        'pin',
        'last_seen',
        'fcm_token',
        'fcm_token_debug',
        'facebook_id',
        'store_logo',
        'district_id',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'pin',
        'setup_token',
        'reset_token',
        'dana_access_token',
        'dana_auth_code',
    ];

    protected function casts(): array
    {
        return [
            'password_hash'     => 'hashed',
            'pin'               => 'hashed',
            'saldo'             => 'decimal:2',
            'balance_iak'       => 'decimal:2',
            'dana_user_balance' => 'decimal:2',
            'is_verified'       => 'boolean',
            'is_whitelisted'    => 'boolean',
            'profile_setup_at'  => 'datetime',
            'token_expiry'      => 'datetime',
            'created_at'        => 'datetime',
            'last_seen_at'      => 'datetime',
            'last_seen'         => 'datetime',
            'deleted_at'        => 'datetime',
        ];
    }

    /**
     * Override getter password autentikasi Laravel
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /**
     * Alias atribut $user->name agar kompatibel dengan komponen bawaan Laravel
     */
    public function getNameAttribute(): string
    {
        return $this->nama_lengkap ?? 'Admin Sancaka';
    }

    /**
     * Helper pengecekan role Admin / Koordinator
     */
    public function isAdmin(): bool
    {
        return in_array(strtolower((string) $this->role), ['admin', 'koordinator'], true);
    }

    /**
     * Relasi ke riwayat email milik pengguna ini
     */
    public function emails()
    {
        return $this->hasMany(Email::class, 'user_id', 'id_pengguna');
    }
}
