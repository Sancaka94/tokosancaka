<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use Exception;

// --- MODEL ---
use App\Models\Pesanan;
use App\Models\Product;
use App\Models\Kontak;
use App\Models\User;
use App\Models\OrderMarketplace;
use App\Models\Order;
use App\Models\Keuangan; // <--- PERBAIKAN UTAMA DISINI

// --- SERVICES ---
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\FonnteService;

// --- NOTIFIKASI ---
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;

class PesananController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan milik pelanggan dengan fitur Filter & Search.
     */
    public function index(Request $request)
    {
        // Ambil ID Customer yang sedang login
        $userId = Auth::id();

        // 1. QUERY DASAR: Hanya ambil pesanan milik customer ini
        $query = Pesanan::where('id_pengguna_pembeli', $userId);

        // 2. LOGIKA PENCARIAN (Search Bar)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('nomor_invoice', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%")
                  // Tambahan: Agar bisa mencari nama ekspedisi manual (misal ketik "lion")
                  ->orWhere('expedition', 'like', "%{$search}%");
            });
        }

        // 3. LOGIKA FILTER EKSPEDISI (Dari Klik Tombol Detail Dashboard)
        // Menangkap parameter url: ?ekspedisi=lion
        if ($request->has('ekspedisi') && $request->ekspedisi != '') {
            $filterKurir = $request->ekspedisi;

            // Bungkus dalam where closure agar aman
            $query->where(function($q) use ($filterKurir) {
                // Mencari format "-jne-" (di tengah string)
                $q->where('expedition', 'LIKE', '%-' . $filterKurir . '-%')
                  // ATAU format "jne-" (di awal string)
                  ->orWhere('expedition', 'LIKE', $filterKurir . '-%');
            });
        }

        // 4. Filter Status (Opsional, jika ada dropdown status)
        if ($request->filled('status')) {
            $query->where('status_pesanan', $request->input('status'));
        }

        // 5. Eksekusi Query, Sorting, dan Pagination
        $pesanans = $query->latest('tanggal_pesanan')->paginate(15);

        // PENTING: Tambahkan appends agar filter tidak hilang saat pindah halaman (Page 2, dst)
        $pesanans->appends($request->all());

        return view('customer.pesanan.index', compact('pesanans'));
    }

    /**
     * Menampilkan form untuk membuat pesanan baru.
     */
    public function create()
    {
        $products = Product::all();

        // <<< TAMBAHKAN BLOK INI (1/2) >>>
        // Buat Idempotency Key unik sekali saat memuat halaman
        $idempotencyKey = (string) Str::uuid();

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

        //dd($request->all());

        // <<< TAMBAHKAN BLOK INI (2/2) - Di awal store() >>>
        $idempotencyKey = $request->input('idempotency_key');

        // 0. CEK IDEMPOTENCY KEY
        if ($idempotencyKey) {
            $existingOrder = Pesanan::where('idempotency_key', $idempotencyKey)->first();

            if ($existingOrder) {
                // Request duplikat terdeteksi. Redirect ke halaman order yang sudah ada.
                Log::warning('Idempotency check failed: Duplicate request detected.', ['key' => $idempotencyKey]);
                return redirect()->route('customer.pesanan.index')->with('warning', 'Order ini sudah berhasil diproses sebelumnya.');
            }
        }
        // <<< AKHIR BLOK TAMBAHAN 2/2 >>>

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

                if (strtoupper($validatedData['payment_method']) === 'DOKU') {
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

                // ==========================================================
            // 🔥 LOGIKA BARU: SIMPAN SHIPPING REF & RESI 🔥
            // ==========================================================

            // 1. Ambil Booking ID (Ref) dari berbagai kemungkinan response
            $bookingId = $kiriminResponse['id']
                      ?? $kiriminResponse['data']['id']
                      ?? $kiriminResponse['payment_ref']
                      ?? ($kiriminResponse['details'][0]['order_id'] ?? null);

            // 2. Simpan ke database
            $order->shipping_ref = $bookingId;

            // 3. Ambil Resi (AWB)
            $awbAsli = $kiriminResponse['awb']
                    ?? $kiriminResponse['result']['awb_no'] // Format v3
                    ?? ($kiriminResponse['results'][0]['awb'] ?? null); // Format v4

            // 4. Update Status & Resi
            $order->status = 'Pesanan Dibuat';
            $order->status_pesanan = 'Pesanan Dibuat';

            // Jika AWB belum keluar, gunakan Booking ID sebagai Resi sementara
            $order->resi = !empty($awbAsli) ? $awbAsli : ($bookingId ?? 'REF-'.$order->nomor_invoice);

            // ==========================================================
            }

            // 7. Simpan finalisasi data pesanan
            $order->price = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $order->save();
            DB::commit();

            self::simpanKeKeuangan($order);

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

    /**
     * Helper Simpan Kontak (FIXED: Wajib Auth User ID & Return Object)
     */
    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        // 1. Cek Checkbox (Wajib dicentang)
        if (empty($data["save_{$prefix}"]) || $data["save_{$prefix}"] !== 'on') {
            return null;
        }

        // 2. Ambil User ID dari Auth (Keamanan Mutlak)
        $userId = Auth::id();
        if (!$userId) return null; // Cegah error jika sesi habis

        // 3. Validasi Data Minimal
        $phoneKey = "{$prefix}_phone";
        if (!isset($data[$phoneKey])) return null;

        $sanitizedPhone = $this->_sanitizePhoneNumber($data[$phoneKey]);
        $name = $data["{$prefix}_name"] ?? null;
        $address = $data["{$prefix}_address"] ?? null;

        if (empty($sanitizedPhone) || empty($name) || empty($address)) {
            return null;
        }

        // 4. LOGIKA SIMPAN (Update or Create)
        // Kuncinya: Cari berdasarkan User ID DAN No HP.
        // Jadi 1 User bisa punya banyak kontak, dan HP yang sama boleh dipakai user lain (data terpisah).
        $contact = Kontak::updateOrCreate(
            [
                'user_id' => $userId,        // KUNCI 1: Milik User Login
                'no_hp'   => $sanitizedPhone // KUNCI 2: Nomor HP
            ],
            [
                'nama'        => $name,
                'alamat'      => $address,
                'province'    => $data["{$prefix}_province"] ?? null,
                'regency'     => $data["{$prefix}_regency"] ?? null,
                'district'    => $data["{$prefix}_district"] ?? null,
                'village'     => $data["{$prefix}_village"] ?? null,
                'postal_code' => $data["{$prefix}_postal_code"] ?? null,
                'tipe'        => $tipe // Simpan sesuai tipe (Pengirim/Penerima)
            ]
        );

        return $contact; // Wajib return objek agar ID-nya bisa diambil
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

            self::simpanKeKeuangan($order);
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
            // ----- PERBAIKAN DI SINI UNTUK SCHEDULE -----
            // Ambil jadwal default dari KiriminAja atau gunakan waktu sekarang
            $scheduleResponse = $kirimaja->getSchedules();
            $scheduleClock = $scheduleResponse['clock'] ?? date('Y-m-d H:i:s');

            // JIKA JAM >= 15:00, SET JADWAL PICKUP BESOK PAGI JAM 09:00
            // Ini untuk menghindari error 'melewati batas pemrosesan' dari SPX/J&T
            if ((int)date('H') >= 15) {
                $scheduleClock = date('Y-m-d H:i:s', strtotime('+1 day 09:00:00'));
                Log::info("Jadwal Pickup digeser ke besok ($scheduleClock) karena sudah sore.");
            }
            // ---------------------------------------------
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
                    'schedule' => $scheduleClock, // <--- Menggunakan jadwal hasil logika di atas
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
     * FUNGSI UNTUK MEMBUAT TRANSAKSI TRIPAY
     * (LOGIKA DISAMAKAN DENGAN ADMIN: AMBIL DARI DATABASE)
     */
    private function _createTripayTransactionInternal(array $data, Pesanan $order, int $total, array $orderItems): array
    {
        // ==========================================================
        // 🔥 LOGIKA BARU: AMBIL CONFIG DARI DATABASE (SEPERTI ADMIN) 🔥
        // ==========================================================

        // 1. Ambil Mode Global dari Database
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // 2. Siapkan wadah variabel
        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        // 3. Isi variabel berdasarkan MODE yang aktif di Database
        if ($mode === 'production') {
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            // Fallback ke Sandbox
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        // Cek Konfigurasi Lengkap
        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING (Customer Mode: ' . $mode . ')');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap di Database.'];
        }

        // ==========================================================
        // AKHIR LOGIKA CONFIG DATABASE
        // ==========================================================

        if ($total <= 0) return ['success' => false, 'message' => 'Jumlah pembayaran tidak valid.'];

        // Ambil email customer
        $customerEmail = Auth::user()->email ?? null;
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customerEmail = 'customer+' . Str::random(5) . '@tokosancaka.com';
        }

        // [OPTIMASI] Gunakan Integer untuk amount
        $amountInt = (int) $total;

        // [OPTIMASI] Expired Time 24 Jam (Agar user punya waktu bayar)
        $expiredTime = time() + (24 * 60 * 60);

        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $order->nomor_invoice,
            'amount'         => $amountInt,
            'customer_name'  => $data['receiver_name'], // Gunakan nama penerima paket
            'customer_email' => $customerEmail,
            'customer_phone' => $data['receiver_phone'], // Gunakan no hp penerima
            'order_items'    => $orderItems,
            // Arahkan kembali ke halaman detail pesanan CUSTOMER
            'return_url'     => route('customer.pesanan.show', ['order' => $order]),
            'expired_time'   => $expiredTime,
            'signature'      => hash_hmac('sha256', $merchantCode . $order->nomor_invoice . $amountInt, $privateKey),
        ];

        Log::info('Tripay Create Transaction Payload (Customer):', $payload);

        try {
            // Setup HTTP Client
            $http = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(60);

            // [KEAMANAN] Matikan verifikasi SSL HANYA jika bukan production
            if ($mode !== 'production') {
                $http->withoutVerifying();
            }

            $response = $http->post($baseUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gagal menghubungi Tripay (HTTP Error) (Customer)', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => 'Gagal menghubungi server pembayaran (HTTP: ' . $response->status() . ').'];
            }

            $responseData = $response->json();
            Log::info('Tripay Create Transaction Response (Customer):', $responseData);

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

    // --- HELPER PRIVAT ---
    private function _buildCustomerMessage($pesanan, $statusBayarText, $finalTotal, $itemPrice, $shippingCost, $insuranceCost, $finalCodFee, $trackingLink, $isCodOngkir, $isCodBarang, $fmt, $roleName, $tglIndo)
    {
        $name = ($roleName === 'Penerima') ? $pesanan->receiver_name : $pesanan->sender_name;

        $rincianPesan = "";
        if ($isCodOngkir || $isCodBarang) {
            $noteBarang = ($isCodOngkir) ? "(Tidak Ditagihkan)" : "";
            $rincianPesan .= "💰 *Rincian Tagihan:*\n";

            // Tampilkan Nilai Barang (Dicoret kalau COD Ongkir di Web, tapi di WA diberi keterangan)
            if ($itemPrice > 0) $rincianPesan .= "- Barang: Rp " . $fmt($itemPrice) . " " . $noteBarang . "\n";

            $rincianPesan .= "- Ongkir: Rp " . $fmt($shippingCost) . "\n";

            if ($insuranceCost > 0) $rincianPesan .= "- Asuransi: Rp " . $fmt($insuranceCost) . "\n";

            // Tampilkan Fee Layanan (Hasil Hitungan Benar)
            if ($finalCodFee > 0) $rincianPesan .= "- Biaya Layanan COD: Rp " . $fmt($finalCodFee) . "\n";

            $rincianPesan .= "--------------------------------\n";
            $rincianPesan .= "*Total Yang Harus Dibayar: Rp " . $fmt($finalTotal) . "*\n";
        } else {
            $rincianPesan .= "Status Pembayaran: *LUNAS* (Tidak perlu bayar ke kurir)\n";
        }

        return <<<TEXT
Halo Kak {$name} ({$roleName}) 👋,

Pesanan Anda telah berhasil diproses dan Resi sudah keluar!

📜 No. Resi: *{$pesanan->resi}*
🚚 Ekspedisi: {$pesanan->expedition}
📦 Layanan: {$pesanan->service_type}
💳 Metode: {$pesanan->payment_method}
📅 Tanggal: {$tglIndo}

{$rincianPesan}
🔍 *Lacak paket di sini:*
{$trackingLink}

Terima kasih telah menggunakan jasa kami. 🙏
TEXT;
    }

    /**
     * FUNGSI INTERNAL: Kirim Notifikasi WA dengan Logika COD Ongkir vs COD Barang
     * Dipanggil dari method store()
     */
    private function _sendWhatsappNotification($order, $data, $shipping_cost, $insurance_cost, $cod_fee, $total_invoice, $request)
    {
        try {
            // 1. Setup Dasar
            $adminNumbers = ['085745808809', '08819435180']; // Nomor Admin
            $fmt = function($val) { return number_format($val, 0, ',', '.'); };

            // Bersihkan format metode pembayaran
            $paymentMethod = strtoupper($data['payment_method']);
            $isCodOngkir   = ($paymentMethod === 'COD');          // Hanya bayar Ongkir
            $isCodBarang   = ($paymentMethod === 'CODBARANG');    // Bayar Barang + Ongkir

            // 2. Tentukan Status Tagihan & Note Admin
            $statusTagihan = "";
            $adminInstruction = "";
            $rincianKeuangan = "";

            // --- LOGIKA PESAN BERDASARKAN TIPE PEMBAYARAN ---

            if ($isCodOngkir) {
                // KASUS: COD ONGKIR
                // User sudah transfer harga barang (atau dianggap lunas), cuma bayar ongkir ke kurir
                $adminInstruction = "⚠️ *INSTRUKSI: TAGIH ONGKIR + FEE SAJA*";

                $rincianKeuangan .= "⛔ Harga Barang: Rp " . $fmt($data['item_price']) . " (JANGAN DITAGIH)\n";
                $rincianKeuangan .= "✅ Ongkir: Rp " . $fmt($shipping_cost) . "\n";
                if($insurance_cost > 0) $rincianKeuangan .= "✅ Asuransi: Rp " . $fmt($insurance_cost) . "\n";

                // Hitung sisa fee agar totalnya klop dengan total_invoice
                // Total Invoice di COD Ongkir biasanya = Ongkir + Asuransi + Fee + PPN
                $calculatedFee = $total_invoice - $shipping_cost - $insurance_cost;
                if($calculatedFee > 0) $rincianKeuangan .= "✅ Biaya Layanan: Rp " . $fmt($calculatedFee) . "\n";

            } elseif ($isCodBarang) {
                // KASUS: COD BARANG (FULL)
                // User bayar semuanya ke kurir
                $adminInstruction = "⚠️ *INSTRUKSI: TAGIH FULL (BARANG + ONGKIR)*";

                $rincianKeuangan .= "✅ Harga Barang: Rp " . $fmt($data['item_price']) . "\n";
                $rincianKeuangan .= "✅ Ongkir: Rp " . $fmt($shipping_cost) . "\n";
                if($insurance_cost > 0) $rincianKeuangan .= "✅ Asuransi: Rp " . $fmt($insurance_cost) . "\n";
                if($cod_fee > 0) $rincianKeuangan .= "✅ Biaya Layanan: Rp " . $fmt($cod_fee) . "\n";

            } else {
                // KASUS: TRANSFER / SALDO
                $adminInstruction = "✅ *INSTRUKSI: NON-COD (SUDAH LUNAS)*";

                $rincianKeuangan .= "☑️ Harga Barang: Rp " . $fmt($data['item_price']) . "\n";
                $rincianKeuangan .= "☑️ Ongkir: Rp " . $fmt($shipping_cost) . "\n";
                $rincianKeuangan .= "Note: Paket langsung serahkan, jangan menagih uang.";
            }

            // 3. Susun Pesan Akhir
            $message = "*🔔 PESANAN BARU MASUK (WEB)*\n";
            $message .= "----------------------------------\n";
            $message .= "🆔 Invoice: *{$order->nomor_invoice}*\n";
            $message .= "📦 Resi: *{$order->resi}*\n";
            $message .= "👤 Customer: {$data['sender_name']} ({$data['sender_phone']})\n";
            $message .= "📍 Tujuan: {$data['receiver_district']}, {$data['receiver_regency']}\n\n";

            $message .= "📦 *Item:* {$data['item_description']}\n";
            $message .= "⚖️ *Berat:* {$data['weight']} gram\n";
            $message .= "🚛 *Ekspedisi:* {$data['expedition']} ({$data['service_type']})\n\n";

            $message .= "💰 *RINCIAN KEUANGAN*\n";
            $message .= "Metode: *{$data['payment_method']}*\n";
            $message .= $rincianKeuangan;
            $message .= "----------------------------------\n";
            $message .= "*TOTAL TAGIHAN KE CUSTOMER:*\n";
            $message .= "*Rp " . $fmt($total_invoice) . "*\n";
            $message .= "----------------------------------\n";
            $message .= $adminInstruction . "\n\n";

            $message .= "Link: " . route('admin.pesanan.index');

            // 4. Kirim ke Semua Nomor Admin
            foreach ($adminNumbers as $number) {
                // Format nomor HP (ubah 08 jadi 628 jika perlu, Fonnte biasanya otomatis tapi lebih aman manual)
                $waTarget = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $number));
                \App\Services\FonnteService::sendMessage($waTarget, $message);
            }

        } catch (\Exception $e) {
            // Error handling silent agar pesanan tetap terbuat meskipun WA gagal
            \Illuminate\Support\Facades\Log::error('WA Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * FUNGSI KHUSUS: Kirim Resi via WhatsApp API (Dipanggil AJAX dari View Cetak Resi)
     * Menangani request dari tombol "Kirim WA (Penerima/Pengirim)"
     */
    public function sendResiViaWhatsappApi(Request $request)
    {
        try {
            // 1. Validasi Input dari AJAX
            $request->validate([
                'resi'   => 'required|string',
                'target' => 'required|in:sender,receiver' // Target: Pengirim atau Penerima
            ]);

            $resi = $request->input('resi');
            $target = $request->input('target');

            // 2. Cari Pesanan Berdasarkan Resi (Bukan ID)
            $pesanan = Pesanan::where('resi', $resi)->first();

            if (!$pesanan) {
                return response()->json(['status' => 'error', 'message' => 'Data pesanan dengan resi tersebut tidak ditemukan.']);
            }

            // 3. Tentukan Data Target (Pengirim atau Penerima)
            $targetName = '';
            $targetPhoneRaw = '';
            $roleName = '';

            if ($target === 'receiver') {
                $targetName = $pesanan->receiver_name;
                $targetPhoneRaw = $pesanan->receiver_phone;
                $roleName = 'Penerima';
            } else {
                $targetName = $pesanan->sender_name;
                $targetPhoneRaw = $pesanan->sender_phone;
                $roleName = 'Pengirim';
            }

            // 4. Sanitize Nomor HP
            $targetPhone = $this->_sanitizePhoneNumber($targetPhoneRaw);

            // 5. Susun Pesan WhatsApp
            // Menggunakan route tracking publik jika ada, atau fallback ke link biasa
            $trackingLink = route('tracking.search', ['resi' => $pesanan->resi]);

            $message = "Halo Kak {$targetName} ({$roleName}) 👋,\n\n";
            $message .= "Berikut adalah *Soft Copy Resi* untuk paket Anda:\n\n";
            $message .= "📜 No. Invoice: *{$pesanan->nomor_invoice}*\n";
            $message .= "📦 No. Resi: *{$pesanan->resi}*\n";
            $message .= "🚚 Ekspedisi: {$pesanan->expedition} ({$pesanan->service_type})\n";
            $message .= "⚖️ Berat: {$pesanan->weight} Gram\n\n";
            $message .= "🔍 *Lacak status paket secara real-time di sini:*\n";
            $message .= "{$trackingLink}\n\n";
            $message .= "Simpan bukti resi ini ya Kak. Terima kasih telah menggunakan Sancaka Express! 🙏";

            // 6. Kirim via Fonnte Service
            $response = \App\Services\FonnteService::sendMessage($targetPhone, $message);

            // 7. Return JSON ke JavaScript
            // Cek respons Fonnte (biasanya mengembalikan JSON string atau array)
            // Kita asumsikan berhasil jika tidak error exception
            return response()->json([
                'status' => 'success',
                'message' => "Resi berhasil dikirim ke WhatsApp {$roleName} ({$targetName}).",
                'debug' => $response
            ]);

        } catch (\Exception $e) {
            Log::error("API WA Resi Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pengiriman: ' . $e->getMessage()
            ], 500);
        }
    }

   /**
     * Endpoint API untuk menyimpan/update kontak secara manual dari formulir pemesanan.
     */
    public function saveContactApi(Request $request)
    {
        try {
            // 1. Validasi Input
            $validated = $request->validate([
                'prefix' => 'required|in:sender,receiver',
                'id' => 'nullable|integer',

                // Data Input
                'sender_name' => 'nullable|string|max:100',
                'sender_phone' => 'nullable|string|max:20',
                'sender_address' => 'nullable|string|min:10|max:500',
                'sender_province' => 'nullable|string|max:100',
                'sender_regency' => 'nullable|string|max:100',
                'sender_district' => 'nullable|string|max:100',
                'sender_village' => 'nullable|string|max:100',
                'sender_postal_code' => 'nullable|string|max:10',

                'receiver_name' => 'nullable|string|max:100',
                'receiver_phone' => 'nullable|string|max:20',
                'receiver_address' => 'nullable|string|min:10|max:500',
                'receiver_province' => 'nullable|string|max:100',
                'receiver_regency' => 'nullable|string|max:100',
                'receiver_district' => 'nullable|string|max:100',
                'receiver_village' => 'nullable|string|max:100',
                'receiver_postal_code' => 'nullable|string|max:10',
            ]);

            $prefix = $request->input('prefix');
            $tipe = ($prefix === 'sender') ? 'Pengirim' : 'Penerima';

            // 2. Cek kelengkapan data dasar
            $nameKey = "{$prefix}_name";
            $phoneKey = "{$prefix}_phone";
            $addressKey = "{$prefix}_address";

            if (empty($request->input($nameKey)) || empty($request->input($phoneKey)) || empty($request->input($addressKey))) {
                 return response()->json(['status' => 'error', 'message' => "Data {$tipe} (Nama, HP, atau Alamat) tidak boleh kosong."], 422);
            }

            // 3. Persiapkan Data
            $data = $request->all();
            $data['user_id'] = Auth::id(); // Paksa User ID
            $data["save_{$prefix}"] = 'on';

            // 4. Simpan
            $savedContact = $this->_saveOrUpdateKontak($data, $prefix, $tipe);

            $contactId = null;
            if ($savedContact && isset($savedContact->id)) {
                $contactId = $savedContact->id;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Kontak {$tipe} berhasil disimpan ke Buku Alamat!",
                'contact_id' => $contactId
            ]);

        } catch (ValidationException $e) {
            Log::warning('saveContactApi Validation Failed:', $e->errors());
            $firstError = collect($e->errors())->first()[0] ?? 'Input tidak valid.';
            return response()->json(['status' => 'error', 'message' => $firstError], 422);

        } catch (\Illuminate\Database\QueryException $e) {
            // --- INI PERBAIKANNYA ---
            // Menangkap error Database (Duplicate Entry)
            $errorCode = $e->errorInfo[1];

           if ($errorCode == 1062) { // Error Duplicate
                // CARI KONTAK YANG SUDAH ADA ITU
                // Kita cari berdasarkan User ID dan No HP yang dikirim
                $prefix = $request->input('prefix');
                $phoneInput = $request->input($prefix . '_phone');

                // Bersihkan nomor HP dulu biar cocok pencariannya
                $sanitizedPhone = $this->_sanitizePhoneNumber($phoneInput);

                $existingContact = \App\Models\Kontak::where('user_id', \Illuminate\Support\Facades\Auth::id())
                    ->where('no_hp', $sanitizedPhone)
                    ->first();

                return response()->json([
                    'status' => 'error',
                    'code'   => 'duplicate', // Kode khusus untuk JS
                    'message' => 'Nomor HP sudah terdaftar. Data formulir telah disinkronkan dengan kontak yang ada.',
                    'existing_contact' => $existingContact // <--- INI DATA PENTINGNYA
                ], 422);
            }

            Log::error('saveContactApi SQL Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan database.'], 500);

        } catch (\Exception $e) {
            Log::error('saveContactApi General Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }

/**
 * Menampilkan halaman cetak thermal berdasarkan Resi atau Nomor Invoice.
 * URL: tokosancaka.com/{resi}/cetak_thermal
 */
public function cetakThermal($resi)
{
    // Cari data berdasarkan resi, jika tidak ada cari berdasarkan nomor invoice
    $order = \App\Models\Pesanan::where('resi', $resi)
                ->orWhere('nomor_invoice', $resi)
                ->orWhere('shipping_ref', $resi) // <--- TAMBAHAN PENTING
                ->firstOrFail();

    // Mengarahkan ke view cetak yang sudah ada
    // Sesuaikan path view ini dengan lokasi file blade cetak Anda
    return view('admin.pesanan.cetak_thermal', compact('order'));
}

 /**
     * HELPER: Simpan Transaksi Keuangan (HANYA JIKA STATUS SUKSES)
     * Cash Basis: Pencatatan dilakukan saat paket benar-benar selesai.
     */
    // Hapus 'private' dan '_', ganti jadi 'public static'
    public static function simpanKeKeuangan(Pesanan $order)
    {
        try {
            // ==========================================================
            // 1. VALIDASI STATUS (GATEKEEPER)
            // ==========================================================
            // Daftar status yang dianggap "Uang Masuk / Transaksi Selesai"
            $statusSukses = [
                'Selesai',
                'Terkirim',
                'Delivered',
                'Success',
                'Berhasil',
                'Finished'
            ];

            // Normalisasi status database agar huruf besar/kecil tidak masalah
            $currentStatus = ucwords(strtolower($order->status_pesanan));

            // JIKA STATUS BELUM SUKSES -> STOP / JANGAN SIMPAN
            if (!in_array($currentStatus, $statusSukses)) {
                return;
            }

            // ==========================================================
            // 2. HITUNG DISKON & PROFIT
            // ==========================================================
            $ekspedisiRules = DB::table('Ekspedisi')->get();
            $diskonPersen = 0;
            $expStr = strtolower($order->expedition);

            foreach ($ekspedisiRules as $rule) {
                if (str_contains($expStr, strtolower($rule->keyword))) {
                    $rules = json_decode($rule->diskon_rules, true);
                    if (is_array($rules)) {
                        foreach ($rules as $key => $val) {
                            if ($key !== 'default' && str_contains($expStr, $key)) {
                                $diskonPersen = $val;
                                break 2;
                            }
                        }
                        if (isset($rules['default'])) $diskonPersen = $rules['default'];
                    }
                    break;
                }
            }

            // ==========================================================
            // 3. HITUNG NOMINAL
            // ==========================================================
            $ongkirPublish = (float) $order->shipping_cost; // Omzet
            $nilaiDiskon   = $ongkirPublish * $diskonPersen;  // Profit
            $modalReal     = $ongkirPublish - $nilaiDiskon;   // Beban Pokok

            // Validasi nominal
            if ($ongkirPublish <= 0) return;

            // Pastikan Resi Ada (Karena status sudah selesai, resi pasti ada)
            $resiFinal = $order->resi ?? $order->nomor_invoice;

            // ==========================================================
            // 4. EKSEKUSI PENYIMPANAN (UPDATE OR CREATE)
            // ==========================================================
            // Menggunakan updateOrCreate agar jika webhook terkirim 2x, data tidak dobel.

            // A. PEMASUKAN (Pendapatan Jasa)
            Keuangan::updateOrCreate(
                [
                    // Kunci Unik (Syarat agar tidak dobel)
                    'nomor_invoice' => $order->nomor_invoice,
                    'jenis'         => 'Pemasukan',
                    'kategori'      => 'Pendapatan Jasa Pengiriman'
                ],
                [
                    // Data yang diupdate/disimpan
                    'kode_akun'     => '4101', // Akun Pendapatan
                    'tanggal'       => now()->toDateString(), // Tanggal SELESAI (bukan tanggal order)
                    'unit_usaha'    => 'Ekspedisi',
                    'keterangan'    => "Pendapatan Resi: " . $resiFinal . " (" . $order->expedition . ")",
                    'jumlah'        => $ongkirPublish,
                    'updated_at'    => now()
                ]
            );

            // B. PENGELUARAN (Beban/Modal ke Pusat)
            Keuangan::updateOrCreate(
                [
                    // Kunci Unik
                    'nomor_invoice' => $order->nomor_invoice,
                    'jenis'         => 'Pengeluaran',
                    'kategori'      => 'Beban Ekspedisi'
                ],
                [
                    // Data yang diupdate/disimpan
                    'kode_akun'     => '5101', // Akun Beban
                    'tanggal'       => now()->toDateString(),
                    'unit_usaha'    => 'Ekspedisi',
                    'keterangan'    => "Setor Modal ke Pusat: " . $resiFinal . " (Profit " . ($diskonPersen * 100) . "%)",
                    'jumlah'        => $modalReal,
                    'updated_at'    => now()
                ]
            );

            Log::info("Keuangan CASH BASIS Tersimpan: Invoice {$order->nomor_invoice} | Status: {$currentStatus}");

        } catch (Exception $e) {
            Log::error('Keuangan Error:', ['invoice' => $order->nomor_invoice, 'msg' => $e->getMessage()]);
        }
    }

    // Tambahkan di dalam class PesananController (Customer)
    public function getTripayChannels()
    {
        // 1. Ambil Mode dari Database
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // 2. Cache Key khusus Customer
        $cacheKey = 'tripay_channels_customer_' . $mode;

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
                return [];
            }
            return [];
        });

        return response()->json(['success' => true, 'data' => $channels]);
    }

    /**
     * Menampilkan halaman Riwayat Belanja.
     */
    public function riwayat()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Ambil ID User (Prioritas id_pengguna, fallback ke id)
        $customerId = $user->id_pengguna ?? $user->id;

        // Ambil data pesanan milik user ini
        // Menggunakan logic yang sama amannya dengan DashboardController kemarin
        $pesanans = \App\Models\Pesanan::where('id_pengguna_pembeli', $customerId)
                            ->latest() // Urutkan dari yang terbaru
                            ->paginate(10); // Batasi 10 per halaman

        // Cek apakah view khusus riwayat sudah ada?
        // Jika file 'resources/views/customer/pesanan/riwayat.blade.php' belum Anda buat,
        // kita arahkan sementara ke view index (tabel pesanan biasa) agar tidak error.
        if (view()->exists('customer.pesanan.riwayat')) {
            return view('customer.pesanan.riwayat', compact('pesanans'));
        }

        // Fallback: Pakai tampilan tabel pesanan biasa
        return view('customer.pesanan.index', compact('pesanans'));
    }

    /**
     * Menampilkan halaman Riwayat Belanja Marketplace.
     */
    public function riwayatBelanja()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Ambil ID User (Prioritas id_pengguna, fallback ke id)
        $customerId = $user->id_pengguna ?? $user->id;

        // Ambil data dari tabel orders (Marketplace)
        // Kita load relasi 'store' dan 'items' agar tidak error N+1 di view
        $pesanans = \App\Models\Order::where('user_id', $customerId)
                    // TAMBAHKAN 'store.user' DISINI AGAR DATA PENGGUNA TERAMBIL
                    ->with(['store.user', 'items.product.images', 'items.variant'])
                    ->latest()
                    ->paginate(10);

        // Arahkan ke file view baru: riwayat_belanja.blade.php
        return view('customer.pesanan.riwayat_belanja', compact('pesanans'));
    }

    public function show($id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userId = $user->id_pengguna ?? $user->id;

        // 1. Cek di tabel 'orders' (Marketplace Baru) -> Pakai 'id'
        $order = \App\Models\Order::where('id', $id)
                    ->where('user_id', $userId)
                    ->first();

        // 2. Jika tidak ketemu, cek tabel 'Pesanan' (Legacy) -> Pakai 'id_pesanan'
        if (!$order) {
            // PERBAIKAN: Ganti 'id' menjadi 'id_pesanan'
            $order = \App\Models\Pesanan::where('id_pesanan', $id)
                        ->where('id_pengguna_pembeli', $userId)
                        ->firstOrFail();

            $invoiceNumber = $order->nomor_invoice;
        } else {
            $invoiceNumber = $order->invoice_number;
        }

        // 3. Redirect ke Invoice
        return redirect()->route('checkout.invoice', ['invoice' => $invoiceNumber]);
    }

}
