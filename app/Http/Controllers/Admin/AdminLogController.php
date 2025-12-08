<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminLogController extends Controller
{
    // Batas baris yang akan dibaca (misalnya 500 baris terakhir)
    const MAX_LINES = 500; 
    
    public function showLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        
        // Cek apakah file log ada
        if (!File::exists($logPath)) {
            return view('admin.logs.viewer', ['logs' => 'File log tidak ditemukan.']);
        }

        try {
            // Baca isi file log
            $content = File::get($logPath);
            
            // Pisahkan konten menjadi baris-baris
            $lines = explode("\n", $content);
            
            // Ambil hanya baris terakhir (misalnya 500 baris terakhir)
            $lastLines = array_slice($lines, -self::MAX_LINES, self::MAX_LINES, true);
            
            // Gabungkan kembali baris-baris tersebut
            $logs = implode("\n", $lastLines);
            
            // Format output agar lebih mudah dibaca (opsional)
            $logs = str_replace('] local.', "] \n<span class='text-red-500'>|</span> local.", $logs);
            
            return view('admin.logs.viewer', compact('logs'));

        } catch (\Exception $e) {
            Log::error('Gagal membaca file log: ' . $e->getMessage());
            return view('admin.logs.viewer', ['logs' => 'Error: Gagal mengakses file log.']);
        }
    }
}