<?php

namespace App\Http\Controllers;

use App\Models\RegistrasiDriverSancaka;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RegisterDriverOnlineController extends Controller
{
    // ==========================================
    // AREA PUBLIC (PENDAFTARAN)
    // ==========================================

    public function create()
    {
        return view('public.register_driver');
    }

    public function store(Request $request)
    {
        Log::info("LOG LOG: Proses pendaftaran driver baru via Web masuk.");

        $request->validate([
            'nama_lengkap'    => 'required|string|max:255',
            'nomor_nik'       => 'required|string|max:20',
            'nomor_kk'        => 'required|string|max:20',
            'nomor_wa'        => 'required|string|max:20',
            'alamat_lengkap'  => 'required|string',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'file_ktp'        => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_kk'         => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_buku_nikah' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_stnk'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_bpkb'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_motor'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto_wajah'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        try {
            $uploadPath = 'drivers';
            $filePaths = [];
            $fields = ['file_ktp', 'file_kk', 'file_buku_nikah', 'file_stnk', 'file_bpkb', 'foto_motor', 'foto_wajah'];

            foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $filePaths[$field] = $request->file($field)->store($uploadPath, 'public');
                } else {
                    $filePaths[$field] = null;
                }
            }

            RegistrasiDriverSancaka::create([
                'nama_lengkap'    => $request->nama_lengkap,
                'nomor_nik'       => $request->nomor_nik,
                'nomor_kk'        => $request->nomor_kk,
                'nomor_wa'        => $request->nomor_wa,
                'alamat_lengkap'  => $request->alamat_lengkap,
                'latitude'        => $request->latitude,
                'longitude'       => $request->longitude,
                'file_ktp'        => $filePaths['file_ktp'],
                'file_kk'         => $filePaths['file_kk'],
                'file_buku_nikah' => $filePaths['file_buku_nikah'],
                'file_stnk'       => $filePaths['file_stnk'],
                'file_bpkb'       => $filePaths['file_bpkb'],
                'foto_motor'      => $filePaths['foto_motor'],
                'foto_wajah'      => $filePaths['foto_wajah'],
                'status'          => 'pending',
                'is_active_map'   => 0,
            ]);

            Log::info("LOG LOG: Pendaftaran driver {$request->nama_lengkap} berhasil disimpan.");
            return redirect()->back()->with('success', 'Pendaftaran berhasil! Tim kami akan segera menghubungi Anda.');

        } catch (\Exception $e) {
            Log::error("LOG LOG: Gagal menyimpan pendaftaran. Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    public function update(Request $request, $id)
    {
        Log::info("LOG LOG: Memperbarui data pendaftaran driver ID: {$id}");

        $driver = RegistrasiDriverSancaka::findOrFail($id);
        
        $driver->update([
            'nama_lengkap'   => $request->nama_lengkap,
            'nomor_nik'      => $request->nomor_nik,
            'nomor_kk'       => $request->nomor_kk,
            'nomor_wa'       => $request->nomor_wa,
            'alamat_lengkap' => $request->alamat_lengkap,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude
        ]);

        return redirect()->back()->with('success', 'Data driver berhasil diperbarui.');
    }

    // ==========================================
    // AREA ADMIN (MANAJEMEN DRIVER)
    // ==========================================

    public function index(Request $request)
    {
        $query = RegistrasiDriverSancaka::query();

        // 1. Filter Pencarian (Nama, WA, NIK)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_wa', 'like', "%{$search}%")
                  ->orWhere('nomor_nik', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Rentang Tanggal (Flatpickr)
        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', $dates[0]);
            }
        }

        // 4. Data untuk Card Monitor (Berdasarkan Total Seluruh Data)
        $totalDrivers = RegistrasiDriverSancaka::count();
        $pendingDrivers = RegistrasiDriverSancaka::where('status', 'pending')->count();
        $approvedDrivers = RegistrasiDriverSancaka::where('status', 'approved')->count();
        $rejectedDrivers = RegistrasiDriverSancaka::where('status', 'rejected')->count();

        // 5. Pagination
        $drivers = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.drivers.index', compact(
            'drivers', 'totalDrivers', 'pendingDrivers', 'approvedDrivers', 'rejectedDrivers'
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $status = $request->status;

        DB::beginTransaction();
        try {
            $driver = RegistrasiDriverSancaka::findOrFail($id);
            $driver->update(['status' => $status]);

            // Jika diapprove dan punya akun pengguna, set role menjadi Driver
            if ($status === 'approved' && $driver->id_pengguna) {
                Pengguna::where('id_pengguna', $driver->id_pengguna)->update(['role' => 'Driver']);
            }

            DB::commit();
            return redirect()->back()->with('success', "Pendaftaran berhasil di-{$status}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal merubah status.');
        }
    }

    public function destroy($id)
    {
        $driver = RegistrasiDriverSancaka::findOrFail($id);
        $driver->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        // Menangkap data ID dari form submission
        $ids = $request->input('selected_ids');
        
        if (!$ids || empty($ids)) {
            return redirect()->back()->with('error', 'Pilih minimal satu data terlebih dahulu.');
        }

        Log::info("LOG LOG: Admin melakukan bulk delete pada ID: " . implode(',', $ids));

        RegistrasiDriverSancaka::whereIn('id', $ids)->delete();
        
        return redirect()->back()->with('success', count($ids) . ' data terpilih berhasil dihapus secara massal.');
    }
}