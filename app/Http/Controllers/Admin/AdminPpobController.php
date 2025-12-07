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

    /**
     * Menampilkan detail transaksi spesifik.
     */
    public function show($id)
    {
        $transaction = PpobTransaction::with('user')->findOrFail($id);
        
        // Decode JSON response jika ada, agar rapi saat ditampilkan
        $response_data = json_decode($transaction->desc, true) ?? $transaction->desc;

        return view('admin.ppob.data.show', compact('transaction', 'response_data'));
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
    public function destroy($id)
    {
        $transaction = PpobTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->route('admin.ppob.index')->with('success', 'Data transaksi berhasil dihapus.');
    }

    public function cekSaldo()
    {
        // 1. Ambil Kredensial
        $username = env('DIGIFLAZZ_USERNAME');
        $apiKey   = env('DIGIFLAZZ_API_KEY');
        $endpoint = 'https://api.digiflazz.com/v1/cek-saldo';

        // 2. Generate Signature
        $sign = md5($username . $apiKey . "depo");

        // 3. Payload
        $payload = [
            'cmd'      => 'deposit',
            'username' => $username,
            'sign'     => $sign
        ];

        try {
            // Log Request (Cek di storage/logs/laravel.log)
            \Illuminate\Support\Facades\Log::info('➡️ Cek Saldo Request:', $payload);

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                            ->post($endpoint, $payload);
            
            $result = $response->json();

            // Log Response (PENTING: Lihat apa balasan Digiflazz di sini)
            \Illuminate\Support\Facades\Log::info('⬅️ Cek Saldo Response:', $result);

            if (isset($result['data']['deposit'])) {
                $saldo = $result['data']['deposit'];
                return response()->json([
                    'status'    => 'success',
                    'saldo'     => $saldo,
                    'formatted' => 'Rp ' . number_format($saldo, 0, ',', '.')
                ]);
            } else {
                // Jika error, ambil pesan errornya
                $msg = $result['data']['message'] ?? 'Respon tidak valid';
                return response()->json(['status' => 'error', 'message' => $msg], 400);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Cek Saldo Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Koneksi Gagal'], 500);
        }
    }
}