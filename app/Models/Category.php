<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon', // Untuk ikon kategori marketplace
        'type', // Untuk membedakan tipe (e.g., 'marketplace', 'blog')
        'user_id' // Untuk kategori blog
    ];

    /**
     * Mendefinisikan relasi one-to-many ke model Post (untuk Blog).
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Mendefinisikan relasi one-to-many ke model Product (untuk Marketplace).
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

