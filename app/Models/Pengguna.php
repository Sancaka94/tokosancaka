<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    protected $table = 'Pengguna';
    protected $primaryKey = 'id_pengguna';
}
