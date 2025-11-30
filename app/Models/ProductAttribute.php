<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    use HasFactory;

    /**
     * Tentukan nama tabel jika tidak standar (opsional).
     * Default: product_attributes
     */
    // protected $table = 'product_attributes';

    /**
     * KOLOM YANG BOLEH DIISI (Sangat Penting!)
     * Harus cocok dengan updateOrCreate di ProductController.
     */
    protected $fillable = [
        'product_id',      // ID Produk
        'attribute_slug',  // Kunci Unik (misal: warna, bahan, jenis-kain)
        'name',            // Label Cantik (misal: Warna, Bahan Utama)
        'value',           // Isi data (Bisa text biasa atau JSON String)
        
        // Kolom Opsional (Hanya jika database Anda memilikinya)
        'attribute_id',    
        'attribute_name',  
        'attribute_type', 
    ];

    /**
     * Relasi balik ke Produk.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke Master Attribute (Opsional).
     * Digunakan jika Anda ingin mengambil data tipe input dsb.
     */
    public function attribute(): BelongsTo
    {
        // Pastikan tabel product_attributes punya kolom 'attribute_id' jika ingin pakai ini
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * Accessor Cerdas untuk Nama Atribut.
     * Memastikan $attr->name selalu mengembalikan sesuatu, 
     * meskipun kolom 'name' di database kosong.
     */
    public function getNameAttribute($value)
    {
        // 1. Jika kolom 'name' sudah terisi (dari Controller), gunakan itu.
        if (!empty($value)) {
            return $value;
        }

        // 2. Fallback: Coba ambil dari attribute_name (jika kolom lama masih ada)
        if (!empty($this->attributes['attribute_name'])) {
            return $this->attributes['attribute_name'];
        }

        // 3. Fallback: Coba ambil dari relasi Master Attribute
        if ($this->relationLoaded('attribute') && $this->attribute) {
            return $this->attribute->name;
        }

        // 4. Fallback Terakhir: Gunakan slug dan rapikan (contoh: jenis-kain -> Jenis Kain)
        $slug = $this->attributes['attribute_slug'] ?? '';
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
    
    /**
     * Accessor untuk Value (Opsional).
     * Jika Anda ingin otomatis decode JSON saat memanggil $attr->value.
     * Namun, karena di Controller kita handle manual, ini opsional.
     */
    // Helper agar saat dipanggil, namanya otomatis rapi (opsional tapi bagus)
    public function getNameAttribute($value)
    {
        if (!empty($value)) return $value;
        $slug = $this->attributes['attribute_slug'] ?? '';
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}