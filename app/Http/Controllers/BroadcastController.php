<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan; // Pastikan Model Pelanggan ada
use App\Models\Kontak;    // Pastikan Model Kontak ada
use App\Services\FonnteService; // Panggil Service Lama Anda

class BroadcastController extends Controller
{
    /**
     * Halaman Utama Broadcast
     */
    public function index()
    {
        // 1. Ambil Data Pelanggan (Filter yang punya WA saja)
        // Ambil kolom yang diperlukan saja biar ringan
        $pelanggans = Pelanggan::whereNotNull('nomor_wa')
            ->where('nomor_wa', '!=', '')
            ->latest()
            ->get(['id', 'nama_pelanggan', 'nomor_wa', 'keterangan']);

        // 2. Ambil Data Kontak (Filter yang punya HP saja)
        $kontaks = Kontak::whereNotNull('no_hp')
            ->where('no_hp', '!=', '')
            ->latest()
            ->get(['id', 'nama', 'no_hp', 'tipe']);

        return view('broadcast.index', compact('pelanggans', 'kontaks'));
    }

    /**
     * Handler Pengiriman (Logika Cerdas Disini)
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'targets' => 'required|array|min:1',
        ]);

        $rawTargets = $request->input('targets');
        $message = $request->input('message');

        // --- LOGIKA CERDAS (HANDLER DI CONTROLLER) ---
        $cleanTargets = [];

        foreach ($rawTargets as $number) {
            // Panggil fungsi pembersih nomor (ada di bawah)
            $formatted = $this->formatNomorIndonesia($number);
            
            if ($formatted) {
                $cleanTargets[] = $formatted;
            }
        }

        // Hapus nomor ganda (misal ada di pelanggan DAN di kontak)
        $cleanTargets = array_unique($cleanTargets);

        // Validasi jika setelah dibersihkan malah kosong
        if (empty($cleanTargets)) {
            return back()->with('error', 'Tidak ada nomor valid yang ditemukan.');
        }

        // Fonnte menerima broadcast dengan format: "nomor1,nomor2,nomor3"
        $targetString = implode(',', $cleanTargets);

        try {
            // --- PANGGIL SERVICE LAMA ANDA ---
            // Kita pakai method sendMessage yang sudah ada. 
            // Karena $targetString isinya banyak nomor dipisah koma, Fonnte otomatis menganggap ini broadcast.
            $response = FonnteService::sendMessage($targetString, $message);

            // Cek Response Laravel HTTP Client
            if ($response->successful()) {
                $json = $response->json();
                // Cek status dari body response Fonnte
                if (isset($json['status']) && $json['status'] == true) {
                    return back()->with('success', 'Broadcast berhasil dikirim ke ' . count($cleanTargets) . ' nomor.');
                }
            }
            
            return back()->with('error', 'Gagal kirim. Response Fonnte: ' . $response->body());

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * PRIVATE HELPER: Format Nomor HP jadi 628xxx
     * Ini yang membuat sistem "Cerdas" tanpa ubah Service
     */
    private function formatNomorIndonesia($no)
    {
        // 1. Hapus spasi, strip, +dll. Ambil angkanya saja.
        $no = preg_replace('/[^0-9]/', '', trim($no));

        // 2. Cek Prefix
        // Kalau 08... ganti 628...
        if (substr($no, 0, 2) === '08') {
            return '62' . substr($no, 1);
        }
        
        // Kalau 8... (lupa 0) ganti 628...
        if (substr($no, 0, 1) === '8') {
            return '62' . $no;
        }

        // Kalau 62... biarkan
        if (substr($no, 0, 2) === '62') {
            return $no;
        }

        // Kalau nomor telepon rumah 021/031... ganti 6221/6231
        if (substr($no, 0, 1) === '0') {
            return '62' . substr($no, 1);
        }

        // Default: Kembalikan apa adanya (atau return null jika ingin strict)
        return $no;
    }
}