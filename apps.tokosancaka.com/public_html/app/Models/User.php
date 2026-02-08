<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id', // Penanda User milik Toko mana
        'role',      // Jabatan (admin, staff, finance, operator, super_admin)
        'permissions', // <--- WAJIB ADA: Untuk menyimpan array hak akses (checklist)
        'saldo', // <--- TAMBAHKAN INI WAJIB
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
            'permissions' => 'array', // <--- PENTING: Mengubah JSON di DB jadi Array PHP otomatis
            'saldo' => 'decimal:2', // Agar dibaca sebagai angka
        ];
    }

    // --- RELASI ---

    /**
     * Relasi User ke Tenant (Toko)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // --- HELPER FUNCTIONS ---

    /**
     * Cek apakah user memiliki role tertentu.
     * Contoh: $user->hasRole('admin')
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Cek apakah user memiliki izin akses ke fitur spesifik.
     * Digunakan untuk logika Checklist Permission Matrix.
     * * Contoh: if ($user->canAccess('reports')) { ... }
     */
    public function canAccess($feature)
    {
        // 1. Jika Super Admin, berikan akses mutlak (Master Key)
        if ($this->role === 'super_admin') {
            return true;
        }

        // 2. Jika bukan Super Admin, cek di daftar permissions miliknya
        // Operator '?? []' mencegah error jika permissions null
        return in_array($feature, $this->permissions ?? []);
    }
}
