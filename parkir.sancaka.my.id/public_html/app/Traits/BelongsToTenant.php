<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('tenant_id') && app('tenant_id') !== null) {
                $builder->where('tenant_id', app('tenant_id'));
            }
        });

        static::creating(function ($model) {
            if (app()->bound('tenant_id') && app('tenant_id') !== null) {
                $model->tenant_id = app('tenant_id');
            }
        });
    }
}