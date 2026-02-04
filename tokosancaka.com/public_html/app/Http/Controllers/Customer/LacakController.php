<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Order;
use App\Models\OrderMarketplace; // <-- Pastikan ini sudah di-import
use App\Models\ScannedPackage;
use Illuminate\Support\Facades\Auth;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Log; // <-- Tambahkan Log
use Exception; // <-- Tambahkan Exception

class LacakController extends Controller
{
    /**
     * Menampilkan halaman pelacakan dan hasil pencarian untuk SEMUA jenis resi.
     */
    public function index(Request $request)
    {
        $result = null;
        $resiToSearch = $request->input('resi', $request->input('search'));
        $customer = Auth::user();

        if ($resiToSearch) {
            $pesanan = null;
            $orderModel = null;
            $orderMarketplaceModel = null; 

            // 1. Cari di tabel pesanan (MANUAL)
            $pesanan = Pesanan::where(function ($query) use ($resiToSearch) {
                $query->where('resi', $resiToSearch)
                      ->orWhere('resi_aktual', $resiToSearch)
                      ->orWhere('nomor_invoice', $resiToSearch); 
            })
            ->where('id_pengguna_pembeli', $customer->id_pengguna)
            ->first();

            // 2. Jika tidak ditemukan, cari di scanned_packages (SPX)
            if (!$pesanan) {
                $pesanan = ScannedPackage::where('resi_number', $resiToSearch)
                    ->where('user_id', $customer->id_pengguna)
                    ->first();
            }

            // 3. Jika tetap tidak ditemukan, cari di tabel Order (LAMA)
            if (!$pesanan) {
                $orderModel = Order::where(function ($query) use ($resiToSearch) {
                        $query->where('shipping_reference', $resiToSearch)
                              ->orWhere('invoice_number', $resiToSearch);
                    })
                    ->where('user_id', $customer->id_pengguna) 
                    ->with(['store.user', 'user']) // Load relasi
                    ->first();
            }
            
            // 4. Jika tetap tidak ditemukan, cari di OrderMarketplace (BARU)
            if (!$pesanan && !$orderModel) {
                $orderMarketplaceModel = OrderMarketplace::where(function ($query) use ($resiToSearch) {
                        $query->where('shipping_resi', $resiToSearch)
                              ->orWhere('invoice_number', $resiToSearch);
                    })
                    ->where('user_id', $customer->id_pengguna)
                    ->with(['store.user', 'user', 'items.product']) // Load relasi
                    ->first();
            }

            // --- Logika Mapping Data ---
            if ($pesanan) {
                $result = $this->_mapPesananToResult($pesanan);
            } 
            elseif ($orderModel) {
                $result = $this->_mapOrderToResult($orderModel);
            }
            elseif ($orderMarketplaceModel) {
                $result = $this->_mapOrderMarketplaceToResult($orderMarketplaceModel);
            }

            // 5. Jika ditemukan, ambil data tracking dari KiriminAja
            if ($result) {
                $kiriminAja = new KiriminAjaService();
                $orderId = $result['resi_aktual'] ?? $result['resi'];
                $serviceType = $result['service_type'] ?? null; // 'regular', 'instant', dll.
                
                $trackingData = null;
                if ($serviceType && in_array($serviceType, ['express', 'instant', 'regular', 'cargo', 'sameday']) && $orderId) {
                    try {
                        $trackingData = $kiriminAja->track($serviceType, $orderId);
                    } catch (Exception $e) {
                        Log::error('Gagal tracking KiriminAja', ['error' => $e->getMessage(), 'resi' => $orderId]);
                    }
                }

                $histories = collect();
                if ($trackingData && isset($trackingData['status']) && $trackingData['status'] === true) {
                    
                    // ==========================================================
                    // PERBAIKAN: INI ADALAH BLOK LOGIKA YANG BENAR
                    // ==========================================================
                    if ($serviceType === 'instant') {
                        // Mapping Instant
                        $resultData = $trackingData['result'] ?? [];
                        $histories->push((object)[
                            'status' => 'Pesanan dibuat',
                            'lokasi' => $resultData['origin']['address'] ?? '-',
                            'keterangan' => 'Pesanan telah diterima oleh driver',
                            'created_at' => $resultData['date']['created_at'] ?? null,
                        ]);
                        // (Tambahkan status lain dari $resultData['status'] jika perlu)
                    } else {
                        // Mapping express/regular/cargo
                        foreach ($trackingData['histories'] ?? [] as $h) {
                            $histories->push((object)[
                                'status' => $h['status'] ?? null,
                                'lokasi' => $h['receiver'] ?? '-',
                                'keterangan' => $h['status'] ?? null,
                                'created_at' => $h['created_at'] ?? null,
                            ]);
                        }
                    }
                    // ==========================================================
                    // AKHIR PERBAIKAN
                    // ==========================================================
                }
                $result['histories'] = $histories;
            }
        } // akhir if ($resiToSearch)

        // ==========================================================
        // PERBAIKAN: 'return' SUDAH BERADA DI DALAM FUNGSI INDEX
        // ==========================================================
        return view('customer.lacak.index', [
            'result' => $result,
            'resi' => $resiToSearch,
        ]);
    } // <-- AKHIR FUNGSI INDEX YANG BENAR

    // --- FUNGSI HELPER UNTUK MAPPING DATA ---

    private function _mapPesananToResult($pesanan)
    {
        // Tipe 1: Pesanan Manual atau SPX
        return [
            'is_pesanan' => true,
            'resi' => $pesanan->resi ?? $pesanan->resi_number,
            'pengirim' => $pesanan->sender_name ?? 'N/A',
            'alamat_pengirim' => implode(', ', array_filter([
                $pesanan->sender_address ?? null, $pesanan->sender_village ?? null,
                $pesanan->sender_district ?? null, $pesanan->sender_regency ?? null,
                $pesanan->sender_province ?? null, $pesanan->sender_postal_code ?? null,
            ])) ?: 'N/A',
            'no_pengirim' => $pesanan->sender_phone,
            'penerima' => $pesanan->receiver_name ?? 'N/A',
            'alamat_penerima' => implode(', ', array_filter([
                $pesanan->receiver_address ?? null, $pesanan->receiver_village ?? null,
                $pesanan->receiver_district ?? null, $pesanan->receiver_regency ?? null,
                $pesanan->receiver_province ?? null, $pesanan->receiver_postal_code ?? null,
            ])) ?: 'N/A',
            'no_penerima' => $pesanan->receiver_phone,
            'status' => $pesanan->status ?? $pesanan->status_pesanan ?? 'N/A',
            'tanggal_dibuat' => $pesanan->created_at ?? $pesanan->tanggal_pesanan,
            'resi_aktual' => $pesanan->resi_aktual ?? $pesanan->resi ?? $pesanan->resi_number,
            'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual ?? explode('-', $pesanan->expedition ?? '')[1] ?? 'N/A',
            'service_type' => $pesanan->service_type ?? explode('-', $pesanan->expedition ?? '')[0] ?? null,
        ];
    }

    private function _mapOrderToResult($orderModel)
    {
        // Tipe 2: Order (Lama)
        return [
            'is_pesanan' => true,
            'resi' => $orderModel->shipping_reference,
            'pengirim' => $orderModel->store->name ?? 'N/A',
            'alamat_pengirim' => $orderModel->store->address_detail ?? 'N/A',
            'no_pengirim' => optional($orderModel->store->user)->no_wa ?? 'N/A', // Lebih aman
            'penerima' => $orderModel->user->nama_lengkap ?? 'N/A',
            'alamat_penerima' => $orderModel->shipping_address ?? 'N/A',
            'no_penerima' => $orderModel->user->no_wa ?? 'N/A',
            'status' => $orderModel->status ?? 'N/A',
            'tanggal_dibuat' => $orderModel->created_at,
            'resi_aktual' => $orderModel->shipping_reference,
            'jasa_ekspedisi_aktual' => $orderModel->courier ?? null,
            'service_type' => explode('-', $orderModel->service_type)[0] ?? null,
        ];
    }

    // FUNGSI HELPER BARU UNTUK MARKETPLACE
    private function _mapOrderMarketplaceToResult($order)
    {
        // Tipe 3: OrderMarketplace (Baru)
        $shippingParts = explode('-', $order->shipping_method);
        
        return [
            'is_pesanan' => true,
            'resi' => $order->shipping_resi ?? $order->invoice_number,
            'pengirim' => $order->store->name ?? 'N/A',
            'alamat_pengirim' => implode(', ', array_filter([
                optional($order->store)->address_detail ?? null, optional($order->store)->village ?? null,
                optional($order->store)->district ?? null, optional($order->store)->regency ?? null,
                optional($order->store)->province ?? null, optional($order->store)->zip_code ?? null,
            ])) ?: 'N/A',
            'no_pengirim' => optional($order->store->user)->no_wa ?? 'N/A', // Lebih aman
            'penerima' => $order->user->nama_lengkap ?? 'N/A',
            'alamat_penerima' => $order->shipping_address ?? 'N/A',
            'no_penerima' => $order->user->no_wa ?? 'N/A',
            'status' => $order->status ?? 'N/A',
            'tanggal_dibuat' => $order->created_at,
            'resi_aktual' => $order->shipping_resi,
            'jasa_ekspedisi_aktual' => $shippingParts[1] ?? 'N/A', // cth: anteraja
            'service_type' => $shippingParts[0] ?? null, // cth: regular
        ];
    }

} // <-- AKHIR CLASS