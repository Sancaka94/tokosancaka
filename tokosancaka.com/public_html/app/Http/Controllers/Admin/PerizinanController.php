<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;
use App\Services\FonnteService; // Pastikan Service ini ada (sesuai kode sebelumnya)
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
        ]);

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
            $msgAdmin .= "🛠 Fungsi: " . $perizinan->fungsi_bangunan . "\n";
            $msgAdmin .= "📜 Legalitas: " . $perizinan->legalitas_saat_ini . "\n";
            $msgAdmin .= "📑 KRK/PKKPR: " . $perizinan->status_krk . "\n\n";
            $msgAdmin .= "Mohon segera di-follow up untuk penentuan harga.";

            FonnteService::sendMessage($adminPhone, $msgAdmin);

            // B. Kirim ke Pelanggan
            $pelangganPhone = $this->formatNumber($perizinan->no_wa);

            $msgPelanggan = "Halo Kak *" . $perizinan->nama_pelanggan . "* 👋,\n\n";
            $msgPelanggan .= "Terima kasih telah mengisi formulir kriteria bangunan di *CV. SANCAKA KARYA HUTAMA*.\n\n";
            $msgPelanggan .= "Data kakak sudah kami terima:\n";
            $msgPelanggan .= "✅ Lokasi: " . $perizinan->lokasi . "\n";
            $msgPelanggan .= "✅ Fungsi: " . $perizinan->fungsi_bangunan . "\n\n";
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
