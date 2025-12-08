<?php

namespace App\Http\Controllers\Admin; // <<< PASTIKAN INI SAMA DENGAN LOKASI FILE

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminLogController extends Controller
{
    // Batas baris log yang akan ditampilkan
    const MAX_LINES = 1000; 
    
    public function showLogs()
    {
        // Path ke file log default Laravel
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return view('admin.logs.viewer', ['logs' => 'File log (laravel.log) tidak ditemukan.']);
        }

        try {
            $content = File::get($logPath);
            
            // Ambil hanya baris terakhir (agar tidak terlalu membebani browser)
            $lines = explode("\n", $content);
            $lastLines = array_slice($lines, -self::MAX_LINES, self::MAX_LINES, true);
            $maxLines = self::MAX_LINES;
            
            // Gabungkan kembali baris-baris tersebut tanpa modifikasi
            $logs = implode("\n", $lastLines);
            
            return view('admin.logs.viewer', compact('logs'));

        } catch (\Exception $e) {
            Log::error('Gagal membaca file log: ' . $e->getMessage());
            return view('admin.logs.viewer', ['logs' => 'Error: Gagal mengakses file log.']);
        }
    }
}