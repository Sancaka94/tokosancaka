<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\PesananAutokirim;
use App\Models\AutoKirim;
use App\Models\User; // 🔥 Wajib untuk cek Saldo
use App\Models\Api;  // 🔥 Wajib untuk kredensial Tripay & Autokirim
use App\Helpers\ShippingHelper;

class PesananAutokirimController extends Controller
{
    // ==========================================
    // AREA CUSTOMER: HALAMAN FORM
    // ==========================================
    public function createCustomer()
    {
        $kategoriBarang = [
            'Pakaian / Fashion', 'Elektronik & Gadget', 'Dokumen / Surat',
            'Makanan Kering / Herbal', 'Kosmetik & Kecantikan', 'Aksesoris & Sparepart', 'Lainnya'
        ];

        // 💡 Metode pembayaran online sekarang diambil otomatis dari Tripay via AJAX (getTripayChannels)
        return view('customer.pesanan_autokirim.create', compact('kategoriBarang'));
    }

    // ==========================================
    // API GET TRIPAY CHANNELS (UNTUK BLADE UI)
    // ==========================================
    public function getTripayChannels()
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $cacheKey = 'tripay_channels_list_' . $mode;

        $channels = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 24, function () use ($mode) {
            $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
            $baseUrl = $mode === 'production'
                ? 'https://tripay.co.id/api/merchant/payment-channel'
                : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

            if (empty($apiKey)) return [];

            try {
                $response = Http::withToken($apiKey)->get($baseUrl);
                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error('Gagal ambil channel Tripay: ' . $e->getMessage());
            }
            return [];
        });

        return response()->json(['success' => true, 'data' => $channels]);
    }

    // ==========================================
    // PENCARIAN & CEK ONGKIR (TETAP SAMA)
    // ==========================================
    public function searchAddressAjax(Request $request)
    {
        $keyword = $request->query('q');
        if (!$keyword || strlen($keyword) < 3) return response()->json([]);

        $data = AutoKirim::where('district_name', 'like', "%{$keyword}%")
            ->orWhere('regency_name', 'like', "%{$keyword}%")
            ->orWhere('zip', 'like', "%{$keyword}%")
            ->select('district_id', 'district_name', 'regency_name', 'province_name', 'zip')
            ->limit(100)->get();

        return response()->json($data);
    }

    public function cekOngkirAjax(Request $request)
    {
        $origin_id      = $request->origin_id;
        $destination_id = $request->destination_id;
        $qty            = $request->input('qty', 1);
        $isSenderPp     = $request->input('is_sender_pp', 1);

        $payload = [
            'origin_id'      => (int) $origin_id,
            'destination_id' => (int) $destination_id,
            'weight'         => (string) $request->berat_gram,
            'length'         => $request->panjang_cm ? (int) $request->panjang_cm : 1,
            'width'          => $request->lebar_cm ? (int) $request->lebar_cm : 1,
            'height'         => $request->tinggi_cm ? (int) $request->tinggi_cm : 1,
            'is_sender_pp'   => (int) $isSenderPp,
        ];

        try {
            $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
            $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
            $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

            $response = Http::timeout(15)->withToken($token)->post("{$baseUrl}/api/v2/check-price", $payload);
            $result = $response->json();

            if ($response->successful() && isset($result['rc']) && $result['rc'] === '00') {
                $flatOngkir = [];
                foreach ($result['data'] as $courier) {
                    if (empty($courier['service_detail'])) continue;
                    $parsedCourier = ShippingHelper::parseShippingMethod($courier['courier_code'] ?? $courier['courier_name']);

                    foreach ($courier['service_detail'] as $service) {
                        $flatOngkir[] = [
                            'kurir'          => $parsedCourier['courier_name'],
                            'logo_url'       => $parsedCourier['logo_url'],
                            'kode_kurir'     => $courier['courier_code'],
                            'layanan'        => $service['service_group'] . ' - ' . $service['service'],
                            'kode_layanan'   => $service['service_code'],
                            'harga_satuan'   => (int) $service['price'],
                            'harga'          => (int) $service['price'] * (int) $qty,
                            'estimasi'       => $service['duration'],
                            'etd'            => $service['etd'] ?? '-',
                            'is_pickup'      => $service['is_pickup'] ?? false,
                        ];
                    }
                }
                return response()->json(['success' => true, 'data' => $flatOngkir]);
            }
            return response()->json(['success' => false, 'message' => $result['rd'] ?? 'Layanan tidak tersedia.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Kendala jaringan logistik.']);
        }
    }

    // ==========================================
    // API 3: CREATE ORDER (INTEGRASI TRIPAY & SALDO)
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'service_code_terpilih' => 'required',
            'pengirim_nama'         => 'required|string|max:50',
            'pengirim_hp'           => 'required|string|min:9|max:15',
            'pengirim_district_id'  => 'required',
            'pengirim_alamat'       => 'required|string|min:15',
            'penerima_nama'         => 'required|string|max:50',
            'penerima_hp'           => 'required|string|min:9|max:15',
            'penerima_district_id'  => 'required',
            'penerima_alamat'       => 'required|string|min:15',
            'berat_gram'            => 'required|numeric|min:1',
            'qty'                   => 'required|numeric|min:1',
            'is_sender_pp'          => 'required|in:0,1',
            'metode_pembayaran'     => 'required|string',
        ]);

        $origin = AutoKirim::where('district_id', $request->pengirim_district_id)->first();
        $destination = AutoKirim::where('district_id', $request->penerima_district_id)->first();

        if (!$origin || !$destination) return redirect()->back()->withInput()->with('error', 'Wilayah tidak valid.');

        $localOrderId = (string) (date('ymdHis') . mt_rand(1000, 9999));
        $isInsurance  = $request->has('asuransi');
        $finalPrice   = $isInsurance && $request->nilai_barang > 0 ? (int) $request->nilai_barang : 10000;

        // Total Bayar (Ongkir dari Frontend)
        $totalTagihan = (int) $request->ongkir_terpilih;

        DB::beginTransaction();
        try {
            // 1. Simpan Pesanan Awal (Status: Menunggu Pembayaran)
            // ⚠️ PASTIKAN KOLOM 'qty' & 'is_sender_pp' DITAMBAHKAN DI TABEL pesanan_autokirim JIKA BELUM ADA
            $pesanan = PesananAutokirim::create([
                'user_id'           => auth()->id(),
                'order_id'          => $localOrderId,
                'pengirim_nama'     => $request->pengirim_nama,
                'pengirim_hp'       => $request->pengirim_hp,
                'pengirim_alamat'   => $request->pengirim_alamat,
                'pengirim_kodepos'  => $origin->zip,
                'penerima_nama'     => $request->penerima_nama,
                'penerima_hp'       => $request->penerima_hp,
                'penerima_alamat'   => $request->penerima_alamat,
                'penerima_kodepos'  => $destination->zip,
                'deskripsi_barang'  => $request->deskripsi_barang,
                'kategori_barang'   => $request->kategori_barang,
                'berat_gram'        => $request->berat_gram,
                'panjang_cm'        => $request->panjang_cm ?? 1,
                'lebar_cm'          => $request->lebar_cm ?? 1,
                'tinggi_cm'         => $request->tinggi_cm ?? 1,
                'asuransi'          => $isInsurance ? 1 : 0,
                'nilai_barang'      => $finalPrice,
                'kurir'             => $request->kurir_terpilih,
                'layanan'           => $request->layanan_terpilih,
                'ongkir'            => $totalTagihan,
                'metode_pembayaran' => $request->metode_pembayaran,

                // Tambahan data penting untuk callback Webhook Tripay nanti
                'qty'               => $request->qty,
                'is_sender_pp'      => $request->is_sender_pp,

                'status'            => 'Menunggu Pembayaran'
            ]);

            // ======================================================
            // 🔥 LOGIKA PEMBAYARAN 🔥
            // ======================================================
            if ($request->metode_pembayaran === 'saldo_wallet') {

                // --- 1. POTONG SALDO INSTAN ---
                $user = User::find(auth()->id());
                if ($user->saldo < $totalTagihan) {
                    throw new \Exception('Saldo Anda tidak mencukupi untuk memproses pesanan ini.');
                }
                $user->decrement('saldo', $totalTagihan);

                // --- 2. TEMBAK API AUTOKIRIM (KARENA SUDAH LUNAS) ---
                $apiResult = $this->_prosesApiAutokirim($pesanan);

                if ($apiResult['success']) {
                    $pesanan->update([
                        'awb_number' => $apiResult['awb'],
                        'status' => 'booking_created'
                    ]);
                    DB::commit();
                    return redirect()->route('customer.pesanan-autokirim.create')->with('success', "Pembayaran Lunas! Resi Terbit: {$apiResult['awb']}");
                } else {
                    // Jika API Logistik Gagal, Kembalikan Saldo Customer!
                    $user->increment('saldo', $totalTagihan);
                    throw new \Exception('Gagal Generate Resi dari Ekspedisi: ' . $apiResult['message']);
                }

            } else {

                // --- 3. PEMBAYARAN ONLINE (TRIPAY) ---
                $orderItems = [[
                    'sku' => 'ONGKIR',
                    'name' => 'Pengiriman ' . $pesanan->kurir,
                    'price' => $totalTagihan,
                    'quantity' => 1
                ]];

                $tripayResponse = $this->_createTripayTransactionInternal($pesanan, $totalTagihan, $orderItems);

                if ($tripayResponse['success']) {
                    $pesanan->update(['payment_url' => $tripayResponse['data']['checkout_url']]);
                    DB::commit();

                    // Redirect Customer ke Halaman Bayar Tripay
                    return redirect()->away($tripayResponse['data']['checkout_url']);
                } else {
                    throw new \Exception('Tripay Error: ' . $tripayResponse['message']);
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AUTOKIRIM STORE ERROR: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // HELPER: EKSEKUSI API AUTOKIRIM
    // ==========================================
    private function _prosesApiAutokirim(PesananAutokirim $pesanan)
    {
        $mode = Api::getValue('AUTOKIRIM_MODE', 'global', 'sandbox');
        $baseUrl = Api::getValue('AUTOKIRIM_BASE_URL', $mode, 'https://api-dev.autokirim.com');
        $token = Api::getValue('AUTOKIRIM_TOKEN', $mode, '');

        $origin = AutoKirim::where('zip', $pesanan->pengirim_kodepos)->first();
        $destination = AutoKirim::where('zip', $pesanan->penerima_kodepos)->first();

        try {
            // 1. INSERT PICKUP POINT
            $pickupPayload = [
                'name'              => (string) $pesanan->pengirim_nama,
                'phone'             => (string) $pesanan->pengirim_hp,
                'address'           => (string) $pesanan->pengirim_alamat,
                'email'             => auth()->user()->email ?? 'customer@mail.com',
                'longitude'         => "", 'latitude' => "",
                'district_id'       => (int) $origin->district_id,
                'is_member_deposit' => false
            ];

            $pickupResponse = Http::timeout(15)->withToken($token)->post("{$baseUrl}/api/pickup-point/insert", $pickupPayload);
            $pickupResult = $pickupResponse->json();

            if (!$pickupResponse->successful() || empty($pickupResult['data']['pickup_point_code'])) {
                return ['success' => false, 'message' => $pickupResult['rd'] ?? 'Gagal generate Pickup Point'];
            }
            $pickupPointCode = $pickupResult['data']['pickup_point_code'];

            // 2. CREATE ORDER
            // Ekstrak Service Code Asli dari layanan (misal: "Reguler - ninja_standard" -> ambil "ninja_standard")
            $serviceCodeRaw = explode(' - ', $pesanan->layanan);
            $pureServiceCode = end($serviceCodeRaw);

            $payloadOrder = [
                'service_code'      => $pureServiceCode,
                'reff_client_id'    => (string) $pesanan->order_id,
                'pickup_point_code' => $pickupPointCode,
                'origin_id'         => (int) $origin->district_id,
                'destination_id'    => (int) $destination->district_id,
                'weight'            => (string) $pesanan->berat_gram,
                'qty'               => (string) ($pesanan->qty ?? 1),
                'length'            => (int) $pesanan->panjang_cm,
                'width'             => (int) $pesanan->lebar_cm,
                'height'            => (int) $pesanan->tinggi_cm,
                'description'       => (string) $pesanan->deskripsi_barang,
                'remarks'           => (string) $pesanan->kategori_barang,
                'is_cod'            => false,
                'price'             => (int) $pesanan->nilai_barang,
                'cod_value'         => 0,
                'is_sender_pp'      => (int) ($pesanan->is_sender_pp ?? 1),
                'is_insurance'      => $pesanan->asuransi == 1,
                'from' => [
                    'name'    => (string) $pesanan->pengirim_nama,
                    'phone'   => (string) $pesanan->pengirim_hp,
                    'address' => (string) $pesanan->pengirim_alamat,
                ],
                'to' => [
                    'name'    => (string) $pesanan->penerima_nama,
                    'phone'   => (string) $pesanan->penerima_hp,
                    'address' => (string) $pesanan->penerima_alamat,
                ],
                'commodity'         => ""
            ];

            $orderResponse = Http::timeout(15)->withToken($token)->post("{$baseUrl}/api/order", $payloadOrder);
            $orderResult = $orderResponse->json();

            if ($orderResponse->successful() && isset($orderResult['rc']) && $orderResult['rc'] === '00') {
                return ['success' => true, 'awb' => $orderResult['data']['awb']];
            }

            return ['success' => false, 'message' => $orderResult['rd'] ?? 'Order Ditolak Ekspedisi (General Error)'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ==========================================
    // HELPER: CREATE TRIPAY INVOICE
    // ==========================================
    private function _createTripayTransactionInternal(PesananAutokirim $pesanan, int $total, array $orderItems): array
    {
        $mode = Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $apiKey = Api::getValue('TRIPAY_API_KEY', $mode);
        $privateKey = Api::getValue('TRIPAY_PRIVATE_KEY', $mode);
        $merchantCode = Api::getValue('TRIPAY_MERCHANT_CODE', $mode);

        if (empty($apiKey) || empty($privateKey)) return ['success' => false, 'message' => 'Konfigurasi Tripay Kosong.'];

        $user = auth()->user();
        $payload = [
            'method'         => $pesanan->metode_pembayaran,
            'merchant_ref'   => $pesanan->order_id,
            'amount'         => $total,
            'customer_name'  => $user->nama_lengkap ?? $pesanan->pengirim_nama,
            'customer_email' => $user->email ?? 'customer@tokosancaka.com',
            'customer_phone' => $user->no_hp ?? $pesanan->pengirim_hp,
            'order_items'    => $orderItems,
            'return_url'     => route('customer.pesanan-autokirim.create'),
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->order_id . $total, $privateKey),
        ];

        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';

        try {
            $response = Http::withToken($apiKey)->post($baseUrl, $payload);
            if ($response->successful() && $response->json()['success'] === true) {
                return $response->json();
            }
            return ['success' => false, 'message' => $response->json()['message'] ?? 'Tripay API Error'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Koneksi Tripay Gagal'];
        }
    }
}
