<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // <-- PERBAIKAN: Mengarah ke lokasi User model yang benar

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'featured_image',
        'status',
    ];

    /**
     * Mendefinisikan relasi many-to-one ke model Category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

   /**
    * Mendapatkan user (author) yang memiliki post ini.
    */
    public function author()
    {
        // Post ini 'milik' satu User.
        // Relasi ini bekerja dengan mencocokkan kolom 'user_id' di tabel posts
        // dengan kolom 'id' di tabel users.
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mendefinisikan relasi many-to-many ke model Tag.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
