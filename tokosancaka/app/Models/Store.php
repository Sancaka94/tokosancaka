<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User; // <-- Pastikan ini ada
use App\Models\Marketplace; // <-- PASTIKAN INI DI-IMPORT
use App\Models\Product; // <-- PERBAIKAN: Import model Product

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'province',
        'regency',
        'district',
        'village',
        'address_detail',
        'zip_code',
        'latitude',
        'longitude',
        'doku_sac_id',
        
        // --- PERBAIKAN: Tambahkan kolom baru DOKU ---
        'doku_status',
        'doku_balance_available',
        'doku_balance_pending',
        'doku_balance_last_updated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // --- PERBAIKAN: Atur casting untuk tipe data ---
        'doku_balance_available' => 'decimal:2',
        'doku_balance_pending' => 'decimal:2',
        'doku_balance_last_updated' => 'datetime',
        'latitude' => 'decimal:8', // Sesuaikan presisi jika perlu
        'longitude' => 'decimal:8', // Sesuaikan presisi jika perlu
    ];

    /**
     * Get the user that owns the store.
     */
    public function user(): BelongsTo
    {
        // ✅ DIPERBAIKI: Menyesuaikan dengan primary key kustom 'id_pengguna'
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
    
    /**
     * Get the products associated with the store.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    
    /**
     * Get the marketplace entries associated with the store.
     */
    public function marketplaces(): HasMany
    {
        // Menghubungkan 'id' (di stores) ke 'store_id' (di marketplaces)
        return $this->hasMany(Marketplace::class, 'store_id', 'id');
    }
}