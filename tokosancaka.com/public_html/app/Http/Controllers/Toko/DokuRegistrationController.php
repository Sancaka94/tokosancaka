<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // <-- WAJIB DITAMBAHKAN
use App\Services\DokuJokulService;
use App\Services\DokuSacService; // Pastikan pakai Service SAC yang baru
use App\Models\User;
use App\Models\Store;
use App\Models\Transaction; // <-- PERBAIKAN: Import Transaction
use Exception;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // <-- PERBAIKAN: Import DB

class DokuRegistrationController extends Controller
{
    protected $dokuService;

    public function __construct(DokuJokulService $dokuService)
    {
        $this->dokuService = $dokuService;
    }

    /**
     * Menampilkan halaman status & pendaftaran DOKU
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir. Harap login kembali.');
        }

        $store = $user->store;

        if (!$store) {
            return redirect()->route('seller.dashboard') // Ganti ke route dashboard seller Anda
                ->with('error', 'Anda harus melengkapi profil toko Anda terlebih dahulu.');
        }

        // ==========================================================
        // === LOGIKA CACHE SALDO (Sudah Benar) ===
        // ==========================================================
        $isCacheStale = $store->doku_balance_last_updated ?
                        Carbon::parse($store->doku_balance_last_updated)->addMinutes(15)->isPast() :
                        true;

        if ($store->doku_sac_id && $isCacheStale && $store->doku_status == 'ACTIVE') { // <-- BARU: Hanya cek jika status ACTIVE
            Log::info("DOKU CACHE: Saldo untuk toko $store->id sudah basi, mengambil data baru...");
            try {
                $balanceResponse = $this->dokuService->getBalance($store->doku_sac_id);

                if ($balanceResponse['success'] ?? false) {

                    $store->doku_balance_available = $balanceResponse['data']['balance']['available'] ?? 0;
                    $store->doku_balance_pending = $balanceResponse['data']['balance']['pending'] ?? 0;
                    $store->doku_balance_last_updated = now();
                    $store->save();
                    Log::info("DOKU CACHE: Saldo toko $store->id berhasil diperbarui di DB.");

                } else {
                    Log::warning('Gagal mengambil saldo DOKU untuk toko: ' . $store->id, [
                        'message' => $balanceResponse['message'] ?? 'Tidak ada pesan'
                    ]);
                }
            } catch (Exception $e) {
                Log::error('DokuJokulService getBalance error: ' . $e->getMessage(), ['store_id' => $store->id]);
            }
        }
        // ==========================================================

        return view('seller.doku.index', compact('store', 'user'));
    }

    /**
     * Memproses pendaftaran Sub Account DOKU
     */
    public function register(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir. Harap login kembali.');
        }

        $store = $user->store;

        if (!$store) {
            return redirect()->back()->with('error', 'Profil toko tidak ditemukan.');
        }

        if ($store->doku_sac_id) {
            return redirect()->back()->with('info', 'Toko Anda sudah terdaftar di DOKU.');
        }



        try {
            if (empty($store->name) || empty($user->email)) {
                return redirect()->back()->with('error', 'Nama Toko dan Email Pendaftar tidak boleh kosong.');
            }

            $phone = $user->no_wa;

            if (empty($phone)) {
                return redirect()->back()->with('error', 'Nomor HP Anda di profil (akun) belum diisi. Harap lengkapi profil Anda terlebih dahulu.');
            }

            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (Str::startsWith($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $response = $this->dokuService->createSubAccount(
                $user,
                $store,
                $phone
            );

            if (is_array($response) && !empty($response['success'])) {

                $sac_id = $response['data']['profileId'] ?? null;

                if (!$sac_id) {
                     return redirect()->back()->with('error', 'Pendaftaran DOKU Gagal: ID Sub Account (profileId) tidak ditemukan dalam respons DOKU.');
                }

                // ==========================================================
                // === SIMPAN DATA BARU KE DATABASE (Sudah Benar) ===
                // ==========================================================
                $store->doku_sac_id = $sac_id;
                $store->doku_status = 'PENDING'; // <- Status awal
                $store->doku_balance_available = 0; // <- Inisialisasi
                $store->doku_balance_pending = 0; // <- Inisialisasi
                $store->doku_balance_last_updated = now(); // <- Set waktu cache
                $store->save();
                // ==========================================================

                return redirect()->back()->with('success', 'Toko Anda berhasil didaftarkan ke DOKU! Sub Account ID Anda adalah: ' . $sac_id);

            } else {
                $errorMessage = (is_array($response) && !empty($response['message']))
                    ? $response['message']
                    : 'Terjadi kesalahan tidak terduga saat mendaftar.';

                Log::error('DOKU Sub-Account Gagal (Respon API): ' . $errorMessage, ['store_id' => $store->id, 'response' => $response]);
                return redirect()->back()->with('error', 'Pendaftaran DOKU Gagal: ' . $errorMessage);
            }

        } catch (Exception $e) {
            Log::error('DOKU Sub-Account Gagal (Exception): ' . $e->getMessage(), [
                'store_id' => $store->id ?? 'Unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Pendaftaran DOKU Gagal: Terjadi masalah koneksi ke server DOKU. Silakan coba lagi nanti.');
        }
    }

     // =================================================================
    // === FITUR BARU: PAYOUT, TRANSFER, REFRESH ===
    // =================================================================

    /**
     * (PERBAIKAN) Memaksa refresh saldo dari API DOKU
     * Tombol ini harus selalu memanggil API, tidak peduli statusnya.
     */
    public function refreshBalance(Request $request)
    {
        $store = Auth::user()->store;

        if (!$store || !$store->doku_sac_id) {
            return redirect()->back()->with('error', 'Akun DOKU tidak ditemukan.');
        }

        // ==========================================================
        // === PERBAIKAN: Panggil API langsung di sini ===
        // ==========================================================
        Log::info("DOKU MANUAL REFRESH: Memaksa refresh saldo untuk toko $store->id...");
        try {
            $balanceResponse = $this->dokuService->getBalance($store->doku_sac_id);

            if ($balanceResponse['success'] ?? false) {
                // Langsung simpan ke DB
                $store->doku_balance_available = $balanceResponse['data']['balance']['available'] ?? 0;
                $store->doku_balance_pending = $balanceResponse['data']['balance']['pending'] ?? 0;
                $store->doku_balance_last_updated = now();
                $store->save();
                Log::info("DOKU MANUAL REFRESH: Saldo toko $store->id berhasil diperbarui.");
                return redirect()->route('seller.doku.index')->with('success', 'Saldo berhasil disinkronkan!');

            } else {
                Log::warning('Gagal refresh saldo DOKU untuk toko: ' . $store->id, [
                    'message' => $balanceResponse['message'] ?? 'Tidak ada pesan'
                ]);
                return redirect()->route('seller.doku.index')->with('error', 'Gagal menyinkronkan saldo: ' . $balanceResponse['message']);
            }
        } catch (Exception $e) {
            Log::error('DokuJokulService getBalance error (Manual Refresh): ' . $e->getMessage(), ['store_id' => $store->id]);
            return redirect()->route('seller.doku.index')->with('error', 'Terjadi kesalahan koneksi saat menyinkronkan saldo.');
        }
        // ==========================================================
    }

 /**
     * (BARU) Menangani form Payout (Withdrawal)
     */
    public function handlePayout(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        // [LOG 1] Cek Data Masuk
        Log::info('PAYOUT: Request Masuk', [
            'store_id' => $store->id ?? 'null',
            'user_id' => $user->id ?? 'null',
            'sac_id' => $store->doku_sac_id ?? 'KOSONG',
            'input_raw' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10000',
            'bank_code' => 'required|string', // Hapus max:10 jika bank code panjang
            'bank_account_number' => 'required|string',
            'bank_account_name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            // [LOG 2] Gagal Validasi Input
            Log::warning('PAYOUT: Gagal Validasi Input', ['errors' => $validator->errors()->toArray()]);
            return redirect()->back()->withErrors($validator)->withInput()->with('activeTab', 'payout');
        }

        $amount = (int) $request->input('amount');

        // [LOG 3] Cek Saldo Sebelum Proses
        Log::info('PAYOUT: Pengecekan Saldo', [
            'saldo_tersedia' => $store->doku_balance_available,
            'permintaan' => $amount
        ]);

        if ($store->doku_balance_available < $amount) {
            Log::warning('PAYOUT: Saldo Tidak Cukup');
            return redirect()->back()->with('error', 'Saldo Tersedia tidak mencukupi.')->with('activeTab', 'payout');
        }

        try {
            // --- PERBAIKAN 1: Sanitasi Nomor Rekening ---
            $cleanRekening = preg_replace('/[^0-9]/', '', $request->input('bank_account_number'));

            $beneficiary = [
                'bank_code' => $request->input('bank_code'),
                'bank_account_number' => $cleanRekening,
                'bank_account_name' => $request->input('bank_account_name'),
            ];

            // --- PERBAIKAN 2: Invoice Lebih Unik ---
            $invoice_number = 'WD-' . $store->id . '-' . time() . '-' . Str::random(4);

            // [LOG 4] Siap Tembak API
            Log::info('PAYOUT: Mengirim Request ke Service DOKU...', [
                'sac_id' => $store->doku_sac_id,
                'amount' => $amount,
                'invoice' => $invoice_number,
                'beneficiary' => $beneficiary
            ]);

            $response = $this->dokuService->sendPayout(
                $store->doku_sac_id,
                $amount,
                $invoice_number,
                $beneficiary
            );

            // [LOG 5] Respon Mentah dari Service
            Log::info('PAYOUT: Respon dari Service DOKU', ['response' => $response]);

            if ($response['success'] ?? false) {
                // Kurangi saldo cache (akan diforce-refresh oleh webhook, tapi ini untuk UI instan)
                $store->doku_balance_available -= $amount;
                $store->doku_balance_last_updated = now(); // Set cache baru
                $store->save();

                // [LOG 6] Sukses & DB Updated
                Log::info('PAYOUT: SUKSES. Saldo lokal dipotong.', [
                    'sisa_saldo' => $store->doku_balance_available,
                    'status_doku' => $response['data']['payout']['status'] ?? 'UNKNOWN'
                ]);

                return redirect()->back()->with('success', 'Permintaan Payout berhasil dikirim! Status: ' . ($response['data']['payout']['status'] ?? 'Success'))->with('activeTab', 'payout');
            } else {
                // [LOG 7] Gagal dari API DOKU
                Log::error('PAYOUT: GAGAL API DOKU', ['pesan_error' => $response['message']]);
                return redirect()->back()->with('error', 'Payout Gagal: ' . $response['message'])->with('activeTab', 'payout');
            }

        } catch (Exception $e) {
            // [LOG 8] Exception / Crash
            Log::error('PAYOUT: EXCEPTION SYSTEM', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString() // Opsional, nyalakan jika butuh trace lengkap
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat Payout: ' . $e->getMessage())->with('activeTab', 'payout');
        }
    }

    /**
     * (BARU) Menangani form Transfer Intra
     */
    public function handleTransfer(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000', // Minimal transfer 1.000
            'destination_sac_id' => 'required|string|starts_with:SAC-',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('activeTab', 'transfer');
        }

        $amount = (int) $request->input('amount');
        if ($store->doku_balance_available < $amount) {
             return redirect()->back()->with('error', 'Saldo Tersedia tidak mencukupi untuk melakukan transfer.')->with('activeTab', 'transfer');
        }

        $destination_sac_id = $request->input('destination_sac_id');
        if ($destination_sac_id == $store->doku_sac_id) {
             return redirect()->back()->with('error', 'Anda tidak bisa transfer ke akun Anda sendiri.')->with('activeTab', 'transfer');
        }

        try {
            $response = $this->dokuService->transferIntra(
                $store->doku_sac_id,
                $destination_sac_id,
                $amount
            );

            if ($response['success'] ?? false) {
                // Kurangi saldo cache
                $store->doku_balance_available -= $amount;
                $store->doku_balance_last_updated = now(); // Set cache baru
                $store->save();

                return redirect()->back()->with('success', 'Transfer berhasil dikirim! Status: ' . $response['data']['transfer']['status'])->with('activeTab', 'transfer');
            } else {
                return redirect()->back()->with('error', 'Transfer Gagal: ' . $response['message'])->with('activeTab', 'transfer');
            }

        } catch (Exception $e) {
            Log::error('DOKU Transfer Exception', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat Transfer: ' . $e->getMessage())->with('activeTab', 'transfer');
        }
    }

     // =================================================================
    // === FITUR BARU: PENCAIRAN SALDO UTAMA KE DOMPET ===
    // =================================================================

    /**
     * (BARU) Memindahkan dana dari Saldo Utama (user->saldo)
     * ke Dompet Sancaka (Store->doku_sac_id)
     */
    public function cairkanSaldoUtama(Request $request)
    {
        $user = Auth::user();
        $store = $user->store;

        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'amount_cairkan' => 'required|numeric|min:1000', // Minimal transfer
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('tab', 'ringkasan');
        }

        $amount = (int) $request->input('amount_cairkan');

        // 2. Validasi Bisnis
        if (!$store || !$store->doku_sac_id) {
             return redirect()->back()->with('error', 'Dompet Sancaka Express Anda tidak terdaftar.')->with('tab', 'ringkasan');
        }

        if ($store->doku_status !== 'ACTIVE') {
             return redirect()->back()->with('error', 'Akun Dompet Sancaka Express Anda harus aktif untuk menerima pencairan.')->with('tab', 'ringkasan');
        }

        if ($amount > $user->saldo) {
            return redirect()->back()->with('error', 'Saldo Utama Anda (Rp ' . number_format($user->saldo) . ') tidak mencukupi untuk pencairan ini.')->with('tab', 'ringkasan');
        }

        // Ambil ID Akun Pusat DOKU dari .env
        $mainSacId = config('doku.main_sac_id');
        if (empty($mainSacId)) {
            Log::error('DOKU CAIRKAN: DOKU_MAIN_SAC_ID belum di-set di .env');
            return redirect()->back()->with('error', 'Fitur pencairan sedang tidak tersedia (Error: Config 501).')->with('tab', 'ringkasan');
        }

        // 3. Proses Transaksi
        DB::beginTransaction();
        try {
            // Panggil service DOKU untuk transfer dari Akun Pusat ke Akun Seller
            $response = $this->dokuService->transferIntra(
                $mainSacId,              // Dari: Akun Pusat
                $store->doku_sac_id,    // Ke: Dompet Seller
                $amount
            );

            if ($response['success'] ?? false) {

                // Jika DOKU berhasil, kurangi Saldo Utama
                $user->decrement('saldo', $amount);

                // Tambahkan ke Saldo Tertunda (karena transfer DOKU butuh waktu)
                // ATAU langsung tambah ke Saldo Tersedia (tergantung respons DOKU)
                // Kita anggap masuk ke Tersedia untuk saat ini.
                $store->doku_balance_available += $amount;
                $store->doku_balance_last_updated = now();
                $store->save();

                // Catat transaksi internal
                Transaction::create([
                    'user_id' => $user->id_pengguna,
                    'amount' => $amount,
                    'type' => 'payout_internal', // Tipe baru: pencairan internal
                    'status' => 'success',
                    'payment_method' => 'internal',
                    'description' => 'Pencairan Saldo Utama ke Dompet Sancaka Express',
                    'reference_id' => $response['data']['transfer']['invoice_number'] ?? 'INTERNAL-'.Str::uuid(),
                ]);

                DB::commit();
                return redirect()->route('seller.doku.index')->with('success', 'Pencairan Rp ' . number_format($amount) . ' ke Dompet Sancaka Express berhasil.');

            } else {
                DB::rollBack();
                return redirect()->back()->with('error', 'DOKU Transfer Gagal: ' . $response['message'])->with('tab', 'ringkasan');
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('cairkanSaldoUtama Exception: ' . $e->getMessage(), ['store_id' => $store->id]);
            return redirect()->back()->with('error', 'Terjadi kesalahan server saat memproses pencairan.')->with('tab', 'ringkasan');
        }
    }

    public function refreshDokuStatus()
    {
        $user = Auth::user();
        $store = $user->store;

        if (!$store || !$store->doku_sac_id) {
            return back()->with('error', 'Akun DOKU belum terdaftar.');
        }

        try {
            // === KREDENSIAL (Tetap Sama) ===
            $mode = \App\Models\Api::getValue('DOKU_MODE', 'global', 'sandbox');
            if ($mode === 'production') {
                $clientId = \App\Models\Api::getValue('DOKU_CLIENT_ID', 'production');
                $secretKey = \App\Models\Api::getValue('DOKU_SECRET_KEY', 'production');
                $baseUrl = 'https://jokul.doku.com';
            } else {
                $clientId = \App\Models\Api::getValue('DOKU_CLIENT_ID', 'sandbox');
                $secretKey = \App\Models\Api::getValue('DOKU_SECRET_KEY', 'sandbox');
                $baseUrl = 'https://api-sandbox.doku.com';
            }

            if (!$clientId || !$secretKey) return back()->with('error', 'Konfigurasi API belum lengkap.');

            // === REQUEST (Tetap Sama) ===
            $requestId = 'REQ-' . time() . rand(100,999);
            $timestamp = gmdate("Y-m-d\TH:i:s\Z");
            $targetPath = '/sac-merchant/v1/accounts/' . $store->doku_sac_id;

            $rawSignature = "Client-Id:" . $clientId . "\n" .
                            "Request-Id:" . $requestId . "\n" .
                            "Request-Timestamp:" . $timestamp . "\n" .
                            "Request-Target:" . $targetPath;

            $signature = "HMACSHA256=" . base64_encode(hash_hmac('sha256', $rawSignature, $secretKey, true));

            $response = Http::withHeaders([
                'Client-Id'         => $clientId,
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $signature,
            ])->get($baseUrl . $targetPath);

            $data = $response->json();

            if ($response->successful()) {

                // === [PERBAIKAN LOGIKA PENCARIAN STATUS] ===
                // Berdasarkan log Anda: {"account": {"status": "ACTIVE"}}
                $statusApi = $data['account']['status'] // <--- Prioritas Utama Sesuai Log
                             ?? ($data['status']
                             ?? ($data['data']['status'] ?? null));

                if ($statusApi) {
                    $store->doku_status = strtoupper($statusApi);
                    $store->save();

                    if (strtoupper($statusApi) === 'ACTIVE') {
                        return back()->with('success', 'Berhasil! Status Akun DOKU sekarang ACTIVE.');
                    } else {
                        return back()->with('warning', 'Status berhasil diambil: ' . $statusApi);
                    }
                } else {
                    return back()->with('error', 'API Valid tapi kolom status tidak ditemukan.');
                }
            } else {
                Log::error('Gagal Cek Status DOKU', ['resp' => $data]);
                return back()->with('error', 'Gagal: ' . ($data['error']['message'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            Log::error('Exception DOKU Status', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Error Sistem: ' . $e->getMessage());
        }
    }

}
