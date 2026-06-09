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

  public function adminPayloadKiriminAja(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate([
            'kode_booking' => 'required|exists:rsud_order_obat,kode_booking',
        ]);

        $order = RsudOrderObat::where('kode_booking', $request->kode_booking)->firstOrFail();

        $sudahLunas = in_array($order->payment_status, ['Lunas', 'Lunas / COD'])
                      || in_array(strtoupper($order->payment_method), ['COD', 'CODBARANG', 'CASH', 'POTONG SALDO']);

        if (!$sudahLunas) {
            return response()->json(['success' => false, 'message' => 'Gagal: Pembayaran belum lunas.']);
        }

        if ($order->status_racik !== 'Selesai Diramu') {
            return response()->json(['success' => false, 'message' => 'Gagal: Obat belum diracik.']);
        }

        if (!empty($order->resi)) {
            return response()->json(['success' => false, 'message' => 'Pesanan sudah memiliki resi.']);
        }

        try {
            $apiBookingCode = str_replace('RSUD-', 'SCK-', $order->kode_booking);

            $expeditionParts = explode('-', $order->expedition ?? '');
            $serviceGroup    = $expeditionParts[0] ?? 'regular';
            $courier         = $expeditionParts[1] ?? 'pos';
            $service_type    = $expeditionParts[2] ?? 'reguler';

            $now = \Carbon\Carbon::now('Asia/Jakarta');
            $scheduleTime = ($now->hour >= 16)
                ? $now->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s')
                : $now->addHour()->format('Y-m-d H:i:s');

            if (in_array($serviceGroup, ['instant', 'sameday'])) {
                $payload = [
                    'service' => $courier,
                    'service_type' => $service_type,
                    'vehicle' => 'motor',
                    'order_prefix' => $apiBookingCode,
                    'schedule' => $scheduleTime,
                    'packages' => [[
                        'destination_name' => $order->receiver_name,
                        'destination_phone' => $order->receiver_phone,
                        'destination_address' => $order->receiver_address,
                        'destination_lat' => (float) ($request->input('receiver_lat') ?? $order->receiver_lat ?? -7.3275223),
                        'destination_long' => (float) ($request->input('receiver_lng') ?? $order->receiver_lng ?? 112.6867899),
                        'origin_name' => $order->sender_name,
                        'origin_phone' => $order->sender_phone,
                        'origin_address' => $order->sender_address,
                        'origin_lat' => (float) ($request->input('sender_lat') ?? $order->sender_lat ?? -7.4422788),
                        'origin_long' => (float) ($request->input('sender_long') ?? $order->sender_lng ?? 111.3869462),
                        'shipping_price' => (int) $order->shipping_cost,
                        'item' => [
                            'name' => $order->item_description,
                            'description' => 'Obat RSUD ' . $apiBookingCode,
                            'price' => (int) $order->item_price,
                            'weight' => (int) $order->weight,
                        ]
                    ]]
                ];
                $response = $kirimaja->createInstantOrder($payload);
            } else {
                $scheduleResponse = $kirimaja->getSchedules();
                $scheduleClock = $scheduleResponse['clock'] ?? $scheduleTime;

                $category = ($order->service_type === 'cargo') ? 'trucking' : 'regular';

                $payload = [
                    'address' => $order->sender_address,
                    'phone' => $order->sender_phone,
                    'name' => $order->sender_name,
                    'kecamatan_id' => (int) $order->sender_district_id,
                    'kelurahan_id' => (int) $order->sender_subdistrict_id,
                    'zipcode' => $order->sender_postal_code ?? '63218',
                    'schedule' => $scheduleClock,
                    'platform_name' => 'tokosancaka.com',
                    'category' => $category,
                    'latitude' => (float) ($order->sender_lat ?? -7.4422788),
                    'longitude' => (float) ($order->sender_lng ?? 111.3869462),
                    'packages' => [[
                        'order_id' => $apiBookingCode,
                        'item_name' => $order->item_description,
                        'package_type_id' => (int) ($order->item_type ?? 9),
                        'destination_name' => $order->receiver_name,
                        'destination_phone' => $order->receiver_phone,
                        'destination_address' => $order->receiver_address,
                        'destination_kecamatan_id' => (int) $order->receiver_district_id,
                        'destination_kelurahan_id' => (int) $order->receiver_subdistrict_id,
                        'destination_zipcode' => $order->receiver_postal_code ?? '60222',
                        'weight' => (int) $order->weight,
                        'width' => (int) ($order->width ?? 10),
                        'height' => (int) ($order->height ?? 10),
                        'length' => (int) ($order->length ?? 10),
                        'item_value' => (int) $order->item_price,
                        'insurance_amount' => (int) $order->insurance_cost,
                        'cod' => in_array(strtoupper($order->payment_method), ['COD', 'CODBARANG']) ? (int) $order->total_price : 0,
                        'service' => $courier,
                        'service_type' => $service_type,
                        'shipping_cost' => (int) $order->shipping_cost
                    ]]
                ];
                $response = $kirimaja->createExpressOrder($payload);
            }

           if (($response['status'] ?? false) === true) {
                // TANGKAP PICKUP NUMBER SEBAGAI REFERENSI AWAL
                $pickupNumber = $response['pickup_number'] ?? 'Sedang diproses';

                // Cek jika API kebetulan sudah ngasih AWB (jarang tapi mungkin)
                $resi = $response['result']['awb_no'] ?? ($response['results'][0]['awb'] ?? null);

                // Update database
                $order->resi = $resi;
                $order->status_racik = 'Diserahkan ke Kurir';
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => $resi ? 'Berhasil! Resi: ' . $resi : 'Permintaan Pickup Dibuat (ID: ' . $pickupNumber . '). Resi akan muncul otomatis saat kurir datang.',
                    'resi' => $resi ?? 'Menunggu Resi (Pickup: ' . $pickupNumber . ')'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'API Error: ' . ($response['text'] ?? 'Gagal membuat order.')
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'System Error: ' . $e->getMessage()], 500);
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
