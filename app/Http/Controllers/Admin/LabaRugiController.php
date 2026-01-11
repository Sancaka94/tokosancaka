<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\LabaRugiExport;     // Pastikan buat file Export nanti
use Maatwebsite\Excel\Facades\Excel; // Library Excel
use Barryvdh\DomPDF\Facade\Pdf;      // Library PDF

class LabaRugiController extends Controller
{
    /**
     * FUNGSI PRIVATE: PENGOLAH DATA PUSAT
     * (Digunakan oleh View HTML, Excel, dan PDF agar angkanya konsisten)
     */
    private function getDataLaporan($tahun)
    {
        // 1. Siapkan Array Struktur Data
        $months = [];
        $report = [];
        
        for ($m = 1; $m <= 12; $m++) {
            $namaBulan = Carbon::create()->month($m)->locale('id')->format('F');
            $months[$m] = $namaBulan;

            $report[$m] = [
                'pendapatan' => ['Ekspedisi' => 0, 'PPOB' => 0, 'Marketplace' => 0, 'Lain-lain' => 0],
                'total_pendapatan' => 0,
                'hpp' => ['Beban Pokok Ekspedisi' => 0, 'Beban Pokok PPOB' => 0, 'Beban Pokok Marketplace' => 0],
                'total_hpp' => 0,
                'laba_kotor' => 0,
                'beban' => [],
                'total_beban' => 0,
                'laba_bersih' => 0
            ];
        }

        // 2. LOGIKA HITUNGAN (SAMA PERSIS DENGAN SEBELUMNYA)
        
        // --- EKSPEDISI (Hitung Diskon via PHP) ---
        $diskonRules = DB::table('Ekspedisi')->whereNotNull('keyword')->get();
        $rawEkspedisi = DB::table('Pesanan')
            ->whereYear('tanggal_pesanan', $tahun)
            ->whereIn('status_pesanan', ['Selesai', 'Terkirim', 'Lunas', 'Delivered', 'Success', 'success'])
            ->select('tanggal_pesanan', 'price', 'shipping_cost', 'insurance_cost', 'expedition')
            ->get();

        foreach ($rawEkspedisi as $row) {
            $bulan = Carbon::parse($row->tanggal_pesanan)->month;
            
            // Logika Diskon
            $diskonPersen = 0;
            $expStr = strtolower($row->expedition); 
            foreach ($diskonRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2; 
                            }
                        }
                        if (isset($rules['default'])) $diskonPersen = $rules['default'];
                    }
                    break;
                }
            }

            $omzet = $row->price;
            $ongkirReal = $row->shipping_cost - ($row->shipping_cost * $diskonPersen);
            $modal = $ongkirReal + $row->insurance_cost;

            $report[$bulan]['pendapatan']['Ekspedisi'] += $omzet;
            $report[$bulan]['hpp']['Beban Pokok Ekspedisi'] += $modal;
        }

        // --- PPOB ---
        $rawPPOB = DB::table('ppob_transactions')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['Success', 'Lunas', 'Berhasil', 'success'])
            ->select('created_at', 'price')
            ->get();

        foreach ($rawPPOB as $row) {
            $bulan = Carbon::parse($row->created_at)->month;
            $report[$bulan]['pendapatan']['PPOB'] += ($row->price + 50);
            $report[$bulan]['hpp']['Beban Pokok PPOB'] += $row->price;
        }

        // --- MARKETPLACE ---
        $rawMarketplace = DB::table('order_marketplace')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['completed', 'success', 'delivered', 'selesai', 'terkirim', 'lunas'])
            ->select('created_at', 'total_amount', 'shipping_cost', 'insurance_cost')
            ->get();

        foreach ($rawMarketplace as $row) {
            $bulan = Carbon::parse($row->created_at)->month;
            $report[$bulan]['pendapatan']['Marketplace'] += $row->total_amount;
            $report[$bulan]['hpp']['Beban Pokok Marketplace'] += ($row->shipping_cost + $row->insurance_cost);
        }

        // --- MANUAL ---
        $rawManual = DB::table('keuangans')->whereYear('tanggal', $tahun)->get();
        $listKategoriBeban = [];

        foreach ($rawManual as $row) {
            $bulan = Carbon::parse($row->tanggal)->month;
            if ($row->jenis == 'Pemasukan') {
                if (!in_array($row->kategori, ['Ekspedisi', 'PPOB', 'Marketplace'])) {
                    $report[$bulan]['pendapatan']['Lain-lain'] += $row->jumlah;
                }
            } elseif ($row->jenis == 'Pengeluaran') {
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

        // 3. HITUNG TOTAL AKHIR
        for ($i = 1; $i <= 12; $i++) {
            $report[$i]['total_pendapatan'] = array_sum($report[$i]['pendapatan']);
            $report[$i]['total_hpp'] = array_sum($report[$i]['hpp']);
            $report[$i]['laba_kotor'] = $report[$i]['total_pendapatan'] - $report[$i]['total_hpp'];
            
            $totalBebanBulan = 0;
            if (!empty($report[$i]['beban'])) {
                foreach ($report[$i]['beban'] as $val) $totalBebanBulan += $val;
            }
            $report[$i]['total_beban'] = $totalBebanBulan;
            $report[$i]['laba_bersih'] = $report[$i]['laba_kotor'] - $report[$i]['total_beban'];
        }

        // Return Data Array
        return [
            'report' => $report,
            'months' => $months,
            'tahun' => $tahun,
            'listKategoriBeban' => $listKategoriBeban
        ];
    }

    // ==========================================================
    // 1. TAMPILAN WEBSITE (INDEX)
    // ==========================================================
    public function index(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = $this->getDataLaporan($tahun); // Panggil fungsi pusat

        return view('admin.keuangan.laba_rugi', $data);
    }

    // ==========================================================
    // 2. EXPORT EXCEL
    // ==========================================================
    public function exportExcel(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = $this->getDataLaporan($tahun);

        $namaFile = 'Laba_Rugi_Tahun_' . $tahun . '_' . date('YmdHis') . '.xlsx';

        // Menggunakan Class Export terpisah (Lihat langkah 2 dibawah)
        return Excel::download(new LabaRugiExport($data), $namaFile);
    }

    // ==========================================================
    // 3. EXPORT PDF
    // ==========================================================
    public function exportPdf(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $data = $this->getDataLaporan($tahun);

        // Kita gunakan view yang sama, tapi diload oleh DomPDF
        $pdf = Pdf::loadView('admin.keuangan.laba_rugi', $data);
        
        // Atur Kertas Landscape agar tabel lebar muat
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Laba_Rugi_Tahun_' . $tahun . '.pdf');
    }
}