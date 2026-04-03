<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

// --- MODEL ---
use App\Models\Pesanan;
use App\Models\User;

// --- SERVICES ---
use App\Services\KiriminAjaService;

class PesananController extends Controller
{
    /**
     * =========================================================================
     * FUNGSI STORE SINGLE (API MOBILE)
     * Menerima request JSON dari React Native dan memproses pesanan
     * =========================================================================
     */
    public function storeSingle(Request $request, KiriminAjaService $kirimaja)
    {
        Log::info('[API MOBILE] Menerima request pembuatan pesanan baru:', $request->all());

        DB::beginTransaction();
        try {
            // 1. VALIDASI INPUT DARI REACT NATIVE (Diperbarui untuk menerima full_region dan expedition_name)
            $validatedData = $request->validate([
                'sender_name'           => 'required|string|max:100',
                'sender_phone'          => 'required|string|max:20',
                'sender_address'        => 'required|string|min:10|max:500',
                // Kolom-kolom wilayah lama (kita buat nullable karena tergantikan oleh full_region)
                'sender_province'       => 'nullable|string|max:100',
                'sender_regency'        => 'nullable|string|max:100',
                'sender_district'       => 'nullable|string|max:100',
                'sender_postal_code'    => 'nullable|string|max:10',
                'sender_district_id'    => 'required|numeric',
                'sender_subdistrict_id' => 'required|numeric',
                'sender_full_region'    => 'nullable|string|max:255', // <-- TAMBAHAN BARU

                'receiver_name'         => 'required|string|max:100',
                'receiver_phone'        => 'required|string|max:20',
                'receiver_address'      => 'required|string|min:10|max:500',
                'receiver_province'     => 'nullable|string|max:100',
                'receiver_regency'      => 'nullable|string|max:100',
                'receiver_district'     => 'nullable|string|max:100',
                'receiver_postal_code'  => 'nullable|string|max:10',
                'receiver_district_id'  => 'required|numeric',
                'receiver_subdistrict_id'=> 'required|numeric',
                'receiver_full_region'  => 'nullable|string|max:255', // <-- TAMBAHAN BARU

                'item_description'      => 'required|string|max:255',
                'item_price'            => 'required|numeric|min:100',
                'weight'                => 'required|numeric|min:1',
                'length'                => 'nullable|numeric|min:1',
                'width'                 => 'nullable|numeric|min:1',
                'height'                => 'nullable|numeric|min:1',
                'ansuransi'             => 'required|string|in:iya,tidak',
                'item_type'             => 'required|integer',

                'service_type'          => 'required|string|in:regular,express,sameday,instant,cargo',
                'expedition'            => 'required|string|max:255', // Kode asli kurir
                'expedition_name'       => 'nullable|string|max:255', // <-- TAMBAHAN BARU (Nama Manusiawi)
                'payment_method'        => 'required|string|max:50',

                'save_sender'           => 'nullable',
                'save_receiver'         => 'nullable',
            ]);

            $user = Auth::user();

            // 2. SANITASI NOMOR HP
            $validatedData['sender_phone_original'] = $validatedData['sender_phone'];
            $validatedData['receiver_phone_original'] = $validatedData['receiver_phone'];
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone_original']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone_original']);

            // 3. KALKULASI BIAYA BERDASARKAN STRING EKSPEDISI
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir'];
            $cod_value         = $calculation['cod_value'];
            $shipping_cost     = $calculation['shipping_cost'];
            $insurance_cost    = $calculation['ansuransi_fee'];
            $cod_fee           = $calculation['cod_fee'];

            // 4. SIAPKAN DATA DATABASE
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;
            $pesananData['id_pengguna_pembeli'] = $user->id_pengguna;
            $pesananData['customer_id'] = $user->id_pengguna;

            // Pastikan data string tidak null
            $pesananData['receiver_district'] = $pesananData['receiver_district'] ?? 'Tidak Diketahui';
            $pesananData['receiver_regency'] = $pesananData['receiver_regency'] ?? 'Tidak Diketahui';

            // 5. BUAT RECORD PESANAN
            $order = Pesanan::create($pesananData);

            // 6. PROSES METODE PEMBAYARAN (POTONG SALDO DARI MOBILE)
            if ($validatedData['payment_method'] === 'Potong Saldo' || $validatedData['payment_method'] === 'Saldo') {
                if ($user->saldo < $total_paid_ongkir) {
                    throw new Exception('Saldo Anda tidak mencukupi. Sisa saldo: Rp ' . number_format($user->saldo, 0, ',', '.'));
                }
                $user->decrement('saldo', $total_paid_ongkir);
                Log::info("[API MOBILE] Saldo user {$user->id_pengguna} dipotong sebesar {$total_paid_ongkir}");
            }

            // 7. TEMBAK API KIRIMINAJA
            $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $order, $kirimaja, $cod_value, $shipping_cost, $insurance_cost);

            if (($kiriminResponse['status'] ?? false) !== true) {
                $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi KiriminAja.');
                throw new Exception($errorMessage);
            }

            // 8. SIMPAN RESI DAN SHIPPING REF
            $bookingId = $kiriminResponse['id'] ?? $kiriminResponse['data']['id'] ?? $kiriminResponse['payment_ref'] ?? null;
            $awbAsli = $kiriminResponse['awb'] ?? $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);

            $order->shipping_ref = $bookingId;
            $order->status = 'Pesanan Dibuat';
            $order->status_pesanan = 'Pesanan Dibuat';
            $order->resi = !empty($awbAsli) ? $awbAsli : ($bookingId ?? 'REF-'.$order->nomor_invoice);
            $order->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $order->save();

            DB::commit();

            // 9. REKAM KEUANGAN (Memanggil fungsi dari Web Controller Bapak)
            try {
                // Pastikan class PesananController di Customer web ada dan fungsinya static
                if (method_exists(\App\Http\Controllers\Customer\PesananController::class, 'simpanKeKeuangan')) {
                    \App\Http\Controllers\Customer\PesananController::simpanKeKeuangan($order);
                }
            } catch (Exception $e) {
                Log::error("[API MOBILE] Gagal rekam keuangan: " . $e->getMessage());
            }

            // 10. KIRIM NOTIFIKASI WA KE ADMIN
            $this->_sendWhatsappNotification($order, $validatedData, $shipping_cost, (int) $order->insurance_cost, (int) $order->cod_fee, $order->price);

            // 11. KEMBALIKAN RESPON SUKSES KE REACT NATIVE
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat dengan Resi: ' . $order->resi,
                'data' => [
                    'invoice' => $order->nomor_invoice,
                    'resi' => $order->resi,
                    'total_potongan' => $total_paid_ongkir
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Input tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[API MOBILE] Order Creation Failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * =========================================================================
     * HELPER FUNCTIONS
     * =========================================================================
     */

    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            if (Str::startsWith(substr($phone, 2), '0')) {
                return '0' . substr($phone, 3);
            }
            return '0' . substr($phone, 2);
        }
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) {
            return '0' . $phone;
        }
        return $phone;
    }

    private function _calculateTotalPaid(array $validatedData): array
    {
        $parts = explode('-', $validatedData['expedition']);
        $count = count($parts);
        $cod_fee = 0; $ansuransi_fee = 0; $shipping_cost = 0;

        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('[API MOBILE] Format expedition tidak dikenal', ['exp' => $validatedData['expedition']]); }

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = $validatedData['ansuransi'] == 'iya';

        $total_paid_ongkir = $shipping_cost;
        if ($use_insurance) {
            $total_paid_ongkir += $ansuransi_fee;
        }

        $cod_value = 0;
        if ($validatedData['payment_method'] === 'CODBARANG') {
            $cod_value = $item_price + $shipping_cost + $cod_fee;
            if ($use_insurance) $cod_value += $ansuransi_fee;
        } elseif ($validatedData['payment_method'] === 'COD') {
            $total_paid = $shipping_cost + $cod_fee;
            if ($use_insurance) $total_paid += $ansuransi_fee;
            $cod_value = $total_paid;
        }

        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }

    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        $fieldsToSave = array_keys($validatedData);
        // Jangan simpan field tambahan ini ke database jika tidak ada kolomnya
        $fieldsToExclude = ['sender_phone_original', 'receiver_phone_original', 'sender_full_region', 'receiver_full_region', 'expedition_name'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();

        return array_merge($pesananCoreData, [
            'nomor_invoice' => $nomorInvoice,
            'status' => 'Menunggu Pembayaran',
            'status_pesanan' => 'Menunggu Pembayaran',
            'tanggal_pesanan' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'total_harga_barang' => $validatedData['item_price'],
        ]);
    }

    private function _createKiriminAjaOrder(array $data, Pesanan $order, KiriminAjaService $kirimaja, int $cod_value, int $shipping_cost, int $insurance_cost): array
    {
        $expeditionParts = explode('-', $data['expedition'] ?? '');
        $serviceGroup = $expeditionParts[0] ?? null;
        $courier = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;

        $apiItemPrice = (float) $data['item_price'];
        $finalInsuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$insurance_cost : 0;
        $finalCodValue = $cod_value;

        // Jadwal Logika Sama dengan Web
        $dayOfWeek = (int)date('N');
        $currentHour = (int)date('H');

        if ($dayOfWeek == 7 || $currentHour >= 15) {
            $scheduleClock = date('Y-m-d 09:00:00', strtotime('+1 day'));
        } else {
            $scheduleClock = date('Y-m-d H:i:s');
        }

        $category = ($data['service_type'] ?? $serviceGroup) === 'cargo' ? 'trucking' : 'regular';

        $weightInput = (int) $data['weight'];
        $lengthInput = (int) ($data['length'] ?? 1);
        $widthInput = (int) ($data['width'] ?? 1);
        $heightInput = (int) ($data['height'] ?? 1);

        $volumetricWeight = 0;
        if ($lengthInput > 0 && $widthInput > 0 && $heightInput > 0) {
            $volumetricWeight = ($widthInput * $lengthInput * $heightInput) / ($category === 'trucking' ? 4000 : 6000) * 1000;
        }
        $finalWeight = max($weightInput, $volumetricWeight);

        $payload = [
            'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
            'kecamatan_id' => $data['sender_district_id'], 'kelurahan_id' => $data['sender_subdistrict_id'],
            'zipcode' => $data['sender_postal_code'] ?? '00000', 'schedule' => $scheduleClock,
            'platform_name' => 'tokosancaka.com', 'category' => $category,
            'packages' => [[
                'order_id' => $order->nomor_invoice, 'item_name' => $data['item_description'],
                'package_type_id' => (int)$data['item_type'],
                'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                'destination_address' => $data['receiver_address'],
                'destination_kecamatan_id' => $data['receiver_district_id'], 'destination_kelurahan_id' => $data['receiver_subdistrict_id'],
                'destination_zipcode' => $data['receiver_postal_code'] ?? '00000',
                'weight' => (int) ceil($finalWeight),
                'width' => $widthInput, 'height' => $heightInput, 'length' => $lengthInput,
                'item_value' => (int)$apiItemPrice,
                'insurance_amount' => (int)$finalInsuranceAmount,
                'cod' => (int)$finalCodValue,
                'schedule' => $scheduleClock,
                'service' => $courier, 'service_type' => $service_type,
                'shipping_cost' => (int)$shipping_cost
            ]]
        ];

        Log::info('[API MOBILE] KiriminAja Payload:', $payload);
        return $kirimaja->createExpressOrder($payload);
    }

    private function _sendWhatsappNotification($order, $data, $shipping_cost, $insurance_cost, $cod_fee, $total_invoice)
    {
        try {
            $adminNumbers = ['085745808809', '08819435180'];
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };

            // PERBAIKAN: Gunakan label dari API jika ada, atau fallback ke data lama
            $lokasiTujuan = $data['receiver_full_region'] ?? ($data['receiver_district'] . ', ' . $data['receiver_regency']);
            $namaEkspedisi = $data['expedition_name'] ?? $data['expedition'];

            $message = "*🔔 PESANAN BARU MASUK (DARI APLIKASI MOBILE)*\n";
            $message .= "----------------------------------\n";
            $message .= "🆔 Invoice: *{$order->nomor_invoice}*\n";
            $message .= "📦 Resi: *{$order->resi}*\n";
            $message .= "👤 Customer: {$data['sender_name']} ({$data['sender_phone']})\n";
            $message .= "📍 Tujuan: {$lokasiTujuan}\n\n";

            $message .= "📦 *Item:* {$data['item_description']}\n";
            $message .= "🚛 *Ekspedisi:* {$namaEkspedisi}\n\n";

            $message .= "💰 *RINCIAN KEUANGAN*\n";
            $message .= "Metode: *{$data['payment_method']}*\n";
            $message .= "☑️ Ongkir: Rp " . $fmt($shipping_cost) . "\n";
            $message .= "----------------------------------\n";
            $message .= "*TOTAL TAGIHAN: Rp " . $fmt($total_invoice) . "*\n";
            $message .= "----------------------------------\n";

            foreach ($adminNumbers as $number) {
                $waTarget = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $number));
                if (class_exists(\App\Services\FonnteService::class)) {
                    \App\Services\FonnteService::sendMessage($waTarget, $message);
                } else {
                     Log::warning("[API MOBILE] FonnteService class not found!");
                }
            }
        } catch (\Exception $e) {
            Log::error('[API MOBILE] WA Notification Error: ' . $e->getMessage());
        }
    }
}
