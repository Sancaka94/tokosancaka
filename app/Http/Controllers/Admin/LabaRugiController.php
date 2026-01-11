<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\LabaRugiExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LabaRugiController extends Controller
{
    /**
     * FUNGSI PRIVATE: PENGOLAH DATA PUSAT
     * Logika disamakan persis dengan KeuanganController
     */
    private function getDataLaporan($tahun)
    {
        // ==========================================================
        // 1. SIAPKAN STRUKTUR ARRAY BULANAN (1-12)
        // ==========================================================
        $months = [];
        $report = [];
        
        for ($m = 1; $m <= 12; $m++) {
            $namaBulan = Carbon::create()->month($m)->locale('id')->format('F');
            $months[$m] = $namaBulan;

            $report[$m] = [
                'pendapatan' => [
                    'Ekspedisi' => 0, 
                    'PPOB' => 0, 
                    'Marketplace' => 0, 
                    'Top Up Saldo' => 0, // Ditambahkan sesuai Logic Lama
                    'Lain-lain' => 0
                ],
                'total_pendapatan' => 0,
                'hpp' => [
                    'Beban Pokok Ekspedisi' => 0, 
                    'Beban Pokok PPOB' => 0, 
                    'Beban Pokok Marketplace' => 0,
                    'Beban Pokok Top Up' => 0 // Ditambahkan (Topup tidak ada profit, jadi Omzet = HPP)
                ],
                'total_hpp' => 0,
                'laba_kotor' => 0,
                'beban' => [], // Untuk Pengeluaran Operasional (Gaji, Listrik, dll)
                'total_beban' => 0,
                'laba_bersih' => 0
            ];
        }

        // ==========================================================
        // 2. LOGIKA HITUNGAN (SUMBER DATA DATABASE)
        // ==========================================================

        // A. EKSPEDISI (Logika Diskon JSON Kompleks)
        // ----------------------------------------------------------
        $diskonRules = DB::table('Ekspedisi')->whereNotNull('keyword')->get();
        $rawEkspedisi = DB::table('Pesanan')
            ->whereYear('tanggal_pesanan', $tahun)
            ->whereIn('status_pesanan', ['Selesai', 'Terkirim', 'Lunas', 'Delivered', 'Success', 'success'])
            ->select('tanggal_pesanan', 'price', 'shipping_cost', 'insurance_cost', 'expedition')
            ->get();

        foreach ($rawEkspedisi as $row) {
            $bulan = Carbon::parse($row->tanggal_pesanan)->month;
            
            // --- Mulai Logika Hitung Diskon (Sama persis dengan KeuanganController) ---
            $diskonPersen = 0;
            $expStr = strtolower($row->expedition); 
            
            foreach ($diskonRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        // 1. Cek Key Spesifik (misal: "cargo", "reg")
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2; // Keluar dari loop rules & loop diskonRules
                            }
                        }
                        // 2. Jika tidak ada key spesifik, pakai Default
                        if (isset($rules['default'])) {
                            $diskonPersen = $rules['default'];
                        }
                    }
                    break; // Keluar dari loop diskonRules jika sudah match keyword utama
                }
            }
            // --- Selesai Logika Hitung Diskon ---

            $omzet      = $row->price;
            $ongkirReal = $row->shipping_cost - ($row->shipping_cost * $diskonPersen);
            $modal      = $ongkirReal + $row->insurance_cost;

            $report[$bulan]['pendapatan']['Ekspedisi'] += $omzet;
            $report[$bulan]['hpp']['Beban Pokok Ekspedisi'] += $modal;
        }

        // B. PPOB (Markup +50 perak)
        // ----------------------------------------------------------
        $rawPPOB = DB::table('ppob_transactions')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['Success', 'Lunas', 'Berhasil', 'success'])
            ->select('created_at', 'price')
            ->get();

        foreach ($rawPPOB as $row) {
            $bulan = Carbon::parse($row->created_at)->month;
            // Logic Lama: Omzet = price + 50, Modal = price
            $report[$bulan]['pendapatan']['PPOB'] += ($row->price + 50);
            $report[$bulan]['hpp']['Beban Pokok PPOB'] += $row->price;
        }

        // C. TOP UP SALDO (Omzet = Modal, Profit 0)
        // ----------------------------------------------------------
        $rawTopUp = DB::table('transactions')
            ->where('type', 'topup')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['success', 'paid', 'lunas', 'berhasil'])
            ->select('created_at', 'amount')
            ->get();

        foreach ($rawTopUp as $row) {
            $bulan = Carbon::parse($row->created_at)->month;
            // Logic Lama: Omzet = amount, Modal = amount
            $report[$bulan]['pendapatan']['Top Up Saldo'] += $row->amount;
            $report[$bulan]['hpp']['Beban Pokok Top Up'] += $row->amount;
        }

        // D. MARKETPLACE
        // ----------------------------------------------------------
        $rawMarketplace = DB::table('order_marketplace')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['completed', 'success', 'delivered', 'selesai', 'terkirim', 'lunas'])
            ->select('created_at', 'total_amount', 'shipping_cost', 'insurance_cost')
            ->get();

        foreach ($rawMarketplace as $row) {
            $bulan = Carbon::parse($row->created_at)->month;
            // Logic Lama: Omzet = total_amount, Modal = shipping + insurance
            $report[$bulan]['pendapatan']['Marketplace'] += $row->total_amount;
            $report[$bulan]['hpp']['Beban Pokok Marketplace'] += ($row->shipping_cost + $row->insurance_cost);
        }

        // E. MANUAL (Keuangan)
        // ----------------------------------------------------------
        $rawManual = DB::table('keuangans')->whereYear('tanggal', $tahun)->get();
        $listKategoriBeban = [];

        foreach ($rawManual as $row) {
            $bulan = Carbon::parse($row->tanggal)->month;
            
            if ($row->jenis == 'Pemasukan') {
                // Masukkan ke Lain-lain agar tidak double counting dengan query otomatis di atas
                // Kecuali user memang input manual dengan kategori 'Lainnya'
                $report[$bulan]['pendapatan']['Lain-lain'] += $row->jumlah;
            } 
            elseif ($row->jenis == 'Pengeluaran') {
                // Dinamis: Menangkap kategori pengeluaran (Gaji, Sewa, Listrik, dll)
                $kategori = $row->kategori;
                
                if (!in_array($kategori, $listKategoriBeban)) {
                    $listKategoriBeban[] = $kategori;
                }

                if (!isset($report[$bulan]['beban'][$kategori])) {
                    $report[$bulan]['beban'][$kategori] = 0;
                }
                $report[$bulan]['beban'][$kategori] += $row->jumlah;
            }
        }

        // ==========================================================
        // 3. HITUNG TOTAL AKHIR (Final Calculation)
        // ==========================================================
        for ($i = 1; $i <= 12; $i++) {
            // A. Total Omzet
            $report[$i]['total_pendapatan'] = array_sum($report[$i]['pendapatan']);
            
            // B. Total HPP
            $report[$i]['total_hpp'] = array_sum($report[$i]['hpp']);
            
            // C. Laba Kotor (Omzet - HPP)
            $report[$i]['laba_kotor'] = $report[$i]['total_pendapatan'] - $report[$i]['total_hpp'];
            
            // D. Total Beban Operasional
            $totalBebanBulan = 0;
            if (!empty($report[$i]['beban'])) {
                foreach ($report[$i]['beban'] as $val) $totalBebanBulan += $val;
            }
            $report[$i]['total_beban'] = $totalBebanBulan;
            
            // E. Laba Bersih (Laba Kotor - Beban Operasional)
            $report[$i]['laba_bersih'] = $report[$i]['laba_kotor'] - $report[$i]['total_beban'];
        }

        return [
            'report' => $report,
            'months' => $months,
            'tahun' => $tahun,
            'listKategoriBeban' => $listKategoriBeban
        ];
    }

    /**
     * HALAMAN UTAMA (VIEW)
     */
    public function index(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = $this->getDataLaporan($tahun);

        return view('admin.keuangan.laba_rugi', $data);
    }

    /**
     * EXPORT EXCEL
     */
    public function exportExcel(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = $this->getDataLaporan($tahun);
        $namaFile = 'Laba_Rugi_' . $tahun . '.xlsx';

        return Excel::download(new LabaRugiExport($data), $namaFile);
    }

    /**
     * EXPORT PDF (OTOMATIS PORTRAIT / LANDSCAPE)
     */
    public function exportPdf(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        // Ambil input 'bulan'. Jika tidak ada, default ke 'all' (semua)
        $bulan = $request->input('bulan', 'all'); 

        $data = $this->getDataLaporan($tahun);

        if ($bulan !== 'all') {
            // ==========================================
            // MODE PORTRAIT: User pilih 1 Bulan Spesifik
            // ==========================================
            // Tampilan: Kolom Bulan Terpilih vs Kolom Total Tahun
            
            $data['bulanDipilih'] = $bulan; // Kirim angka bulan (1-12) ke View
            
            $pdf = Pdf::loadView('admin.keuangan.pdf_laba_rugi_ringkas', $data);
            $pdf->setPaper('a4', 'portrait'); // SET PORTRAIT
            
            $namaFile = 'Laporan_Ringkas_Bulan_' . $bulan . '_' . $tahun . '.pdf';
        } else {
            // ==========================================
            // MODE LANDSCAPE: User pilih "Semua Bulan"
            // ==========================================
            // Tampilan: Tabel Lebar Jan-Des + Total
            
            $pdf = Pdf::loadView('admin.keuangan.pdf_laba_rugi', $data);
            $pdf->setPaper('a4', 'landscape'); // SET LANDSCAPE
            
            $namaFile = 'Laporan_Detail_Tahunan_' . $tahun . '.pdf';
        }

        return $pdf->download($namaFile);
    }
}