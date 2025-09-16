<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $table = 'reg_districts';
    public $timestamps = false;

    /**
     * Get the regency that owns the district.
     */
    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    /**
     * Get the villages for the district.
     */
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class, 'district_id');
    }
}
