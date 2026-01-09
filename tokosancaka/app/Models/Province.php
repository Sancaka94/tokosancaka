<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    // KUNCI: Memberitahu Laravel nama tabel yang benar.
    protected $table = 'reg_provinces';
    public $timestamps = false;
    protected $fillable = ['id', 'name'];

    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class, 'province_id');
    }
}
