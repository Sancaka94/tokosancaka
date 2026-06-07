<?php

namespace App\Http\Controllers\Rsud;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RsudOrderObat;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminOrderObatController extends Controller
{
    public function index()
    {
        $orders = RsudOrderObat::orderBy('created_at', 'desc')->get();
        return view('admin.rsud.index', compact('orders'));
    }

    public function updateStatusRacik(Request $request)
    {
        $request->validate([
            'kode_booking' => 'required|exists:rsud_order_obat,kode_booking'
        ]);

        try {
            $order = RsudOrderObat::where('kode_booking', $request->kode_booking)->firstOrFail();
            $order->status_racik = 'Selesai Diramu';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Status obat berhasil diubah menjadi Selesai Diramu!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function adminPayloadKiriminAja(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate([
            'kode_booking' => 'required|exists:rsud_order_obat,kode_booking'
        ]);

        $order = RsudOrderObat::where('kode_booking', $request->kode_booking)->firstOrFail();

        if ($order->payment_status !== 'Lunas' && !in_array($order->payment_method, ['COD', 'CODBARANG', 'cash', 'Potong Saldo'])) {
            return response()->json(['success' => false, 'message' => 'Gagal payload. Pasien belum melunasi pembayaran.']);
        }

        if ($order->status_racik !== 'Selesai Diramu') {
            return response()->json(['success' => false, 'message' => 'Obat belum selesai diramu!']);
        }

        if ($order->resi) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini sudah dikirim ke Ekspedisi.']);
        }

        try {
            $expeditionParts = explode('-', $order->expedition);
            $serviceGroup = $expeditionParts[0] ?? null;
            $courier = $expeditionParts[1] ?? 'pos';
            $service_type = $expeditionParts[2] ?? 'reguler';

            if (in_array($serviceGroup, ['instant', 'sameday'])) {
                $payload = [
                    'service' => $courier,
                    'service_type' => $service_type,
                    'vehicle' => 'motor',
                    'order_prefix' => $order->kode_booking,
                    'packages' => [[
                        'destination_name' => $order->receiver_name,
                        'destination_phone' => $order->receiver_phone,
                        'destination_address' => $order->receiver_address,
                        'destination_lat' => $request->input('receiver_lat', '-7.399564'),
                        'destination_long' => $request->input('receiver_lng', '111.459312'),
                        'origin_name' => $order->sender_name,
                        'origin_phone' => $order->sender_phone,
                        'origin_address' => $order->sender_address,
                        'origin_lat' => $request->input('sender_lat', '-7.402511'),
                        'origin_long' => $request->input('sender_long', '111.444212'),
                        'shipping_price' => $order->shipping_cost,
                        'item' => [
                            'name' => $order->item_description,
                            'description' => 'Obat RSUD ' . $order->kode_booking,
                            'price' => $order->item_price,
                            'weight' => $order->weight,
                        ]
                    ]]
                ];
                $response = $kirimaja->createInstantOrder($payload);
            } else {
                $payload = [
                    'address' => $order->sender_address,
                    'phone' => $order->sender_phone,
                    'name' => $order->sender_name,
                    'kecamatan_id' => $order->sender_district_id,
                    'kelurahan_id' => $order->sender_subdistrict_id,
                    'zipcode' => '63218',
                    'platform_name' => 'tokosancaka.com',
                    'category' => 'regular',
                    'packages' => [[
                        'order_id' => $order->kode_booking,
                        'item_name' => $order->item_description,
                        'package_type_id' => 9,
                        'destination_name' => $order->receiver_name,
                        'destination_phone' => $order->receiver_phone,
                        'destination_address' => $order->receiver_address,
                        'destination_kecamatan_id' => $order->receiver_district_id,
                        'destination_kelurahan_id' => $order->receiver_subdistrict_id,
                        'destination_zipcode' => '00000',
                        'weight' => $order->weight,
                        'width' => 10,
                        'height' => 10,
                        'length' => 10,
                        'item_value' => $order->item_price,
                        'insurance_amount' => $order->insurance_cost,
                        'cod' => in_array($order->payment_method, ['COD', 'CODBARANG']) ? $order->total_price : 0,
                        'service' => $courier,
                        'service_type' => $service_type,
                        'shipping_cost' => $order->shipping_cost
                    ]]
                ];
                $response = $kirimaja->createExpressOrder($payload);
            }

            if (($response['status'] ?? false) === true) {
                $order->resi = $response['result']['awb_no'] ?? ($response['results'][0]['awb'] ?? null);
                $order->status_racik = 'Diserahkan ke Kurir';
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil payload ke Ekspedisi!',
                    'resi' => $order->resi
                ]);
            } else {
                Log::error('Payload KiriminAja Error: ', $response);
                return response()->json([
                    'success' => false,
                    'message' => 'API Error: ' . ($response['text'] ?? 'Gagal membuat order ekspedisi.')
                ]);
            }

        } catch (Exception $e) {
            Log::error('Exception Payload KiriminAja: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}