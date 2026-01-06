<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Logging aktif
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- Pastikan baris ini ada di paling atas file

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\Affiliate;
use App\Models\Store; 
use App\Models\TopUp;
use App\Models\User;

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;

class OrderController extends Controller
{
    // --- TEMPEL KODE index() DI SINI ---
    public function index()
    {
        $orders = Order::orderBy('created_at', 'desc')->paginate(10);
        return view('orders.index', compact('orders'));
    }
    
    /**
     * Menampilkan Halaman Kasir (POS)
     */
    public function create(Request $request)
    {
        $products = Product::where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        $customers = Affiliate::orderBy('name', 'asc')
                              ->get()
                              ->map(function($aff) {
                                  $aff->saldo = 0; 
                                  $aff->affiliate_balance = $aff->balance; 
                                  $aff->has_pin = !empty($aff->pin); 
                                  return $aff;
                              });

        $autoCoupon = $request->query('coupon');

        return view('orders.create', compact('products', 'customers', 'autoCoupon'));
    }

    /**
     * API: Pencarian Lokasi (Kecamatan/Kelurahan)
     */
    public function searchLocation(Request $request, KiriminAjaService $kiriminAja)
    {
        $keyword = $request->query('query');

        if (empty($keyword) || strlen($keyword) < 3) {
            return response()->json(['status' => 'error', 'data' => []]);
        }

        try {
            $response = $kiriminAja->searchAddress($keyword);

            if (isset($response['status']) && $response['status'] == true) {
                return response()->json([
                    'status' => 'success',
                    'data'   => $response['data']
                ]);
            }
            return response()->json(['status' => 'error', 'data' => []]);

        } catch (\Exception $e) {
            Log::error("Search Location Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

   /**
     * Fungsi Geocode "Smart Filter"
     * Mencari prioritas tipe 'village' agar koordinat akurat di tengah desa.
     */
    private function geocode(string $address): ?array
    {
        // 1. Bersihkan query (Hapus koma agar pencarian lebih fleksibel)
        $cleanAddress = str_replace(',', ' ', $address); 
        $cleanAddress = preg_replace('/\s+/', ' ', $cleanAddress); // Hapus spasi ganda

        Log::info("GEOCODING QUERY: $cleanAddress");

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'AplikasiKasirSancaka/1.0'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q'            => $cleanAddress,
                    'format'       => 'json',
                    'limit'        => 5, // Ambil 5 opsi untuk difilter
                    'countrycodes' => 'id',
                    'addressdetails' => 1
                ]);
            
            if ($response->successful()) {
                $results = $response->json();
                
                // === FILTER PINTAR (SMART FILTERING) ===
                $selectedPlace = null;

                // PRIORITAS 1: Cari yang tipe-nya 'village' (Desa/Kelurahan)
                // Ini yang paling akurat untuk ongkir (biasanya titik tengah desa)
                foreach ($results as $place) {
                    if (isset($place['type']) && $place['type'] === 'village') {
                        $selectedPlace = $place;
                        Log::info("GEOCODING: Mengambil prioritas 'VILLAGE' -> " . ($place['display_name'] ?? ''));
                        break; // Ketemu desa, stop looping!
                    }
                }

                // PRIORITAS 2: Jika Desa tidak ketemu, cari 'administrative', 'city', atau 'town'
                if (!$selectedPlace) {
                    foreach ($results as $place) {
                        if (isset($place['type']) && in_array($place['type'], ['administrative', 'city', 'town', 'residential'])) {
                            $selectedPlace = $place;
                            Log::info("GEOCODING: Mengambil prioritas 'ADMINISTRATIVE/CITY'");
                            break;
                        }
                    }
                }

                // PRIORITAS 3: Fallback (Ambil data pertama apapun itu)
                if (!$selectedPlace && isset($results[0])) {
                    $selectedPlace = $results[0];
                    Log::info("GEOCODING: Mengambil hasil teratas (Tanpa Filter Khusus)");
                }

                // Return Hasil Akhir
                if ($selectedPlace) {
                    $lat = (float) $selectedPlace['lat'];
                    $lng = (float) $selectedPlace['lon']; // API Nominatim pakai key 'lon'
                    
                    Log::info("GEOCODING FINAL: $lat, $lng");
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
        } catch (\Exception $e) {
            Log::error("GEOCODING ERROR: " . $e->getMessage());
        }
        return null;
    }

    /**
     * CEK ONGKIR (Menggabungkan Reguler & Instant dengan Lat/Long)
     */
    public function checkShippingRates(Request $request, KiriminAjaService $kiriminAja)
    {
        Log::info('CEK ONGKIR REQUEST:', $request->all());

        $request->validate([
            'weight' => 'required|numeric',
            'destination_district_id' => 'required',
            'destination_subdistrict_id' => 'nullable',
        ]);

        try {
            // 1. DEFINISI MAPPING LOGO KURIR
            $courierMap = [
                'gojek'    => ['name' => 'GoSend',      'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'grab'     => ['name' => 'GrabExpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                'jne'      => ['name' => 'JNE',         'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                'jnt'      => ['name' => 'J&T Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'sicepat'  => ['name' => 'SiCepat',     'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                'anteraja' => ['name' => 'Anteraja',    'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'posindonesia'      => ['name' => 'POS Indonesia','logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'tiki'     => ['name' => 'TIKI',        'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                'lion'     => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'ninja'    => ['name' => 'Ninja Express','logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'idx'      => ['name' => 'ID Express',  'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'spx'      => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'sap'      => ['name' => 'SAP Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                'ncs'      => ['name' => 'NCS Kurir',   'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'jtcargo'  => ['name' => 'J&T Cargo',   'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'borzo'    => ['name' => 'Borzo',       'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
            ];

            // 2. CONFIG
            $originDistrict    = config('services.kiriminaja.origin_district_id'); 
            $originSubDistrict = config('services.kiriminaja.origin_subdistrict_id');
            
            $originLat = config('services.kiriminaja.origin_lat'); 
            $originLng = config('services.kiriminaja.origin_long');
            $originAddr = config('services.kiriminaja.origin_address', 'Toko Sancaka');

            Log::info("CONFIG ORIGIN: Lat: $originLat, Lng: $originLng");

            // 3. DATA TUJUAN
            $destDistrict    = $request->destination_district_id;
            // [PENTING] Ambil Kelurahan. Jika tidak ada, set 0 (tapi sebaiknya ada untuk POS)
            $destSubDistrict = $request->destination_subdistrict_id ? (int)$request->destination_subdistrict_id : 0; 
            
            Log::info("CEK ONGKIR: District: $destDistrict, SubDistrict (Kelurahan): $destSubDistrict");
            // --- LOGIKA GEOCODING TUJUAN UNTUK INSTANT ---
            $destLat = null;
            $destLng = null;
            
            if ($request->filled('destination_text')) {
                $geo = $this->geocode($request->destination_text);
                if ($geo) {
                    $destLat = $geo['lat'];
                    $destLng = $geo['lng'];
                }
            }

            $formattedRates = [];
            // 3. REQUEST API REGULER (Kirim parameter subdistrict)
            Log::info("Mengambil Ongkir REGULER (Include Kelurahan)...");

            // A. API REGULER (JNE, SICEPAT, DLL)
            Log::info("Mengambil Ongkir REGULER...");
            $responseReguler = $kiriminAja->getExpressPricing(
                (int) $originDistrict, (int) $originSubDistrict, 
                (int) $destDistrict, (int) $destSubDistrict,   
                (int) $request->weight,
                10, 10, 10, 1000, [], 'regular', 0
            );

            if (isset($responseReguler['status']) && $responseReguler['status'] == true) {
                $results = $responseReguler['results'] ?? [];
                Log::info("Ongkir REGULER Sukses: " . count($results) . " kurir ditemukan.");
                
                foreach ($results as $rate) {
                    $serviceCode = strtolower($rate['service']);
                    $mapData = $courierMap[$serviceCode] ?? null;
                    
                    $formattedRates[] = [
                        'code'    => 'kiriminaja',
                        'name'    => $mapData ? $mapData['name'] : strtoupper($serviceCode), 
                        'logo'    => $mapData ? $mapData['logo_url'] : null, 
                        'service' => $rate['service_name'] ?? 'Layanan', 
                        'cost'    => (int) $rate['cost'], 
                        'etd'     => $rate['etd'] ?? '-',
                        // === CRITICAL ADDITION ===
                        'courier_code' => $rate['service'], // e.g., 'jne', 'sicepat' - This was missing!
                        'service_type' => $rate['service_type'],
                    ];
                }
            } else {
                Log::warning("Ongkir REGULER Gagal/Kosong", ['response' => $responseReguler]);
            }

            // B. API INSTANT (GOJEK/GRAB)
            if ($destLat && $destLng && $originLat && $originLng) {
                Log::info("Mencoba Ongkir INSTANT (Grab/Gojek)...");
                Log::info("Route: $originLat,$originLng -> $destLat,$destLng");

                // 1. PANGGIL FUNGSI DULU SAMPAI SELESAI (JANGAN DISELIPIN LOG DI DALAMNYA)
                $responseInstant = $kiriminAja->getInstantPricing(
                    (float) $originLat, 
                    (float) $originLng, 
                    $originAddr,
                    (float) $destLat, 
                    (float) $destLng, 
                    $request->destination_text,
                    (int) $request->weight,
                    1000, 
                    'motor',
                    ['gosend', 'grab_express']
                ); // <--- Tanda kurung tutup & titik koma ini WAJIB ada dulu

                // 2. BARU LOG HASILNYA DISINI (DI BAWAHNYA)
                Log::info("RESPONSE MENTAH INSTANT:", ['body' => $responseInstant]);

                // --- UPDATE PARSING JSON SESUAI LOG TERBARU ---
                // PERBAIKAN: Ambil langsung dari $responseInstant
                $bodyResponse = $responseInstant;
                
                if (isset($bodyResponse['status']) && $bodyResponse['status'] == true) {
                    $instantResults = $bodyResponse['result'] ?? []; 
                    
                    Log::info("Ongkir INSTANT Raw Result Count: " . count($instantResults));

                    // Filter: Jika POS Indonesia muncul, pastikan logonya ada
                    if ($serviceCode === 'pos' || $serviceCode === 'posindonesia') {
                        $mapData = ['name' => 'POS Indonesia', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'];
                    }

                    foreach ($instantResults as $courierData) {
                        $courierName = strtolower($courierData['name'] ?? 'instant'); // gosend / grab
                        $costs = $courierData['costs'] ?? [];

                        foreach ($costs as $costData) {
                            $priceData = $costData['price'] ?? [];
                            $totalPrice = $priceData['total_price'] ?? 0;

                            if ($totalPrice > 0) {
                                // Mapping Logo Manual (karena API tidak kasih logo)
                                $logoUrl = null;
                                if(str_contains($courierName, 'go')) $logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png';
                                if(str_contains($courierName, 'grab')) $logoUrl = 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png';

                                $formattedRates[] = [
                                    'code'    => 'kiriminaja_instant',
                                    'name'    => strtoupper($courierName), // GOSEND / GRAB
                                    'logo'    => $logoUrl,
                                    'service' => 'Instant (' . ($costData['service_type'] ?? 'Motor') . ')',
                                    'cost'    => (int) $totalPrice,
                                    'etd'     => $costData['estimation'] ?? 'Instant',
                                ];
                                Log::info("INSTANT ADDED: $courierName - Rp $totalPrice");
                            }
                        }
                    }
                } else {
                    Log::warning("Ongkir INSTANT Gagal/Tidak Ada Driver", ['response' => $responseInstant]);
                }

            } else {
                Log::info("Ongkir INSTANT Skipped: Koordinat tidak lengkap.");
            }

            // C. GABUNGKAN HASIL & SORTIR
            usort($formattedRates, function ($a, $b) {
                return $a['cost'] <=> $b['cost'];
            });

            Log::info("TOTAL Ongkir Ditampilkan: " . count($formattedRates));

            return response()->json(['status' => 'success', 'data' => $formattedRates]);

        } catch (\Exception $e) {
            Log::error('Controller Ongkir Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function store(Request $request, DokuJokulService $dokuService, KiriminAjaService $kiriminAja)
    {
        // [LOG 1] Cek Input Mentah dari Frontend
        Log::info('================ START ORDER STORE ================');
        Log::info('RAW REQUEST:', $request->all());

        // 1. SETUP VARIABEL
        $customerNote = $request->input('customer_note'); 
        $catatanSistem = '';                      
        
        Log::info('Customer Note (Input Manual): ' . $customerNote);

        // Ambil input asli
        $inputMethod = $request->payment_method;
        $custId      = $request->customer_id;

        Log::info("Cek Metode Bayar Awal: {$inputMethod} | Customer ID: {$custId}");

        // [LOGIKA PENYELAMAT]
        if ($inputMethod === 'affiliate_balance' && empty($custId)) {
            Log::warning('⚠️ AUTO-FIX TRIGGERED: Metode Saldo Member tapi ID Kosong -> Ubah ke CASH');
            $metodeBayarFix = 'cash';
        } else {
            $metodeBayarFix = $inputMethod;
            Log::info("Metode Bayar Valid (Tidak berubah): {$metodeBayarFix}");
        }

        // 2. VALIDASI
        Log::info('Start Validasi Laravel...');
        $request->validate([
            'items'                   => 'required', 
            'total'                   => 'required|numeric',
            'delivery_type'           => 'required|in:pickup,shipping',
            'shipping_cost'           => 'required_if:delivery_type,shipping|numeric',
            'courier_name'            => 'nullable|string|required_if:delivery_type,shipping',
            'destination_text'        => 'nullable|string', 
            'destination_district_id' => 'nullable|required_if:delivery_type,shipping',
            // Validasi nama/phone jika shipping guest
            'customer_name' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
            'customer_phone' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
        ]);
        Log::info('Validasi Laravel Lolos.');

        $cartItems = json_decode($request->items, true);
        if (!is_array($cartItems) || count($cartItems) < 1) {
            Log::error('Keranjang Kosong / Invalid JSON');
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong.'], 400);
        }

        DB::beginTransaction();
        Log::info('Database Transaction Started.');

        try {
            $subtotal = 0;
            $finalCart = []; 
            $totalWeight = 0;

            // 3. CEK STOK & HITUNG TOTAL
            Log::info('Start Loop Produk...');
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['id']);
                
                if (!$product) {
                    Log::error("Produk ID {$item['id']} tidak ditemukan di DB.");
                    throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                }
                
                Log::info("Cek Produk: {$product->name} | Stok DB: {$product->stock} | Request Qty: {$item['qty']}");

                if ($product->stock < $item['qty']) throw new \Exception("Stok '{$product->name}' kurang.");

                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;
                
                $weightPerItem = ($product->weight > 0) ? $product->weight : 5; 
                $totalWeight += ($weightPerItem * $item['qty']);

                $finalCart[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            if ($totalWeight < 1000) $totalWeight = 1000;
            Log::info("Total Berat Final: {$totalWeight} gram | Subtotal: {$subtotal}");

            // 4. DISKON KUPON
            $discount = 0;
            $couponId = null;
            if ($request->coupon) {
                Log::info("Cek Kupon: " . $request->coupon);
                $couponDB = Coupon::where('code', $request->coupon)->first();
                if ($couponDB && $couponDB->is_active) {
                    $couponId = $couponDB->id;
                    $discount = ($couponDB->type == 'percent') ? $subtotal * ($couponDB->value / 100) : $couponDB->value;
                    $couponDB->increment('used_count');
                    Log::info("Kupon Valid. Diskon: {$discount}");
                }
            }

            // 1. Hitung Harga Barang setelah Diskon
            $hargaSetelahDiskon = max(0, $subtotal - $discount);

            // 2. Ambil Biaya Ongkir dari Input
            $biayaOngkir = ($request->delivery_type === 'shipping') ? (int)$request->shipping_cost : 0;

            // 3. [FIX] TOTAL FINAL = (Barang - Diskon) + ONGKIR
            $finalPrice = $hargaSetelahDiskon + $biayaOngkir; 
            
            Log::info("Final Price Calculation: ($subtotal - $discount) + $biayaOngkir = $finalPrice");

            // 5. DATA CUSTOMER
            $paymentStatus = 'unpaid';
            $paymentUrl    = null;    
            $changeAmount  = 0;     
            $customerName  = $request->customer_name ?? 'Customer Umum';
            $customerPhone = $request->customer_phone ?? '08819435180';
            $customerEmail = 'tokosancaka@gmail.com'; 
            
             if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name; 
                    $customerPhone = $affiliateMember->whatsapp;
                    if (!empty($affiliateMember->email)) $customerEmail = $affiliateMember->email;
                    Log::info("Data Member Ditemukan: {$customerName}");
                } else {
                    Log::warning("Customer ID {$request->customer_id} dikirim tapi tidak ditemukan di DB.");
                }
            }

            // 6. PROSES PEMBAYARAN
            Log::info("Masuk Switch Case Pembayaran. Metode: {$metodeBayarFix}");
            
             switch ($metodeBayarFix) {
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    Log::info("Bayar Tunai. Uang Masuk: {$cashReceived}");
                    
                    if ($cashReceived < $finalPrice) throw new \Exception("Uang tunai kurang!");
                    
                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; 
                    $catatanSistem = "[INFO KASIR] Tunai. Terima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;

                case 'affiliate_balance':
                    Log::info("Bayar Saldo Member. ID Member: " . $request->customer_id);
                    
                    if (!$request->customer_id) {
                        Log::error("FATAL: Masuk case affiliate_balance tapi customer_id kosong!");
                        throw new \Exception("Sistem Error: Member tidak terdeteksi.");
                    }
                    
                    $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);
                    
                    // Cek PIN
                    if (!$affiliatePayor || !Hash::check($request->affiliate_pin, $affiliatePayor->pin)) {
                        Log::error("PIN Salah untuk member ID: " . $request->customer_id);
                        throw new \Exception("PIN Salah!");
                    }
                    
                    // Cek Saldo
                    if ($affiliatePayor->balance < $finalPrice) {
                        Log::error("Saldo Kurang. Saldo: {$affiliatePayor->balance}, Tagihan: {$finalPrice}");
                        throw new \Exception("Saldo Kurang.");
                    }
                    
                    $affiliatePayor->decrement('balance', $finalPrice);
                    $paymentStatus = 'paid'; 
                    $catatanSistem = "[INFO BAYAR] Potong Saldo Member";
                    Log::info("Potong Saldo Berhasil. Sisa Saldo: " . $affiliatePayor->balance);
                    break;

                case 'tripay':
                case 'doku':
                    Log::info("Bayar Payment Gateway: {$metodeBayarFix}");
                    $paymentStatus = 'unpaid';
                    break;
            }

            // 7. GENERATE ORDER NUMBER
            $orderNumber = 'SCK-' . date('ymdHis') . rand(100, 999);
            $shippingRef = null;
            Log::info("Generated Order Number: {$orderNumber}");

            // 8. INTEGRASI KIRIMINAJA
            if ($request->delivery_type === 'shipping') {
                Log::info("Delivery Type: Shipping. Preparing API...");
                
                $destLat = null; $destLng = null;
                if ($request->filled('destination_text')) {
                    $geo = $this->geocode($request->destination_text);
                    if ($geo) {
                        $destLat = (string)$geo['lat'];
                        $destLng = (string)$geo['lng'];
                        Log::info("Geocoding Success: {$destLat}, {$destLng}");
                    } else {
                        Log::warning("Geocoding Failed for: " . $request->destination_text);
                    }
                }

                $serviceCodeCheck = strtolower($request->courier_service ?? $request->courier_name ?? ''); 
                $isInstant = (str_contains($serviceCodeCheck, 'gosend') || str_contains($serviceCodeCheck, 'grab'));
                $kaResponse = null;

                if ($isInstant) {
                    Log::info("Requesting INSTANT Courier...");
                    if (!$destLat || !$destLng) throw new \Exception("Gagal Instant: Koordinat tujuan tidak ditemukan.");

                    $instantPayload = [
                        'order_id'   => $orderNumber,
                        'service'    => $serviceCodeCheck,
                        'item_price' => (int) $subtotal,
                        'origin'     => [
                            'lat'     => config('services.kiriminaja.origin_lat'),
                            'long'    => config('services.kiriminaja.origin_long'),
                            'address' => config('services.kiriminaja.origin_address'),
                            'phone'   => '085745808809',
                            'name'    => 'Toko Sancaka'
                        ],
                        'destination' => [
                            'lat'     => $destLat,
                            'long'    => $destLng,
                            'address' => $request->destination_text,
                            'phone'   => $customerPhone,
                            'name'    => $customerName
                        ],
                        'weight'  => (int) $totalWeight,
                        'vehicle' => 'motor',
                    ];
                    $kaResponse = $kiriminAja->createInstantOrder($instantPayload);

                } else { 
                    // [B] REGULER & KARGO
                    Log::info("Requesting REGULAR/CARGO Courier...");
                    
                    $serviceCode = $request->courier_code; 
                    $serviceType = $request->service_type;

                    // JIKA KODE KURIR KOSONG, KITA CARI DARI NAMANYA
                    if (empty($serviceCode) && $request->courier_name) {
                        $parts = explode('-', $request->courier_name); 
                        $namaKurir = strtolower(trim($parts[0])); // misal: "pos indonesia " -> "pos indonesia"
                        
                        // DAFTAR LENGKAP EKSPEDISI (URUTAN PENTING!)
                        // Taruh yang nama panjang/spesifik di atas
                        $mapManual = [
                            'j&t cargo'   => 'jtcargo',      // Cek ini dulu sebelum J&T biasa
                            'j&t'         => 'jnt',
                            'jne'         => 'jne',
                            'sicepat'     => 'sicepat',
                            'anteraja'    => 'anteraja',
                            'pos'         => 'posindonesia', // Menangkap "pos indonesia", "pos kilat"
                            'tiki'        => 'tiki',
                            'lion'        => 'lion',
                            'ninja'       => 'ninja',
                            'id express'  => 'idx',
                            'idx'         => 'idx',
                            'spx'         => 'spx',
                            'shopee'      => 'spx',
                            'sap'         => 'sap',
                            'ncs'         => 'ncs',          // NCS ada di log Anda
                            'paxel'       => 'paxel',
                            'rpx'         => 'rpx',
                            'sentral'     => 'sentral',
                            'star'        => 'star',         // Star Cargo
                            'borzo'       => 'borzo',
                            'gojek'       => 'gosend',
                            'grab'        => 'grab_express'
                        ];

                        foreach ($mapManual as $nameKey => $codeVal) {
                            if (str_contains($namaKurir, $nameKey)) { 
                                $serviceCode = $codeVal; 
                                Log::info("Mapping Manual Berhasil: '{$namaKurir}' -> '{$serviceCode}'");
                                break; 
                            }
                        }
                    }

                    // Fallback jika tidak ketemu juga
                    if (empty($serviceCode)) {
                        Log::warning("Kurir tidak dikenali: " . $request->courier_name . ". Default ke JNE.");
                        $serviceCode = 'jne'; 
                    }
                    
                    if (empty($serviceType)) $serviceType = 'REG';

                    $now = \Carbon\Carbon::now();
                    $pickupSchedule = $now->addMinutes(60)->format('Y-m-d H:i:s');
                    if ($now->isSunday() || $now->hour >= 14) {
                        $pickupSchedule = $now->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                    }

                    $declaredValue = max(1000, (int)$subtotal);
                    $fullAddress = $request->destination_text;
                    if ($request->filled('customer_address_detail')) {
                        $fullAddress = $request->customer_address_detail . " (" . $request->destination_text . ")";
                    }

                    // [UPDATE] Pastikan ID Kelurahan diambil
                    $destSubDistrictId = $request->destination_subdistrict_id ? (int)$request->destination_subdistrict_id : 0;

                    $kaPayload = [
                        'address'      => config('services.kiriminaja.origin_address'),
                        'phone'        => '085745808809',
                        'name'         => 'Toko Sancaka',
                        'kecamatan_id' => (int) config('services.kiriminaja.origin_district_id'),
                        'kelurahan_id' => (int) config('services.kiriminaja.origin_subdistrict_id'),
                        'zipcode'      => '63211', 
                        'schedule'     => $pickupSchedule, 
                        'platform_name'=> 'Sancaka Store',
                        'packages' => [
                            [
                                'order_id'                 => $orderNumber,
                                'destination_name'         => $customerName,
                                'destination_phone'        => $customerPhone,
                                'destination_address'      => $fullAddress,
                                'destination_kecamatan_id' => (int) $request->destination_district_id,
                                'destination_kelurahan_id' => (int) ($request->destination_subdistrict_id ?? 0),
                                'destination_zipcode'      => $request->postal_code ?? '00000',
                                'weight'           => (int) $totalWeight,
                                'width'            => 10, 'length' => 10, 'height' => 10, 'qty' => 1,
                                'item_value'       => $declaredValue, 
                                'shipping_cost'    => (int) $request->shipping_cost,
                                'insurance_amount' => 0,
                                'service'          => $serviceCode,
                                'service_type'     => $serviceType,
                                'package_type_id'  => 1, 
                                'item_name'        => 'Paket Order ' . $orderNumber,
                                'cod'              => 0,
                                'note'             => 'Handle with care',
                            ]
                        ]
                    ];

                    Log::info("Mencoba Request Pickup (SubDistrict: $destSubDistrictId)...", ['schedule' => $pickupSchedule]);
                    $kaResponse = $kiriminAja->createExpressOrder($kaPayload);

                    if (isset($kaResponse['status']) && $kaResponse['status'] == false) {
                        $errorMsg = strtolower($kaResponse['text'] ?? '');
                        if (str_contains($errorMsg, 'jadwal') || str_contains($errorMsg, 'schedule')) {
                            $newSchedule = \Carbon\Carbon::now()->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                            Log::warning("Jadwal Ditolak. Retry: {$newSchedule}");
                            $kaPayload['schedule'] = $newSchedule;
                            $kaResponse = $kiriminAja->createExpressOrder($kaPayload);
                        }
                    }
                } 

                Log::info("KiriminAja Response:", (array) $kaResponse);

                if (isset($kaResponse['status']) && $kaResponse['status'] == true) {
                    $shippingRef = $kaResponse['data']['order_id'] ?? $kaResponse['pickup_number'] ?? null;
                    $catatanSistem .= "\n[RESI OTOMATIS] Ref: " . $shippingRef;
                } else {
                    $errMsg = $kaResponse['text'] ?? json_encode($kaResponse);
                    throw new \Exception("Gagal Order Kurir: " . $errMsg);
                }
            }

            $fullAddressSaved = null;
            if ($request->delivery_type === 'shipping') {
                $detail = $request->customer_address_detail ?? '';
                $district = $request->destination_text ?? '';
                $fullAddressSaved = $detail . ' (' . $district . ')';
            }

            // 9. SIMPAN ORDER KE DATABASE
            Log::info("Mulai Insert Order ke DB...");
            
            // Susun data dulu biar bisa di-log
            $orderData = [
                'order_number'    => $orderNumber,
                'user_id'         => $request->customer_id ?? null, 
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $finalPrice,
                'payment_method'  => $metodeBayarFix, 
                'status'          => ($paymentStatus === 'paid') ? 'processing' : 'pending', 
                'payment_status'  => $paymentStatus,
                'note'            => $catatanSistem,
                'customer_note'   => $customerNote,
                'shipping_cost'   => $request->delivery_type === 'shipping' ? $request->shipping_cost : 0,
                'courier_service' => $request->delivery_type === 'shipping' ? $request->courier_name : null,
                'shipping_ref'    => $shippingRef,
                'destination_address' => $fullAddressSaved,
            ];

            Log::info("Data yang akan diinsert:", $orderData);

            try {
                $order = Order::create($orderData);
                Log::info("DATABASE SUKSES. Order ID: " . $order->id);
            } catch (\Exception $e) {
                Log::error("DATABASE SAVE GAGAL: " . $e->getMessage());
                throw new \Exception("Gagal menyimpan order ke database: " . $e->getMessage());
            }

            // 10. DETAIL ITEM
            foreach ($finalCart as $data) {
                $prod = $data['product'];
                OrderDetail::create([
                    'order_id'          => $order->id,
                    'product_id'        => $prod->id,
                    'product_name'      => $prod->name,    
                    'base_price_at_order' => $prod->base_price, 
                    'price_at_order'    => $prod->sell_price, 
                    'quantity'          => $data['qty'],
                    'subtotal'          => $data['subtotal'],
                ]);
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                if ($prod->stock <= 0) $prod->update(['stock_status' => 'unavailable']);
            }

            // 11. UPLOAD LAMPIRAN
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $details = $request->input('attachment_details', []); 

                Log::info("Mengupload " . count($files) . " lampiran...");

                foreach ($files as $index => $file) {
                    $path = $file->store('orders', 'public');
                    $meta = $details[$index] ?? [];
                    
                    OrderAttachment::create([
                        'order_id'   => $order->id,
                        'file_path'  => $path,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getClientMimeType(),
                        'color_mode' => $meta['color'] ?? 'BW',
                        'paper_size' => $meta['size'] ?? 'A4',
                        'quantity'   => $meta['qty'] ?? 1
                    ]);
                }
            }

            // 12. LINK PEMBAYARAN
            if ($metodeBayarFix === 'tripay') {
                Log::info("Membuat Tripay Transaction...");
                if (empty($request->payment_channel)) throw new \Exception("Harap pilih Channel Pembayaran.");
                $orderItems = [];
                foreach ($finalCart as $item) {
                    $orderItems[] = [
                        'sku'      => (string) $item['product']->id,
                        'name'     => $item['product']->name,
                        'price'    => (int) $item['product']->sell_price,
                        'quantity' => (int) $item['qty']
                    ];
                }
                $tripayRes = $this->_createTripayTransaction($order, $request->payment_channel, (int)$finalPrice, $customerName, $customerEmail, $customerPhone, $orderItems);
                if (!$tripayRes['success']) throw new \Exception("Tripay Gagal: " . ($tripayRes['message'] ?? 'Unknown Error'));
                $paymentUrl = $tripayRes['data']['checkout_url'];
                $order->update(['payment_url' => $paymentUrl]);
            } 
            elseif ($metodeBayarFix === 'doku') {
                Log::info("Membuat DOKU Payment...");
                $dokuData = ['name' => $customerName, 'email' => $customerEmail, 'phone' => $customerPhone];
                $paymentUrl = $dokuService->createPayment($order->order_number, $order->final_price, $dokuData);
                if (empty($paymentUrl)) throw new \Exception("Gagal DOKU.");
                $order->update(['payment_url' => $paymentUrl]);
            }

            DB::commit();
            Log::info("TRANSAKSI SELESAI & COMMIT DB.");

            // 13. NOTIFIKASI
            if ($request->coupon && $paymentStatus == 'paid') {
                $this->_processAffiliateCommission($request->coupon, $finalPrice);
            }
            $this->_sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus);

            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $order->payment_url,
                'change_amount'  => $changeAmount,
                'payment_method' => $metodeBayarFix 
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ORDER FAILED (Exception): ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    // ... (PRIVATE METHODS TETAP SAMA) ...
    // Pastikan _createTripayTransaction, checkCoupon, _processAffiliateCommission, dll ada di sini.
    // Tidak saya tulis ulang semua agar tidak kepanjangan, karena logicnya tidak berubah.
    // Tapi fungsi geocode() di atas SUDAH UPDATE dengan LOG.
    
    private function _createTripayTransaction($order, $methodChannel, $amount, $custName, $custEmail, $custPhone, $items)
    {
        $apiKey       = config('tripay.api_key');
        $privateKey   = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code');
        $mode         = config('tripay.mode');

        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap.'];
        }

        $calculatedTotalItems = 0;
        foreach ($items as $item) {
            $calculatedTotalItems += ($item['price'] * $item['quantity']);
        }
        $amount = (int) $amount;

        if ($calculatedTotalItems !== $amount) {
            $items = [[
                'sku'      => 'INV-' . $order->order_number,
                'name'     => 'Pembayaran Invoice #' . $order->order_number,
                'price'    => $amount,
                'quantity' => 1
            ]];
        }

        $baseUrl = ($mode === 'production') 
            ? 'https://tripay.co.id/api/transaction/create' 
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        $signature = hash_hmac('sha256', $merchantCode . $order->order_number . $amount, $privateKey);

        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->order_number,
            'amount'         => $amount,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'customer_phone' => $custPhone,
            'order_items'    => $items, 
            'return_url'     => url('/'), 
            'expired_time'   => (time() + (24 * 60 * 60)), 
            'signature'      => $signature
        ];

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(30)->post($baseUrl, $payload);
            $body = $response->json();
            if ($response->successful() && ($body['success'] ?? false) === true) {
                return ['success' => true, 'data' => $body['data']];
            }
            return ['success' => false, 'message' => $body['message'] ?? 'Gagal membuat transaksi Tripay.'];
        } catch (\Exception $e) {
            Log::error("Tripay Connection Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal terhubung ke server pembayaran.'];
        }
    }

    public function checkCoupon(Request $request) {
        $request->validate(['coupon_code' => 'required|string', 'total_belanja' => 'required|numeric|min:0']);
        $code = trim($request->coupon_code);
        $total = $request->total_belanja;
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) return response()->json(['status' => 'error', 'message' => 'Kode kupon tidak ditemukan.'], 404);
        if (!$coupon->is_active) return response()->json(['status' => 'error', 'message' => 'Kupon tidak aktif.'], 400);

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) return response()->json(['status' => 'error', 'message' => 'Promo belum dimulai.'], 400);
        if ($coupon->expiry_date && $now->gt($coupon->expiry_date)) return response()->json(['status' => 'error', 'message' => 'Kupon sudah kedaluwarsa.'], 400);
        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) return response()->json(['status' => 'error', 'message' => 'Kuota kupon habis.'], 400);
        if ($coupon->min_order_amount > 0 && $total < $coupon->min_order_amount) return response()->json(['status' => 'error', 'message' => 'Min. belanja Rp ' . number_format($coupon->min_order_amount, 0, ',', '.') . '.'], 400);

        $discountAmount = 0;
        if ($coupon->type == 'percent') {
            $discountAmount = $total * ($coupon->value / 100);
            if (isset($coupon->max_discount_amount) && $coupon->max_discount_amount > 0 && $discountAmount > $coupon->max_discount_amount) {
                $discountAmount = $coupon->max_discount_amount;
            }
        } else {
            $discountAmount = $coupon->value;
        }
        if ($discountAmount > $total) $discountAmount = $total;

        return response()->json([
            'status' => 'success',
            'message' => 'Kupon diterapkan!',
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'discount_amount' => $discountAmount,
                'final_total' => $total - $discountAmount,
                'type' => $coupon->type
            ]
        ]);
    }

    private function _processAffiliateCommission($couponCode, $finalPrice) {
        try {
            $affiliateOwner = Affiliate::where('coupon_code', $couponCode)->first();
            if ($affiliateOwner) {
                $komisiRate = 0.10; 
                $komisiDiterima = $finalPrice * $komisiRate;
                $affiliateOwner->increment('balance', $komisiDiterima);
                $this->_sendFonnteMessage($affiliateOwner->whatsapp, "💰 *KOMISI MASUK!* 💰\n\nSelamat! Kupon *{$couponCode}* digunakan.\nKomisi: Rp " . number_format($komisiDiterima, 0, ',', '.'));
            }
        } catch (\Exception $e) { Log::error("Gagal tambah komisi: " . $e->getMessage()); }
    }

    private function _sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus) {
        try {
            if ($order->customer_phone) {
                $msg = "Halo *{$order->customer_name}*, Order *{$order->order_number}* Berhasil!\nTotal: Rp " . number_format($finalPrice,0,',','.') . "\nStatus: $paymentStatus";
                if($paymentUrl) $msg .= "\nLink Bayar: $paymentUrl";
                $this->_sendFonnteMessage($order->customer_phone, $msg);
            }
            $adminPhone = '085745808809'; 
            $msgAdmin = "🔔 *ORDER BARU*\nInv: {$order->order_number}\nTotal: Rp " . number_format($finalPrice, 0, ',', '.') . "\nMetode: {$order->payment_method}";
            $this->_sendFonnteMessage($adminPhone, $msgAdmin);
        } catch (\Exception $e) {}
    }

    private function _sendFonnteMessage($target, $message) {
        $token = env('FONNTE_API_KEY') ?? env('FONNTE_KEY');
        if (!$token) return;
        Http::withHeaders(['Authorization' => $token])->post('https://api.fonnte.com/send', ['target' => $target, 'message' => $message]);
    }

    public function getPaymentChannels() {
        $apiKey = config('tripay.api_key');
        $mode = config('tripay.mode');
        $baseUrl = ($mode === 'production') ? 'https://tripay.co.id/api/merchant/payment-channel' : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->get($baseUrl);
            if ($response->successful()) return response()->json(['status' => 'success', 'data' => $response->json()['data'] ?? []]);
            return response()->json(['status' => 'error', 'message' => 'Gagal koneksi ke Tripay: ' . $response->status(), 'debug' => $response->json()], 500);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // WEBHOOK HANDLER KIRIMINAJA
    // =========================================================================
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        
        // 1. Log Payload Masuk (Penting untuk Debugging)
        Log::info('WEBHOOK KIRIMINAJA RECEIVED:', $payload);
        
        $method    = $payload['method'] ?? null;
        $dataArray = $payload['data'] ?? [];

        if (empty($dataArray)) {
            return response()->json(['error' => 'Invalid payload, data[] is missing'], 400);
        }

        try {
            DB::beginTransaction();

            // 2. Mapping Status KiriminAja -> Status Database Kita
            $statusMap = [
                'processed_packages'       => 'processing',  // Paket diproses
                'shipped_packages'         => 'shipped',     // Sedang dikirim (Kurir jalan)
                'finished_packages'        => 'completed',   // Selesai/Diterima
                'canceled_packages'        => 'cancelled',   // Dibatalkan
                'returned_packages'        => 'returning',   // Retur (Dikembalikan)
                'return_finished_packages' => 'returned',    // Retur Selesai
            ];

            // 3. Mapping Timestamp (Kolom waktu di DB)
            $timestampMap = [
                'shipped_packages'         => 'shipped_at',
                'finished_packages'        => 'finished_at', // Pastikan kolom ini ada di DB orders
                'canceled_packages'        => 'cancelled_at',
            ];

            $orderStatus = $statusMap[$method] ?? null;

            foreach ($dataArray as $data) {
                if (!$data) continue;

                $orderNumber = $data['order_id'] ?? null; // Di KiriminAja kita kirim order_number sebagai order_id
                $awb         = $data['awb'] ?? null;      // Nomor Resi Asli (JNE/J&T/dll)
                
                if (!$orderNumber) {
                    Log::warning('Webhook KiriminAja: Data tanpa order_id dilewati.', $data);
                    continue; 
                }

                // 4. Cari Order (Gunakan lockForUpdate agar aman dari race condition)
                $order = Order::where('order_number', $orderNumber)->lockForUpdate()->first();

                if ($order) {
                    // A. Update Nomor Resi (Jika belum ada atau berubah)
                    if ($awb && $order->shipping_ref !== $awb) {
                        $order->shipping_ref = $awb;
                        Log::info("Resi Update Order #$orderNumber: $awb");
                    }

                    // B. Update Status Order
                    if ($orderStatus && $order->status !== 'completed' && $order->status !== 'cancelled') {
                        $order->status = $orderStatus;

                        // Ambil waktu dari payload atau gunakan waktu sekarang
                        $datePayload = $data['date'] ?? null;
                        $updateTime  = $datePayload ? Carbon::parse($datePayload)->timezone('Asia/Jakarta') : now();

                        // C. Update Timestamp Spesifik
                        // Jika status shipped
                        if ($method === 'shipped_packages') {
                            $shippedTime = $data['shipped_at'] ?? $datePayload;
                            $order->shipped_at = $shippedTime ? Carbon::parse($shippedTime)->timezone('Asia/Jakarta') : $updateTime;
                        } 
                        // Jika status completed
                        elseif ($method === 'finished_packages') {
                            $finishedTime = $data['finished_at'] ?? $datePayload;
                            // Pastikan Anda punya kolom 'finished_at' di tabel orders, atau hapus baris ini
                            // $order->finished_at = $finishedTime ? Carbon::parse($finishedTime)->timezone('Asia/Jakarta') : $updateTime;
                        }

                        // D. Logika Tambah Saldo Seller (Jika Sistem Marketplace)
                        // Fitur ini diambil dari referensi Anda. Hapus jika tidak pakai sistem Multi-Vendor/Store.
                        if ($orderStatus === 'completed') {
                            $this->_processSellerBalance($order, $updateTime); 
                        }
                    }

                    $order->save();
                    Log::info("Order #$orderNumber updated to status: $orderStatus");
                } else {
                    Log::warning("Webhook: Order #$orderNumber tidak ditemukan di database.");
                }
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WEBHOOK ERROR:', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ]);
            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    // Helper: Proses Saldo Seller (Dari Kode Referensi Anda)
    private function _processSellerBalance($order, $updateTime)
    {
        // Cek apakah model Store/User/TopUp ada di aplikasi Anda
        if (class_exists('App\Models\Store') && $order->store_id) {
            try {
                $store = Store::find($order->store_id);
                if ($store) {
                    $seller = User::where('id', $store->user_id)->first(); // Sesuaikan 'id' atau 'id_pengguna'
                    if ($seller) {
                        $revenue = $order->total_price; // Atau subtotal, sesuaikan logika bisnis
                        
                        $seller->saldo += $revenue;
                        $seller->save();
                        
                        // Catat Mutasi Saldo
                        if (class_exists('App\Models\TopUp')) {
                            TopUp::create([
                                'user_id'        => $seller->id,
                                'amount'         => $revenue,             
                                'status'         => 'success',
                                'payment_method' => 'sales_revenue', 
                                'transaction_id' => 'REV-' . $order->order_number,
                                'created_at'     => $updateTime, 
                            ]);
                        }
                        Log::info('Saldo Seller berhasil ditambah.', ['order_id' => $order->id, 'revenue' => $revenue]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error Saldo Seller: ' . $e->getMessage());
            }
        }
    }

    // =========================================================================
    // SET CALLBACK URL (Jalankan sekali saja via Postman/Browser)
    // =========================================================================
    public function setCallback(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        // URL Webhook Anda (Pastikan sudah ONLINE / Ngrok, bukan localhost)
        // Ganti 'api/kiriminaja/webhook' sesuai route yang Anda buat
        $url = url('/api/kiriminaja/webhook'); 
        
        try {
            $response = $kiriminAja->setCallback($url);
        
            if (!empty($response) && isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback URL berhasil diset di KiriminAja',
                    'url_set' => $url,
                    'data'    => $response
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['text'] ?? 'Gagal menyet callback URL',
                    'data'    => $response
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan Detail Order
     */
    public function show($id)
    {
        // Load order beserta item produk dan lampiran file
        $order = Order::with(['items', 'attachments', 'coupon'])->findOrFail($id);

        return view('orders.show', compact('order'));
    }
}