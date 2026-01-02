<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingTheme extends Model
{
    use HasFactory;

    // Nama tabel (opsional jika sesuai standar, tapi baik untuk ketegasan)
    protected $table = 'setting_themes';

    // Kolom yang boleh diisi
    protected $fillable = ['key', 'value'];
    
    // Matikan timestamp jika Anda tidak butuh created_at/updated_at (Opsional)
    // public $timestamps = false;
}