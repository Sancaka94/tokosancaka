<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Kontak;
use Illuminate\Support\Str;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Ditambahkan untuk fallback email
use App\Services\FonnteService; // <-- FONTE SUDAH ADA
// use App\Events\AdminNotificationEvent; // <-- [PERBAIKAN] Dinonaktifkan, diganti NotifikasiUmum
use App\Services\DanaSignatureService;
use App\Http\Controllers\Api\PayPalGatewayController;

// 👇 [PERBAIKAN] Import yang benar untuk notifikasi
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;
use Exception;

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
     * Endpoint API untuk fitur pencarian alamat secara real-time.
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
            Log::channel('daily')->error('KiriminAja Address Search Failed (Public): ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat dari ekspedisi.'], 500);
        }
    }

    /**
     * Endpoint API untuk pencarian kontak berdasarkan nama atau no_hp.
     * Disesuaikan untuk mencari di tabel Pengguna DAN tabel Kontak.
     */
    public function searchKontak(Request $request)
    {
        $searchTerm = $request->input('term', $request->input('search'));
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
             return response()->json([], 422);
        }

        // 1. Cari dari tabel Pengguna (Karena "Amal" dkk ada di sini)
        $users = \App\Models\User::where('nama_lengkap', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('no_wa', 'LIKE', "%{$searchTerm}%")
                         ->limit(5)
                         ->get([
                             'id_pengguna as id', 'nama_lengkap as nama', 'no_wa as no_hp',
                             'address_detail as alamat', 'province', 'regency', 'district', 'village', 'postal_code'
                         ]);

        // 2. Cari dari tabel Kontak (Buku Alamat)
        $kontaks = Kontak::where('nama', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%")
                         ->limit(5)
                         ->get([
                             'id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code'
                         ]);

        // 3. Gabungkan hasil dari kedua tabel
        $results = $users->merge($kontaks);

        return response()->json($results);
    }

    /**
     * Menggunakan Nominatim untuk geocoding alamat (Sinkronisasi dari Admin)
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(10)->withHeaders(['User-Agent' => 'SancakaPublic/1.0 (support@tokosancaka.com)'])
                ->get("https://nominatim.openstreetmap.org/search", ['q' => $address, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'id']);

            if (!$response->successful() || empty($response[0]) || !isset($response[0]['lat']) || !isset($response[0]['lon'])) {
                Log::warning("Geocoding failed for address: " . $address, ['response_status' => $response->status(), 'response_body' => $response->body()]);
                return null;
            }
            return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Geocoding connection failed: " . $e->getMessage(), ['address' => $address]); return null;
        } catch (\Exception $e) {
            Log::error("Geocoding general error: " . $e->getMessage(), ['address' => $address]); return null;
        }
    }

    /**
     * Mengambil dan memvalidasi data alamat (Sinkronisasi dari Admin)
     */
    private function _getAddressData(Request $request, string $type): array
{
    $lat = $request->input("{$type}_lat");
    $lng = $request->input("{$type}_lng");

    $kirimajaAddr = [
        'district_id'    => $request->input("{$type}_district_id"),
        'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
        'postal_code'    => $request->input("{$type}_postal_code"),
    ];

    if (!is_numeric($lat) || !is_numeric($lng) || $lat == 0 || $lng == 0) {

        // PERBAIKAN — Format sederhana (Kelurahan + Kecamatan + Kabupaten)
        $simpleAddressQuery = implode(', ', array_filter([
            $request->input("{$type}_village"),
            $request->input("{$type}_district"),
            $request->input("{$type}_regency")
        ]));

        Log::info("Geocode fallback (Public) triggered for {$type}. Query: {$simpleAddressQuery}");

        $geo = $this->geocode($simpleAddressQuery);

        if ($geo) {
            $lat = $geo['lat'];
            $lng = $geo['lng'];
            Log::info("Geocode fallback (Public) SUCCESS for {$type}. Lat: {$lat}, Lng: {$lng}");
        } else {
            Log::warning("Geocode fallback (Public) FAILED.", [
                'query' => $simpleAddressQuery
            ]);
        }
    }

    $finalLat = (is_numeric($lat) && $lat != 0) ? (float) $lat : null;
    $finalLng = (is_numeric($lng) && $lng != 0) ? (float) $lng : null;

    return [
        'lat'           => $finalLat,
        'lng'           => $finalLng,
        'kirimaja_data' => $kirimajaAddr
    ];
}


/**
 * Cek ongkir (Sinkronisasi dari Admin/Customer)
 * [FUNGSI DIPERBAIKI: PANGGIL SEMUA LAYANAN]
 */
public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
{
    try {
        // Validasi
        $validated = $request->validate([
            'sender_district_id' => 'required|integer',
            'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer',
            'receiver_subdistrict_id' => 'required|integer',
            'item_price' => 'required|numeric|min:1',
            'weight' => 'required|numeric|min:1',
            'service_type' => 'required|string|in:regular,express,sameday,instant,cargo',
            'item_type' => 'required|integer',
            'ansuransi' => 'required|string|in:iya,tidak',
            'length' => 'nullable|numeric|min:1',
            'width' => 'nullable|numeric|min:1',
            'height' => 'nullable|numeric|min:1',
            'sender_lat' => 'nullable|numeric',
            'sender_lng' => 'nullable|numeric',
            'receiver_lat' => 'nullable|numeric',
            'receiver_lng' => 'nullable|numeric',
            'sender_address' => 'required_if:service_type,instant,sameday|nullable|string',
            'receiver_address' => 'required_if:service_type,instant,sameday|nullable|string',

            'sender_village' => 'nullable|string',
            'sender_district' => 'nullable|string',
            'sender_regency' => 'nullable|string',
            'sender_province' => 'nullable|string',

            'receiver_village' => 'nullable|string',
            'receiver_district' => 'nullable|string',
            'receiver_regency' => 'nullable|string',
            'receiver_province' => 'nullable|string',
        ]);

        $senderLat = $validated['sender_lat'] ?? null;
        $senderLng = $validated['sender_lng'] ?? null;
        $receiverLat = $validated['receiver_lat'] ?? null;
        $receiverLng = $validated['receiver_lng'] ?? null;

        $useInsurance = ($validated['ansuransi'] == 'iya') ? 1 : 0;
        $itemValue = $validated['item_price'];

        $instantOptions = ['status' => false, 'result' => []];
        $expressOptions = ['status' => false, 'results' => []];

        // --- 1. PANGGIL LAYANAN INSTANT/SAMEDAY ---
        if (in_array($validated['service_type'], ['instant', 'sameday'])) {

            if (empty($senderLat) || empty($senderLng)) {
                $senderFullAddress = implode(', ', array_filter([
                    $validated['sender_village'] ?? null,
                    $validated['sender_district'] ?? null
                ]));

                Log::info('Mencoba geocode fallback (SIMPLE) untuk Pengirim: ' . $senderFullAddress);
                $geo = $this->geocode($senderFullAddress);
                if ($geo) {
                    $senderLat = $geo['lat'];
                    $senderLng = $geo['lng'];
                }
            }

            if (empty($receiverLat) || empty($receiverLng)) {
                $receiverFullAddress = implode(', ', array_filter([
                    $validated['receiver_village'] ?? null,
                    $validated['receiver_district'] ?? null
                ]));

                Log::info('Mencoba geocode fallback (SIMPLE) untuk Penerima: ' . $receiverFullAddress);
                $geo = $this->geocode($receiverFullAddress);
                if ($geo) {
                    $receiverLat = $geo['lat'];
                    $receiverLng = $geo['lng'];
                }
            }

            if (empty($senderLat) || empty($senderLng) || empty($receiverLat) || empty($receiverLng)) {
                Log::warning('Geocode tetap gagal, melewati instant.', [
                    'slat' => $senderLat,
                    'slng' => $senderLng,
                    'rlat' => $receiverLat,
                    'rlng' => $receiverLng
                ]);
            } else {
                $instantOptions = $kirimaja->getInstantPricing(
                    $senderLat,
                    $senderLng,
                    $validated['sender_address'],
                    $receiverLat,
                    $receiverLng,
                    $validated['receiver_address'],
                    $validated['weight'],
                    $itemValue,
                    'motor'
                );

                // PERBAIKAN 40KM
                if (!is_array($instantOptions)) {
                    Log::warning('getInstantPricing return non-array (null?). Asumsi > 40km.', [
                        'response' => $instantOptions
                    ]);

                    return response()->json([
                        'status' => false,
                        'message' => 'Layanan Instant/Sameday tidak tersedia. Jarak pengiriman kemungkinan melebihi 40KM.'
                    ], 404);
                }
            }
        }

        // --- 2. PANGGIL EXPRESS/REGULAR/CARGO ---
        if (in_array($validated['service_type'], ['regular', 'express', 'cargo'])) {
            $category = $validated['service_type'] === 'cargo' ? 'trucking' : 'regular';

            $length = $request->input('length', 1);
            $width = $request->input('width', 1);
            $height = $request->input('height', 1);

            $expressOptions = $kirimaja->getExpressPricing(
                $validated['sender_district_id'],
                $validated['sender_subdistrict_id'],
                $validated['receiver_district_id'],
                $validated['receiver_subdistrict_id'],
                $validated['weight'],
                $length,
                $width,
                $height,
                $itemValue,
                null,
                $category,
                $useInsurance
            );
        }

        // --- 3. GABUNGKAN HASIL ---
        $finalResults = [
            'status' => true,
            'text' => 'OK',
            'result' => ($instantOptions['status'] == true) ? $instantOptions['result'] : [],
            'results' => ($expressOptions['status'] == true) ? $expressOptions['results'] : [],
            'final_weight' => $expressOptions['final_weight'] ?? $validated['weight']
        ];

        if (empty($finalResults['result']) && empty($finalResults['results'])) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada layanan pengiriman yang tersedia untuk rute atau parameter ini.'
            ], 404);
        }

        return response()->json($finalResults);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Input tidak valid.',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {
        Log::error('Cek Ongkir General Error (Public):', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
        ], 500);
    }
}


    /**
     * =========================================================================
     * FUNGSI STORE BARU (DISINKRONKAN DENGAN ADMIN/CUSTOMER)
     * =========================================================================
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            // 1. Validasi
            $validatedData = $this->_validateOrderRequest($request);

            // 2. Simpan Kontak
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone_original'] = $request->input('sender_phone');
            $validatedData['receiver_phone_original'] = $request->input('receiver_phone');
            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone_original']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone_original']);

            // 3. Kalkulasi Biaya (Fungsi baru)
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; // Untuk Tripay
            $cod_value = $calculation['cod_value']; // Untuk COD/CODBARANG
            $shipping_cost = $calculation['shipping_cost'];
            $insurance_cost = $calculation['ansuransi_fee'];
            $cod_fee = $calculation['cod_fee'];

            // 4. Siapkan Data Pesanan (Fungsi baru)
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());

            // Tambahkan biaya terpisah (PENTING UNTUK CALLBACK)
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;

            // Karena ini publik, id_pengguna_pembeli & customer_id = null
            $pesananData['id_pengguna_pembeli'] = null;
            $pesananData['customer_id'] = null;

            $pesanan = Pesanan::create($pesananData);

            $paymentUrl = null;

          // 5. Proses Pembayaran
            $paymentMethodRaw = strtoupper($validatedData['payment_method']);

            if ($paymentMethodRaw === 'POTONG SALDO') {
                if (!\Illuminate\Support\Facades\Auth::check()) throw new Exception('Silakan login untuk menggunakan Saldo.');

                // Gunakan Pessimistic Locking agar saldo tidak minus jika di-klik 2x
                $user = \App\Models\User::where('id_pengguna', \Illuminate\Support\Facades\Auth::id())->lockForUpdate()->first();
                $totalTagihan = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;

                if ($user->saldo < $totalTagihan) {
                    throw new Exception('Saldo Sancaka Anda tidak mencukupi.');
                }

                // Potong saldo
                $user->saldo -= $totalTagihan;
                $user->save();

                // Ubah payment method menjadi 'cash' sementar agar KiriminAja memprosesnya seperti COD/Cash
                $validatedData['payment_method'] = 'cash';
                $pesanan->payment_method = 'Potong Saldo';
            }
            elseif (in_array($paymentMethodRaw, ['COD', 'CODBARANG', 'CASH'])) {
                // Biarkan lolos, diproses KiriminAja
            }
            else {
                $customerData = [
                    'name'  => $validatedData['receiver_name'],
                    'email' => $request->input('customer_email', 'guest' . time() . '@tokosancaka.com'),
                    'phone' => $validatedData['receiver_phone']
                ];

                $paymentGateway = 'tripay'; // Default
                if ($paymentMethodRaw === 'DOKU_JOKUL') $paymentGateway = 'doku';
                elseif ($paymentMethodRaw === 'MIDTRANS') $paymentGateway = 'midtrans';
                elseif ($paymentMethodRaw === 'PAYPAL') $paymentGateway = 'paypal';
                elseif ($paymentMethodRaw === 'DANA_BINDING') $paymentGateway = 'dana_binding';
                elseif (in_array($paymentMethodRaw, ['DANA', 'NETWORK_PAY_PG_DANA'])) $paymentGateway = 'dana_direct';

                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $insurance_cost, $validatedData['ansuransi']);

                // EKSEKUSI GATEWAY
                if ($paymentGateway === 'dana_binding') {
                    if (!\Illuminate\Support\Facades\Auth::check()) throw new Exception('Silakan login untuk menggunakan DANA Auto-Debit.');
                    $user = \Illuminate\Support\Facades\Auth::user();
                    if (empty($user->dana_access_token)) throw new Exception('Akses token DANA tidak ditemukan. Silakan hubungkan ulang akun DANA Anda.');

                    Log::info('Memulai DANA BINDING (Auto Debit) untuk ' . $pesanan->nomor_invoice);
                    // Gunakan fungsi createPaymentDanaBindingPublic yang menarik dana langsung dari token
                    $paymentUrl = $this->createPaymentDanaBindingPublic($pesanan, $total_paid_ongkir, $user);
                    if (empty($paymentUrl)) throw new Exception('Gagal melakukan penarikan DANA Auto-Debit.');
                    $pesanan->payment_url = $paymentUrl;
                }
                elseif ($paymentGateway === 'doku') {
                    $dokuService = new DokuJokulService();
                    $paymentUrl = $dokuService->createPayment($pesanan->nomor_invoice, $total_paid_ongkir, $customerData, $orderItemsPayload);
                    if (empty($paymentUrl)) throw new Exception('Gagal membuat transaksi pembayaran DOKU.');
                    $pesanan->payment_url = $paymentUrl;
                }
                elseif ($paymentGateway === 'midtrans') {
                    $paymentUrl = $this->createPaymentMidtransSnapPublic($pesanan, $total_paid_ongkir, $customerData);
                    if (empty($paymentUrl)) throw new Exception('Gagal membuat transaksi pembayaran Midtrans.');
                    $pesanan->payment_url = $paymentUrl;
                }
                elseif ($paymentGateway === 'dana_direct') {
                    $paymentUrl = $this->createPaymentDanaPublic($pesanan, $total_paid_ongkir, $customerData);
                    if (empty($paymentUrl)) throw new Exception('Gagal membuat transaksi pembayaran DANA.');
                    $pesanan->payment_url = $paymentUrl;
                }
                elseif ($paymentGateway === 'paypal') {
                    $paymentUrl = $this->createPaymentPayPalPublic($pesanan, $total_paid_ongkir);
                    if (empty($paymentUrl)) throw new Exception('Gagal membuat transaksi pembayaran PayPal.');
                    $pesanan->payment_url = $paymentUrl;
                }
                else {
                    $tripayResponse = $this->_createTripayTransactionInternal($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);
                    if (empty($tripayResponse['success'])) {
                        throw new Exception('Gagal transaksi Tripay. ' . ($tripayResponse['message'] ?? ''));
                    }
                    $pesanan->payment_url = $tripayResponse['data']['checkout_url'] ?? null;
                }
            }
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'cash'])) {
                // Pembayaran Online via Tripay
                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $insurance_cost, $validatedData['ansuransi']);

                // Panggil _createTripayTransactionInternal (Fungsi baru)
                $tripayResponse = $this->_createTripayTransactionInternal($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                if (empty($tripayResponse['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($tripayResponse['message'] ?? 'Tidak ada pesan.'));
                }
                $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                $pesanan->payment_url = $paymentUrl;
            }

            // 6. Proses KiriminAja HANYA jika COD/Cash
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'cash'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');

                // Panggil _createKiriminAjaOrder (Fungsi baru)
                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.');
                    throw new Exception($errorMessage);
                }
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            }

            // 7. Simpan finalisasi data
            $pesanan->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $pesanan->save();
            DB::commit();

            // 8. ✅ KIRIM NOTIFIKASI FONNTE (SETELAH COMMIT)
            $notification_total = $pesanan->price;
            $this->_sendWhatsappNotification(
                $pesanan, $validatedData, $shipping_cost,
                (int) $pesanan->insurance_cost, (int) $pesanan->cod_fee,
                $notification_total, $request // Kirim request asli
            );

             // ==========================================================
            // 👇 [PERBAIKAN] Mengganti AdminNotificationEvent
            // ==========================================================
            try {
                $admins = User::where('role', 'admin')->get(); // Cari admin
                if ($admins->isNotEmpty()) {
                    $adminUrl = $pesanan->resi
                                ? route('admin.pesanan.show', $pesanan->resi)
                                : route('admin.pesanan.index');

                    $dataNotifAdmin = [
                        'tipe'        => 'Pesanan',
                        'judul'       => 'Pesanan Publik Baru!',
                        'pesan_utama' => ($validatedData['sender_name'] ?? 'Pelanggan publik') . ' membuat pesanan ' . $pesanan->nomor_invoice,
                        'url'         => $adminUrl,
                        'icon'        => 'fas fa-globe-asia', // Ikon berbeda untuk pesanan publik
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal broadcast NotifikasiUmum (Public): ' . $e->getMessage());
            }
            // ==========================================================
            // 👆 AKHIR PERBAIKAN
            // ==========================================================

            // 9. Arahkan pengguna
            if ($paymentUrl) {
                return redirect()->away($paymentUrl);
            }
            // Redirect ke halaman sukses publik
            return redirect()->route('pesanan.public.success')->with('order', $pesanan);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validasi gagal saat membuat pesanan (Public):', $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order Creation Failed (Public): '. $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * =========================================================================
     * FUNGSI VALIDASI BARU (SINKRONISASI)
     * =========================================================================
     */
    private function _validateOrderRequest(Request $request): array
    {
        // Validasi disamakan, 'customer_id' dihapus
        return $request->validate([
            'sender_name' => 'required|string|max:255', 'sender_phone' => 'required|string|min:9|max:20', 'sender_address' => 'required|string',
            'sender_province' => 'required|string|max:100', 'sender_regency' => 'required|string|max:100', 'sender_district' => 'required|string|max:100',
            'sender_village' => 'required|string|max:100', 'sender_postal_code' => 'required|string|max:10', 'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|min:9|max:20', 'receiver_address' => 'required|string', 'receiver_province' => 'required|string|max:100',
            'receiver_regency' => 'required|string|max:100', 'receiver_district' => 'required|string|max:100', 'receiver_village' => 'required|string|max:100',
            'receiver_postal_code' => 'required|string|max:10', 'item_description' => 'required|string|max:255', 'item_price' => 'required|numeric|min:1000',
            'weight' => 'required|numeric|min:1', 'service_type' => 'required|string|in:regular,express,sameday,instant,cargo', 'expedition' => 'required|string',
            'payment_method' => 'required|string', 'ansuransi' => 'required|string|in:iya,tidak',
            'pengirim_id' => 'nullable|integer',
            'penerima_id' => 'nullable|integer',
            'length' => 'nullable|numeric|min:0', 'width' => 'nullable|numeric|min:0', 'height' => 'nullable|numeric|min:0',
            'save_sender' => 'nullable', 'save_receiver' => 'nullable',
            'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
            'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
            'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
            'sender_note' => 'nullable|string|max:255', 'receiver_note' => 'nullable|string|max:255',
            'item_type' => 'required|integer',
            'customer_email' => 'nullable|email', // Tetap ada untuk jaga-jaga
        ]);
    }

    /**
     * =========================================================================
     * FUNGSI PERSIAPAN DATA BARU (SINKRONISASI)
     * =========================================================================
     */
    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        // Prefix SCK- akan diroute ke AdminPesananController oleh CheckoutController
        do { $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        $fieldsToSave = array_keys($this->_validateOrderRequest(request()));
        $fieldsToExclude = ['save_sender', 'save_receiver', 'customer_email', 'sender_phone_original', 'receiver_phone_original'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();

        // Data ini harus SAMA PERSIS dengan Customer/Admin Controller
        return array_merge($pesananCoreData, [
            'nomor_invoice' => $nomorInvoice,
            'status' => 'Menunggu Pembayaran',
            'status_pesanan' => 'Menunggu Pembayaran',
            'tanggal_pesanan' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null,
            'kontak_penerima_id' => $validatedData['penerima_id'] ?? null,
            'total_harga_barang' => $validatedData['item_price'],
            'nama_pembeli' => $validatedData['receiver_name'], // <-- Kolom lama
            'telepon_pembeli' => $validatedData['receiver_phone'], // <-- Kolom lama
            'alamat_pengiriman' => $validatedData['receiver_address'], // <-- Kolom lama
            'tujuan' => $validatedData['receiver_regency'], // <-- Kolom lama
            // 'price' akan diisi di 'store'
        ]);
    }

    /**
     * =========================================================================
     * FUNGSI KALKULASI BARU (SINKRONISASI)
     * =========================================================================
     */
    private function _calculateTotalPaid(array $validatedData): array
    {
        $parts = explode('-', $validatedData['expedition']); $count = count($parts);
        $cod_fee = 0; $ansuransi_fee = 0; $shipping_cost = 0;
        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('Format expedition tidak dikenal (Public)', ['exp' => $validatedData['expedition']]); }

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = $validatedData['ansuransi'] == 'iya';

        // Untuk Tripay (hanya ongkir + asuransi)
        $total_paid_ongkir = $shipping_cost;
        if ($use_insurance) {
            $total_paid_ongkir += $ansuransi_fee;
        }

        // Untuk COD / CODBARANG (Gunakan logika yang sama dengan CustomerController)
        $cod_value = 0;
        if ($validatedData['payment_method'] === 'CODBARANG') {
            $cod_value = $item_price + $shipping_cost + $cod_fee;
            if ($use_insurance) $cod_value += $ansuransi_fee;
        } elseif ($validatedData['payment_method'] === 'COD') {
            // Logika COD Ongkir dari CustomerController LAMA
            $cod_value = $shipping_cost + $cod_fee;
            if ($use_insurance) $cod_value += $ansuransi_fee;
        }
        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }

    /**
     * FUNGSI UNTUK MEMBUAT ORDER DI KIRIMIN AJA
     */
    private function _createKiriminAjaOrder(
        array $data, Pesanan $order, KiriminAjaService $kirimaja,
        array $senderData, array $receiverData, int $cod_value,
        int $shipping_cost, int $insurance_cost
    ): array
    {
        $expeditionParts = explode('-', $data['expedition'] ?? '');
        $serviceGroup = $expeditionParts[0] ?? null;
        $courier = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;

        if (empty($data['sender_address']) || empty($data['sender_phone']) || empty($data['sender_name']) ||
            empty($data['receiver_name']) || empty($data['receiver_phone']) || empty($data['receiver_address']) ||
            empty($data['item_description']) || !isset($data['item_price']) || !isset($data['weight']) ||
            !isset($data['item_type']) || empty($courier) || empty($service_type)) {
                Log::error('_createKiriminAjaOrder (Customer): Missing required data.', ['invoice' => $order->nomor_invoice]);
                return ['status' => false, 'text' => 'Data pesanan tidak lengkap untuk dikirim ke ekspedisi.'];
        }

         // ============================================================
        // LOGIKA FINAL: FEE (Min 2.500) + PPN 11% + PEMBULATAN 500
        // ============================================================

        $apiItemPrice = (float) $data['item_price'];
        $finalInsuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$insurance_cost : 0;
        $finalCodValue = $cod_value;

        // JIKA METODE 'COD' (COD Ongkir):
        if (isset($data['payment_method']) && $data['payment_method'] === 'COD') {

            // 1. Tentukan Asuransi & Harga Barang untuk API
            if ($data['ansuransi'] == 'iya') {
                $apiItemPrice = (float) $data['item_price'];
                $finalInsuranceAmount = (int) $insurance_cost;
            } else {
                $apiItemPrice = 10000;
                $finalInsuranceAmount = 0;
            }

            // 2. Hitung Total Dasar (Ongkir + Asuransi)
            $totalBasic = (int)$shipping_cost + (int)$finalInsuranceAmount;

            // 3. Hitung COD Fee (3% dari Total Dasar, Minimal 2.500)
            // Contoh: 3% dari 72.000 = 2.160 -> Dipaksa jadi 2.500
            $calculatedFee = $totalBasic * 0.03;
            $codFeeValue = max(2500, $calculatedFee);

            // 4. Hitung PPN 11% HANYA DARI FEE COD
            $ppnFee = $codFeeValue * 0.11;

            // 5. Jumlahkan Semua (Total Mentah)
            $grandTotalMentah = $totalBasic + $codFeeValue + $ppnFee;

            // 6. LOGIKA PEMBULATAN (REQUEST BAPAK)
            // 1-499 -> 500 | 501-999 -> 1000
            $finalCodValue = (int) (ceil($grandTotalMentah / 500) * 500);

            // Update harga di database
            $order->price = $finalCodValue;
            $order->save();
        }
        // ============================================================
        if (in_array($serviceGroup, ['instant', 'sameday'])) {
            if (empty($senderData['lat']) || empty($senderData['lng']) || empty($receiverData['lat']) || empty($receiverData['lng'])) {
                return ['status' => false, 'text' => 'Koordinat alamat tidak valid untuk pengiriman instan/sameday.'];
            }

            $payload = [
                'service' => $courier, 'service_type' => $service_type, 'vehicle' => 'motor',
                'order_prefix' => $order->nomor_invoice,
                'packages' => [[
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                    'destination_lat' => $receiverData['lat'], 'destination_long' => $receiverData['lng'],
                    'destination_address' => $data['receiver_address'],'destination_address_note' => $data['receiver_note'] ?? '-',
                    'origin_name' => $data['sender_name'], 'origin_phone' => $data['sender_phone'],
                    'origin_lat' => $senderData['lat'], 'origin_long' => $senderData['lng'],
                    'origin_address' => $data['sender_address'], 'origin_address_note' => $data['sender_note'] ?? '-',
                    'shipping_price' => (int)$shipping_cost,
                    'item' => [
                        'name' => $data['item_description'], 'description' => 'Pesanan ' . $order->nomor_invoice,
                        'price' => (int)$data['item_price'], 'weight' => (int)$data['weight'],
                    ]
                ]]
            ];
            Log::info('KiriminAja Create Instant Order Payload (Customer):', $payload);
            return $kirimaja->createInstantOrder($payload);

        } else {
            $scheduleResponse = $kirimaja->getSchedules();
            $scheduleClock = $scheduleResponse['clock'] ?? null;
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

            if (empty($senderData['kirimaja_data']['district_id']) || empty($senderData['kirimaja_data']['subdistrict_id']) ||
                empty($receiverData['kirimaja_data']['district_id']) || empty($receiverData['kirimaja_data']['subdistrict_id']) ||
                empty($senderData['kirimaja_data']['postal_code']) || empty($receiverData['kirimaja_data']['postal_code'])) {
                    Log::error('_createKiriminAjaOrder (Customer): Missing KiriminAja address IDs.', ['invoice' => $order->nomor_invoice]);
                    return ['status' => false, 'text' => 'ID alamat KiriminAja tidak lengkap.'];
            }

            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $scheduleClock,
                'platform_name' => 'tokosancaka.com', 'category' => $category,
                'latitude' => $senderData['lat'], 'longitude' => $senderData['lng'],
                'packages' => [[
                    'order_id' => $order->nomor_invoice, 'item_name' => $data['item_description'],
                    'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'],
                    'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => (int) ceil($finalWeight),
                    'width' => $widthInput, 'height' => $heightInput, 'length' => $lengthInput,

                    // --- DATA FINAL KE API ---
                    'item_value' => (int)$apiItemPrice,
                    'insurance_amount' => (int)$finalInsuranceAmount,
                    'cod' => (int)$finalCodValue,
                    // -------------------------

                    'service' => $courier, 'service_type' => $service_type,
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Express/Cargo Order Payload (Customer):', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _createTripayTransactionInternal(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        // 1. Ambil Mode dari Database
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // 2. Siapkan wadah variabel
        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        // 3. Isi variabel berdasarkan MODE
        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING (Public Mode: ' . $mode . ')');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap di Database.'];
        }

        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tagihan tidak valid.'];

        $customerEmail = $data['customer_email'] ?? 'customer@tokosancaka.com';
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer' . Str::random(5) . '@tokosancaka.com';
        }

        $finalAmount = (int) $total;
        if ($finalAmount < 10000) {
            Log::info("Total transaksi Rp {$finalAmount} di bawah minimum. Menaikkan menjadi Rp 10.000 agar Tripay memproses.");
            $finalAmount = 10000;
        }

        // 4. Validasi Hitungan Total (Safety Net)
        $calculatedTotalItems = 0;
        foreach ($orderItems as $item) {
            $calculatedTotalItems += ($item['price'] * $item['quantity']);
        }

        // Jika ada selisih (misal karena kita menaikkan harga jadi 10rb),
        // update item agar sesuai dengan $finalAmount
        if ($calculatedTotalItems !== $finalAmount) {
            $orderItems = [[
                'sku'      => 'INV-' . $pesanan->nomor_invoice,
                'name'     => 'Pembayaran Invoice #' . $pesanan->nomor_invoice,
                'price'    => $finalAmount,
                'quantity' => 1
            ]];
        }

        // 5. Buat Signature menggunakan $finalAmount
        $signature = hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $finalAmount, $privateKey);

        // 6. Siapkan Payload
        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $pesanan->nomor_invoice,
            'amount'         => $finalAmount, // Pakai nilai yang sudah di-floor 10rb
            'customer_name'  => $data['receiver_name'],
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'],
            'order_items'    => $orderItems,
            'return_url'     => route('pesanan.public.success'),
            'expired_time'   => time() + (24 * 60 * 60), // Expired diset 24 Jam agar aman
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . (int)$total, $privateKey),
        ];

        Log::info('Tripay Create Transaction Payload (Internal Public):', $payload);

        try {
            // Setup HTTP Client
            $http = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(60);

            // Matikan verifikasi SSL jika bukan production
            if ($mode !== 'production') {
                $http->withoutVerifying();
            }

            $response = $http->post($baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gagal menghubungi Tripay (Public)', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . ').'];
            }

            $responseData = $response->json();

            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                Log::error('Tripay mengembalikan respon gagal (Public)', ['response' => $responseData]);
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
            }

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Error saat membuat transaksi Tripay (Public)', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Terjadi kesalahan internal saat proses pembayaran.'];
        }
    }


    /**
     * =========================================================================
     * FUNGSI HELPER SINKRONISASI LAINNYA
     * =========================================================================
     */

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
{
    if (!empty($data["save_{$prefix}"])) {

        // 1. Validasi dasar
        $phoneKey = "{$prefix}_phone";

        if (!isset($data[$phoneKey])) {
            Log::warning("Gagal simpan kontak (Public): Nomor HP kosong.", [
                'prefix' => $prefix
            ]);
            return;
        }

        $sanitizedPhone = $this->_sanitizePhoneNumber($data[$phoneKey]);
        $name    = $data["{$prefix}_name"] ?? null;
        $address = $data["{$prefix}_address"] ?? null;

        if (empty($sanitizedPhone) || empty($name) || empty($address)) {
            Log::warning("Gagal simpan kontak (Public): Data (Nama/HP/Alamat) tidak lengkap.", [
                'prefix' => $prefix
            ]);
            return;
        }

        // 2. Cek kontak lama
        $existingContact = Kontak::where('no_hp', $sanitizedPhone)->first();

        // 3. Tentukan tipe baru
        $newTipe = $tipe;
        if ($existingContact) {
            if ($existingContact->tipe === 'Keduanya') {
                $newTipe = 'Keduanya';
            } elseif ($existingContact->tipe !== $tipe) {
                $newTipe = 'Keduanya';
            }
        }

        // 4. Simpan / Update
        Kontak::updateOrCreate(
            ['no_hp' => $sanitizedPhone],
            [
                'nama'        => $name,
                'no_hp'       => $sanitizedPhone,
                'alamat'      => $address,
                'province'    => $data["{$prefix}_province"] ?? null,
                'regency'     => $data["{$prefix}_regency"] ?? null,
                'district'    => $data["{$prefix}_district"] ?? null,
                'village'     => $data["{$prefix}_village"] ?? null,
                'postal_code' => $data["{$prefix}_postal_code"] ?? null,
                'tipe'        => $newTipe
            ]
        );
    }
}


    private function _sanitizePhoneNumber(string $phone): string
    {
        // Versi sinkronisasi dari AdminController
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

    /**
     * =========================================================================
     * FUNGSI BARU: _sendWhatsappNotification (Disalin dari CustomerController)
     * =========================================================================
     */
    private function _sendWhatsappNotification(
        Pesanan $pesanan, array $validatedData, int $shipping_cost,
        int $ansuransi_fee, int $cod_fee, int $total_paid,
        Request $request
    ) {
        // Ambil nomor display (belum disanitasi) dari request
        $displaySenderPhone = $request->input('sender_phone') ?? $validatedData['sender_phone_original'] ?? $pesanan->sender_phone;
        $displayReceiverPhone = $request->input('receiver_phone') ?? $validatedData['receiver_phone_original'] ?? $pesanan->receiver_phone;

        // Detail Paket
        $detailPaket = "*Detail Paket:*\n";
        $detailPaket .= "Deskripsi: " . ($pesanan->item_description ?? '-') . "\n";
        $detailPaket .= "Berat: " . ($pesanan->weight ?? 0) . " Gram\n";
        if ($pesanan->length && $pesanan->width && $pesanan->height) {
            $detailPaket .= "Dimensi: {$pesanan->length}x{$pesanan->width}x{$pesanan->height} cm\n";
        }
        $expeditionParts = explode('-', $pesanan->expedition ?? '');
        $exp_vendor = $expeditionParts[1] ?? '';
        $exp_service_type = $expeditionParts[2] ?? '';
        $service_display = trim(ucwords(strtolower(str_replace('_', ' ', $exp_vendor))) . ' ' . ucwords(strtolower(str_replace('_', ' ', $exp_service_type))));
        $detailPaket .= "Ekspedisi: " . ($service_display ?: '-') . "\n";
        $detailPaket .= "Layanan: " . ucwords($pesanan->service_type ?? '-');

        if ($pesanan->resi) {
            $detailPaket .= "\nResi: *" . $pesanan->resi . "*";
        } else {
            $detailPaket .= "\nResi: Menunggu Resi";
        }

        // Rincian Biaya
        $rincianBiaya = "*Rincian Biaya:*\n- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.');
        $itemPrice = $validatedData['item_price'] ?? $pesanan->item_price ?? 0;
        $use_insurance = $ansuransi_fee > 0;
        $is_cod_payment = in_array($pesanan->payment_method, ['COD', 'CODBARANG']);

        if ($use_insurance || $is_cod_payment || !$is_cod_payment) {
             if ($itemPrice > 0) {
                 $rincianBiaya .= "\n- Nilai Barang: Rp " . number_format($itemPrice, 0, ',', '.');
             }
        }
        if ($use_insurance) {
            $rincianBiaya .= "\n- Asuransi: Rp " . number_format($ansuransi_fee, 0, ',', '.');
        }
        if ($cod_fee > 0) {
            $rincianBiaya .= "\n- Biaya COD: Rp " . number_format($cod_fee, 0, ',', '.');
        }

        // Status Bayar
        $statusBayar = "⏳ Menunggu Pembayaran"; // Default
        if (in_array($pesanan->payment_method, ['COD', 'CODBARANG'])) {
            $statusBayar = "⏳ Bayar di Tempat (COD)";
        } elseif ($pesanan->payment_method === 'cash') { // Ditambahkan 'cash'
            $statusBayar = "✅ Lunas via Tunai";
        } elseif (in_array($pesanan->status, ['Menunggu Pickup', 'Diproses', 'Terkirim', 'Selesai', 'Pembayaran Lunas (Gagal Auto-Resi)', 'Pembayaran Lunas (Error Kirim API)'])) {
            $statusBayar = "✅ Lunas";
        } elseif (in_array($pesanan->status, ['Gagal Bayar', 'Kadaluarsa'])) {
            $statusBayar = "❌ Pembayaran Gagal/Kadaluarsa";
        }

        // Template Pesan
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
*Total Bayar: Rp {TOTAL_BAYAR}*
Status Pembayaran: {STATUS_BAYAR}
----------------------------------------

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek status pesanan/resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={LINK_RESI}

*Manajemen Sancaka*
TEXT;

        // Proses Template
        $linkResi = $pesanan->resi ?? $pesanan->nomor_invoice;
        $message = str_replace(
            [
                '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                '{DETAIL_PAKET}', '{RINCIAN_BIAYA}', '{TOTAL_BAYAR}', '{STATUS_BAYAR}', '{LINK_RESI}'
            ],
            [
                $pesanan->nomor_invoice,
                $pesanan->sender_name, $displaySenderPhone,
                $pesanan->receiver_name, $displayReceiverPhone,
                $detailPaket, $rincianBiaya,
                number_format($total_paid, 0, ',', '.'),
                $statusBayar, $linkResi
            ],
            $messageTemplate
        );

        // Sanitasi nomor untuk Fonnte
        $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->sender_phone));
        $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($pesanan->receiver_phone));

        // Kirim Pesan
        try {
            if ($senderWa) \App\Services\FonnteService::sendMessage($senderWa, $message);
            if ($receiverWa) \App\Services\FonnteService::sendMessage($receiverWa, $message);
            Log::info("Notifikasi WA Terkirim (Public) untuk Invoice: " . $pesanan->nomor_invoice);
        } catch (Exception $e) {
            Log::error('Fonnte Service (Public) sendMessage failed: ' . $e->getMessage(), ['invoice' => $pesanan->nomor_invoice]);
        }
    }


    public function success()
    {
        $order = session('order');
        if (!$order) {
            // Jika tidak ada session, redirect ke form create
            return redirect()->route('pesanan.public.create')->with('info', 'Silakan buat pesanan baru.');
        }
        // Pastikan $order adalah model, bukan array
        if (is_array($order)) {
            $order = Pesanan::find($order['id_pesanan']); // Sesuaikan dengan primary key
            if (!$order) {
                 return redirect()->route('pesanan.public.create')->with('info', 'Pesanan tidak ditemukan.');
            }
        }
        return view('pesanan_customer.success', ['order' => $order]);
    }

    public function search(Request $request)
    {
        try {
            $term = $request->get('term');
            if (empty($term)) return response()->json([]);

            $kontak = \App\Models\Kontak::where('nama', 'like', "%{$term}%")
                ->limit(10)
                ->get(['id', 'nama']);
            return response()->json($kontak);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * =========================================================================
     * FUNGSI PROSESOR CALLBACK (PENTING)
     * =========================================================================
     * Fungsi ini HARUS DIBUAT dan DIPANGGIL oleh CheckoutController
     * jika Anda ingin prefix invoice yang BERBEDA (misal CUSTO-)
     *
     * NAMUN, karena controller ini menggunakan prefix 'SCK-',
     * CheckoutController Anda sudah benar MENGARAHKANNYA ke AdminPesananController.
     *
     * Fungsi di bawah ini saya sertakan sebagai referensi jika Anda memutuskan
     * untuk mengubah prefix invoice dari controller ini.
     */
    public static function processCallback($merchantRef, $status, $callbackData)
    {
        Log::info('Processing Pesanan Callback (dipanggil untuk CUSTO-)...', ['ref' => $merchantRef, 'status' => $status]);
        $kirimaja = app(KiriminAjaService::class); // Dapatkan service

        $pesanan = Pesanan::where('nomor_invoice', $merchantRef)->lockForUpdate()->first();

        if (!$pesanan) {
            Log::error('Tripay Callback (CUSTO-): Pesanan Not found.', ['merchant_ref' => $merchantRef]);
            return;
        }

        if ($pesanan->status !== 'Menunggu Pembayaran') {
            Log::info('Tripay Callback (CUSTO-): Already processed.', ['invoice' => $merchantRef, 'current_status' => $pesanan->status]);
            return;
        }

        if ($status === 'PAID') {
            Log::info('Tripay Callback (CUSTO-): PAID. Preparing KiriminAja call...', ['invoice' => $merchantRef]);
            $pesanan->status = 'paid'; // Status internal
            $pesanan->status_pesanan = 'paid';
            $pesanan->save();

            try {
                // Buat instance baru untuk akses method private
                $instance = new self();
                $validatedData = $pesanan->toArray(); // Ambil data dari model

                // Siapkan data alamat (sudah ada di $pesanan berkat sinkronisasi)
                $senderAddressData = [
                    'lat' => $pesanan->sender_lat, 'lng' => $pesanan->sender_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->sender_district_id, 'subdistrict_id' => $pesanan->sender_subdistrict_id, 'postal_code' => $pesanan->sender_postal_code]
                ];
                $receiverAddressData = [
                    'lat' => $pesanan->receiver_lat, 'lng' => $pesanan->receiver_lng,
                    'kirimaja_data' => ['district_id' => $pesanan->receiver_district_id, 'subdistrict_id' => $pesanan->receiver_subdistrict_id, 'postal_code' => $pesanan->receiver_postal_code]
                ];

                $cod_value = 0; // Pasti 0
                $shipping_cost = (int) $pesanan->shipping_cost;
                $insurance_cost = (int) $pesanan->insurance_cost;

                // Panggil method private _createKiriminAjaOrder
                $kiriminResponse = $instance->_createKiriminAjaOrder(
                    $validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    Log::critical('Tripay Callback (CUSTO-): KiriminAja Order FAILED!', ['invoice' => $merchantRef, 'response' => $kiriminResponse]);
                    $pesanan->status = 'Pembayaran Lunas (Gagal Auto-Resi)';
                    $pesanan->status_pesanan = 'Pembayaran Lunas (Gagal Auto-Resi)';
                } else {
                    $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                    $pesanan->status = 'Menunggu Pickup';
                    $pesanan->status_pesanan = 'Menunggu Pickup';
                    Log::info('Tripay Callback (CUSTO-): KiriminAja Order SUCCESS.', ['invoice' => $merchantRef, 'resi' => $pesanan->resi]);

                    // TODO: Kirim notifikasi WA sukses?
                }
                $pesanan->save();

            } catch (Exception $e) {
                Log::error("Tripay Callback (CUSTO-): Exception during KiriminAja process.", [ 'ref' => $merchantRef, 'error' => $e->getMessage()]);
                $pesanan->status = 'Pembayaran Lunas (Error Kirim API)';
                $pesanan->status_pesanan = 'Pembayaran Lunas (Error Kirim API)';
                $pesanan->save();
            }

        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
            Log::info('Tripay Callback (CUSTO-): Payment FAILED/EXPIRED.', ['invoice' => $merchantRef, 'status' => $status]);
            $pesanan->status = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            $pesanan->status_pesanan = ($status === 'EXPIRED') ? 'Kadaluarsa' : 'Gagal Bayar';
            $pesanan->save();
        } else {
            Log::warning('Tripay Callback (CUSTO-): Received unknown status.', ['ref' => $merchantRef, 'status' => $status]);
        }
    }

    /**
     * Endpoint API untuk mengambil daftar metode pembayaran Tripay secara dinamis
     * Berdasarkan konfigurasi di Database Sancaka
     */
    public function getTripayChannels()
    {
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $cacheKey = 'tripay_channels_public_' . $mode;

        $channels = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 24, function () use ($mode) {
            $apiKey = '';
            $baseUrl = '';

            if ($mode === 'production') {
                $baseUrl = 'https://tripay.co.id/api/merchant/payment-channel';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            } else {
                $baseUrl = 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            }

            if (empty($apiKey)) return [];

            try {
                $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->get($baseUrl);
                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                // Biarkan return array kosong jika gagal
            }
            return [];
        });

        return response()->json(['success' => true, 'data' => $channels]);
    }

    /**
     * EKSEKUTOR MIDTRANS SNAP UNTUK PESANAN PUBLIK
     */
    private function createPaymentMidtransSnapPublic(Pesanan $pesanan, int $amount, array $customerData)
    {
        try {
            $mode = \App\Models\Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
            $serverKey = \App\Models\Api::getValue('MIDTRANS_SERVER_KEY', $mode);
            $isProduction = ($mode === 'production');

            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $payload = [
                'transaction_details' => [
                    'order_id'     => $pesanan->nomor_invoice,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $customerData['name'],
                    'email'      => $customerData['email'],
                    'phone'      => $customerData['phone'],
                ],
                'callbacks' => [
                    'finish' => route('pesanan.public.success') // Arahkan ke sukses publik
                ]
            ];

            $response = Http::withBasicAuth($serverKey, '')->post($baseUrl, $payload);
            $result = $response->json();

            if (isset($result['redirect_url'])) {
                return $result['redirect_url'];
            }

            Log::error('Midtrans Snap Error (Public)', $result);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception Midtrans Snap (Public): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * EKSEKUTOR DANA GAPURA UNTUK PESANAN PUBLIK
     */
    private function createPaymentDanaPublic(Pesanan $pesanan, int $amount, array $customerData)
    {
        // 1. DYNAMIC CONFIGURATION
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            $merchantIdConf = \App\Models\Api::getValue('dana_prod_merchant_id', 'production');
            $partnerIdConf  = \App\Models\Api::getValue('dana_prod_client_id', 'production');
            $privateKey     = \App\Models\Api::getValue('dana_prod_private_key', 'production');
            $clientSecret   = \App\Models\Api::getValue('dana_prod_client_secret', 'production');
            $publicKey      = \App\Models\Api::getValue('dana_prod_public_key', 'production');
            $baseUrl        = 'https://api.saas.dana.id';
        } else {
            $merchantIdConf = \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox');
            $partnerIdConf  = \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox');
            $privateKey     = \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox');
            $clientSecret   = \App\Models\Api::getValue('dana_sandbox_client_secret', 'sandbox');
            $publicKey      = \App\Models\Api::getValue('dana_sandbox_public_key', 'sandbox');
            $baseUrl        = 'https://api.sandbox.dana.id';
        }

        config([
            'services.dana.merchant_id'   => $merchantIdConf,
            'services.dana.client_id'     => $partnerIdConf,
            'services.dana.x_partner_id'  => $partnerIdConf,
            'services.dana.private_key'   => $privateKey,
            'services.dana.public_key'    => $publicKey,
            'services.dana.client_secret' => $clientSecret,
            'services.dana.base_url'      => $baseUrl,
            'services.dana.dana_env'      => $isProduction ? 'PRODUCTION' : 'SANDBOX',
            'services.dana.origin'        => url('/')
        ]);

        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $pesanan->nomor_invoice);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$amount, 2, '.', '');

        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => ["value" => $amountValue, "currency" => "IDR"],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
                [
                    "url"        => url('/dana/return') . '?trx_id=' . $cleanInvoice,
                    "type"       => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url"        => url('/dana/notify'),
                    "type"       => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "additionalInfo"     => [
                "mcc" => "5732",
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ],
                "order" => [
                    "orderTitle"        => substr("Pay " . $cleanInvoice, 0, 64),
                    "scenario"          => "REDIRECT",
                    "merchantTransType" => "01",
                    "buyer" => [
                        "externalUserId"   => "PUBLIC" . rand(1000, 9999),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $customerData['name']), 0, 64),
                    ],
                    "goods" => [
                        [
                            "name"            => "Pengiriman Paket " . $pesanan->nomor_invoice,
                            "merchantGoodsId" => substr("ITEM" . $cleanInvoice, 0, 64),
                            "description"     => "Layanan Pengiriman",
                            "category"        => "LOGISTICS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        try {
            // Instantiate service (Make sure you use App\Services\DanaSignatureService at the top of file)
            $danaSignature = app(\App\Services\DanaSignatureService::class);
            $accessToken = $danaSignature->getAccessToken();
            $signature   = $danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf,
                'X-EXTERNAL-ID'  => \Illuminate\Support\Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => url('/'),
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $relativePath);

            $result = $response->json();

            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                return $result['webRedirectUrl'] ?? null;
            }

            Log::error('DANA_FAIL_CHECKOUT (Public)', ['Result' => $result]);
            return null;

        } catch (\Exception $e) {
            Log::error('DANA_EXCEPTION_CHECKOUT (Public)', ['Error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * EKSEKUTOR PAYPAL UNTUK PESANAN PUBLIK
     */
    private function createPaymentPayPalPublic(Pesanan $pesanan, int $amount)
    {
        try {
            // Instantiate service (Make sure you use App\Http\Controllers\Api\PayPalGatewayController at the top)
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);

            $rate = 16000;
            $usdAmount = round($amount / $rate, 2);

            $items = [[
                'name' => 'Kirim Paket ' . $pesanan->nomor_invoice,
                'quantity' => '1',
                'unit_amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($usdAmount, 2, '.', '')
                ]
            ]];

            $response = $paypalService->createOrder(
                $items,
                $usdAmount,
                $pesanan->nomor_invoice, // custom_id
                'CAPTURE',
                route('paypal.capture.return', ['invoice' => $pesanan->nomor_invoice]), // Ganti route sesuai kebutuhan
                route('pesanan.public.create')
            );

            $result = $response->getData(true);

            if (isset($result['success']) && $result['success'] === true && !empty($result['approve_url'])) {
                return $result['approve_url'];
            }

            Log::error('PayPal Create Order Error (Public)', $result);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception PayPal (Public): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Endpoint API untuk memverifikasi PIN Pengguna (M-Banking Style)
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|min:6|max:6'
        ]);

        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        if (empty($user->pin)) {
            return response()->json(['success' => false, 'message' => 'PIN keamanan belum diatur di profil Anda.']);
        }

        // Cek PIN menggunakan Hash Laravel
        if (\Illuminate\Support\Facades\Hash::check($request->pin, $user->pin)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'PIN yang Anda masukkan salah!']);
    }

    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN DANA BINDING (AUTO-DEBIT / DIRECT DEBIT) UNTUK PUBLIC
     * =========================================================================
     */
    private function createPaymentDanaBindingPublic(Pesanan $pesanan, int $amount, \App\Models\User $userAccount)
    {
        // 1. DYNAMIC CONFIGURATION DARI DATABASE
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            $merchantIdConf = \App\Models\Api::getValue('dana_prod_merchant_id', 'production');
            $partnerIdConf  = \App\Models\Api::getValue('dana_prod_client_id', 'production');
            $privateKey     = \App\Models\Api::getValue('dana_prod_private_key', 'production');
            $publicKey      = \App\Models\Api::getValue('dana_prod_public_key', 'production');
            $baseUrl        = 'https://api.saas.dana.id';
        } else {
            $merchantIdConf = \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox');
            $partnerIdConf  = \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox');
            $privateKey     = \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox');
            $publicKey      = \App\Models\Api::getValue('dana_sandbox_public_key', 'sandbox');
            $baseUrl        = 'https://api.sandbox.dana.id';
        }

        // WAJIB: Timpa config runtime agar DanaSignatureService membaca key yang dinamis ini
        config([
            'services.dana.merchant_id'   => $merchantIdConf,
            'services.dana.client_id'     => $partnerIdConf,
            'services.dana.x_partner_id'  => $partnerIdConf,
            'services.dana.private_key'   => $privateKey,
            'services.dana.public_key'    => $publicKey,
            'services.dana.base_url'      => $baseUrl,
            'services.dana.dana_env'      => $isProduction ? 'PRODUCTION' : 'SANDBOX',
            'services.dana.origin'        => url('/')
        ]);

        // Cek Keberadaan Token User
        if (empty($userAccount->dana_access_token)) {
            Log::error('DANA_BINDING_FAIL (Public): Token DANA user kosong.');
            return null;
        }

        // 2. DATA PREPARATION
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $pesanan->nomor_invoice);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo    = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$amount, 2, '.', '');
        $path         = '/rest/redirection/v1.0/debit/payment-host-to-host';

        // 3. BODY REQUEST (DANA SNAP BI B2B2C)
        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "validUpTo"          => $validUpTo,
            "amount" => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams" => [
                [
                    "url" => route('dana.return', ['trx_id' => $cleanInvoice]),
                    "type" => "PAY_RETURN",
                    "isDeeplink" => "N"
                ],
                [
                    "url" => url('/dana/notify'),
                    "type" => "NOTIFICATION",
                    "isDeeplink" => "N"
                ]
            ],
            "payOptionDetails" => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo" => [
                "supportDeepLinkCheckoutUrl" => "true",
                "productCode"                => "51051000100000000001",
                "mcc"                        => "5732",
                "order" => [
                    "orderTitle"        => substr("Checkout " . $cleanInvoice, 0, 64),
                    "merchantTransType" => "01",
                    "scenario"          => "DIRECT_DEBIT",
                    "buyer" => [
                        "externalUserId"   => (string) ($userAccount->id_pengguna ?? 'GUEST'),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $userAccount->nama_lengkap ?? 'Customer'), 0, 64)
                    ]
                ],
                "envInfo" => [
                    "sourcePlatform"    => "IPG",
                    "terminalType"      => "SYSTEM",
                    "orderTerminalType" => "WEB"
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            // 4. GENERATE TOKEN B2B & SIGNATURE
            $danaSignature  = app(\App\Services\DanaSignatureService::class);
            $accessTokenB2B = $danaSignature->getAccessToken();
            $signature      = $danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            $headers = [
                'Content-Type'           => 'application/json',
                'Authorization'          => 'Bearer ' . $accessTokenB2B,
                'Authorization-Customer' => 'Bearer ' . $userAccount->dana_access_token, // TOKEN USER
                'X-TIMESTAMP'            => $timestamp,
                'X-SIGNATURE'            => $signature,
                'ORIGIN'                 => url('/'),
                'X-PARTNER-ID'           => $partnerIdConf,
                'X-EXTERNAL-ID'          => (string) time() . \Illuminate\Support\Str::random(6),
                'X-DEVICE-ID'            => 'SANCAKA-WEB-POS',
                'CHANNEL-ID'             => '95221'
            ];

            \Illuminate\Support\Facades\Log::info('DANA_BINDING_REQ (Public)', ['URL' => $baseUrl . $path]);

            // 5. SEND REQUEST
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            \Illuminate\Support\Facades\Log::info('DANA_BINDING_RES (Public)', ['Result' => $result]);

            // 6. HANDLE RESPONSE
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // Skenario 1: Dana mengembalikan URL (Butuh PIN / Limit harian tercapai / Token Expired)
                if (!empty($result['webRedirectUrl'])) {
                    return $result['webRedirectUrl'];
                }

                // Skenario 2: Instant Success (Auto-Debit Berhasil Seketika)
                return route('pesanan.public.success', ['invoice' => $pesanan->nomor_invoice, 'status' => 'paid']);
            }

            Log::error('DANA_BINDING_FAIL (Public)', ['Result' => $result]);
            return null;

        } catch (\Exception $e) {
            Log::error('DANA_BINDING_EXCEPTION (Public)', ['Error' => $e->getMessage()]);
            return null;
        }
    }

}

