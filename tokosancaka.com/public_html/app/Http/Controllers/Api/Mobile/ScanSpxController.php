<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\AdminNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use App\Models\Kontak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScansExport;

class ScanSpxController extends Controller
{
    /**
     * Helper Method: Cek apakah user adalah Admin
     */
    private function isAdmin()
    {
        $user = Auth::user();
        return ($user && ($user->id_pengguna == 4 || strtolower($user->role) === 'admin'));
    }

    /**
     * Helper Method: Menerapkan Filter "JOIN" Otomatis
     */
    private function applyUserFilter($query)
    {
        if ($this->isAdmin()) {
            return $query; // Admin bebas melihat semua
        }

        $user = Auth::user();

        // PENCEGAH ERROR: Jika dibuka via browser eksternal (tanpa login app), tolak aksesnya
        if (!$user) {
            $query->where('id', -1);
            return $query;
        }

        $userId = $user->id_pengguna;

        // Ambil semua ID Kontak yang user_id-nya adalah milik user ini
        $kontakIds = Kontak::where('user_id', $userId)->pluck('id')->toArray();

        // Filter: Ambil yang user_id-nya cocok, ATAU kontak_id-nya ada di dalam daftar kontak miliknya
        $query->where(function($q) use ($userId, $kontakIds) {
            $q->where('user_id', $userId);

            if (!empty($kontakIds)) {
                $q->orWhereIn('kontak_id', $kontakIds);
            }
        });

        return $query;
    }

    /**
     * 1. Menampilkan halaman utama Riwayat Scan dengan paginasi.
     */
    public function index(Request $request)
    {
        $query = ScannedPackage::with(['kontak']);

        $this->applyUserFilter($query); // Terapkan Filter Join

        if ($request->has('search')) {
            $query->where('resi_number', 'like', '%' . $request->search . '%');
        }

        $now = Carbon::now();
        if ($request->has('filter_waktu')) {
            $filterWaktu = $request->query('filter_waktu');

            if ($filterWaktu === 'Hari Ini') {
                $query->whereDate('created_at', $now->toDateString());
            } elseif ($filterWaktu === 'Bulan Ini') {
                $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
            } elseif ($filterWaktu === 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month);
            } elseif ($filterWaktu === 'Tahun Ini') {
                $query->whereYear('created_at', $now->year);
            }
        }

        $scans = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $scans
        ]);
    }

    /**
     * 2. Init data awal untuk halaman scanner SPX Mobile.
     */
    public function initMobile()
    {
        $customer = Auth::user();
        $todays_scans = $this->getTodaysScans();

        return response()->json([
            'success' => true,
            'data' => [
                'customer_name' => $customer->nama_lengkap,
                'customer_phone' => $customer->no_wa ?? $customer->no_hp ?? '',
                'saldo' => $customer->saldo,
                'saldo_format' => number_format($customer->saldo, 0, ',', '.'),
                'todays_count' => $todays_scans->count(),
                'recent_scans' => $todays_scans,
                'is_admin' => $this->isAdmin()
            ]
        ]);
    }

    /**
     * 3. Menyimpan data resi yang baru di-scan.
     */
    public function storeSpxScan(Request $request)
    {
        $request->validate([
            'resi_number' => 'required|string|unique:scanned_packages,resi_number|max:255'
        ]);

        $customer = Auth::user();
        $biayaScan = 1000;

        if ($customer->saldo < $biayaScan) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo tidak mencukupi! Sisa saldo Anda: Rp ' . number_format($customer->saldo, 0, ',', '.'),
                'type'    => 'error'
            ], 400);
        }

        $resi = $request->input('resi_number');
        $package = null;

        try {
            DB::transaction(function () use ($customer, $resi, $biayaScan, &$package) {
                $customer->decrement('saldo', $biayaScan);

                // Cek apakah user punya kontak default, jika ada ambil ID-nya
                $kontak = Kontak::where('user_id', $customer->id_pengguna)->first();

                $package = ScannedPackage::create([
                    'user_id' => $customer->id_pengguna,
                    'kontak_id' => $kontak ? $kontak->id : null, // Hubungkan juga ke kontak_id
                    'resi_number' => $resi,
                    'status' => 'Proses Pickup',
                ]);
            });

            $message = $customer->nama_lengkap . ' telah scan resi baru: ' . $resi;
            $url = route('admin.spx_scans.index', ['search' => $resi]);
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
                'message' => 'Terjadi kesalahan sistem saat memproses saldo.',
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

        // Cek apakah user punya kontak default
        $kontak = Kontak::where('user_id', $customer->id_pengguna)->first();

        $suratJalan = SuratJalan::create([
            'user_id' => $customer->id_pengguna,
            'kontak_id' => $kontak ? $kontak->id : null, // Hubungkan juga ke kontak_id
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($resiList),
        ]);

        $queryUpdate = ScannedPackage::whereIn('resi_number', $resiList);
        $this->applyUserFilter($queryUpdate); // Pastikan hanya resi miliknya
        $queryUpdate->update(['surat_jalan_id' => $suratJalan->id]);

        $message = $customer->nama_lengkap . ' telah membuat Surat Jalan baru.';
        $url = route('admin.spx_scans.index', ['search' => $kodeUnik]);
        event(new AdminNotificationEvent('Surat Jalan Baru Dibuat!', $message, $url));

        return response()->json([
            'success' => true,
            'message' => 'Surat Jalan berhasil dibuat!',
            'data' => [
                'pdf_url' => url('/api/mobile/suratjalan/download/' . $kodeUnik),
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
        // 1. Langsung cari berdasarkan kode uniknya (Tanpa filter Auth/Login karena dibuka via Browser)
        $suratJalan = SuratJalan::where('kode_surat_jalan', $kode_surat_jalan)->firstOrFail();

        // 2. Ambil paket/resi yang terikat
        $scans = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();

        // 3. Prioritas Data Pengirim (Kontak -> User)
        if ($suratJalan->kontak_id) {
            $customer = Kontak::find($suratJalan->kontak_id);
        } else {
            $customer = User::where('id_pengguna', $suratJalan->user_id)->first();
        }

        // 4. Generate PDF
        $pdf = Pdf::loadView('customer.scan.surat-jalan-pdf', compact('suratJalan', 'scans', 'customer'));
        return $pdf->download('surat-jalan-' . $kode_surat_jalan . '.pdf');
    }

    /**
     * 6. Mengambil data riwayat scan untuk filter periodik
     */
    public function getHistory(Request $request)
    {
        $query = ScannedPackage::with(['kontak']);
        $this->applyUserFilter($query);

        $now = Carbon::now();
        if ($request->has('filter_waktu')) {
            $filterWaktu = $request->query('filter_waktu');
            if ($filterWaktu === 'Hari Ini') $query->whereDate('created_at', $now->toDateString());
            elseif ($filterWaktu === 'Bulan Ini') $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
            elseif ($filterWaktu === 'Bulan Kemarin') {
                $lastMonth = $now->copy()->subMonth();
                $query->whereYear('created_at', $lastMonth->year)->whereMonth('created_at', $lastMonth->month);
            } elseif ($filterWaktu === 'Tahun Ini') $query->whereYear('created_at', $now->year);
        } elseif ($request->has('period')) {
            switch ($request->input('period')) {
                case 'today': $query->whereDate('created_at', Carbon::today()); break;
                case '7days': $query->where('created_at', '>=', Carbon::now()->subDays(7)); break;
                case '14days': $query->where('created_at', '>=', Carbon::now()->subDays(14)); break;
                case '30days': $query->where('created_at', '>=', Carbon::now()->subDays(30)); break;
                case 'lastmonth': $query->whereMonth('created_at', Carbon::now()->subMonth()->month); break;
            }
        }

        $scans = $query->latest()->paginate(50);
        return response()->json(['success' => true, 'data' => $scans]);
    }

    /**
     * 7. Memperbarui data status scan
     */
    public function update(Request $request, $resi_number)
    {
        $validated = $request->validate(['status' => 'required|string|max:255']);

        $query = ScannedPackage::where('resi_number', $resi_number);
        $this->applyUserFilter($query);

        $scan = $query->firstOrFail();
        $scan->update($validated);

        return response()->json(['success' => true, 'message' => 'Status resi berhasil diperbarui.', 'data' => $scan]);
    }

    /**
     * 8. Menghapus data scan dari database.
     */
    public function destroy($resi_number)
    {
        $query = ScannedPackage::where('resi_number', $resi_number);
        $this->applyUserFilter($query);

        $scan = $query->firstOrFail();
        $scan->delete();

        return response()->json(['success' => true, 'message' => 'Data scan berhasil dihapus.']);
    }

    /**
     * 9. Mengekspor data riwayat scan ke PDF.
     */
    public function exportPdf()
    {
        $query = ScannedPackage::query();
        $this->applyUserFilter($query);

        $scans = $query->latest()->get();
        $pdf = Pdf::loadView('customer.scan.pdf', compact('scans'));
        return $pdf->download('riwayat-scan.pdf');
    }

    /**
     * 10. Mengekspor data riwayat scan ke Excel.
     */
    public function exportExcel()
    {
        $userId = $this->isAdmin() ? null : Auth::user()->id_pengguna;
        return Excel::download(new ScansExport($userId), 'riwayat-scan.xlsx');
    }

    /**
     * Helper method untuk mengambil data scan hari ini
     */
    private function getTodaysScans()
    {
        $query = ScannedPackage::whereDate('created_at', today())
                               ->whereNull('surat_jalan_id');

        $this->applyUserFilter($query);

        return $query->latest()->get();
    }

   /**
     * 11. Mengambil daftar Riwayat Surat Jalan beserta resi di dalamnya.
     */
    public function historySuratJalan()
    {
        $query = SuratJalan::query();
        $this->applyUserFilter($query);

        $suratJalans = $query->latest()->get();

        $history = $suratJalans->map(function ($sj) {
            $sj->scanned_packages = ScannedPackage::where('surat_jalan_id', $sj->id)->get();

            // Prioritas Data Pengirim
            if (!empty($sj->kontak_id)) {
                $kontak = Kontak::find($sj->kontak_id);
                $sj->kontak = [
                    'nama'   => $kontak->nama ?? '-',
                    'no_hp'  => $kontak->no_hp ?? '-',
                    'alamat' => $kontak->alamat ?? '-'
                ];
            } else {
                $user = User::where('id_pengguna', $sj->user_id)->first();
                $sj->kontak = [
                    'nama'   => $user->nama_lengkap ?? '-',
                    'no_hp'  => $user->no_wa ?? $user->no_hp ?? '-',
                    'alamat' => $user->address_detail ?? $user->alamat ?? '-'
                ];
            }

            return $sj;
        });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }



}
