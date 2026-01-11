<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LabaRugiController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Tahun (Default tahun ini)
        $tahun = $request->input('tahun', date('Y'));
        
        // 2. Siapkan Array Bulan (Januari - Desember)
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create()->month($m)->format('F'); // Nama Bulan
        }

        // =========================================================================
        // A. PENDAPATAN (REVENUE)
        // =========================================================================
        
        // A1. Pendapatan Ekspedisi (Total Price)
        $revEkspedisi = DB::table('Pesanan')
            ->selectRaw('MONTH(tanggal_pesanan) as bulan, SUM(price) as total')
            ->whereYear('tanggal_pesanan', $tahun)
            ->whereIn('status_pesanan', ['Selesai', 'Success', 'Lunas'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // A2. Pendapatan PPOB (Price + Margin 50 perak)
        $revPPOB = DB::table('ppob_transactions')
            ->selectRaw('MONTH(created_at) as bulan, SUM(price + 50) as total')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['Success', 'Lunas'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // A3. Pendapatan Marketplace
        $revMarketplace = DB::table('order_marketplace')
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_amount) as total')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['completed', 'success'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // A4. Pendapatan Manual (Lain-lain)
        $revLain = DB::table('keuangans')
            ->selectRaw('MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->whereYear('tanggal', $tahun)
            ->where('jenis', 'Pemasukan')
            ->whereNotIn('kategori', ['Ekspedisi', 'PPOB', 'Marketplace']) // Hindari double count
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // =========================================================================
        // B. BEBAN POKOK PENDAPATAN (HPP / COGS)
        // =========================================================================

        // B1. HPP Ekspedisi (Ongkir Real + Asuransi - Diskon)
        // Kita hitung manual modalnya seperti di KeuanganController
        // Note: Untuk performa, idealnya ini disimpan di kolom 'modal' saat transaksi terjadi.
        // Di sini saya pakai estimasi kasar query: (Shipping Cost + Insurance)
        $hppEkspedisi = DB::table('Pesanan')
            ->selectRaw('MONTH(tanggal_pesanan) as bulan, SUM(shipping_cost + insurance_cost) as total')
            ->whereYear('tanggal_pesanan', $tahun)
            ->whereIn('status_pesanan', ['Selesai', 'Success', 'Lunas'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // B2. HPP PPOB (Harga Dasar)
        $hppPPOB = DB::table('ppob_transactions')
            ->selectRaw('MONTH(created_at) as bulan, SUM(price) as total')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['Success', 'Lunas'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // B3. HPP Marketplace (Ongkir + Biaya Admin/Layanan jika ada)
        $hppMarketplace = DB::table('order_marketplace')
            ->selectRaw('MONTH(created_at) as bulan, SUM(shipping_cost + insurance_cost) as total')
            ->whereYear('created_at', $tahun)
            ->whereIn('status', ['completed', 'success'])
            ->groupBy('bulan')->pluck('total', 'bulan')->toArray();

        // =========================================================================
        // C. BEBAN OPERASIONAL (EXPENSES) - Dari Input Manual
        // =========================================================================
        
        // Ambil semua kategori pengeluaran manual
        $bebanManual = DB::table('keuangans')
            ->selectRaw('kategori, MONTH(tanggal) as bulan, SUM(jumlah) as total')
            ->whereYear('tanggal', $tahun)
            ->where('jenis', 'Pengeluaran')
            // Kecualikan jika ada kategori HPP manual, masukkan di atas. Sisanya Operasional.
            ->groupBy('kategori', 'bulan')
            ->get();

        // Mapping Beban ke Array [Kategori][Bulan]
        $listBeban = [];
        foreach($bebanManual as $b) {
            $listBeban[$b->kategori][$b->bulan] = $b->total;
        }

        // =========================================================================
        // D. DATA FINAL (STRUKTUR TABEL)
        // =========================================================================
        
        $report = [];
        
        // Loop 12 Bulan untuk menyusun kolom
        for ($i = 1; $i <= 12; $i++) {
            // 1. Pendapatan
            $p_ekspedisi = $revEkspedisi[$i] ?? 0;
            $p_ppob      = $revPPOB[$i] ?? 0;
            $p_market    = $revMarketplace[$i] ?? 0;
            $p_lain      = $revLain[$i] ?? 0;
            $total_pendapatan = $p_ekspedisi + $p_ppob + $p_market + $p_lain;

            // 2. HPP
            $h_ekspedisi = $hppEkspedisi[$i] ?? 0;
            $h_ppob      = $hppPPOB[$i] ?? 0;
            $h_market    = $hppMarketplace[$i] ?? 0;
            $total_hpp   = $h_ekspedisi + $h_ppob + $h_market;

            // 3. Laba Kotor
            $laba_kotor = $total_pendapatan - $total_hpp;

            // 4. Beban Operasional
            $total_beban = 0;
            foreach ($listBeban as $kategori => $dataBulan) {
                $total_beban += ($dataBulan[$i] ?? 0);
            }

            // 5. Laba Bersih (Net Income)
            // (Disini diasumsikan Beban Pajak & Bunga masuk di input manual kategori "Pajak" / "Bunga")
            $laba_bersih = $laba_kotor - $total_beban;

            $report[$i] = [
                'pendapatan' => [
                    'Ekspedisi' => $p_ekspedisi,
                    'PPOB' => $p_ppob,
                    'Marketplace' => $p_market,
                    'Lain-lain' => $p_lain,
                ],
                'total_pendapatan' => $total_pendapatan,
                'hpp' => [
                    'Beban Pokok Ekspedisi' => $h_ekspedisi,
                    'Beban Pokok PPOB' => $h_ppob,
                    'Beban Pokok Marketplace' => $h_market,
                ],
                'total_hpp' => $total_hpp,
                'laba_kotor' => $laba_kotor,
                'beban' => $listBeban, // Array [Kategori][Bulan]
                'total_beban' => $total_beban,
                'laba_bersih' => $laba_bersih
            ];
        }

        // List Kategori Beban Unik untuk Judul Baris
        $kategoriBeban = array_keys($listBeban);

        return view('admin.keuangan.laba_rugi', compact('report', 'months', 'tahun', 'kategoriBeban'));

    }
}