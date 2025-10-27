<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Services\KiriminAjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TripayCallbackController extends Controller
{
    /**
     * Injeksi KiriminAjaService.
     */
    protected $kirimaja;

    public function __construct(KiriminAjaService $kirimaja)
    {
        $this->kirimaja = $kirimaja;
    }

    /**
     * Menangani notifikasi webhook (callback) dari TriPay.
     */
    public function handle(Request $request)
    {
        // 1. Ambil Private Key dari config
        $privateKey = config('tripay.private_key');
        
        // 2. Validasi Signature
        $callbackSignature = $request->header('X-Callback-Signature');
        $json = $request->getContent();
        $signature = hash_hmac('sha256', $json, $privateKey);

        if ($signature !== $callbackSignature) {
            Log::warning('Tripay Webhook: Invalid signature.', ['request' => $json]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // 3. Decode JSON
        $data = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
             Log::error('Tripay Webhook: Invalid JSON payload.', ['request' => $json]);
            return response()->json(['success' => false, 'message' => 'Invalid JSON'], 400);
        }

        Log::info('Tripay Webhook Diterima:', (array) $data);

        // 4. Hanya proses jika status 'PAID' (LUNAS)
        if ($data && isset($data->status) && $data->status == 'PAID') {
            
            DB::beginTransaction();
            try {
                // 5. Cari Pesanan. 'merchant_ref' adalah 'nomor_invoice' kita.
                $pesanan = Pesanan::where('nomor_invoice', $data->merchant_ref)
                                  ->where('status', 'Menunggu Pembayaran') // Pastikan hanya proses yang belum lunas
                                  ->lockForUpdate() // Kunci row agar tidak ada proses ganda
                                  ->first();

                if (!$pesanan) {
                    // Jika pesanan tidak ditemukan atau statusnya BUKAN 'Menunggu Pembayaran',
                    // artinya sudah diproses. Beri respon sukses agar TriPay berhenti mengirim.
                    Log::info('Tripay Webhook: Order not found or already processed.', ['merchant_ref' => $data->merchant_ref]);
                    DB::rollBack();
                    return response()->json(['success' => true]); 
                }

                // 6. INI BAGIAN KUNCI: MENDORONG ORDER KE KIRIMINAJA
                // Kita panggil fungsi helper yang logikanya sama dengan di PesananController
                $kiriminResponse = $this->_pushToKiriminAja($pesanan);

                if (($kiriminResponse['status'] ?? false) !== true) {
                    // Jika gagal push ke KiriminAja, JANGAN rollback. 
                    // Pembayaran tetap sukses. Catat errornya agar bisa di-push manual.
                    Log::error('Tripay Webhook: Gagal push ke KiriminAja.', [
                        'invoice' => $pesanan->nomor_invoice,
                        'error' => $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Unknown error')
                    ]);
                    // Tetap update status bayar, tapi tandai gagal push
                    $pesanan->status = 'Diproses'; // Status "Dibayar tapi Gagal Push"
                    $pesanan->status_pesanan = 'Gagal Push ke Ekspedisi';
                    // Anda bisa tambahkan kolom 'failed_push' => true jika perlu
                } else {
                    // 7. Update status pesanan JIKA BERHASIL
                    $pesanan->status = 'Menunggu Pickup';
                    $pesanan->status_pesanan = 'Menunggu Pickup';
                    $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                }
                
                $pesanan->save();
                DB::commit();

                // Kirim notifikasi WA (opsional, bisa ditambahkan di sini jika perlu)
                // $this->_sendWhatsappNotificationAfterPayment($pesanan);

                return response()->json(['success' => true]);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Tripay Webhook: Exception error.', [
                    'invoice' => $data->merchant_ref ?? 'unknown',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Beri respon 500 agar TriPay mencoba lagi
                return response()->json(['success' => false, 'message' => 'Server Error'], 500);
            }
        }

        // Beri respon 200 untuk status lain (e.g., EXPIRED, FAILED)
        return response()->json(['success' => true]);
    }


    /**
     * Fungsi helper untuk push order ke KiriminAja.
     * Logika ini diadaptasi dari PesananController::_createKiriminAjaOrder
     * menggunakan data dari objek $pesanan.
     */
    private function _pushToKiriminAja(Pesanan $pesanan): array
    {
        // 1. Siapkan data alamat dari $pesanan
        // (Berkat perbaikan Anda di PesananController, data ini sekarang tersimpan)
        $senderData = [
            'lat' => $pesanan->sender_lat,
            'lng' => $pesanan->sender_lng,
            'kirimaja_data' => [
                'district_id' => $pesanan->sender_district_id,
                'subdistrict_id' => $pesanan->sender_subdistrict_id,
                'postal_code' => $pesanan->sender_postal_code,
            ]
        ];

        $receiverData = [
            'lat' => $pesanan->receiver_lat,
            'lng' => $pesanan->receiver_lng,
            'kirimaja_data' => [
                'district_id' => $pesanan->receiver_district_id,
                'subdistrict_id' => $pesanan->receiver_subdistrict_id,
                'postal_code' => $pesanan->receiver_postal_code,
            ]
        ];
        
        // 2. Siapkan data order dari $pesanan
        $data = $pesanan->toArray();
        
        // 3. Pembayaran Non-COD (TriPay) berarti $cod_value = 0
        $cod_value = 0;

        // 4. Adaptasi logika dari PesananController::_createKiriminAjaOrder
        $expeditionParts = explode('-', $data['expedition']);
        $count = count($expeditionParts);

        $serviceGroup = $expeditionParts[0] ?? null;
        $courier      = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;
        $shipping_cost = ($count > 4) ? (int)$expeditionParts[$count - 3] : 0; // Ambil ongkir asli

        if (in_array($serviceGroup, ['instant', 'sameday'])) { 
            // Logika untuk Instant (jika Anda mengizinkan Tripay untuk instant)
            $payload = [
                'service' => $courier,
                'service_type' => $service_type,
                'vehicle' => 'motor',
                'order_prefix' => $pesanan->nomor_invoice,
                'packages' => [
                    [
                        'destination_name' => $data['receiver_name'],
                        'destination_phone' => $data['receiver_phone'],
                        'destination_lat' => $receiverData['lat'],
                        'destination_long' => $receiverData['lng'],
                        'destination_address' => $data['receiver_address'],
                        'destination_address_note' => $data['receiver_note'] ?? '-',
                        'origin_name' => $data['sender_name'],
                        'origin_phone' => $data['sender_phone'],
                        'origin_lat' => $senderData['lat'],
                        'origin_long' => $senderData['lng'],
                        'origin_address' => $data['sender_address'],
                        'origin_address_note' => $data['sender_note'] ?? '-',
                        'shipping_price' => (int)$shipping_cost,
                        'item' => [
                            'name' => $data['item_description'],
                            'description' => 'Pesanan dari pelanggan',
                            'price' => (int)$data['item_price'],
                            'weight' => (int)$data['weight'],
                        ]
                    ]
                ]
            ];
            return $this->kirimaja->createInstantOrder($payload);

        } else {
            // Logika untuk Express/Regular/Cargo
            $schedule = $this->kirimaja->getSchedules();
            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 
                'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 
                'schedule' => $schedule['clock'], 'platform_name' => 'tokosancaka.com',
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 'item_name' => $data['item_description'], 'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'], 'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 
                    'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => $data['weight'], 'width' => $data['width'] ?? 1, 'height' => $data['height'] ?? 1, 'length' => $data['length'] ?? 1,
                    'item_value' => (int)$data['item_price'], 'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0,
                    'cod' => 0, // <-- PENTING: Selalu 0 untuk TriPay
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Order Payload (from Webhook):', $payload);
            return $this->kirimaja->createExpressOrder($payload);
        }
    }
}

