<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     * Opsional jika nama tabel adalah 'product_attributes'.
     *
     * @var string
     */
    // protected $table = 'product_attributes';

    /**
     * Atribut yang dapat diisi secara massal.
     * Sesuaikan dengan kolom di tabel product_attributes Anda.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_slug', // Slug dari tabel 'attributes'
        'attribute_name', // Opsional, bisa diambil dari relasi
        'attribute_type', // Opsional, bisa diambil dari relasi
        'value',
        'name',       // <-- TAMBAHKAN BARIS INI
    ];

    /**
     * Atribut yang harus di-cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Jika Anda menyimpan value checkbox sebagai JSON
        // 'value' => 'array',
    ];

    /**
     * Mendapatkan produk yang memiliki atribut ini.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mendapatkan detail atribut (dari tabel 'attributes').
     */
    public function attribute(): BelongsTo
    {
        // Pastikan foreign key 'attribute_id' ada di tabel ini
        return $this->belongsTo(Attribute::class);
    }

    /**
     * ACCESSOR MAGIC: get_name_attribute
     * Ini membuat kita bisa memanggil $attr->name meskipun di database
     * kolomnya bernama 'attribute_name' atau 'attribute_slug'.
     */
    public function getNameAttribute($value)
    {
        // 1. Jika kolom 'name' asli ada isinya, kembalikan itu.
        if (!empty($value)) {
            return $value;
        }

        // 2. Jika kosong, coba ambil dari kolom 'attribute_name'
        if (!empty($this->attributes['attribute_name'])) {
            return $this->attributes['attribute_name'];
        }

        // 3. Jika masih kosong, coba ambil dari relasi attribute (jika pakai master data)
        // (Pastikan relasi 'attribute' dimuat jika ingin menggunakan ini untuk menghindari N+1 problem)
        if ($this->relationLoaded('attribute') && $this->attribute) {
            return $this->attribute->name;
        }

        // 4. Terakhir, jika terpaksa, kembalikan attribute_slug
        return $this->attributes['attribute_slug'] ?? null;
    }
}
