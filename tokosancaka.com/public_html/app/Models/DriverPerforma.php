<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DriverPerforma extends Model
{
    protected $table = 'driver_performa';
    protected $guarded = [];

    // Relasi untuk mengambil medali secara otomatis
    public function medali()
    {
        return $this->belongsTo(DriverMedali::class, 'id_medali', 'id');
    }
}
