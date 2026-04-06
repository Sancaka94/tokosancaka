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
use App\Services\DokuJokulService;
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
        elseif ($pm === '#DOKU') $pm = 'DOKU_JOKUL';

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
    // API: CEK ONGKIR
    // ==========================================
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $request->validate([
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'weight' => 'required|numeric', 'volume' => 'required|numeric',
            ]);

            $itemPrice = (int) str_replace(['Rp', '.', ',', ' '], '', $request->item_price);
            $useInsurance = ($request->ansuransi == 'iya' || $request->ansuransi == 1 || $request->ansuransi == 'true') ? 1 : 0;

            $cat = $request->input('service_type', 'regular');

            $options = $kirimaja->getExpressPricing(
                $request->sender_district_id, $request->sender_subdistrict_id,
                $request->receiver_district_id, $request->receiver_subdistrict_id,
                (int) $request->weight, (int) $request->volume, 1, 1,
                $itemPrice, null, $cat, $useInsurance
            );

            if (isset($options['status']) && $options['status'] === true && !empty($options['results'])) {
                 return response()->json(['success' => true, 'data' => $options]);
            }
            return response()->json(['success' => false, 'message' => 'Layanan tidak tersedia untuk rute ini.'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // API: STORE SINGLE (Pesanan Satuan Mobile)
    // ==========================================
    public function storeSingle(Request $request, KiriminAjaService $kirimaja)
    {
        $this->_normalizePaymentMethod($request);
        $key = $request->input('idempotency_key');

        if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pesanan ini sudah dibuat. Harap refresh.'], 422);
        }

        DB::beginTransaction();
        try {
            $itemPrice = (int) str_replace(['Rp', '.', ',', ' '], '', $request->item_price);

            $request->validate([
                'sender_name' => 'required', 'sender_phone' => 'required',
                'receiver_name' => 'required', 'receiver_phone' => 'required|min:9|max:13',
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'receiver_address' => 'required|string|min:10', 'sender_address' => 'required|string|min:10',
                'courier_code' => 'required', 'service_code' => 'required',
                'weight' => 'required|numeric', 'payment_method' => 'required',
                'item_description' => 'required'
            ]);

            $this->_saveKontak($request, 'sender', 'Pengirim');
            $this->_saveKontak($request, 'receiver', 'Penerima');

            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            // --- HITUNG VOLUME DAN BERAT AKTUAL (P x L x T) ---
            $beratFisik = (int) $request->weight;
            $p = (int) ($request->length ?? 10);
            $l = (int) ($request->width ?? 10);
            $t = (int) ($request->height ?? 10);

            $divisor = (strpos(strtolower($request->service_code), 'cargo') !== false || strpos(strtolower($request->service_code), 'trucking') !== false) ? 4000 : 6000;
            $volumeWeight = ($p * $l * $t) / $divisor * 1000;
            $chargeableWeight = (int) ceil(max($beratFisik, $volumeWeight));

            $realCostData = $this->_getRealShippingCost(
                $kirimaja, $senderAddressData['kirimaja_data'], $receiverAddressData['kirimaja_data'],
                $beratFisik, $p, $l, $t, $request->courier_code, $request->service_code, $itemPrice, $request->ansuransi
            );

            $shippingCost = (int) $realCostData['cost'];
            $insuranceCost = ($request->ansuransi == 'iya') ? (int)$realCostData['insurance'] : 0;

            $codFee = 0; $totalPrice = 0; $codValueApi = 0;

            if ($request->payment_method == 'COD' || $request->payment_method == 'CODBARANG') {
                $baseTotal = $shippingCost + $insuranceCost;
                if ($request->payment_method == 'CODBARANG') $baseTotal += $itemPrice;

                $rawFee = $baseTotal * 0.03;
                $codFeeBeforePPN = max(2500, $rawFee);
                $ppnFee = $codFeeBeforePPN * 0.11;
                $grandTotalMentah = $baseTotal + $codFeeBeforePPN + $ppnFee;

                $codValueApi = (int) (ceil($grandTotalMentah / 500) * 500);
                $totalPrice = $codValueApi;
                $codFee = $codFeeBeforePPN + $ppnFee;
            } else {
                $totalPrice = $shippingCost + $insuranceCost;
                $codValueApi = 0;
            }

            $pesanan = new Pesanan();
            $pesanan->nomor_invoice = $this->generateInvoiceNumber();
            $pesanan->customer_id = Auth::id();
            $pesanan->id_pengguna_pembeli = Auth::id();

            // SENDER & RECEIVER
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
            $pesanan->item_type = $request->item_type ?? 7; $pesanan->item_price = $itemPrice;

            $pesanan->payment_method = $request->payment_method;
            $pesanan->ansuransi = $request->ansuransi;

            $pesanan->shipping_cost = $shippingCost;
            $pesanan->insurance_cost = $insuranceCost;
            $pesanan->cod_fee = $codFee;
            $pesanan->price = $totalPrice;

            $pesanan->expedition = sprintf('mix-%s-%s-%d-%d-%d', strtolower($request->courier_code), strtoupper($request->service_code), $shippingCost, $insuranceCost, $codFee);
            $pesanan->service_type = 'Single-Mobile';
            $pesanan->status = 'Menunggu Pembayaran';
            $pesanan->status_pesanan = 'Menunggu Pembayaran';
            $pesanan->tanggal_pesanan = now();
            $pesanan->idempotency_key = $key;

            $pesanan->save();

            $paymentUrl = null;
            $user = User::where('id_pengguna', Auth::id())->first();


            // --- PEMBAYARAN ---
            // LOGIKA KHUSUS VIP ADMIN ID 4 (BAYAR CASH TANPA POTONG SALDO)
            if ($request->payment_method === 'CASH') {
                // UBAH 'id' MENJADI 'id_pengguna'
                if ($user->id_pengguna != 4) {
                    throw new Exception("Metode pembayaran Cash hanya untuk Admin.");
                }
                $pesanan->status = 'Menunggu Pickup'; $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->save();
            }
            elseif ($request->payment_method === 'Potong Saldo') {
                if ($user->saldo < $pesanan->price) throw new Exception("Saldo Anda tidak mencukupi.");
                $user->decrement('saldo', $pesanan->price);

                $pesanan->status = 'Menunggu Pickup'; $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->save();
            }
            elseif (in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                $pesanan->status = 'Menunggu Pickup'; $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->save();
            }
            else {
                $paymentGateway = 'tripay';
                if ($request->payment_method === 'DOKU_JOKUL') $paymentGateway = 'doku';

                if ($paymentGateway === 'doku') {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($pesanan->nomor_invoice, $pesanan->price);
                    if (empty($paymentUrl)) throw new Exception('Gagal generate DOKU URL.');
                } else {
                    $orderItems = [[ 'sku' => 'SHIPPING', 'name' => 'Ongkir Pesanan', 'price' => (int)$pesanan->price, 'quantity' => 1 ]];
                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $pesanan, $pesanan->price, $orderItems);
                    if (empty($tripayResponse['success'])) throw new Exception('Tripay Error: ' . ($tripayResponse['message'] ?? 'Unknown'));
                    $paymentUrl = $tripayResponse['data']['checkout_url'];
                }
                $pesanan->payment_url = $paymentUrl;
                $pesanan->save();
            }

            // HIT API KIRIMINAJA JIKA LUNAS / COD / CASH
            $resi = "DIPROSES";
            if (in_array($request->payment_method, ['COD', 'CODBARANG', 'Potong Saldo', 'CASH'])) {
                $apiPayload = [
                    'item_description' => $pesanan->item_description, 'item_price' => $itemPrice,
                    'weight' => $chargeableWeight, 'length' => $p, 'width' => $l, 'height' => $t,
                    'courier_code' => $request->courier_code, 'service_code' => $request->service_code, 'ansuransi' => $request->ansuransi
                ];

                $kiriminResponse = $this->_createKiriminAjaOrderLocal(
                    $apiPayload, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $codValueApi, $shippingCost, $insuranceCost
                );

                if (($kiriminResponse['status'] ?? false) === true) {
                    $resi = $kiriminResponse['details']['awb'] ?? $kiriminResponse['awb'] ?? null;
                    if($resi) {
                        $pesanan->resi = $resi;
                        $pesanan->status = 'Dikemas'; $pesanan->status_pesanan = 'Dikemas';
                    } else {
                        $pesanan->status = 'Menunggu Resi'; $pesanan->status_pesanan = 'Menunggu Resi';
                    }
                    $pesanan->save();
                } else {
                    throw new Exception("Ekspedisi Error: " . ($kiriminResponse['text'] ?? 'Unknown Error'));
                }
            }

            try {
                $this->_sendWhatsappNotification($pesanan, ['payment_method' => $request->payment_method, 'item_price' => $itemPrice], $shippingCost, $insuranceCost, $codFee, $totalPrice, $request, 1);
            } catch (Exception $e) { Log::error('WA Error: ' . $e->getMessage()); }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'resi' => $resi,
                'invoice' => $pesanan->nomor_invoice,
                'payment_url' => $paymentUrl
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("StoreSingle Mobile Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // API: STORE MULTI (Pesanan Massal Mobile)
    // ==========================================
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        $this->_normalizePaymentMethod($request);
        $key = $request->input('idempotency_key');

        if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
            return response()->json(['success' => false, 'message' => 'Pesanan massal sudah diproses sebelumnya.'], 422);
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

                // --- HITUNG VOLUME DAN BERAT AKTUAL (P x L x T) ---
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
            $user = User::find(Auth::id());

            // --- PEMBAYARAN ---
            if ($request->payment_method === 'CASH') {
                if ($user->id != 4) {
                    throw new Exception("Metode pembayaran Cash hanya tersedia untuk Admin.");
                }
                foreach ($createdOrders as $o) { $o->status = 'Menunggu Pickup'; $o->status_pesanan = 'Menunggu Pickup'; $o->save(); }
            }
            elseif ($request->payment_method === 'Potong Saldo') {
                if ($user->saldo < $grandTotalTagihan) throw new Exception("Saldo Anda tidak mencukupi.");
                $user->decrement('saldo', $grandTotalTagihan);
                foreach ($createdOrders as $o) { $o->status = 'Menunggu Pickup'; $o->status_pesanan = 'Menunggu Pickup'; $o->save(); }
            }
            elseif (in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                 foreach ($createdOrders as $o) { $o->status = 'Menunggu Pickup'; $o->status_pesanan = 'Menunggu Pickup'; $o->save(); }
            }
            else {
                $paymentGateway = 'tripay';
                if ($request->payment_method === 'DOKU_JOKUL') $paymentGateway = 'doku';

                if ($paymentGateway === 'doku') {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($masterOrder->nomor_invoice, $grandTotalTagihan);
                    if (empty($paymentUrl)) throw new Exception('Gagal generate URL DOKU.');
                    foreach ($createdOrders as $o) { $o->payment_url = $paymentUrl; $o->save(); }
                } else {
                    $orderItems = [];
                    foreach ($createdOrders as $o) {
                        $orderItems[] = ['sku' => 'SHIP-'.$o->nomor_invoice, 'name' => 'Ongkir Koli', 'price' => (int)$o->price, 'quantity' => 1];
                    }
                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $masterOrder, $grandTotalTagihan, $orderItems);
                    if (empty($tripayResponse['success'])) throw new Exception('Tripay Error: ' . ($tripayResponse['message'] ?? 'Unknown'));
                    $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                    foreach ($createdOrders as $o) { $o->payment_url = $paymentUrl; $o->save(); }
                }
            }

            // HIT API KIRIMINAJA JIKA LUNAS / COD / CASH
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
                            $order->resi = $resi; $order->status = 'Dikemas'; $order->status_pesanan = 'Dikemas'; $order->save();
                        }
                    } else {
                        $order->status = 'Gagal Kirim Resi'; $order->save();
                    }
                }
            }

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
                'payment_url' => $paymentUrl
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Store Multi Mobile Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // INNER HELPERS: COST, KIRIMINAJA & TRIPAY
    // ==========================================
    private function _getRealShippingCost($kirimaja, $senderData, $receiverData, $weight, $length, $width, $height, $courier, $service, $itemValue, $ansuransi)
    {
        if($weight < 1) $weight = 1;
        $useInsurance = ($ansuransi == 'iya') ? 1 : 0;

        $category = 'regular';
        if (strpos(strtolower($service), 'trucking') !== false || strpos(strtolower($service), 'cargo') !== false) {
            $category = 'trucking';
        }

        // --- HITUNG VOLUME DAN BERAT AKTUAL (P x L x T) UNTUK API CEK ONGKIR ---
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
        if (strpos(strtolower($data['service_code']), 'cargo') !== false) $serviceGroup = 'trucking';

        $schedules = $kirimaja->getSchedules();
        $pickupSchedule = $schedules['clock'] ?? 'now';
        $useInsuranceFlag = ($data['ansuransi'] == 'iya') ? 1 : 0;

        $payload = [
            'address' => $order->sender_address, 'phone' => $order->sender_phone, 'name' => $order->sender_name,
            'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
            'zipcode' => $senderData['kirimaja_data']['postal_code'],
            'platform_name' => 'tokosancaka.com', 'category' => $serviceGroup,
            'schedule' => $pickupSchedule,
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
        return $kirimaja->createExpressOrder($payload);
    }

    private function _createTripayTransactionInternal(array $data, Pesanan $order, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key'); $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode', 'sandbox');
        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tidak valid.'];

        $customerEmail = Auth::user()->email ?? 'admin+' . Str::random(5) . '@tokosancaka.com';

        $payload = [
            'method' => $data['payment_method'], 'merchant_ref' => $order->nomor_invoice, 'amount' => $total,
            'customer_name' => $data['receiver_name'], 'customer_email' => $customerEmail, 'customer_phone' => $data['receiver_phone'],
            'order_items' => $orderItems,
            'return_url' => 'https://tokosancaka.com',
            'expired_time' => time() + (1 * 60 * 60),
            'signature' => hash_hmac('sha256', $merchantCode . $order->nomor_invoice . $total, $privateKey),
        ];

        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(60)->withoutVerifying()->post($baseUrl, $payload);
            if (!$response->successful()) return ['success' => false, 'message' => 'Gagal menghubung server pembayaran.'];

            $responseData = $response->json();
            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan.'];
            }
            return $responseData;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Kesalahan internal pembayaran.'];
        }
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
}
