<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariantType extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     * Opsional jika nama tabel adalah 'product_variant_types'.
     *
     * @var string
     */
    // protected $table = 'product_variant_types';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name', // Nama tipe varian (e.g., "Warna", "Ukuran")
    ];

    /**
     * Mendapatkan produk yang memiliki tipe varian ini.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mendapatkan semua opsi yang dimiliki oleh tipe varian ini.
     * (e.g., Untuk "Warna", opsinya bisa "Merah", "Biru")
     * Pastikan foreign key 'product_variant_type_id' ada di tabel 'product_variant_options'
     * dan diatur ON DELETE CASCADE di migration.
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class);
    }
}
