<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\Api;
use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Support\Str;

class DokuBalanceController extends Controller
{
    public function index()
    {
        // 1. AMBIL KONFIGURASI DARI DATABASE
        $dbEnv = Api::where('key', 'DOKU_ENV')->value('value');
        $mode = (strtolower($dbEnv) === 'production') ? 'production' : 'sandbox';

        // Ambil ID Akun Utama (Cari di Scope dulu, lalu Global)
        $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', $mode)->value('value');
        if (empty($mainSacId)) {
            $mainSacId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'global')->value('value');
        }

        // 2. TERAPKAN KONFIGURASI AWAL (Biasanya Production sesuai DB Anda)
        $this->applyDokuConfig($mode);

        // 3. EKSEKUSI API & LOGIKA AUTO-SWITCH
        $dokuService = new DokuJokulService();
        $balanceData = null;
        $error = null;
        $isAutoSwitched = false;

        if (empty($mainSacId)) {
            $error = "ID Akun Utama (DOKU_MAIN_SAC_ID) tidak ditemukan.";
        } else {
            try {
                // COBA REQUEST PERTAMA
                $response = $dokuService->getBalance($mainSacId);

                // --- LOGIKA CERDAS V2 (LEBIH FLEKSIBEL) ---
                // Cek jika GAGAL + Mode PRODUCTION + Pesan Error mengandung "not found"
                $pesanError = $response['message'] ?? '';

                if (
                    $mode === 'production' &&
                    (!isset($response['success']) || !$response['success']) &&
                    (stripos($pesanError, 'not found') !== false) // <--- PERBAIKAN DI SINI
                ) {
                    Log::warning("DOKU Balance: Gagal di Production (404 Not Found). Mengaktifkan AUTO-SWITCH ke Sandbox...");

                    // 1. Paksa ubah config ke SANDBOX
                    $this->applyDokuConfig('sandbox');
                    $mode = 'sandbox';
                    $isAutoSwitched = true;

                    // 2. Buat Service Baru & Coba Lagi
                    $dokuService = new DokuJokulService();

                    // 3. Cari ID Sandbox (jika ID Production beda dengan Sandbox)
                    // Fallback lagi ke global jika di sandbox kosong
                    $sandboxId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'sandbox')->value('value');
                    if(empty($sandboxId)) {
                         $sandboxId = Api::where('key', 'DOKU_MAIN_SAC_ID')->where('environment', 'global')->value('value');
                    }

                    $response = $dokuService->getBalance($sandboxId);
                    $mainSacId = $sandboxId; // Update ID untuk tampilan View
                }
                // ------------------------------------------

                if (($response['success'] ?? false) === true && isset($response['data']['balance'])) {
                    $balanceData = $response['data'];
                } else {
                    $error = $response['message'] ?? 'Gagal mengambil data saldo.';
                }

            } catch (\Exception $e) {
                $error = 'Exception: ' . $e->getMessage();
            }
        }

        // 4. KIRIM KE VIEW
        return view('admin.doku.balance', [
            'mainSacId'   => $mainSacId,
            'mode'        => $mode,
            'balanceData' => $balanceData,
            'error'       => $error,
            'autoSwitched'=> $isAutoSwitched
        ]);
    }

    private function applyDokuConfig($scope)
    {
        if ($scope === 'production') {
            Config::set('doku.url', 'https://api.doku.com');
        } else {
            Config::set('doku.url', 'https://api-sandbox.doku.com');
        }
        Config::set('doku.mode', $scope);

        // Ambil Credential DB
        Config::set('doku.client_id', Api::where('key', 'DOKU_CLIENT_ID')->where('environment', $scope)->value('value'));
        Config::set('doku.secret_key', Api::where('key', 'DOKU_SECRET_KEY')->where('environment', $scope)->value('value'));

        // Fallback SAC Credential
        $sacClient = Api::where('key', 'DOKU_SAC_CLIENT_ID')->where('environment', $scope)->value('value');
        $sacSecret = Api::where('key', 'DOKU_SAC_SECRET_KEY')->where('environment', $scope)->value('value');

        Config::set('doku.sac_client_id', $sacClient ?: Config::get('doku.client_id'));
        Config::set('doku.sac_secret_key', $sacSecret ?: Config::get('doku.secret_key'));
    }

    /**
     * =========================================================================
     * FITUR BARU: Menampilkan Halaman Transfer Dana (Admin ke Sub Account)
     * =========================================================================
     */
    public function showTransferPage()
    {
        // Ambil data toko yang sudah memiliki SAC ID
        $stores = Store::whereNotNull('doku_sac_id')->orderBy('name', 'asc')->get();

        return view('admin.doku.transfer', compact('stores'));
    }

    /**
     * =========================================================================
     * FITUR BARU: Memproses Transfer Dana / Pencairan Payout (Admin ke Sub Account)
     * =========================================================================
     */
    public function processTransfer(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255'
        ]);

        $store = Store::findOrFail($request->store_id);
        $amount = (int) $request->amount;
        $description = $request->description ?? 'Transfer/Pencairan dana dari Admin';
        
        $targetSacId = $store->doku_sac_id;
        $payoutRefId = 'ADM-TRF-' . time() . '-' . Str::random(4);

        Log::info('LOG LOG: [ADMIN DOKU] Memulai Transfer Intra ke ' . $store->name, [
            'target_sac' => $targetSacId,
            'amount' => $amount,
            'ref_id' => $payoutRefId
        ]);

        // 1. Terapkan Config DOKU
        $dbEnv = Api::where('key', 'DOKU_ENV')->value('value');
        $mode = (strtolower($dbEnv) === 'production') ? 'production' : 'sandbox';
        $this->applyDokuConfig($mode);

        $dokuService = new DokuJokulService();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 2. Eksekusi Transfer ke Service DOKU
            // Pastikan DokuJokulService Anda memiliki method transferToSubAccount()
            $response = $dokuService->transferToSubAccount(
                $payoutRefId,
                $targetSacId,
                $amount,
                $description
            );

            // 3. Cek Respon
            if (isset($response['status']) && $response['status'] === 'SUCCESS') {
                
                // Catat transaksi
                Transaction::create([
                    'user_id' => $store->user_id,
                    'reference_id' => $response['transaction_id'] ?? $payoutRefId,
                    'amount' => $amount,
                    'type' => 'admin_transfer_to_sac',
                    'status' => 'success',
                    'payment_method' => 'internal_doku',
                    'description' => $description,
                ]);

                // Update cache saldo tersedia milik toko (UI instan)
                $store->doku_balance_available += $amount;
                $store->doku_balance_last_updated = now();
                $store->save();

                \Illuminate\Support\Facades\DB::commit();
                Log::info('LOG LOG: [ADMIN DOKU] Transfer Sukses ke ' . $targetSacId);

                return redirect()->back()->with('success', 'Berhasil! Dana sebesar Rp ' . number_format($amount, 0, ',', '.') . ' telah ditransfer ke Dompet Toko: ' . $store->name);
            }

            // Jika Gagal dari DOKU
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('LOG LOG: [ADMIN DOKU] Transfer Gagal via API.', ['response' => $response]);
            return redirect()->back()->with('error', 'Gagal mentransfer dana: ' . ($response['message'] ?? 'Kesalahan dari server DOKU.'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::critical('LOG LOG: [ADMIN DOKU] Exception Error!', ['msg' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * FUNGSI: Admin Transfer ke Sub Account Toko
     */
    public function transferToStore(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'amount'   => 'required|numeric|min:1000',
        ]);

        $store = \App\Models\Store::findOrFail($request->store_id);
        $amount = (int) $request->amount;
        $refId = 'ADM-TRF-' . time() . '-' . Str::random(4);

        try {
            // Instansiasi Service
            $dokuService = new \App\Services\DokuJokulService();
            
            // Panggil fungsi transfer antar Sub-Account (Admin -> Toko)
            $response = $dokuService->transferToSubAccount(
                $refId,
                $store->doku_sac_id,
                $amount,
                "Transfer Saldo Admin"
            );

            if ($response['status'] === 'SUCCESS') {
                // Update lokal saldo toko
                $store->increment('doku_balance_available', $amount);
                $store->update(['doku_balance_last_updated' => now()]);

                return back()->with('success', 'Transfer Rp ' . number_format($amount) . ' ke ' . $store->name . ' berhasil!');
            }

            return back()->with('error', 'Gagal Transfer: ' . ($response['message'] ?? 'Error API DOKU'));

        } catch (\Exception $e) {
            Log::error('ADMIN TRANSFER ERROR: ' . $e->getMessage());
            return back()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }
}
