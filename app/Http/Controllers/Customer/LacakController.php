<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\ScannedPackage;
use Illuminate\Support\Facades\Auth;
use App\Services\KiriminAjaService;

class LacakController extends Controller
{
    /**
     * Menampilkan halaman pelacakan dan hasil pencarian untuk kedua jenis resi.
     * Data diambil langsung dari database setiap kali halaman diakses, sehingga selalu real-time.
     */
    public function index(Request $request)
{
    $result = null;
    $resiToSearch = $request->input('resi', $request->input('search'));
    $customer = Auth::user();

    if ($resiToSearch) {
        // 1. Cari di tabel pesanan (internal Sancaka)
        $pesanan = Pesanan::where('resi', $resiToSearch)
                          ->orWhere('resi_aktual', $resiToSearch)
                          ->where('id_pengguna_pembeli', $customer->id_pengguna)
                          ->first();

        // 2. Jika tidak ditemukan, coba cari di scanned_packages (SPX)
        if (!$pesanan) {
            $pesanan = ScannedPackage::where('resi_number', $resiToSearch)
                                     ->where('user_id', $customer->id_pengguna)
                                     ->first();
        }

        $orderModel = null;

        // 3. Jika tetap tidak ditemukan, coba cari di tabel Order
        if (!$pesanan) {
            $orderModel = Order::where('shipping_reference', $resiToSearch)->first();

            if ($orderModel) {
                $pesanan = (object)[
                    'resi' => $orderModel->shipping_reference,
                    'resi_aktual' => $orderModel->shipping_reference,

                    // Sender
                    'sender_name' => $orderModel->store->name ?? 'N/A',
                    'sender_address' => $orderModel->store->address_detail ?? 'N/A',
                    'sender_province' => $orderModel->store->province ?? 'N/A',
                    'sender_regency' => $orderModel->store->regency ?? 'N/A',
                    'sender_district' => $orderModel->store->district ?? 'N/A',
                    'sender_village' => $orderModel->store->village ?? 'N/A',
                    'sender_postal_code' => $orderModel->store->postal_code ?? 'N/A',
                    'sender_phone' => $orderModel->store->user->no_wa ?? 'N/A',

                    // Receiver
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
                    'service_type' => explode('-', $orderModel->service_type)[0] ?? null,
                    'created_at' => $orderModel->created_at,
                ];
            }
        }

        // 4. Jika ditemukan, mapping tracking
        if ($pesanan) {
            $kiriminAja = new KiriminAjaService();
            $orderId = $pesanan->resi_aktual ?? $pesanan->resi;
            $trackingData = null;

            if ($pesanan->service_type && in_array($pesanan->service_type, ['express', 'instant']) && $orderId) {
                $trackingData = $kiriminAja->track($pesanan->service_type, $orderId);
            }

            $histories = collect();
            if ($trackingData) {
                if ($pesanan->service_type === 'instant') {
                    $resultData = $trackingData['result'] ?? [];
                    $histories->push((object)[
                        'status' => 'Pesanan dibuat',
                        'lokasi' => $resultData['origin']['address'] ?? '-',
                        'keterangan' => 'Pesanan telah diterima oleh driver',
                        'created_at' => $resultData['date']['created_at'] ?? null,
                    ]);
                } else {
                    foreach ($trackingData['histories'] ?? [] as $h) {
                        $histories->push((object)[
                            'status' => $h['status'] ?? null,
                            'lokasi' => $h['receiver'] ?? '-',
                            'keterangan' => $h['status'] ?? null,
                            'created_at' => $h['created_at'] ?? null,
                        ]);
                    }
                }
            }

            $result = [
                'is_pesanan' => true,
                'resi' => $pesanan->resi,
                'pengirim' => $pesanan->sender_name ?? 'N/A',
                'alamat_pengirim' => implode(', ', array_filter([
                    $pesanan->sender_address ?? null,
                    $pesanan->sender_village ?? null,
                    $pesanan->sender_district ?? null,
                    $pesanan->sender_regency ?? null,
                    $pesanan->sender_province ?? null,
                    $pesanan->sender_postal_code ?? null,
                ])) ?: 'N/A',
                'no_pengirim' => $pesanan->sender_phone,
                'penerima' => $pesanan->receiver_name ?? 'N/A',
                'alamat_penerima' => implode(', ', array_filter([
                    $pesanan->receiver_address ?? null,
                    $pesanan->receiver_village ?? null,
                    $pesanan->receiver_district ?? null,
                    $pesanan->receiver_regency ?? null,
                    $pesanan->receiver_province ?? null,
                    $pesanan->receiver_postal_code ?? null,
                ])) ?: 'N/A',
                'no_penerima' => $pesanan->receiver_phone,
                'status' => $pesanan->status,
                'tanggal_dibuat' => $pesanan->created_at,
                'histories' => $histories,
                'resi_aktual' => $pesanan->resi_aktual,
                'jasa_ekspedisi_aktual' => $pesanan->jasa_ekspedisi_aktual,
            ];
        }
    }

    return view('customer.lacak.index', [
        'result' => $result,
        'resi' => $resiToSearch,
    ]);
}

}
