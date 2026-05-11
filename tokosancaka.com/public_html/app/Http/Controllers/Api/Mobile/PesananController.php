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

                'sender_lat'            => 'nullable|numeric',
                'sender_lng'            => 'nullable|numeric',
                'receiver_lat'          => 'nullable|numeric',
                'receiver_lng'          => 'nullable|numeric',

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

                // ==========================================================
                // 🔥 LOGIKA PENCARIAN TITIK KOORDINAT (GEOCODING) 🔥
                // ==========================================================
                $serviceGroup = explode('-', $validatedData['expedition'] ?? '')[0] ?? '';
                if (in_array(strtolower($serviceGroup), ['instant', 'sameday'])) {
                    Log::info('[API MOBILE] Layanan Instant/Sameday terdeteksi. Mencari koordinat otomatis...');

                    // Cari Koordinat Pengirim
                    if (empty($validatedData['sender_lat']) || empty($validatedData['sender_lng'])) {
                        $senderQuery = $validatedData['sender_full_region'] ?? implode(', ', array_filter([$validatedData['sender_district'], $validatedData['sender_regency']]));
                        $geoSender = $this->geocode($senderQuery);
                        $validatedData['sender_lat'] = $geoSender['lat'] ?? '-7.250445'; // Default jika gagal
                        $validatedData['sender_lng'] = $geoSender['lng'] ?? '112.768845';
                    }

                    // Cari Koordinat Penerima
                    if (empty($validatedData['receiver_lat']) || empty($validatedData['receiver_lng'])) {
                        $receiverQuery = $validatedData['receiver_full_region'] ?? implode(', ', array_filter([$validatedData['receiver_district'], $validatedData['receiver_regency']]));
                        $geoReceiver = $this->geocode($receiverQuery);
                        $validatedData['receiver_lat'] = $geoReceiver['lat'] ?? '-7.250445';
                        $validatedData['receiver_lng'] = $geoReceiver['lng'] ?? '112.768845';
                    }
                }
                // ==========================================================

                // ==========================================================
                // 🔥 LOGIKA PENCARIAN TITIK KOORDINAT (GEOCODING) 🔥
                // ==========================================================

                // 1. Koordinat Pengirim (Origin)
                $origin_lat = $request->input('sender_lat');
                $origin_long = $request->input('sender_lng');

                if (empty($origin_lat) || empty($origin_long) || $origin_lat == 0) {
                    // Gabungkan Desa, Kecamatan, dan Kabupaten
                    $senderQuery = implode(', ', array_filter([$request->sender_village, $request->sender_district, $request->sender_regency]));
                    $geoSender = $this->geocode($senderQuery);
                    $origin_lat = $geoSender['lat'] ?? '-7.250445'; // Fallback aman jika gagal
                    $origin_long = $geoSender['lng'] ?? '112.768845';
                }

                // 2. Koordinat Penerima (Destination)
                $dest_lat = $request->input('receiver_lat');
                $dest_long = $request->input('receiver_lng');

                if (empty($dest_lat) || empty($dest_long) || $dest_lat == 0) {
                    $receiverQuery = implode(', ', array_filter([$request->receiver_village, $request->receiver_district, $request->receiver_regency]));
                    $geoReceiver = $this->geocode($receiverQuery);
                    $dest_lat = $geoReceiver['lat'] ?? '-7.250445';
                    $dest_long = $geoReceiver['lng'] ?? '112.768845';
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
            // C. PAYMENT GATEWAY (Diarahkan ke Web Browser)
            elseif (strtoupper($validatedData['payment_method']) === 'GATEWAY') {
                Log::info("[API MOBILE] Metode pembayaran menggunakan Web Payment Gateway...");

                // Set status order ke Menunggu Pembayaran
                $order->status = 'Menunggu Pembayaran';
                $order->status_pesanan = 'Menunggu Pembayaran';

                // Buatkan URL ke portal pembayaran Sancaka, bawa parameter akun (No WA)
                // Gunakan nomor WA user yang sedang login
                $paymentUrl = url('/pembayaran?akun=' . urlencode($user->no_wa));

                // Opsional: Simpan url ke tabel Pesanan jika kolom payment_url tersedia
                $order->payment_url = $paymentUrl;

                Log::info("[API MOBILE] URL Portal Pembayaran disiapkan: {$paymentUrl}");
            }
            // D. JIKA ADA METODE LAIN YANG TERLEWAT (Fallback)
            else {
                Log::info("[API MOBILE] Metode pembayaran lainnya...");
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
        // Format: serviceGroup - courier - service_type - ongkir - asuransi - feeOngkir - feeBarang
        $parts = explode('-', $validatedData['expedition']);

        $shipping_cost  = (int) ($parts[3] ?? 0);
        $ansuransi_fee  = (int) ($parts[4] ?? 0);
        $cod_fee_ongkir = (int) ($parts[5] ?? 0);
        $cod_fee_barang = (int) ($parts[6] ?? 0);

        // Deteksi penamaan metode dari React Native
        $pm = strtoupper(trim($validatedData['payment_method']));
        $isCodOngkir = in_array($pm, ['COD', '#COD_ONGKIR']);
        $isCodBarang = in_array($pm, ['CODBARANG', '#COD_BARANG']);

        $cod_fee = $isCodOngkir ? $cod_fee_ongkir : ($isCodBarang ? $cod_fee_barang : 0);

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = ($validatedData['ansuransi'] == 'iya');

        // Total untuk non-COD
        $total_paid_ongkir = $shipping_cost + ($use_insurance ? $ansuransi_fee : 0);

        // =========================================================
        // 🔥 PENJUMLAHAN AKHIR YANG KEMBAR DENGAN KIRIMINAJA 🔥
        // =========================================================
        $cod_value = 0;
        if ($isCodOngkir) {
            // WAJIB ditambah 1000 agar sinkron dengan KiriminAja
            $cod_value = 1000 + $shipping_cost + ($use_insurance ? $ansuransi_fee : 0) + $cod_fee_ongkir;

        } elseif ($isCodBarang) {
            $apiItemPrice = $item_price;
            if (!$use_insurance && $apiItemPrice < 1000) {
                $apiItemPrice = 1000;
            }
            $cod_value = $apiItemPrice + $shipping_cost + ($use_insurance ? $ansuransi_fee : 0) + $cod_fee_barang;
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
        $serviceGroup = strtolower($expeditionParts[0] ?? '');
        $courier = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;

        $apiItemPrice = (float) $data['item_price'];
        $finalInsuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$insurance_cost : 0;
        $finalCodValue = $cod_value;

        // Logika Penyelarasan Harga (Tetap Dipertahankan)
        $pm = strtoupper(trim($data['payment_method'] ?? ''));
        $isCodOngkir = in_array($pm, ['COD', '#COD_ONGKIR']);
        $isCodBarang = in_array($pm, ['CODBARANG', '#COD_BARANG']);

        if ($isCodOngkir || $isCodBarang) {
            if ($isCodOngkir) {
                $apiItemPrice = 1000;
            } else {
                if (($data['ansuransi'] ?? 'tidak') !== 'iya' && $apiItemPrice < 1000) {
                    $apiItemPrice = 1000;
                }
            }
            $order->price = $finalCodValue;
            $order->save();

            if (method_exists(\App\Http\Controllers\Customer\PesananController::class, 'simpanKeKeuangan')) {
                \App\Http\Controllers\Customer\PesananController::simpanKeKeuangan($order);
            }
        }

        // =========================================================
        // JIKA LAYANAN INSTANT / SAMEDAY (Memakai Titik Koordinat)
        // =========================================================
        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            $payload = [
                'service' => $courier, 'service_type' => $service_type, 'vehicle' => 'motor',
                'order_prefix' => $order->nomor_invoice,
                'packages' => [[
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                    'destination_lat' => $data['receiver_lat'] ?? '-7.250445', 'destination_long' => $data['receiver_lng'] ?? '112.768845',
                    'destination_address' => $data['receiver_address'], 'destination_address_note' => '-',
                    'origin_name' => $data['sender_name'], 'origin_phone' => $data['sender_phone'],
                    'origin_lat' => $data['sender_lat'] ?? '-7.250445', 'origin_long' => $data['sender_lng'] ?? '112.768845',
                    'origin_address' => $data['sender_address'], 'origin_address_note' => '-',
                    'shipping_price' => (int)$shipping_cost,
                    'item' => [
                        'name' => $data['item_description'], 'description' => 'Pesanan ' . $order->nomor_invoice,
                        'price' => (int)$apiItemPrice, 'weight' => (int)$data['weight'],
                    ]
                ]]
            ];
            Log::info('[API MOBILE] KiriminAja Payload Instant:', $payload);
            return $kirimaja->createInstantOrder($payload);
        }

        // =========================================================
        // JIKA LAYANAN REGULAR / EXPRESS / CARGO
        // =========================================================
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        if ($now->isSunday() || $now->hour >= 17) {
            $pickupDate = $now->copy()->addDay();
            if ($pickupDate->isSunday()) $pickupDate->addDay();
            $scheduleClock = $pickupDate->setTime(9, 0, 0)->format('Y-m-d H:i:s');
        } else {
            $scheduleClock = $now->setTime(17, 0, 0)->format('Y-m-d H:i:s');
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
            // Opsional: Sertakan koordinat pengirim untuk regular jika ada
            'latitude' => $data['sender_lat'] ?? null, 'longitude' => $data['sender_lng'] ?? null,
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

        Log::info('[API MOBILE] KiriminAja Payload Express:', $payload);
        return $kirimaja->createExpressOrder($payload);
    }

    private function _sendWhatsappNotification($order, $validatedData, $shipping_cost, $ansuransi_fee, $cod_fee, $total_invoice)
    {
        try {
            $displaySenderPhone = $validatedData['sender_phone_original'] ?? $order->sender_phone;
            $displayReceiverPhone = $validatedData['receiver_phone_original'] ?? $order->receiver_phone;

            $pmClean = strtoupper(trim($validatedData['payment_method']));
            $isCodOngkir = in_array($pmClean, ['COD', '#COD_ONGKIR']);
            $isCodBarang = in_array($pmClean, ['CODBARANG', '#COD_BARANG']);

            // Ambil Harga Barang Asli
            $realItemPrice = $validatedData['item_price'] ?? $order->item_price ?? 0;

            if ($isCodOngkir) {
                // === RUMUS COD ONGKIR PRESISI ===
                $basisBarang = ($realItemPrice > 1000000) ? 10000 : $realItemPrice;
                $basisHitung = $shipping_cost + $basisBarang;
                $feeHitung   = $basisHitung * 0.03;
                $feeCodMurni = max(2500, $feeHitung); // Minimal 2.500

                // PPN 11% dari Fee
                $ppnFee = $feeCodMurni * 0.11;

                // Pembulatan Kelipatan 500 ke Atas (Total Final)
                $grandTotalMentah = $shipping_cost + $ansuransi_fee + $feeCodMurni + $ppnFee;
                $finalTotal = (int) (ceil($grandTotalMentah / 500) * 500);

                // Fee Layanan Tampilan di WA (Sisa dari Total)
                $finalCodFee = $finalTotal - $shipping_cost - $ansuransi_fee;

            } elseif ($isCodBarang) {
                $finalCodFee = $cod_fee;
                $finalTotal  = $order->price ?? $total_invoice;
            } else {
                $finalCodFee = $cod_fee;
                $finalTotal  = $order->price ?? $total_invoice;
            }

            // Susun Detail Paket
            $detailPaket = "*Detail Paket:*\n";
            $detailPaket .= "Deskripsi: " . ($validatedData['item_description'] ?? '-') . "\n";
            $detailPaket .= "Berat: " . ($validatedData['weight'] ?? 0) . " Gram\n";

            $expeditionParts = explode('-', $validatedData['expedition'] ?? '');
            $service_display = trim(strtoupper($expeditionParts[1] ?? '') . ' ' . strtoupper($expeditionParts[2] ?? ''));

            $detailPaket .= "Ekspedisi: " . ($service_display ?: '-') . "\n";
            $detailPaket .= "Layanan: " . ucwords($validatedData['service_type'] ?? '-');
            $detailPaket .= "\nResi: *" . ($order->resi ?? 'Menunggu Resi') . "*";

            // Susun Rincian Biaya
            $rincianBiaya = "*Rincian Biaya:*\n";
            if ($realItemPrice > 0) {
                $rincianBiaya .= "- Nilai Barang: Rp " . number_format($realItemPrice, 0, ',', '.');
                $rincianBiaya .= $isCodOngkir ? " (Tidak Masuk Tagihan COD)\n" : "\n";
            }
            $rincianBiaya .= "- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.') . "\n";

            if ($ansuransi_fee > 0) {
                $rincianBiaya .= "- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.') . "\n";
            }
            if ($finalCodFee > 0) {
                $rincianBiaya .= "- Biaya Layanan: Rp " . number_format($finalCodFee, 0, ',', '.') . "\n";
            }

            // Tentukan Status Pembayaran
            $statusBayar = "⏳ Menunggu Pembayaran";
            if ($isCodOngkir || $isCodBarang) {
                $statusBayar = "⏳ Bayar di Tempat (COD)";
            } elseif (in_array($pmClean, ['POTONG SALDO', 'CASH', '#SALDO'])) {
                $statusBayar = "✅ Lunas";
            }

            // Template WA Akhir
            $messageTemplate = <<<TEXT
*Terimakasih Ya Kak Atas Orderannya 🙏*

Berikut adalah Nomor Order ID / Nomor Invoice Kakak:
*{NOMOR_INVOICE}*

📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )
➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )

----------------------------------------
{DETAIL_PAKET}
----------------------------------------
{RINCIAN_BIAYA}
----------------------------------------
*Total Tagihan: Rp {TOTAL_BAYAR}*
Status Pembayaran: {STATUS_BAYAR}
----------------------------------------

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek status pesanan/resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={LINK_RESI}

*Manajemen Sancaka*
TEXT;

            $message = str_replace(
                [
                    '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                    '{DETAIL_PAKET}', '{RINCIAN_BIAYA}', '{TOTAL_BAYAR}', '{STATUS_BAYAR}', '{LINK_RESI}'
                ],
                [
                    $order->nomor_invoice, $validatedData['sender_name'], $displaySenderPhone,
                    $validatedData['receiver_name'], $displayReceiverPhone,
                    $detailPaket, $rincianBiaya, number_format($finalTotal, 0, ',', '.'),
                    $statusBayar, ($order->resi ?? $order->nomor_invoice)
                ],
                $messageTemplate
            );

            $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($validatedData['sender_phone']));
            $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($validatedData['receiver_phone']));

            // Kirim menggunakan Fonnte
            if ($senderWa) $this->_sendFonnte($senderWa, $message);
            if ($receiverWa) $this->_sendFonnte($receiverWa, $message);

        } catch (\Exception $e) {
            Log::error('[API MOBILE] WA Notification failed: ' . $e->getMessage(), ['invoice' => $order->nomor_invoice]);
        }
    }

    private function _sendFonnte($target, $message)
    {
        // Menggunakan token Fonnte dari file .env
        $token = env('FONNTE_TOKEN');

        if (\Illuminate\Support\Str::startsWith($target, '0')) {
            $target = '62' . substr($target, 1);
        }

        try {
            // Fonnte menggunakan Header Authorization untuk tokennya
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'delay' => '1' // Delay pengiriman pesan (opsional)
            ]);

            Log::info("[API MOBILE] Fonnte Sent to $target", ['response' => $response->json()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error("[API MOBILE] Fonnte Failed to $target: " . $e->getMessage());
            return ['status' => false, 'error' => $e->getMessage()];
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

        // Kumpulkan semua metode Non-Payment Gateway agar mudah di-filter
        $nonPgMethods = ['COD', '#COD_ONGKIR', 'CODBARANG', '#COD_BARANG', 'Potong Saldo', 'Cash', 'CASH', '#SALDO'];

        $stats = [
            'countSelesai' => (clone $baseQuery)->whereIn('status_pesanan', ['Selesai', 'Terkirim'])->count(),
            'countPickup'  => (clone $baseQuery)->where('status_pesanan', 'Menunggu Pickup')->count(),
            'countDikirim' => (clone $baseQuery)->where('status_pesanan', 'Diproses')->count(),

            // Perbaikan query countGagal agar lebih aman untuk id_pengguna
            'countGagal'   => (clone $baseQuery)->where(function($q) {
                                  $q->whereIn('status_pesanan', ['Batal', 'Kadaluarsa', 'Gagal Bayar', 'Dibatalkan'])
                                    ->orWhere('status_pesanan', 'LIKE', '%Gagal Auto-Resi%');
                              })->count(),

            // ==========================================================
            // 🔥 TAMBAHAN STATS METODE PEMBAYARAN UNTUK REACT NATIVE 🔥
            // ==========================================================
            'countCodOngkir' => (clone $baseQuery)->whereIn('payment_method', ['COD', '#COD_ONGKIR'])->count(),
            'countCodBarang' => (clone $baseQuery)->whereIn('payment_method', ['CODBARANG', '#COD_BARANG'])->count(),
            'countCash'      => (clone $baseQuery)->whereIn('payment_method', ['Potong Saldo', 'Cash', 'CASH', '#SALDO'])->count(),
            'countPg'        => (clone $baseQuery)->whereNotIn('payment_method', $nonPgMethods)->count(),
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
        \Illuminate\Support\Facades\Log::info('[API MOBILE] Memulai proses cancelOrder. Data Request:', $request->all());

        try {
            // 1. Validasi Input
            $validatedData = $request->validate([
                'awb'    => 'required|string|max:30',
                'reason' => 'required|string|min:5|max:200',
            ]);
            \Illuminate\Support\Facades\Log::info('[API MOBILE] Validasi cancelOrder berhasil dilewati.', $validatedData);

            // 2. Cari Pesanan
            $order = Pesanan::where('resi', $validatedData['awb'])->first();

            if (!$order) {
                \Illuminate\Support\Facades\Log::warning("[API MOBILE] CancelOrder gagal: Pesanan dengan resi {$validatedData['awb']} tidak ditemukan.");
                return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
            }

            // 3. KEAMANAN: Pastikan pesanan ini milik user yang sedang login!
            $user = auth('sanctum')->user();
            if ($user && $order->id_pengguna_pembeli != $user->id_pengguna && strtolower($user->role) !== 'admin') {
                \Illuminate\Support\Facades\Log::warning("[API MOBILE] CancelOrder DITOLAK: User ID {$user->id_pengguna} mencoba membatalkan pesanan ID {$order->id_pengguna_pembeli}.");
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membatalkan pesanan ini.'], 403);
            }

            \Illuminate\Support\Facades\Log::info("[API MOBILE] Pesanan ditemukan. Melanjutkan pembatalan untuk ID/Invoice: {$order->nomor_invoice}");

            // =========================================================================
            // 🔥 LOGIKA BYPASS UNTUK RESI MOCK (TESTING) 🔥
            // =========================================================================
            if (\Illuminate\Support\Str::startsWith($validatedData['awb'], 'MOCK')) {
                \Illuminate\Support\Facades\Log::info("[API MOBILE] Resi MOCK terdeteksi. Membatalkan pesanan secara lokal tanpa hit API KiriminAja.");

                $order->status = 'Dibatalkan';
                $order->status_pesanan = 'Dibatalkan';
                $order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan (Testing) berhasil dibatalkan secara lokal.'
                ], 200);
            }
            // =========================================================================


            $apiKey = env('KIRIMINAJA_API_KEY'); // Pastikan API Key di .env sudah benar

            // 4. LOGIKA URL DINAMIS (Berdasarkan mode di .env)
            $isProduction = env('KIRIMINAJA_MODE', 'sandbox') === 'production';
            $domain = $isProduction ? 'https://client.kiriminaja.com' : 'https://tdev.kiriminaja.com';
            $endpoint = $domain . '/api/mitra/v3/cancel_shipment';

            \Illuminate\Support\Facades\Log::info('[API MOBILE] Mengirim payload pembatalan ke API KiriminAja...', [
                'endpoint' => $endpoint,
                'awb' => $validatedData['awb'],
                'reason' => $validatedData['reason']
            ]);

            // 5. Tembak API Ekspedisi
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ])->post($endpoint, [
                'awb'    => $validatedData['awb'],
                'reason' => $validatedData['reason']
            ]);

            $jsonResponse = $response->json();
            \Illuminate\Support\Facades\Log::info('[API MOBILE] Response dari KiriminAja (cancel_shipment):', is_array($jsonResponse) ? $jsonResponse : ['response' => $jsonResponse]);

            // 6. Evaluasi Respons
            if (isset($jsonResponse['status']) && $jsonResponse['status'] === true) {
                $order->status = 'Dibatalkan';
                $order->status_pesanan = 'Dibatalkan';
                $order->save();

                \Illuminate\Support\Facades\Log::info("[API MOBILE] Status pesanan {$order->nomor_invoice} berhasil diupdate menjadi 'Dibatalkan' di database.");
                return response()->json(['success' => true, 'message' => $jsonResponse['text'] ?? 'Pesanan berhasil dibatalkan.'], 200);
            }

            \Illuminate\Support\Facades\Log::error('[API MOBILE] Gagal membatalkan di sistem ekspedisi KiriminAja.', $jsonResponse ?? []);
            return response()->json(['success' => false, 'message' => $jsonResponse['text'] ?? 'Gagal di sistem ekspedisi.'], 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('[API MOBILE] Validasi gagal saat cancelOrder:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Input tidak valid. Pastikan alasan pembatalan minimal 5 karakter.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[API MOBILE] Exception pada cancelOrder: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fungsi Helper untuk mengubah teks alamat menjadi Koordinat
     */
    private function geocode(string $address): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders(['User-Agent' => 'SancakaCargoMobile/1.0 (api@tokosancaka.com)'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $address, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'id'
                ]);

            if (!$response->successful() || empty($response[0]) || !isset($response[0]['lat']) || !isset($response[0]['lon'])) {
                Log::warning("[API MOBILE] Geocoding gagal untuk alamat: " . $address);
                return null;
            }
            return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
        } catch (\Exception $e) {
            Log::error("[API MOBILE] Geocoding error: " . $e->getMessage());
            return null;
        }
    }

}
