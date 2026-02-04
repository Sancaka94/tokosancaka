<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class OrderDetail extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    // Aktifkan timestamps karena kita sudah menambahkan kolom created_at & updated_at di SQL sebelumnya
    public $timestamps = true;

    // PENTING: Daftarkan semua nama kolom ini agar bisa disimpan
    protected $fillable = [
        'tenant_id',    // <--- TAMBAHKAN INI DI BARIS PALING ATAS
        'order_id',
        'product_id',
        'product_name',   // Kolom yang baru ditambahkan
        'price_at_order', // WAJIB ADA (Penyebab Error Anda)
        'quantity',       // WAJIB ADA
        'subtotal',
        'base_price_at_order', // <--- INI BIANG KEROKNYA (Wajib Ada)
        'file_design',    // Sesuai struktur database lama Anda
        'width',          // Sesuai struktur database lama Anda
        'height'          // Sesuai struktur database lama Anda
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
