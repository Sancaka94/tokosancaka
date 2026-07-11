<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoKirim extends Model
{
    use HasFactory;

    protected $table = 'auto_kirims';

    protected $fillable = [
        'zip',
        'district_id',
        'district_name',
        'regency_name',
        'province_name',
    ];
}