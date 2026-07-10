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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail; // Ditambahkan untuk pengiriman Email
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
            'email' => 'required|email|unique:kontaks,email',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan,Pribadi,Perusahaan',
            'instansi_perusahaan' => 'nullable|string|max:255',
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
     * Memperbarui profil kontak yang belum memiliki email/jenis kelamin.
     */
    public function updateKontakProfil(Request $request, $id)
    {
        $kontak = Kontak::findOrFail($id);
        
        $validated = $request->validate([
            'email' => 'required|email|unique:kontaks,email,' . $id,
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan,Pribadi,Perusahaan',
            'instansi_perusahaan' => 'nullable|string|max:255',
        ]);

        $kontak->update($validated);

        return response()->json([
            'success' => true, 
            'data' => $kontak,
            'message' => 'Data berhasil dilengkapi.'
        ]);
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
     * Membuat surat jalan dan mengirim notifikasi.
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

        // Kirim Notifikasi Email ke Admin Sancaka
        $this->_sendSuratJalanEmail($suratJalan);

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
     * Method private untuk mengirim notifikasi Email ke Admin
     */
    private function _sendSuratJalanEmail(SuratJalan $suratJalan)
    {
        $suratJalan->load('kontak');
        $kontak = $suratJalan->kontak;

        if (!$kontak) {
            Log::warning("Gagal kirim Email: Kontak tidak ditemukan untuk Surat Jalan ID {$suratJalan->id}");
            return;
        }

        Carbon::setLocale('id');
        $timestamp = $suratJalan->created_at->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y - H:i');

        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        // Gabungkan resi dengan tag <br> agar rapi di email HTML
        $resiList = $packages->pluck('resi_number')->implode("<br>");
        $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";

        // Membuat Body HTML langsung di sini tanpa file Blade
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #d9534f;'>⚠ Surat Jalan Baru Dibuat ⚠</h2>
                <p>Telah dibuat surat jalan baru oleh <strong>{$kontak->nama}</strong>.</p>
                
                <table style='width: 100%; max-width: 500px; border-collapse: collapse; margin-bottom: 15px;'>
                    <tr><td style='padding: 5px 0;'><strong>Waktu Input:</strong></td><td>{$timestamp}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>No. Surat Jalan:</strong></td><td>{$suratJalan->kode_surat_jalan}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>Jumlah Paket:</strong></td><td>{$suratJalan->jumlah_paket}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>No. WA Pengirim:</strong></td><td>{$kontak->no_hp}</td></tr>
                    <tr><td style='padding: 5px 0;'><strong>Alamat Pengirim:</strong></td><td>{$kontak->alamat}</td></tr>
                </table>

                <div style='background: #f9f9f9; padding: 10px; border-left: 4px solid #0275d8; margin-bottom: 15px;'>
                    <strong>Daftar Resi:</strong><br>
                    {$resiList}
                </div>

                <p>
                    <strong>Lokasi Pickup:</strong><br>
                    <a href='{$googleMapsUrl}' style='color: #0275d8; text-decoration: none;'>Buka di Google Maps &rarr;</a>
                </p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #999;'>Notifikasi Sistem Sancaka</p>
            </div>
        ";

        try {
            // Eksekusi pengiriman email HTML ke alamat statis & CC ke customer
            Mail::html($htmlBody, function ($message) use ($suratJalan, $kontak) {
                $message->to('tokosancaka@gmail.com')
                        ->subject("Surat Jalan Baru Dibuat - {$suratJalan->kode_surat_jalan}");
                
                // Tambahkan CC ke email customer jika datanya ada
                if (!empty($kontak->email)) {
                    $message->cc($kontak->email);
                }
            });

            Log::info("Email Admin & Customer terkirim untuk SJ: {$suratJalan->kode_surat_jalan}");

        } catch (\Exception $e) {
            Log::error("Gagal mengirim notifikasi Email (Surat Jalan): " . $e->getMessage());
        }
    }
}