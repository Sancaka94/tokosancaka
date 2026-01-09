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
use App\Services\DanaSignatureService; // <--- TAMBAHKAN INI

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

/**
     * Store a newly created resource in storage.
     */
    public function store(
        Request $request, 
        DokuJokulService $dokuService, 
        KiriminAjaService $kiriminAja, 
        DanaSignatureService $danaService // Inject Service DANA
    ) {
        Log::info('================ START ORDER STORE (REVISION) ================');
        Log::info('RAW REQUEST:', $request->all());

        // 1. VALIDASI INPUT
        $request->validate([
            'items'                   => 'required', 
            'total'                   => 'required|numeric',
            'delivery_type'           => 'required|in:pickup,shipping',
            'shipping_cost'           => 'required_if:delivery_type,shipping|numeric',
            'courier_name'            => 'nullable|string|required_if:delivery_type,shipping',
            'destination_text'        => 'nullable|string', 
            'destination_district_id' => 'nullable|required_if:delivery_type,shipping',
            'customer_name' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
            'customer_phone' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
        ]);

        // 2. SETUP DATA AWAL
        $cartItems = json_decode($request->items, true);
        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong.'], 400);
        }

        $customerNote = $request->input('customer_note'); 
        $catatanSistem = ''; 
        $inputMethod = $request->payment_method;
        $custId      = $request->customer_id;

        // Auto-fix: Jika pilih saldo member tapi tidak login -> ubah ke cash
        if ($inputMethod === 'affiliate_balance' && empty($custId)) {
            Log::warning('⚠️ AUTO-FIX: Metode Saldo Member tapi ID Kosong -> Ubah ke CASH');
            $metodeBayarFix = 'cash';
        } else {
            $metodeBayarFix = $inputMethod;
        }

        DB::beginTransaction();
        Log::info('Database Transaction Started.');

        try {
            // ============================================================
            // LANGKAH 1: KALKULASI HARGA, BERAT & CEK STOK
            // ============================================================
            $subtotal = 0;
            $finalCart = []; 
            $totalWeight = 0;

            foreach ($cartItems as $item) {
                // Lock for update agar stok tidak balapan
                $product = Product::lockForUpdate()->find($item['id']);
                
                if (!$product) throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                if ($product->stock < $item['qty']) throw new \Exception("Stok '{$product->name}' kurang (Sisa: {$product->stock}).");

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

            if ($totalWeight < 1000) $totalWeight = 1000; // Min 1kg

            // Hitung Diskon Kupon
            $discount = 0;
            $couponId = null;
            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                if ($couponDB && $couponDB->is_active) {
                    $couponId = $couponDB->id;
                    $discount = ($couponDB->type == 'percent') ? $subtotal * ($couponDB->value / 100) : $couponDB->value;
                    $couponDB->increment('used_count');
                }
            }

            // Hitung Harga Akhir
            $hargaSetelahDiskon = max(0, $subtotal - $discount);
            $biayaOngkir = ($request->delivery_type === 'shipping') ? (int)$request->shipping_cost : 0;
            $finalPrice = $hargaSetelahDiskon + $biayaOngkir; 

            // Generate Order Number Unik
            $orderNumber = 'SCK-' . date('ymdHis') . rand(100, 999);
            Log::info("[STEP 1] Generated Order Number: {$orderNumber}");

            // Identifikasi Customer
            $customerName  = $request->customer_name ?? 'Customer Umum';
            $customerPhone = $request->customer_phone ?? '08819435180';
            $customerEmail = 'tokosancaka@gmail.com'; 

            if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name; 
                    $customerPhone = $affiliateMember->whatsapp;
                    if (!empty($affiliateMember->email)) $customerEmail = $affiliateMember->email;
                }
            }

            $fullAddressSaved = null;
            if ($request->delivery_type === 'shipping') {
                $detail = $request->customer_address_detail ?? '';
                $district = $request->destination_text ?? '';
                $fullAddressSaved = $detail . ' (' . $district . ')';
            }

            // ============================================================
            // LANGKAH 2: SIMPAN KE DATABASE (STATUS: PENDING)
            // ============================================================
            // Kita simpan dulu agar punya ID Order yang valid untuk logistik & payment
            Log::info("[STEP 2] Saving Order to DB (Status: Pending)...");

            $order = Order::create([
                'order_number'    => $orderNumber,
                'user_id'         => $request->customer_id ?? null, 
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $finalPrice,
                'payment_method'  => $metodeBayarFix, 
                'status'          => 'pending',  // WAJIB PENDING
                'payment_status'  => 'unpaid',   // WAJIB UNPAID
                'note'            => $catatanSistem,
                'customer_note'   => $customerNote,
                'shipping_cost'   => $biayaOngkir,
                'courier_service' => $request->delivery_type === 'shipping' ? $request->courier_name : null,
                'shipping_ref'    => null, // Nanti diupdate setelah request kurir
                'destination_address' => $fullAddressSaved,
            ]);

            // Simpan Detail Item
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
                
                // Kurangi Stok & Tambah Terjual
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                if ($prod->stock <= 0) $prod->update(['stock_status' => 'unavailable']);
            }

            // Simpan Lampiran (Jika ada)
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $details = $request->input('attachment_details', []); 
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

            // ============================================================
            // LANGKAH 3: INTEGRASI KIRIMINAJA (JIKA PENGIRIMAN)
            // ============================================================
            if ($request->delivery_type === 'shipping') {
                Log::info("[STEP 3] Processing Shipping (KiriminAja)...");

                // 1. Geocoding
                $destLat = null; $destLng = null;
                if ($request->filled('destination_text')) {
                    $geo = $this->geocode($request->destination_text); // Pastikan method geocode ada di class
                    if ($geo) {
                        $destLat = (string)$geo['lat'];
                        $destLng = (string)$geo['lng'];
                    }
                }

                // 2. Tentukan Service Code (Mapping Manual)
                $serviceCode = $request->courier_code; 
                
                if (empty($serviceCode) && $request->courier_name) {
                    $parts = explode('-', $request->courier_name); 
                    $namaKurir = strtolower(trim($parts[0]));
                    
                    $mapManual = [
                        'j&t cargo' => 'jtcargo', 'j&t' => 'jnt', 'jne' => 'jne', 'sicepat' => 'sicepat',
                        'anteraja' => 'anteraja', 'pos' => 'posindonesia', 'tiki' => 'tiki', 
                        'lion' => 'lion', 'ninja' => 'ninja', 'id express' => 'idx', 'idx' => 'idx', 'spx' => 'spx',
                        'gojek' => 'gosend', 'grab' => 'grab_express', 'borzo' => 'borzo', 'ncs' => 'ncs'
                    ];
                    
                    foreach ($mapManual as $nameKey => $codeVal) {
                        if (str_contains($namaKurir, $nameKey)) { $serviceCode = $codeVal; break; }
                    }
                    if (empty($serviceCode)) $serviceCode = 'jne'; // Default fallback
                }

                $serviceType = $request->service_type ?? 'REG';
                $isInstant = (str_contains($serviceCode, 'gosend') || str_contains($serviceCode, 'grab'));
                $kaResponse = null;

                // 3. Request ke API KiriminAja
                if ($isInstant) {
                    // --- LOGIKA INSTANT ---
                    Log::info("Requesting INSTANT Courier ($serviceCode)...");
                    if (!$destLat || !$destLng) throw new \Exception("Gagal Instant: Koordinat tujuan tidak ditemukan.");

                    $instantPayload = [
                        'order_id'   => $orderNumber,
                        'service'    => $serviceCode,
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
                    // --- LOGIKA REGULER/CARGO ---
                    Log::info("Requesting REGULAR/CARGO Courier ($serviceCode)...");
                    
                    // Jadwal Pickup (1 jam dari sekarang, atau besok jam 9 pagi)
                    $now = \Carbon\Carbon::now();
                    $pickupSchedule = $now->addMinutes(60)->format('Y-m-d H:i:s');
                    if ($now->isSunday() || $now->hour >= 14) {
                        $pickupSchedule = $now->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                    }

                    $declaredValue = max(1000, (int)$subtotal);
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
                                'destination_address'      => $fullAddressSaved,
                                'destination_kecamatan_id' => (int) $request->destination_district_id,
                                'destination_kelurahan_id' => (int) $destSubDistrictId,
                                'destination_zipcode'      => $request->postal_code ?? '00000',
                                'weight'           => (int) $totalWeight,
                                'width'            => 10, 'length' => 10, 'height' => 10, 'qty' => 1,
                                'item_value'       => $declaredValue, 
                                'shipping_cost'    => (int) $biayaOngkir,
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

                    $kaResponse = $kiriminAja->createExpressOrder($kaPayload);

                    // Retry Logic jika jadwal ditolak
                    if (isset($kaResponse['status']) && $kaResponse['status'] == false) {
                        $errorMsg = strtolower($kaResponse['text'] ?? '');
                        if (str_contains($errorMsg, 'jadwal') || str_contains($errorMsg, 'schedule')) {
                            $newSchedule = \Carbon\Carbon::now()->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                            Log::warning("Jadwal Ditolak. Retry dengan jadwal: {$newSchedule}");
                            $kaPayload['schedule'] = $newSchedule;
                            $kaResponse = $kiriminAja->createExpressOrder($kaPayload);
                        }
                    }
                }

                // 4. Update Resi ke Database
                if (isset($kaResponse['status']) && $kaResponse['status'] == true) {
                    $shippingRef = $kaResponse['data']['order_id'] ?? $kaResponse['pickup_number'] ?? null;
                    Log::info("Shipping Success. Ref: $shippingRef");
                    
                    // UPDATE DATABASE DENGAN RESI
                    $order->update([
                        'shipping_ref' => $shippingRef,
                        'note' => $catatanSistem . "\n[RESI OTOMATIS] Ref: " . $shippingRef
                    ]);
                } else {
                    $errMsg = $kaResponse['text'] ?? json_encode($kaResponse);
                    throw new \Exception("Gagal Booking Kurir: " . $errMsg);
                }
            }

            // ============================================================
            // LANGKAH 4: PAYMENT GATEWAY REQUEST
            // ============================================================
            $paymentUrl = null;
            $paymentStatus = 'unpaid';
            $changeAmount = 0;

            Log::info("[STEP 4] Processing Payment Gateway: {$metodeBayarFix}");

            switch ($metodeBayarFix) {
                case 'cash':
                    $cashReceived = (int) $request->cash_amount;
                    if ($cashReceived < $finalPrice) throw new \Exception("Uang tunai kurang!");
                    
                    $changeAmount = $cashReceived - $finalPrice;
                    
                    $order->update([
                        'status' => 'processing',
                        'payment_status' => 'paid',
                        'note' => $order->note . "\n[INFO KASIR] Tunai. Terima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.')
                    ]);
                    
                    $paymentStatus = 'paid';
                    // Kirim WA (Hanya untuk Cash karena instan)
                    $this->_sendWaNotification($order, $finalPrice, null, 'paid');
                    break;

                case 'affiliate_balance':
                    if (!$request->customer_id) throw new \Exception("Member tidak terdeteksi.");
                    $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);
                    
                    if (!$affiliatePayor || !Hash::check($request->affiliate_pin, $affiliatePayor->pin)) {
                        throw new \Exception("PIN Salah!");
                    }
                    if ($affiliatePayor->balance < $finalPrice) {
                        throw new \Exception("Saldo Kurang.");
                    }
                    
                    $affiliatePayor->decrement('balance', $finalPrice);
                    $order->update([
                        'status' => 'processing',
                        'payment_status' => 'paid',
                        'note' => $order->note . "\n[INFO BAYAR] Potong Saldo Member"
                    ]);
                    
                    $paymentStatus = 'paid';
                    $this->_sendWaNotification($order, $finalPrice, null, 'paid');
                    break;

                case 'dana':
                    Log::info("[DANA] Requesting Payment URL...");
                    try {
                        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
                        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
                        
                        $bodyArray = [
                            "partnerReferenceNo" => $order->order_number, 
                            "merchantId" => config('services.dana.merchant_id'),
                            "amount" => [
                                "value" => number_format($finalPrice, 2, '.', ''), 
                                "currency" => "IDR"
                            ],
                            "validUpTo" => $expiryTime,
                            "urlParams" => [
                                [
                                    "url" => route('dana.return'),
                                    "type" => "PAY_RETURN",
                                    "isDeeplink" => "Y"
                                ],
                                [
                                    "url" => route('dana.notify'),
                                    "type" => "NOTIFICATION",
                                    "isDeeplink" => "Y"
                                ]
                            ],
                            "additionalInfo" => [
                                "mcc" => "5732", 
                                "order" => [
                                    "orderTitle" => "Invoice " . $order->order_number,
                                    "merchantTransType" => "01",
                                    "scenario" => "REDIRECT",
                                ],
                                "envInfo" => [
                                    "sourcePlatform" => "IPG",
                                    "terminalType" => "SYSTEM",
                                    "orderTerminalType" => "WEB",
                                ]
                            ]
                        ];

                        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm'; 

                        $accessToken = $danaService->getAccessToken();
                        $signature = $danaService->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

                        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';
                        
                        $response = Http::withHeaders([
                            'Authorization'  => 'Bearer ' . $accessToken,
                            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
                            'X-EXTERNAL-ID'  => Str::random(32),
                            'X-TIMESTAMP'    => $timestamp,
                            'X-SIGNATURE'    => $signature,
                            'Content-Type'   => 'application/json',
                            'CHANNEL-ID'     => '95221', 
                            'ORIGIN'         => config('services.dana.origin'),
                        ])
                        ->withBody($jsonBody, 'application/json')
                        ->post($baseUrl . $relativePath);

                        $result = $response->json();
                        Log::info('DANA_RESPONSE', $result);

                        if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                             $paymentUrl = $result['webRedirectUrl'] ?? null;
                             // UPDATE ORDER DENGAN LINK BAYAR
                             $order->update(['payment_url' => $paymentUrl]);
                        } else {
                             throw new \Exception("DANA API Error: " . ($result['responseMessage'] ?? 'General Error'));
                        }
                    } catch (\Exception $e) {
                        Log::error("DANA ERROR: " . $e->getMessage());
                        throw $e;
                    }
                    break;

                case 'tripay':
                    Log::info("[TRIPAY] Requesting Payment URL...");
                    $orderItems = [];
                    foreach ($finalCart as $item) {
                        $orderItems[] = [
                            'sku'      => (string) $item['product']->id,
                            'name'     => $item['product']->name,
                            'price'    => (int) $item['product']->sell_price,
                            'quantity' => (int) $item['qty']
                        ];
                    }
                    
                    if (empty($request->payment_channel)) throw new \Exception("Harap pilih Channel Pembayaran.");

                    // Panggil fungsi Tripay (yang ada di bawah controller)
                    $tripayRes = $this->_createTripayTransaction($order, $request->payment_channel, (int)$finalPrice, $customerName, $customerEmail, $customerPhone, $orderItems);
                    
                    if (!$tripayRes['success']) throw new \Exception("Tripay Gagal: " . ($tripayRes['message'] ?? 'Unknown Error'));
                    
                    $paymentUrl = $tripayRes['data']['checkout_url'];
                    $order->update(['payment_url' => $paymentUrl]);
                    break;

                case 'doku':
                    Log::info("[DOKU] Requesting Payment URL...");
                    $dokuData = ['name' => $customerName, 'email' => $customerEmail, 'phone' => $customerPhone];
                    $paymentUrl = $dokuService->createPayment($order->order_number, $order->final_price, $dokuData);
                    
                    if (empty($paymentUrl)) throw new \Exception("Gagal DOKU.");
                    $order->update(['payment_url' => $paymentUrl]);
                    break;
            }

            // ============================================================
            // LANGKAH 5: FINALISASI & COMMIT
            // ============================================================
            
            DB::commit();
            Log::info("[STEP 5] Transaction Committed. Order ID: {$order->id}");

            // Proses Komisi Afiliasi (Hanya jika langsung Paid/Cash/Saldo)
            if ($request->coupon && $paymentStatus == 'paid') {
                $this->_processAffiliateCommission($request->coupon, $finalPrice);
            }

            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil Dibuat!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $paymentUrl,
                'change_amount'  => $changeAmount,
                'payment_method' => $metodeBayarFix 
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ORDER FAILED (Exception): ' . $e->getMessage());
            Log::error($e->getTraceAsString());
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

    /**
     * ========================================================================
     * HANDLE DANA WEBHOOK (DANA NOTIFY)
     * Support: SNAP BI & DANA Notification 2.0
     * ========================================================================
     */
    public function handleDanaCallback(Request $request)
    {
        Log::info("========== DANA WEBHOOK (OrderController) ==========");
        
        // 1. Deteksi Format Payload
        $payload = $request->all();
        $isV2Notify = isset($payload['request']['body']); // Cek format V2 (Nested)

        $orderNumber = null;
        $status = null;
        $amount = 0;
        $refNoDana = null;

        try {
            if ($isV2Notify) {
                // --- LOGIKA DANA V2 (Sesuai Log Error Anda) ---
                Log::info("Mode: DANA Notification 2.0");
                
                $body = $payload['request']['body'];
                $status = $body['acquirementStatus'] ?? null; // SUCCESS, CLOSED
                $amount = $body['orderAmount']['value'] ?? 0;
                $refNoDana = $body['acquirementId'] ?? null;

                // Cari Order ID (SCK-...)
                // Cek 1: merchantTransId
                $orderNumber = $body['merchantTransId'] ?? null;

                // Cek 2: extendInfo -> originalMerchantTransId (Biasanya disini untuk V2)
                if (isset($body['extendInfo'])) {
                    $extendInfo = json_decode($body['extendInfo'], true);
                    if (isset($extendInfo['originalMerchantTransId'])) {
                        $orderNumber = $extendInfo['originalMerchantTransId'];
                    }
                }

            } else {
                // --- LOGIKA SNAP BI (Standard) ---
                Log::info("Mode: SNAP BI Standard");
                
                $orderNumber = $request->input('partnerReferenceNo');
                $refNoDana   = $request->input('referenceNo');
                $status      = $request->input('transactionStatus'); 
                $amount      = $request->input('amount.value');
            }

            Log::info("Parsed Data:", ['OrderNo' => $orderNumber, 'Status' => $status]);

            // 2. Validasi Data
            if (!$orderNumber) {
                Log::error("WEBHOOK ERROR: Order Number tidak ditemukan.");
                return response()->json(['responseCode' => '400', 'responseMessage' => 'Bad Request'], 400);
            }

            // 3. Cari Order
            $order = Order::where('order_number', $orderNumber)->first();
            if (!$order) {
                Log::error("WEBHOOK ERROR: Order #$orderNumber tidak ditemukan di DB.");
                return response()->json(['responseCode' => '404', 'responseMessage' => 'Order Not Found'], 404);
            }

            // 4. Idempotency (Cek jika sudah lunas)
            if ($order->payment_status === 'paid') {
                Log::info("WEBHOOK INFO: Order #$orderNumber sudah lunas. Skip.");
                return response()->json(['responseCode' => '200', 'responseMessage' => 'Success'], 200);
            }

            // 5. Update Status
            // Status Sukses DANA V2 = "SUCCESS" atau "FINISHED"
            // Status Sukses SNAP = "PAID"
            if (in_array($status, ['SUCCESS', 'PAID', 'FINISHED'])) {
                
                $order->update([
                    'status'         => 'processing',
                    'payment_status' => 'paid',
                    'note'           => $order->note . "\n[DANA PAID] Ref: $refNoDana | Time: " . now(),
                ]);

                Log::info("WEBHOOK SUKSES: Order #$orderNumber LUNAS.");

                // Panggil Helper yang sudah ada di OrderController
                if ($order->coupon_id) {
                    $this->_processAffiliateCommission($order->coupon->code ?? '', $order->final_price);
                }
                
                // Kirim WA (Helper OrderController)
                // Parameter: $order, $finalPrice, $paymentUrl, $paymentStatus
                $this->_sendWaNotification($order, $order->final_price, null, 'paid');

            } elseif ($status === 'CLOSED') {
                Log::warning("WEBHOOK CLOSED: Transaksi #$orderNumber kadaluarsa.");
                // Opsional: Cancel order jika masih pending
                if ($order->status === 'pending') {
                    $order->update(['status' => 'cancelled', 'note' => $order->note . "\n[DANA] Timeout/Closed."]);
                }
            }

            return response()->json(['responseCode' => '200', 'responseMessage' => 'Success'], 200);

        } catch (\Exception $e) {
            Log::error("WEBHOOK EXCEPTION: " . $e->getMessage());
            return response()->json(['responseCode' => '500', 'responseMessage' => 'Error'], 500);
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

    public function checkTransactionStatus(Request $request, \App\Services\DanaSignatureService $danaService)
{
    // Ambil orderNumber dari request (misal lewat input postman atau parameter)
    $orderNumber = $request->input('order_number');

    Log::info('DANA_STATUS_CHECK_START: Memulai pengecekan status.', ['order' => $orderNumber]);

    $order = \App\Models\Order::where('order_number', $orderNumber)->first();
    if (!$order) {
        Log::error('DANA_STATUS_NOT_FOUND: Order tidak ada di DB.', ['order' => $orderNumber]);
        return response()->json(['message' => 'Order not found'], 404);
    }

    $timestamp = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
    $path = '/v1.1/debit/status'; 
    
    // Body Inquiry Status SNAP BI
    $bodyArray = [
        "originalPartnerReferenceNo" => $order->order_number,
        "originalReferenceNo"        => "", 
        "serviceCode"                => "05", 
        "transactionDate"            => $order->created_at->toIso8601String(),
        "amount" => [
            "value"    => number_format($order->final_price, 2, '.', ''),
            "currency" => "IDR"
        ],
        "merchantId"    => config('services.dana.merchant_id'),
        "additionalInfo" => (object)[] 
    ];

    $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    try {
        // PERBAIKAN: Gunakan $danaService (dari parameter method), bukan $this->danaSignature
        $accessToken = $danaService->getAccessToken();
        $signature = $danaService->generateSignature('POST', $path, $jsonBody, $timestamp);

        Log::info('DANA_STATUS_SIGN_GENERATED', [
            'order' => $orderNumber,
            'signature' => $signature
        ]);

        $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization'  => 'Bearer ' . $accessToken,
            'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
            'X-EXTERNAL-ID'  => \Illuminate\Support\Str::random(32),
            'X-TIMESTAMP'    => $timestamp,
            'X-SIGNATURE'    => $signature,
            'Content-Type'   => 'application/json',
            'CHANNEL-ID'     => '95221',
            'ORIGIN'         => config('services.dana.origin'),
        ])
        ->withBody($jsonBody, 'application/json')
        ->post($baseUrl . $path);

        $result = $response->json();

        Log::info('DANA_STATUS_RESPONSE', [
            'status' => $response->status(),
            'body'   => $result
        ]);

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('DANA_STATUS_FATAL_ERROR', ['msg' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}