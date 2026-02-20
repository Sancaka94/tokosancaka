<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',      // Wajib ada untuk memisahkan data antar cabang/perusahaan
        'name',
        'email',
        'password',
        'role',           // superadmin, admin, atau operator
        'profile_photo',  // Untuk fitur upload logo/foto profil nanti
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke tabel tenants (Perusahaan/Cabang Parkir yang menaungi user ini)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relasi ke tabel transactions (Jika user ini adalah operator, dia punya histori transaksi)
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'operator_id');
    }

    /**
     * Helper functions untuk mempermudah pengecekan role di Controller & Blade
     * Contoh penggunaan di Blade: @if(auth()->user()->isSuperadmin()) ... @endif
     */
    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }
}
