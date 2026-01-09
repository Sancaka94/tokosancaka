<?php

// Namespace ini harus cocok dengan path folder file.
namespace App\Http\Controllers\Customer;

// Baris 'use' ini memastikan kita menggunakan base Controller yang benar dari Laravel.
use App\Http\Controllers\Controller;
use App\Models\Ekspedisi;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Exception;

/**
 * Controller DashboardController
 * Bertanggung jawab untuk menangani semua logika yang berkaitan dengan
 * halaman dashboard admin, termasuk pengambilan data statistik dan rekap.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama dashboard admin.
     *
     * Metode ini mengambil, memproses, dan menyusun semua data yang diperlukan
     * untuk ditampilkan di dashboard, lalu mengirimkannya ke view.
     *
     * @return View
     */
    public function index(): View
    {
        try {
            // --- 1. PENGAMBILAN DATA STATISTIK UTAMA (KPI) ---
            // Mengambil data Key Performance Indicator (KPI) dari beberapa tabel.
            $infoBoxes = $this->getKpiData();

            // --- 2. PENGAMBILAN DATA REKAP EKSPEDISI ---
            // Mengambil data rekapitulasi untuk setiap ekspedisi.
            $rekapEkspedisi = $this->getExpeditionRecap();

            // --- 3. PERSIAPAN DATA UNTUK GRAFIK ---
            // Mengubah data rekap menjadi format yang siap digunakan oleh library Chart.js.
            $chartData = [
                'labels' => $rekapEkspedisi->pluck('nama')->toArray(),
                'data'   => $rekapEkspedisi->pluck('order')->toArray(),
            ];

            // --- 4. PROSES NOTIFIKASI ---
            // Memeriksa adanya pendaftaran atau pesanan baru dan menyiapkan pesan notifikasi.
            $this->prepareNotifications();

            // --- 5. MENGIRIM SEMUA DATA KE VIEW ---
            // Mengirim semua variabel yang sudah terstruktur ke view 'admin.dashboard'.
            // compact() adalah cara singkat untuk membuat array ['infoBoxes' => $infoBoxes, ...]
            return view('admin.dashboard', compact('infoBoxes', 'rekapEkspedisi', 'chartData'));

        } catch (Exception $e) {
            // Penanganan error jika terjadi masalah saat query database.
            // Ini akan menghentikan eksekusi dan menampilkan pesan error yang jelas.
            report($e); // Melaporkan error ke sistem logging Laravel (storage/logs/laravel.log)
            abort(500, "Tidak dapat memuat data dashboard. Terjadi kesalahan pada server.");
        }
    }

    /**
     * Mengambil data statistik utama (KPI) untuk info boxes.
     *
     * @return array
     */
    private function getKpiData(): array
    {
        // Pastikan nama model dan kolom sesuai dengan database Anda.
        return [
            'total_pesanan'   => Pesanan::count(),
            'total_pemasukan' => Pesanan::where('status_pembayaran', 'lunas')->sum('total_harga'),
            'total_pengguna'  => User::where('role', 'customer')->count(),
            'total_kurir'     => User::where('role', 'kurir')->count(),
        ];
    }

    /**
     * Mengambil data rekapitulasi performa untuk setiap ekspedisi.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getExpeditionRecap()
    {
        // Query ini menggunakan leftJoin agar ekspedisi tanpa pesanan tetap muncul.
        return Ekspedisi::select(
                'ekspedisis.nama',
                'ekspedisis.logo',
                DB::raw('COUNT(DISTINCT pesanans.user_id) as pelanggan'),
                DB::raw('COUNT(pesanans.id) as `order`'),
                DB::raw('COALESCE(SUM(pesanans.profit), 0) as profit')
            )
            ->leftJoin('pesanans', 'ekspedisis.id', '=', 'pesanans.ekspedisi_id')
            ->groupBy('ekspedisis.id', 'ekspedisis.nama', 'ekspedisis.logo')
            ->orderByDesc('order')
            ->get();
    }

    /**
     * Memeriksa data baru dan menyiapkan notifikasi flash session.
     *
     * @return void
     */
    private function prepareNotifications(): void
    {
        $pendaftaranBaruCount = User::where('status', 'pending')->count();
        $pesananBaruCount = Pesanan::where('status', 'baru')->count();

        $daftarPesan = [];
        if ($pendaftaranBaruCount > 0) {
            $daftarPesan[] = "Ada <b>{$pendaftaranBaruCount} pendaftaran baru</b> yang menunggu persetujuan.";
        }
        if ($pesananBaruCount > 0) {
            $daftarPesan[] = "Ada <b>{$pesananBaruCount} pesanan baru</b> yang telah masuk.";
        }

        // session()->flash() akan membuat notifikasi hanya tampil sekali.
        if (!empty($daftarPesan)) {
            session()->flash('dashboard_alert', implode('<br>', $daftarPesan));
        }
    }
}
