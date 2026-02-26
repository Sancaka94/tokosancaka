<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    // Menentukan nama tabel (opsional, Laravel akan otomatis mencari tabel 'invoices')
    protected $table = 'invoices';

    // Mengizinkan semua kolom untuk diisi (Mass Assignment) kecuali ID
    protected $guarded = ['id'];

    /**
     * Relasi ke InvoiceItem (One to Many)
     * Satu invoice bisa memiliki banyak item/produk
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }
}
