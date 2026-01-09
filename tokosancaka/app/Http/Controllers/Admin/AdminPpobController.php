<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PpobTransaction;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan package dompdf sudah diinstall
use Illuminate\Support\Facades\Http; // WAJIB: Tambahkan ini untuk akses API

class AdminPpobController extends Controller
{
    /**
     * Menampilkan data transaksi dengan filter & search.
     */
    public function index(Request $request)
    {
        // Panggil fungsi query builder private di bawah
        $query = $this->getFilteredQuery($request);

        // Gunakan paginate untuk tampilan web
        $transactions = $query->paginate(20);

        return view('admin.ppob.data.index', compact('transactions'));
    }

    /**
     * Export data ke Excel (Format CSV)
     * Menggunakan Stream Download agar hemat memori server.
     */
    public function exportExcel(Request $request)
    {
        $fileName = 'transaksi_ppob_' . date('Y-m-d_H-i') . '.csv';
        
        // Ambil semua data (bukan paginate)
        $transactions = $this->getFilteredQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Waktu', 'Order ID', 'User', 'Produk', 'Tujuan', 'Harga Beli', 'Harga Jual', 'Profit', 'Status', 'SN / Pesan'];

        $callback = function() use ($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $trx) {
                // Gunakan helper get_ppob_message jika ada RC
                $statusMessage = $trx->sn ? "SN: " . $trx->sn : (function_exists('get_ppob_message') && $trx->rc ? get_ppob_message($trx->rc) : $trx->message);

                $row = [
                    $trx->id,
                    $trx->created_at->format('Y-m-d H:i:s'),
                    $trx->order_id,
                    $trx->user->name ?? 'User Terhapus',
                    $trx->product_name . ' (' . $trx->buyer_sku_code . ')',
                    $trx->customer_no,
                    $trx->price, // Harga Beli (Modal)
                    $trx->selling_price, // Harga Jual
                    $trx->profit,
                    $trx->status,
                    $statusMessage
                ];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export data ke PDF
     * Membutuhkan view khusus untuk cetak.
     */
    public function exportPdf(Request $request)
    {
        // Ambil semua data
        $transactions = $this->getFilteredQuery($request)->get();

        // Hitung total untuk ringkasan di PDF
        $totalOmset = $transactions->sum('selling_price');
        $totalProfit = $transactions->sum('profit');

        // Load View PDF (Kita akan buat view ini di langkah ke-2)
        $pdf = Pdf::loadView('admin.ppob.data.pdf', compact('transactions', 'totalOmset', 'totalProfit'));
        
        // Setup Kertas Landscape agar muat banyak kolom
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-transaksi-ppob-' . date('d-m-Y') . '.pdf');
    }

    /**
     * PRIVATE FUNCTION: Logika Filter Utama
     * Dipisahkan agar tidak menulis ulang kode yang sama di index, excel, dan pdf.
     */
    private function getFilteredQuery(Request $request)
    {
        $query = PpobTransaction::with('user')->latest(); 

        // 1. SEARCH
        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_no', 'like', "%{$search}%")
                  ->orWhere('sn', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. FILTER STATUS
        if ($request->has('status') && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. FILTER TANGGAL
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate   = $request->end_date . ' 23:59:59';
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }

public function show($id)
{
    // 1. Ambil data transaksi
    $transaction = PpobTransaction::with('user')->findOrFail($id);

    // 2. TANGKAP JSON SEBAGAI ARRAY
    // Ambil data mentah
    $rawDesc = $transaction->desc;
    
    // Default array kosong jika null
    $responseData = []; 

    // Cek: Jika tipe datanya string, kita decode. 
    // Jika sudah array (karena $casts di model), biarkan saja.
    if (is_string($rawDesc)) {
        // Parameter 'true' membuat hasil menjadi ARRAY, bukan Object.
        $responseData = json_decode($rawDesc, true);
    } elseif (is_array($rawDesc)) {
        $responseData = $rawDesc;
    }

    // 3. Kirim ke View
    return view('admin.ppob.data.show', [
        'transaction'   => $transaction,
        'response_data' => $responseData // Ini sekarang sudah jadi Array
    ]);
}

    /**
     * Update status transaksi secara manual (Emergency use).
     * Berguna jika webhook/callback provider gagal.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,Success,Failed',
            'sn'     => 'nullable|string'
        ]);

        $transaction = PpobTransaction::findOrFail($id);
        
        $oldStatus = $transaction->status;
        $transaction->status = $request->status;
        
        // Update SN jika diisi admin
        if ($request->filled('sn')) {
            $transaction->sn = $request->sn;
        }

        $transaction->save();

        // TODO: Tambahkan logika pengembalian saldo jika status diubah dari Pending ke Failed
        // if ($oldStatus == 'Pending' && $request->status == 'Failed') {
        //      $user = $transaction->user;
        //      $user->balance += $transaction->price;
        //      $user->save();
        // }

        return redirect()->back()->with('success', 'Status transaksi berhasil diperbarui manual.');
    }

    /**
     * Hapus transaksi (Hati-hati, biasanya soft delete lebih disarankan).
     */
   // Pastikan Anda menggunakan 'PpobProduct' di Controller Anda.

// Fungsi Asli Anda (Menghapus Transaksi)

public function destroy($id)
{
    // Ini menghapus data dari tabel 'ppob_transactions'
    $transaction = PpobTransaction::findOrFail($id); 
    $transaction->delete();

    // Pastikan 'admin.ppob.data.index' adalah route untuk daftar transaksi PPOB
    // (Berdasarkan route file Anda, daftar transaksi adalah 'ppob.data.index')
    return redirect()->route('admin.ppob.data.index')->with('success', 'Data transaksi berhasil dihapus.');
}

    /**
     * FITUR BARU: CEK SALDO DIGIFLAZZ
     * Sesuai dokumentasi: md5(username + apiKey + "depo")
     */
    public function cekSaldo()
{
    // Pastikan Anda sudah mengimpor Facade Http di bagian atas file Controller:
    // use Illuminate\Support\Facades\Http; 
    
    // =================================================================
    // 1. KREDENSIAL LANGSUNG (HARDCODE)
    // =================================================================
    $username = 'mihetiDVGdeW'; 
    $apiKey   = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; 
    $endpoint = 'https://api.digiflazz.com/v1/cek-saldo';

    // 2. Generate Signature
    // Rumus: md5(username + apiKey + "depo")
    $sign = md5($username . $apiKey . "depo");

    // 3. Payload
    $payload = [
        'cmd' => 'deposit',
        'username' => $username,
        'sign' => $sign
    ];

    try {
        // Log Request (Untuk memastikan data yang dikirim benar)
        \Illuminate\Support\Facades\Log::info('➡️ Cek Saldo Request (Hardcode):', $payload);

        // 4. Kirim Request
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
                         ->timeout(30) // ✅ TAMBAH TIMEOUT 20 DETIK
                         ->retry(2, 500) // ✅ COBA ULANG 2 KALI JIKA GAGAL/TIMEOUT (dengan delay 500ms)
                         ->post($endpoint, $payload);
        
        // 5. Cek apakah request gagal pada level HTTP (timeout, koneksi)
        if ($response->failed()) {
            // Ini akan menangkap kegagalan yang tidak memicu Exception (seperti HTTP error codes 4xx/5xx dari Digiflazz)
            // Namun, karena log Anda menunjukkan cURL error 28, Exception block di bawah lebih sering bekerja.
            \Illuminate\Support\Facades\Log::error('❌ Cek Saldo HTTP Failure (Status: ' . $response->status() . '): ' . $response->body());
            
            // Jika status 400/500, kita kembalikan error.
             return response()->json(['status' => 'error', 'message' => 'Gagal koneksi ke API (Status: ' . $response->status() . ')'], $response->status());
        }

        $result = $response->json();

        // Log Response
        \Illuminate\Support\Facades\Log::info('⬅️ Cek Saldo Response:', $result ?? []);

        // 6. Proses Hasil (Validasi Respons API)
        if (isset($result['data']['deposit'])) {
            $saldo = $result['data']['deposit'];
            return response()->json([
                'status' => 'success',
                'saldo'  => $saldo,
                'formatted' => 'Rp ' . number_format($saldo, 0, ',', '.')
            ]);
        } else {
            // Ambil pesan error dari Digiflazz jika ada (misal: signature salah)
            $msg = $result['data']['message'] ?? 'Gagal mengambil data (Response tidak valid atau Deposit tidak ditemukan).';
            \Illuminate\Support\Facades\Log::error('❌ Cek Saldo API Error: ' . $msg);
            
            return response()->json(['status' => 'error', 'message' => $msg], 400);
        }

    } catch (\Illuminate\Http\Client\RequestException $e) {
         // Ini menangkap cURL error 28 (timeout)
        \Illuminate\Support\Facades\Log::error('❌ Cek Saldo Exception (Request Timeout/Failed): ' . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => 'Gagal: Waktu koneksi habis atau error jaringan. Cek IP Whitelist.'], 500);

    } catch (\Exception $e) {
        // Menangkap exception umum lainnya
        \Illuminate\Support\Facades\Log::error('❌ Cek Saldo Exception (General): ' . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => 'Koneksi Server Gagal (Internal Server Error)'], 500);
    }
}

    /**
     * REQUEST TIKET DEPOSIT KE DIGIFLAZZ (DENGAN LOG)
     */
    public function requestDeposit(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'amount'     => 'required|numeric|min:200000',
            'bank'       => 'required|string',
            'owner_name' => 'required|string',
        ]);

        // 2. Kredensial Langsung (HARDCODE)
        $username = 'mihetiDVGdeW'; 
        $apiKey   = '1f48c69f-8676-5d56-a868-10a46a69f9b7'; 
        $endpoint = 'https://api.digiflazz.com/v1/deposit';

        // 3. Generate Signature
        $sign = md5($username . $apiKey . "deposit");

        // 4. Siapkan Payload
        $payload = [
            'username'   => $username,
            'amount'     => (int) $request->amount,
            'Bank'       => $request->bank, 
            'owner_name' => $request->owner_name,
            'sign'       => $sign
        ];

        try {
            // [LOG 1] Mencatat apa yang kita kirim
            \Illuminate\Support\Facades\Log::info('➡️ [DEPOSIT REQ] Mengirim request ke Digiflazz:', $payload);

            // 5. Kirim Request
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                            ->post($endpoint, $payload);
            
            $result = $response->json();

            // [LOG 2] Mencatat balasan mentah dari Digiflazz
            \Illuminate\Support\Facades\Log::info('⬅️ [DEPOSIT RESP] Balasan Server:', $result ?? []);

            // 6. Cek Response RC "00" (Sukses)
            if (isset($result['data']['rc']) && $result['data']['rc'] === '00') {
                
                // [LOG 3] Sukses
                \Illuminate\Support\Facades\Log::info('✅ [DEPOSIT SUKSES] Tiket berhasil dibuat. Nominal: ' . ($result['data']['amount'] ?? 0));

                return response()->json([
                    'status' => 'success',
                    'data'   => $result['data']
                ]);
            } else {
                // [LOG 4] Gagal dari API (Misal: Saldo kurang, Bank gangguan, dll)
                $msg = $result['data']['message'] ?? 'Gagal request deposit.';
                \Illuminate\Support\Facades\Log::warning('⚠️ [DEPOSIT GAGAL] API Menolak: ' . $msg);

                return response()->json(['status' => 'error', 'message' => $msg], 400);
            }

        } catch (\Exception $e) {
            // [LOG 5] Error Sistem / Koneksi
            \Illuminate\Support\Facades\Log::error('❌ [DEPOSIT ERROR] Exception: ' . $e->getMessage());

            return response()->json([
                'status' => 'error', 
                'message' => 'Koneksi Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FITUR TOPUP / TRANSAKSI MANUAL
     * Endpoint: https://api.digiflazz.com/v1/transaction
     */
    public function topup(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'buyer_sku_code' => 'required|string', // Contoh: xld10, pulsa5
            'customer_no'    => 'required|string', // Nomor HP / ID Pelanggan
        ]);

        // 2. Kredensial Langsung (HARDCODE)
        $username = 'mihetiDVGdeW';
        $apiKey   = '1f48c69f-8676-5d56-a868-10a46a69f9b7';
        $endpoint = 'https://api.digiflazz.com/v1/transaction';

        // 3. Generate Ref ID Unik
        // Format: TRX-[TIMESTAMP]-[RANDOM] agar tidak duplikat
        $refId = 'TRX-' . time() . rand(100, 999);

        // 4. Generate Signature
        // Formula: md5(username + apiKey + ref_id)
        $sign = md5($username . $apiKey . $refId);

        // 5. Siapkan Payload
        $payload = [
            'username'       => $username,
            'buyer_sku_code' => $request->buyer_sku_code,
            'customer_no'    => $request->customer_no,
            'ref_id'         => $refId,
            'sign'           => $sign,
            'testing'        => false // Ubah ke true jika ingin mode testing
        ];

        try {
            // [LOG REQUEST]
            \Illuminate\Support\Facades\Log::info('➡️ [TOPUP REQ] ' . $refId, $payload);

            // 6. Kirim Request
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                            ->post($endpoint, $payload);
            
            $result = $response->json();

            // [LOG RESPONSE]
            \Illuminate\Support\Facades\Log::info('⬅️ [TOPUP RESP] ' . $refId, $result ?? []);

            // 7. Cek Hasil
            if (isset($result['data'])) {
                $data = $result['data'];
                
                // Disini Anda bisa menambahkan logic simpan ke database (PpobTransaction)
                // PpobTransaction::create([...]);

                return response()->json([
                    'status'  => 'success',
                    'message' => $data['message'], // "Transaksi Pending" / "Sukses"
                    'data'    => $data
                ]);
            } else {
                return response()->json([
                    'status'  => 'error', 
                    'message' => 'Respon tidak valid dari provider'
                ], 400);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ [TOPUP ERROR] ' . $e->getMessage());
            return response()->json([
                'status'  => 'error', 
                'message' => 'Koneksi Error: ' . $e->getMessage()
            ], 500);
        }
    }
}