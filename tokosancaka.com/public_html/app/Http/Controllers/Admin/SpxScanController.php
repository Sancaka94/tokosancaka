<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScannedPackage;
use App\Models\User;
use App\Models\SuratJalan;
use App\Exports\SpxScansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Events\SuratJalanCreated;

class SpxScanController extends Controller
{
    // METHOD 'show()' YANG SALAH SUDAH DIHAPUS

    /**
     * Menampilkan daftar semua data SPX Scan dengan filter dan pencarian.
     */
    public function index(Request $request)
    {
        $query = ScannedPackage::with('user', 'suratJalan')->latest();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where('resi_number', 'like', "%{$search}%")
              ->orWhereHas('user', function ($subq) use ($search) {
                  $subq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('no_wa', 'like', "%{$search}%");
              });
        });
        
        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        $scans = $query->paginate(20)->withQueryString();

        return view('admin.spx_scans.index', compact('scans'));
    }

    /**
     * Menampilkan halaman scanner SPX untuk admin.
     */
    public function create()
    {
        $customers = User::where('role', 'Pelanggan')->orderBy('nama_lengkap')->get();
        return view('admin.spx_scans.create', compact('customers'));
    }
    
    
    /**
     * Mengunduh Surat Jalan sebagai PDF.
     */
    public function downloadSuratJalan($kode_surat_jalan)
    {
        $suratJalan = SuratJalan::with('user', 'packages')
                                ->where('kode_surat_jalan', $kode_surat_jalan)
                                ->firstOrFail();

        $pdf = Pdf::loadView('admin.spx_scans.pdf-surat-jalan', compact('suratJalan'));
        return $pdf->download('surat-jalan-' . $suratJalan->kode_surat_jalan . '.pdf');
    }


    /**
     * Menyimpan data scan yang diinput oleh admin atas nama pelanggan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:Pengguna,id_pengguna',
            'resi_number' => 'required|string|max:255',
        ]);

        $existingScan = ScannedPackage::where('resi_number', $validated['resi_number'])->first();

        if ($existingScan) {
            return response()->json(['success' => false, 'message' => 'Resi ini sudah pernah di-scan sebelumnya.'], 422);
        }

        $package = ScannedPackage::create([
            'user_id' => $validated['user_id'],
            'resi_number' => $validated['resi_number'],
            'status' => 'Proses Pickup',
        ]);

        $todays_scans = ScannedPackage::where('user_id', $validated['user_id'])
                                            ->whereDate('created_at', today())
                                            ->latest()
                                            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil didaftarkan!',
            'todays_count' => $todays_scans->count(),
            'recent_scans_html' => view('admin.spx_scans.partials.recent-scans', ['scans' => $todays_scans])->render(),
        ]);
    }

    /**
     * Menampilkan form untuk mengedit data scan.
     */
    public function edit(ScannedPackage $spx_scan)
    {
        $users = User::where('role', 'Pelanggan')->orderBy('nama_lengkap')->get(); 
        return view('admin.spx_scans.edit', compact('spx_scan', 'users'));
    }

    /**
     * Memperbarui data scan di database.
     */
    public function update(Request $request, ScannedPackage $spx_scan)
    {
        $validated = $request->validate([
            'resi_number' => 'required|string|max:255|unique:scanned_packages,resi_number,' . $spx_scan->id,
            'user_id' => 'required|exists:Pengguna,id_pengguna',
            'status' => 'required|string|max:255',
        ]);

        $spx_scan->update($validated);

        return redirect()->route('admin.spx_scans.index')->with('success', 'Data scan berhasil diperbarui.');
    }

    /**
     * Menghapus data scan dari database.
     */
    public function destroy(ScannedPackage $spx_scan)
    {
        $spx_scan->delete();
        return redirect()->route('admin.spx_scans.index')->with('success', 'Data scan berhasil dihapus.');
    }

    /**
     * Memperbarui status menjadi 'Diterima Sancaka'.
     */
    public function updateStatus(ScannedPackage $spx_scan)
    {
        if ($spx_scan->status === 'Proses Pickup' || $spx_scan->status === null) {
            $spx_scan->status = 'Diterima Sancaka';
            $spx_scan->save();
            return back()->with('success', "Status resi {$spx_scan->resi_number} berhasil diperbarui.");
        }
        return back()->with('error', "Status resi {$spx_scan->resi_number} tidak dapat diubah atau sudah diproses.");
    }

    /**
     * Mengambil data scan hari ini untuk pelanggan tertentu via AJAX.
     */
    public function getTodaysScansForCustomer(User $customer)
    {
        $todays_scans = ScannedPackage::where('user_id', $customer->id_pengguna)
                                            ->whereDate('created_at', today())
                                            ->latest()
                                            ->get();

        return response()->json([
            'todays_count' => $todays_scans->count(),
            'recent_scans_html' => view('admin.spx_scans.partials.recent-scans', ['scans' => $todays_scans])->render(),
        ]);
    }

    /**
     * Membuat surat jalan untuk pelanggan yang dipilih.
     */
    public function createSuratJalan(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:Pengguna,id_pengguna',
            'resi_list' => 'required|array|min:1',
        ]);

        $customer = User::find($validated['user_id']);
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
                        
        $user = auth()->user(); 
        broadcast(new SuratJalanCreated($suratJalan, $user))->toOthers();


        return response()->json([
            'success' => true,
            'message' => 'Surat Jalan berhasil dibuat!',
            'pdf_url' => route('admin.suratjalan.download', ['kode_surat_jalan' => $kodeUnik]),
            'customer_name' => $customer->nama_lengkap,
            'customer_phone' => $customer->no_wa,
            'package_count' => $suratJalan->jumlah_paket,
            'surat_jalan_code' => $kodeUnik,
        ]);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new SpxScansExport($request->all()), 'data-spx_scans.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = ScannedPackage::with('user')->latest();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where('resi_number', 'like', "%{$search}%")
              ->orWhereHas('user', function ($subq) use ($search) {
                  $subq->where('nama_lengkap', 'like', "%{$search}%");
              });
        });
        
        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $q->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        });

        $scans = $query->get();
        $pdf = Pdf::loadView('admin.spx_scans.pdf', compact('scans'));
        return $pdf->download('data-spx-scans-' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Menampilkan halaman monitoring surat jalan dengan filter.
     */
    public function showMonitorPage(Request $request)
    {
        // Mengatur locale Carbon ke Bahasa Indonesia agar format tanggal benar
        Carbon::setLocale('id');

        // Mulai query ke model SuratJalan dengan relasi user
        $query = SuratJalan::with(['user', 'kontak'])->latest();

        // Terapkan filter pencarian jika ada
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where('kode_surat_jalan', 'like', "%{$search}%")
              ->orWhereHas('user', function ($subq) use ($search) {
                  $subq->where('nama_lengkap', 'like', "%{$search}%");
              })
              ->orWhereHas('kontak', function ($subq) use ($search) {
                  $subq->where('nama', 'like', "%{$search}%");
              });
        });
        
        // Terapkan filter rentang tanggal jika ada
        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        // Ambil data dengan pagination
        $suratJalans = $query->paginate(15)->withQueryString();

        // Kembalikan view dengan data yang sudah difilter
        return view('admin.spx_scans.monitor', compact('suratJalans'));
    }

    /**
     * Menangani export PDF dari halaman monitoring.
     */
    public function exportMonitorPdf(Request $request)
    {
        // Logika query sama seperti showMonitorPage, tetapi tanpa pagination
        $query = SuratJalan::with(['user', 'kontak'])->latest();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where('kode_surat_jalan', 'like', "%{$search}%")
              ->orWhereHas('user', function ($subq) use ($search) {
                  $subq->where('nama_lengkap', 'like', "%{$search}%");
              })
              ->orWhereHas('kontak', function ($subq) use ($search) {
                  $subq->where('nama', 'like', "%{$search}%");
              });
        });
        
        $query->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        });

        $suratJalans = $query->get();
        
        $fileName = 'laporan-surat-jalan-' . now()->format('Y-m-d') . '.pdf';
        
        // Anda perlu membuat view blade baru untuk template PDF ini
        $pdf = Pdf::loadView('admin.spx_scans.pdf.monitor-export', compact('suratJalans')); 
        
        return $pdf->download($fileName);
        
        // BARIS YANG SALAH SUDAH DIHAPUS DARI SINI
    }

    /**
     * [BARU] Mengambil data surat jalan hari ini untuk notifikasi real-time.
     */
    public function todays_data()
    {
        try {
            $total = SuratJalan::whereDate('created_at', today())->count();

            $latest = SuratJalan::with(['user', 'kontak'])
                ->whereDate('created_at', today())
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($sj) {
                    return [
                        'kode_surat_jalan' => $sj->kode_surat_jalan,
                        // Menggunakan null coalescing operator untuk keamanan
                        'user_name' => $sj->user->nama_lengkap ?? $sj->kontak->nama ?? 'N/A',
                        'jumlah_paket' => $sj->jumlah_paket,
                        'time' => $sj->created_at->format('H:i'),
                    ];
                });

            return response()->json([
                'total' => $total,
                'latest' => $latest,
            ]);

        } catch (\Exception $e) {
            // Mengirim pesan error jika terjadi masalah
            return response()->json(['message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }
}
