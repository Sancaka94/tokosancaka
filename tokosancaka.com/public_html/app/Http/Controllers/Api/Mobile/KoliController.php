<?php
// LOG LOG: Controller Mobile API - JANGAN DIHAPUS
namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService; // <--- TAMBAHKAN INI
use App\Models\Api; // <--- TAMBAHKAN INI UNTUK TRIPAY
use Exception;

class KoliController extends Controller
{
    // ==========================================
    // HELPER: GENERATE INVOICE & NORMALISASI
    // ==========================================
    protected function generateInvoiceNumber()
    {
        return 'SCK-' . now()->format('ymd') . '-' . Str::upper(Str::random(3));
    }

    private function _normalizePaymentMethod(Request $request)
    {
        $pm = $request->payment_method;
        if ($pm === '#SALDO') $pm = 'Potong Saldo';
        elseif ($pm === 'COD_ONGKIR') $pm = 'COD';
        elseif ($pm === 'COD_BARANG') $pm = 'CODBARANG';

        // Catatan: #DOKU atau DOKU_JOKUL sudah tidak digunakan lagi dari Mobile
        // karena semuanya akan dikirim sebagai 'GATEWAY'

        $request->merge(['payment_method' => $pm]);
    }

    private function _sanitizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) return Str::startsWith(substr($phone, 2), '0') ? '0'.substr($phone, 3) : '0'.substr($phone, 2);
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) return '0'.$phone;
        return $phone;
    }

    // ==========================================
    // HELPER: GEOCODING & ADDRESS
    // ==========================================
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'SancakaMobileAPI/1.0 (support@tokosancaka.com)'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $address, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'id'
                ]);

            if (!$response->successful() || empty($response[0]) || !isset($response[0]['lat']) || !isset($response[0]['lon'])) {
                return null;
            }

            return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function _getAddressData(Request $request, string $type): array
    {
        $lat = $request->input("{$type}_lat");
        $lng = $request->input("{$type}_lng");

        $kirimajaAddr = [
            'district_id'    => $request->input("{$type}_district_id"),
            'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
            'postal_code'    => $request->input("{$type}_postal_code") ?? '00000',
        ];

        if (!is_numeric($lat) || !is_numeric($lng) || $lat == 0 || $lng == 0) {
            $parts = array_filter([
                $request->input("{$type}_village"), $request->input("{$type}_district"), $request->input("{$type}_regency")
            ]);
            $simpleAddressQuery = implode(', ', $parts);

            if(!empty($simpleAddressQuery)) {
                $geo = $this->geocode($simpleAddressQuery);
                if ($geo) { $lat = $geo['lat']; $lng = $geo['lng']; }
            }
        }

        return [
            'lat' => (is_numeric($lat) && $lat != 0) ? (float) $lat : null,
            'lng' => (is_numeric($lng) && $lng != 0) ? (float) $lng : null,
            'kirimaja_data' => $kirimajaAddr
        ];
    }

    private function _saveKontak($request, $prefix, $tipe)
    {
        if ($request->has("save_{$prefix}") && $request->input("save_{$prefix}") === 'on') {
            Kontak::updateOrCreate(
                ['no_hp' => $this->_sanitizePhone($request->input("{$prefix}_phone"))],
                [
                    'nama' => $request->input("{$prefix}_name"),
                    'alamat' => $request->input("{$prefix}_address"),
                    'district_id' => $request->input("{$prefix}_district_id"),
                    'subdistrict_id' => $request->input("{$prefix}_subdistrict_id"),
                    'tipe' => $tipe,
                    'province' => $request->input("{$prefix}_province"),
                    'regency' => $request->input("{$prefix}_regency"),
                    'district' => $request->input("{$prefix}_district"),
                    'village' => $request->input("{$prefix}_village"),
                    'postal_code' => $request->input("{$prefix}_postal_code"),
                ]
            );
        }
    }

    // ==========================================
    // API: CEK ONGKIR (MURNI PROXY KE KIRIMINAJA)
    // ==========================================
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $request->validate([
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'weight' => 'required|numeric', 'volume' => 'required|numeric',
            ]);

            // Bersihkan format harga
            $itemPrice = (int) str_replace(['Rp', '.', ',', ' '], '', $request->item_price);
            $useInsurance = ($request->ansuransi == 'iya' || $request->ansuransi == 1 || $request->ansuransi == 'true') ? 1 : 0;

            $cat = $request->input('service_type', 'regular');

            // 1. LANGSUNG TEMBAK API KIRIMINAJA
            $options = $kirimaja->getExpressPricing(
                $request->sender_district_id, $request->sender_subdistrict_id,
                $request->receiver_district_id, $request->receiver_subdistrict_id,
                (int) $request->weight, (int) $request->volume, 1, 1,
                $itemPrice, null, $cat, $useInsurance
            );

            // 2. LANGSUNG LEMPAR RESPONSE ASLI DARI KA KE MOBILE
            if (isset($options['status']) && $options['status'] === true && !empty($options['results'])) {
                 return response()->json(['success' => true, 'data' => $options]);
            }

            return response()->json(['success' => false, 'message' => 'Layanan tidak tersedia untuk rute ini.'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // API: STORE SINGLE (Pesanan Satuan / Per-Div Mobile)
    // ==========================================
    public function storeSingle(Request $request, KiriminAjaService $kirimaja)
    {
        Log::info('[API MOBILE SINGLE] Menerima request pembuatan Single Koli:', $request->all());
        $this->_normalizePaymentMethod($request);

        // Group ID yang didapat dari React Native (Date.now())
        $groupId = $request->input('transaction_group_id');

        // Deteksi apakah kurir yang dipilih adalah Instant atau Sameday
        $isInstant = (stripos($request->courier_code, 'instant') !== false || stripos($request->service_code, 'sameday') !== false);

        // Jika kurir Instant, pastikan lat & lng tidak kosong atau 0
        if ($isInstant) {
            if (empty($request->sender_lat) || $request->sender_lat == 0 ||
                empty($request->receiver_lat) || $request->receiver_lat == 0) {

                return response()->json([
                    'success' => false,
                    'message' => 'PENGIRIMAN GAGAL: Untuk kurir Instant/Sameday, Anda wajib menekan tombol "Ambil Lokasi Otomatis" untuk menangkap koordinat GPS.'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $rawItemPrice = str_replace(['Rp', '.', ' ', ','], '', $request->input('item_price'));
            $request->merge(['item_price' => $rawItemPrice]);

            $request->validate([
                'sender_name' => 'required', 'sender_phone' => 'required',
                'receiver_name' => 'required', 'receiver_phone' => 'required|min:9|max:13',
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'payment_method' => 'required', 'item_price' => 'required|numeric|min:1000',
                'weight' => 'required|numeric|min:1',
                'courier_code' => 'required', 'service_code' => 'required',
            ]);

            $this->_saveKontak($request, 'sender', 'Pengirim');
            $this->_saveKontak($request, 'receiver', 'Penerima');

            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            // 1. Generate Nomor Invoice Unik
            do {
                $nomorInvoice = 'SCK-' . date('ymd') . '-'. strtoupper(Str::random(5));
            } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

            $beratFisik = (int) $request->weight;
            $p = (int) ($request->length ?? 10);
            $l = (int) ($request->width ?? 10);
            $t = (int) ($request->height ?? 10);

            $targetCourier = $request->courier_code;
            $targetService = $request->service_code;

            // 2. Hitung Berat Volumetrik
            $divisor = (strpos(strtolower($targetService), 'cargo') !== false || strpos(strtolower($targetService), 'trucking') !== false) ? 4000 : 6000;
            $volumeWeight = ($p * $l * $t) / $divisor * 1000;
            $finalBookingWeight = (int) ceil(max($beratFisik, $volumeWeight));

            // 3. Tembak API Cek Ongkir di belakang layar (Keamanan anti-hack harga)
            $realCostData = $this->_getRealShippingCost(
                $kirimaja, $senderAddressData['kirimaja_data'], $receiverAddressData['kirimaja_data'],
                $beratFisik, $p, $l, $t, $targetCourier, $targetService, $rawItemPrice, $request->ansuransi
            );

            $ongkirFix = (int) $realCostData['cost'];
            $asuransiFix = ($request->ansuransi == 'iya') ? (int) $realCostData['insurance'] : 0;

            // 4. Hitung Harga & COD Fee
            $codFeeFix = 0; $finalPriceDB = 0; $finalCodValueAPI = 0;
            $paymentMethod = $request->payment_method;

            if ($paymentMethod === 'COD' || $paymentMethod === 'CODBARANG') {
                $baseTotal = $ongkirFix + $asuransiFix;
                if ($paymentMethod === 'CODBARANG') $baseTotal += $rawItemPrice;

                $rawFee = $baseTotal * 0.03;
                $codFeeBeforePPN = max(2500, $rawFee);
                $ppnFee = $codFeeBeforePPN * 0.11;
                $grandTotalMentah = $baseTotal + $codFeeBeforePPN + $ppnFee;

                $finalCodValueAPI = (int) (ceil($grandTotalMentah / 500) * 500);
                $finalPriceDB = $finalCodValueAPI;
                $codFeeFix = $codFeeBeforePPN + $ppnFee;
            } else {
                $finalPriceDB = $ongkirFix + $asuransiFix;
                $finalCodValueAPI = 0;
            }

            // 5. Simpan Pesanan ke DB
            $pesanan = new Pesanan();
            $pesanan->nomor_invoice = $nomorInvoice;

            // Pengikat bahwa paket ini adalah bagian dari "Multi Koli"
            $pesanan->shipping_ref = $groupId;
            $pesanan->idempotency_key = $groupId . '-' . Str::random(4);

            $pesanan->customer_id = Auth::id();
            $pesanan->id_pengguna_pembeli = Auth::id();

            $pesanan->sender_name = $request->sender_name; $pesanan->sender_phone = $this->_sanitizePhone($request->sender_phone);
            $pesanan->sender_address = $request->sender_address; $pesanan->sender_district_id = $request->sender_district_id;
            $pesanan->sender_subdistrict_id = $request->sender_subdistrict_id; $pesanan->sender_province = $request->sender_province;
            $pesanan->sender_regency = $request->sender_regency; $pesanan->sender_district = $request->sender_district;
            $pesanan->sender_village = $request->sender_village; $pesanan->sender_postal_code = $request->sender_postal_code;
            $pesanan->sender_lat = $senderAddressData['lat'] ?? 0; $pesanan->sender_lng = $senderAddressData['lng'] ?? 0;

            $pesanan->receiver_name = $request->receiver_name; $pesanan->receiver_phone = $this->_sanitizePhone($request->receiver_phone);
            $pesanan->receiver_address = $request->receiver_address; $pesanan->receiver_district_id = $request->receiver_district_id;
            $pesanan->receiver_subdistrict_id = $request->receiver_subdistrict_id; $pesanan->receiver_province = $request->receiver_province;
            $pesanan->receiver_regency = $request->receiver_regency; $pesanan->receiver_district = $request->receiver_district;
            $pesanan->receiver_village = $request->receiver_village; $pesanan->receiver_postal_code = $request->receiver_postal_code;
            $pesanan->receiver_lat = $receiverAddressData['lat'] ?? 0; $pesanan->receiver_lng = $receiverAddressData['lng'] ?? 0;

            $pesanan->item_description = $request->item_description;
            $pesanan->weight = $beratFisik; $pesanan->length = $p; $pesanan->width = $l; $pesanan->height = $t;
            $pesanan->item_type = $request->item_type ?? 7; $pesanan->item_price = $rawItemPrice;

            $pesanan->expedition = sprintf('mix-%s-%s-%d-%d-%d', strtolower($targetCourier), strtoupper($targetService), $ongkirFix, $asuransiFix, $codFeeFix);
            $pesanan->service_type = 'Single-Mobile';
            $pesanan->payment_method = $request->payment_method;
            $pesanan->ansuransi = $request->ansuransi;

            $pesanan->shipping_cost = $ongkirFix; $pesanan->insurance_cost = $asuransiFix;
            $pesanan->cod_fee = $codFeeFix; $pesanan->price = $finalPriceDB;

            $pesanan->status = 'Menunggu Pembayaran';
            $pesanan->status_pesanan = 'Menunggu Pembayaran';
            $pesanan->tanggal_pesanan = now();

            $user = User::where('id_pengguna', Auth::id())->first();
            $paymentUrl = null;

            // 6. LOGIKA PEMBAYARAN PER-PAKET
            if ($request->payment_method === 'CASH') {
                if (!$user || $user->id_pengguna != 4) throw new Exception("Metode Cash hanya untuk Admin.");
                $pesanan->status = 'Menunggu Pickup'; $pesanan->status_pesanan = 'Menunggu Pickup';
            } elseif ($request->payment_method === 'Potong Saldo') {
                if (!$user || $user->saldo < $finalPriceDB) throw new Exception("Saldo tidak mencukupi untuk paket ini.");
                $user->decrement('saldo', $finalPriceDB);
                $pesanan->status = 'Menunggu Pickup'; $pesanan->status_pesanan = 'Menunggu Pickup';
            } elseif (!in_array($request->payment_method, ['CASH', 'Potong Saldo', 'COD', 'CODBARANG'])) {

                $pesanan->status = 'Menunggu Pembayaran';
                $pesanan->status_pesanan = 'Menunggu Pembayaran';
                $pesanan->save();

                $paymentUrl = null;

                if ($request->payment_method === 'DOKU_JOKUL') {
                    // --- 1. VIA DOKU JOKUL ---
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($pesanan->nomor_invoice, $finalPriceDB);

                    if (empty($paymentUrl)) {
                        throw new Exception("Gagal membuat tagihan DOKU.");
                    }
                } else {
                    // --- 2. VIA TRIPAY (BRIVA, QRIS, dll) ---
                    $orderItems = [
                        ['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim ' . $pesanan->nomor_invoice, 'price' => $finalPriceDB, 'quantity' => 1]
                    ];

                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $pesanan, $finalPriceDB, $orderItems, $user);

                    if (empty($tripayResponse['success'])) {
                        throw new Exception($tripayResponse['message'] ?? 'Gagal membuat tagihan Tripay.');
                    }
                    $paymentUrl = $tripayResponse['data']['checkout_url'];
                }

                $pesanan->payment_url = $paymentUrl;
                $pesanan->save();

            } else {
                throw new Exception("Metode pembayaran tidak valid.");
            }

            $pesanan->save();

            // 7. HIT API KIRIMINAJA
            if (in_array($request->payment_method, ['COD', 'CODBARANG', 'Potong Saldo', 'CASH'])) {
                $apiPayload = [
                    'item_description' => $pesanan->item_description, 'item_price' => $rawItemPrice,
                    'weight' => $finalBookingWeight, 'length' => $p, 'width' => $l, 'height' => $t,
                    'courier_code' => $targetCourier, 'service_code' => $targetService, 'ansuransi' => $request->ansuransi
                ];

                $kiriminResponse = $this->_createKiriminAjaOrderLocal(
                    $apiPayload, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $finalCodValueAPI, $ongkirFix, $asuransiFix
                );

                if (($kiriminResponse['status'] ?? false) === true) {
                    $resi = $kiriminResponse['details']['awb'] ?? $kiriminResponse['awb'] ?? $kiriminResponse['order_id'] ?? null;
                    if($resi) {
                        $pesanan->resi = $resi;
                        $pesanan->status = 'Pesanan Dibuat';
                        $pesanan->status_pesanan = 'Pesanan Dibuat';
                        $pesanan->save();
                    }
                } else {
                    $pesanan->status = 'Gagal Kirim Resi';
                    $pesanan->save();
                    Log::error("Gagal KiriminAja Single:", $kiriminResponse);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Paket berhasil dikunci & disimpan!",
                'resi' => $pesanan->resi ?? 'DIPROSES',
                'payment_url' => $paymentUrl
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Store Single Mobile Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // API: STORE MULTI (Pesanan Massal Mobile)
    // ==========================================
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        Log::info('[API MOBILE MULTI] Menerima request pembuatan Multi Koli. Data Request:', $request->all());
        $this->_normalizePaymentMethod($request);
        $key = $request->input('idempotency_key');

        if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pesanan massal sudah diproses sebelumnya.'], 422);
        }

        // Deteksi apakah kurir yang dipilih adalah Instant atau Sameday
        $isInstant = (stripos($request->courier_code, 'instant') !== false || stripos($request->service_code, 'sameday') !== false);

        // Jika kurir Instant, pastikan lat & lng tidak kosong atau 0
        if ($isInstant) {
            if (empty($request->sender_lat) || $request->sender_lat == 0 ||
                empty($request->receiver_lat) || $request->receiver_lat == 0) {

                return response()->json([
                    'success' => false,
                    'message' => 'PENGIRIMAN GAGAL: Untuk kurir Instant/Sameday, Anda wajib menekan tombol "Ambil Lokasi Otomatis" untuk menangkap koordinat GPS.'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $rawItemPrice = str_replace(['Rp', '.', ' ', ','], '', $request->input('item_price'));
            $request->merge(['item_price' => $rawItemPrice]);

            $request->validate([
                'sender_name' => 'required', 'sender_phone' => 'required',
                'receiver_name' => 'required', 'receiver_phone' => 'required|min:9|max:13',
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'payment_method' => 'required', 'item_price' => 'required|numeric|min:1000',
                'packages' => 'required|array|min:1',
                'packages.*.weight' => 'required|numeric|min:1',
                'packages.*.courier_code' => 'required|string',
                'packages.*.service_code' => 'required|string',
            ]);

            $this->_saveKontak($request, 'sender', 'Pengirim');
            $this->_saveKontak($request, 'receiver', 'Penerima');

            $packages = $request->input('packages');
            $totalPaket = count($packages);
            $hargaBarangPerPaket = floor($request->input('item_price') / $totalPaket);
            if ($hargaBarangPerPaket < 1000) $hargaBarangPerPaket = 1000;

            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            $createdOrders = [];
            $tempDataPerOrder = [];
            $grandTotalTagihan = 0;
            $totalOngkirAll = 0;

            foreach ($packages as $index => $pkg) {
                do {
                    $nomorInvoice = 'SCK-' . date('ymd') . '-'. strtoupper(Str::random(3)) . '-' . ($index + 1);
                } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

                $beratFisik = (int) $pkg['weight'];
                $p = (int) ($pkg['length'] ?? 10);
                $l = (int) ($pkg['width'] ?? 10);
                $t = (int) ($pkg['height'] ?? 10);

                $targetCourier = $pkg['courier_code'];
                $targetService = $pkg['service_code'];

                $divisor = (strpos(strtolower($targetService), 'cargo') !== false || strpos(strtolower($targetService), 'trucking') !== false) ? 4000 : 6000;
                $volumeWeight = ($p * $l * $t) / $divisor * 1000;
                $finalBookingWeight = (int) ceil(max($beratFisik, $volumeWeight));

                $realCostData = $this->_getRealShippingCost(
                    $kirimaja, $senderAddressData['kirimaja_data'], $receiverAddressData['kirimaja_data'],
                    $beratFisik, $p, $l, $t, $targetCourier, $targetService, $hargaBarangPerPaket, $request->ansuransi
                );

                $ongkirFix = (int) $realCostData['cost'];
                $asuransiFix = ($request->ansuransi == 'iya') ? (int) $realCostData['insurance'] : 0;

                $codFeeFix = 0; $finalPriceDB = 0; $finalCodValueAPI = 0;
                $paymentMethod = $request->payment_method;

                if ($paymentMethod === 'COD' || $paymentMethod === 'CODBARANG') {
                    $baseTotal = $ongkirFix + $asuransiFix;
                    if ($paymentMethod === 'CODBARANG') $baseTotal += $hargaBarangPerPaket;

                    $rawFee = $baseTotal * 0.03;
                    $codFeeBeforePPN = max(2500, $rawFee);
                    $ppnFee = $codFeeBeforePPN * 0.11;
                    $grandTotalMentah = $baseTotal + $codFeeBeforePPN + $ppnFee;

                    $finalCodValueAPI = (int) (ceil($grandTotalMentah / 500) * 500);
                    $finalPriceDB = $finalCodValueAPI;
                    $codFeeFix = $codFeeBeforePPN + $ppnFee;
                } else {
                    $finalPriceDB = $ongkirFix + $asuransiFix;
                    $finalCodValueAPI = 0;
                }

                $pesanan = new Pesanan();
                $pesanan->nomor_invoice = $nomorInvoice;
                $pesanan->customer_id = Auth::id();
                $pesanan->id_pengguna_pembeli = Auth::id();

                $pesanan->sender_name = $request->sender_name; $pesanan->sender_phone = $this->_sanitizePhone($request->sender_phone);
                $pesanan->sender_address = $request->sender_address; $pesanan->sender_district_id = $request->sender_district_id;
                $pesanan->sender_subdistrict_id = $request->sender_subdistrict_id; $pesanan->sender_province = $request->sender_province;
                $pesanan->sender_regency = $request->sender_regency; $pesanan->sender_district = $request->sender_district;
                $pesanan->sender_village = $request->sender_village; $pesanan->sender_postal_code = $request->sender_postal_code;
                $pesanan->sender_lat = $senderAddressData['lat'] ?? 0; $pesanan->sender_lng = $senderAddressData['lng'] ?? 0;

                $pesanan->receiver_name = $request->receiver_name; $pesanan->receiver_phone = $this->_sanitizePhone($request->receiver_phone);
                $pesanan->receiver_address = $request->receiver_address; $pesanan->receiver_district_id = $request->receiver_district_id;
                $pesanan->receiver_subdistrict_id = $request->receiver_subdistrict_id; $pesanan->receiver_province = $request->receiver_province;
                $pesanan->receiver_regency = $request->receiver_regency; $pesanan->receiver_district = $request->receiver_district;
                $pesanan->receiver_village = $request->receiver_village; $pesanan->receiver_postal_code = $request->receiver_postal_code;
                $pesanan->receiver_lat = $receiverAddressData['lat'] ?? 0; $pesanan->receiver_lng = $receiverAddressData['lng'] ?? 0;

                $pesanan->item_description = $request->item_description . " (Paket " . ($index+1) . ")";
                $pesanan->weight = $beratFisik; $pesanan->length = $p; $pesanan->width = $l; $pesanan->height = $t;
                $pesanan->item_type = $request->item_type ?? 7; $pesanan->item_price = $hargaBarangPerPaket;

                $pesanan->expedition = sprintf('mix-%s-%s-%d-%d-%d', strtolower($targetCourier), strtoupper($targetService), $ongkirFix, $asuransiFix, $codFeeFix);
                $pesanan->service_type = 'Multi-Mobile';
                $pesanan->payment_method = $request->payment_method;
                $pesanan->ansuransi = $request->ansuransi;

                $pesanan->shipping_cost = $ongkirFix; $pesanan->insurance_cost = $asuransiFix;
                $pesanan->cod_fee = $codFeeFix; $pesanan->price = $finalPriceDB;

                $pesanan->status = 'Menunggu Pembayaran'; $pesanan->status_pesanan = 'Menunggu Pembayaran';
                $pesanan->tanggal_pesanan = now();
                if ($index === 0) $pesanan->idempotency_key = $key;

                $pesanan->save();

                $createdOrders[] = $pesanan;
                $tempDataPerOrder[$pesanan->id] = [
                    'cod_value_api' => $finalCodValueAPI, 'shipping_cost' => $ongkirFix, 'insurance_cost' => $asuransiFix,
                    'item_value_api' => $hargaBarangPerPaket, 'courier_code' => $targetCourier, 'service_code' => $targetService,
                    'booking_weight' => $finalBookingWeight
                ];

                if (!in_array($request->payment_method, ['COD', 'CODBARANG'])) $grandTotalTagihan += $finalPriceDB;
                $totalOngkirAll += $ongkirFix;
            }

            $masterOrder = $createdOrders[0];
            $paymentUrl = null;

            // Mengambil user dengan akurat dari Auth
            $user = User::where('id_pengguna', Auth::id())->first();

            // =========================================================
            // PROSES PEMBAYARAN MULTI KOLI
            // =========================================================
            if ($request->payment_method === 'CASH') {
                if (!$user || $user->id_pengguna != 4) {
                    throw new Exception("Metode pembayaran Cash hanya tersedia untuk Admin.");
                }
                foreach ($createdOrders as $o) {
                    $o->status = 'Menunggu Pickup';
                    $o->status_pesanan = 'Menunggu Pickup';
                    $o->save();
                }
            }
            elseif ($request->payment_method === 'Potong Saldo') {
                if (!$user || $user->saldo < $grandTotalTagihan) {
                    throw new Exception("Saldo Anda tidak mencukupi.");
                }
                $user->decrement('saldo', $grandTotalTagihan);
                foreach ($createdOrders as $o) {
                    $o->status = 'Menunggu Pickup';
                    $o->status_pesanan = 'Menunggu Pickup';
                    $o->save();
                }
            }
            elseif (in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                 foreach ($createdOrders as $o) {
                     $o->status = 'Menunggu Pickup';
                     $o->status_pesanan = 'Menunggu Pickup';
                     $o->save();
                }
            } elseif (!in_array($request->payment_method, ['CASH', 'Potong Saldo', 'COD', 'CODBARANG'])) {

                $paymentUrl = null;
                $masterInvoice = $masterOrder->nomor_invoice;

                // Set status semua paket jadi Menunggu Pembayaran
                foreach ($createdOrders as $o) {
                    $o->status = 'Menunggu Pembayaran';
                    $o->status_pesanan = 'Menunggu Pembayaran';
                    $o->save();
                }

                if ($request->payment_method === 'DOKU_JOKUL') {
                    // --- 1. VIA DOKU JOKUL MULTI ---
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($masterInvoice, $grandTotalTagihan);

                    if (empty($paymentUrl)) {
                        throw new Exception("Gagal membuat tagihan DOKU.");
                    }
                } else {
                    // --- 2. VIA TRIPAY MULTI ---
                    $orderItems = [
                        ['sku' => 'SHIPPING', 'name' => 'Ongkir Multi Koli ' . $masterInvoice, 'price' => $grandTotalTagihan, 'quantity' => 1]
                    ];

                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $masterOrder, $grandTotalTagihan, $orderItems, $user);

                    if (empty($tripayResponse['success'])) {
                        throw new Exception($tripayResponse['message'] ?? 'Gagal membuat tagihan Tripay.');
                    }
                    $paymentUrl = $tripayResponse['data']['checkout_url'];
                }

                // Simpan URL yang sama persis ke semua paket (Penting untuk webhook DOKU/Tripay nantinya)
                foreach ($createdOrders as $o) {
                    $o->payment_url = $paymentUrl;
                    $o->save();
                }

            } else {
                throw new Exception("Metode pembayaran " . $request->payment_method . " tidak dikenali.");
            }

            // =========================================================
            // HIT API KIRIMINAJA JIKA LUNAS / COD / CASH
            // =========================================================
            if (in_array($request->payment_method, ['COD', 'CODBARANG', 'Potong Saldo', 'CASH'])) {
                foreach ($createdOrders as $order) {
                    $temp = $tempDataPerOrder[$order->id];
                    $apiPayload = [
                        'item_description' => $order->item_description, 'item_price' => $temp['item_value_api'],
                        'weight' => $temp['booking_weight'], 'length' => (int) $order->length, 'width' => (int) $order->width, 'height' => (int) $order->height,
                        'courier_code' => $temp['courier_code'], 'service_code' => $temp['service_code'], 'ansuransi' => $request->ansuransi
                    ];

                    $kiriminResponse = $this->_createKiriminAjaOrderLocal(
                        $apiPayload, $order, $kirimaja, $senderAddressData, $receiverAddressData,
                        $temp['cod_value_api'], $temp['shipping_cost'], $temp['insurance_cost']
                    );

                    if (($kiriminResponse['status'] ?? false) === true) {
                        $resi = $kiriminResponse['details']['awb'] ?? $kiriminResponse['awb'] ?? $kiriminResponse['order_id'] ?? null;
                        if($resi) {
                            $order->resi = $resi;
                            $order->status = 'Pesanan Dibuat';
                            $order->status_pesanan = 'Pesanan Dibuat';
                            $order->save();
                        }
                    } else {
                        $order->status = 'Gagal Kirim Resi';
                        $order->save();
                    }
                }
            }

            // NOTIFIKASI WA
            try {
                $waTotalFee = collect($createdOrders)->sum('cod_fee');
                $waTotalIns = collect($createdOrders)->sum('insurance_cost');
                $waTotalPrice = collect($createdOrders)->sum('price');
                $this->_sendWhatsappNotification(
                    $masterOrder, ['payment_method' => $request->payment_method, 'item_price' => $request->item_price],
                    $totalOngkirAll, $waTotalIns, $waTotalFee, $waTotalPrice, $request, $totalPaket
                );
            } catch (Exception $e) { Log::error('Notif Error: ' . $e->getMessage()); }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdOrders) . " Pesanan berhasil dibuat!",
                'payment_url' => $paymentUrl // <--- Ini akan ditangkap oleh React Native jika GATEWAY
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Store Multi Mobile Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // INNER HELPERS: COST & KIRIMINAJA
    // ==========================================
    private function _getRealShippingCost($kirimaja, $senderData, $receiverData, $weight, $length, $width, $height, $courier, $service, $itemValue, $ansuransi)
    {
        if($weight < 1) $weight = 1;
        $useInsurance = ($ansuransi == 'iya') ? 1 : 0;

        $category = 'regular';
        if (strpos(strtolower($service), 'trucking') !== false || strpos(strtolower($service), 'cargo') !== false) {
            $category = 'trucking';
        }

        $divisor = ($category === 'trucking') ? 4000 : 6000;
        $volumeWeight = ($length * $width * $height) / $divisor * 1000;
        $chargeableWeight = (int) ceil(max($weight, $volumeWeight));

        $options = $kirimaja->getExpressPricing(
            $senderData['district_id'], $senderData['subdistrict_id'],
            $receiverData['district_id'], $receiverData['subdistrict_id'],
            $chargeableWeight, $length, $width, $height, $itemValue, null, $category, $useInsurance
        );

        $foundCost = 0; $foundInsurance = 0;

        if (isset($options['results'])) {
            foreach ($options['results'] as $res) {
                $apiCourier = strtolower($res['service'] ?? '');
                $apiService = strtolower($res['service_type'] ?? '');
                if ($apiCourier == strtolower($courier) && strpos($apiService, strtolower($service)) !== false) {
                    $foundCost = (int) ($res['cost'] ?? 0);
                    $foundInsurance = (int) ($res['insurance'] ?? 0);
                    break;
                }
            }
        }

        if ($foundCost == 0 && isset($options['results'])) {
             foreach ($options['results'] as $res) {
                if (strtolower($res['service']) == strtolower($courier)) {
                    $foundCost = (int) ($res['cost'] ?? 0);
                    $foundInsurance = (int) ($res['insurance'] ?? 0);
                    break;
                }
             }
        }

        if ($foundCost == 0) throw new Exception("Ongkir tidak ditemukan utk kurir {$courier}.");
        return ['cost' => $foundCost, 'insurance' => $foundInsurance];
    }

    private function _createKiriminAjaOrderLocal($data, $order, $kirimaja, $senderData, $receiverData, $cod_value, $shipping_cost, $insurance_cost) {
        $serviceGroup = 'regular';
        $serviceCodeLower = strtolower($data['service_code']);

        if (strpos($serviceCodeLower, 'cargo') !== false || strpos($serviceCodeLower, 'trucking') !== false) {
            $serviceGroup = 'trucking';
        }

        // 1. Deteksi apakah ini layanan Instant / Sameday
        $isInstant = (stripos($request->courier_code, 'instant') !== false || stripos($request->service_code, 'sameday') !== false);
        if ($isInstant && (empty($request->sender_lat) || empty($request->receiver_lat))) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan Instant/Sameday wajib melampirkan titik koordinat GPS.'
            ], 422);
        }

        $useInsuranceFlag = ($data['ansuransi'] == 'iya') ? 1 : 0;

        // =========================================================
        // JIKA LAYANAN INSTANT / SAMEDAY (Memakai Titik Koordinat)
        // =========================================================
        if ($isInstant) {
            $payload = [
                'service' => strtolower($data['courier_code']), 'service_type' => $data['service_code'], 'vehicle' => 'motor',
                'order_prefix' => $order->nomor_invoice,
                'packages' => [[
                    'destination_name' => $order->receiver_name, 'destination_phone' => $order->receiver_phone,
                    // Gunakan koordinat dari database, fallback ke titik default jika 0
                    'destination_lat' => (!empty($order->receiver_lat) && $order->receiver_lat != 0) ? $order->receiver_lat : null,
                    'destination_long' => (!empty($order->receiver_lng) && $order->receiver_lng != 0) ? $order->receiver_lng : null,
                    'destination_address' => $order->receiver_address, 'destination_address_note' => '-',

                    'origin_name' => $order->sender_name, 'origin_phone' => $order->sender_phone,
                    // Gunakan koordinat dari database, fallback ke titik default jika 0
                    'origin_lat' => (!empty($order->sender_lat) && $order->sender_lat != 0) ? $order->sender_lat : null,
                    'origin_long' => (!empty($order->sender_lng) && $order->sender_lng != 0) ? $order->sender_lng : null,
                    'origin_address' => $order->sender_address, 'origin_address_note' => '-',

                    'shipping_price' => (int)$shipping_cost,
                    'item' => [
                        'name' => $data['item_description'], 'description' => 'Pesanan ' . $order->nomor_invoice,
                        'price' => (int)$data['item_price'], 'weight' => (int)$data['weight'],
                    ]
                ]]
            ];

            Log::info('[API MOBILE] Payload Instant/Sameday KiriminAja:', $payload);
            return $kirimaja->createInstantOrder($payload);
        }

        // =========================================================
        // JIKA LAYANAN REGULAR / EXPRESS / CARGO
        // =========================================================
        $schedules = $kirimaja->getSchedules();
        $pickupSchedule = $schedules['clock'] ?? 'now';

        $payload = [
            'address' => $order->sender_address, 'phone' => $order->sender_phone, 'name' => $order->sender_name,
            'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
            'zipcode' => $senderData['kirimaja_data']['postal_code'],
            'platform_name' => 'tokosancaka.com', 'category' => $serviceGroup,
            'schedule' => $pickupSchedule,
            // Opsional: API Regulernya KiriminAja terkadang juga butuh koordinat pengirim
            'latitude' => $order->sender_lat != 0 ? $order->sender_lat : null,
            'longitude' => $order->sender_lng != 0 ? $order->sender_lng : null,

            'packages' => [[
                'order_id' => $order->nomor_invoice, 'item_name' => $data['item_description'],
                'package_type_id' => $order->item_type ?? 7,
                'destination_name' => $order->receiver_name, 'destination_phone' => $order->receiver_phone,
                'destination_address' => $order->receiver_address,
                'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'],
                'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                'weight' => (int)$data['weight'], 'width' => (int)$data['width'], 'height' => (int)$data['height'], 'length' => (int)$data['length'],
                'item_value' => (int)$data['item_price'],
                'insurance' => $useInsuranceFlag, 'insurance_amount' => ($useInsuranceFlag === 1) ? (int)$insurance_cost : 0,
                'cod' => (int)$cod_value,
                'service' => $data['courier_code'],
                'service_type' => $data['service_code'],
                'shipping_cost' => (int)$shipping_cost
            ]]
        ];

        Log::info('[API MOBILE] Payload Express/Cargo KiriminAja:', $payload);
        return $kirimaja->createExpressOrder($payload);
    }

    // ==========================================
    // HELPER: WHATSAPP NOTIFICATION
    // ==========================================
    private function _sendWhatsappNotification($masterOrder, $data, $ongkir, $ins, $fee, $total, $req, $count) {
        $adminPhone = '085745808809';
        $formattedTotal = 'Rp ' . number_format($total, 0, ',', '.');
        $resiText = $masterOrder->resi ? $masterOrder->resi : 'Sedang diproses';

        $msgSender = "Halo *{$masterOrder->sender_name}*,\n\nPesanan Anda melalui *Sancaka Express* berhasil kami proses.\n\n🧾 Invoice: *{$masterOrder->nomor_invoice}*\n👤 Penerima: *{$masterOrder->receiver_name}*\n📦 Jumlah Koli: *{$count} Paket*\n🏷️ Total Tagihan: *{$formattedTotal}*\n📍 Status: *{$masterOrder->status_pesanan}*\n\nTerima kasih telah mempercayakan pengiriman Anda kepada kami.";
        $msgReceiver = "Halo *{$masterOrder->receiver_name}*,\n\nAda paket kiriman untuk Anda dari *{$masterOrder->sender_name}* melalui *Sancaka Express*.\n\n🧾 Invoice: *{$masterOrder->nomor_invoice}*\n🏷️ Nomor Resi: *{$resiText}*\n\nMohon tunggu kedatangan kurir kami di lokasi Anda. Terima kasih.";
        $msgAdmin = "⚠️ *ORDER BARU SANCAKA EXPRESS* ⚠️\n\n🧾 Invoice: *{$masterOrder->nomor_invoice}*\n📤 Pengirim: *{$masterOrder->sender_name}* ({$masterOrder->sender_phone})\n📥 Penerima: *{$masterOrder->receiver_name}* ({$masterOrder->receiver_phone})\n📦 Jumlah Koli: *{$count} Paket*\n💳 Metode Bayar: *{$data['payment_method']}*\n💰 Total Tagihan: *{$formattedTotal}*";

        $this->_kirimFonnte($masterOrder->sender_phone, $msgSender);
        $this->_kirimFonnte($masterOrder->receiver_phone, $msgReceiver);
        $this->_kirimFonnte($adminPhone, $msgAdmin);
        return true;
    }

    private function _kirimFonnte($target, $message) {
        $token = env('FONNTE_TOKEN', 'TOKEN_FONNTE_ANDA');
        try {
            Http::withHeaders(['Authorization' => $token])->post('https://api.fonnte.com/send', [
                'target' => $target, 'message' => $message, 'countryCode' => '62'
            ]);
        } catch (\Exception $e) {
            Log::error('Fonnte API Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // HELPER: TRIPAY TRANSACTION
    // ==========================================
    private function _createTripayTransactionInternal(array $data, Pesanan $pesanan, int $total, array $orderItems, clone $user): array
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        $customerEmail = $user->email;
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
        }

        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $pesanan->nomor_invoice,
            'amount'         => $total,
            'customer_name'  => $data['receiver_name'], // Pakai nama penerima
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'],
            'order_items'    => $orderItems,
            'return_url'     => url('/riwayatpesanan'), // Arahkan kembali jika sudah bayar
            'expired_time'   => time() + (24 * 60 * 60), // 24 jam
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)->post($baseUrl, $payload);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP ' . $response->status() . ').'];
            }

            $responseData = $response->json();
            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Error saat membuat transaksi Tripay Mobile: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan internal saat memproses pembayaran.'];
        }
    }
}
