<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // 1. Filter Otomatis saat mengambil data (SELECT)
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('tenant_id', Auth::user()->tenant_id);
            }
        });

        // 2. Isi Otomatis saat simpan data (INSERT)
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }
}
