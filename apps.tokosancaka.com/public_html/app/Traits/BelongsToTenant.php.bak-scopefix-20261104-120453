<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // 1. FILTER OTOMATIS SAAT 'READ' DATA
        // Setiap kali query dijalankan, tambahkan "WHERE tenant_id = X"
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where('tenant_id', Auth::user()->tenant_id);
            }
        });

        // 2. INPUT OTOMATIS SAAT 'CREATE' DATA
        // Setiap kali simpan data baru, otomatis isi kolom tenant_id
        static::creating(function (Model $model) {
            if (Auth::check()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
