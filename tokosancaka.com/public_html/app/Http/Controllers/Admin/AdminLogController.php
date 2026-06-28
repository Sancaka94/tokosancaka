<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Diperlukan untuk method clearLogs & destroySelected
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminLogController extends Controller
{
    // Batas baris log yang akan ditampilkan
    const MAX_LINES = 10000; 
    
    public function showLogs()
    {
        // Path ke file log default Laravel
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            // Perbaikan: Pastikan $maxLines dikirim meskipun file tidak ada
            $maxLines = self::MAX_LINES;
            return view('admin.logs.viewer', compact('maxLines'), ['logs' => 'File log (laravel.log) tidak ditemukan.']);
        }

        try {
            $content = File::get($logPath);
            
            // Ambil hanya baris terakhir (agar tidak terlalu membebani browser)
            $lines = explode("\n", $content);
            $lastLines = array_slice($lines, -self::MAX_LINES, self::MAX_LINES, true);
            
            // Ambil nilai konstanta ke dalam variabel lokal
            $maxLines = self::MAX_LINES; 
            
            // Gabungkan kembali baris-baris tersebut tanpa modifikasi
            $logs = implode("\n", $lastLines);
            
            // Perbaikan: Kirim variabel $maxLines ke view
            return view('admin.logs.viewer', compact('logs', 'maxLines')); 

        } catch (\Exception $e) {
            Log::error('Gagal membaca file log: ' . $e->getMessage());
            $maxLines = self::MAX_LINES;
            return view('admin.logs.viewer', compact('maxLines'), ['logs' => 'Error: Gagal mengakses file log.']);
        }
    }

    // ==========================================================
    // FUNGSI BARU: HAPUS SEMUA ISI LOG
    // ==========================================================
    public function clearLogs(Request $request)
    {
        // Hanya izinkan admin yang terotentikasi dan memiliki role yang sesuai
        if (!auth()->check() || auth()->user()->role !== 'Admin') {
             return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }
        
        $logPath = storage_path('logs/laravel.log');

        try {
            if (File::exists($logPath)) {
                // Hapus isi file (menulis string kosong ke file)
                File::put($logPath, ''); 
                Log::info('Log file cleared by Admin ID: ' . auth()->id()); // Log aksi penghapusan
                
                return response()->json(['status' => 'success', 'message' => 'Semua isi log berhasil dihapus.']);
            }
            return response()->json(['status' => 'warning', 'message' => 'File log tidak ditemukan.'], 404);
            
        } catch (\Exception $e) {
            Log::error('Gagal menghapus file log: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus log: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================================
    // FUNGSI BARU: HAPUS LOG SPESIFIK (SINGLE & BULK) PERMANEN
    // ==========================================================
    public function destroySelected(Request $request)
    {
        // Pengecekan otorisasi yang sama seperti clearLogs
        if (!auth()->check() || auth()->user()->role !== 'Admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $textsToDelete = $request->input('texts_to_delete', []); 
        $logPath = storage_path('logs/laravel.log');

        if (empty($textsToDelete)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada log yang dipilih untuk dihapus.']);
        }

        if (!File::exists($logPath)) {
            return response()->json(['status' => 'error', 'message' => 'File log tidak ditemukan di server.']);
        }

        try {
            // Baca seluruh isi file log
            $logContent = File::get($logPath);

            // Gunting (replace) teks yang persis dikirim dari frontend menjadi kosong
            foreach ($textsToDelete as $text) {
                $logContent = str_replace($text, '', $logContent);
            }

            // Bersihkan baris kosong berlebih (blank lines) akibat proses replace di atas
            $logContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $logContent);

            // Timpa kembali ke file log fisik
            File::put($logPath, $logContent);

            return response()->json(['status' => 'success', 'message' => count($textsToDelete) . ' Log berhasil dimusnahkan secara permanen.']);
            
        } catch (\Exception $e) {
            Log::error('Gagal menghapus sebagian file log: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus log: ' . $e->getMessage()], 500);
        }
    }

}