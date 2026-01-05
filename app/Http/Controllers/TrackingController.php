<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // WAJIB
use Illuminate\Support\Facades\Http;
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
        // Debug Log
        Log::info("NORMALISASI DATA (WITH IMAGES):", [
            'has_data' => isset($rawResponse['data']),
        ]);

        // ============================================================
        // 1. MAPPING DATA
        // ============================================================
        
        $dataRoot = $rawResponse['data'] ?? [];
        
        // Ambil History & Summary
        $histories = $dataRoot['histories'] ?? ($rawResponse['histories'] ?? []);
        $details = $dataRoot['summary'] ?? ($rawResponse['details'] ?? []);
        $apiTextStatus = $rawResponse['text'] ?? ($dataRoot['text'] ?? '-');

        // ============================================================
        // 2. NORMALISASI HISTORY (+ GAMBAR)
        // ============================================================
        $normalizedHistories = collect($histories)->map(function ($history) use ($details) {
            
            // A. Parsing Tanggal
            try {
                $timestampWIB = Carbon::parse($history['created_at'])->timezone('Asia/Jakarta');
            } catch (\Exception $e) {
                $timestampWIB = now();
            }

            // B. Bersihkan Teks Status
            $statusText = $history['status'] ?? '-';
            $statusText = preg_replace('/\s\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}\s\|/i', '', $statusText);

            // C. AMBIL GAMBAR (DARI LOG ANDA, LETAKNYA DI 'images')
            // Pastikan formatnya array. Jika null, jadikan array kosong.
            $evidenceImages = $history['images'] ?? [];
            
            // Opsional: Kadang ada juga di 'attachment'
            if (empty($evidenceImages) && isset($history['attachment'])) {
                $evidenceImages = [$history['attachment']];
            }

            return (object)[
                'status' => $statusText,
                'lokasi' => $history['city_name'] ?? ($details['destination']['city'] ?? 'Hub/Gudang'), 
                'keterangan' => $history['note'] ?? ($history['status'] ?? ''),
                'created_at' => $timestampWIB,
                // --- TAMBAHAN PENTING: ARRAY GAMBAR ---
                'images' => $evidenceImages, 
            ];
        })->toArray();

        // ============================================================
        // 3. GABUNG STATUS INTERNAL
        // ============================================================
        if ($pesanan->created_at) {
            $createdHistory = (object)[
                'status' => 'Pesanan Dibuat',
                'lokasi' => 'Sistem Internal',
                'keterangan' => 'Data pesanan masuk ke sistem.',
                'created_at' => Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta'), 
                'images' => [], // Internal biasanya tidak ada gambar
            ];

            $hasDuplicate = collect($normalizedHistories)->contains(function ($h) use ($createdHistory) {
                return $h->created_at->diffInMinutes($createdHistory->created_at) < 2 || stripos($h->status, 'dibuat') !== false;
            });

            if (!$hasDuplicate) {
                 $normalizedHistories[] = $createdHistory;
            }
        }

        // Urutkan (Terbaru di atas)
        $sortedHistories = collect($normalizedHistories)->sortByDesc('created_at')->values();

        // ============================================================
        // 4. RETURN DATA FINAL
        // ============================================================
        $get = function($arr, $key1, $key2) { return $arr[$key1][$key2] ?? null; };

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $details['awb'] ?? $pesanan->resi_aktual,
            
            'pengirim' => $get($details, 'origin', 'name') ?? $pesanan->sender_name ?? '-',
            'alamat_pengirim' => $get($details, 'origin', 'address') ?? $pesanan->sender_address ?? '-',
            'no_pengirim' => $get($details, 'origin', 'phone') ?? $pesanan->sender_phone ?? '-',
            
            'penerima' => $get($details, 'destination', 'name') ?? $pesanan->receiver_name ?? '-',
            'alamat_penerima' => $get($details, 'destination', 'address') ?? $pesanan->receiver_address ?? '-',
            'no_penerima' => $get($details, 'destination', 'phone') ?? $pesanan->receiver_phone ?? '-',
            
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
        Log::info("=== CETAK THERMAL START: {$resi} ===");

        $pesanan = null;

        // -----------------------------------------------------------
        // 1. CEK DB 1 (MODEL: PESANAN - INTERNAL EKSPEDISI)
        // -----------------------------------------------------------
        // Model Pesanan (paket manual) biasanya sudah lengkap propertinya
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('resi_aktual', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->first();

        if ($pesanan) {
            Log::info("KETEMU di DB1 (Table Pesanan). ID: {$pesanan->id}");
        } 
        
        // -----------------------------------------------------------
        // 2. CEK DB 1 (MODEL: ORDER - TOKO ONLINE)
        // -----------------------------------------------------------
        if (!$pesanan) {
            Log::info("Cek DB1 Model Order...");
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->first();

            if ($orderModel) {
                Log::info("KETEMU di DB1 (Table Order)");
                
                // MAPPING DATA LENGKAP
                $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'nomor_invoice' => $orderModel->invoice_number,
                    'status' => $orderModel->status,
                    
                    // --- PENGIRIM (DILENGKAPI) ---
                    'sender_name' => $orderModel->store->name ?? 'N/A',
                    'sender_phone' => $orderModel->store->user->no_wa ?? '-',
                    'sender_address' => $orderModel->store->address_detail ?? '-',
                    'sender_village' => $orderModel->store->village ?? '',       // <--- TAMBAHAN
                    'sender_district' => $orderModel->store->district ?? '',     // <--- TAMBAHAN
                    'sender_regency' => $orderModel->store->regency ?? '',       // <--- TAMBAHAN
                    'sender_province' => $orderModel->store->province ?? '',     // <--- TAMBAHAN
                    'sender_postal_code' => $orderModel->store->postal_code ?? '', // <--- TAMBAHAN
                    
                    // --- PENERIMA ---
                    'receiver_name' => $orderModel->user->nama_lengkap ?? 'N/A',
                    'receiver_phone' => $orderModel->user->no_wa ?? '-',
                    'receiver_address' => $orderModel->shipping_address ?? '-',
                    'receiver_district' => $orderModel->user->district ?? '',
                    'receiver_city' => $orderModel->user->regency ?? '',
                    'receiver_province' => $orderModel->user->province ?? '',
                    'receiver_postal_code' => $orderModel->user->postal_code ?? '',
                    
                    // --- LAINNYA ---
                    'expedition' => $orderModel->courier ?? 'JNE',
                    'jasa_ekspedisi_aktual' => $orderModel->courier ?? 'JNE',
                    'service_type' => $orderModel->service_type ?? 'REG',
                    'weight' => $orderModel->total_weight ?? 1,
                    
                    'created_at' => $orderModel->created_at,
                    'updated_at' => $orderModel->updated_at,
                    'cod_amount' => 0, 
                    'payment_method' => $orderModel->payment_method ?? 'Transfer',
                ];
            }
        }

        // -----------------------------------------------------------
        // 3. CEK DB 2 (DATABASE PERCETAKAN)
        // -----------------------------------------------------------
        if (!$pesanan) {
            Log::info("Cek DB2 (Percetakan)...");
            try {
                $orderPercetakan = DB::connection('mysql_second')
                    ->table('orders') 
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($orderPercetakan) {
                    Log::info("KETEMU di DB2!");

                    // MAPPING DATA LENGKAP DB2
                    $pesanan = (object)[
                        'resi' => $orderPercetakan->shipping_ref ?? $orderPercetakan->order_number,
                        'nomor_invoice' => $orderPercetakan->order_number,
                        'status' => $orderPercetakan->status,
                        
                        // --- PENGIRIM (HARDCODE PERCETAKAN SANCAKA) ---
                        'sender_name' => 'Sancaka Percetakan',
                        'sender_phone' => '08819435180',
                        'sender_address' => 'Jl.Dr.Wahidin No.18 A',
                        'sender_village' => 'Ketanggi',      // <--- TAMBAHAN
                        'sender_district' => 'Ngawi',        // <--- TAMBAHAN
                        'sender_regency' => 'Kab. Ngawi',    // <--- TAMBAHAN
                        'sender_province' => 'Jawa Timur',   // <--- TAMBAHAN
                        'sender_postal_code' => '63211',     // <--- TAMBAHAN
                        
                        // --- PENERIMA ---
                        'receiver_name' => $orderPercetakan->customer_name ?? 'Pelanggan',
                        'receiver_phone' => $orderPercetakan->customer_phone ?? '-',
                        'receiver_address' => $orderPercetakan->destination_address ?? '-',
                        'receiver_district' => '', 
                        'receiver_city' => '',
                        'receiver_province' => '',
                        'receiver_postal_code' => '',
                        
                        // --- LAINNYA ---
                        'expedition' => $orderPercetakan->courier_service ?? 'Express',
                        'jasa_ekspedisi_aktual' => $orderPercetakan->courier_service ?? 'Express',
                        'service_type' => 'REG',
                        'weight' => 1,
                        
                        'created_at' => $orderPercetakan->created_at,
                        'updated_at' => $orderPercetakan->updated_at ?? $orderPercetakan->created_at,
                        'cod_amount' => 0,
                        'payment_method' => $orderPercetakan->payment_method ?? 'Manual',
                    ];
                }
            } catch (\Exception $e) {
                Log::error("ERROR DB2: " . $e->getMessage());
            }
        }

        // -----------------------------------------------------------
        // 4. FINAL
        // -----------------------------------------------------------
        if (!$pesanan) {
            abort(404, 'Data Resi tidak ditemukan.');
        }

        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }
    
    public function refreshTimeline()
    {
        return redirect()->back()->with('success', 'Timeline diperbarui');
    }
}