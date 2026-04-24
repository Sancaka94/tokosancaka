<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PerizinanController extends Controller
{
    // --- ADMIN: LIST DATA (READ) ---
    public function index()
    {
        $data = Perizinan::latest()->paginate(10);
        return view('admin.perizinan.index', compact('data'));
    }

    // --- PUBLIC: FORMULIR (CREATE) ---
    public function create()
    {
        return view('public.perizinan-form');
    }

    // --- PUBLIC: SIMPAN & KIRIM WA (STORE) ---
    public function store(Request $request)
    {
        // 1. Validasi
        $req = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_wa' => 'required|numeric',
            'lebar' => 'required',
            'panjang' => 'required',
            'status_bangunan' => 'required',
            'jenis_bangunan' => 'required',
            'lokasi' => 'required',
            'jumlah_lantai' => 'required',
            'fungsi_bangunan' => 'required',
            'legalitas_saat_ini' => 'required',
            'status_krk' => 'required',
            // --- TAMBAHAN BARU: Validasi Field Baru ---
            'jumlah_penghuni' => 'nullable|string', // Bisa diisi angka atau teks
            'memiliki_basement' => 'nullable|boolean', // 1 atau 0 (Checkbox/Radio)
            'rekom_dishub' => 'nullable|boolean',
            'rekom_damkar' => 'nullable|boolean',
            'andalalin' => 'nullable|boolean',
            'lingkungan' => 'nullable|boolean', // Untuk SPPL/UKL-UPL/AMDAL
            'nib' => 'nullable|boolean',
            'siup' => 'nullable|boolean',
            'status_tanah' => 'nullable|string', // SHM, HGB, dll
            'perizinan_lain' => 'nullable|string',
        ]);

        // Opsional: Konversi nilai checkbox yang mungkin tidak terkirim menjadi false/0
        // Jika form HTML tidak mengirimkan nilai saat checkbox tidak dicentang.
        $checkboxFields = ['memiliki_basement', 'rekom_dishub', 'rekom_damkar', 'andalalin', 'lingkungan', 'nib', 'siup'];
        foreach ($checkboxFields as $field) {
            $req[$field] = $request->has($field) ? true : false;
        }

        // 2. Simpan ke Database
        $perizinan = Perizinan::create($req);

        // 3. Logika Kirim WA
        try {
            // A. Kirim ke Admin (Hardcode: 085745808809)
            $adminPhone = '6285745808809';

            $msgAdmin = "*FORMULIR PERIZINAN BARU MASUK* 📄\n\n";
            $msgAdmin .= "Nama: *" . $perizinan->nama_pelanggan . "*\n";
            $msgAdmin .= "WA: " . $perizinan->no_wa . "\n\n";
            $msgAdmin .= "*Detail Bangunan:*\n";
            $msgAdmin .= "📏 Dimensi: " . $perizinan->lebar . "m x " . $perizinan->panjang . "m\n";
            $msgAdmin .= "🏗 Status: " . $perizinan->status_bangunan . "\n";
            $msgAdmin .= "🏠 Jenis: " . $perizinan->jenis_bangunan . "\n";
            $msgAdmin .= "📍 Lokasi: " . $perizinan->lokasi . "\n";
            $msgAdmin .= "🏢 Lantai: " . $perizinan->jumlah_lantai . "\n";
            
            // --- TAMBAHAN BARU: Penghuni & Basement ---
            $msgAdmin .= "👥 Penghuni/Karyawan: " . ($perizinan->jumlah_penghuni ?? '-') . "\n";
            $msgAdmin .= "⬇️ Basement: " . ($perizinan->memiliki_basement ? 'Ada' : 'Tidak Ada') . "\n";
            
            $msgAdmin .= "🛠 Fungsi: " . $perizinan->fungsi_bangunan . "\n";
            $msgAdmin .= "📜 Legalitas: " . $perizinan->legalitas_saat_ini . "\n";
            $msgAdmin .= "📑 KRK/PKKPR: " . $perizinan->status_krk . "\n\n";

            // --- TAMBAHAN BARU: Checklist Kelengkapan Perizinan ---
            $msgAdmin .= "*Kelengkapan Perizinan:*\n";
            $msgAdmin .= ($perizinan->rekom_dishub ? "✅" : "❌") . " Rekom Dishub\n";
            $msgAdmin .= ($perizinan->rekom_damkar ? "✅" : "❌") . " Rekom Damkar\n";
            $msgAdmin .= ($perizinan->andalalin ? "✅" : "❌") . " Andalalin\n";
            $msgAdmin .= ($perizinan->lingkungan ? "✅" : "❌") . " SPPL/UKL-UPL/AMDAL\n";
            $msgAdmin .= ($perizinan->nib ? "✅" : "❌") . " NIB\n";
            $msgAdmin .= ($perizinan->siup ? "✅" : "❌") . " SIUP\n";
            $msgAdmin .= "Status Tanah: *" . ($perizinan->status_tanah ?? 'Belum Diisi') . "*\n";
            
            if (!empty($perizinan->perizinan_lain)) {
                $msgAdmin .= "Lain-lain: " . $perizinan->perizinan_lain . "\n";
            }
            $msgAdmin .= "\nMohon segera di-follow up untuk penentuan harga.";

            FonnteService::sendMessage($adminPhone, $msgAdmin);

            // B. Kirim ke Pelanggan
            $pelangganPhone = $this->formatNumber($perizinan->no_wa);

            $msgPelanggan = "Halo Kak *" . $perizinan->nama_pelanggan . "* 👋,\n\n";
            $msgPelanggan .= "Terima kasih telah mengisi formulir kriteria bangunan di *CV. SANCAKA KARYA HUTAMA*.\n\n";
            $msgPelanggan .= "Data kriteria bangunan kakak sudah kami terima dan sedang direview oleh tim kami.\n\n";
            $msgPelanggan .= "Tim kami akan segera menghubungi kakak untuk estimasi biaya perizinannya. Mohon ditunggu ya! 🙏\n\n";
            $msgPelanggan .= "--\n*Admin Sancaka*";

            FonnteService::sendMessage($pelangganPhone, $msgPelanggan);

        } catch (\Exception $e) {
            Log::error("Gagal kirim WA Perizinan: " . $e->getMessage());
        }

        return redirect()->route('perizinan.form')->with('success', 'Data berhasil dikirim! Cek WhatsApp Anda untuk konfirmasi.');
    }

    // --- ADMIN: DELETE ---
    public function destroy($id)
    {
        $item = Perizinan::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.perizinan.index')->with('success', 'Data berhasil dihapus.');
    }

    // --- HELPER: Format Nomor HP ---
    private function formatNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number); // Hapus karakter aneh
        if (substr($number, 0, 1) == '0') {
            $number = '62' . substr($number, 1);
        } elseif (substr($number, 0, 1) == '8') {
            $number = '62' . $number;
        }
        return $number;
    }
}