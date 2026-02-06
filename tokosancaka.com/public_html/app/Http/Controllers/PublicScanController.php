<?php

namespace App\Http\Controllers;

// use App\Events\AdminNotificationEvent;
use App\Models\Kontak;
use App\Models\ScannedPackage;
use App\Models\SuratJalan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Milon\Barcode\DNS1D;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;
use Exception;

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
        $url = url('admin/spx-scans');

        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'Scan',
                    'judul'       => $title,
                    'pesan_utama' => $message,
                    'url'         => $url,
                    'icon'        => 'fas fa-barcode',
                    'latitude'    => $package->latitude,
                    'longitude'   => $package->longitude,
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (Exception $e) {
            Log::error('Gagal broadcast NotifikasiUmum (handleScan): ' . $e->getMessage());
        }

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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $kontak = Kontak::find($validated['kontak_id']);
        $kodeUnik = 'SJL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $suratJalan = SuratJalan::create([
            'kontak_id' => $kontak->id,
            'kode_surat_jalan' => $kodeUnik,
            'jumlah_paket' => count($validated['resi_list']),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        // Update paket-paket yang di-scan dengan ID surat jalan
        ScannedPackage::whereIn('resi_number', $validated['resi_list'])
            ->whereNull('surat_jalan_id')
            ->update(['surat_jalan_id' => $suratJalan->id]);

        // Kirim Notifikasi WhatsApp (Admin & Pelanggan)
        $this->_sendSuratJalanWhatsapp($suratJalan);

        // Notifikasi Panel Admin
        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                $dataNotifAdmin = [
                    'tipe'        => 'SuratJalan',
                    'judul'       => 'Surat Jalan Dibuat (Publik)',
                    'pesan_utama' => "SJ {$suratJalan->kode_surat_jalan} ({$suratJalan->jumlah_paket} paket) dari {$kontak->nama} dibuat.",
                    'url'         => url('admin/spx-scans'),
                    'icon'        => 'fas fa-truck',
                    'latitude'    => $suratJalan->latitude,
                    'longitude'   => $suratJalan->longitude,
                ];
                Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
            }
        } catch (Exception $e) {
            Log::error('Gagal broadcast NotifikasiUmum (createSuratJalan): ' . $e->getMessage());
        }

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

    public function generateSuratJalan(Request $request)
    {
        $kodeSuratJalan = $request->query('kode');

        $suratJalan = SuratJalan::with('kontak')
                            ->where('kode_surat_jalan', $kodeSuratJalan)
                            ->firstOrFail();

        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();

        // Barcode 1D
        $generator = new \Milon\Barcode\DNS1D();
        $barcodeRectBase64 = base64_encode(
            $generator->getBarcodePNG($suratJalan->kode_surat_jalan, 'C128', 2, 60, [1, 1, 1], true)
        );

        // QR Identitas
        $qrCodeBase64 = base64_encode(
            QrCode::format('png')->size(80)->generate($suratJalan->kode_surat_jalan)
        );

        // QR Lokasi
        $locationQrCodeBase64 = null;
        if ($suratJalan->latitude && $suratJalan->longitude) {
            $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
            $locationQrCodeBase64 = base64_encode(
                QrCode::format('png')->size(80)->generate($googleMapsUrl)
            );
        }

        $pdf = Pdf::loadView('public.scan.pdf.surat-jalan-spx', [
            'suratJalan' => $suratJalan,
            'packages' => $packages,
            'barcodeRectBase64' => $barcodeRectBase64,
            'qrCodeBase64' => $qrCodeBase64,
            'locationQrCodeBase64' => $locationQrCodeBase64
        ]);

        return $pdf->stream('SuratJalan-' . $suratJalan->kode_surat_jalan . '.pdf');
    }

    /**
     * Method private untuk mengirim notifikasi WhatsApp (ADMIN + PELANGGAN)
     */
    private function _sendSuratJalanWhatsapp(SuratJalan $suratJalan)
    {
        $suratJalan->load('kontak');
        $kontak = $suratJalan->kontak;

        if (!$kontak) {
            Log::warning("Gagal kirim WA: Kontak tidak ditemukan untuk Surat Jalan ID {$suratJalan->id}");
            return;
        }

        // Data Umum
        Carbon::setLocale('id');
        $timestamp = $suratJalan->created_at->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i');

        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        $resiList = $packages->pluck('resi_number')->implode("\n");
        $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";

        // ==========================================
        // 1. SIAPKAN PESAN UNTUK ADMIN
        // ==========================================
        $adminTemplate = <<<TEXT
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

        $msgAdmin = str_replace(
            ['{WAKTU_INPUT}', '{NAMA_PENGIRIM}', '{KODE_SURAT_JALAN}', '{JUMLAH_PAKET}', '{NO_HP_PENGIRIM}', '{ALAMAT_PENGIRIM}', '{DAFTAR_RESI}', '{LINK_LOKASI}'],
            [$timestamp, $kontak->nama, $suratJalan->kode_surat_jalan, $suratJalan->jumlah_paket, $kontak->no_hp, $kontak->alamat, $resiList, $googleMapsUrl],
            $adminTemplate
        );

        // ==========================================
        // 2. SIAPKAN PESAN UNTUK PELANGGAN
        // ==========================================
        $pelangganTemplate = <<<TEXT
Halo Kak *{NAMA_PENGIRIM}* 👋,

Surat Jalan Anda berhasil dibuat! ✅

📜 No. SJ: *{KODE_SURAT_JALAN}*
📦 Total: *{JUMLAH_PAKET} Paket*
🕒 Waktu: {WAKTU_INPUT}

*Daftar Resi:*
{DAFTAR_RESI}

Terima kasih telah menggunakan *Sancaka Express*.
Paket Anda akan segera kami proses pickup/input. 🙏
TEXT;

        $msgPelanggan = str_replace(
            ['{NAMA_PENGIRIM}', '{KODE_SURAT_JALAN}', '{JUMLAH_PAKET}', '{WAKTU_INPUT}', '{DAFTAR_RESI}'],
            [$kontak->nama, $suratJalan->kode_surat_jalan, $suratJalan->jumlah_paket, $timestamp, $resiList],
            $pelangganTemplate
        );

        // ==========================================
        // 3. EKSEKUSI PENGIRIMAN
        // ==========================================
        try {
            // A. Kirim ke Admin (Hardcoded)
            $waNumber = '628819435180';
            //$waAdmin = '6285745808809';

            FonnteService::sendMessage($waNumber, $msgAdmin);
            //FonnteService::sendMessage($waAdmin, $msgAdmin);
            Log::info("WA Admin terkirim untuk SJ: {$suratJalan->kode_surat_jalan}");

            // B. Kirim ke Pelanggan (Dynamic)
            if (!empty($kontak->no_hp)) {
                // Format nomor HP (Ganti 08 jadi 628)
                $hpPelanggan = $kontak->no_hp;
                if (Str::startsWith($hpPelanggan, '0')) {
                    $hpPelanggan = '62' . substr($hpPelanggan, 1);
                }

                FonnteService::sendMessage($hpPelanggan, $msgPelanggan);
                Log::info("WA Pelanggan terkirim ke: {$hpPelanggan}");
            }

        } catch (\Exception $e) {
            Log::error("Gagal mengirim notifikasi WA (Surat Jalan): " . $e->getMessage());
        }
    }
}
