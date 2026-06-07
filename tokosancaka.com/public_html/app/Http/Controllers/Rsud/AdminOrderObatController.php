<?php

namespace App\Http\Controllers\Rsud;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RsudOrderObat;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminOrderObatController extends Controller
{
    public function index()
    {
        $orders = RsudOrderObat::orderBy('created_at', 'desc')->get();
        return view('admin.rsud.index', compact('orders'));
    }

    /**
     * Tandai obat sudah selesai diracik oleh apoteker.
     * Setelah ini, tombol "Panggil Kurir" baru muncul.
     */
    public function updateStatusRacik(Request $request)
    {
        $request->validate([
            'kode_booking' => 'required|exists:rsud_order_obat,kode_booking',
        ]);

        try {
            $order = RsudOrderObat::where('kode_booking', $request->kode_booking)->firstOrFail();
            $order->status_racik = 'Selesai Diramu';
            $order->save();

            return response()->json(['success' => true, 'message' => 'Status obat diubah ke Selesai Diramu!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PAYLOAD KE KIRIMIN AJA — hanya boleh jika:
     * 1. Payment status = Lunas (atau COD/cash)
     * 2. Status racik = Selesai Diramu
     * 3. Belum punya resi
     */
    public function adminPayloadKiriminAja(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate([
            'kode_booking' => 'required|exists:rsud_order_obat,kode_booking',
        ]);

        $order = RsudOrderObat::where('kode_booking', $request->kode_booking)->firstOrFail();

        // === GUARD: Cek status pembayaran ===
        $sudahLunas = in_array($order->payment_status, ['Lunas', 'Lunas / COD'])
                   || in_array(strtoupper($order->payment_method), ['COD', 'CODBARANG', 'CASH', 'POTONG SALDO']);

        if (!$sudahLunas) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Pasien belum melunasi pembayaran (Status: ' . $order->payment_status . ').',
            ]);
        }

        // === GUARD: Obat harus sudah selesai diramu ===
        if ($order->status_racik !== 'Selesai Diramu') {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: Obat belum selesai diramu (Status: ' . $order->status_racik . ').',
            ]);
        }

        // === GUARD: Jangan payload 2 kali ===
        if (!empty($order->resi)) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini sudah memiliki resi: ' . $order->resi,
            ]);
        }

        try {
            // Parse expedition string: serviceType-courier-serviceCode-cost-insuranceFee-codFee
            $expeditionParts = explode('-', $order->expedition ?? '');
            $serviceGroup    = $expeditionParts[0] ?? 'regular';
            $courier         = $expeditionParts[1] ?? 'pos';
            $service_type    = $expeditionParts[2] ?? 'reguler';

            // Ambil koordinat dari request (jika admin mau override) atau dari DB
            $senderLat  = $request->input('sender_lat',  $order->sender_lat  ?? '-7.4422788');
            $senderLng  = $request->input('sender_lng',  $order->sender_lng  ?? '111.3869462');
            $receiverLat = $request->input('receiver_lat', $order->receiver_lat ?? null);
            $receiverLng = $request->input('receiver_lng', $order->receiver_lng ?? null);

            if (in_array($serviceGroup, ['instant', 'sameday'])) {
                // ---- INSTANT / SAMEDAY ----
                if (empty($receiverLat) || empty($receiverLng)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Koordinat penerima tidak tersedia untuk layanan Instant/Sameday.',
                    ]);
                }

                $payload  = [
                    'service'      => $courier,
                    'service_type' => $service_type,
                    'vehicle'      => 'motor',
                    'order_prefix' => $order->kode_booking,
                    'packages'     => [[
                        'destination_name'     => $order->receiver_name,
                        'destination_phone'    => $order->receiver_phone,
                        'destination_address'  => $order->receiver_address,
                        'destination_lat'      => (float) $receiverLat,
                        'destination_long'     => (float) $receiverLng,
                        'origin_name'          => $order->sender_name,
                        'origin_phone'         => $order->sender_phone,
                        'origin_address'       => $order->sender_address,
                        'origin_lat'           => (float) $senderLat,
                        'origin_long'          => (float) $senderLng,
                        'shipping_price'       => (int) $order->shipping_cost,
                        'item' => [
                            'name'        => $order->item_description,
                            'description' => 'Obat RSUD ' . $order->kode_booking,
                            'price'       => (int) $order->item_price,
                            'weight'      => (int) $order->weight,
                        ],
                    ]],
                ];
                $response = $kirimaja->createInstantOrder($payload);

            } else {
                // ---- REGULAR / EXPRESS / CARGO ----
                $scheduleResponse = $kirimaja->getSchedules();
                $scheduleClock    = $scheduleResponse['clock'] ?? now()->format('Y-m-d H:i:s');

                $category = ($order->service_type === 'cargo') ? 'trucking' : 'regular';

                $weightInput    = (int) ($order->weight  ?? 1000);
                $lengthInput    = (int) ($order->length  ?? 10);
                $widthInput     = (int) ($order->width   ?? 10);
                $heightInput    = (int) ($order->height  ?? 10);

                // Cek apakah COD
                $codValue = 0;
                if (in_array(strtoupper($order->payment_method), ['COD', 'CODBARANG'])) {
                    $codValue = (int) $order->total_price;
                }

                $payload  = [
                    'address'        => $order->sender_address,
                    'phone'          => $order->sender_phone,
                    'name'           => $order->sender_name,
                    'kecamatan_id'   => (int) $order->sender_district_id,
                    'kelurahan_id'   => (int) $order->sender_subdistrict_id,
                    'zipcode'        => $order->sender_postal_code ?? '63218',
                    'schedule'       => $scheduleClock,
                    'platform_name'  => 'tokosancaka.com',
                    'category'       => $category,
                    'latitude'       => $senderLat  ? (float) $senderLat  : null,
                    'longitude'      => $senderLng  ? (float) $senderLng  : null,
                    'packages'       => [[
                        'order_id'                  => $order->kode_booking,
                        'item_name'                 => $order->item_description,
                        'package_type_id'           => (int) ($order->item_type ?? 9),
                        'destination_name'          => $order->receiver_name,
                        'destination_phone'         => $order->receiver_phone,
                        'destination_address'       => $order->receiver_address,
                        'destination_kecamatan_id'  => (int) $order->receiver_district_id,
                        'destination_kelurahan_id'  => (int) $order->receiver_subdistrict_id,
                        'destination_zipcode'       => $order->receiver_postal_code ?? '00000',
                        'weight'                    => $weightInput,
                        'width'                     => $widthInput,
                        'height'                    => $heightInput,
                        'length'                    => $lengthInput,
                        'item_value'                => (int) $order->item_price,
                        'insurance_amount'          => (int) $order->insurance_cost,
                        'cod'                       => $codValue,
                        'service'                   => $courier,
                        'service_type'              => $service_type,
                        'shipping_cost'             => (int) $order->shipping_cost,
                    ]],
                ];
                $response = $kirimaja->createExpressOrder($payload);
            }

            Log::info('RSUD Payload KiriminAja:', [
                'kode_booking' => $order->kode_booking,
                'response'     => $response,
            ]);

            if (($response['status'] ?? false) === true) {
                $resi              = $response['result']['awb_no']
                                  ?? ($response['results'][0]['awb'] ?? null);
                $order->resi       = $resi;
                $order->status_racik = 'Diserahkan ke Kurir';
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil payload ke Ekspedisi! Resi: ' . $resi,
                    'resi'    => $resi,
                ]);
            }

            $errorText = $response['text']
                      ?? ($response['errors'][0]['text'] ?? 'Gagal membuat order ekspedisi.');

            return response()->json(['success' => false, 'message' => 'API Error: ' . $errorText]);

        } catch (Exception $e) {
            Log::error('RSUD adminPayloadKiriminAja Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ], 500);
        }
    }

   public static function processCallback(string $merchantRef, string $status, array $callbackData): void
{
    $order = \App\Models\RsudOrderObat::where('kode_booking', $merchantRef)->first();
 
    if (!$order) {
        \Illuminate\Support\Facades\Log::warning(
            'RSUD processCallback: kode_booking tidak ditemukan.',
            ['ref' => $merchantRef]
        );
        return;
    }
 
    // Idempoten — jangan proses 2 kali
    if ($order->payment_status === 'Lunas') {
        \Illuminate\Support\Facades\Log::info(
            'RSUD processCallback: sudah Lunas, skip.',
            ['ref' => $merchantRef]
        );
        return;
    }
 
    if ($status === 'PAID') {
        // Set Lunas — JANGAN payload KiriminAja di sini
        // KiriminAja dipanggil MANUAL oleh admin via adminPayloadKiriminAja()
        $order->payment_status = 'Lunas';
        $order->save();
 
        \Illuminate\Support\Facades\Log::info(
            "RSUD Pembayaran Lunas via Gateway: {$merchantRef}. Menunggu admin racik obat."
        );
 
        // Notifikasi admin: pembayaran masuk, obat siap diracik
        try {
            $admins = \App\Models\User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                \Illuminate\Support\Facades\Notification::send(
                    $admins,
                    new \App\Notifications\NotifikasiUmum([
                        'tipe'        => 'Pembayaran',
                        'judul'       => '💊 Booking Obat RSUD Lunas!',
                        'pesan_utama' => $merchantRef . ' — ' . $order->receiver_name . ' sudah membayar. Siap diracik.',
                        'url'         => route('admin.rsud.index'),
                        'icon'        => 'fas fa-pills',
                    ])
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('RSUD notif admin callback gagal: ' . $e->getMessage());
        }
 
    } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
        $order->payment_status = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
        $order->save();
 
        \Illuminate\Support\Facades\Log::info("RSUD Pembayaran {$status}: {$merchantRef}.");
    }
}
}
