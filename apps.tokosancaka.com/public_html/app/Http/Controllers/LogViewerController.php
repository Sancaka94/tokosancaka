<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    /**
     * Menampilkan halaman viewer log raw.
     */
    public function index()
    {
        $path = storage_path('logs/laravel.log');
        $maxLines = 500; // Konfigurasi: Mau tampilkan berapa baris terakhir?
        $logs = '';

        if (File::exists($path)) {
            // Membaca file log
            $fileContent = File::get($path);
            
            // Memecah menjadi array per baris
            // Catatan: Jika file log > 100MB, teknik explode ini bisa berat. 
            // Tapi untuk penggunaan wajar (simple), ini aman.
            $lines = explode("\n", $fileContent);
            
            // Mengambil baris-baris terakhir saja (Tail)
            $logs = implode("\n", array_slice($lines, -$maxLines));
            
            if (empty(trim($logs))) {
                $logs = '[INFO] File log saat ini kosong.';
            }
        } else {
            $logs = '[ERROR] File laravel.log tidak ditemukan.';
        }

        // Sesuaikan 'admin.logs.viewer' dengan nama folder view Anda
        return view('admin.logs.viewer', compact('logs', 'maxLines'));
    }

    /**
     * Menghapus isi file log (AJAX).
     */
    public function clear()
    {
        $path = storage_path('logs/laravel.log');

        if (File::exists($path)) {
            // Timpa file dengan string kosong
            File::put($path, '');
            
            return response()->json([
                'status' => 'success',
                'message' => 'File log berhasil dikosongkan.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'File log tidak ditemukan.'
        ], 404);
    }
}