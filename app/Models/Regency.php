<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Regency extends Model
{
    use HasFactory;

    protected $table = 'reg_regencies';
    public $timestamps = false;

    /**
     * Get the province that owns the regency.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    /**
     * Get the districts for the regency.
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'regency_id');
    }
}
