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
        // 'attribute_name', // Opsional, bisa diambil dari relasi
        // 'attribute_type', // Opsional, bisa diambil dari relasi
        'value',
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
}
