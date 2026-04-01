<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IakResponseCode extends Model
{
    protected $table = 'iak_response_codes';

    protected $fillable = [
        'code',
        'description',
        'status',
        'solution'
    ];

    // LOG LOG - Sesuai instruksi Anda untuk tidak menghapus log ini
}
