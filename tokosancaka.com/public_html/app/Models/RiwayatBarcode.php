<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatBarcode extends Model
{
    // Beri tahu Laravel nama tabel spesifiknya karena kita tidak pakai standar nama bahasa inggris (plural)
    protected $table = 'riwayat_barcodes';

    // Izinkan kolom url untuk diisi
    protected $fillable = ['url'];
}
