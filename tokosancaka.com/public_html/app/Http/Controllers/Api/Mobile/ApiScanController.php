<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScansExport;

class ApiScanController extends Controller
{
    /**
     * Helper Method: Mengecek apakah user saat ini adalah Admin (ID 4 atau Role 'Admin')
     */
    private function isAdmin()
    {
        $user = Auth::user();
        return ($user && ($user->id_pengguna == 4 || strtolower($user->role) === 'admin'));
    }

    /**
     * 1. Menampilkan halaman utama Riwayat Scan dengan paginasi.
     */
    public function index(Request $request)
    {
        $query = ScannedPackage::query();

        // Jika BUKAN Admin, batasi data hanya milik user tersebut
        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        // Logika untuk pencarian
        if ($request->has('search')) {
            $query->where('resi_number', 'like', '%' . $request->search . '%');
        }

        $scans = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $scans
        ]);
    }

    /**
     * 2. Init data untuk scanner SPX Mobile.
     */
    public function initMobile()
    {
        $customer = Auth::user();
        $todays_scans = $this->getTodaysScans();

        return response()->json([
            'success' => true,
            'data' => [
                'customer_name' => $customer->nama_lengkap,
                'customer_phone' => $customer->no_hp ?? '',
                'saldo' => $customer->saldo,
                'saldo_format' => number_format($customer->saldo, 0, ',', '.'),
                'todays_count' => $todays_scans->count(),
                'recent_scans' => $todays_scans,
                'is_admin' => $this->isAdmin() // Mengirim flag ke frontend jika butuh
            ]
        ]);
    }

    /**
     * 3. Menyimpan data resi yang baru di-scan dengan LOGIKA PEMOTONGAN SALDO.
     */
    public function storeSpxScan(Request $request)
    {
        $request->validate(['resi_number' => 'required|string|unique:scanned_packages,resi_number|max:255']);

        $customer = Auth::user();
        $biayaScan = 1000; // Biaya per scan

        // 1. CEK SALDO DULU (Bahkan Admin pun tetap di cek saldonya jika sistem mengharuskan, atau Bapak bisa by-pass Admin di sini)
        if ($customer->saldo < $biayaScan) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo tidak mencukupi! Sisa saldo Anda: Rp ' . number_format($customer->saldo, 0, ',', '.'),
                'type'    => 'error'
            ], 400);
        }

        $resi = $request->input('resi_number');
        $package = null;

        // 2. GUNAKAN DATABASE TRANSACTION
        try {
            DB::transaction(function () use ($customer, $resi, $biayaScan, &$package) {

                // Potong Saldo
                $customer->decrement('saldo', $biayaScan);

                // Simpan Resi
                $package = ScannedPackage::create([
                    'user_id' => $customer->id_pengguna,
                    'resi_number' => $resi,
                    'status' => 'Proses Pickup',
                ]);
            });

            // Jika transaksi berhasil, lanjut kirim notif & response
            $message = $customer->nama_lengkap . ' telah scan resi baru: ' . $resi;
            $url = route('admin.spx_scans.index', ['search' => $resi]);

            // Trigger Event Notifikasi
            event(new AdminNotificationEvent('Paket SPX Baru Di-scan!', $message, $url));

            $todays_scans = $this->getTodaysScans();

            return response()->json([
                'success' => true,
                'message' => 'Resi berhasil didaftarkan! Saldo terpotong Rp ' . number_format($biayaScan, 0, ',', '.'),
                'data' => [
                    'current_saldo' => number_format($customer->fresh()->saldo, 0, ',', '.'),
                    'package' => $package,
                    'todays_count' => $todays_scans->count(),
                    'recent_scans' => $todays_scans
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses saldo. Silakan coba lagi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * 4. Membuat surat jalan.
     */
    public function createSuratJalan(Request $request)
    {
        $validated = $request->validate(['resi_list' => 'required|array|min:1']);

        $customer = Auth::user();
        $resiList = $validated['resi_list'];
        $kodeUnik = 'SJ-' . strtoupper(Str::random(8));

        $suratJalan = SuratJalan::create([
            'user_id' => $customer->id_pengguna,
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($resiList),
        ]);

        $queryUpdate = ScannedPackage::whereIn('resi_number', $resiList);

        // Admin bisa membuat Surat Jalan dari paket siapa saja yang di-scan,
        // selain Admin hanya bisa memaketkan resi miliknya.
        if (!$this->isAdmin()) {
            $queryUpdate->where('user_id', $customer->id_pengguna);
        }

        $queryUpdate->update(['surat_jalan_id' => $suratJalan->id]);

        $message = $customer->nama_lengkap . ' telah membuat Surat Jalan baru.';
        $url = route('admin.spx_scans.index', ['search' => $kodeUnik]);
        event(new AdminNotificationEvent('Surat Jalan Baru Dibuat!', $message, $url));

        return response()->json([
            'success' => true,
            'message' => 'Surat Jalan berhasil dibuat!',
            'data' => [
                'pdf_url' => route('api.suratjalan.download', ['kode_surat_jalan' => $kodeUnik]),
                'customer_name' => $customer->nama_lengkap,
                'package_count' => $suratJalan->jumlah_paket,
                'surat_jalan_code' => $suratJalan->kode_surat_jalan,
            ]
        ]);
    }

    /**
     * 5. Mengunduh Surat Jalan dalam format PDF.
     */
    public function downloadSuratJalan($kode_surat_jalan)
    {
        $querySJ = SuratJalan::where('kode_surat_jalan', $kode_surat_jalan);

        if (!$this->isAdmin()) {
            $querySJ->where('user_id', Auth::user()->id_pengguna);
        }

        $suratJalan = $querySJ->firstOrFail();
        $scans = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        $customer = Auth::user();

        $pdf = Pdf::loadView('customer.scan.surat-jalan-pdf', compact('suratJalan', 'scans', 'customer'));
        return $pdf->download('surat-jalan-' . $kode_surat_jalan . '.pdf');
    }

    /**
     * 6. Mengambil data riwayat scan untuk filter.
     */
    public function getHistory(Request $request)
    {
        $request->validate(['period' => 'required|string|in:today,7days,14days,30days,lastmonth']);

        $query = ScannedPackage::query();

        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        switch ($request->input('period')) {
            case 'today': $query->whereDate('created_at', Carbon::today()); break;
            case '7days': $query->where('created_at', '>=', Carbon::now()->subDays(7)); break;
            case '14days': $query->where('created_at', '>=', Carbon::now()->subDays(14)); break;
            case '30days': $query->where('created_at', '>=', Carbon::now()->subDays(30)); break;
            case 'lastmonth': $query->whereMonth('created_at', Carbon::now()->subMonth()->month); break;
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get()
        ]);
    }

    /**
     * 7. Memperbarui data scan di database (Edit -> Update jadi 1 API).
     */
    public function update(Request $request, $resi_number)
    {
        $validated = $request->validate(['status' => 'required|string|max:255']);

        $query = ScannedPackage::where('resi_number', $resi_number);

        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        $scan = $query->firstOrFail();
        $scan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status resi berhasil diperbarui.',
            'data' => $scan
        ]);
    }

    /**
     * 8. Menghapus data scan dari database.
     */
    public function destroy($resi_number)
    {
        $query = ScannedPackage::where('resi_number', $resi_number);

        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        $scan = $query->firstOrFail();
        $scan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data scan berhasil dihapus.'
        ]);
    }

    /**
     * 9. Mengekspor data riwayat scan ke PDF.
     */
    public function exportPdf()
    {
        $query = ScannedPackage::query();

        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        $scans = $query->latest()->get();
        $pdf = Pdf::loadView('customer.scan.pdf', compact('scans'));
        return $pdf->download('riwayat-scan.pdf');
    }

    /**
     * 10. Mengekspor data riwayat scan ke Excel.
     */
    public function exportExcel()
    {
        // Jika export logic bergantung pada ID, pastikan Anda juga menyesuaikan logic di dalam class ScansExport
        $userId = $this->isAdmin() ? null : Auth::user()->id_pengguna;

        // Kita kirim $userId (Bisa null jika admin). Jangan lupa ubah constructor di app/Exports/ScansExport.php agar menerima null
        return Excel::download(new ScansExport($userId), 'riwayat-scan.xlsx');
    }

    /**
     * Helper method untuk mengambil data scan hari ini
     * HANYA YANG BELUM DICETAK SURAT JALANNYA.
     */
    private function getTodaysScans()
    {
        $query = ScannedPackage::whereDate('created_at', today())
                               ->whereNull('surat_jalan_id');

        // Batasi untuk non-admin
        if (!$this->isAdmin()) {
            $query->where('user_id', Auth::user()->id_pengguna);
        }

        return $query->latest()->get();
    }
}
