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
     * Disesuaikan untuk 'term' (jQuery UI) atau 'search'
     */
    public function searchKontak(Request $request)
    {
        $searchTerm = $request->input('term', $request->input('search'));
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
             return response()->json([], 422);
        }

        $kontaks = Kontak::where('nama', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%")
                         ->limit(10)
                         ->get(['id', 'nama', 'no_hp', 'alamat', 'province', 'regency', 'district', 'village', 'postal_code']);

        return response()->json($kontaks);
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
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                // Public form tidak bisa potong saldo
                throw new Exception('Metode pembayaran tidak valid untuk form ini.');
            }
            elseif (!in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'cash'])) {
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

    /**
     * =========================================================================
     * FUNGSI TRIPAY BARU (SINKRONISASI)
     * =========================================================================
     */
    private function _createTripayTransactionInternal(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key'); $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode', 'sandbox');
        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah tidak valid.'];

        // Adaptasi untuk Publik: Coba ambil email dari form, jika tidak ada, fallback
        $customerEmail = $data['customer_email'] ?? 'customer@tokosancaka.com'; // Ambil dari validasi
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            // Jika tidak ada email, gunakan fallback
            $customerEmail = 'customer' . Str::random(5) . '@tokosancaka.com';
        }

        $payload = [
            'method' => $data['payment_method'], 'merchant_ref' => $pesanan->nomor_invoice, 'amount' => $total,
            'customer_name' => $data['receiver_name'], 
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'], 
            'order_items' => $orderItems,
            'return_url' => route('pesanan.public.success'), // Arahkan ke halaman sukses publik
            'expired_time' => time() + (1 * 60 * 60), // 1 jam
            'signature' => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        Log::info('Tripay Create Transaction Payload (Internal Public):', $payload);
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)
                ->withoutVerifying() // Hapus .withoutVerifying() di produksi
                ->post($baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gagal menghubungi Tripay (HTTP Error) (Public)', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . ').'];
            }
            $responseData = $response->json();
            Log::info('Tripay Create Transaction Response (Internal Public):', $responseData);

            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                Log::error('Tripay mengembalikan respon gagal (Public)', ['response' => $responseData]);
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
            }
            return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Koneksi ke Tripay gagal (Public)', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        } catch (Exception $e) {
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

}

