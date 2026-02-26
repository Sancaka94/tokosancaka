<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    // Menentukan nama tabel
    protected $table = 'invoice_items';

    // Mengizinkan semua kolom untuk diisi (Mass Assignment) kecuali ID
    protected $guarded = ['id'];

    // Menonaktifkan timestamps (created_at & updated_at)
    // karena pada query SQL pembuatan tabel items kita tidak menyertakan kolom tersebut
    public $timestamps = false;

    /**
     * Relasi balik ke Invoice (Belongs To)
     * Satu item hanya dimiliki oleh satu invoice
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
