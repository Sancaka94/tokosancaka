<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Marketplace;
use App\Models\Product;

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
        'seller_logo', // <-- DITAMBAHKAN: Wajib agar logo bisa tersimpan
        'province',
        'regency',
        'district',
        'village',
        'address_detail',
        'zip_code',
        'latitude',
        'longitude',
        'doku_sac_id',
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
        'doku_balance_available' => 'decimal:2',
        'doku_balance_pending' => 'decimal:2',
        'doku_balance_last_updated' => 'datetime',
        'latitude' => 'decimal:7',  // Standar presisi Google Maps/GPS
        'longitude' => 'decimal:7', // Standar presisi Google Maps/GPS
    ];

    /**
     * Get the user that owns the store.
     */
    public function user(): BelongsTo
    {
        // Menyesuaikan dengan primary key kustom 'id_pengguna'
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
