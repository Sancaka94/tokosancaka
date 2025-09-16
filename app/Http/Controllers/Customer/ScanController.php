<?php

namespace App\Http\Controllers\Customer;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScansExport; // Pastikan Anda membuat file Export ini

class ScanController extends Controller
{
    /**
     * Menampilkan halaman utama Riwayat Scan dengan paginasi.
     */
    public function index(Request $request)
    {
        $query = ScannedPackage::where('user_id', Auth::user()->id_pengguna);

        // Logika untuk pencarian
        if ($request->has('search')) {
            $query->where('resi_number', 'like', '%' . $request->search . '%');
        }

        $scans = $query->latest()->paginate(20)->withQueryString();

        return view('customer.scan.index', compact('scans'));
    }

    /**
     * Menampilkan halaman scanner SPX.
     */
    public function showSpxScanner()
    {
        // ✅ PERBAIKAN: Menambahkan data customer yang hilang
        $customer = Auth::user();
        $todays_scans = $this->getTodaysScans();
        return view('customer.scan.spx', compact('todays_scans', 'customer'));
    }

    /**
     * Menyimpan data resi yang baru di-scan.
     */
    public function storeSpxScan(Request $request)
    {
        $request->validate(['resi_number' => 'required|string|unique:scanned_packages,resi_number|max:255']);
        
        $customer = Auth::user();
        $resi = $request->input('resi_number');

        $package = ScannedPackage::create([
            'user_id' => $customer->id_pengguna,
            'resi_number' => $resi,
            'status' => 'Proses Pickup',
        ]);

        $message = $customer->nama_lengkap . ' telah scan resi baru: ' . $resi;
        $url = route('admin.spx_scans.index', ['search' => $resi]);
        event(new AdminNotificationEvent('Paket SPX Baru Di-scan!', $message, $url));

        $todays_scans = $this->getTodaysScans();

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil didaftarkan!',
            'package' => $package,
            'todays_count' => $todays_scans->count(),
            'recent_scans_html' => view('customer.partials.recent-scans', ['scans' => $todays_scans])->render(),
        ]);
    }

     /**
     * Membuat surat jalan.
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

        ScannedPackage::whereIn('resi_number', $resiList)
                      ->where('user_id', $customer->id_pengguna)
                      ->update(['surat_jalan_id' => $suratJalan->id]);

        $message = $customer->nama_lengkap . ' telah membuat Surat Jalan baru.';
        $url = route('admin.spx_scans.index', ['search' => $kodeUnik]);
        event(new AdminNotificationEvent('Surat Jalan Baru Dibuat!', $message, $url));

        // =======================================================================
        // PERBAIKAN: Menambahkan data lengkap ke respons JSON
        // =======================================================================
        return response()->json([
            'success' => true,
            'message' => 'Surat Jalan berhasil dibuat!',
            'pdf_url' => route('customer.suratjalan.download', ['kode_surat_jalan' => $kodeUnik]),
            'customer_name' => $customer->nama_lengkap,
            'package_count' => $suratJalan->jumlah_paket,
            'surat_jalan_code' => $suratJalan->kode_surat_jalan,
        ]);
        // =======================================================================
        // AKHIR PERBAIKAN
        // =======================================================================
    }


    /**
     * Mengunduh Surat Jalan dalam format PDF.
     */
    public function downloadSuratJalan($kode_surat_jalan)
    {
        $suratJalan = SuratJalan::where('kode_surat_jalan', $kode_surat_jalan)
                                ->where('user_id', Auth::user()->id_pengguna)
                                ->firstOrFail();

        $scans = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        $customer = Auth::user();

        $pdf = Pdf::loadView('customer.scan.surat-jalan-pdf', compact('suratJalan', 'scans', 'customer'));
        return $pdf->download('surat-jalan-' . $kode_surat_jalan . '.pdf');
    }

    /**
     * Mengambil data riwayat scan untuk filter AJAX.
     */
    public function getHistory(Request $request)
    {
        $request->validate(['period' => 'required|string|in:today,7days,14days,30days,lastmonth']);

        $query = ScannedPackage::where('user_id', Auth::user()->id_pengguna);

        switch ($request->input('period')) {
            case 'today': $query->whereDate('created_at', Carbon::today()); break;
            case '7days': $query->where('created_at', '>=', Carbon::now()->subDays(7)); break;
            case '14days': $query->where('created_at', '>=', Carbon::now()->subDays(14)); break;
            case '30days': $query->where('created_at', '>=', Carbon::now()->subDays(30)); break;
            case 'lastmonth': $query->whereMonth('created_at', Carbon::now()->subMonth()->month); break;
        }

        return response()->json(['scans' => $query->latest()->get()]);
    }

    /**
     * Menampilkan form untuk mengedit data scan.
     */
    public function edit($resi_number)
    {
        $scan = ScannedPackage::where('resi_number', $resi_number)
                              ->where('user_id', Auth::user()->id_pengguna)
                              ->firstOrFail();
        
        return view('customer.scan.edit', compact('scan'));
    }

    /**
     * Memperbarui data scan di database.
     */
    public function update(Request $request, $resi_number)
    {
        $validated = $request->validate(['status' => 'required|string|max:255']);

        $scan = ScannedPackage::where('resi_number', $resi_number)
                              ->where('user_id', Auth::user()->id_pengguna)
                              ->firstOrFail();
        
        $scan->update($validated);

        return redirect()->route('customer.scan.index')->with('success', 'Status resi berhasil diperbarui.');
    }

    /**
     * Menghapus data scan dari database.
     */
    public function destroy($resi_number)
    {
        $scan = ScannedPackage::where('resi_number', $resi_number)
                              ->where('user_id', Auth::user()->id_pengguna)
                              ->firstOrFail();
        
        $scan->delete();

        return redirect()->route('customer.scan.index')->with('success', 'Data scan berhasil dihapus.');
    }

    /**
     * Mengekspor data riwayat scan ke PDF.
     */
    public function exportPdf()
    {
        $scans = ScannedPackage::where('user_id', Auth::user()->id_pengguna)->latest()->get();
        $pdf = Pdf::loadView('customer.scan.pdf', compact('scans'));
        return $pdf->download('riwayat-scan.pdf');
    }

    /**
     * Mengekspor data riwayat scan ke Excel.
     */
    public function exportExcel()
    {
        return Excel::download(new ScansExport(Auth::user()->id_pengguna), 'riwayat-scan.xlsx');
    }

    /**
     * Helper method untuk mengambil data scan hari ini.
     */
    private function getTodaysScans()
    {
        return ScannedPackage::where('user_id', Auth::user()->id_pengguna)
                             ->whereDate('created_at', today())
                             ->latest()
                             ->get();
    }
}
