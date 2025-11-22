<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WebhookController extends Controller
{
    /**
     * Menangani permintaan webhook dari GitHub untuk proses deployment otomatis.
     */
    public function github(Request $request)
    {
        // 1. Ambil Secret Key dari .env
        $secret = env('GITHUB_WEBHOOK_SECRET');
        if (empty($secret)) {
             Log::error('GitHub Webhook: GITHUB_WEBHOOK_SECRET is not set.');
             return response('Secret not configured', 500); 
        }

        // 2. Validasi Signature (Wajib untuk keamanan)
        $signature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($signature, $request->header('X-Hub-Signature-256'))) {
            Log::error('GitHub Webhook: Invalid signature received.');
            return response('Invalid signature', 400); // 400 Bad Request jika secret gagal
        }

        // 3. Validasi Branch (Pastikan hanya deploy branch 'master')
        $payload = json_decode($request->getContent(), true);
        $target_branch = 'refs/heads/master'; // Ganti dengan 'refs/heads/main' jika perlu

        if ($payload['ref'] !== $target_branch) {
            Log::info('GitHub Webhook: Pushed to wrong branch. Deployment skipped.');
            return response('Deployment skipped (Wrong branch)', 200);
        }

        // --- DEPLOYMENT SCRIPT ---
        
        $path = base_path();
        
        // Asumsi: 'php' dan 'artisan' ada di path. 
        // Jika composer.phar ada di root proyek, gunakan 'php composer.phar'
        // Jika tidak, ganti 'composer' dengan path absolute, misalnya: '/usr/local/bin/composer'
        $composer_cmd = 'composer'; 
        $php_artisan_cmd = 'php artisan';
        
        // Perintah untuk GIT dan Post-Deployment (dipisah dengan &&)
        $deployment_commands = [
            // 1. Sinkronisasi Kode: Fetch semua commit terbaru & reset hard ke master remote
            "git fetch origin",
            "git reset --hard origin/master",

            // 2. Pembaruan PHP: Dump Autoloading dan Bersihkan Cache
            "{$composer_cmd} dump-autoload",
            "{$php_artisan_cmd} cache:clear",
            "{$php_artisan_cmd} config:clear",
            "{$php_artisan_cmd} view:clear",

            // 3. Migrasi Database (Wajib jika ada perubahan skema)
            "{$php_artisan_cmd} migrate --force", 
        ];

        // Gabungkan semua perintah menjadi satu string dengan "&&"
        $full_cmd = "cd {$path} && " . implode(' && ', $deployment_commands) . " 2>&1";

        // Jalankan semua perintah menggunakan Process
        $result = Process::run($full_cmd);

        if ($result->failed()) {
            // Jika proses gagal, log output errornya dan kembalikan 500
            Log::error('GitHub Webhook Deployment FAILED: ' . $result->errorOutput() . ' - Output: ' . $result->output());
            return response('Deployment failed. Check laravel.log.', 500);
        }

        // Logging output git dan post-deployment
        Log::info('GitHub Webhook Deployment SUCCESS. Output: ' . $result->output());

        // --- RESPONSE FINAL ---
        return response('OK - Deployment Complete', 200);
    }
}