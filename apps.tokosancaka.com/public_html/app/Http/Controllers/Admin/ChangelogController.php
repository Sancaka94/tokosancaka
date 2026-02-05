<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ChangelogController extends Controller
{
    public function index()
    {
        $commits = [];
        $version = '1.0.0';

        // CARA BACA: Ambil data dari file version.json yang di-upload
        $jsonPath = base_path('version.json');

        if (File::exists($jsonPath)) {
            // 1. Decode JSON ke Array
            $jsonData = json_decode(File::get($jsonPath), true);

            // 2. Ambil Versi
            $version = $jsonData['version'] ?? '1.0.0';

            // 3. Ambil Data Commits (Raw)
            $rawCommits = $jsonData['commits'] ?? [];

            // 4. Format Tanggal agar enak dibaca (pakai Carbon)
            foreach ($rawCommits as $log) {
                // Pastikan format tanggal aman
                try {
                    $dateObj = Carbon::parse($log['date']);
                    $dateString = $dateObj->translatedFormat('d F Y, H:i'); // 05 Februari 2026, 15:30
                    $agoString = $dateObj->diffForHumans(); // 2 jam yang lalu
                } catch (\Exception $e) {
                    $dateString = $log['date'];
                    $agoString = '-';
                }

                $commits[] = [
                    'hash'    => $log['hash'],
                    'date'    => $dateString,
                    'ago'     => $agoString,
                    'author'  => $log['author'],
                    'message' => $log['message'],
                ];
            }
        }

        return view('admin.changelog.index', compact('commits', 'version'));
    }
}
