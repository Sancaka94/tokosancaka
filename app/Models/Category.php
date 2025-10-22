<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Impor HasMany

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'type',
        'user_id'
    ];

    /**
     * Relasi ke model Post (untuk Blog).
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * PERBAIKAN: Relasi ini sekarang menunjuk ke model Marketplace.
     * Sebelumnya kemungkinan menunjuk ke model Product yang salah.
     */
    public function products(): HasMany
    {
        // Menghubungkan Category dengan Marketplace melalui foreign key 'category_id'
        return $this->hasMany(Marketplace::class, 'category_id');
    }

     public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }
}

