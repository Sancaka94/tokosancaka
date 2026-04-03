<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\Order;
use App\Models\ScannedPackage;
use App\Models\ReturnOrder;
use App\Services\KiriminAjaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ShippingHelper;

class TrackingController extends Controller
{
    /**
     * [TRACKING API MOBILE]
     * Logika: DB1 -> DB1 (Retur) -> DB2 (Percetakan) -> SPX -> ScannedPackage
     */
    public function track($resi)
    {
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

        // PROSES API KIRIMINAJA (JIKA DB1 / RETUR KETEMU)
        if ($pesanan) {
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
        // RETURN JSON RESPONSE FOR MOBILE
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
            'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual ?? 'Sancaka Express',
        ];
    }
}
