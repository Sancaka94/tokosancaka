<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\SpxScan;
use App\Models\Order;
use App\Models\ScannedPackage;
use App\Models\ScanHistory;
use App\Services\KiriminAjaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // <--- WAJIB UTK DB KEDUA
use Illuminate\Support\Facades\Log;
// --- TAMBAHKAN BARIS INI ---
use App\Helpers\ShippingHelper;
// ---------------------------

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
     * Logika: DB1 -> DB2 (Percetakan) -> SPX -> ScannedPackage
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
            ->orWhere('shipping_ref', $resi) // <--- TAMBAHAN: Cari berdasarkan Shipping Ref
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

        // PROSES API KIRIMINAJA (JIKA DB1 KETEMU)
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
                 ];
            }
        }

        // ==========================================================
        // 2. CARI DI DB 2 (PERCETAKAN) - UPDATE LOGIKA BARU
        // ==========================================================
        if (!$result) {
            try {
                // Debugging: Cek apakah koneksi berhasil (Lihat di storage/logs/laravel.log jika error)
                $percetakan = DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($percetakan) {
                    // --- 1. PARSING & CLEANING ---
                    $rawService = $percetakan->courier_service ?? 'Internal';
                    $shipInfo = ShippingHelper::parseShippingMethod($rawService);

                    // Bersihkan nama layanan (Hapus nama kurir ganda)
                    $cleanService = str_replace($shipInfo['courier_name'], '', $shipInfo['service_name']);
                    $cleanService = trim($cleanService);
                    if (empty($cleanService)) $cleanService = 'Regular';

                    // Format tampilan yang rapi (TIKI - REGULER)
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
                        // Gunakan format yang sudah dibersihkan
                        'jasa_ekspedisi_aktual' => $displayEkspedisi,
                    ];
                }
            } catch (\Exception $e) {
                // PENTING: Log error agar ketahuan kenapa "Tidak Ditemukan"
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

        if ($result) {
            return view('public.tracking.index', compact('result'));
        }

        return redirect()->route('tracking.index')->with('error', "Nomor resi '{$resi}' tidak ditemukan.");
    }
    /**
     * [CETAK THERMAL ADMIN]
     * Logika: DB1 -> DB2 (Percetakan)
     */

    public function cetakThermal($resi)
    {
        // ====================================================================
        // BAGIAN 1: KODE ASLI ANDA (SAYA COPY PASTE 100% TANPA UBAH)
        // ====================================================================

        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->orWhere('shipping_ref', $resi) // <--- TAMBAHAN: Cari berdasarkan Shipping Ref
            ->first();

        if (!$pesanan) {
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->orWhere('shipping_ref', $resi) // <--- TAMBAHAN: Cari berdasarkan Shipping Ref
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

        // ====================================================================
        // BAGIAN 2: MAPPING DB 2 (PERCETAKAN) - SESUAI NAMA KOLOM BARU
        // ====================================================================
        if (!$pesanan) {
            try {
                $orderPercetakan = \DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($orderPercetakan) {

                    // Logic Ekspedisi (ambil kata pertama dari 'TIKI - Tiki Reguler')
                    $rawService = $orderPercetakan->courier_service ?? 'Internal';
                    $expeditionName = explode(' - ', $rawService)[0];

                    $pesanan = (object)[
                        // KOLOM UTAMA
                        'resi' => $orderPercetakan->shipping_ref ?? $orderPercetakan->order_number,
                        'nomor_invoice' => $orderPercetakan->order_number,
                        'status' => $orderPercetakan->status,

                        // PENGIRIM (Tetap Sancaka)
                        'sender_name' => 'Sancaka Percetakan',
                        'sender_phone' => '08819435180',
                        'sender_address' => 'Jl.Dr.Wahidin No.18 A',
                        'sender_village' => 'Ketanggi',
                        'sender_district' => 'Ngawi',
                        'sender_regency' => 'Ngawi',
                        'sender_province' => 'Jawa Timur',
                        'sender_postal_code' => '63211',

                        // PENERIMA (Sesuai Kolom DB: customer_name, destination_address)
                        'receiver_name' => $orderPercetakan->customer_name ?? 'Pelanggan',
                        'receiver_phone' => $orderPercetakan->customer_phone ?? '-',
                        'receiver_address' => $orderPercetakan->destination_address ?? '-',
                        'receiver_village' => '', // Kosongkan agar tidak undefined
                        'receiver_district' => '',
                        'receiver_regency' => '',
                        'receiver_province' => '',
                        'receiver_postal_code' => '',

                        // PAKET & BIAYA (Sesuai Kolom DB: final_price, shipping_cost)
                        'weight' => 1000,
                        'item_description' => 'Produk Percetakan',
                        'item_price' => $orderPercetakan->final_price ?? 0, // Harga setelah diskon
                        'shipping_cost' => $orderPercetakan->shipping_cost ?? 0,
                        'ongkir' => $orderPercetakan->shipping_cost ?? 0,
                        'insurance_cost' => 0,
                        // Total COD = Harga Akhir + Ongkir
                        'total_cod' => ($orderPercetakan->final_price ?? 0) + ($orderPercetakan->shipping_cost ?? 0),
                        'cod_amount' => 0,

                        // EKSPEDISI (Sesuai Kolom DB: courier_service)
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
            } catch (\Exception $e) {
                // Silent Fail
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
        // 1. Siapkan Wadah History
        $normalizedHistories = collect([]);

        // 2. Ambil History dari API KiriminAja (Jika Ada)
        if (isset($rawResponse['histories']) && is_array($rawResponse['histories'])) {
            foreach ($rawResponse['histories'] as $history) {
                // Bersihkan teks status yang kotor
                $statusText = preg_replace('/\s\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}\s\|/i', '', $history['status'] ?? 'N/A');

                $normalizedHistories->push((object)[
                    'status' => $statusText,
                    'lokasi' => $rawResponse['details']['destination']['city'] ?? '-', // Lokasi default kota tujuan
                    'keterangan' => $history['status'] ?? null,
                    'created_at' => Carbon::parse($history['created_at'])->timezone('Asia/Jakarta'),
                ]);
            }
        }

        // 3. TAMBAHKAN HISTORY LOKAL "PESANAN DIBUAT" (WAJIB ADA)
        // Kita ambil dari 'created_at' database lokal
        if ($pesanan->created_at) {
            $waktuDibuat = Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta');

            // Cek apakah sudah ada status serupa dari API di jam yang sangat dekat (biar gak dobel)
            // Tapi logika ini kita longgarkan: Hanya skip kalau PERSIS SAMA teksnya.
            // Kalau beda teks, tetep tampilin biar user tau kapan order masuk sistem.

            $alreadyExists = $normalizedHistories->contains(function ($h) use ($waktuDibuat) {
                return str_contains(strtolower($h->status), 'created') ||
                       str_contains(strtolower($h->status), 'dibuat');
            });

            if (!$alreadyExists) {

                // --- LOGIKA LOKASI SESUAI AKUN (PENGIRIM) ---
                // Kita ambil Kota/Kabupaten Pengirim sebagai lokasi pembuatan
                $lokasiAkun = strtoupper($pesanan->sender_regency ?? 'NGAWI');
                if (!empty($pesanan->sender_district)) {
                    $lokasiAkun = strtoupper($pesanan->sender_district) . ', ' . $lokasiAkun;
                }

                $normalizedHistories->push((object)[
                    'status' => 'Pesanan Dibuat Oleh TOKOSANCAKA.COM', // <-- STATUS REQUEST MAS
                    'lokasi' => $lokasiAkun, // <-- LOKASI DINAMIS DARI DB
                    'keterangan' => 'Pesanan berhasil dibuat di sistem SANCAKA EXPRESS. <br><b>INGIN KIRIM PAKET?</b> <a href="https://tokosancaka.com/register" target="_blank">Daftar AKUN ANDA Disini, GRATIS! </a>',
                    'created_at' => $waktuDibuat,
                ]);
            }
        }

        // 4. Sorting: Paling Baru di Atas
        $sortedHistories = $normalizedHistories->sortByDesc('created_at')->values();

        // 5. Return Data Lengkap
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

            // Status Prioritas: Ambil dari API Text -> API Delivered -> DB Lokal
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
}
