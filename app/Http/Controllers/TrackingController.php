<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // WAJIB
use Carbon\Carbon;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\Order;
use App\Models\ScannedPackage;
use App\Services\KiriminAjaService;

class TrackingController extends Controller
{
    protected $kiriminAjaService;

    public function __construct(KiriminAjaService $kiriminAjaService)
    {
        $this->kiriminAjaService = $kiriminAjaService;
    }

    public function showTrackingPage(Request $request)
    {
        if ($request->has('resi')) {
            return $this->trackPackage($request);
        }
        return view('public.tracking.index');
    }

    /**
     * LOGIC UTAMA TRACKING
     */
    public function trackPackage(Request $request)
    {
        $request->validate(['resi' => 'required|string|max:255']);
        $resi = $request->input('resi');
        
        // LOG START
        Log::info("==========================================");
        Log::info("TRACKING STARTED: Mencari Resi [{$resi}]");

        $result = null;
        $pesanan = null;

        // ==========================================
        // TAHAP 1: CARI DI DATABASE UTAMA
        // ==========================================
        Log::info("STEP 1: Mencari di Database Utama (Tabel Pesanan)...");
        
        $pesanan = Pesanan::where('resi', $resi)
                  ->orWhere('resi_aktual', $resi)
                  ->orWhere('nomor_invoice', $resi)
                  ->first();

        if ($pesanan) {
            Log::info("KETEMU: Data ditemukan di Model 'Pesanan' (DB1). ID: {$pesanan->id}");
        } else {
            Log::info("SKIP: Tidak ada di 'Pesanan'. Mencari di Model 'Order'...");
            
            $orderModel = Order::with(['store', 'user'])
                        ->where('shipping_reference', $resi)
                        ->orWhere('invoice_number', $resi)
                        ->first();
            
            if ($orderModel) {
                Log::info("KETEMU: Data ditemukan di Model 'Order' (DB1). ID: {$orderModel->id}");
                // Standarisasi Objek
                $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'resi_aktual' => $orderModel->shipping_reference,
                    'nomor_invoice' => $orderModel->invoice_number,
                    'status' => $orderModel->status,
                    'sender_name' => $orderModel->store->name ?? 'N/A',
                    'sender_address' => $orderModel->store->address_detail ?? 'N/A',
                    'sender_phone' => $orderModel->store->user->no_wa ?? '-',
                    'receiver_name' => $orderModel->user->nama_lengkap ?? 'N/A',
                    'receiver_address' => $orderModel->shipping_address ?? 'N/A',
                    'receiver_phone' => $orderModel->user->no_wa ?? '-',
                    'jasa_ekspedisi_aktual' => $orderModel->courier ?? null,
                    'service_type' => explode('-', $orderModel->service_type)[0] ?? 'regular',
                    'created_at' => $orderModel->created_at,
                ];
            } else {
                Log::warning("GAGAL: Resi tidak ditemukan sama sekali di Database Utama (DB1).");
            }
        }

        // ==========================================
        // TAHAP 2: JIKA KOSONG, CARI DI DATABASE KEDUA
        // ==========================================
        if (!$pesanan) {
            Log::info("STEP 2: Beralih ke Database Kedua (mysql_second)...");
            
            try {
                // Cek koneksi dulu (opsional, tapi bagus untuk debug)
                // DB::connection('mysql_second')->getPdo(); 

                $orderPercetakan = DB::connection('mysql_second')
                                ->table('orders')
                                ->where('order_number', $resi)
                                ->orWhere('shipping_ref', $resi)
                                ->first();

                if ($orderPercetakan) {
                    Log::info("KETEMU: Data ditemukan di Database Kedua (Percetakan). ID: {$orderPercetakan->id}");
                    
                    $pesanan = (object)[
                        'resi' => $orderPercetakan->shipping_ref ?? $orderPercetakan->order_number,
                        'resi_aktual' => $orderPercetakan->shipping_ref,
                        'nomor_invoice' => $orderPercetakan->order_number,
                        'status' => $orderPercetakan->status,
                        'sender_name' => 'Sancaka Percetakan',
                        'sender_address' => 'Jl.Dr.Wahidin No.18 A Ngawi',
                        'sender_phone' => '08819435180',
                        'receiver_name' => $orderPercetakan->customer_name ?? 'Pelanggan',
                        'receiver_address' => $orderPercetakan->destination_address ?? '-',
                        'receiver_phone' => $orderPercetakan->customer_phone ?? '-',
                        'jasa_ekspedisi_aktual' => $orderPercetakan->courier_service ?? 'JNE/J&T',
                        'service_type' => 'regular',
                        'created_at' => $orderPercetakan->created_at,
                    ];
                } else {
                    Log::warning("GAGAL: Resi juga tidak ditemukan di Database Kedua.");
                }

            } catch (\Exception $e) {
                Log::error("CRITICAL ERROR DB2: Gagal koneksi ke mysql_second. Pesan: " . $e->getMessage());
            }
        }

        // ==========================================
        // TAHAP 3: EKSEKUSI API KIRIMINAJA
        // ==========================================
        if ($pesanan) {
            $nomorResiApi = $pesanan->resi_aktual ?? $pesanan->resi;
            Log::info("STEP 3: Mengirim Request ke API KiriminAja untuk Resi: {$nomorResiApi}");

            // Call Service
            $trackingData = $this->kiriminAjaService->trackPackage($nomorResiApi);
            
            // Log raw response status (tanpa body lengkap agar tidak spam)
            $statusApi = isset($trackingData['status']) && $trackingData['status'] ? 'SUCCESS' : 'FAILED';
            Log::info("API RESPONSE STATUS: {$statusApi}");

            // Normalisasi
            $result = $this->normalizeKiriminAjaResponse($trackingData, $pesanan);
        } else {
            Log::info("SKIP API: Karena data pesanan tidak ditemukan di DB1 maupun DB2.");
        }

        // ==========================================
        // TAHAP 4: FALLBACK LOKAL (SPX / SCAN MANUAL)
        // ==========================================
        if (!$result) {
            Log::info("STEP 4: Mencari di Tabel Scan Lokal (Fallback)...");
            $result = $this->checkLocalScans($resi);
            
            if($result) {
                Log::info("KETEMU: Data ditemukan di Scan Lokal.");
            } else {
                Log::info("GAGAL: Data tidak ditemukan dimanapun (DB1, DB2, Local Scan).");
            }
        }

        Log::info("TRACKING FINISHED.\n");

        if ($result) {
            return view('public.tracking.index', compact('result'));
        }

        return redirect()->route('tracking.index')->with('error', "Nomor resi '{$resi}' tidak ditemukan di sistem kami.");
    }
    
    private function normalizeKiriminAjaResponse(array $rawResponse, $pesanan): array
    {
        // Debugging di Log untuk memastikan isi datanya
        Log::info("RAW RESPONSE DARI API (DEBUG):", $rawResponse);

        // -------------------------------------------------------------
        // 1. CARI DATA HISTORY (AGRESIF)
        // Kita cari array 'histories' dimanapun dia bersembunyi
        // -------------------------------------------------------------
        $histories = [];
        
        if (!empty($rawResponse['histories'])) {
            // Jika ada di root (langsung dari API mentah)
            $histories = $rawResponse['histories'];
        } elseif (!empty($rawResponse['data']['histories'])) {
            // Jika dibungkus oleh Service (umumnya struktur: ['data' => ['histories' => ...]])
            $histories = $rawResponse['data']['histories'];
        } elseif (!empty($rawResponse['details']['history'])) {
            // Kadang ada API yang taruh di details
            $histories = $rawResponse['details']['history'];
        }

        // -------------------------------------------------------------
        // 2. CARI DATA UTAMA (Text, Details)
        // -------------------------------------------------------------
        // Cek text di root, atau di dalam data, atau default '-'
        $apiTextStatus = $rawResponse['text'] ?? ($rawResponse['data']['text'] ?? '-');
        
        // Cek details di root, atau di dalam data
        $details = $rawResponse['details'] ?? ($rawResponse['data']['summary'] ?? []);

        // -------------------------------------------------------------
        // 3. OLAH HISTORY MENJADI ARRAY RAPI
        // -------------------------------------------------------------
        $normalizedHistories = collect($histories)->map(function ($history) use ($details) {
            // Pastikan format tanggal aman
            try {
                $timestampWIB = Carbon::parse($history['created_at'])->timezone('Asia/Jakarta');
            } catch (\Exception $e) {
                $timestampWIB = now(); // Fallback jika tanggal error
            }
            
            // Bersihkan teks status (hapus tanggal ganda yang menempel di teks)
            $statusText = $history['status'] ?? '-';
            $statusText = preg_replace('/\s\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}\s\|/i', '', $statusText);

            return (object)[
                'status' => $statusText,
                'lokasi' => $history['city_name'] ?? ($details['destination']['city'] ?? '-'), // Coba cari kota di history
                'keterangan' => $history['note'] ?? ($history['status'] ?? ''),
                'created_at' => $timestampWIB, 
            ];
        })->toArray();

        // -------------------------------------------------------------
        // 4. GABUNGKAN DENGAN HISTORY INTERNAL (PESANAN DIBUAT)
        // -------------------------------------------------------------
        if ($pesanan->created_at) {
            $createdHistory = (object)[
                'status' => 'Pesanan Dibuat',
                'lokasi' => 'Sistem Internal',
                'keterangan' => 'Resi masuk ke sistem Sancaka Express.',
                'created_at' => Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta'), 
            ];
            
            // Cek duplikat agar tidak muncul double jika API sudah mencatat "Picked Up" di jam yang sama
            $hasCreated = collect($normalizedHistories)->contains(function ($h) {
                return stripos($h->status, 'dibuat') !== false || stripos($h->status, 'created') !== false;
            });

            if (!$hasCreated) {
                 $normalizedHistories[] = $createdHistory;
            }
        }

        // Urutkan dari yang paling BARU ke LAMA
        $sortedHistories = collect($normalizedHistories)->sortByDesc('created_at')->values();

        // -------------------------------------------------------------
        // 5. SIAPKAN DATA PENGIRIM & PENERIMA (FALLBACK KE DB JIKA API KOSONG)
        // -------------------------------------------------------------
        // Helper kecil untuk mengambil data bersarang tanpa error
        $getDetail = function($key1, $key2) use ($details) {
            return $details[$key1][$key2] ?? null;
        };

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $details['awb'] ?? $pesanan->resi_aktual,
            
            // Pengirim: Prioritas API -> DB
            'pengirim' => $getDetail('origin', 'name') ?? $pesanan->sender_name ?? '-',
            'alamat_pengirim' => $getDetail('origin', 'address') ?? $pesanan->sender_address ?? '-',
            'no_pengirim' => $getDetail('origin', 'phone') ?? $pesanan->sender_phone ?? '-',
            
            // Penerima: Prioritas API -> DB
            'penerima' => $getDetail('destination', 'name') ?? $pesanan->receiver_name ?? '-',
            'alamat_penerima' => $getDetail('destination', 'address') ?? $pesanan->receiver_address ?? '-',
            'no_penerima' => $getDetail('destination', 'phone') ?? $pesanan->receiver_phone ?? '-',
            
            // Status Akhir
            'status' => $apiTextStatus, 
            
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $sortedHistories,
            'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual,
        ];
    }

    private function checkLocalScans($resi)
    {
        // 1. Cek SPX Scan
        $spxScan = SpxScan::with('kontak')->where('resi', $resi)->first();
        if ($spxScan) {
             Log::info("Found in SpxScan Table.");
             return [
                'is_pesanan' => false,
                'resi' => $spxScan->resi,
                'pengirim' => $spxScan->kontak->nama ?? 'N/A',
                'alamat_pengirim' => $spxScan->kontak->alamat ?? 'N/A',
                'penerima' => 'Agen SPX Express (Sancaka Express)',
                'alamat_penerima' => 'Jl.Dr.Wahidin No.18 A Ngawi',
                'status' => $spxScan->status,
                'tanggal_dibuat' => $spxScan->created_at,
                'histories' => collect([(object)[
                    'status' => $spxScan->status,
                    'lokasi' => 'SPX Ngawi',
                    'keterangan' => 'Paket telah di-scan oleh pengirim.',
                    'created_at' => $spxScan->created_at
                ]]),
            ];
        }

        // 2. Cek Scanned Packages
        $scannedHistories = ScannedPackage::with(['user', 'kontak'])
                                ->where('resi_number', $resi)
                                ->orderBy('created_at', 'desc')
                                ->get();
        
        if ($scannedHistories->isNotEmpty()) {
            Log::info("Found in ScannedPackages Table.");
            $latest = $scannedHistories->first();
            $first = $scannedHistories->last();
            $senderName = $first->kontak->nama ?? ($first->user->name ?? 'Mitra Sancaka');

            return [
                'is_pesanan' => false,
                'resi' => $latest->resi_number,
                'pengirim' => $senderName,
                'alamat_pengirim' => '-',
                'penerima' => 'Agen Drop Point',
                'alamat_penerima' => 'Gudang Sancaka',
                'status' => $latest->status,
                'tanggal_dibuat' => $first->created_at,
                'histories' => $scannedHistories->map(function ($item) {
                    return (object)[
                        'status' => $item->status,
                        'lokasi' => 'Gudang Sancaka',
                        'keterangan' => 'Paket diproses manual.',
                        'created_at' => $item->created_at
                    ];
                }),
            ];
        }

        return null;
    }

    public function cetakThermal($resi)
    {
        Log::info("Cetak Thermal Request: {$resi}");
        $pesanan = Pesanan::where('resi', $resi)->orWhere('nomor_invoice', $resi)->first();
        if (!$pesanan) {
            Log::error("Cetak Thermal Gagal: Pesanan tidak ditemukan.");
            abort(404);
        }
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }
    
    public function refreshTimeline()
    {
        return redirect()->back()->with('success', 'Timeline diperbarui');
    }
}