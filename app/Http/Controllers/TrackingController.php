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
use Illuminate\Support\Facades\DB; // <--- WAJIB ADA UNTUK KONEKSI DB2
use Illuminate\Support\Facades\Log;

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
     * TRACKING UTAMA (PUBLIC)
     * Mencari dan menampilkan hasil pelacakan paket dari berbagai sumber.
     */
    public function trackPackage(Request $request)
    {
        $request->validate(['resi' => 'required|string|max:255']);
        $resi = $request->input('resi');
        $result = null;

        // ==========================================================
        // LANGKAH 1: CARI DI TABEL PESANAN & ORDER (DB 1 - ORIGINAL)
        // ==========================================================
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('resi_aktual', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->first();

        // Jika tidak ketemu di Pesanan, cari di Order
        if (!$pesanan) {
            $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->first();
            
            if ($orderModel) {
                // Mapping Data Order ke Object Pesanan (Kode Asli Anda)
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
                    'jasa_ekspedisi_aktual' => $orderModel->courier ?? null,
                    'service_type' => explode('-', $orderModel->service_type ?? '')[0] ?? 'regular', 
                    'created_at' => $orderModel->created_at,
                ];
            }
        }

        // JIKA KETEMU DI DB 1, PROSES API KIRIMINAJA
        if ($pesanan) {
            $kiriminAja = new KiriminAjaService();
            $orderId = $pesanan->nomor_invoice ?? $pesanan->resi;
            $serviceType = $pesanan->service_type ?? 'regular';

            // Bersihkan service type jika formatnya 'regular-jne-...'
            if (str_contains($serviceType, '-')) {
                $serviceType = explode('-', $serviceType)[0];
            }

            $trackingData = $kiriminAja->track($serviceType, $orderId);

            if ($trackingData && ($trackingData['status'] ?? false)) {
                 $result = $this->normalizeKiriminAjaResponse($trackingData, $pesanan);
            } else {
                 // Fallback Data Lokal
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
        // LANGKAH 1.5: CARI DI DB 2 (PERCETAKAN) <--- TAMBAHAN BARU
        // ==========================================================
        // Hanya jalan jika DB 1 ZONK ($result masih null)
        if (!$result) {
            try {
                $percetakan = DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($percetakan) {
                    // Buat History Palsu Sederhana
                    $fakeHistory = collect([
                        (object)[
                            'status' => 'Pesanan Dibuat',
                            'lokasi' => 'Percetakan Sancaka',
                            'keterangan' => 'Pesanan masuk ke sistem percetakan.',
                            'created_at' => Carbon::parse($percetakan->created_at)
                        ],
                        (object)[
                            'status' => $percetakan->status, // Status terakhir (misal: Selesai)
                            'lokasi' => 'Percetakan Sancaka',
                            'keterangan' => 'Status terkini: ' . $percetakan->status,
                            'created_at' => Carbon::parse($percetakan->updated_at ?? $percetakan->created_at)
                        ]
                    ])->sortByDesc('created_at')->values();

                    $result = [
                        'is_pesanan' => true,
                        'resi' => $percetakan->shipping_ref ?? $percetakan->order_number,
                        'resi_aktual' => $percetakan->shipping_ref,
                        
                        // Pengirim = Kita Sendiri
                        'pengirim' => 'Sancaka Percetakan',
                        'alamat_pengirim' => 'Jl.Dr.Wahidin No.18 A, Ngawi',
                        'no_pengirim' => '08819435180',
                        
                        // Penerima
                        'penerima' => $percetakan->customer_name ?? 'Pelanggan',
                        'alamat_penerima' => $percetakan->destination_address ?? '-',
                        'no_penerima' => $percetakan->customer_phone ?? '-',
                        
                        'status' => $percetakan->status,
                        'tanggal_dibuat' => $percetakan->created_at,
                        'histories' => $fakeHistory,
                        'jasa_ekspedisi_aktual' => $percetakan->courier_service ?? 'Internal',
                    ];
                }
            } catch (\Exception $e) {
                // Silent Error: Jika DB2 error, lanjut ke langkah berikutnya
            }
        }

        // ==========================================================
        // LANGKAH 2: CARI DI SPX SCAN (ORIGINAL)
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
        // LANGKAH 3: CARI DI SCANNED PACKAGES (ORIGINAL)
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
     * CETAK THERMAL
     * Menggabungkan DB1 (Original) dan DB2 (Percetakan)
     */
    public function cetakThermal($resi)
    {
        // ==========================================================
        // 1. CARI DI DB 1 (LOGIKA ORIGINAL - JANGAN DIGANGGU)
        // ==========================================================
        $pesanan = Pesanan::where('resi', $resi)
            ->orWhere('nomor_invoice', $resi)
            ->first();

        if (!$pesanan) {
             $orderModel = Order::with(['store', 'user'])
                ->where('shipping_reference', $resi)
                ->orWhere('invoice_number', $resi)
                ->first();
                
             if ($orderModel) {
                 // Mapping Original Anda
                 $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'nomor_invoice' => $orderModel->invoice_number,
                    'status' => $orderModel->status,
                    'sender_name' => $orderModel->store->name ?? '-',
                    'sender_phone' => $orderModel->store->user->no_wa ?? '-',
                    'sender_address' => $orderModel->store->address_detail ?? '-',
                    'sender_village' => $orderModel->store->village ?? '',
                    'sender_district' => $orderModel->store->district ?? '',
                    'sender_regency' => $orderModel->store->regency ?? '',
                    'sender_province' => $orderModel->store->province ?? '',
                    'sender_postal_code' => $orderModel->store->postal_code ?? '',
                    'receiver_name' => $orderModel->user->nama_lengkap ?? '-',
                    'receiver_phone' => $orderModel->user->no_wa ?? '-',
                    'receiver_address' => $orderModel->shipping_address ?? '-',
                    'receiver_village' => $orderModel->user->village ?? '',
                    'receiver_district' => $orderModel->user->district ?? '',
                    'receiver_regency' => $orderModel->user->regency ?? '',
                    'receiver_province' => $orderModel->user->province ?? '',
                    'receiver_postal_code' => $orderModel->user->postal_code ?? '',
                    'weight' => $orderModel->total_weight ?? 1000,
                    'isi_paket' => 'Paket Toko Online',
                    'nilai_barang' => $orderModel->sub_total ?? 0,
                    'ongkir' => $orderModel->shipping_cost ?? 0,
                    'biaya_asuransi' => 0,
                    'cod_amount' => 0,
                    'total_cod' => $orderModel->grand_total ?? 0,
                    'payment_method' => $orderModel->payment_method ?? 'Transfer',
                    'courier' => $orderModel->courier ?? 'JNE',
                    'service_type' => 'REG',
                    'jasa_ekspedisi_aktual' => $orderModel->courier ?? 'JNE',
                    'resi_aktual' => null,
                    'created_at' => $orderModel->created_at,
                    'panjang' => 10, 'lebar' => 10, 'tinggi' => 10,
                 ];
             }
        }

        // ==========================================================
        // 2. CARI DI DB 2 (PERCETAKAN) <--- TAMBAHAN BARU
        // ==========================================================
        // Hanya jalan jika DB1 ZONK ($pesanan masih null)
        if (!$pesanan) {
            try {
                $orderPercetakan = DB::connection('mysql_second')
                    ->table('orders')
                    ->where('order_number', $resi)
                    ->orWhere('shipping_ref', $resi)
                    ->first();

                if ($orderPercetakan) {
                    $pesanan = (object)[
                        'resi' => $orderPercetakan->shipping_ref ?? $orderPercetakan->order_number,
                        'nomor_invoice' => $orderPercetakan->order_number,
                        'status' => $orderPercetakan->status,
                        
                        // Pengirim (Hardcode Sancaka)
                        'sender_name' => 'Sancaka Percetakan',
                        'sender_phone' => '08819435180',
                        'sender_address' => 'Jl.Dr.Wahidin No.18 A',
                        'sender_village' => 'Ketanggi',
                        'sender_district' => 'Ngawi',
                        'sender_regency' => 'Ngawi',
                        'sender_province' => 'Jawa Timur',
                        'sender_postal_code' => '63211',

                        // Penerima
                        'receiver_name' => $orderPercetakan->customer_name ?? 'Pelanggan',
                        'receiver_phone' => $orderPercetakan->customer_phone ?? '-',
                        'receiver_address' => $orderPercetakan->destination_address ?? '-',
                        'receiver_village' => '',
                        'receiver_district' => '',
                        'receiver_regency' => '',
                        'receiver_province' => '',
                        'receiver_postal_code' => '',

                        'weight' => 1000,
                        'isi_paket' => 'Produk Percetakan',
                        'nilai_barang' => $orderPercetakan->total_amount ?? 0,
                        'ongkir' => 0,
                        'biaya_asuransi' => 0,
                        'total_cod' => $orderPercetakan->total_amount ?? 0,
                        'cod_amount' => 0,
                        'payment_method' => $orderPercetakan->payment_method ?? 'Manual',
                        'courier' => $orderPercetakan->courier_service ?? 'Express',
                        'service_type' => 'REG',
                        'jasa_ekspedisi_aktual' => $orderPercetakan->courier_service ?? 'Express',
                        'resi_aktual' => $orderPercetakan->shipping_ref,
                        'created_at' => $orderPercetakan->created_at,
                        'panjang' => 10, 'lebar' => 10, 'tinggi' => 10,
                    ];
                }
            } catch (\Exception $e) {
                // Silent Fail jika DB2 error
            }
        }

        if (!$pesanan) {
            abort(404, 'Pesanan tidak ditemukan untuk dicetak.');
        }
        
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    /**
     * Helper Normalisasi API KiriminAja (Sama seperti sebelumnya)
     */
    private function normalizeKiriminAjaResponse(array $rawResponse, $pesanan): array
    {
        if (!isset($rawResponse['status']) || !$rawResponse['status'] || !isset($rawResponse['details'])) {
            return [
                'error' => $rawResponse['text'] ?? 'Gagal mengambil data pelacakan.',
                'is_external_error' => true
            ];
        }

        $details = $rawResponse['details'];
        $histories = $rawResponse['histories'] ?? [];

        $normalizedHistories = collect($histories)->map(function ($history) use ($details) {
            $timestampWIB = Carbon::parse($history['created_at'], 'Asia/Jakarta');
            $statusText = preg_replace('/\s\d{2}-\d{2}-\d{4}\s\d{2}:\d{2}\s\|/i', '', $history['status'] ?? 'N/A');

            return (object)[
                'status' => $statusText,
                'lokasi' => $details['destination']['city'] ?? '-',
                'keterangan' => $history['status'] ?? null,
                'created_at' => $timestampWIB,
            ];
        })->toArray();

        if ($pesanan->created_at) {
            $createdHistory = (object)[
                'status' => 'Pesanan Dibuat',
                'lokasi' => 'Sistem Internal',
                'keterangan' => 'Data diterima sistem Sancaka Express.',
                'created_at' => Carbon::parse($pesanan->created_at)->timezone('Asia/Jakarta'),
            ];
            
            $isDuplicate = collect($normalizedHistories)->contains(function ($h) use ($createdHistory) {
                return $h->created_at->diffInMinutes($createdHistory->created_at) < 5 || str_contains(strtolower($h->status), 'dibuat');
            });

            if (!$isDuplicate) {
                $normalizedHistories[] = $createdHistory;
            }
        }

        $sortedHistories = collect($normalizedHistories)->sortByDesc('created_at')->values();

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
            'status' => $rawResponse['text'] ?? ($details['delivered'] ? 'Telah Diterima' : $pesanan->status),
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