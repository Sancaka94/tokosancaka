<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftKas extends Model
{
    protected $table = 'shift_kas';
    protected $fillable = ['store_id', 'user_id', 'status', 'modal_awal', 'uang_fisik'];

    public function flows()
    {
        return $this->hasMany(ShiftFlow::class, 'shift_id');
    }
}