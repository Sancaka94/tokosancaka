<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyek extends Model
{
    // LOG LOG
    protected $table = 'proyek';
    protected $guarded = ['id'];

    public function rabItems()
    {
        return $this->hasMany(RabItem::class, 'proyek_id');
    }
}