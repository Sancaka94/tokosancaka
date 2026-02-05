<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateVersion extends Command
{
    // Nama command yang akan kita panggil nanti
    protected $signature = 'app:generate-version';

    protected $description = 'Generate version.json from Git history';

    public function handle()
    {
        $this->info('Sedang membaca history Git...');

        $commits = [];
        $version = '1.0.0';
        $lastUpdate = now()->format('d M Y H:i');

        try {
            // [REVISI] Hapus pengecekan File::exists(.git)
            // Biarkan perintah git log yang mendeteksi otomatis lokasi repository-nya

            // 1. Cek dulu apakah ini folder git valid dengan perintah ringan
            exec('git rev-parse --is-inside-work-tree 2>&1', $checkGit, $returnVar);

            if ($returnVar !== 0) {
                $this->error('Gagal: Folder ini tidak terdeteksi sebagai repository Git.');
                return;
            }

            // 2. Ambil Log Git
            exec('git log --pretty=format:"%h|%ci|%an|%s" -n 20', $outputLogs);

            // 3. Hitung Versi
            $buildNumber = trim(exec('git rev-list --count HEAD'));
            if ($buildNumber) {
                $version = "1.0.0.{$buildNumber}";
            }

            // 4. Parsing Data
            foreach ($outputLogs as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 4) {
                    $commits[] = [
                        'hash'    => $parts[0],
                        'date'    => $parts[1],
                        'author'  => $parts[2],
                        'message' => $parts[3],
                    ];
                }
            }

            // 5. Susun Data JSON
            $data = [
                'version' => $version,
                'last_update' => $lastUpdate,
                'commits' => $commits
            ];

            // 6. Simpan ke file version.json
            File::put(base_path('version.json'), json_encode($data, JSON_PRETTY_PRINT));

            $this->info("✅ SUKSES! File version.json berhasil dibuat.");
            $this->info("Versi Baru: $version");

        } catch (\Exception $e) {
            $this->error("Gagal: " . $e->getMessage());
        }
    }
}
