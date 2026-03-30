<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardSetting extends Model
{
    use HasFactory;

    // Menentukan nama tabel (opsional, tapi bagus untuk memastikan)
    protected $table = 'dashboard_settings';

    // Mengizinkan semua kolom diisi secara massal saat Admin menyimpan pengaturan
    protected $guarded = ['id'];

    // Memastikan Laravel membaca data 1/0 dari database sebagai true/false
    protected $casts = [
        'parkir_dibagi_dua'      => 'boolean',
        'nginap_dibagi_dua'      => 'boolean',
        'toilet_masuk_profit'    => 'boolean',
        'gaji_hanya_dari_parkir' => 'boolean',

        'tampil_card_harian'     => 'boolean',
        'tampil_card_mingguan'   => 'boolean',
        'tampil_card_bulanan'    => 'boolean',
        'tampil_grafik_harian'   => 'boolean',
        'tampil_grafik_bulanan'  => 'boolean',
    ];
}
