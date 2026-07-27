<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache; // Wajib import Cache
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;   // Wajib import Log
use Illuminate\Support\Facades\Http;  // Wajib import Http
use App\Http\View\Composers\HeaderComposer;
use App\Models\Api;
use App\Models\User;     // Model User
use App\Models\Pesanan;  // Model Pesanan
use App\Models\TopUp;    // Model TopUp

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // --- TAMBAHKAN KODE INI ---
        // Cek jika file helper ada, lalu muat (require)
        if (file_exists(app_path('Helpers/ImageHelper.php'))) {
            require_once app_path('Helpers/ImageHelper.php');
        }
        // --------------------------
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // =========================================================
        // FITUR BARU: LOG LISTENER (KIRIM LOG REAL-TIME KE TELEGRAM)
        // =========================================================
        Log::listen(function ($log) {
            $pesanLog = $log->message;

            // 1. FILTER SPAM: Abaikan log looping DANA agar bot tidak diblokir
            if (str_contains($pesanLog, '[DANA BALANCE CHECK]') || str_contains($pesanLog, 'DANA Menggunakan Mode')) {
                return;
            }

            // 2. CHAT ID TELEGRAM TOKOSANCAKA.COM
            $adminChatId = '1885140247';

            // Ambil token dari file .env (services.telegram.token)
            $botToken = config('services.telegram.token');

            if (empty($botToken)) {
                return;
            }

            // 3. SUSUN PESAN TELEGRAM
            $levelText = strtoupper($log->level); // INFO, ERROR, WARNING, dll

            // Pilih emoji berdasarkan tingkat urgensi log
            $emoji = '🔔';
            if ($levelText === 'ERROR' || $levelText === 'CRITICAL') {
                $emoji = '🚨';
            } elseif ($levelText === 'WARNING') {
                $emoji = '⚠️';
            }

            $pesan = "$emoji <b>LOG MASUK [$levelText]</b>\n";
            $pesan .= "Waktu: <b>" . now()->format('Y-m-d H:i:s') . "</b>\n\n";

            // Batasi 3000 karakter agar Telegram tidak menolak pesannya
            $pesan .= substr($pesanLog, 0, 3000);

            // 4. KIRIM KE TELEGRAM (Timeout 2 detik agar web tidak lemot)
            try {
                Http::timeout(2)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $adminChatId,
                    'text' => $pesan,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ]);
            } catch (\Exception $e) {
                // Biarkan kosong jika gagal (misal tidak ada internet),
                // agar tidak membuat fungsi utama aplikasi menjadi error.
            }
        });
        // =========================================================

        View::composer('layouts.marketplace', function ($view) {
            $view->with('weblogo', 'logo.png'); // Replace 'logo.png' with your logic
        });
        // ----------------------------------------
        // 1. VIEW COMPOSER
        // ----------------------------------------
        View::composer('layouts.partials.header', HeaderComposer::class);

        View::composer('*', function ($view) {
            try {
                if (auth()->check()) {
                    // Cache data global (Monitor & Aktivitas)
                    $stats = Cache::remember('global_sidebar_data_v5', 30, function () { // Ubah jadi 30 agar sesuai komentar (30 detik)
                        return [
                            // --- DATA MONITOR (8 KARTU) ---
                            'totalPendapatan' => \App\Models\TopUp::where('status', 'success')->sum('amount')
                                               + Pesanan::sum('shipping_cost'),
                            'totalPesanan' => Pesanan::count(),
                            'jumlahToko' => \App\Models\User::where('role', 'Seller')->count(),
                            'penggunaBaru' => \App\Models\User::where('role', 'Pelanggan')
                                                 ->where('created_at', '>=', now()->subDays(30))->count(),
                            'totalTerkirim' => Pesanan::where('status_pesanan', 'Selesai')->count(),
                            'totalSedangDikirim' => Pesanan::whereIn('status_pesanan',
                                ['Sedang Dikirim', 'Dikirim', 'Diproses', 'Sedang Diantar'])->count(),
                            'totalMenungguPickup' => Pesanan::where('status_pesanan', 'Menunggu Pickup')->count(),
                            'totalGagal' => Pesanan::whereIn('status_pesanan',
                                ['Batal', 'Gagal', 'Retur', 'Kadaluarsa', 'Dibatalkan', 'Gagal Resi'])->count(),

                            // --- DATA AKTIVITAS (Baru Ditambahkan) ---
                            'pesananTerbaru' => Pesanan::with('pembeli')
                                                ->latest('created_at')
                                                //->take(10)
                                                ->limit(100) // Ambil 10 terakhir (Note: Limit 100 tapi komentar bilang 10 terakhir)
                                                ->get()
                        ];
                    });

                    foreach ($stats as $key => $val) {
                        $view->with($key, $val);
                    }
                } else {
                    // TAMBAHAN: Kirim nilai default jika user belum login / session habis
                    // agar tidak terjadi error "Undefined variable" di Blade
                    $view->with('totalPendapatan', 0)
                         ->with('totalPesanan', 0)
                         ->with('jumlahToko', 0)
                         ->with('penggunaBaru', 0)
                         ->with('totalTerkirim', 0)
                         ->with('totalSedangDikirim', 0)
                         ->with('totalMenungguPickup', 0)
                         ->with('totalGagal', 0)
                         ->with('pesananTerbaru', collect([])); // Berikan collection kosong
                }
            } catch (\Throwable $e) {
                // Silent fail
            }
        });

        // ----------------------------------------
        // 2. INJECT CONFIG API DARI DATABASE
        // ----------------------------------------

        // PERBAIKAN: "try" dimulai DULUAN sebelum cek database
        try {
            // Cek apakah tabel API ada (dilakukan di dalam blok try agar aman)
            if (Schema::hasTable('API')) {

                // --- A. INJECT KIRIMINAJA ---
                $kaMode = Api::getValue('KIRIMINAJA_MODE', 'global', 'staging');
                $kaToken = Api::getValue('KIRIMINAJA_TOKEN', $kaMode);
                $kaBaseUrl = Api::getValue('KIRIMINAJA_BASE_URL', $kaMode);

                Config::set('services.kiriminaja.token', $kaToken);
                Config::set('services.kiriminaja.base_url', $kaBaseUrl);

                // --- B. INJECT FONNTE ---
                $fonnteKey = Api::getValue('FONNTE_API_KEY', 'global');
                Config::set('services.fonnte.key', $fonnteKey);

                // --- C. INJECT TRIPAY ---
                $tpMode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
                // === PERBAIKAN DI SINI ===
                // Kita harus ubah URL sasaran berdasarkan mode
                if ($tpMode === 'production') {
                    Config::set('tripay.api_sandbox', false); // Matikan mode sandbox (Pakai URL Asli)
                } else {
                    Config::set('tripay.api_sandbox', true);  // Nyalakan mode sandbox (Pakai URL Simulator)
                }
                // =========================
                Config::set('tripay.api_key', Api::getValue('TRIPAY_API_KEY', $tpMode));
                Config::set('tripay.private_key', Api::getValue('TRIPAY_PRIVATE_KEY', $tpMode));
                Config::set('tripay.merchant_code', Api::getValue('TRIPAY_MERCHANT_CODE', $tpMode));

                // --- D. INJECT DOKU (LENGKAP) ---
                $dokuEnv = Api::getValue('DOKU_ENV', 'global', 'sandbox');

                Config::set('doku.mode', $dokuEnv);
                Config::set('doku.client_id', Api::getValue('DOKU_CLIENT_ID', $dokuEnv));
                Config::set('doku.secret_key', Api::getValue('DOKU_SECRET_KEY', $dokuEnv));
                Config::set('doku.sac_client_id', Api::getValue('DOKU_CLIENT_ID', $dokuEnv));
                Config::set('doku.sac_secret_key', Api::getValue('DOKU_SECRET_KEY', $dokuEnv));
                Config::set('doku.main_sac_id', Api::getValue('DOKU_MAIN_SAC_ID', 'global'));

                // Inject Keys
                Config::set('doku.doku_public_key', Api::getValue('DOKU_PUBLIC_KEY', $dokuEnv));
                Config::set('doku.merchant_private_key', Api::getValue('MERCHANT_PRIVATE_KEY', $dokuEnv));

                // Set URL sesuai mode
                if ($dokuEnv === 'production') {
                    Config::set('doku.url', 'https://api.doku.com');
                } else {
                    Config::set('doku.url', 'https://api-sandbox.doku.com');
                }
            }
        } catch (\Throwable $e) {
            // Jika koneksi DB gagal, diam saja (jangan crash).
            // Aplikasi akan lanjut jalan pakai settingan dari .env
        }
    }
}
