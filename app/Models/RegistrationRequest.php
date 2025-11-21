<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    use HasFactory;

    // Tentukan kolom mana saja yang boleh diisi secara massal.
    protected $fillable = [
        'name',
        'email',
        'no_wa',
        'store_nama',
    ];
}