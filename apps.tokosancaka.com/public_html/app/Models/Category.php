<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import

class Category extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $table = 'categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        // Tambahan kolom baru agar bisa disimpan (Mass Assignment)
        'type',             // physical / service
        'default_unit',     // pcs, kg, dll
        'product_presets',  // JSON array
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // WAJIB: Mengubah JSON di database menjadi Array PHP
        // Agar fungsi array_slice() di view tidak error
        'product_presets' => 'array',
    ];

    /**
     * Relasi: Satu Kategori punya banyak Produk
     * Fungsi ini sekarang hanya satu (tidak duplikat)
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Boot function untuk auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Opsional: Update slug jika nama berubah saat edit
        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
