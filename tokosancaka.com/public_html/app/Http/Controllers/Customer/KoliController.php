<?php

namespace App\Http\Controllers\Customer;

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
use App\Services\FonnteService;
use Exception;

class KoliController extends Controller
{
    public function create()
    {
        return view('customer.pesanan.create_multi');
    }
    
    protected function generateInvoiceNumber()
    {
        return 'SCK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
    }

    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'SancakaCustomer/1.0 (support@tokosancaka.com)'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $address, 
                    'format' => 'json', 
                    'limit' => 1, 
                    'countrycodes' => 'id'
                ]);
            
            if (!$response->successful() || empty($response[0]) || !isset($response[0]['lat']) || !isset($response[0]['lon'])) {
                Log::warning("Geocoding failed for address: " . $address);
                return null;
            }

            return [
                'lat' => (float) $response[0]['lat'], 
                'lng' => (float) $response[0]['lon']
            ];

        } catch (\Exception $e) {
            Log::error("Geocoding error: " . $e->getMessage()); 
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
                $request->input("{$type}_village"),
                $request->input("{$type}_district"),
                $request->input("{$type}_regency")
            ]);
            
            $simpleAddressQuery = implode(', ', $parts);

            if(!empty($simpleAddressQuery)) {
                $geo = $this->geocode($simpleAddressQuery);
                if ($geo) {
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                }
            }
        }

        $finalLat = (is_numeric($lat) && $lat != 0) ? (float) $lat : null;
        $finalLng = (is_numeric($lng) && $lng != 0) ? (float) $lng : null;

        return [
            'lat'            => $finalLat,
            'lng'            => $finalLng,
            'kirimaja_data' => $kirimajaAddr
        ];
    }

    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $request->validate([
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'weight' => 'required|numeric', 'volume' => 'required|numeric',
            ]);

            $itemPrice = (int) str_replace(['Rp', '.', ',', ' '], '', $request->item_price);
            
            // Konversi input 'iya'/'tidak' menjadi integer 1/0 untuk API
            $useInsurance = ($request->ansuransi == 'iya' || $request->ansuransi == 1 || $request->ansuransi == 'true') ? 1 : 0;

            $options = $kirimaja->getExpressPricing(
                $request->sender_district_id, $request->sender_subdistrict_id,
                $request->receiver_district_id, $request->receiver_subdistrict_id,
                (int) $request->weight, 
                (int) $request->volume, 1, 1, 
                $itemPrice, 
                null, 
                'regular', 
                $useInsurance // Kirim 1 atau 0
            );

            if (isset($options['status']) && $options['status'] === true && !empty($options['results'])) {
                 return response()->json($options);
            }
            return response()->json(['status' => false, 'message' => 'Layanan tidak tersedia.']);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        // 🔥 0. CEK IDEMPOTENCY KEY
        $key = $request->input('idempotency_key');
        if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
            return redirect()->route('customer.pesanan.index')
                ->with('warning', 'Pesanan Massal ini sudah diproses sebelumnya (Mencegah Dobel Input).');
        }
        
        DB::beginTransaction(); 
        try {
            $rawItemPrice = str_replace(['Rp', '.', ' ', ','], '', $request->input('item_price'));
            $request->merge(['item_price' => $rawItemPrice]);

            $request->validate([
                'sender_name' => 'required', 'sender_phone' => 'required',
                'receiver_name' => 'required', 'receiver_phone' => 'required',
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'payment_method' => 'required',
                'item_price' => 'required|numeric|min:100', 
                'packages' => 'required|array|min:1',
            ]);

            $this->_saveKontak($request, 'sender', 'Pengirim');
            $this->_saveKontak($request, 'receiver', 'Penerima');

            $packages = $request->input('packages');
            $totalPaket = count($packages);
            
            $hargaBarangPerPaket = floor($request->input('item_price') / $totalPaket);
            if ($hargaBarangPerPaket < 100) $hargaBarangPerPaket = 100;

            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            $createdOrders = [];
            $tempDataPerOrder = []; 
            $grandTotalTagihan = 0; 
            $totalOngkirAll = 0;

            foreach ($packages as $index => $pkg) {
                do { 
                    $baseInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(5)); 
                    $nomorInvoice = $baseInvoice . '-' . ($index + 1);
                } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

                $beratFisik = (int) $pkg['weight']; 
                $p = (int) ($pkg['length'] ?? 10);
                $l = (int) ($pkg['width'] ?? 10);
                $t = (int) ($pkg['height'] ?? 10);
                
                $targetCourier = $pkg['courier_code'];
                $targetService = $pkg['service_code'];

                $divisor = (strpos(strtolower($targetService), 'cargo') !== false || strpos(strtolower($targetService), 'trucking') !== false) ? 4000 : 6000;
                $volumeWeight = ($p * $l * $t) / $divisor * 1000; 
                $chargeableWeight = max($beratFisik, $volumeWeight);
                
                $finalBookingWeight = (int) ceil($chargeableWeight); 

                $realCostData = $this->_getRealShippingCost(
                    $kirimaja, 
                    $senderAddressData['kirimaja_data'], 
                    $receiverAddressData['kirimaja_data'],
                    $beratFisik,
                    $p, $l, $t, 
                    $targetCourier, $targetService, 
                    $hargaBarangPerPaket, 
                    $request->ansuransi
                );

                $ongkirFix = (int) $realCostData['cost'];
                $asuransiFix = ($request->ansuransi == 'iya') ? (int) $realCostData['insurance'] : 0;

                $codFeeFix = 0;
                $finalPriceDB = 0; $finalCodValueAPI = 0;  
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
                
                $pesanan->sender_name = $request->sender_name;
                $pesanan->sender_phone = $this->_sanitizePhone($request->sender_phone);
                $pesanan->sender_address = $request->sender_address;
                $pesanan->sender_district_id = $request->sender_district_id;
                $pesanan->sender_subdistrict_id = $request->sender_subdistrict_id;
                $pesanan->sender_province = $request->sender_province;
                $pesanan->sender_regency = $request->sender_regency;
                $pesanan->sender_district = $request->sender_district;
                $pesanan->sender_village = $request->sender_village;
                $pesanan->sender_postal_code = $request->sender_postal_code; 
                
                $pesanan->receiver_name = $request->receiver_name;
                $pesanan->receiver_phone = $this->_sanitizePhone($request->receiver_phone);
                $pesanan->receiver_address = $request->receiver_address;
                $pesanan->receiver_district_id = $request->receiver_district_id;
                $pesanan->receiver_subdistrict_id = $request->receiver_subdistrict_id;
                $pesanan->receiver_province = $request->receiver_province;
                $pesanan->receiver_regency = $request->receiver_regency;
                $pesanan->receiver_district = $request->receiver_district;
                $pesanan->receiver_village = $request->receiver_village;
                $pesanan->receiver_postal_code = $request->receiver_postal_code; 
                
                $pesanan->item_description = $request->item_description . " (Paket " . ($index+1) . ")";
                $pesanan->weight = $beratFisik; 
                $pesanan->length = $p; 
                $pesanan->width = $l; 
                $pesanan->height = $t;
                $pesanan->item_type = $request->item_type;
                $pesanan->item_price = $hargaBarangPerPaket; 

                $expStringDB = sprintf('%s-%s-%s-%d-%d-%d', 'mix', strtolower($targetCourier), strtoupper($targetService), $ongkirFix, $asuransiFix, $codFeeFix);
                $pesanan->expedition = $expStringDB;
                $pesanan->service_type = 'Multi'; 
                $pesanan->payment_method = $request->payment_method;
                $pesanan->ansuransi = $request->ansuransi;
                
                $pesanan->shipping_cost = $ongkirFix;
                $pesanan->insurance_cost = $asuransiFix;
                $pesanan->cod_fee = $codFeeFix;
                $pesanan->price = $finalPriceDB;

                $pesanan->status = 'Menunggu Pembayaran';
                $pesanan->status_pesanan = 'Menunggu Pembayaran';
                $pesanan->tanggal_pesanan = now();

                // 🔥 Simpan Kunci Pengaman hanya di paket pertama (Master Order)
                if ($index === 0) {
                    $pesanan->idempotency_key = $key;
                }

                $pesanan->save();
                
                $createdOrders[] = $pesanan;

                $tempDataPerOrder[$pesanan->id] = [
                    'cod_value_api' => $finalCodValueAPI,
                    'shipping_cost' => $ongkirFix,
                    'insurance_cost' => $asuransiFix,
                    'item_value_api' => $hargaBarangPerPaket, 
                    'courier_code' => $targetCourier, 
                    'service_code' => $targetService,
                    'booking_weight' => $finalBookingWeight 
                ];

                if (!in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                    $grandTotalTagihan += $finalPriceDB; 
                }
                $totalOngkirAll += $ongkirFix;
            }

            $masterOrder = $createdOrders[0]; 
            $paymentUrl = null;

            // LOGIKA PEMBAYARAN
            if ($request->payment_method === 'Potong Saldo') {
                $user = User::find(Auth::id());
                if ($user->saldo < $grandTotalTagihan) throw new Exception("Saldo tidak cukup.");
                $user->decrement('saldo', $grandTotalTagihan);
                foreach ($createdOrders as $o) { $o->status = 'Menunggu Pickup'; $o->status_pesanan = 'Menunggu Pickup'; $o->save(); }
            } elseif (in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                 foreach ($createdOrders as $o) { $o->status = 'Menunggu Pickup'; $o->status_pesanan = 'Menunggu Pickup'; $o->save(); }
            } else {
                // Tripay / Doku Logic here (sama seperti di storeSingle)
                $paymentGateway = 'tripay'; 
                if (strtoupper($request->payment_method) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                if ($paymentGateway === 'doku') {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($masterOrder->nomor_invoice, $grandTotalTagihan);
                    if (empty($paymentUrl)) throw new Exception('Gagal membuat pembayaran DOKU.');
                    foreach ($createdOrders as $o) { $o->payment_url = $paymentUrl; $o->save(); }
                } else {
                    $orderItems = [];
                    foreach ($createdOrders as $o) {
                        $orderItems[] = [
                            'sku' => 'SHIPPING-' . $o->nomor_invoice,
                            'name' => 'Ongkir: ' . $o->nomor_invoice,
                            'price' => (int)$o->price,
                            'quantity' => 1
                        ];
                    }
                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $masterOrder, $grandTotalTagihan, $orderItems);
                    if (empty($tripayResponse['success'])) throw new Exception('Tripay Error: ' . ($tripayResponse['message'] ?? 'Unknown'));
                    $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                    foreach ($createdOrders as $o) { $o->payment_url = $paymentUrl; $o->save(); }
                }
            }

            DB::commit(); 

            // BOOKING KE EKSPEDISI (Hanya jika COD/Saldo/Sudah Lunas)
            if (in_array($request->payment_method, ['COD', 'CODBARANG', 'Potong Saldo'])) {
                foreach ($createdOrders as $order) {
                    $temp = $tempDataPerOrder[$order->id];
                    
                    $apiPayload = [
                        'item_description' => $order->item_description,
                        'item_price' => $temp['item_value_api'],
                        'weight' => $temp['booking_weight'], 
                        'length' => (int) $order->length, 
                        'width' => (int) $order->width, 
                        'height' => (int) $order->height,
                        'courier_code' => $temp['courier_code'], 
                        'service_code' => $temp['service_code'],
                        'ansuransi' => $request->ansuransi
                    ];

                    $kiriminResponse = $this->_createKiriminAjaOrderLocal(
                        $apiPayload, $order, $kirimaja, 
                        $senderAddressData, $receiverAddressData,
                        $temp['cod_value_api'], 
                        $temp['shipping_cost'], 
                        $temp['insurance_cost']
                    );

                    if (($kiriminResponse['status'] ?? false) === true) {
                        $resi = $kiriminResponse['details']['awb'] ?? $kiriminResponse['awb'] ?? $kiriminResponse['order_id'] ?? null;
                        if($resi) {
                             $order->resi = $resi;
                             $order->save();
                        }
                    } else {
                        $order->status = 'Gagal Kirim Resi';
                        $order->save();
                        Log::error("Booking Fail {$order->nomor_invoice}: " . json_encode($kiriminResponse));
                    }
                }
            }

            try {
                $waTotalFee = collect($createdOrders)->sum('cod_fee');
                $waTotalIns = collect($createdOrders)->sum('insurance_cost');
                $waTotalPrice = collect($createdOrders)->sum('price');
                $this->_sendWhatsappNotification(
                    $masterOrder, ['payment_method' => $request->payment_method, 'item_price' => $request->input('item_price')],
                    $totalOngkirAll, $waTotalIns, $waTotalFee, $waTotalPrice, $request, $totalPaket
                );
            } catch (Exception $e) { Log::error('Notif Error: ' . $e->getMessage()); }

            if ($paymentUrl) {
                return redirect()->away($paymentUrl);
            }

            return redirect()->route('customer.pesanan.index')->with('success', count($createdOrders) . " Pesanan berhasil dibuat!");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    private function _getRealShippingCost($kirimaja, $senderData, $receiverData, $weight, $length, $width, $height, $courier, $service, $itemValue, $ansuransi) 
    {
        if($weight < 1) $weight = 1;
        
        // Konversi 'iya' ke 1, 'tidak' ke 0
        $useInsurance = ($ansuransi == 'iya') ? 1 : 0;
        
        $category = 'regular';
        if (strpos(strtolower($service), 'trucking') !== false || strpos(strtolower($service), 'cargo') !== false) {
            $category = 'trucking';
        }

        $options = $kirimaja->getExpressPricing(
            $senderData['district_id'], $senderData['subdistrict_id'],
            $receiverData['district_id'], $receiverData['subdistrict_id'],
            $weight, 
            $length, $width, $height, 
            $itemValue, null, $category, 
            $useInsurance // Pastikan ini dikirim sebagai 1 atau 0
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
        
        if ($foundCost == 0) throw new Exception("Ongkir berubah/tidak ditemukan utk kurir {$courier}.");
        return ['cost' => $foundCost, 'insurance' => $foundInsurance];
    }

    // --- FUNGSI UTAMA BOOKING KE API ---
    private function _createKiriminAjaOrderLocal($data, $order, $kirimaja, $senderData, $receiverData, $cod_value, $shipping_cost, $insurance_cost) {
        $serviceGroup = 'regular';
        if (strpos(strtolower($data['service_code']), 'cargo') !== false) $serviceGroup = 'trucking';
        
        $schedules = $kirimaja->getSchedules();
        $pickupSchedule = $schedules['clock'] ?? 'now'; 

        // [FIX] Konversi Asuransi ke 1 (Iya) atau 0 (Tidak) untuk flag API
        $useInsuranceFlag = ($data['ansuransi'] == 'iya') ? 1 : 0;

        $payload = [
            'address' => $order->sender_address, 'phone' => $order->sender_phone, 'name' => $order->sender_name,
            'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
            'zipcode' => $senderData['kirimaja_data']['postal_code'], 
            'platform_name' => 'tokosancaka.com', 'category' => $serviceGroup,
            'schedule' => $pickupSchedule,
            'packages' => [[
                'order_id' => $order->nomor_invoice, 
                'item_name' => $data['item_description'],
                'package_type_id' => (int)request('item_type'),
                'destination_name' => $order->receiver_name, 'destination_phone' => $order->receiver_phone,
                'destination_address' => $order->receiver_address,
                'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 
                'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                'weight' => (int)$data['weight'], 
                'width' => (int)$data['width'], 'height' => (int)$data['height'], 'length' => (int)$data['length'],
                'item_value' => (int)$data['item_price'], 
                
                // [PENTING] Field 'insurance' (flag) wajib dikirim 1/0 agar API mengaktifkan asuransi
                'insurance' => $useInsuranceFlag, 
                
                // Field 'insurance_amount' adalah NILAI nominal asuransi (biaya)
                'insurance_amount' => ($useInsuranceFlag === 1) ? (int)$insurance_cost : 0, 
                
                'cod' => (int)$cod_value,
                'service' => $data['courier_code'], 'service_type' => $data['service_code'],
                'shipping_cost' => (int)$shipping_cost
            ]]
        ];
        return $kirimaja->createExpressOrder($payload);
    }

    // ... (Method Private lainnya seperti _sanitizePhone, _saveKontak, _sendWhatsappNotification, _createTripayTransactionInternal sama seperti sebelumnya) ...
    private function _sanitizePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) return Str::startsWith(substr($phone, 2), '0') ? '0'.substr($phone, 3) : '0'.substr($phone, 2);
        if (!Str::startsWith($phone, '0') && Str::startsWith($phone, '8')) return '0'.$phone;
        return $phone;
    }
    
    private function _saveKontak($request, $prefix, $tipe) { 
        if ($request->has("save_{$prefix}")) {
            Kontak::updateOrCreate(
                ['no_hp' => $this->_sanitizePhone($request->input("{$prefix}_phone"))],
                [
                    'nama' => $request->input("{$prefix}_name"), 'alamat' => $request->input("{$prefix}_address"),
                    'district_id' => $request->input("{$prefix}_district_id"), 'subdistrict_id' => $request->input("{$prefix}_subdistrict_id"),
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

    private function _createTripayTransactionInternal(array $data, Pesanan $order, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key'); $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode', 'sandbox');
        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tidak valid.'];

        $customerEmail = Auth::user()->email ?? null;
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com'; 
        }

        $payload = [
            'method' => $data['payment_method'], 'merchant_ref' => $order->nomor_invoice, 'amount' => $total,
            'customer_name' => $data['receiver_name'], 
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'], 
            'order_items' => $orderItems,
            'return_url' => route('customer.pesanan.index'), 
            'expired_time' => time() + (1 * 60 * 60), 
            'signature' => hash_hmac('sha256', $merchantCode . $order->nomor_invoice . $total, $privateKey),
        ];
        
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)->withoutVerifying()->post($baseUrl, $payload);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gagal menghubur server pembayaran.'];
            }
            $responseData = $response->json();
            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan.'];
            }
            return $responseData;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Kesalahan internal pembayaran.'];
        }
    }

    public function storeSingle(Request $request, KiriminAjaService $kirimaja)
    {
        // 🔥 0. CEK IDEMPOTENCY KEY (AJAX)
        $key = $request->input('idempotency_key');
        if ($key && Pesanan::where('idempotency_key', $key)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini sudah dibuat. Harap refresh halaman.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. CLEANING & VALIDASI
            // Kita tidak pakai shipping_cost dari request untuk hitungan final, tapi tetap diambil untuk fallback/log
            $itemPrice = (int) str_replace(['Rp', '.', ',', ' '], '', $request->item_price);
            
            $request->validate([
                'sender_name' => 'required', 'sender_phone' => 'required',
                'receiver_name' => 'required', 'receiver_phone' => 'required',
                'sender_district_id' => 'required', 'receiver_district_id' => 'required',
                'courier_code' => 'required', 'service_code' => 'required',
                'weight' => 'required|numeric', 
                'payment_method' => 'required',
                'item_description' => 'required'
            ]);

            $senderAddressData = $this->_getAddressData($request, 'sender');
            $receiverAddressData = $this->_getAddressData($request, 'receiver');

            // 2. [FIX] DAPATKAN HARGA REAL DARI API DULU (SEBELUM HITUNG COD)
            $realCostData = $this->_getRealShippingCost(
                $kirimaja, 
                $senderAddressData['kirimaja_data'], 
                $receiverAddressData['kirimaja_data'],
                (int)$request->weight,
                (int)$request->length, (int)$request->width, (int)$request->height, 
                $request->courier_code, $request->service_code, 
                $itemPrice, 
                $request->ansuransi
            );

            $shippingCost = (int) $realCostData['cost'];
            $insuranceCost = ($request->ansuransi == 'iya') ? (int)$realCostData['insurance'] : 0;

            // 3. KALKULASI BIAYA & COD (Disamakan dengan Logic Multi Koli)
            $codFee = 0;
            $totalPrice = 0;
            $codValueApi = 0; // Nilai COD yang akan ditembak ke API

            if ($request->payment_method == 'COD' || $request->payment_method == 'CODBARANG') {
                // Base Total = Ongkir + Asuransi + (Barang jika COD Barang)
                $baseTotal = $shippingCost + $insuranceCost;
                if ($request->payment_method == 'CODBARANG') {
                    $baseTotal += $itemPrice;
                }

                // Hitung Fee: 3% dari Base Total, Minimal Rp 2.500
                $rawFee = $baseTotal * 0.03;
                $codFeeBeforePPN = max(2500, $rawFee);
                
                // PPN 11% (Hanya dari Fee)
                $ppnFee = $codFeeBeforePPN * 0.11;
                
                // Total Mentah
                $grandTotalMentah = $baseTotal + $codFeeBeforePPN + $ppnFee;

                // Pembulatan ke atas kelipatan 500 (Request Owner)
                $codValueApi = (int) (ceil($grandTotalMentah / 500) * 500);
                
                $totalPrice = $codValueApi;
                $codFee = $codFeeBeforePPN + $ppnFee; // Simpan fee murni untuk laporan
            } else {
                // Non-COD (Transfer/Saldo)
                $totalPrice = $shippingCost + $insuranceCost;
                $codValueApi = 0;
            }

            // 4. SIMPAN KE DATABASE
            $pesanan = new Pesanan();
            $pesanan->nomor_invoice = $this->generateInvoiceNumber();
            $pesanan->customer_id = Auth::id();
            $pesanan->id_pengguna_pembeli = Auth::id();

            $pesanan->sender_name = $request->sender_name;
            $pesanan->sender_phone = $this->_sanitizePhone($request->sender_phone);
            $pesanan->sender_address = $request->sender_address;
            $pesanan->sender_district_id = $request->sender_district_id;
            $pesanan->sender_subdistrict_id = $request->sender_subdistrict_id;
            $pesanan->sender_province     = $request->sender_province;
            $pesanan->sender_regency      = $request->sender_regency;
            $pesanan->sender_district     = $request->sender_district;
            $pesanan->sender_village      = $request->sender_village;
            $pesanan->sender_postal_code = $request->sender_postal_code;
            $pesanan->sender_lat = $senderAddressData['lat'] ?? 0;
            $pesanan->sender_lng = $senderAddressData['lng'] ?? 0;
            
            $pesanan->receiver_name = $request->receiver_name;
            $pesanan->receiver_phone = $this->_sanitizePhone($request->receiver_phone);
            $pesanan->receiver_address = $request->receiver_address;
            $pesanan->receiver_district_id = $request->receiver_district_id;
            $pesanan->receiver_subdistrict_id = $request->receiver_subdistrict_id;
            $pesanan->receiver_province     = $request->receiver_province;
            $pesanan->receiver_regency      = $request->receiver_regency;
            $pesanan->receiver_district     = $request->receiver_district;
            $pesanan->receiver_village      = $request->receiver_village;
            $pesanan->receiver_postal_code = $request->receiver_postal_code;
            $pesanan->receiver_lat = $receiverAddressData['lat'] ?? 0;
            $pesanan->receiver_lng = $receiverAddressData['lng'] ?? 0;

            $pesanan->item_description = $request->item_description;
            $pesanan->weight = $request->weight;
            $pesanan->length = $request->length;
            $pesanan->width = $request->width;
            $pesanan->height = $request->height;
            $pesanan->item_price = $itemPrice;
            
            $pesanan->payment_method = $request->payment_method;
            $pesanan->ansuransi = $request->ansuransi;
            
            // Simpan data biaya yang sudah dihitung fix
            $pesanan->shipping_cost = $shippingCost;
            $pesanan->insurance_cost = $insuranceCost;
            $pesanan->cod_fee = $codFee;
            $pesanan->price = $totalPrice;

            $expString = sprintf('mix-%s-%s-%d-%d-%d', 
                strtolower($request->courier_code), 
                strtoupper($request->service_code), 
                $shippingCost, 
                $insuranceCost, 
                $codFee
            );
            $pesanan->expedition = $expString;
            $pesanan->service_type = 'Single-Multi'; 
            $pesanan->status = 'Menunggu Pembayaran'; 
            $pesanan->status_pesanan = 'Menunggu Pembayaran';
            $pesanan->tanggal_pesanan = now();

            // 🔥 Simpan Kunci Pengaman
            $pesanan->idempotency_key = $key;

            $pesanan->save(); 

            $paymentUrl = null;

            // 5. PROSES PEMBAYARAN
            if ($request->payment_method === 'Potong Saldo') {
                $user = User::find(Auth::id());
                if ($user->saldo < $pesanan->price) throw new Exception("Saldo tidak cukup.");
                $user->decrement('saldo', $pesanan->price);
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->save();
            } 
            elseif (in_array($request->payment_method, ['COD', 'CODBARANG'])) {
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->save();
            } 
            else {
                // --- PAYMENT GATEWAY (TRIPAY / DOKU) ---
                $paymentGateway = 'tripay';
                if (strtoupper($request->payment_method) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                if ($paymentGateway === 'doku') {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($pesanan->nomor_invoice, $pesanan->price);
                    if (empty($paymentUrl)) throw new Exception('Gagal generate DOKU URL.');
                    $pesanan->payment_url = $paymentUrl;
                    $pesanan->save();
                } else {
                    $orderItems = [[
                        'sku' => 'SHIPPING',
                        'name' => 'Ongkir',
                        'price' => (int)$pesanan->price,
                        'quantity' => 1
                    ]];
                    $tripayResponse = $this->_createTripayTransactionInternal($request->all(), $pesanan, $pesanan->price, $orderItems);
                    if (empty($tripayResponse['success'])) {
                        throw new Exception('Tripay Error: ' . ($tripayResponse['message'] ?? 'Unknown'));
                    }
                    $paymentUrl = $tripayResponse['data']['checkout_url'];
                    $pesanan->payment_url = $paymentUrl;
                    $pesanan->save();
                }
            }

            // 6. BOOKING KE EKSPEDISI (Jika Lunas / COD)
            $resi = "DIPROSES";
            if (in_array($request->payment_method, ['COD', 'CODBARANG', 'Potong Saldo'])) {
                
                $apiPayload = [
                    'item_description' => $pesanan->item_description,
                    'item_price' => $itemPrice,
                    'weight' => (int) $pesanan->weight,
                    'length' => (int) $pesanan->length,
                    'width' => (int) $pesanan->width,
                    'height' => (int) $pesanan->height,
                    'courier_code' => $request->courier_code,
                    'service_code' => $request->service_code,
                    'ansuransi' => $request->ansuransi
                ];

                // Panggil Helper Booking dengan Nilai COD yang SUDAH TERHITUNG BENAR ($codValueApi)
                $kiriminResponse = $this->_createKiriminAjaOrderLocal(
                    $apiPayload, 
                    $pesanan, 
                    $kirimaja, 
                    $senderAddressData, 
                    $receiverAddressData,
                    $codValueApi, // Ini nilai COD yang dikirim ke API (sudah include asuransi+fee)
                    $shippingCost, 
                    $insuranceCost 
                );

                if (($kiriminResponse['status'] ?? false) === true) {
                    $resi = $kiriminResponse['details']['awb'] ?? $kiriminResponse['awb'] ?? null;
                    if($resi) {
                        $pesanan->resi = $resi;
                        $pesanan->status = 'Dikemas'; 
                        $pesanan->status_pesanan = 'Dikemas';
                    } else {
                        $pesanan->status = 'Menunggu Resi'; 
                        $pesanan->status_pesanan = 'Menunggu Resi';
                    }
                    $pesanan->save();
                } else {
                    $errMsg = $kiriminResponse['text'] ?? 'Unknown error from Expedition API';
                    throw new Exception("Ekspedisi Error: " . $errMsg);
                }
            }

            DB::commit(); 

            return response()->json([
                'success' => true,
                'message' => 'Paket berhasil disimpan.',
                'resi' => $resi,
                'invoice' => $pesanan->nomor_invoice,
                'payment_url' => $paymentUrl 
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("StoreSingle Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}