<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use App\Models\Product;
use App\Models\Kontak;
use App\Models\User;
use App\Models\OrderMarketplace;
use Illuminate\Support\Str;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use App\Models\Order;
use Exception;
// 👇 Dependensi untuk Notifikasi Real-time
use App\Services\FonnteService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;


class PesananController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan milik pelanggan.
     */
    public function index()
    {
        $pesanans = Auth::user()->pesanans()
            ->latest('tanggal_pesanan')
            ->paginate(15);

        return view('customer.pesanan.index', compact('pesanans'));
    }

    /**
     * Menampilkan form untuk membuat pesanan baru.
     */
    public function create()
    {
        $products = Product::all();
        return view('customer.pesanan.create', compact('products'));
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
            Log::channel('daily')->error('KiriminAja Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat dari ekspedisi.'], 500);
        }
    }

    /**
     * Endpoint API untuk pencarian kontak berdasarkan nama atau no_hp.
     */
    public function searchKontak(Request $request)
    {
        Log::info('API Search Kontak dipanggil dengan:', $request->all());
        $request->validate([ 'search' => 'required|string|min:2', 'tipe' => 'nullable|in:Pengirim,Penerima,Keduanya', ]);
        $searchTerm = $request->input('search'); $tipe = $request->input('tipe');
        $query = Kontak::query();
        $query->where(function ($q) use ($searchTerm) {
            $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($searchTerm) . '%')
              ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%");
        });
        if ($tipe) {
            $query->where(function ($q) use ($tipe) {
                $q->where('tipe', $tipe)->orWhere('tipe', 'Keduanya');
            });
        }
        $kontaks = $query->limit(10)->get();
        return response()->json($kontaks);
    }

    /**
     * Menggunakan Nominatim untuk geocoding alamat (untuk layanan Instant).
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(10)->withHeaders(['User-Agent' => 'SancakaCustomer/1.0 (support@tokosancaka.com)'])
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

    private function _getAddressData(Request $request, string $type): array
    {
        $lat = $request->input("{$type}_lat");
        $lng = $request->input("{$type}_lng");

        $kirimajaAddr = [
            'district_id'   => $request->input("{$type}_district_id"),
            'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
            'postal_code'   => $request->input("{$type}_postal_code"),
        ];

        if (!is_numeric($lat) || !is_numeric($lng) || $lat == 0 || $lng == 0) {
            $simpleAddressQuery = implode(', ', array_filter([
                $request->input("{$type}_village"),
                $request->input("{$type}_district"),
                $request->input("{$type}_regency")
            ]));

            Log::info("Geocode fallback (Customer) triggered for {$type}. Query: {$simpleAddressQuery}");

            $geo = $this->geocode($simpleAddressQuery);

            if ($geo) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
                Log::info("Geocode fallback (Customer) SUCCESS for {$type}. Lat: {$lat}, Lng: {$lng}");
            } else {
                Log::warning("Geocode fallback (Customer) FAILED.", ['query' => $simpleAddressQuery]);
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
     * Cek ongkir dan validasi data.
     */
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $validated = $request->validate([
                'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                'item_price' => 'required|numeric|min:1',
                'weight' => 'required|numeric|min:1',
                'service_type' => 'required|string|in:regular,express,sameday,instant,cargo',
                'item_type' => 'required|integer', 
                'ansuransi' => 'required|string|in:iya,tidak',
                'length' => 'nullable|numeric|min:1',
                'width' => 'nullable|numeric|min:1',
                'height' => 'nullable|numeric|min:1',
                'sender_lat' => 'nullable|numeric', 'sender_lng' => 'nullable|numeric',
                'receiver_lat' => 'nullable|numeric', 'receiver_lng' => 'nullable|numeric',
                'sender_address' => 'required_if:service_type,instant,sameday|nullable|string',
                'receiver_address' => 'required_if:service_type,instant,sameday|nullable|string',
            ]);

            $senderLat = $validated['sender_lat'] ?? null;
            $senderLng = $validated['sender_lng'] ?? null;
            $receiverLat = $validated['receiver_lat'] ?? null;
            $receiverLng = $validated['receiver_lng'] ?? null;

            $mandatoryTypes = [1, 3, 4, 8]; // Sesuaikan ID package_types jika perlu
            $isMandatory = in_array((int) $validated['item_type'], $mandatoryTypes);

            if($isMandatory && $validated['ansuransi'] == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Jenis barang ini wajib menggunakan asuransi.'], 422);
            }
            $useInsurance = ($validated['ansuransi'] == 'iya') ? 1 : 0;
            $itemValue = $validated['item_price'];
            $options = [];

            if (in_array($validated['service_type'], ['instant', 'sameday'])) {
                if (!$senderLat || !$senderLng) {
                    $geo = $this->geocode($validated['sender_address']);
                    if ($geo) { $senderLat = $geo['lat']; $senderLng = $geo['lng']; }
                }
                if (!$receiverLat || !$receiverLng) {
                    $geo = $this->geocode($validated['receiver_address']);
                    if ($geo) { $receiverLat = $geo['lat']; $receiverLng = $geo['lng']; }
                }
                if (empty($senderLat) || empty($senderLng) || empty($receiverLat) || empty($receiverLng)) {
                    return response()->json(['status' => false, 'message' => 'Koordinat alamat tidak valid/ditemukan untuk ongkir instan/sameday.'], 422);
                }
                $options = $kirimaja->getInstantPricing(
                    $senderLat, $senderLng, $validated['sender_address'],
                    $receiverLat, $receiverLng, $validated['receiver_address'],
                    $validated['weight'], $itemValue, 'motor'
                );
            } else { // regular, express, cargo
                $category = $validated['service_type'] === 'cargo' ? 'trucking' : 'regular';
                $length = $request->input('length', 1); $width = $request->input('width', 1); $height = $request->input('height', 1);
                $options = $kirimaja->getExpressPricing(
                    $validated['sender_district_id'], $validated['sender_subdistrict_id'],
                    $validated['receiver_district_id'], $validated['receiver_subdistrict_id'],
                    $validated['weight'], $length, $width, $height, $itemValue,
                    null, $category, $useInsurance
                );
            }
            
            if (isset($options['status']) && $options['status'] === true && isset($options['results'])) {
                $options['results'] = array_filter($options['results'], function($opt) {
                    $price = $opt['cost'] ?? ($opt['final_price'] ?? 0);
                    return isset($price) && is_numeric($price) && $price > 0;
                });
                if (empty($options['results'])) {
                    return response()->json(['status' => false, 'message' => 'Tidak ada layanan pengiriman yang tersedia untuk rute atau parameter ini.'], 404);
                }
            } elseif (!isset($options['status']) || $options['status'] !== true) {
                 $errorMessage = $options['text'] ?? 'Gagal mengambil data ongkir dari ekspedisi.';
                 return response()->json(['status' => false, 'message' => $errorMessage], 500);
            }
            
            return response()->json($options);
        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Input tidak valid.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Cek Ongkir General Error (Customer):', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => false, 'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * =========================================================================
     * FUNGSI STORE
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

            // 3. Kalkulasi Biaya
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; 
            $cod_value = $calculation['cod_value'];
            $shipping_cost = $calculation['shipping_cost'];
            $insurance_cost = $calculation['ansuransi_fee'];
            $cod_fee = $calculation['cod_fee'];

            // 4. Siapkan Data Pesanan
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesananData['shipping_cost'] = $shipping_cost;
            $pesananData['insurance_cost'] = ($validatedData['ansuransi'] == 'iya') ? $insurance_cost : 0;
            $pesananData['cod_fee'] = ($cod_value > 0) ? $cod_fee : 0;
            
            $pesananData['id_pengguna_pembeli'] = Auth::user()->id_pengguna;
            $pesananData['customer_id'] = Auth::user()->id_pengguna;
            
            $pesanan = Pesanan::create($pesananData);
            $order = $pesanan; // Gunakan $order untuk konsistensi Model Binding

            $paymentUrl = null; 

            // 5. Proses logika pembayaran spesifik
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = Auth::user(); 

                if ($customer->saldo < $total_paid_ongkir) {
                    throw new Exception('Saldo Anda tidak mencukupi.');
                }
                $customer->decrement('saldo', $total_paid_ongkir);
            }
            elseif (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'cash'])) {
                // Diproses di Langkah 6
            }
            else {
                // --- BLOK PEMBAYARAN ONLINE (TRIPAY atau DOKU JOKUL) ---
                
                $paymentGateway = 'tripay'; 
                
                if (strtoupper($validatedData['payment_method']) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                if ($paymentGateway === 'doku') {
                    // --- PROSES VIA DOKU JOKUL ---
                    Log::info('Memulai proses DOKU (Jokul) untuk ' . $order->nomor_invoice);
                    $dokuService = new DokuJokulService();
                    
                    $orderData = (object) [
                        'invoice_number' => $order->nomor_invoice,
                        'amount' => $total_paid_ongkir 
                    ];
                    
                    $paymentUrl = $dokuService->createPayment($orderData->invoice_number, $orderData->amount);
                    
                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi pembayaran DOKU.');
                    }
                    
                    $order->payment_url = $paymentUrl;
                    
                } else {
                    // --- PROSES VIA TRIPAY ---
                    Log::info('Memulai proses TRIPAY untuk ' . $order->nomor_invoice);
                    $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $insurance_cost, $validatedData['ansuransi']);
                    // PERBAIKAN: Melewatkan variabel $order
                    $tripayResponse = $this->_createTripayTransactionInternal($validatedData, $order, $total_paid_ongkir, $orderItemsPayload);

                    if (empty($tripayResponse['success'])) {
                        throw new Exception('Gagal membuat transaksi pembayaran Tripay. Pesan: ' . ($tripayResponse['message'] ?? 'Tidak ada pesan.'));
                    }
                    $paymentUrl = $tripayResponse['data']['checkout_url'] ?? null;
                    $order->payment_url = $paymentUrl;
                }
            }


            // 6. Proses KiriminAja HANYA jika COD/Saldo/Cash
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo', 'cash'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');

                // PERBAIKAN: Melewatkan variabel $order
                $kiriminResponse = $this->_createKiriminAjaOrder(
                    $validatedData, $order, $kirimaja, $senderAddressData, $receiverAddressData,
                    $cod_value, $shipping_cost, $insurance_cost
                );

                if (($kiriminResponse['status'] ?? false) !== true) {
                    $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.');
                    throw new Exception($errorMessage);
                }
                $order->status = 'Menunggu Pickup';
                $order->status_pesanan = 'Menunggu Pickup';
                $order->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            }

            // 7. Simpan finalisasi data pesanan
            $order->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $order->save();
            DB::commit();
            
            // BLOK NOTIFIKASI REAL-TIME ADMIN 
            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->count() > 0) {
                    $customerName = Auth::user() ? Auth::user()->nama_lengkap : $validatedData['sender_name'];
                    
                    $adminUrl = $order->resi 
                                        ? route('admin.pesanan.show', $order->resi) 
                                        : route('admin.pesanan.index');

                    $dataNotifAdmin = [
                        'tipe'        => 'Pesanan',
                        'judul'       => 'Pesanan Manual Baru!',
                        'pesan_utama' => "Pesanan {$order->nomor_invoice} dibuat oleh {$customerName}.",
                        'url'         => $adminUrl,
                        'icon'        => 'fas fa-shipping-fast',
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal mengirim notifikasi pesanan manual ke admin: ' . $e->getMessage());
            }

            // 8. Kirim notifikasi WhatsApp
            $notification_total = $order->price;
            // PERBAIKAN: Melewatkan variabel $order
            $this->_sendWhatsappNotification(
                $order, $validatedData, $shipping_cost,
                (int) $order->insurance_cost, (int) $order->cod_fee, 
                $notification_total, $request
            );
            
            $notifMessage = 'Pesanan baru ' . ($order->resi ? 'dengan resi ' . $order->resi : 'dengan invoice ' . $order->nomor_invoice) . ' berhasil dibuat!';
            
            // Mengganti AdminNotificationEvent
            try {
                $admins = User::where('role', 'admin')->get(); 
                if ($admins->isNotEmpty()) {
                    $adminUrl = $order->resi 
                                        ? route('admin.pesanan.show', $order->resi) 
                                        : route('admin.pesanan.index');
                    
                    $dataNotifAdmin = [
                        'tipe'        => 'Pesanan',
                        'judul'       => 'Pesanan Baru Diterima!',
                        'pesan_utama' => Auth::user()->nama_lengkap . ' telah membuat pesanan baru.',
                        'url'         => $adminUrl,
                        'icon'        => 'fas fa-shipping-fast',
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal broadcast NotifikasiUmum (Customer): ' . $e->getMessage());
            }

            // 9. Arahkan pengguna
            if ($paymentUrl) {
                return redirect()->away($paymentUrl);
            }
            return redirect()->route('customer.pesanan.index')->with('success', $notifMessage);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validasi gagal saat membuat pesanan (Customer):', $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order Creation Failed (Customer): '. $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        if (!empty($data["save_{$prefix}"])) {
            $phoneKey = "{$prefix}_phone";
            if (!isset($data[$phoneKey])) {
                Log::warning("Gagal simpan kontak (Customer): Nomor HP kosong.", ['prefix' => $prefix]);
                return;
            }

            $sanitizedPhone = $this->_sanitizePhoneNumber($data[$phoneKey]);
            $name = $data["{$prefix}_name"] ?? null;
            $address = $data["{$prefix}_address"] ?? null;

            if (empty($sanitizedPhone) || empty($name) || empty($address)) {
                Log::warning("Gagal simpan kontak (Customer): Data (Nama/HP/Alamat) tidak lengkap.", [
                    'prefix'  => $prefix,
                    'phone'   => $sanitizedPhone,
                    'name'    => $name,
                    'address' => $address
                ]);
                return;
            }

            $existingContact = Kontak::where('no_hp', $sanitizedPhone)->first();

            $newTipe = $tipe;
            if ($existingContact) {
                if ($existingContact->tipe === 'Keduanya') {
                    $newTipe = 'Keduanya';
                } elseif ($existingContact->tipe !== $tipe) {
                    $newTipe = 'Keduanya';
                }
            }

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

    
    /**
     * FUNGSI PERSIAPAN DATA PESANAN
     */
    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        do { $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6)); } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());
        
        $fieldsToSave = array_keys($validatedData);
        $fieldsToExclude = ['save_sender', 'save_receiver', 'customer_email', 'sender_phone_original', 'receiver_phone_original'];
        $fieldsToSave = array_diff($fieldsToSave, $fieldsToExclude);

        $pesananCoreData = collect($validatedData)->only($fieldsToSave)->all();

        return array_merge($pesananCoreData, [
            'nomor_invoice' => $nomorInvoice,
            'status' => 'Menunggu Pembayaran',
            'status_pesanan' => 'Menunggu Pembayaran',
            'tanggal_pesanan' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'kontak_pengirim_id' => $validatedData['sender_id'] ?? null, 
            'kontak_penerima_id' => $validatedData['receiver_id'] ?? null, 
            'total_harga_barang' => $validatedData['item_price'], 
        ]);
    }
    
    /**
     * Melakukan validasi request untuk pembuatan/update pesanan.
     */
    private function _validateOrderRequest(Request $request): array
    {
        return $request->validate([
            // --- Data Pengirim ---
            'sender_name'           => 'required|string|max:100',
            'sender_phone'          => 'required|string|max:20',
            'sender_address'        => 'required|string|min:10|max:500',
            'sender_province'       => 'required|string|max:100',
            'sender_regency'        => 'required|string|max:100',
            'sender_district'       => 'required|string|max:100',
            'sender_village'        => 'nullable|string|max:100',
            'sender_postal_code'    => 'required|string|max:10',
            'sender_lat'            => 'nullable|numeric',
            'sender_lng'            => 'nullable|numeric',
            'sender_note'           => 'nullable|string|max:255',
            'save_sender'           => 'nullable|string|in:on,true,1', 

            // --- Data Penerima ---
            'receiver_name'         => 'required|string|max:100',
            'receiver_phone'        => 'required|string|max:20',
            'receiver_address'      => 'required|string|min:10|max:500',
            'receiver_province'     => 'required|string|max:100',
            'receiver_regency'      => 'required|string|max:100',
            'receiver_district'     => 'required|string|max:100',
            'receiver_village'      => 'nullable|string|max:100',
            'receiver_postal_code'  => 'required|string|max:10',
            'receiver_lat'          => 'nullable|numeric',
            'receiver_lng'          => 'nullable|numeric',
            'receiver_note'         => 'nullable|string|max:255',
            'save_receiver'         => 'nullable|string|in:on,true,1', 

            // --- Data Barang/Paket ---
            'item_description'      => 'required|string|max:255',
            'item_price'            => 'required|numeric|min:100',
            'weight'                => 'required|numeric|min:1',
            'length'                => 'nullable|numeric|min:1',
            'width'                 => 'nullable|numeric|min:1',
            'height'                => 'nullable|numeric|min:1',
            'ansuransi'             => 'required|string|in:iya,tidak',
            'item_type'             => 'required|integer', 

            // --- Data Pengiriman & Pembayaran ---
            'service_type'          => 'required|string|in:regular,express,sameday,instant,cargo',
            'expedition'            => 'required|string|max:255', 
            'payment_method'        => 'required|string|max:50',
            
            // Hidden IDs for KiriminAja/Kontak lookup
            'sender_district_id'    => 'required|integer',
            'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id'  => 'required|integer',
            'receiver_subdistrict_id' => 'required|integer',
            'sender_id'             => 'nullable|integer',
            'receiver_id'           => 'nullable|integer',
        ]);
    }

    /**
     * FUNGSI KALKULASI TOTAL YANG DIBAYAR
     */
    private function _calculateTotalPaid(array $validatedData): array
    {
        $parts = explode('-', $validatedData['expedition']); $count = count($parts);
        $cod_fee = 0; $ansuransi_fee = 0; $shipping_cost = 0;
        if ($count >= 6) { $cod_fee = (int) end($parts); $ansuransi_fee = (int) $parts[$count - 2]; $shipping_cost = (int) $parts[$count - 3]; }
        elseif ($count === 5) { $ansuransi_fee = (int) $parts[4]; $shipping_cost = (int) $parts[3]; }
        elseif ($count === 4) { $shipping_cost = (int) $parts[3]; }
        else { Log::warning('Format expedition tidak dikenal (Customer)', ['exp' => $validatedData['expedition']]); }

        $item_price = (int)$validatedData['item_price'];
        $use_insurance = $validatedData['ansuransi'] == 'iya';

        $total_paid_ongkir = $shipping_cost;
        if ($use_insurance) {
            $total_paid_ongkir += $ansuransi_fee;
        }

        $cod_value = 0;
        if ($validatedData['payment_method'] === 'CODBARANG') {
            $cod_value = $item_price + $shipping_cost + $cod_fee;
            if ($use_insurance) {
                $cod_value += $ansuransi_fee;
            }
        } elseif ($validatedData['payment_method'] === 'COD') {
            $total_paid = $shipping_cost + $cod_fee;
            if ($use_insurance) {
                $total_paid += $ansuransi_fee;
            }
            $cod_value = $total_paid;
        }
        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }

    /**
     * FUNGSI UNTUK MEMBUAT ORDER DI KIRIMIN AJA
     */
    private function _createKiriminAjaOrder(
        array $data, Pesanan $order, KiriminAjaService $kirimaja, // Menerima $order
        array $senderData, array $receiverData, int $cod_value,
        int $shipping_cost, int $insurance_cost
    ): array
    {
        $expeditionParts = explode('-', $data['expedition'] ?? '');
        $serviceGroup = $expeditionParts[0] ?? null; $courier = $expeditionParts[1] ?? null; $service_type = $expeditionParts[2] ?? null;

        if (empty($data['sender_address']) || empty($data['sender_phone']) || empty($data['sender_name']) ||
            empty($data['receiver_name']) || empty($data['receiver_phone']) || empty($data['receiver_address']) ||
            empty($data['item_description']) || !isset($data['item_price']) || !isset($data['weight']) ||
            !isset($data['item_type']) || empty($courier) || empty($service_type)) {
                Log::error('_createKiriminAjaOrder (Customer): Missing required data.', ['invoice' => $order->nomor_invoice]);
                return ['status' => false, 'text' => 'Data pesanan tidak lengkap untuk dikirim ke ekspedisi.'];
        }

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

        } else { // Express, Regular, Cargo
            $scheduleResponse = $kirimaja->getSchedules(); $scheduleClock = $scheduleResponse['clock'] ?? null;
            $category = ($data['service_type'] ?? $serviceGroup) === 'cargo' ? 'trucking' : 'regular';

            $weightInput = (int) $data['weight'];
            $lengthInput = (int) ($data['length'] ?? 1); $widthInput = (int) ($data['width'] ?? 1); $heightInput = (int) ($data['height'] ?? 1);
            $volumetricWeight = 0;
            if ($lengthInput > 0 && $widthInput > 0 && $heightInput > 0) {
                $volumetricWeight = ($widthInput * $lengthInput * $heightInput) / ($category === 'trucking' ? 4000 : 6000) * 1000;
            }
            $finalWeight = max($weightInput, $volumetricWeight);
            $insuranceAmount = ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0;

            if (empty($senderData['kirimaja_data']['district_id']) || empty($senderData['kirimaja_data']['subdistrict_id']) ||
                empty($receiverData['kirimaja_data']['district_id']) || empty($receiverData['kirimaja_data']['subdistrict_id']) ||
                empty($senderData['kirimaja_data']['postal_code']) || empty($receiverData['kirimaja_data']['postal_code'])) {
                    Log::error('_createKiriminAjaOrder (Customer): Missing KiriminAja address IDs.', ['invoice' => $order->nomor_invoice, 'sender_data' => $senderData, 'receiver_data' => $receiverData]);
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
                    'weight' => ceil($finalWeight), 
                    'width' => $widthInput, 'height' => $heightInput, 'length' => $lengthInput,
                    'item_value' => (int)$data['item_price'],
                    'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => $insuranceAmount,
                    'cod' => $cod_value,
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Express/Cargo Order Payload (Customer):', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    /**
     * FUNGSI UNTUK MEMBUAT TRANSAKSI TRIPAY
     */
    private function _createTripayTransactionInternal(array $data, Pesanan $order, int $total, array $orderItems): array // Menerima $order
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
            // PERBAIKAN: Menggunakan kunci 'order' sesuai rute {order}
            'return_url' => route('customer.pesanan.show', ['order' => $order]), 
            'expired_time' => time() + (1 * 60 * 60), 
            'signature' => hash_hmac('sha256', $merchantCode . $order->nomor_invoice . $total, $privateKey),
        ];
        Log::info('Tripay Create Transaction Payload (Internal Customer):', $payload);
        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)
                ->withoutVerifying()
                ->post($baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gagal menghubungi Tripay (HTTP Error) (Customer)', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . ').'];
            }
            $responseData = $response->json();
            Log::info('Tripay Create Transaction Response (Internal Customer):', $responseData);

            if (!isset($responseData['success']) || $responseData['success'] !== true) {
                Log::error('Tripay mengembalikan respon gagal (Customer)', ['response' => $responseData]);
                return ['success' => false, 'message' => $responseData['message'] ?? 'Gagal membuat tagihan pembayaran.'];
            }
            return $responseData;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Koneksi ke Tripay gagal (Customer)', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        } catch (Exception $e) {
            Log::error('Error saat membuat transaksi Tripay (Customer)', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Terjadi kesalahan internal saat proses pembayaran.'];
        }
    }


    // --- FUNGSI HELPER LAINNYA ---

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

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

    private function _sendWhatsappNotification(
        Pesanan $order, array $validatedData, int $shipping_cost, // Menerima $order
        int $ansuransi_fee, int $cod_fee, int $total_paid,
        Request $request
    ) {
        $displaySenderPhone = $request->input('sender_phone') ?? $validatedData['sender_phone_original'] ?? $order->sender_phone;
        $displayReceiverPhone = $request->input('receiver_phone') ?? $validatedData['receiver_phone_original'] ?? $order->receiver_phone;

        $detailPaket = "*Detail Paket:*\n";
        $detailPaket .= "Deskripsi: " . ($order->item_description ?? '-') . "\n";
        $detailPaket .= "Berat: " . ($order->weight ?? 0) . " Gram\n";
        if ($order->length && $order->width && $order->height) {
            $detailPaket .= "Dimensi: {$order->length}x{$order->width}x{$order->height} cm\n";
        }
        $expeditionParts = explode('-', $order->expedition ?? '');
        $exp_vendor = $expeditionParts[1] ?? '';
        $exp_service_type = $expeditionParts[2] ?? '';
        $service_display = trim(ucwords(strtolower(str_replace('_', ' ', $exp_vendor))) . ' ' . ucwords(strtolower(str_replace('_', ' ', $exp_service_type))));
        $detailPaket .= "Ekspedisi: " . ($service_display ?: '-') . "\n";
        $detailPaket .= "Layanan: " . ucwords($order->service_type ?? '-');
        
        if ($order->resi) {
            $detailPaket .= "\nResi: *" . $order->resi . "*";
        } else {
            $detailPaket .= "\nResi: Menunggu Resi";
        }

        $rincianBiaya = "*Rincian Biaya:*\n- Ongkir: Rp " . number_format($shipping_cost, 0, ',', '.');
        $itemPrice = $validatedData['item_price'] ?? $order->item_price ?? 0;
        
        $use_insurance = $ansuransi_fee > 0;
        $is_cod_payment = in_array($order->payment_method, ['COD', 'CODBARANG']);
        
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

        $statusBayar = "⏳ Menunggu Pembayaran"; 
        if (in_array($order->payment_method, ['COD', 'CODBARANG'])) {
            $statusBayar = "⏳ Bayar di Tempat (COD)";
        } elseif ($order->payment_method === 'Potong Saldo' || $order->payment_method === 'cash') {
            $statusBayar = "✅ Lunas via Saldo / Tunai";
        } elseif (in_array($order->status, ['Menunggu Pickup', 'Diproses', 'Terkirim', 'Selesai', 'Pembayaran Lunas (Gagal Auto-Resi)', 'Pembayaran Lunas (Error Kirim API)'])) {
            $statusBayar = "✅ Lunas";
        } elseif (in_array($order->status, ['Gagal Bayar', 'Kadaluarsa'])) {
            $statusBayar = "❌ Pembayaran Gagal/Kadaluarsa";
        }

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

        $linkResi = $order->resi ?? $order->nomor_invoice;
        $message = str_replace(
            [
                '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                '{DETAIL_PAKET}', '{RINCIAN_BIAYA}', '{TOTAL_BAYAR}', '{STATUS_BAYAR}', '{LINK_RESI}'
            ],
            [
                $order->nomor_invoice,
                $order->sender_name, $displaySenderPhone,
                $order->receiver_name, $displayReceiverPhone,
                $detailPaket, $rincianBiaya,
                number_format($total_paid, 0, ',', '.'),
                $statusBayar, $linkResi
            ],
            $messageTemplate
        );

        $senderWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($order->sender_phone));
        $receiverWa = preg_replace('/^0/', '62', $this->_sanitizePhoneNumber($order->receiver_phone));
        // Daftar nomor admin (format 62xxxxxxxxxx)
        // Nomor 085745808809 menjadi 6285745808809
        // Nomor 08819435180 menjadi 628819435180
        $adminWaS = [
            '6285745808809',
            '628819435180',
        ];
        
        // Buat pesan khusus untuk admin (opsional, tapi disarankan)
        $adminMessage = "🚨 *NOTIFIKASI PESANAN BARU CUSTOMER*\nInvoice: {$order->nomor_invoice}\nDari: {$order->sender_name}\nKe: {$order->receiver_name}\nTotal Bayar: Rp " . number_format($total_paid, 0, ',', '.') . "\n\nLihat Detail: " . route('admin.pesanan.show', $order->resi ?: $order->nomor_invoice);

        try {
            if ($senderWa) \App\Services\FonnteService::sendMessage($senderWa, $message);
            if ($receiverWa) \App\Services\FonnteService::sendMessage($receiverWa, $message);
            // 👇 LOGIKA BARU: Kirim ke Admin
            foreach ($adminWaS as $adminWa) {
                \App\Services\FonnteService::sendMessage($adminWa, $adminMessage);
            }
            // 👆 AKHIR LOGIKA BARU
            
            
            
            Log::info("Notifikasi WA Terkirim (Customer) untuk Invoice: " . $order->nomor_invoice);
        } catch (Exception $e) {
            Log::error('Fonnte Service (Customer) sendMessage failed: ' . $e->getMessage(), ['invoice' => $order->nomor_invoice]);
        }
    }
    
    // --- FUNGSI TAMPILAN LAINNYA ---

    public function success()
    {
        $order = session('order');
        if (!$order) return redirect()->route('home');
        return view('pesanan_customer.success', ['order' => $order]);
    }
    
    /**
     * FUNGSI SHOW (Perbaikan Model Binding: $order)
     */
    public function show(Pesanan $order) // MENGGUNAKAN $order
    {
        // Otorisasi: Pastikan pesanan ini milik user yang sedang login
        if ($order->id_pengguna_pembeli !== Auth::user()->id_pengguna) {
              abort(403, 'Anda tidak diizinkan mengakses pesanan ini.');
        }
        
        $pesanan = $order; // Buat $pesanan untuk konsistensi view
        return view('customer.pesanan.status', compact('pesanan'));
    }

    public function cetakResiThermal($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)
                            ->where('id_pengguna_pembeli', auth()->id())
                            ->firstOrFail();
        return view('customer.pesanan.cetak_thermal', compact('pesanan'));
    }

    /**
     * FUNGSI RIWAYAT
     */
    public function riwayat() 
    {
        $userId = Auth::id();

        $orders = OrderMarketplace::where('user_id', $userId) 
            ->with([
                'user', 
                'store.user', 
                'items.product' 
            ]) 
            ->latest('created_at')    
            ->paginate(15);           

        return view('customer.pesanan.riwayat', ['pesanans' => $orders]);
    }
    
    /**
     * Endpoint API untuk menyimpan/memperbarui Kontak secara real-time (Autosave).
     * Dipanggil via AJAX saat checkbox 'Simpan/Perbarui data' dicentang.
     */
    public function saveContactApi(Request $request)
    {
        // Log::info('Autosave Contact API Called:', $request->all());

        try {
            // 1. Validasi data yang masuk dari AJAX (TANPA lat dan lng)
            $validated = $request->validate([
                'name'        => 'required|string|max:100',
                'phone'       => 'required|string|max:20',
                'address'     => 'required|string|min:10|max:500', 
                'tipe'        => 'required|in:Pengirim,Penerima',
                'province'    => 'nullable|string|max:100',
                'regency'     => 'nullable|string|max:100',
                'district'    => 'nullable|string|max:100',
                'village'     => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:10',
                'id'          => 'nullable|integer', 
            ]);
        } catch (ValidationException $e) {
            // Kirim kembali error validasi
            return response()->json([
                'status' => 'error', 
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        }

        // 2. Sanitasi dan Cek Tipe Kontak
        $sanitizedPhone = $this->_sanitizePhoneNumber($validated['phone']);

        $existingContact = \App\Models\Kontak::where('no_hp', $sanitizedPhone)->first();
        
        $newTipe = $validated['tipe'];
        if ($existingContact) {
            if ($existingContact->tipe === 'Keduanya' || $existingContact->tipe !== $validated['tipe']) {
                $newTipe = 'Keduanya';
            }
        }
        
        // 3. Simpan/Perbarui ke Database
        try {
            // Menggunakan fully qualified class name atau pastikan App\Models\Kontak diimpor
            $contact = \App\Models\Kontak::updateOrCreate(
                ['no_hp' => $sanitizedPhone], // Kunci untuk mencari
                [
                    'id_Pengguna' => Auth::id() ?? null, 
                    'nama'        => $validated['name'],
                    'no_hp'       => $sanitizedPhone,
                    'alamat'      => $validated['address'],
                    
                    'province'    => $validated['province'],
                    'regency'     => $validated['regency'],
                    'district'    => $validated['district'],
                    'village'     => $validated['village'],
                    'postal_code' => $validated['postal_code'],
                    
                    // TIDAK LAGI MENYIMPAN lat DAN lng
                    
                    'tipe'        => $newTipe,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Data kontak berhasil disimpan.',
                'contact_id' => $contact->id // Mengembalikan ID yang baru/diperbarui
            ]);

        } catch (Exception $e) {
            Log::error('Autosave Contact API Failed: ' . $e->getMessage(), ['data' => $validated]);
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()], 500);
        }
    }
    
    public function sendResiViaWhatsappApi(Request $request)
{
    // 1. Validasi Input
    $request->validate([
        'resi' => 'required|string',
        'target' => 'required|in:receiver,sender'
    ]);

    $resi = $request->input('resi');
    $target = $request->input('target');

    // 2. Cari Pesanan
    $pesanan = Pesanan::where('resi', $resi)->first();

    if (!$pesanan) {
        return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'], 404);
    }
    
    // 3. Tentukan Target dan Pesan
    if ($target === 'receiver') {
        $name = $pesanan->receiver_name;
        $phone = $pesanan->receiver_phone;
        $messagePrefix = "Halo {$name}, pesanan Anda dengan resi {$pesanan->resi} dari {$pesanan->sender_name} telah berhasil dibuat.";
    } else { // sender
        $name = $pesanan->sender_name;
        $phone = $pesanan->sender_phone;
        $messagePrefix = "Halo {$name}, pesanan Anda dengan resi {$pesanan->resi} untuk {$pesanan->receiver_name} telah berhasil dibuat.";
    }

    $trackingLink = "https://tokosancaka.com/tracking/search?resi={$pesanan->resi}";
    $fullMessage = "{$messagePrefix} Anda dapat melacak resi di sini: {$trackingLink}";
    $waPhone = preg_replace('/^0/', '62', $phone);
    
    // 4. Kirim dengan Fonnte
    try {
        if (!empty($waPhone)) {
            // Asumsi FonnteService::sendMessage($to, $message) adalah method yang benar
            \App\Services\FonnteService::sendMessage($waPhone, $fullMessage);
            
            return response()->json([
                'status' => 'success',
                'message' => "Pesan berhasil dikirim ke {$name} ({$waPhone})."
            ]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Nomor WhatsApp tidak valid atau kosong.'], 400);
        }
    } catch (\Exception $e) {
        Log::error("Fonnte API failed for resi {$resi}: " . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => 'Gagal mengirim pesan melalui Fonnte. Periksa log server.'], 500);
    }
}

}