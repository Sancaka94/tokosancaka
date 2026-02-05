<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ChangelogController extends Controller
{
    public function index()
    {
        $commits = [];
        $version = '1.0.0'; // Default jika gagal baca git

        // Cek apakah folder .git ada di root project
        if (File::exists(base_path('.git'))) {
            try {
                // 1. Ambil 20 Log Terakhir
                // Format: Hash Singkat | Tanggal ISO | Author | Pesan Commit
                $cmdLogs = 'git log --pretty=format:"%h|%ci|%an|%s" -n 20';
                exec($cmdLogs, $outputLogs);

                // 2. Ambil Total Count untuk Versioning (Build Number)
                $buildNumber = trim(exec('git rev-list --count HEAD'));
                $version = "1.0.0.{$buildNumber}";

                foreach ($outputLogs as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 4) {
                        $commits[] = [
                            'hash'    => $parts[0],
                            'date'    => \Carbon\Carbon::parse($parts[1])->translatedFormat('d F Y, H:i'), // Format Indonesia
                            'ago'     => \Carbon\Carbon::parse($parts[1])->diffForHumans(),
                            'author'  => $parts[2],
                            'message' => $parts[3],
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Silent error jika git command gagal
            }
        }

        // Kirim data ke View
        return view('admin.changelog.index', compact('commits', 'version'));
    }
}
