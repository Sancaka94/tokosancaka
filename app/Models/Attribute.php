<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'type',
        'options',
        'is_required',
    ];

    /**
     * Mengubah 'options' dari JSON ke array secara otomatis.
     */
    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    /**
     * Relasi ke model Category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
