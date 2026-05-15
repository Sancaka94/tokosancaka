<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingApi extends Model 
{
    // Beri tahu Laravel secara paksa bahwa nama tabelnya adalah 'settingapi'
    protected $table = 'settingapi';

    protected $fillable = ['key', 'value'];
}