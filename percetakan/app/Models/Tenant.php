<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    // Nama tabel di database (opsional jika nama tabelnya jamak 'tenants')
    protected $table = 'tenants';

    // Kolom yang boleh diisi secara massal (create/update)
    protected $fillable = [
        'name',         // Nama Toko/Percetakan
        'subdomain',    // Subdomain (misal: gemini)
        'category',     // Kategori (misal: percetakan, retail, fnb)
        'logo',         // (Opsional) path logo
        'status',       // (Opsional) active/inactive
    ];

    /**
     * Relasi: Satu Tenant memiliki banyak User
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Helper untuk mendapatkan URL lengkap subdomain
     * Cara panggil: $tenant->full_url
     */
    public function getFullUrlAttribute()
    {
        $protocol = request()->secure() ? 'https://' : 'http://';
        return $protocol . $this->subdomain . '.tokosancaka.com';
    }
}
