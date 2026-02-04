<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // <-- Pastikan ini di-import


class OrderAttachment extends Model
{
    use HasFactory;
    use BelongsToTenant; // <-- Pastikan ini dipasang di dalam class

    protected $fillable = [
        'tenant_id',    // <--- TAMBAHKAN INI DI BARIS PALING ATAS
        'order_id',
        'file_path',
        'file_name',
        'file_type',
        // Tambahkan 3 kolom ini:
        'color_mode',
        'paper_size',
        'quantity'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
