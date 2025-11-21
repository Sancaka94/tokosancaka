<?php

namespace App\Http\Controllers;

use App\Models\KodePos;
use Illuminate\Http\Request;
use App\Jobs\ImportKodePosJob;
use Illuminate\Support\Facades\Log;

class KodePosController extends Controller
{
    /**
     * Menampilkan halaman daftar kode pos.
     */
    public function index(Request $request)
    {
        $headline = 'Pencarian Kode Pos';
        $query = KodePos::query();

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('id', 'like', "%{$search}%")
                ->orWhere('provinsi', 'like', "%{$search}%")
                ->orWhere('kota_kabupaten', 'like', "%{$search}%")
                ->orWhere('kecamatan', 'like', "%{$search}%")
                ->orWhere('kelurahan_desa', 'like', "%{$search}%")
                ->orWhere('kode_pos', 'like', "%{$search}%");
        }

        // DIUBAH: Mengurutkan hanya berdasarkan ID (terkecil ke terbesar)
        $kode_pos_list = $query->orderBy('id', 'asc')->paginate(10);

        return view('admin.kodepos.index', compact('kode_pos_list', 'headline'));
    }

    /**
     * Menangani pengiriman tugas impor ke antrian.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx'
        ]);

        try {
            // 1. Simpan file sementara di server
            $path = $request->file('file')->store('imports');

            // 2. Kirim path file ke Job, lalu dispatch ke antrian
            ImportKodePosJob::dispatch($path);

            // 3. Langsung kembalikan response sukses ke pengguna
            return response()->json(['success' => 'File Anda telah diterima dan sedang diproses di latar belakang. Ini mungkin memakan waktu beberapa menit.']);

        } catch (\Exception $e) {
            Log::error('Upload for import failed: ' . $e->getMessage());

            return response()->json(['error' => 'Gagal mengunggah file. Silakan periksa log server.'], 500);
        }
    }
}

