<?php

namespace App\Http\Controllers;

// use App\Events\AdminNotificationEvent; // <-- [PERBAIKAN] Dihapus
use App\Models\Kontak;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use App\Models\User; // <-- [DITAMBAHKAN] Diperlukan untuk notifikasi
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Milon\Barcode\DNS1D;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification; // <-- [DITAMBAHKAN] Diperlukan
use App\Notifications\NotifikasiUmum;      // <-- [DITAMBAHKAN] Diperlukan
use Exception; // <-- [DITAMBAHKAN] Menangkap Exception


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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $package = ScannedPackage::create([
            'kontak_id' => $validated['kontak_id'],
            'resi_number' => $validated['resi'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'Proses Pickup',
        ]);

        $kontak = Kontak::find($validated['kontak_id']);

        // Menyiapkan data untuk notifikasi real-time
        $title = 'Scan Resi Baru (Publik)';
        $message = "Resi '{$package->resi_number}' dari '{$kontak->nama}' telah di-scan.";
        $url = url('admin/spx-scans'); // Mengarahkan ke halaman daftar scan

        // ==========================================================
        // 👇 [PERBAIKAN] Ganti 'AdminNotificationEvent' dengan 'NotifikasiUmum'
        // ==========================================================
        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'Scan',
                    'judul'       => $title,
                    'pesan_utama' => $message,
                    'url'         => $url,
                    'icon'        => 'fas fa-barcode', // Ikon scan
                    'latitude'    => $package->latitude, // <-- DATA LOKASI DITAMBAHKAN
                    'longitude'   => $package->longitude, // <-- DATA LOKASI DITAMBAHKAN
                    
                    
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (Exception $e) {
            Log::error('Gagal broadcast NotifikasiUmum (handleScan): ' . $e->getMessage());
        }
        // ==========================================================
        // 👆 AKHIR PERBAIKAN
        // ==========================================================
        
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
            'latitude' => 'required|numeric',  // <-- Validasi sudah ada
            'longitude' => 'required|numeric', // <-- Validasi sudah ada
        ]);

        $kontak = Kontak::find($validated['kontak_id']);
        $kodeUnik = 'SJL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $suratJalan = SuratJalan::create([
            'kontak_id' => $kontak->id,
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($validated['resi_list']),
            'latitude' => $validated['latitude'],   // <-- Sudah benar
            'longitude' => $validated['longitude'], // <-- Sudah benar
        ]);

        // Update paket-paket yang di-scan dengan ID surat jalan
        ScannedPackage::whereIn('resi_number', $validated['resi_list'])
            ->whereNull('surat_jalan_id') // Hanya update yang belum punya surat jalan
            ->update(['surat_jalan_id' => $suratJalan->id]);

        // Kirim Notifikasi WhatsApp ke admin (Fungsi ini sudah ada)
        $this->_sendSuratJalanWhatsapp($suratJalan);

        // ==========================================================
        // 👇 [DILENGKAPI] Tambahkan notifikasi real-time ke Admin Panel
        // ==========================================================
        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'SuratJalan',
                    'judul'       => 'Surat Jalan Dibuat (Publik)',
                    'pesan_utama' => "SJ {$suratJalan->kode_surat_jalan} ({$suratJalan->jumlah_paket} paket) dari {$kontak->nama} dibuat.",
                    // Arahkan ke rute yang benar. Asumsi 'admin.spx_scans.index' sudah ada
                    'url'         => $url, 
                    'icon'        => 'fas fa-truck',
                    'latitude'    => $suratJalan->latitude, // <-- DATA LOKASI DITAMBAHKAN
                    'longitude'   => $suratJalan->longitude, // <-- DATA LOKASI DITAMBAHKAN
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (Exception $e) {
            Log::error('Gagal broadcast NotifikasiUmum (createSuratJalan): ' . $e->getMessage());
        }
        // ==========================================================
        // 👆 AKHIR DARI TAMBAHAN
        // ==========================================================

        return response()->json([
            'success' => true,
            'kode_surat_jalan' => $kodeUnik,
            'customer_name' => $kontak->nama,
            'package_count' => $suratJalan->jumlah_paket,
            'created_at' => $suratJalan->created_at->format('d-m-Y H:i'),
            'pdf_url' => route('surat.jalan.pdf') // Asumsi rute ini ada
                . '?resi=' . implode(',', $validated['resi_list'])
                . '&kode=' . $kodeUnik,
        ]);
    }

 /**
     * GANTI FUNGSI LAMA ANDA DENGAN YANG INI
     */
    public function generateSuratJalan(Request $request)
    {
        // Ambil kode dari request
        $kodeSuratJalan = $request->query('kode');

        // 1. PENGAMBILAN DATA YANG BENAR
        $suratJalan = SuratJalan::with('kontak')
                            ->where('kode_surat_jalan', $kodeSuratJalan)
                            ->firstOrFail();

        // 2. Ambil semua paket yang terhubung
        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();

        // 3. BUAT SEMUA BARCODE & QR CODE DI SINI
        
        // Barcode horizontal (atas)
        $generator = new \Milon\Barcode\DNS1D();
        $barcodeRectBase64 = base64_encode(
            $generator->getBarcodePNG($suratJalan->kode_surat_jalan, 'C128', 2, 60, [1, 1, 1], true)
        );

        // QR identitas surat jalan (kanan bawah)
        $qrCodeBase64 = base64_encode(
            QrCode::format('png')->size(80)->generate($suratJalan->kode_surat_jalan)
        );

        // QR lokasi (kiri bawah)
        $locationQrCodeBase64 = null; // Definisikan sebagai null dulu
        
        // Cek apakah data latitude/longitude ada di Model
        if ($suratJalan->latitude && $suratJalan->longitude) {
            $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
            $locationQrCodeBase64 = base64_encode(
                QrCode::format('png')->size(80)->generate($googleMapsUrl)
            );
        }

        // 4. KIRIM SEMUA DATA KE VIEW
        // Pastikan path view-nya benar
        $pdf = Pdf::loadView('public.scan.pdf.surat-jalan-spx', [
            'suratJalan' => $suratJalan,
            'packages' => $packages,
            'barcodeRectBase64' => $barcodeRectBase64,   // Variabel untuk barcode 1D
            'qrCodeBase64' => $qrCodeBase64,         // Variabel untuk QR tanda tangan
            'locationQrCodeBase64' => $locationQrCodeBase64 // Variabel untuk QR lokasi
        ]);

        return $pdf->stream('SuratJalan-' . $suratJalan->kode_surat_jalan . '.pdf');
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

        // PERUBAHAN: Dapatkan waktu input dan format dalam Bahasa Indonesia
        Carbon::setLocale('id');
        $timestamp = $suratJalan->created_at->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i');

        // PERUBAHAN: Ambil daftar resi dari surat jalan
        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        $resiList = $packages->pluck('resi_number')->implode("\n");
        
        // BARU: Buat link Google Maps
        // Pastikan $suratJalan->latitude/longitude ada (dari Langkah 1)
        $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";

        // PERUBAHAN: Template diubah menjadi notifikasi untuk admin
        $messageTemplate = <<<TEXT
⚠ *Surat Jalan Baru Dibuat* ⚠

Telah dibuat surat jalan baru oleh *{NAMA_PENGIRIM}*.

*RINCIAN:*

Waktu Input: *{WAKTU_INPUT}*
Nomor Surat Jalan: *{KODE_SURAT_JALAN}*
Jumlah Paket: *{JUMLAH_PAKET}*
No. WA Pengirim: *{NO_HP_PENGIRIM}*
Alamat Pengirim: *{ALAMAT_PENGIRIM}*

*Daftar Resi:*
{DAFTAR_RESI}

*Lokasi Pickup (Google Maps):*
{LINK_LOKASI}

*Mohon segera proses untuk input ke system SPX.*

*Notifikasi Sistem Sancaka*
TEXT;

        $message = str_replace(
            ['{WAKTU_INPUT}', '{NAMA_PENGIRIM}', '{KODE_SURAT_JALAN}', '{JUMLAH_PAKET}', '{NO_HP_PENGIRIM}', '{ALAMAT_PENGIRIM}', '{DAFTAR_RESI}', '{LINK_LOKASI}'],
            [$timestamp, $kontak->nama, $suratJalan->kode_surat_jalan, $suratJalan->jumlah_paket, $kontak->no_hp, $kontak->alamat, $resiList, $googleMapsUrl],
            $messageTemplate
        );

        try {
            // PERUBAHAN: Nomor tujuan di-hardcode sesuai permintaan
            $waNumber = '628819435180';
            $waAdmin = '6285745808809';
            FonnteService::sendMessage($waNumber, $message);
            FonnteService::sendMessage($waAdmin, $message);

            Log::info("Notifikasi WA admin (Surat Jalan) berhasil dikirim ke {$waNumber} untuk SJ: {$suratJalan->kode_surat_jalan}");
        } catch (\Exception $e) {
            // Mencatat error jika pengiriman gagal, agar tidak menghentikan proses utama
            Log::error("Gagal mengirim notifikasi WA admin (Surat Jalan) ke {$waNumber}: " . $e->getMessage());
        }
    }
}