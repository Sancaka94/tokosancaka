<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kontak;
use App\Models\SpxScan;
use App\Models\ScanHistory;
use App\Models\SuratJalan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use App\Models\ScannedPackage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
// use Milon\Barcode\DNS1D; // <-- TIDAK DIPAKAI LAGI
use Picqer\Barcode\BarcodeGeneratorPNG; // <--- KITA KEMBALI PAKAI INI

class ScanController extends Controller
{
    // ... (Semua fungsi lain dari showSpxScanner sampai registerKontakFromScan tetap sama) ...
    // =================================================================

    public function showSpxScanner()
    {
        return view('admin.public.spx');
    }

    public function handleSpxScan(Request $request)
    {
        $request->validate([
            'kontak_id' => 'required|exists:kontaks,id',
            'resi' => 'required|string|max:255|unique:spx_scans,resi',
        ], [
            'kontak_id.required' => 'Pilih nama Anda terlebih dahulu.',
            'resi.unique' => 'Resi ini sudah pernah di-scan.',
        ]);

        try {
            $kontak = Kontak::findOrFail($request->kontak_id);

            DB::transaction(function () use ($request, $kontak) {
                $spxScan = SpxScan::create([
                    'kontak_id' => $kontak->id,
                    'resi' => $request->resi,
                    'status' => 'Proses Pickup',
                ]);

                // Membuat riwayat scan awal
                ScanHistory::create([
                    'spx_scan_id' => $spxScan->id, // Pastikan ada kolom spx_scan_id di tabel scan_histories
                    'status' => 'Data Scan Dibuat',
                    'lokasi' => 'Sistem Customer',
                    'keterangan' => 'Menunggu pickup oleh kurir.',
                ]);
            });

            $jumlahPaket = SpxScan::where('kontak_id', $kontak->id)
                                    ->where('status', 'Proses Pickup')
                                    ->whereNull('surat_jalan_id')
                                    ->count();

            $responseData = [
                'nama_pengirim' => $kontak->nama,
                'nomor_resi' => $request->resi,
                'waktu_scan' => Carbon::now('Asia/Jakarta')->format('d M Y, H:i:s'),
                'jumlah_paket_pickup' => $jumlahPaket,
            ];

            return response()->json(['success' => true, 'data' => $responseData]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PERUBAHAN: Membuat record Surat Jalan dan mengembalikan kodenya via JSON.
     */
    public function createSpxSuratJalan(Request $request)
    {
        $request->validate([
            'kontak_id' => 'required|exists:kontaks,id',
            // Pastikan Anda juga mengirim lat/long dari Javascript jika diperlukan
            'latitude' => 'nullable|numeric', 
            'longitude' => 'nullable|numeric',
        ]);
        
        $kontak = Kontak::findOrFail($request->kontak_id);

        $packages = SpxScan::where('kontak_id', $kontak->id)
                            ->where('status', 'Proses Pickup')
                            ->whereNull('surat_jalan_id')
                            ->get();

        if ($packages->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada paket baru untuk dibuatkan surat jalan.'], 404);
        }

        $suratJalan = SuratJalan::create([
            'kode_surat_jalan' => 'SJL-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'kontak_id' => $kontak->id,
            'jumlah_paket' => $packages->count(),
            'status' => 'Menunggu Pickup Kurir',
            'latitude' => $request->latitude,   // <-- SIMPAN LATITUDE
            'longitude' => $request->longitude, // <-- SIMPAN LONGITUDE
        ]);

        SpxScan::whereIn('id', $packages->pluck('id'))->update(['surat_jalan_id' => $suratJalan->id]);

        return response()->json([
            'success' => true,
            'kode_surat_jalan' => $suratJalan->kode_surat_jalan,
            'nama_pengirim' => $kontak->nama,
            'jumlah_paket' => $packages->count(),
        ]);
    }

    /**
     * METODE BARU: Menghasilkan dan men-download PDF berdasarkan kode surat jalan.
     */
    public function downloadSpxSuratJalan($kode_surat_jalan)
    {
        $suratJalan = SuratJalan::with('kontak')->where('kode_surat_jalan', $kode_surat_jalan)->firstOrFail();
        $packages = SpxScan::where('surat_jalan_id', $suratJalan->id)->get();

        // FUNGSI INI JUGA KURANG VARIABEL, TAPI TIDAK DIPANGGIL OLEH ERROR ANDA
        // Seharusnya ini sama dengan 'generateSuratJalan' di bawah
        $pdf = DomPdf::loadView('admin.public.pdf.surat-jalan-spx', [
            'suratJalan' => $suratJalan, 
            'packages' => $packages
            // ... (kurang barcode & QR code)
        ]);
        
        $fileName = 'SURAT-JALAN-' . $suratJalan->kode_surat_jalan . '.pdf';

        return $pdf->stream($fileName);
    }

    public function searchKontak(Request $request)
    {
        $query = $request->input('query');
        if(empty($query)) return response()->json([]);

        $kontaks = Kontak::where('nama', 'LIKE', "%{$query}%")
                            ->orWhere('no_hp', 'LIKE', "%{$query}%")
                            ->limit(5)
                            ->get(['id', 'nama', 'no_hp']);

        return response()->json($kontaks);
    }

    // =================================================================
    // ROLE 2: KURIR - SCAN TERIMA PAKET DARI CUSTOMER (DISESUAIKAN)
    // =================================================================

    public function showCourierScanner()
    {
        return view('admin.scan.courier');
    }

    public function handleCourierScan(Request $request)
    {
        // ... (fungsi ini sepertinya OK)
        $request->validate(['kode_surat_jalan' => 'required|string|exists:surat_jalans,kode_surat_jalan']);

        try {
            $suratJalan = SuratJalan::where('kode_surat_jalan', $request->kode_surat_jalan)->firstOrFail();

            if ($suratJalan->status !== 'Menunggu Pickup Kurir') {
                return response()->json(['success' => false, 'message' => 'Surat jalan ini sudah pernah di-scan atau statusnya tidak valid.'], 409);
            }

            DB::transaction(function () use ($suratJalan) {
                // Update status Surat Jalan
                $suratJalan->status = 'Diterima Kurir';
                $suratJalan->discan_oleh_kurir_at = now();
                $suratJalan->save();

                // PENYEMPURNAAN: Update status semua SpxScan yang terkait
                $scans = SpxScan::where('surat_jalan_id', $suratJalan->id)->get();
                foreach ($scans as $scan) {
                    $scan->status = 'Diterima Kurir';
                    $scan->save();

                    // Membuat riwayat untuk setiap paket
                    ScanHistory::create([
                        'spx_scan_id' => $scan->id,
                        'status' => 'Diterima Kurir',
                        'lokasi' => 'Pickup Point',
                        'keterangan' => 'Paket telah di-pickup oleh kurir dari pengirim.',
                    ]);
                }
            });

            return response()->json(['success' => true, 'data' => [
                'kode_surat_jalan' => $suratJalan->kode_surat_jalan,
                'nama_pengirim' => $suratJalan->kontak->nama,
                'jumlah_paket' => $suratJalan->jumlah_paket,
                'waktu_scan' => Carbon::now('Asia/Jakarta')->format('d M Y, H:i:s'),
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    // =================================================================
    // ROLE 3: ADMIN - VALIDASI PAKET DI GUDANG (DISESUAIKAN)
    // =================================================================

    public function showAdminValidation()
    {
        return view('admin.scan.validation');
    }

    public function handleAdminValidation(Request $request)
    {
        // ... (fungsi ini sepertinya OK)
        $request->validate([
            'kode_surat_jalan' => 'required|string|exists:surat_jalans,kode_surat_jalan',
            'scanned_resi' => 'required|array|min:1',
            'scanned_resi.*' => 'required|string',
        ]);

        try {
            // PENYEMPURNAAN: Mengambil relasi ke spx_scans
            // Pastikan di Model SuratJalan ada relasi: public function spxScans() { return $this->hasMany(SpxScan::class); }
            $suratJalan = SuratJalan::with('spxScans')->where('kode_surat_jalan', $request->kode_surat_jalan)->firstOrFail();
            
            $scannedResi = $request->scanned_resi;
            $actualResi = $suratJalan->spxScans->pluck('resi')->toArray();

            if (count($scannedResi) !== $suratJalan->jumlah_paket) {
                return response()->json(['success' => false, 'message' => 'Jumlah paket tidak cocok! Seharusnya ' . $suratJalan->jumlah_paket . ', tapi Anda scan ' . count($scannedResi) . ' paket.'], 422);
            }

            $missingResi = array_diff($actualResi, $scannedResi);
            $extraResi = array_diff($scannedResi, $actualResi);

            if (!empty($missingResi) || !empty($extraResi)) {
                $errorMessage = "Resi tidak cocok! ";
                if(!empty($missingResi)) $errorMessage .= "Resi yang hilang: " . implode(', ', $missingResi) . ". ";
                if(!empty($extraResi)) $errorMessage .= "Resi tambahan yang tidak seharusnya ada: " . implode(', ', $extraResi) . ".";
                return response()->json(['success' => false, 'message' => $errorMessage], 422);
            }

            DB::transaction(function () use ($suratJalan) {
                // Update status Surat Jalan
                $suratJalan->status = 'Tervalidasi di Gudang';
                $suratJalan->divalidasi_oleh_admin_at = now();
                $suratJalan->save();
                
                // PENYEMPURNAAN: Update status semua SpxScan yang terkait
                foreach ($suratJalan->spxScans as $scan) {
                    $scan->status = 'Paket Tervalidasi di Gudang';
                    $scan->save();

                    // Membuat riwayat untuk setiap paket
                    ScanHistory::create([
                        'spx_scan_id' => $scan->id,
                        'status' => 'Tervalidasi di Gudang',
                        'lokasi' => 'Gudang Utama',
                        'keterangan' => 'Paket telah diterima dan divalidasi oleh admin gudang.',
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Validasi berhasil! Semua paket cocok.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }
    
    // =================================================================
    // HALAMAN BARU: PELACAKAN RIWAYAT SCAN (TRACKING)
    // =================================================================

    /**
     * Menampilkan halaman untuk melacak resi.
     */
    public function showTrackingPage()
    {
        return view('public.track.index'); // Akan kita buat view baru ini
    }

    /**
     * Menangani pencarian resi dan menampilkan hasilnya.
     */
    public function trackSpxPackage(Request $request)
    {
        $request->validate(['resi' => 'required|string']);

        $scan = SpxScan::with(['kontak', 'scanHistories']) // Eager load relasi
                        ->where('resi', $request->resi)
                        ->first();

        if (!$scan) {
            return redirect()->route('track.show')->with('error', 'Resi tidak ditemukan. Pastikan nomor resi sudah benar.');
        }

        return view('public.track.index', compact('scan'));
    }
    
    // =================================================================
    // METODE BARU: REGISTRASI PELANGGAN DARI HALAMAN SCAN
    // =================================================================

    
    /**
     * Menangani pendaftaran kontak baru via AJAX dari halaman scan.
     */
    public function registerKontakFromScan(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20|unique:kontaks,no_hp',
            'alamat' => 'required|string|max:255',
        ], [
            'no_hp.unique' => 'Nomor HP ini sudah terdaftar.',
        ]);

        try {
            $kontak = Kontak::create([
                'nama' => $validatedData['nama'],
                'no_hp' => $validatedData['no_hp'],
                'alamat' => $validatedData['alamat'],
                'tipe' => 'Pengirim',
            ]);

            return response()->json(['success' => true, 'data' => $kontak]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mendaftarkan kontak: ' . $e->getMessage()], 500);
        }
    }
    
    // =================================================================
    // FUNGSI INI YANG DIPANGGIL DAN MENYEBABKAN ERROR
    // =================================================================

    /**
     * GANTI FUNGSI LAMA ANDA DENGAN YANG INI
     */
    public function generateSuratJalan(Request $request)
    {
        $kode = $request->query('kode'); // Ambil KODE surat jalan
        if (!$kode) {
            abort(404, 'Parameter kode tidak ditemukan.');
        }

        // 1. AMBIL DATA SURAT JALAN ASLI DARI DATABASE
        // Ini akan mengambil data sebagai Eloquent Model, BUKAN stdClass
        // dan akan berisi latitude/longitude jika ada di database
        $suratJalan = SuratJalan::with('kontak')
                            ->where('kode_surat_jalan', $kode)
                            ->firstOrFail();

        // 2. AMBIL PACKAGES BERDASARKAN SURAT JALAN ID
        // (Mengganti logika lama yang mengambil dari resi)
        $packages = ScannedPackage::where('surat_jalan_id', $suratJalan->id)->get();
        
        // Jika packages kosong (mungkin data lama), coba ambil dari resi
        if ($packages->isEmpty() && $request->has('resi')) {
             $resiArray = explode(',', $request->query('resi'));
             $packages = ScannedPackage::with('kontak')
                           ->whereIn('resi_number', $resiArray)
                           ->get();
        }

        try {
            // 3. Membuat Barcode Persegi Panjang (Code 128)
            // --- PERBAIKAN DI SINI: Kembali menggunakan Picqer ---
            $generator = new BarcodeGeneratorPNG();
            $barcodeRectBase64 = base64_encode(
                $generator->getBarcode(
                    $suratJalan->kode_surat_jalan,
                    $generator::TYPE_CODE_128,
                    2,  // Tinggi
                    60  // Lebar (Anda bisa sesuaikan)
                )
            );
            // --- Akhir Perbaikan ---
        
            // 4. Membuat QR Code Tanda Tangan
            $qrCodeBase64 = base64_encode(
                QrCode::format('png')
                    ->size(80)
                    ->generate($suratJalan->kode_surat_jalan)
            );
            
            // 5. MEMBUAT QR LOKASI (YANG HILANG)
            $locationQrCodeBase64 = null; // Definisikan
            if ($suratJalan->latitude && $suratJalan->longitude) { // Cek dari data asli
                $googleMapsUrl = "https://www.google.com/maps?q={$suratJalan->latitude},{$suratJalan->longitude}";
                $locationQrCodeBase64 = base64_encode(
                    QrCode::format('png')->size(80)->generate($googleMapsUrl)
                );
            }

        } catch (\Exception $e) {
            Log::error('Gagal membuat barcode/QR: ' . $e->getMessage());
            $barcodeRectBase64 = null;
            $qrCodeBase64 = null;
            $locationQrCodeBase64 = null; // Pastikan null jika gagal
        }
        
        // 6. KIRIM SEMUA VARIABEL KE VIEW
        $pdf = DomPdf::loadView('public.scan.pdf.surat-jalan-spx', [
            'suratJalan' => $suratJalan,
            'packages' => $packages,
            'barcodeRectBase64' => $barcodeRectBase64,   // <-- Variabel ini dikirim
            'qrCodeBase64' => $qrCodeBase64,           // <-- Variabel ini dikirim
            'locationQrCodeBase64' => $locationQrCodeBase64 // <-- Variabel ini SEKARANG dikirim
        ]);

        return $pdf->stream('SURAT-JALAN-' . $suratJalan->kode_surat_jalan . '.pdf');
    }
}

