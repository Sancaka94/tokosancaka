<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

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
        'zip_code'
    ];

    /**
     * Get the user that owns the store.
     */
    public function user(): BelongsTo
    {
        // ✅ DIPERBAIKI: Menyesuaikan dengan primary key kustom 'id_pengguna'
        return $this->belongsTo(User::class, 'user_id', 'id_pengguna');
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}