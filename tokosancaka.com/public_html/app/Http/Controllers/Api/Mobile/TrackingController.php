<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\Order;
use App\Models\ScannedPackage;
use App\Models\ReturnOrder;
use App\Models\PesananAutokirim;
use App\Models\RsudOrderObat;
use App\Models\Api;
use App\Services\KiriminAjaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Helpers\ShippingHelper;

class TrackingController extends Controller
{
    /**
     * [TRACKING API MOBILE]
     * Endpoint: GET /api/mobile/track/{resi}
     * Logika: DB1 -> DB1 (Retur) -> DB1 (Autokirim) -> Routing API (Deliveree/Lalamove/iPaymu/Autokirim/KiriminAja) 
     *         -> DB2 (Percetakan) -> SPX -> ScannedPackage -> RSUD
     */
    public function track($resi)
    {
        $pesanan = null;
        $result = null;

        // ==========================================================
        // 1. CARI DI TABEL PESANAN & ORDER (DB UTAMA)
        // ==========================================================
        $pesananModel = Pesanan::where('resi', $resi)
            ->orWhere('resi_aktual', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->orWhere('shipping_ref', $resi)
            ->first();

        if ($pesananModel) {
            $pesanan = $pesananModel;
        } else {
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->first();

            if ($orderModel) {
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
                $orderModel = Order::with(['store.user', 'user'])->find($returnOrder->order_id);

                if ($orderModel) {
                    $shipInfo = ShippingHelper::parseShippingMethod($returnOrder->courier . ' REG');

                    $pesanan = (object)[
                        'resi' => $returnOrder->new_resi,
                        'resi_aktual' => $returnOrder->new_resi,
                        'nomor_invoice' => $returnOrder->new_resi,
                        'sender_name' => $orderModel->user->nama_lengkap ?? 'Pembeli (Retur)',
                        'sender_address' => $orderModel->shipping_address ?? 'N/A',
                        'sender_province' => $orderModel->user->province ?? 'N/A',
                        'sender_regency' => $orderModel->user->regency ?? 'N/A',
                        'sender_district' => $orderModel->user->district ?? 'N/A',
                        'sender_village' => $orderModel->user->village ?? 'N/A',
                        'sender_postal_code' => $orderModel->user->postal_code ?? 'N/A',
                        'sender_phone' => $orderModel->user->no_wa ?? 'N/A',
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

        // ==========================================================
        // 1.8. CARI DI TABEL PESANAN AUTOKIRIM
        // ==========================================================
        if (!$pesanan) {
            $autokirim = PesananAutokirim::where('awb_number', $resi)
                ->orWhere('order_id', $resi)
                ->first();

            if ($autokirim) {
                $isCod = in_array(strtolower($autokirim->metode_pembayaran), ['cod', 'codbarang', 'cod_barang', 'cod_ongkir']);

                $pesanan = (object)[
                    'is_autokirim' => true,
                    'pickup_point_code' => $autokirim->pickup_point_code ?? '',
                    'resi' => $autokirim->awb_number ?? $autokirim->order_id,
                    'resi_aktual' => $autokirim->awb_number,
                    'nomor_invoice' => $autokirim->order_id,
                    'sender_name' => $autokirim->pengirim_nama ?? 'N/A',
                    'sender_phone' => $autokirim->pengirim_hp ?? '-',
                    'sender_address' => $autokirim->pengirim_alamat ?? '-',
                    'sender_village' => '',
                    'sender_district' => '',
                    'sender_regency' => '',
                    'sender_province' => '',
                    'sender_postal_code' => $autokirim->pengirim_kodepos ?? '',
                    'receiver_name' => $autokirim->penerima_nama ?? 'N/A',
                    'receiver_phone' => $autokirim->penerima_hp ?? '-',
                    'receiver_address' => $autokirim->penerima_alamat ?? '-',
                    'receiver_village' => '',
                    'receiver_district' => '',
                    'receiver_regency' => '',
                    'receiver_province' => '',
                    'receiver_postal_code' => $autokirim->penerima_kodepos ?? '',
                    'weight' => $autokirim->berat_gram ?? 1000,
                    'item_price' => $autokirim->nilai_barang ?? 0,
                    'shipping_cost' => $autokirim->ongkir ?? 0,
                    'ongkir' => $autokirim->ongkir ?? 0,
                    'insurance_cost' => $autokirim->asuransi ? round(($autokirim->nilai_barang ?? 0) * 0.002) : 0,
                    'total_cod' => $isCod ? $autokirim->grand_total : 0,
                    'cod_amount' => $isCod ? $autokirim->grand_total : 0,
                    'item_description' => $autokirim->deskripsi_barang ?? $autokirim->kategori_barang ?? 'Paket Autokirim',
                    'length' => $autokirim->panjang_cm ?? 10,
                    'width' => $autokirim->lebar_cm ?? 10,
                    'height' => $autokirim->tinggi_cm ?? 10,
                    'expedition' => $autokirim->kurir ?? 'Sancaka Express',
                    'service_type' => $autokirim->layanan ?? 'REG',
                    'payment_method' => str_replace('_', ' ', strtoupper($autokirim->metode_pembayaran)),
                    'created_at' => $autokirim->created_at,
                    'jasa_ekspedisi_aktual' => $autokirim->kurir,
                ];
            }
        }

        // ==========================================================
        // PROSES API EKSPEDISI JIKA DB UTAMA / AUTOKIRIM KETEMU
        // ==========================================================
        if ($pesanan) {
            $expeditionRaw = strtolower($pesanan->expedition ?? $pesanan->jasa_ekspedisi_aktual ?? $pesanan->service_type ?? '');

            if (isset($pesanan->is_autokirim) && $pesanan->is_autokirim) {
                $result = $this->trackAutokirim($pesanan);
            } elseif (str_contains($expeditionRaw, 'deliveree')) {
                $result = $this->trackDeliveree($pesanan);
            } elseif (str_contains($expeditionRaw, 'lalamove')) {
                $result = $this->trackLalamove($pesanan);
            } elseif (str_contains($expeditionRaw, 'ipaymu') || str_contains($expeditionRaw, 'komship')) {
                $result = $this->trackIpaymu($pesanan);
            } else {
                // Tracking KiriminAja
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
                        'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual ?? 'Sancaka Express',
                    ];
                }
            }
        }

        // ==========================================================
        // 2. CARI DI DB 2 (PERCETAKAN)
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
                    'jasa_ekspedisi_aktual' => 'Shopee Express (SPX)'
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
                    'jasa_ekspedisi_aktual' => 'Sancaka Express'
                ];
            }
        }

        // ==========================================================
        // 5. CARI DI SISTEM RSUD
        // ==========================================================
        if (!$result) {
            $cleanedResi = str_replace('SCK-', '', $resi);

            $rsudOrder = RsudOrderObat::where('kode_booking', $cleanedResi)
                ->orWhere('resi', $resi)
                ->first();

            if ($rsudOrder) {
                $kiriminAja = new KiriminAjaService();
                $trackingData = null;

                if (!empty($rsudOrder->resi)) {
                    $serviceType = $rsudOrder->service_type ?? 'regular';
                    $trackingData = $kiriminAja->track($serviceType, $rsudOrder->resi);
                }

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
                    'jasa_ekspedisi_aktual' => 'Sancaka Express'
                ];

                if ($trackingData && isset($trackingData['histories'])) {
                    foreach ($trackingData['histories'] as $h) {
                        $result['histories'][] = (object)[
                            'status' => $h['status'],
                            'lokasi' => 'Ekspedisi',
                            'keterangan' => $h['status'],
                            'created_at' => Carbon::parse($h['created_at'])
                        ];
                    }
                }

                // Inject Status Racik RSUD
                $result['histories'][] = (object)[
                    'status' => 'Status Apotek: ' . $rsudOrder->status_racik,
                    'lokasi' => 'Apotek RSUD',
                    'keterangan' => 'Proses penyiapan obat internal.',
                    'created_at' => $rsudOrder->updated_at ?? $rsudOrder->created_at
                ];

                $result['histories'][] = (object)[
                    'status' => 'Booking Obat Dibuat',
                    'lokasi' => 'Sistem RSUD',
                    'keterangan' => 'Pesanan masuk ke sistem dan menunggu pembayaran/verifikasi.',
                    'created_at' => $rsudOrder->created_at
                ];

                $result['histories'] = collect($result['histories'])->sortByDesc('created_at')->values()->all();
            }
        }

        // ==========================================================
        // MENGEMBALIKAN RESPONSE JSON UNTUK MOBILE APP
        // ==========================================================
        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => "Nomor resi '{$resi}' tidak ditemukan."
        ], 404);
    }

    /**
     * Helper Normalisasi KiriminAja
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
            'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual ?? 'Sancaka Express',
        ];
    }

    /**
     * Helper Ekstraksi Tracking Deliveree
     */
    private function trackDeliveree($pesanan)
    {
        $mode = Api::getValue('DELIVEREE_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('DELIVEREE_BASE_URL', $mode, 'https://api.sandbox.deliveree.com/public_api/v10');
        $apiKey = Api::getValue('DELIVEREE_API_KEY', $mode);

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

                    $statusMap = [
                        'locating_driver' => 'Mencari Pengemudi / Kurir',
                        'driver_accept_booking' => 'Pengemudi Ditemukan & Menuju Lokasi Anda',
                        'delivery_in_progress' => 'Kurir Dalam Perjalanan Mengantar Paket',
                        'delivery_completed' => 'Pesanan Selesai / Terkirim',
                        'canceled' => 'Pesanan Dibatalkan',
                        'locating_driver_timeout' => 'Waktu Tunggu Habis (Kurir Tidak Ditemukan)'
                    ];
                    $statusText = $statusMap[$statusRaw] ?? ucfirst(str_replace('_', ' ', $statusRaw));

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

                    $histories->push((object)[
                        'status' => $statusText,
                        'lokasi' => 'Update Sistem Deliveree',
                        'keterangan' => $keterangan,
                        'created_at' => Carbon::now()->timezone('Asia/Jakarta')
                    ]);

                    if (!empty($data['locations']) && is_array($data['locations'])) {
                        foreach ($data['locations'] as $loc) {
                            if (!empty($loc['arrived_at'])) {
                                $tipeLokasi = ($loc['is_payer'] ?? false) ? 'Tujuan' : 'Penjemputan';
                                $histories->push((object)[
                                    'status' => "Kurir Tiba di Titik " . $tipeLokasi,
                                    'lokasi' => $loc['name'] ?? 'Alamat',
                                    'keterangan' => 'Kurir telah tiba di titik lokasi.',
                                    'created_at' => Carbon::parse($loc['arrived_at'])->timezone('Asia/Jakarta')
                                ]);
                            }
                            if (!empty($loc['delivery_status']) && strtolower($loc['delivery_status']) === 'delivered') {
                                $histories->push((object)[
                                    'status' => 'Paket Diserahkan',
                                    'lokasi' => $loc['name'] ?? 'Lokasi Tujuan',
                                    'keterangan' => 'Diterima oleh: ' . ($loc['recipient_name'] ?? 'Penerima'),
                                    'created_at' => Carbon::parse($loc['leaved_at'] ?? now())->timezone('Asia/Jakarta')
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Deliveree Tracking Error: ' . $e->getMessage());
            }
        }

        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
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
            'histories' => $histories->sortByDesc('created_at')->values(),
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
        ];
    }

    /**
     * Helper Ekstraksi Tracking Lalamove
     */
    private function trackLalamove($pesanan)
    {
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

                    $shareLink = $data['shareLink'] ?? null;
                    $keterangan = "<b>Status Terkini:</b> " . $statusText;
                    if ($shareLink) {
                        $keterangan .= "<br><a href='$shareLink' target='_blank' class='btn btn-sm mt-2 fw-bold text-white' style='background:#f27024; border:none;'><i class='fas fa-map-marker-alt'></i> Lacak Live Map Lalamove</a>";
                    }

                    $histories->push((object)[
                        'status' => $statusText,
                        'lokasi' => 'Sistem Lalamove',
                        'keterangan' => $keterangan,
                        'created_at' => Carbon::now()->timezone('Asia/Jakarta')
                    ]);

                    if (!empty($data['stops']) && is_array($data['stops'])) {
                        foreach ($data['stops'] as $stop) {
                            if (!empty($stop['POD']['status']) && $stop['POD']['status'] === 'DELIVERED') {
                                $waktuTerkirim = !empty($stop['POD']['deliveredAt'])
                                    ? Carbon::parse($stop['POD']['deliveredAt'])->timezone('Asia/Jakarta')
                                    : Carbon::now()->timezone('Asia/Jakarta');

                                $histories->push((object)[
                                    'status' => 'Paket Diserahkan',
                                    'lokasi' => $stop['address'] ?? 'Lokasi Tujuan',
                                    'keterangan' => 'Paket telah berhasil dikirimkan ke penerima.',
                                    'created_at' => $waktuTerkirim
                                ]);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Lalamove Tracking Exception: ' . $e->getMessage());
            }
        }

        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
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
            'histories' => $histories->sortByDesc('created_at')->values(),
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
            'logo_ekspedisi' => 'https://tokosancaka.com/public/assets/lalamove.png',
        ];
    }

    /**
     * Helper Generator HTTP Request Lalamove
     */
    private function _lalamoveRequest($method, $path)
    {
        $mode = Api::getValue('LALAMOVE_MODE', 'global', 'sandbox');
        $apiKey = Api::getValue('LALAMOVE_API_KEY', $mode);
        $apiSecret = Api::getValue('LALAMOVE_API_SECRET', $mode);
        $baseUrl = ($mode === 'production') ? 'https://rest.lalamove.com' : 'https://rest.sandbox.lalamove.com';
        $market = Api::getValue('LALAMOVE_MARKET', 'global', 'ID');

        if (empty($apiKey) || empty($apiSecret)) {
            return null;
        }

        $timestamp = round(microtime(true) * 1000);
        $bodyStr = ''; 

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

    /**
     * Helper Ekstraksi Tracking IPAYMU
     */
    private function trackIpaymu($pesanan)
    {
        $awb = $pesanan->resi_aktual ?? $pesanan->resi;
        $trxId = $pesanan->nomor_invoice ?? $pesanan->resi;

        $histories = collect([]);
        $statusText = 'Diproses (Menunggu Ekspedisi)';
        $jasaEkspedisi = 'iPaymu COD (Komship)';

        if (!empty($awb)) {
            try {
                $ipaymuService = app(\App\Services\IpaymuService::class);
                $response = $ipaymuService->trackCodPackage($awb, $trxId);

                if (is_array($response) && !empty($response)) {
                    $statusText = $response['Status'] ?? $response['status'] ?? 'Sedang Dalam Pengiriman';

                    if (isset($response['Data']['history']) && is_array($response['Data']['history'])) {
                        foreach ($response['Data']['history'] as $h) {
                            $histories->push((object)[
                                'status' => $h['status'] ?? 'Update Pengiriman',
                                'lokasi' => $h['city'] ?? 'Ekspedisi iPaymu',
                                'keterangan' => $h['note'] ?? $h['desc'] ?? '-',
                                'created_at' => Carbon::parse($h['date'] ?? now())->timezone('Asia/Jakarta')
                            ]);
                        }
                    } else {
                        $histories->push((object)[
                            'status' => 'Status iPaymu: ' . $statusText,
                            'lokasi' => 'Sistem iPaymu',
                            'keterangan' => 'Paket sedang dilacak melalui integrasi iPaymu COD.',
                            'created_at' => Carbon::now()->timezone('Asia/Jakarta')
                        ]);
                    }
                } else {
                    $histories->push((object)[
                        'status' => 'Data Diterima iPaymu',
                        'lokasi' => 'Sistem Terintegrasi',
                        'keterangan' => 'Request tracking ke iPaymu berhasil (Menunggu sinkronisasi status kurir).',
                        'created_at' => Carbon::now()->timezone('Asia/Jakarta')
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('iPaymu Tracking Error: ' . $e->getMessage());
                $statusText = 'Gagal Melacak';
                $histories->push((object)[
                    'status' => 'Error Lacak iPaymu',
                    'lokasi' => 'Sistem',
                    'keterangan' => 'Koneksi ke server iPaymu gagal saat ini.',
                    'created_at' => Carbon::now()->timezone('Asia/Jakarta')
                ]);
            }
        }

        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');
            $lokasiAkun = strtoupper($pesanan->sender_regency ?? 'NGAWI');
            if (!empty($pesanan->sender_district)) {
                $lokasiAkun = strtoupper($pesanan->sender_district) . ', ' . $lokasiAkun;
            }

            $histories->push((object)[
                'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM',
                'lokasi' => $lokasiAkun,
                'keterangan' => 'Pesanan berhasil dibuat di sistem SANCAKA EXPRESS. Menggunakan integrasi iPaymu COD.',
                'created_at' => $waktuDibuat,
            ]);
        }

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $awb,
            'pengirim' => $pesanan->sender_name ?? 'N/A',
            'alamat_pengirim' => $pesanan->sender_address ?? 'N/A',
            'no_pengirim' => $pesanan->sender_phone ?? 'N/A',
            'penerima' => $pesanan->receiver_name ?? 'N/A',
            'alamat_penerima' => $pesanan->receiver_address ?? 'N/A',
            'no_penerima' => $pesanan->receiver_phone ?? 'N/A',
            'status' => $statusText,
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $histories->sortByDesc('created_at')->values(),
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
            'logo_ekspedisi' => 'https://tokosancaka.com/public/assets/ipaymu.jpg',
        ];
    }

    /**
     * Helper Ekstraksi Tracking Autokirim
     */
    private function trackAutokirim($pesanan)
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
        $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

        $histories = collect([]);
        $statusText = $pesanan->status == 'booking_created' ? 'Menunggu Pickup Ekspedisi' : $pesanan->status;
        $jasaEkspedisi = ($pesanan->jasa_ekspedisi_aktual ?? 'Autokirim') . ' - ' . ($pesanan->service_type ?? 'REG');

        if (!empty($pesanan->resi_aktual)) {
            try {
                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post("{$baseUrl}/api/tracking", [
                        'awb' => $pesanan->resi_aktual,
                        'pickup_point_code' => $pesanan->pickup_point_code ?? ''
                    ]);

                $result = $response->json();

                if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                    $data = $result['data'] ?? [];

                    if (isset($data['stats'])) {
                        $statusText = $data['stats']; 
                    }
                    if (isset($data['courier_name'])) {
                        $jasaEkspedisi = $data['courier_name'] . ' - ' . ($data['service'] ?? 'REG');
                    }

                    if (isset($data['histories']) && is_array($data['histories'])) {
                        foreach ($data['histories'] as $h) {
                            $datetimeString = trim(($h['date'] ?? '') . ' ' . ($h['time'] ?? ''));
                            $parsedDate = !empty($datetimeString)
                                ? Carbon::parse($datetimeString)->timezone('Asia/Jakarta')
                                : now()->timezone('Asia/Jakarta');

                            $keterangan = 'Status Paket: ' . ($h['desc'] ?? '-');

                            if (!empty($h['image'])) {
                                $keterangan .= '<br><a href="' . $h['image'] . '" target="_blank" style="color: #ff0800; text-decoration: none; font-size: 13px; margin-top: 5px; display: inline-block; font-weight: 600;"><i class="fas fa-camera"></i> Lihat Bukti Photo</a>';
                            }

                            $histories->push((object)[
                                'status' => $h['desc'] ?? 'Update Pengiriman',
                                'lokasi' => 'Sistem ' . ($data['courier_name'] ?? 'Ekspedisi'),
                                'keterangan' => $keterangan,
                                'created_at' => $parsedDate
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Autokirim Tracking API Error: ' . $e->getMessage());
            }
        }

        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');

            $histories->push((object)[
                'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM',
                'lokasi' => 'Sistem Integrasi',
                'keterangan' => 'Data pesanan berhasil disubmit ke server logistik Sancaka Express.',
                'created_at' => $waktuDibuat,
            ]);
        }

        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi,
            'resi_aktual' => $pesanan->resi_aktual,
            'pengirim' => $pesanan->sender_name,
            'alamat_pengirim' => $pesanan->sender_address,
            'no_pengirim' => $pesanan->sender_phone,
            'penerima' => $pesanan->receiver_name,
            'alamat_penerima' => $pesanan->receiver_address,
            'no_penerima' => $pesanan->receiver_phone,
            'status' => $statusText,
            'tanggal_dibuat' => $pesanan->created_at,
            'histories' => $histories->sortByDesc('created_at')->values(),
            'jasa_ekspedisi_aktual' => $jasaEkspedisi,
            'logo_ekspedisi' => null,
        ];
    }

    /**
     * Webhook DOKU untuk Tiket Pesawat 
     * (Sebaiknya ditaruh di controller Payment, namun disertakan untuk kelengkapan porting)
     */
    public function handleDokuCallback($data)
    {
        try {
            $invoiceNumber = $data['order']['invoice_number'];

            $parts = explode('-', $invoiceNumber);
            if (count($parts) < 2) {
                return response()->json(['status' => 'error', 'message' => 'Format invoice tidak valid']);
            }
            $transactionId = (int) $parts[1];

            $order = DB::table('flight_orders')->where('id', $transactionId)->first();

            if (!$order) {
                Log::warning("DOKU Webhook Pesawat: Transaksi $transactionId tidak ditemukan.");
                return response()->json(['status' => 'error', 'message' => 'Transaction not found']);
            }

            if ($order->status !== 'HOLD') {
                Log::info("DOKU Webhook Pesawat: Transaksi $transactionId sudah diproses (Status: {$order->status}).");
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            DB::table('flight_orders')->where('id', $transactionId)->update([
                'status' => 'PAID_PROCESSING',
                'updated_at' => now()
            ]);

            Log::info("DOKU Webhook Pesawat: Uang pesanan $transactionId sudah masuk Sancaka. Silakan jalankan Auto-Issued.");

            return response()->json(['status' => 'success', 'message' => 'Webhook Pesawat berhasil']);

        } catch (\Exception $e) {
            Log::error("Webhook Pesawat Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Sistem Error'], 500);
        }
    }
}