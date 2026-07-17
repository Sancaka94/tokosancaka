<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAutoKirim extends Model
{
    use HasFactory;

    protected $table = 'data_auto_kirims';

    // Kolom komisi_agen ditambahkan di sini
    protected $fillable = [
        'brand_logistik', 
        'service', 
        'satuan', 
        'cashback', 
        'admin_cod', 
        'komisi_agen' 
    ];
}