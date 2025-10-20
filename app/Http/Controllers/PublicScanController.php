<?php

namespace App\Http\Controllers;

use App\Events\AdminNotificationEvent;
use App\Models\Kontak;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use App\Services\FonnteService; // Pastikan path ini sesuai dengan struktur proyek Anda
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicScanController extends Controller
{
    /**
     * Menampilkan halaman scan utama.
     */
    public function show()
    {
        return view('public.scan-spx');
    }

    /**
     * Mencari kontak berdasarkan nama atau no_hp untuk autocomplete.
     */
    public function searchKontak(Request $request)
    {
        $query = $request->input('query');
        $kontaks = Kontak::where('nama', 'LIKE', "%{$query}%")
            ->orWhere('no_hp', 'LIKE', "%{$query}%")
            ->take(5)
            ->get();
        return response()->json($kontaks);
    }

    /**
     * Mendaftarkan kontak baru dari halaman scan.
     */
    public function registerKontak(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|unique:kontaks,no_hp',
            'alamat' => 'required|string',
        ]);

        // Menambahkan nilai default untuk kolom yang tidak boleh null
        $validated['tipe'] = 'Pengirim';
        $validated['province'] = '';
        $validated['regency'] = '';
        $validated['district'] = '';
        $validated['village'] = '';
        $validated['postal_code'] = '';

        $kontak = Kontak::create($validated);
        return response()->json(['success' => true, 'data' => $kontak]);
    }

    /**
     * Menangani dan menyimpan resi yang di-scan, serta mengirim notifikasi admin.
     */
    public function handleScan(Request $request)
    {
        $validated = $request->validate([
            'kontak_id' => 'required|exists:kontaks,id',
            'resi' => 'required|string|unique:scanned_packages,resi_number',
        ]);

        $package = ScannedPackage::create([
            'kontak_id' => $validated['kontak_id'],
            'resi_number' => $validated['resi'],
        ]);

        $kontak = Kontak::find($validated['kontak_id']);

        // Menyiapkan data untuk notifikasi real-time
        $title = 'Scan Resi Baru';
        $message = "Resi '{$package->resi_number}' dari '{$kontak->nama}' telah di-scan.";
        $url = url('admin/spx-scans'); // Mengarahkan ke halaman daftar scan

        // Mengirim event notifikasi ke admin
        broadcast(new AdminNotificationEvent($title, $message, $url))->toOthers();

        return response()->json([
            'success' => true,
            'data' => [
                'nomor_resi' => $package->resi_number,
                'nama_pengirim' => $kontak->nama,
                'waktu_scan' => Carbon::now()->format('H:i:s'),
            ]
        ]);
    }

    /**
     * Membuat surat jalan dan mengirim notifikasi WhatsApp.
     */
    public function createSuratJalan(Request $request)
    {
        $validated = $request->validate([
            'kontak_id' => 'required|exists:kontaks,id',
            'resi_list' => 'required|array',
            'resi_list.*' => 'string',
        ]);

        $kontak = Kontak::find($validated['kontak_id']);
        $kodeUnik = 'SJL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $suratJalan = SuratJalan::create([
            'kontak_id' => $kontak->id,
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($validated['resi_list']),
        ]);

        // Update paket-paket yang di-scan dengan ID surat jalan
        ScannedPackage::whereIn('resi_number', $validated['resi_list'])
            ->whereNull('surat_jalan_id') // Hanya update yang belum punya surat jalan
            ->update(['surat_jalan_id' => $suratJalan->id]);

        // Kirim Notifikasi WhatsApp ke pengirim
        $this->_sendSuratJalanWhatsapp($suratJalan);

        return response()->json([
            'success' => true,
            'kode_surat_jalan' => $kodeUnik,
            'customer_name' => $kontak->nama,
            'package_count' => $suratJalan->jumlah_paket,
            'created_at' => $suratJalan->created_at->format('d-m-Y H:i'),
            'pdf_url' => route('surat.jalan.pdf')
                . '?resi=' . implode(',', $validated['resi_list'])
                . '&kode=' . $kodeUnik,
        ]);
    }

    /**
     * Fungsi placeholder untuk download PDF.
     */
    public function downloadSuratJalan($kode)
    {
        // Implementasi logika untuk generate dan download PDF, misalnya menggunakan DomPDF
        return "Halaman PDF untuk Surat Jalan: " . $kode;
    }

    /**
     * Method private untuk mengirim notifikasi WhatsApp setelah surat jalan dibuat.
     */
    private function _sendSuratJalanWhatsapp(SuratJalan $suratJalan)
    {
        $suratJalan->load('kontak');
        $kontak = $suratJalan->kontak;

        if (!$kontak) { // Cek hanya kontak, no_hp tidak lagi krusial untuk pengiriman
            Log::warning("Gagal kirim WA: Kontak tidak ditemukan untuk Surat Jalan ID {$suratJalan->id}");
            return;
        }

        // PERUBAHAN: Template diubah menjadi notifikasi untuk admin
        $messageTemplate = <<<TEXT
*Surat Jalan Baru Dibuat* ℹ️

Telah dibuat surat jalan baru oleh *{NAMA_PENGIRIM}*.

Rincian:
Nomor Surat Jalan: *{KODE_SURAT_JALAN}*
Jumlah Paket: *{JUMLAH_PAKET}*

Mohon segera proses untuk pickup.

*Notifikasi Sistem Sancaka*
TEXT;

        $message = str_replace(
            ['{NAMA_PENGIRIM}', '{KODE_SURAT_JALAN}', '{JUMLAH_PAKET}'],
            [$kontak->nama, $suratJalan->kode_surat_jalan, $suratJalan->jumlah_paket],
            $messageTemplate
        );

        try {
            // PERUBAHAN: Nomor tujuan di-hardcode sesuai permintaan
            $waNumber = '628819435180';
            FonnteService::sendMessage($waNumber, $message);
            Log::info("Notifikasi WA admin (Surat Jalan) berhasil dikirim ke {$waNumber} untuk SJ: {$suratJalan->kode_surat_jalan}");
        } catch (\Exception $e) {
            // Mencatat error jika pengiriman gagal, agar tidak menghentikan proses utama
            Log::error("Gagal mengirim notifikasi WA admin (Surat Jalan) ke {$waNumber}: " . $e->getMessage());
        }
    }
}

