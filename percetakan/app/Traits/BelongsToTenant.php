<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant {
    protected static function bootBelongsToTenant() {
        static::creating(function ($model) {
            if (request()->has('current_tenant')) {
                $model->tenant_id = request()->current_tenant->id;
            }
        });

        // Otomatis filter semua query berdasarkan tenant_id
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (request()->has('current_tenant')) {
                $builder->where('tenant_id', request()->current_tenant->id);
            }
        });
    }
}
