<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\Order;
use App\Models\ScannedPackage;
use App\Models\RsudOrderObat;
use App\Models\ScanHistory;
use App\Models\ReturnOrder; // <--- WAJIB TAMBAHKAN IMPORT INI
use App\Services\KiriminAjaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Helpers\ShippingHelper;

class TrackingController extends Controller
{
    /**
     * Menampilkan halaman tracking utama.
     */
    public function showTrackingPage(Request $request)
    {
        if ($request->has('resi')) {
            return $this->trackPackage($request);
        }
        return view('public.tracking.index');
    }

    /**
     * [TRACKING PUBLIC]
     * Logika: DB1 -> DB1 (Retur) -> DB2 (Percetakan) -> SPX -> ScannedPackage
     */
    public function trackPackage(Request $request)
    {
        $request->validate(['resi' => 'required|string|max:255']);
        $resi = $request->input('resi');
        $result = null;

        // ==========================================================
        // 1. CARI DI TABEL PESANAN & ORDER (DB UTAMA)
        // ==========================================================
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('resi_aktual', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->orWhere('shipping_ref', $resi)
            ->first();

        if (!$pesanan) {
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->first();

            if ($orderModel) {
                // Gunakan Helper untuk parsing layanan Order DB1
                $shipInfo = ShippingHelper::parseShippingMethod($orderModel->courier . ' ' . ($orderModel->service_type ?? 'REG'));

                $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'resi_aktual' => $orderModel->shipping_reference,
                    'nomor_invoice' => $orderModel->invoice_number,
                    'sender_name' => $orderModel->store->name ?? 'N/A',
                    'sender_address' => $orderModel->store->address_detail ?? 'N/A',
                    'sender_province' => $orderModel->store->province ?? 'N/A',
                    'sender_regency' => $orderModel->store->regency ?? 'N/A',
                    'sender_district' => $orderModel->store->district ?? 'N/A',
                    'sender_village' => $orderModel->store->village ?? 'N/A',
                    'sender_postal_code' => $orderModel->store->postal_code ?? 'N/A',
                    'sender_phone' => $orderModel->store->user->no_wa ?? 'N/A',
                    'receiver_name' => $orderModel->user->nama_lengkap ?? 'N/A',
                    'receiver_address' => $orderModel->shipping_address ?? 'N/A',
                    'receiver_province' => $orderModel->user->province ?? 'N/A',
                    'receiver_regency' => $orderModel->user->regency ?? 'N/A',
                    'receiver_district' => $orderModel->user->district ?? 'N/A',
                    'receiver_village' => $orderModel->user->village ?? 'N/A',
                    'receiver_postal_code' => $orderModel->user->postal_code ?? 'N/A',
                    'receiver_phone' => $orderModel->user->no_wa ?? 'N/A',
                    'status' => $orderModel->status ?? 'N/A',
                    'jasa_ekspedisi_aktual' => $shipInfo['name'],
                    'service_type' => $shipInfo['service_name'],
                    'created_at' => $orderModel->created_at,
                ];
            }
        }

        // ==========================================================
        // 1.5. CARI DI TABEL RETURN ORDER (RESI RETUR)
        // ==========================================================
        if (!$pesanan) {
            $returnOrder = ReturnOrder::where('new_resi', $resi)->first();

            if ($returnOrder && $returnOrder->new_resi !== 'PROSES-PICKUP') {
                // Ambil data order aslinya untuk mengambil data pembeli dan toko
                $orderModel = Order::with(['store.user', 'user'])->find($returnOrder->order_id);

                if ($orderModel) {
                    $shipInfo = ShippingHelper::parseShippingMethod($returnOrder->courier . ' REG');

                    $pesanan = (object)[
                        'resi' => $returnOrder->new_resi,
                        'resi_aktual' => $returnOrder->new_resi,
                        // KiriminAja API bisa melacak menggunakan no resi (AWB) langsung
                        'nomor_invoice' => $returnOrder->new_resi,

                        // KARENA RETUR: PENGIRIM = PEMBELI
                        'sender_name' => $orderModel->user->nama_lengkap ?? 'Pembeli (Retur)',
                        'sender_address' => $orderModel->shipping_address ?? 'N/A',
                        'sender_province' => $orderModel->user->province ?? 'N/A',
                        'sender_regency' => $orderModel->user->regency ?? 'N/A',
                        'sender_district' => $orderModel->user->district ?? 'N/A',
                        'sender_village' => $orderModel->user->village ?? 'N/A',
                        'sender_postal_code' => $orderModel->user->postal_code ?? 'N/A',
                        'sender_phone' => $orderModel->user->no_wa ?? 'N/A',

                        // KARENA RETUR: PENERIMA = TOKO
                        'receiver_name' => $orderModel->store->name ?? 'Toko',
                        'receiver_address' => $orderModel->store->address_detail ?? 'N/A',
                        'receiver_province' => $orderModel->store->province ?? 'N/A',
                        'receiver_regency' => $orderModel->store->regency ?? 'N/A',
                        'receiver_district' => $orderModel->store->district ?? 'N/A',
                        'receiver_village' => $orderModel->store->village ?? 'N/A',
                        'receiver_postal_code' => $orderModel->store->postal_code ?? 'N/A',
                        'receiver_phone' => $orderModel->store->user->no_wa ?? 'N/A',

                        'status' => 'Pengembalian Barang (Retur)',
                        'jasa_ekspedisi_aktual' => $shipInfo['name'] ?? $returnOrder->courier,
                        'service_type' => $shipInfo['service_name'] ?? 'REG',
                        'created_at' => $returnOrder->created_at,
                    ];
                }
            }
        }

       // PROSES API EKPEDISI (JIKA DB1 / RETUR KETEMU)
        if ($pesanan) {
            // Ambil string nama ekspedisi untuk mendeteksi Deliveree
            $expeditionRaw = strtolower($pesanan->expedition ?? $pesanan->jasa_ekspedisi_aktual ?? $pesanan->service_type ?? '');

            // ==========================================================
            // LOGIKA CABANG 1: JIKA EKSPEDISI ADALAH DELIVEREE
            // ==========================================================
            if (str_contains($expeditionRaw, 'deliveree')) {
                $result = $this->trackDeliveree($pesanan);
            } 

            elseif (str_contains($expeditionRaw, 'lalamove')) {
                $result = $this->trackLalamove($pesanan);
            }

            // ==========================================================
            // LOGIKA CABANG 2: JIKA EKSPEDISI LAINNYA (VIA KIRIMINAJA)
            // ==========================================================
            else {
                $kiriminAja = new KiriminAjaService();
                $orderId = $pesanan->nomor_invoice ?? $pesanan->resi;
                $serviceType = $pesanan->service_type ?? 'regular';

                if (str_contains($serviceType, '-')) {
                    $serviceType = explode('-', $serviceType)[0];
                }

                $trackingData = $kiriminAja->track($serviceType, $orderId);

                if ($trackingData && ($trackingData['status'] ?? false)) {
                     $result = $this->normalizeKiriminAjaResponse($trackingData, $pesanan);
                } else {
                     $result = [
                        'is_pesanan' => true,
                        'resi' => $pesanan->resi,
                        'pengirim' => $pesanan->sender_name ?? 'N/A',
                        'alamat_pengirim' => $pesanan->sender_address ?? '-',
                        'penerima' => $pesanan->receiver_name ?? 'N/A',
                        'alamat_penerima' => $pesanan->receiver_address ?? '-',
                        'no_pengirim' => $pesanan->sender_phone ?? '-',
                        'no_penerima' => $pesanan->receiver_phone ?? '-',
                        'status' => $pesanan->status,
                        'tanggal_dibuat' => $pesanan->created_at,
                        'histories' => [],
                        'resi_aktual' => $pesanan->resi_aktual,
                     ];
                }
            }
        }

        // ==========================================================
        // 2. CARI DI DB 2 (PERCETAKAN) - UPDATE LOGIKA BARU
        // ==========================================================
        if (!$result) {
            try {
                $percetakan = DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($percetakan) {
                    $rawService = $percetakan->courier_service ?? 'Internal';
                    $shipInfo = ShippingHelper::parseShippingMethod($rawService);

                    $cleanService = str_replace($shipInfo['courier_name'], '', $shipInfo['service_name']);
                    $cleanService = trim($cleanService);
                    if (empty($cleanService)) $cleanService = 'Regular';

                    $displayEkspedisi = $shipInfo['courier_name'] . ' - ' . strtoupper($cleanService);

                    $fakeHistory = collect([
                        (object)[
                            'status' => 'Pesanan Dibuat',
                            'lokasi' => 'Percetakan Sancaka',
                            'keterangan' => 'Pesanan masuk ke sistem percetakan.',
                            'created_at' => Carbon::parse($percetakan->created_at)
                        ],
                        (object)[
                            'status' => $percetakan->status,
                            'lokasi' => 'Percetakan Sancaka',
                            'keterangan' => 'Status terkini: ' . $percetakan->status,
                            'created_at' => Carbon::parse($percetakan->updated_at ?? $percetakan->created_at)
                        ]
                    ])->sortByDesc('created_at')->values();

                    $result = [
                        'is_pesanan' => true,
                        'resi' => $percetakan->shipping_ref ?? $percetakan->order_number,
                        'resi_aktual' => $percetakan->shipping_ref,
                        'pengirim' => 'Sancaka Percetakan',
                        'alamat_pengirim' => 'Jl.Dr.Wahidin No.18 A, Ngawi',
                        'no_pengirim' => '08819435180',
                        'penerima' => $percetakan->customer_name ?? 'Pelanggan',
                        'alamat_penerima' => $percetakan->destination_address ?? '-',
                        'no_penerima' => $percetakan->customer_phone ?? '-',
                        'status' => $percetakan->status,
                        'tanggal_dibuat' => $percetakan->created_at,
                        'histories' => $fakeHistory,
                        'jasa_ekspedisi_aktual' => $displayEkspedisi,
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error Tracking DB2 (Percetakan): " . $e->getMessage());
            }
        }

        // ==========================================================
        // 3. CARI DI SPX SCAN
        // ==========================================================
        if (!$result) {
            $spxScan = SpxScan::with('kontak')->where('resi', $resi)->first();
            if ($spxScan) {
                $result = [
                    'is_pesanan' => false,
                    'resi' => $spxScan->resi,
                    'pengirim' => $spxScan->kontak->nama ?? 'N/A',
                    'alamat_pengirim' => $spxScan->kontak->alamat ?? 'N/A',
                    'penerima' => 'Agen SPX Express (Sancaka Express)',
                    'alamat_penerima' => 'Jl.Dr.Wahidin No.18 A RT.22 RW.05 Kel.Ketanggi',
                    'status' => $spxScan->status,
                    'tanggal_dibuat' => $spxScan->created_at,
                    'histories' => collect([
                        (object)[
                            'status' => $spxScan->status,
                            'lokasi' => 'SPX Ngawi',
                            'keterangan' => 'Paket telah di-scan oleh pengirim.',
                            'created_at' => Carbon::parse($spxScan->created_at),
                        ]
                    ]),
                ];
            }
        }

        // ==========================================================
        // 4. CARI DI SCANNED PACKAGES
        // ==========================================================
        if (!$result) {
            $scannedHistories = ScannedPackage::with(['user', 'kontak'])
                                ->where('resi_number', $resi)
                                ->orderBy('created_at', 'desc')
                                ->get();

            if ($scannedHistories->isNotEmpty()) {
                $latestScan = $scannedHistories->first();
                $firstScan = $scannedHistories->last();
                $senderName = $firstScan->kontak->nama ?? ($firstScan->user->name ?? 'Mitra Sancaka Express');
                $senderAddress = $firstScan->kontak->alamat ?? ($firstScan->user->address ?? 'N/A');

                $result = [
                    'is_pesanan' => false,
                    'resi' => $latestScan->resi_number,
                    'pengirim' => $senderName,
                    'alamat_pengirim' => $senderAddress,
                    'penerima' => 'Agen Drop Point SPX Sancaka Express',
                    'alamat_penerima' => 'Jl.Dr.Wahidin No.18 A RT.22 RW.05 Kel.Ketanggi Kec.Ngawi Kab.Ngawi Jawa Timur 63211',
                    'status' => $latestScan->status,
                    'tanggal_dibuat' => $firstScan->created_at,
                    'histories' => $scannedHistories->map(function ($item) {
                        return (object)[
                            'status' => $item->status,
                            'lokasi' => 'Gudang Sancaka',
                            'keterangan' => 'Paket telah diproses di gudang.',
                            'created_at' => Carbon::parse($item->created_at)
                        ];
                    }),
                ];
            }
        }

       // Blok pencarian RSUD
        if (!$pesanan) {
            // 1. Sanitasi input: jika ada prefix SCK-, buang agar sesuai format di DB
            $cleanedResi = str_replace('SCK-', '', $resi);
            $rsudOrder = \App\Models\RsudOrderObat::where('kode_booking', $cleanedResi)->first();

            if ($rsudOrder) {
                $kiriminAja = new \App\Services\KiriminAjaService();
                $trackingData = null;

                // Jika sudah ada resi, baru kita tracking ke API KiriminAja
                if (!empty($rsudOrder->resi)) {
                    $serviceType = $rsudOrder->service_type ?? 'regular';
                    $trackingData = $kiriminAja->track($serviceType, $rsudOrder->resi);
                }

                // Mapping Data untuk View
                $result = [
                    'is_pesanan' => true,
                    'resi' => $rsudOrder->resi ?? 'Belum ada resi',
                    'resi_aktual' => $rsudOrder->resi,
                    'pengirim' => $rsudOrder->sender_name,
                    'alamat_pengirim' => $rsudOrder->sender_address,
                    'no_pengirim' => $rsudOrder->sender_phone,
                    'penerima' => $rsudOrder->receiver_name,
                    'alamat_penerima' => $rsudOrder->receiver_address,
                    'no_penerima' => $rsudOrder->receiver_phone,
                    'status' => ($rsudOrder->resi) ? ($trackingData['text'] ?? 'Diserahkan ke Kurir') : 'Status: ' . $rsudOrder->status_racik,
                    'tanggal_dibuat' => $rsudOrder->created_at,
                    'histories' => [],
                ];

                // Jika ada histori dari API KiriminAja, masukkan ke array
                if ($trackingData && isset($trackingData['histories'])) {
                    foreach ($trackingData['histories'] as $h) {
                        $result['histories'][] = (object)[
                            'status' => $h['status'],
                            'lokasi' => 'Ekspedisi',
                            'keterangan' => $h['status'],
                            'created_at' => \Carbon\Carbon::parse($h['created_at'])
                        ];
                    }
                }

                // =========================================================
                // 🔥 KODE TAMBAHAN: INJECT STATUS RACIK RSUD KE TIMELINE 🔥
                // =========================================================
                
                // Tambahkan Status Apotek Saat Ini
                $result['histories'][] = (object)[
                    'status' => 'Status Apotek: ' . $rsudOrder->status_racik,
                    'lokasi' => 'Apotek RSUD',
                    'keterangan' => 'Proses penyiapan obat internal.',
                    'created_at' => $rsudOrder->updated_at ?? $rsudOrder->created_at
                ];

                // Tambahkan Status Pesanan Pertama Kali Dibuat
                $result['histories'][] = (object)[
                    'status' => 'Booking Obat Dibuat',
                    'lokasi' => 'Sistem RSUD',
                    'keterangan' => 'Pesanan masuk ke sistem dan menunggu pembayaran/verifikasi.',
                    'created_at' => $rsudOrder->created_at
                ];

                // Urutkan histori berdasarkan waktu (yang paling baru di atas)
                $result['histories'] = collect($result['histories'])->sortByDesc('created_at')->values()->all();
            }
        }

        if ($result) {
            return view('public.tracking.index', compact('result'));
        }

        return redirect()->route('tracking.index')->with('error', "Nomor resi '{$resi}' tidak ditemukan.");
    }

  /**
     * [CETAK THERMAL ADMIN]
     */
    public function cetakThermal($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->orWhere('shipping_ref', $resi)
            ->first();

        if (!$pesanan) {
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->orWhere('shipping_ref', $resi)
                ->first();

            if ($orderModel) {
                $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'nomor_invoice' => $orderModel->invoice_number,
                    'status' => $orderModel->status,
                    'sender_name' => $orderModel->store->name ?? 'N/A',
                    'sender_phone' => $orderModel->store->user->no_wa ?? '-',
                    'sender_address' => $orderModel->store->address_detail ?? '-',
                    'sender_village' => $orderModel->store->village ?? '',
                    'sender_district' => $orderModel->store->district ?? '',
                    'sender_regency' => $orderModel->store->regency ?? '',
                    'sender_province' => $orderModel->store->province ?? '',
                    'sender_postal_code' => $orderModel->store->postal_code ?? '',
                    'receiver_name' => $orderModel->user->nama_lengkap ?? 'N/A',
                    'receiver_phone' => $orderModel->user->no_wa ?? '-',
                    'receiver_address' => $orderModel->shipping_address ?? '-',
                    'receiver_village' => $orderModel->user->village ?? '',
                    'receiver_district' => $orderModel->user->district ?? '',
                    'receiver_regency' => $orderModel->user->regency ?? '',
                    'receiver_province' => $orderModel->user->province ?? '',
                    'receiver_postal_code' => $orderModel->user->postal_code ?? '',
                    'weight' => $orderModel->total_weight ?? 1000,
                    'item_price' => $orderModel->sub_total ?? 0,
                    'shipping_cost' => $orderModel->shipping_cost ?? 0,
                    'ongkir' => $orderModel->shipping_cost ?? 0,
                    'insurance_cost' => 0,
                    'total_cod' => $orderModel->grand_total ?? 0,
                    'cod_amount' => 0,
                    'item_description' => 'Paket Toko Online',
                    'length' => 10, 'width' => 10, 'height' => 10,
                    'expedition' => $orderModel->courier ?? 'JNE',
                    'service_type' => 'REG',
                    'payment_method' => $orderModel->payment_method ?? 'Transfer',
                    'created_at' => $orderModel->created_at,
                    'resi_aktual' => null,
                    'jasa_ekspedisi_aktual' => $orderModel->courier ?? 'JNE',
                ];
            }
        }

        if (!$pesanan) {
            try {
                $orderPercetakan = \DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($orderPercetakan) {
                    $rawService = $orderPercetakan->courier_service ?? 'Internal';
                    $expeditionName = explode(' - ', $rawService)[0];

                    $pesanan = (object)[
                        'resi' => $orderPercetakan->shipping_ref ?? $orderPercetakan->order_number,
                        'nomor_invoice' => $orderPercetakan->order_number,
                        'status' => $orderPercetakan->status,
                        'sender_name' => 'Sancaka Percetakan',
                        'sender_phone' => '08819435180',
                        'sender_address' => 'Jl.Dr.Wahidin No.18 A',
                        'sender_village' => 'Ketanggi',
                        'sender_district' => 'Ngawi',
                        'sender_regency' => 'Ngawi',
                        'sender_province' => 'Jawa Timur',
                        'sender_postal_code' => '63211',
                        'receiver_name' => $orderPercetakan->customer_name ?? 'Pelanggan',
                        'receiver_phone' => $orderPercetakan->customer_phone ?? '-',
                        'receiver_address' => $orderPercetakan->destination_address ?? '-',
                        'receiver_village' => '',
                        'receiver_district' => '',
                        'receiver_regency' => '',
                        'receiver_province' => '',
                        'receiver_postal_code' => '',
                        'weight' => 1000,
                        'item_description' => 'Produk Percetakan',
                        'item_price' => $orderPercetakan->final_price ?? 0,
                        'shipping_cost' => $orderPercetakan->shipping_cost ?? 0,
                        'ongkir' => $orderPercetakan->shipping_cost ?? 0,
                        'insurance_cost' => 0,
                        'total_cod' => ($orderPercetakan->final_price ?? 0) + ($orderPercetakan->shipping_cost ?? 0),
                        'cod_amount' => 0,
                        'expedition' => $expeditionName,
                        'service_type' => 'REG',
                        'payment_method' => $orderPercetakan->payment_method ?? 'Manual',
                        'created_at' => $orderPercetakan->created_at,
                        'resi_aktual' => null,
                        'jasa_ekspedisi_aktual' => null,
                        'length' => 10,
                        'width'  => 10,
                        'height' => 10,
                    ];
                }
            } catch (\Exception $e) {}
        }

        // ==========================================================
        // 🔥 TAMBAHAN LOGIK UNTUK RSUD AGAR TIDAK 404 SAAT DICETAK 🔥
        // ==========================================================
        if (!$pesanan) {
            $cleanedResi = str_replace('SCK-', '', $resi); // Bersihkan prefix jika ada
            
            $rsudOrder = \App\Models\RsudOrderObat::where('kode_booking', $cleanedResi)
                ->orWhere('resi', $resi)
                ->first();

            if ($rsudOrder) {
                // Konversi format RsudOrderObat agar sesuai dengan property object Pesanan
                $pesanan = (object)[
                    'resi' => $rsudOrder->resi ?? $rsudOrder->kode_booking,
                    'nomor_invoice' => $rsudOrder->kode_booking,
                    'status' => $rsudOrder->status_racik,
                    'sender_name' => $rsudOrder->sender_name ?? 'Sancaka Express',
                    'sender_phone' => $rsudOrder->sender_phone ?? '-',
                    'sender_address' => $rsudOrder->sender_address ?? '-',
                    'sender_village' => '',
                    'sender_district' => '',
                    'sender_regency' => '',
                    'sender_province' => '',
                    'sender_postal_code' => '',
                    'receiver_name' => $rsudOrder->receiver_name ?? 'Pasien RSUD',
                    'receiver_phone' => $rsudOrder->receiver_phone ?? '-',
                    'receiver_address' => $rsudOrder->receiver_address ?? '-',
                    'receiver_village' => '',
                    'receiver_district' => '',
                    'receiver_regency' => '',
                    'receiver_province' => '',
                    'receiver_postal_code' => '',
                    'weight' => $rsudOrder->weight ?? 1000,
                    'item_price' => $rsudOrder->item_price ?? 0,
                    'shipping_cost' => $rsudOrder->shipping_cost ?? 0,
                    'ongkir' => $rsudOrder->shipping_cost ?? 0,
                    'insurance_cost' => 0,
                    
                    // Cek jika ada flag COD
                    'total_cod' => in_array(strtoupper($rsudOrder->payment_method), ['COD', 'CODBARANG']) ? $rsudOrder->total_price : 0,
                    'cod_amount' => in_array(strtoupper($rsudOrder->payment_method), ['COD', 'CODBARANG']) ? $rsudOrder->total_price : 0,
                    
                    'item_description' => $rsudOrder->item_description ?? 'Pengiriman Obat RSUD',
                    'length' => 10, 'width' => 10, 'height' => 10,
                    'expedition' => 'Sancaka Express', // Atau ambil dari $rsudOrder->expedition jika ada
                    'service_type' => $rsudOrder->service_type ?? 'REG',
                    'payment_method' => $rsudOrder->payment_method ?? 'Transfer',
                    'created_at' => $rsudOrder->created_at,
                    'resi_aktual' => $rsudOrder->resi,
                    'jasa_ekspedisi_aktual' => 'Sancaka Express',
                ];
            }
        }

        if (!$pesanan) {
            abort(404, 'Pesanan tidak ditemukan untuk dicetak.');
        }

        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    /**
     * Helper Normalisasi: Gabungkan Data API + Data Lokal
     */
    private function normalizeKiriminAjaResponse(array $rawResponse, $pesanan): array
    {
        $normalizedHistories = collect([]);

        if (isset($rawResponse['histories']) && is_array($rawResponse['histories'])) {
            foreach ($rawResponse['histories'] as $history) {
                $statusText = preg_replace('/\s\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}\s\|/i', '', $history['status'] ?? 'N/A');

                $normalizedHistories->push((object)[
                    'status' => $statusText,
                    'lokasi' => $rawResponse['details']['destination']['city'] ?? '-',
                    'keterangan' => $history['status'] ?? null,
                    'created_at' => Carbon::parse($history['created_at'])->timezone('Asia/Jakarta'),
                ]);
            }
        }

        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
            $alreadyExists = $normalizedHistories->contains(function ($h) use ($waktuDibuat) {
                return str_contains(strtolower($h->status), 'created') || str_contains(strtolower($h->status), 'dibuat');
            });

            if (!$alreadyExists) {
                $lokasiAkun = strtoupper($pesanan->sender_regency ?? 'NGAWI');
                if (!empty($pesanan->sender_district)) {
                    $lokasiAkun = strtoupper($pesanan->sender_district) . ', ' . $lokasiAkun;
                }

                $normalizedHistories->push((object)[
                    'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM',
                    'lokasi' => $lokasiAkun,
                    'keterangan' => 'Pesanan berhasil dibuat di sistem SANCAKA EXPRESS. <br><b>INGIN KIRIM PAKET?</b> <a href="https://tokosancaka.com/register" target="_blank">Daftar AKUN ANDA Disini, GRATIS! </a>',
                    'created_at' => $waktuDibuat,
                ]);
            }
        }

        $sortedHistories = $normalizedHistories->sortByDesc('created_at')->values();
        $details = $rawResponse['details'] ?? [];

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $details['awb'] ?? $pesanan->resi_aktual,
            'pengirim' => $details['origin']['name'] ?? $pesanan->sender_name ?? 'N/A',
            'alamat_pengirim' => $details['origin']['address'] ?? $pesanan->sender_address ?? 'N/A',
            'no_pengirim' => $details['origin']['phone'] ?? $pesanan->sender_phone ?? 'N/A',
            'penerima' => $details['destination']['name'] ?? $pesanan->receiver_name ?? 'N/A',
            'alamat_penerima' => $details['destination']['address'] ?? $pesanan->receiver_address ?? 'N/A',
            'no_penerima' => $details['destination']['phone'] ?? $pesanan->receiver_phone ?? 'N/A',
            'status' => $rawResponse['text'] ?? ($pesanan->status ?? 'Pesanan Dibuat'),
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $sortedHistories,
            'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual,
        ];
    }

    public function refreshTimeline()
    {
        return redirect()->back()->with('success', 'Timeline diperbarui');
    }

    /**
     * Helper Ekstraksi Tracking Deliveree
     */
    private function trackDeliveree($pesanan)
    {
        $mode = \App\Models\Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');
        $baseUrl = \App\Models\Api::getValue('DELIVEREE_BASE_URL', $mode, 'https://api.sandbox.deliveree.com/public_api/v10');
        $apiKey = \App\Models\Api::getValue('DELIVEREE_API_KEY', $mode);
        
        // Asumsi $pesanan->resi berisi ID Booking Deliveree (contoh: 82128)
        $delivereeId = $pesanan->resi; 
        $histories = collect([]);
        $statusText = 'Menunggu Kurir';
        $jasaEkspedisi = 'Deliveree';

        if (!empty($delivereeId)) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                    'Accept-Language' => 'id'
                ])->get($baseUrl . '/deliveries/' . $delivereeId);

                if ($response->successful()) {
                    $data = $response->json();
                    $statusRaw = $data['status'] ?? '';
                    
                    // Terjemahkan Status Deliveree
                    $statusMap = [
                        'locating_driver' => 'Mencari Pengemudi / Kurir',
                        'driver_accept_booking' => 'Pengemudi Ditemukan & Menuju Lokasi Anda',
                        'delivery_in_progress' => 'Kurir Dalam Perjalanan Mengantar Paket',
                        'delivery_completed' => 'Pesanan Selesai / Terkirim',
                        'canceled' => 'Pesanan Dibatalkan',
                        'locating_driver_timeout' => 'Waktu Tunggu Habis (Kurir Tidak Ditemukan)'
                    ];
                    $statusText = $statusMap[$statusRaw] ?? ucfirst(str_replace('_', ' ', $statusRaw));

                    // Ambil info driver & Live Tracking URL
                    $driverName = $data['driver']['name'] ?? null;
                    $driverPhone = $data['driver']['phone'] ?? null;
                    $trackingUrl = $data['tracking_url'] ?? null;
                    $vehicleName = $data['vehicle_type_info']['name'] ?? 'Armada';

                    $jasaEkspedisi = 'Deliveree - ' . $vehicleName;

                    $keterangan = "<b>Status Terkini:</b> " . $statusText;
                    if ($driverName) {
                        $keterangan .= "<br><b>Kurir:</b> $driverName ($driverPhone)";
                    }
                    if ($trackingUrl) {
                        $keterangan .= "<br><a href='$trackingUrl' target='_blank' class='btn btn-sm btn-success mt-2' style='background:#00b14f; border:none;'><i class='fas fa-map-marker-alt'></i> Lacak Live Map Pengemudi</a>";
                    }

                    // Push status utama saat ini
                    $histories->push((object)[
                        'status' => $statusText,
                        'lokasi' => 'Update Sistem Deliveree',
                        'keterangan' => $keterangan,
                        'created_at' => \Carbon\Carbon::now()->timezone('Asia/Jakarta')
                    ]);

                    // Iterasi lokasi untuk mendapatkan jam tiba / jam berangkat kurir
                    if (!empty($data['locations']) && is_array($data['locations'])) {
                        foreach ($data['locations'] as $loc) {
                            if (!empty($loc['arrived_at'])) {
                                $tipeLokasi = ($loc['is_payer'] ?? false) ? 'Tujuan' : 'Penjemputan';
                                $histories->push((object)[
                                    'status' => "Kurir Tiba di Titik " . $tipeLokasi,
                                    'lokasi' => $loc['name'] ?? 'Alamat',
                                    'keterangan' => 'Kurir telah tiba di titik lokasi.',
                                    'created_at' => \Carbon\Carbon::parse($loc['arrived_at'])->timezone('Asia/Jakarta')
                                ]);
                            }
                            if (!empty($loc['delivery_status']) && strtolower($loc['delivery_status']) === 'delivered') {
                                $histories->push((object)[
                                    'status' => 'Paket Diserahkan',
                                    'lokasi' => $loc['name'] ?? 'Lokasi Tujuan',
                                    'keterangan' => 'Diterima oleh: ' . ($loc['recipient_name'] ?? 'Penerima'),
                                    'created_at' => \Carbon\Carbon::parse($loc['leaved_at'] ?? now())->timezone('Asia/Jakarta')
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Deliveree Tracking Error: ' . $e->getMessage());
            }
        }

        // Timeline Default: "Pesanan Dibuat"
        if ($pesanan->created_at) {
            $waktuDibuat = \Carbon\Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
            
            // Cek lokasi pengirim untuk tampilan
            $lokasiAkun = strtoupper($pesanan->sender_regency ?? 'NGAWI');
            if (!empty($pesanan->sender_district)) {
                $lokasiAkun = strtoupper($pesanan->sender_district) . ', ' . $lokasiAkun;
            }

            $histories->push((object)[
                'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM',
                'lokasi' => $lokasiAkun,
                'keterangan' => 'Pesanan berhasil dibuat di sistem SANCAKA EXPRESS. Menggunakan layanan Deliveree.',
                'created_at' => $waktuDibuat,
            ]);
        }

        $sortedHistories = $histories->sortByDesc('created_at')->values();

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $pesanan->resi_aktual ?? $pesanan->resi,
            'pengirim' => $pesanan->sender_name ?? 'N/A',
            'alamat_pengirim' => $pesanan->sender_address ?? 'N/A',
            'no_pengirim' => $pesanan->sender_phone ?? 'N/A',
            'penerima' => $pesanan->receiver_name ?? 'N/A',
            'alamat_penerima' => $pesanan->receiver_address ?? 'N/A',
            'no_penerima' => $pesanan->receiver_phone ?? 'N/A',
            'status' => $statusText,
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $sortedHistories,
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
        ];
    }

    /**
     * =========================================================================
     * HELPER EKSTRAKSI TRACKING LALAMOVE
     * =========================================================================
     */
    private function trackLalamove($pesanan)
    {
        // Asumsi $pesanan->resi menyimpan Order ID Lalamove (contoh: 3516154960524399292)
        $orderId = $pesanan->resi; 
        $histories = collect([]);
        $statusText = 'Menunggu Kurir';
        $jasaEkspedisi = 'Lalamove';

        if (!empty($orderId)) {
            try {
                $response = $this->_lalamoveRequest('GET', "/v3/orders/{$orderId}");

                if ($response && $response->successful()) {
                    $data = $response->json('data');
                    $statusRaw = $data['status'] ?? '';
                    
                    // Terjemahkan Status Lalamove
                    $statusMap = [
                        'ASSIGNING_DRIVER' => 'Mencari Driver / Kurir',
                        'ON_GOING' => 'Driver Menuju Lokasi Penjemputan',
                        'PICKED_UP' => 'Paket Telah Diambil (Dalam Perjalanan)',
                        'COMPLETED' => 'Pesanan Selesai / Terkirim',
                        'CANCELED' => 'Pesanan Dibatalkan',
                        'REJECTED' => 'Ditolak oleh Driver (Mencari Ulang)',
                        'EXPIRED' => 'Waktu Tunggu Habis (Driver Tidak Ditemukan)'
                    ];
                    $statusText = $statusMap[$statusRaw] ?? ucfirst(str_replace('_', ' ', $statusRaw));

                    // Ekstrak URL Live Tracking
                    $shareLink = $data['shareLink'] ?? null;
                    
                    $keterangan = "<b>Status Terkini:</b> " . $statusText;
                    if ($shareLink) {
                        $keterangan .= "<br><a href='$shareLink' target='_blank' class='btn btn-sm mt-2 fw-bold text-white' style='background:#f27024; border:none;'><i class='fas fa-map-marker-alt'></i> Lacak Live Map Lalamove</a>";
                    }

                    // Push status utama Lalamove saat ini ke Timeline
                    $histories->push((object)[
                        'status' => $statusText,
                        'lokasi' => 'Sistem Lalamove',
                        'keterangan' => $keterangan,
                        'created_at' => \Carbon\Carbon::now()->timezone('Asia/Jakarta')
                    ]);

                    // Cek Bukti Pengiriman / Proof Of Delivery (POD)
                    if (!empty($data['stops']) && is_array($data['stops'])) {
                        foreach ($data['stops'] as $stop) {
                            if (!empty($stop['POD']['status']) && $stop['POD']['status'] === 'DELIVERED') {
                                $waktuTerkirim = !empty($stop['POD']['deliveredAt']) 
                                    ? \Carbon\Carbon::parse($stop['POD']['deliveredAt'])->timezone('Asia/Jakarta') 
                                    : \Carbon\Carbon::now()->timezone('Asia/Jakarta');

                                $histories->push((object)[
                                    'status' => 'Paket Diserahkan',
                                    'lokasi' => $stop['address'] ?? 'Lokasi Tujuan',
                                    'keterangan' => 'Paket telah berhasil dikirimkan ke penerima.',
                                    'created_at' => $waktuTerkirim
                                ]);
                            }
                        }
                    }

                } else {
                    Log::error('Lalamove Tracking Error: ' . ($response ? $response->body() : 'No Response'));
                }
            } catch (\Exception $e) {
                Log::error('Lalamove Tracking Exception: ' . $e->getMessage());
            }
        }

        // Timeline Default: "Pesanan Dibuat"
        if ($pesanan->created_at) {
            $waktuDibuat = \Carbon\Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
            
            // Cek lokasi pengirim untuk tampilan
            $lokasiAkun = strtoupper($pesanan->sender_regency ?? 'NGAWI');
            if (!empty($pesanan->sender_district)) {
                $lokasiAkun = strtoupper($pesanan->sender_district) . ', ' . $lokasiAkun;
            }

            $histories->push((object)[
                'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM',
                'lokasi' => $lokasiAkun,
                'keterangan' => 'Pesanan berhasil dibuat di sistem SANCAKA EXPRESS. Menggunakan layanan Lalamove.',
                'created_at' => $waktuDibuat,
            ]);
        }

        $sortedHistories = $histories->sortByDesc('created_at')->values();

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $pesanan->resi_aktual ?? $pesanan->resi,
            'pengirim' => $pesanan->sender_name ?? 'N/A',
            'alamat_pengirim' => $pesanan->sender_address ?? 'N/A',
            'no_pengirim' => $pesanan->sender_phone ?? 'N/A',
            'penerima' => $pesanan->receiver_name ?? 'N/A',
            'alamat_penerima' => $pesanan->receiver_address ?? 'N/A',
            'no_penerima' => $pesanan->receiver_phone ?? 'N/A',
            'status' => $statusText,
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $sortedHistories,
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
            'logo_ekspedisi' => 'https://tokosancaka.com/public/assets/lalamove.png', // Logo Lalamove disisipkan
        ];
    }

    /**
     * Helper Generator HTTP Request Lalamove
     */
    private function _lalamoveRequest($method, $path)
    {
        $mode = \App\Models\Api::getValue('LALAMOVE_MODE', 'global', 'sandbox');
        $apiKey = \App\Models\Api::getValue('LALAMOVE_API_KEY', $mode);
        $apiSecret = \App\Models\Api::getValue('LALAMOVE_API_SECRET', $mode);
        $baseUrl = ($mode === 'production') ? 'https://rest.lalamove.com' : 'https://rest.sandbox.lalamove.com';
        $market = \App\Models\Api::getValue('LALAMOVE_MARKET', 'global', 'ID');

        if (empty($apiKey) || empty($apiSecret)) {
            return null;
        }

        $timestamp = round(microtime(true) * 1000);
        $bodyStr = ''; // Method GET tidak memiliki body request
        
        $rawSignature = "{$timestamp}\r\n{$method}\r\n{$path}\r\n\r\n{$bodyStr}";
        $signature = hash_hmac('sha256', $rawSignature, $apiSecret);
        $token = "{$apiKey}:{$timestamp}:{$signature}";
        $requestId = \Illuminate\Support\Str::uuid()->toString();

        $headers = [
            'Authorization' => "hmac {$token}",
            'Market'        => $market,
            'Request-ID'    => $requestId,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        return Http::withHeaders($headers)->get($baseUrl . $path);
    }
    
}
