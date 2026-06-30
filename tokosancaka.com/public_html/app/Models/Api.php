<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Api extends Model
{
    // Nama tabel disesuaikan dengan SQL Anda
    protected $table = 'API';

    // Kolom yang boleh diisi
    protected $fillable = [
        'key',
        'value',
        'group',
        'environment',
        'name',
        'mode',
    ];

    /**
     * Helper untuk mengambil value konfigurasi.
     * Prioritas: Ambil dari Cache -> Database -> Default.
     * @param string $key Nama Key (contoh: TRIPAY_API_KEY)
     * @param string $env Lingkungan (global, sandbox, production)
     */
    public static function getValue($key, $env = 'global', $default = null)
    {
        $cacheKey = "api_{$key}_{$env}";

        return Cache::remember($cacheKey, 60 * 60, function () use ($key, $env, $default) {
            $setting = self::where('key', $key)
                           ->where('environment', $env)
                           ->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Helper untuk menyimpan konfigurasi dan menghapus cache.
     */
    public static function setValue($key, $value, $group, $env = 'global')
    {
        self::updateOrCreate(
            ['key' => $key, 'environment' => $env],
            [
                'value' => $value,
                'group' => $group,
                'name'  => $key,  // <-- SOLUSI ERROR 1364: Otomatis isi kolom name sama dengan key
                'mode'  => $env   // <-- Otomatis isi kolom mode sama dengan environment
            ]
        );

        // Hapus cache agar data terupdate di frontend dan tidak nyangkut lagi
        Cache::forget("api_{$key}_{$env}");
    }
}
