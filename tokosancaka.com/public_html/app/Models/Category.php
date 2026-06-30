<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'type',
        'category_group',
        'flag',            // 3 bendera: non_fisik, fisik, lokal
        'user_id'
    ];

    /**
     * Relasi ke model User (Pembuat Kategori).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Post (untuk Blog).
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relasi ke produk Marketplace.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Marketplace::class, 'category_id');
    }

    /**
     * Relasi ke atribut spesifikasi produk.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    /**
     * Relasi khusus untuk produk Marketplace
     * agar tidak bentrok dengan relasi 'products()' yang lama.
     */
    public function marketplaceProducts(): HasMany
    {
        return $this->hasMany(Marketplace::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS & SCOPES (Untuk Cek 3 Bendera)
    |--------------------------------------------------------------------------
    | Gunakan fungsi ini agar logic checkout lebih rapi.
    | Contoh pemakaian di controller:
    | if ($category->isFisik()) { // Panggil API KirimAja }
    */

    public function isNonFisik(): bool
    {
        return $this->flag === 'non_fisik';
    }

    public function isFisik(): bool
    {
        return $this->flag === 'fisik';
    }

    public function isLokal(): bool
    {
        return $this->flag === 'lokal';
    }

    // Query Scopes untuk filter cepat di database
    // Contoh pemanggilan: Category::flagFisik()->get();

    public function scopeFlagNonFisik(Builder $query): Builder
    {
        return $query->where('flag', 'non_fisik');
    }

    public function scopeFlagFisik(Builder $query): Builder
    {
        return $query->where('flag', 'fisik');
    }

    public function scopeFlagLokal(Builder $query): Builder
    {
        return $query->where('flag', 'lokal');
    }
}
