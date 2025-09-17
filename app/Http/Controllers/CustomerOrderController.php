<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Kontak;
use Illuminate\Support\Str;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    /**
     * Menampilkan form pemesanan baru dengan input alamat yang disederhanakan.
     */
    public function create()
    {
        return view('pesanan_customer.create');
    }

    /**
     * BARU: Endpoint API untuk fitur pencarian alamat secara real-time.
     */
    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);
        $searchQuery = $request->input('search');

        try {
            $results = $kirimaja->searchAddress($searchQuery);
            if (empty($results['status']) || empty($results['data'])) {
                return response()->json([]);
            }
            return response()->json($results['data']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data alamat dari ekspedisi.'], 500);
        }
    }

    /**
     * Menggunakan Nominatim untuk geocoding alamat (untuk layanan Instant).
     */
    public function geocode(string $address): ?array
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
        try {
            $response = Http::withHeaders(['User-Agent' => 'SancakaExpressApp/1.0 (support@tokosancaka.com)'])->get($url)->json();
            if (!empty($response[0])) {
                return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    /**
     * Mengambil dan memvalidasi data alamat dari request yang sudah diproses oleh frontend.
     */
    private function _getAddressData(Request $request, string $type): array
    {
        $prefix = $type;
        $village = $request->input("{$prefix}_village");
        $district = $request->input("{$prefix}_district");
        $regency = $request->input("{$prefix}_regency");
        $province = $request->input("{$prefix}_province");
        $lat = $request->input("{$prefix}_lat");
        $lng = $request->input("{$prefix}_lng");
        
        $kirimajaAddr = [
            'district_id' => $request->input("{$prefix}_district_id"),
            'subdistrict_id' => $request->input("{$prefix}_subdistrict_id"),
            'postal_code' => $request->input("{$prefix}_postal_code")
        ];
        
        if ((!$lat || !$lng) && $village) {
             $fullAddressForGeocode = implode(', ', array_filter([$village, $district, $regency, $province]));
            if ($geo = $this->geocode($fullAddressForGeocode)) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        return ['lat' => $lat, 'lng' => $lng, 'kirimaja_data' => $kirimajaAddr];
    }
    
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $validated = $request->validate([
                'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                'item_price' => 'required|numeric', 'weight' => 'required|numeric',
                'service_type' => 'required|string',
            ]);

            $senderData = $this->_getAddressData($request, 'sender');
            $receiverData = $this->_getAddressData($request, 'receiver');
            
            if (in_array($request->service_type, ['instant', 'sameday']) && (!$senderData['lat'] || !$receiverData['lat'])) {
                 return response()->json(['status' => false, 'message' => 'Koordinat alamat tidak ditemukan, tidak dapat menghitung ongkir instan/sameday.'], 422);
            }
    
            $itemValue = $request->item_price; 
            $options = [];
            
            $isMandatory = in_array((int) $request->item_type, [1, 3, 4, 8]) ? 1 : 0;
            
            if($isMandatory && $request->ansuransi == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Wajib ada ansuransi.'], 422);
            }

            if (in_array($request->service_type, ['instant', 'sameday'])) {
                $options = $kirimaja->getInstantPricing($senderData['lat'], $senderData['lng'], $request->sender_address, $receiverData['lat'], $receiverData['lng'], $request->receiver_address, $request->weight, $itemValue, 'motor');
            } else { 
                $category = $request->service_type === 'cargo' ? 'trucking' : 'regular';
                $options = $kirimaja->getExpressPricing($validated['sender_district_id'], $validated['sender_subdistrict_id'], $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], $request->weight, $request->length ?? 1, $request->width ?? 1, $request->height ?? 1, $itemValue, null, $category, $request->ansuransi == 'iya' ? 1 : 0);
            }
            
            return response()->json($options);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            $validatedData = $this->_validateOrderRequest($request);
            list($serviceGroup, $courier, $service, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 6, 0);
            
            $shipping_cost = (int) $shipping_cost;
            $ansuransi_fee = (int) $ansuransi_fee;
            $cod_fee = (int) $cod_fee;

            $base_total = (int)$validatedData['item_price'] + $shipping_cost;
            if ($validatedData['ansuransi'] == 'iya') {
                $base_total += $ansuransi_fee;
            }

            $total_paid = $base_total;
            $cod_value = 0;
            
            if ($validatedData['payment_method'] === 'COD') {
                $total_paid = $shipping_cost + $cod_fee;
                $cod_value = $total_paid; 
            } elseif ($validatedData['payment_method'] === 'CODBARANG') {
                $total_paid = $base_total + $cod_fee;
                $cod_value = $total_paid;
            }

            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);
            
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                $kiriminResponse = $this->_createKiriminAjaOrder($request, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if ($kiriminResponse['status'] !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { 
                $tripay_amount = $shipping_cost;
                if ($validatedData['ansuransi'] == 'iya') {
                    $tripay_amount += $ansuransi_fee;
                }

                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $ansuransi_fee);
                
                $response = $this->_createTripayTransaction($request, $pesanan, $tripay_amount, $orderItemsPayload);
                
                if (!$response['success']) {
                    throw new Exception('Gagal membuat transaksi pembayaran online.');
                }
                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            $pesanan->price = $total_paid;
            $pesanan->save();
            DB::commit();

            if (!empty($pesanan->payment_url)) return redirect()->away($pesanan->payment_url);
            return redirect()->route('pesanan.public.success')->with('order', $pesanan);
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }
    
    private function _validateOrderRequest(Request $request): array
    {
        return $request->validate([
            'sender_name' => 'required|string|max:255', 'sender_phone' => 'required|string|max:20', 'sender_address' => 'required|string',
            'sender_province' => 'required|string|max:100', 'sender_regency' => 'required|string|max:100', 'sender_district' => 'required|string|max:100',
            'sender_village' => 'required|string|max:100', 'sender_postal_code' => 'required|string|max:10', 'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20', 'receiver_address' => 'required|string', 'receiver_province' => 'required|string|max:100',
            'receiver_regency' => 'required|string|max:100', 'receiver_district' => 'required|string|max:100', 'receiver_village' => 'required|string|max:100',
            'receiver_postal_code' => 'required|string|max:10', 'item_description' => 'required|string|max:255', 'item_price' => 'required|numeric|min:1000',
            'weight' => 'required|numeric|min:1', 'service_type' => 'required|string|in:regular,express,sameday,instant,cargo', 'expedition' => 'required|string',
            'payment_method' => 'required|string', 'ansuransi' => 'required|string|in:iya,tidak', 'pengirim_id' => 'nullable|integer|exists:kontaks,id',
            'penerima_id' => 'nullable|integer|exists:kontaks,id', 'length' => 'nullable|numeric|min:0', 'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0', 'item_type' => 'required|integer', 'save_sender' => 'nullable', 'save_receiver' => 'nullable',
            'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
        ]);
    }
    
    private function _preparePesananData(array $validatedData, int $total, string $ip, string $userAgent): array
    {
        do { $nomorInvoice = 'SCK-' . strtoupper(Str::random(4)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());
        return array_merge(
            collect($validatedData)->only(['sender_name', 'sender_phone', 'sender_address', 'sender_province', 'sender_regency', 'sender_district', 'sender_village', 'sender_postal_code', 'receiver_name', 'receiver_phone', 'receiver_address', 'receiver_province', 'receiver_regency', 'receiver_district', 'receiver_village', 'receiver_postal_code', 'item_description', 'weight', 'service_type', 'expedition', 'payment_method', 'item_type'])->all(),
            [ 'nama_pembeli' => $validatedData['receiver_name'], 'telepon_pembeli' => $validatedData['receiver_phone'], 'alamat_pengiriman' => $validatedData['receiver_address'], 'total_harga_barang' => $validatedData['item_price'], 'length' => $validatedData['length'] ?? 1, 'width' => $validatedData['width'] ?? 1, 'height' => $validatedData['height'] ?? 1, 'status_pesanan' => 'Menunggu Pembayaran', 'tanggal_pesanan' => now(), 'status' => 'Menunggu Pembayaran', 'tujuan' => $validatedData['receiver_regency'], 'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null, 'kontak_penerima_id' => $validatedData['penerima_id'] ?? null, 'ip_address' => $ip, 'user_agent' => $userAgent, 'nomor_invoice' => $nomorInvoice, 'price' => $total, ]
        );
    }
    
    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee): array
    {
        $payload = [];
        $payload[] = ['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1];
        if ($ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

    private function _createKiriminAjaOrder(Request $request, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        list($serviceGroup, $courier, $service_type, $shipping_cost) = array_pad(explode('-', $request->expedition), 4, null);
        if (in_array($serviceGroup, ['instant', 'sameday'])) { 
            return []; // Placeholder
        } 
        else {
            $schedule = $kirimaja->getSchedules();
            $payload = [
                'address' => $request->sender_address, 'phone' => $request->sender_phone, 'name' => $request->sender_name,
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $schedule['clock'], 'platform_name' => 'TOKOSANCAKA.COM',
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 'item_name' => $request->item_description, 'package_type_id' => (int)$request->item_type,
                    'destination_name' => $request->receiver_name, 'destination_phone' => $request->receiver_phone, 'destination_address' => $request->receiver_address,
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => $request->weight, 'width' => $request->width ?? 1, 'height' => $request->height ?? 1, 'length' => $request->length ?? 1,
                    'item_value' => (int)$request->item_price, 'shipping_cost' => (int)$shipping_cost, 'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => ($request->ansuransi == 'iya') ? (int)$request->item_price : 0, 'cod' => $cod_value
                ]]
            ];
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _createTripayTransaction(Request $request, Pesanan $pesanan, int $total, array $orderItemsPayload): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode');
        $payload = [
            'method' => $request->payment_method, 'merchant_ref' => $pesanan->nomor_invoice, 'amount' => $total,
            'customer_name' => $request->receiver_name, 'customer_email' => 'customer@tokosancaka.com',
            'customer_phone' => $request->receiver_phone, 'order_items' => $orderItemsPayload,
            'expired_time' => time() + (24 * 60 * 60),
            // PERBAIKAN KRUSIAL: Menggunakan 'sha256' bukan 'sha26'
            'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        return Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload)->json();
    }

        /**
     * BARU: Endpoint API untuk pencarian kontak berdasarkan nama atau no_hp.
     */
    public function searchKontak(Request $request)
    {
        $request->validate([
            'term' => 'required|string|min:2'
        ]);

        $searchTerm = $request->input('term');

        $kontaks = Kontak::where('nama', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%")
                         ->limit(10) // Batasi hasil agar tidak terlalu banyak
                         ->get([
                             'id',
                             'nama',
                             'no_hp',
                             'alamat',
                             'province',
                             'regency',
                             'district',
                             'village',
                             'postal_code',
                             // Pastikan nama kolom di bawah ini sesuai dengan tabel 'kontaks' Anda
                             // Jika tidak ada, Anda bisa hapus atau sesuaikan.
                             // 'district_id',
                             // 'subdistrict_id'
                         ]);

        return response()->json($kontaks);
    }

    public function success()
    {
        $order = session('order');
        if (!$order) return redirect()->route('home');
        return view('pesanan_customer.success', ['order' => $order]);
    }
}

