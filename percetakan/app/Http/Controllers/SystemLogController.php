<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $filePath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($filePath)) {
            // Ambil konten file
            $content = File::get($filePath);
            
            // Regex untuk menangkap format log standar Laravel: [Date] Env.Level: Message
            // Pattern: [2024-01-09 12:00:00] local.ERROR: Pesan error...
            $pattern = '/^\[(?<date>.*)\] (?<env>\w+)\.(?<level>\w+): (?<message>.*)/m';
            
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER, 0);

            // Balik urutan agar log terbaru ada di atas
            $logs = array_reverse($matches);
        }

        // --- FILTERING (Optional) ---
        // Jika ada pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $logs = array_filter($logs, function ($log) use ($search) {
                return str_contains(strtolower($log['message']), $search) || 
                       str_contains(strtolower($log['level']), $search);
            });
        }

        // Jika filter level dipilih
        if ($request->filled('level') && $request->level !== 'ALL') {
            $logs = array_filter($logs, function ($log) use ($request) {
                return $log['level'] === $request->level;
            });
        }

        // Pagination Manual (Array Pagination)
        $perPage = 20;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $col = collect($logs);
        $currentPageItems = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator($currentPageItems, count($col), $perPage);
        $paginatedLogs->setPath($request->url());

        return view('admin.logs.index', [
            'logs' => $paginatedLogs,
            'levels' => $this->getLevels()
        ]);
    }

    public function clear()
    {
        $filePath = storage_path('logs/laravel.log');
        
        // Kosongkan file log
        if (File::exists($filePath)) {
            File::put($filePath, '');
        }

        return redirect()->back()->with('success', 'File Log berhasil dibersihkan.');
    }

    private function getLevels()
    {
        return ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
    }
}