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
use App\Services\DokuJokulService;

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
        Log::info('[API MOBILE] Menerima request pembuatan pesanan baru (storeSingle). Data Request:', $request->all());

        DB::beginTransaction();
        try {
            Log::info('[API MOBILE] Memulai proses validasi input dari React Native...');

            // 1. VALIDASI INPUT DARI REACT NATIVE
            $validatedData = $request->validate([
                'sender_name'           => 'required|string|max:100',
                'sender_phone'          => 'required|string|max:20',
                'sender_address'        => 'required|string|min:10|max:500',
                'sender_province'       => 'nullable|string|max:100',
                'sender_regency'        => 'nullable|string|max:100',
                'sender_district'       => 'nullable|string|max:100',
                'sender_postal_code'    => 'nullable|string|max:10',
                'sender_district_id'    => 'required|numeric',
                'sender_subdistrict_id' => 'required|numeric',
                'sender_full_region'    => 'nullable|string|max:255',

                'receiver_name'         => 'required|string|max:100',
                'receiver_phone'        => 'required|string|max:20',
                'receiver_address'      => 'required|string|min:10|max:500',
                'receiver_province'     => 'nullable|string|max:100',
                'receiver_regency'      => 'nullable|string|max:100',
                'receiver_district'     => 'nullable|string|max:100',
                'receiver_postal_code'  => 'nullable|string|max:10',
                'receiver_district_id'  => 'required|numeric',
                'receiver_subdistrict_id'=> 'required|numeric',
                'receiver_full_region'  => 'nullable|string|max:255',

                'item_description'      => 'required|string|max:255',
                'item_price'            => 'required|numeric|min:100',
                'weight'                => 'required|numeric|min:1',
                'length'                => 'nullable|numeric|min:1',
                'width'                 => 'nullable|numeric|min:1',
                'height'                => 'nullable|numeric|min:1',
                'ansuransi'             => 'required|string|in:iya,tidak',
                'item_type'             => 'required|integer',

                'service_type'          => 'required|string|in:regular,express,sameday,instant,cargo',
                'expedition'            => 'required|string|max:255',
                'expedition_name'       => 'nullable|string|max:255',
                'payment_method'        => 'required|string|max:50',

                'save_sender'           => 'nullable',
                'save_receiver'         => 'nullable',
                'idempotency_key'       => 'nullable|string',
                'cart_items'            => 'nullable|array',
            ]);

            Log::info('[API MOBILE] Validasi input berhasil dilewati.');

            // Cek pencegahan klik double
            if (!empty($validatedData['idempotency_key'])) {
                Log::info("[API MOBILE] Mengecek idempotency_key: {$validatedData['idempotency_key']}");
                if (Pesanan::where('idempotency_key', $validatedData['idempotency_key'])->exists()) {
                    Log::warning("[API MOBILE] Idempotency_key sudah ada. Terdeteksi percobaan dobel input.");
                    throw new Exception('Pesanan ini sudah berhasil dibuat sebelumnya (Dobel Input).');
                }
            }

            $user = Auth::user();
            Log::info("[API MOBILE] User yang melakukan request: ID {$user->id_pengguna}");

            // --- TAMBAHAN KHUSUS: VALIDASI PEMBAYARAN CASH ---
            if (strtoupper($validatedData['payment_method']) === 'CASH') {
                Log::info("[API MOBILE] User memilih metode pembayaran CASH. Memeriksa otorisasi...");
                $isAdmin = (isset($user->role) && strtolower($user->role) === 'admin');
                if (!$isAdmin && $user->id_pengguna != 4) {
                    Log::warning("[API MOBILE] Otorisasi CASH ditolak untuk User ID {$user->id_pengguna} (Bukan Admin / Bukan ID 4).");
                    throw new Exception('Metode pembayaran CASH hanya tersedia khusus untuk Admin dan User ID 4.');
                }
                Log::info("[API MOBILE] Otorisasi pembayaran CASH disetujui.");
            }
            // -------------------------------------------------

            // 2. SANITASI NOMOR HP
            Log::info('[API MOBILE] Memulai proses sanitasi nomor HP...');
            $validatedData['sender_phone_original'] = $validatedData['sender_phone'];
            $validatedData['receiver_phone_original'] = $validatedData['receiver_phone'];
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone_original']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone_original']);
            Log::info('[API MOBILE] Sanitasi nomor HP selesai.');

            // 3. KALKULASI BIAYA BERDASARKAN STRING EKSPEDISI
            Log::info('[API MOBILE] Memulai kalkulasi biaya ekspedisi dan total...');
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir'];
            $cod_value         = $calculation['cod_value'];
            $shipping_cost     = $calculation['shipping_cost'];
            $insurance_cost    = $calculation['ansuransi_fee'];
            $cod_fee           = $calculation['cod_fee'];
            Log::info('[API MOBILE] Hasil kalkulasi:', $calculation);

            // 4. SIAPKAN DATA DATABASE
            Log::info('[API MOBILE] Menyiapkan array data untuk disimpan ke tabel Pesanan...');
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;
            $pesananData['id_pengguna_pembeli'] = $user->id_pengguna;
            $pesananData['customer_id'] = $user->id_pengguna;
            $pesananData['idempotency_key'] = $validatedData['idempotency_key'] ?? null;

            // Pastikan data string tidak null
            $pesananData['receiver_district'] = $pesananData['receiver_district'] ?? 'Tidak Diketahui';
            $pesananData['receiver_regency'] = $pesananData['receiver_regency'] ?? 'Tidak Diketahui';

            // 5. BUAT RECORD PESANAN
            Log::info("[API MOBILE] Mengeksekusi create() ke tabel Pesanan dengan Invoice: {$pesananData['nomor_invoice']}");
            $order = Pesanan::create($pesananData);
            Log::info("[API MOBILE] Berhasil menyimpan data Pesanan dengan ID: {$order->id}");
            $paymentUrl = null;

            // --- SIMPAN ITEM PRODUK ---
            if (!empty($validatedData['cart_items']) && is_array($validatedData['cart_items'])) {
                Log::info("[API MOBILE] Ditemukan " . count($validatedData['cart_items']) . " item di keranjang. Menyimpan ke OrderItem...");
                foreach ($validatedData['cart_items'] as $item) {
                    \App\Models\OrderItem::create([
                        'order_id' => $order->id, // Ganti ke 'pesanan_id' jika foreign key Anda pesanan_id
                        'product_id' => $item['product_id'] ?? null,
                        'product_variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['qty'] ?? 1,
                        'price' => $item['price'] ?? 0,
                    ]);
                }
                Log::info("[API MOBILE] Berhasil menyimpan semua item pesanan.");
            } else {
                Log::info("[API MOBILE] Tidak ada data cart_items yang dikirim.");
            }

            // 6. PROSES LOGIKA BERDASARKAN METODE PEMBAYARAN
            // ==============================================================
            Log::info("[API MOBILE] Memproses metode pembayaran: {$validatedData['payment_method']}");

            // A. PEMBAYARAN LUNAS (POTONG SALDO / CASH) -> Langsung Booking Resi
            if (in_array($validatedData['payment_method'], ['#SALDO', 'Potong Saldo', 'Saldo', 'CASH'])) {

                // Jika pakai saldo (bukan CASH), cek dan potong saldo user
                if ($validatedData['payment_method'] !== 'CASH') {
                    Log::info("[API MOBILE] Cek kecukupan saldo user...");
                    $totalTagihanFinal = $validatedData['item_price'] + $total_paid_ongkir;
                    if ($user->saldo < $totalTagihanFinal) {
                        Log::warning("[API MOBILE] Saldo tidak cukup. Saldo: {$user->saldo}, Tagihan: {$totalTagihanFinal}");
                        throw new Exception('Saldo Anda tidak mencukupi. Tagihan: Rp ' . number_format($totalTagihanFinal, 0, ',', '.'));
                    }
                    $user->decrement('saldo', $totalTagihanFinal);
                    Log::info("[API MOBILE] Saldo user {$user->id_pengguna} berhasil dipotong sebesar {$totalTagihanFinal}");
                }

                // Tembak API KiriminAja
                Log::info("[API MOBILE] Membuat order lunas ke KiriminAja...");
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $order, $kirimaja, $cod_value, $shipping_cost, $insurance_cost);
                Log::info("[API MOBILE] Response KiriminAja (Lunas):", is_array($kiriminResponse) ? $kiriminResponse : ['response' => $kiriminResponse]);

                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::error("[API MOBILE] KiriminAja gagal memproses order lunas.", $kiriminResponse);
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi KiriminAja.'));
                }

                $bookingId = $kiriminResponse['id'] ?? $kiriminResponse['data']['id'] ?? $kiriminResponse['payment_ref'] ?? null;
                $awbAsli = $kiriminResponse['awb'] ?? $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);

                $order->shipping_ref = $bookingId;
                $order->status = 'Pesanan Dibuat';
                $order->status_pesanan = 'Pesanan Dibuat';
                $order->resi = !empty($awbAsli) ? $awbAsli : ($bookingId ?? 'REF-'.$order->nomor_invoice);
                Log::info("[API MOBILE] Resi berhasil di-generate: {$order->resi}");

                // Rekam Keuangan
                try {
                    Log::info("[API MOBILE] Mencoba merekam transaksi ke log keuangan...");
                    if (method_exists(\App\Http\Controllers\Customer\PesananController::class, 'simpanKeKeuangan')) {
                        \App\Http\Controllers\Customer\PesananController::simpanKeKeuangan($order);
                        Log::info("[API MOBILE] Berhasil merekam transaksi keuangan.");
                    }
                } catch (Exception $e) {
                    Log::error("[API MOBILE] Gagal rekam keuangan: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                }
            }
            // B. PEMBAYARAN COD -> Langsung Booking Resi (Bayar Nanti)
            elseif (in_array($validatedData['payment_method'], ['#COD_ONGKIR', '#COD_BARANG', 'COD', 'CODBARANG'])) {

                Log::info("[API MOBILE] Membuat order COD ke KiriminAja...");
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $order, $kirimaja, $cod_value, $shipping_cost, $insurance_cost);
                Log::info("[API MOBILE] Response KiriminAja (COD):", is_array($kiriminResponse) ? $kiriminResponse : ['response' => $kiriminResponse]);

                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::error("[API MOBILE] KiriminAja gagal memproses order COD.", $kiriminResponse);
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order COD di KiriminAja.'));
                }

                $bookingId = $kiriminResponse['id'] ?? $kiriminResponse['data']['id'] ?? $kiriminResponse['payment_ref'] ?? null;
                $awbAsli = $kiriminResponse['awb'] ?? $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);

                $order->shipping_ref = $bookingId;
                $order->status = 'Pesanan Dibuat';
                $order->status_pesanan = 'Pesanan Dibuat';
                $order->resi = !empty($awbAsli) ? $awbAsli : ($bookingId ?? 'REF-'.$order->nomor_invoice);
                Log::info("[API MOBILE] Resi COD berhasil di-generate: {$order->resi}");
            }
            // C. PAYMENT GATEWAY (DOKU dll) -> Tunda Resi (Menunggu Pembayaran)
            else {
                Log::info("[API MOBILE] Metode pembayaran menggunakan Payment Gateway...");
                if (in_array($validatedData['payment_method'], ['#DOKU', 'DOKU_JOKUL'])) {
                    try {
                        Log::info("[API MOBILE] Meminta link pembayaran ke DOKU Jokul...");
                        $dokuService = new DokuJokulService();
                        $tagihanDoku = $validatedData['item_price'] + $total_paid_ongkir; // Harga Barang + Ongkir
                        $paymentUrl = $dokuService->createPayment($order->nomor_invoice, $tagihanDoku);

                        if (empty($paymentUrl)) {
                            throw new Exception('Gagal membuat transaksi DOKU. Response link kosong.');
                        }

                        $order->payment_url = $paymentUrl;
                        Log::info("[API MOBILE] Link DOKU berhasil dibuat: {$paymentUrl}");
                    } catch (Exception $e) {
                        Log::error('[API MOBILE] DOKU Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                        throw new Exception('Gagal menghubungi DOKU Payment Gateway.');
                    }
                }
                $order->status = 'Menunggu Pembayaran';
                $order->status_pesanan = 'Menunggu Pembayaran';
            }

            // ==============================================================
            // 7. Simpan Harga Final (Harga Barang + Ongkir)
            Log::info("[API MOBILE] Menyimpan harga final dan meng-update pesanan ke database...");
            $order->price = ($cod_value > 0) ? $cod_value : ($validatedData['item_price'] + $total_paid_ongkir);
            $order->save();

            Log::info("[API MOBILE] Melakukan DB Commit...");
            DB::commit();

            // 8. KIRIM NOTIFIKASI WA KE ADMIN
            Log::info("[API MOBILE] Memulai proses pengiriman notifikasi WhatsApp...");
            $this->_sendWhatsappNotification($order, $validatedData, $shipping_cost, (int) $order->insurance_cost, (int) $order->cod_fee, $order->price);

            // 9. KEMBALIKAN RESPON SUKSES KE REACT NATIVE
            Log::info("[API MOBILE] Proses selesai dengan sukses. Mengirim response 200 ke aplikasi Mobile.");
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'data' => [
                    'invoice' => $order->nomor_invoice,
                    'resi' => $order->resi ?? 'Menunggu Pembayaran',
                    'total_potongan' => $total_paid_ongkir,
                    'payment_url' => $paymentUrl // TSX akan menangkap ini dan membuka WebBrowser
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('[API MOBILE] Order Creation Failed karena Validasi: ', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Input tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[API MOBILE] Order Creation Failed (Exception Catch): ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
        else { Log::warning('[API MOBILE] _calculateTotalPaid: Format expedition tidak dikenal', ['exp' => $validatedData['expedition']]); }

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = $validatedData['ansuransi'] == 'iya';

        $total_paid_ongkir = $shipping_cost;
        if ($use_insurance) {
            $total_paid_ongkir += $ansuransi_fee;
        }

        $cod_value = 0;
        if (in_array($validatedData['payment_method'], ['#COD_BARANG', 'CODBARANG'])) {
            $cod_value = $item_price + $shipping_cost + $cod_fee;
            if ($use_insurance) $cod_value += $ansuransi_fee;
        } elseif (in_array($validatedData['payment_method'], ['#COD_ONGKIR', 'COD'])) {
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
        Log::info('[API MOBILE] Menyiapkan payload KiriminAja...');
        $expeditionParts = explode('-', $data['expedition'] ?? '');
        $serviceGroup = $expeditionParts[0] ?? null;
        $courier = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;

        $apiItemPrice = (float) $data['item_price'];
        $finalInsuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$insurance_cost : 0;
        $finalCodValue = $cod_value;

        // ---------------------------------------------------------
        // LOGIKA PENJADWALAN PICKUP KIRIMINAJA BARU
        // ---------------------------------------------------------
        $now = \Carbon\Carbon::now('Asia/Jakarta');

        // Jika hari ini adalah hari Minggu ATAU sudah lewat jam 17:00 (5 Sore)
        if ($now->isSunday() || $now->hour >= 17) {
            $pickupDate = $now->copy()->addDay(); // Jadwalkan ke Besok
            if ($pickupDate->isSunday()) {
                $pickupDate->addDay();
            }
            $scheduleClock = $pickupDate->setTime(9, 0, 0)->format('Y-m-d H:i:s');
        } else {
            $scheduleClock = $now->setTime(17, 0, 0)->format('Y-m-d H:i:s');
        }
        Log::info("[API MOBILE] Jadwal pickup KiriminAja ditetapkan pada: {$scheduleClock}");

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

        Log::info('[API MOBILE] KiriminAja Payload akhir:', $payload);
        return $kirimaja->createExpressOrder($payload);
    }

    private function _sendWhatsappNotification($order, $data, $shipping_cost, $insurance_cost, $cod_fee, $total_invoice)
    {
        try {
            $adminNumbers = ['085745808809', '08819435180'];
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };

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

            Log::info("[API MOBILE] Mengirim WA Notif ke list admin...");
            foreach ($adminNumbers as $number) {
                $waTarget = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $number));
                if (class_exists(\App\Services\FonnteService::class)) {
                    \App\Services\FonnteService::sendMessage($waTarget, $message);
                    Log::info("[API MOBILE] Pesan WA terkirim ke: {$waTarget}");
                } else {
                     Log::warning("[API MOBILE] FonnteService class not found! Gagal kirim WA ke {$waTarget}");
                }
            }
        } catch (\Exception $e) {
            Log::error('[API MOBILE] WA Notification Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

   /**
     * Mengambil daftar riwayat pesanan & statistik untuk Mobile
     */
    public function riwayat(Request $request)
    {
        Log::info('[API MOBILE] Menerima request riwayat pesanan.');

        $user = auth('sanctum')->user();
        if (!$user) {
            Log::warning('[API MOBILE] Akses riwayat ditolak: User tidak terotentikasi (Unauthorized).');
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        Log::info("[API MOBILE] User request riwayat: ID {$user->id_pengguna}, Role: {$user->role}");

        // ==========================================
        // LOGIKA BARU: CEK ADMIN (Bisa Lihat Semua)
        // ==========================================
        $isAdmin = ($user->id_pengguna == 4 && strtolower($user->role) === 'admin');

        if ($isAdmin) {
            Log::info("[API MOBILE] Otorisasi riwayat: ADMIN. Akan menampilkan semua pesanan.");
        } else {
            Log::info("[API MOBILE] Otorisasi riwayat: USER BIASA. Hanya menampilkan pesanan sendiri.");
        }

        // Jika bukan admin, kunci query hanya untuk user tersebut
        $query = Pesanan::with(['items.product', 'items.productVariant', 'store']);
        $baseQuery = Pesanan::query();

        if (!$isAdmin) {
            $query->where('id_pengguna_pembeli', $user->id_pengguna);
            $baseQuery->where('id_pengguna_pembeli', $user->id_pengguna);
        }

        // 1. Logika Pencarian
        if ($request->filled('search')) {
            Log::info("[API MOBILE] Menerapkan pencarian: {$request->search}");
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_invoice', 'LIKE', "%{$search}%")
                  ->orWhere('resi', 'LIKE', "%{$search}%")
                  ->orWhere('receiver_name', 'LIKE', "%{$search}%")
                  ->orWhere('receiver_phone', 'LIKE', "%{$search}%");
            });
        }

        // 2. Logika Filter Status
        if ($request->filled('status') && $request->status !== 'Semua') {
            Log::info("[API MOBILE] Menerapkan filter status: {$request->status}");
            if ($request->status === 'Gagal Resi') {
                $query->where('status_pesanan', 'LIKE', '%Gagal Auto-Resi%');
            } else {
                $query->where('status_pesanan', $request->status);
            }
        }

        // 3. Logika Statistik (Admin liat stat semua, User liat stat sendiri)
        Log::info("[API MOBILE] Mulai menghitung statistik pesanan...");
        $stats = [
            'countSelesai' => (clone $baseQuery)->whereIn('status_pesanan', ['Selesai', 'Terkirim'])->count(),
            'countPickup'  => (clone $baseQuery)->where('status_pesanan', 'Menunggu Pickup')->count(),
            'countDikirim' => (clone $baseQuery)->where('status_pesanan', 'Diproses')->count(),
            'countGagal'   => (clone $baseQuery)->whereIn('status_pesanan', ['Batal', 'Kadaluarsa', 'Gagal Bayar', 'Dibatalkan'])
                                                ->orWhere('status_pesanan', 'LIKE', '%Gagal Auto-Resi%')
                                                ->count(),
        ];
        Log::info("[API MOBILE] Statistik selesai dihitung:", $stats);

        // 4. Ambil Data dengan Paginasi
        Log::info("[API MOBILE] Menjalankan query utama dengan paginasi...");
        $orders = $query->latest()->paginate(10);

        Log::info("[API MOBILE] Pengambilan riwayat selesai. Total item: " . $orders->total() . " pada halaman " . $orders->currentPage());

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'stats' => $stats,
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage()
        ]);
    }

    public function cancelOrder(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'awb'    => 'required|string|max:30',
                'reason' => 'required|string|min:5|max:200',
            ]);

            $order = Pesanan::where('resi', $validatedData['awb'])->first();
            if (!$order) return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);

            $apiKey = env('KIRIMINAJA_API_KEY'); // Sesuaikan API Key Anda
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ])->post('https://api.kiriminaja.com/api/mitra/v2/cancel_shipment', [
                'awb'    => $validatedData['awb'],
                'reason' => $validatedData['reason']
            ]);

            $jsonResponse = $response->json();

            if (isset($jsonResponse['status']) && $jsonResponse['status'] === true) {
                $order->status = 'Dibatalkan';
                $order->status_pesanan = 'Dibatalkan';
                $order->save();

                return response()->json(['success' => true, 'message' => $jsonResponse['text'] ?? 'Berhasil.'], 200);
            }

            return response()->json(['success' => false, 'message' => $jsonResponse['text'] ?? 'Gagal di sistem ekspedisi.'], 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
