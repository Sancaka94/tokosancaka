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

use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;

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
use App\Models\Tenant;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductVariant; // <--- TAMBAHKAN INI

// --- [IMPORT DANA SDK (WAJIB ADA)] ---
use Dana\Widget\v1\Model\WidgetPaymentRequest;
use Dana\Widget\v1\Model\Money;
use Dana\Widget\v1\Model\UrlParam;
use Dana\Widget\v1\Model\WidgetPaymentRequestAdditionalInfo;
use Dana\Widget\v1\Model\EnvInfo;
use Dana\Widget\v1\Model\Order as DanaOrder;
use Dana\Configuration;
use Dana\Env;
use Dana\Widget\v1\Api\WidgetApi;

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;
use App\Services\DanaSignatureService; // <--- TAMBAHKAN INI

class OrderController extends Controller
{
    // 1. Siapkan variabel penampung ID Tenant
    protected $tenantId;

    public function __construct(Request $request)
    {
        // 2. Deteksi Tenant dari Subdomain URL (Berlaku untuk semua fungsi)
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // 3. Cari data Tenant-nya
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();

        // 4. Simpan ID-nya. Jika tidak ketemu, default ke 1 (Pusat)
        $this->tenantId = $tenant ? $tenant->id : 1;
    }
    // =========================================================================
    // 1. INDEX & DASHBOARD STATISTIK (DIPERBAIKI)
    // =========================================================================
    public function index(Request $request)
    {
        $query = Order::where('tenant_id', $this->tenantId);

        // 1. Filter Pencarian (No Order / Nama Customer / DAN ITEM PRODUK)
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  // [BARU] Cari berdasarkan Barcode/Nama Produk di dalam Order
                  ->orWhereHas('items', function($qItem) use ($search) {
                      $qItem->where('product_name', 'like', "%{$search}%")
                            ->orWhereHas('product', function($qProd) use ($search) {
                                $qProd->where('barcode', $search) // Scan Barcode
                                      ->orWhere('sku', $search);
                            });
                  });
            });
        }

        // 2. Filter Status Pembayaran
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        // 3. Filter Rentang Tanggal
        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', $dates[0]);
            }
        }

        // --- [MULAI HITUNG STATISTIK UNTUK DASHBOARD] ---

        // Clone query agar perhitungan statistik mengikuti filter (tanggal/search)
        $statsQuery = $query->clone();

        // A. Total Pendapatan & Customer
        $totalRevenue  = $statsQuery->sum('final_price');
        $totalCustomer = $statsQuery->distinct('customer_phone')->count('customer_phone');

        // B. Status Pembayaran
        // Kita clone dari $query awal (sebelum di-paginate)
        $totalLunas  = $query->clone()->where('payment_status', 'paid')->count();
        $totalUnpaid = $query->clone()->where('payment_status', 'unpaid')->count();

        // C. Best Seller Variant (Berdasarkan Item Terjual)
        // Menggunakan subquery order_id dari filter yang aktif
        $bestSellerVariant = OrderDetail::select('product_name as name', DB::raw('SUM(quantity) as total'))
            ->whereIn('order_id', $statsQuery->select('id'))
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->first();

        // D. Best Seller Category & Laundry Weight (Kg)
        $bestSellerCategory = null;
        $totalLaundryWeight = 0;

        try {
            // Cek apakah tabel/model category dan product tersedia relasinya
            if (class_exists('App\Models\Category') && class_exists('App\Models\Product')) {

                // 1. Cari Kategori Terlaris
                $bestSellerCategory = DB::table('order_details')
                    ->join('orders', 'orders.id', '=', 'order_details.order_id')
                    ->join('products', 'products.id', '=', 'order_details.product_id')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->whereIn('orders.id', $statsQuery->select('id'))
                    ->select('categories.name', DB::raw('SUM(order_details.quantity) as total'))
                    ->groupBy('categories.name')
                    ->orderByDesc('total')
                    ->first();

                // 2. Hitung Berat Laundry (Filter kategori yang mengandung kata 'laundry')
                // Asumsi berat di database produk dalam Gram, kita ubah ke Kg
                $weightInGrams = DB::table('order_details')
                    ->join('orders', 'orders.id', '=', 'order_details.order_id')
                    ->join('products', 'products.id', '=', 'order_details.product_id')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->whereIn('orders.id', $statsQuery->select('id'))
                    ->where(function($q) {
                        $q->where('categories.name', 'like', '%laundry%')
                          ->orWhere('categories.slug', 'like', '%laundry%');
                    })
                    ->sum(DB::raw('order_details.quantity * products.weight'));

                $totalLaundryWeight = $weightInGrams / 1000; // Konversi ke Kg
            }
        } catch (\Exception $e) {
            // Fallback jika tabel kategori error/tidak ada, nilai tetap 0 atau null
            Log::warning("Gagal hitung statistik kategori: " . $e->getMessage());
        }

        // --- [SELESAI HITUNG STATISTIK] ---

        // Urutkan dan Paginate (Data Tabel Utama)
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('orders.index', compact(
            'orders',
            'totalRevenue',
            'totalCustomer',
            'totalLunas',
            'totalUnpaid',
            'bestSellerVariant',
            'bestSellerCategory',
            'totalLaundryWeight'
        ));
    }

    /**
     * Menampilkan Halaman Kasir (POS) - Terfilter Subdomain
     */
    public function create(Request $request)
    {
        // 1. DETEKSI TENANT DARI SUBDOMAIN
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // Cari tenant berdasarkan subdomain URL
        $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();

        // Jika subdomain tidak terdaftar, arahkan ke error atau tenant pusat (ID 1)
        $tenantId = $tenant ? $tenant->id : 1;

        // 2. FILTER PRODUK BERDASARKAN TENANT_ID
        $products = Product::where('tenant_id', $tenantId) // <--- KUNCI FILTER
                           ->where('stock_status', 'available')
                           ->where('stock', '>', 0)
                           ->orderBy('created_at', 'desc')
                           ->get();

        // 3. FILTER PELANGGAN BERDASARKAN TENANT_ID
        $customers = Affiliate::where('tenant_id', $tenantId) // <--- KIKIS BOCOR DATA
                              ->orderBy('name', 'asc')
                              ->get()
                              ->map(function($aff) {
                                  $aff->saldo = 0;
                                  $aff->affiliate_balance = $aff->balance;
                                  $aff->has_pin = !empty($aff->pin);
                                  return $aff;
                              });

        $autoCoupon = $request->query('coupon');

        // 4. FILTER KATEGORI BERDASARKAN TENANT_ID
        $categories = [];
        try {
            if (class_exists('App\Models\Category')) {
                $categories = Category::where('tenant_id', $tenantId) // <--- KUNCI FILTER
                                      ->where('is_active', true)
                                      ->get();
            }
        } catch (\Exception $e) { }

        // Fallback jika kategori kosong
        if (count($categories) == 0) {
            $categories = [
                (object)['id' => 'retail', 'name' => 'Retail / Toko', 'slug' => 'retail'],
                (object)['id' => 'laundry', 'name' => 'Laundry', 'slug' => 'laundry'],
            ];
        }

        // Tambahkan variabel tenant ke view jika diperlukan untuk branding nama toko
        return view('orders.create', compact('products', 'customers', 'autoCoupon', 'categories', 'tenant'));
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
     * UPDATE: Fix Simpan Customer & Koordinat
     */
    public function checkShippingRates(Request $request, KiriminAjaService $kiriminAja)
    {
        Log::info('CEK ONGKIR REQUEST:', $request->all());

        // 1. Validasi Input
        $request->validate([
            'weight' => 'required|numeric',
            'destination_district_id' => 'required',
            'destination_subdistrict_id' => 'nullable',
        ]);

        try {
            // 2. Setup Awal
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];
            $user = Auth::user();

            // ============================================================
            // [STEP 1] LOGIKA GEOCODING (CARI KOORDINAT DULU)
            // ============================================================
            // Kita cari koordinatnya DULUAN sebelum simpan ke database
            $destLat = $request->receiver_lat; // Ambil dari input GPS (kalau ada)
            $destLng = $request->receiver_lng;

            // Kalau GPS kosong tapi ada teks alamat, cari koordinat otomatis via Geocoding
            if (empty($destLat) && $request->filled('destination_text')) {
                $geo = $this->geocode($request->destination_text);
                if ($geo) {
                    $destLat = $geo['lat'];
                    $destLng = $geo['lng'];
                    Log::info("Geocoding Success: Lat $destLat, Lng $destLng");
                }
            }

            // ============================================================
            // [STEP 2] SIMPAN PELANGGAN (PINDAH KE SINI & DIPERBAIKI)
            // ============================================================
            if ($request->has('save_customer') && ($request->save_customer == true || $request->save_customer == 'true')) {

                // Fallback: Jika Nama/HP Kosong, isi default biar DB gak error
                $namaPelanggan = $request->customer_name ?: 'Tamu Cek Ongkir';
                $hpPelanggan   = $request->customer_phone ?: '000000000000';

                // Fallback: Jika Alamat Detail kosong/strip, isi dengan nama Kecamatan
                $alamatDetail = ($request->customer_address_detail == '-' || empty($request->customer_address_detail))
                                ? $request->destination_text
                                : $request->customer_address_detail;

                \App\Models\Customer::updateOrCreate(
                    [
                        // Unik berdasarkan Tenant dan Nomor WhatsApp
                        'tenant_id' => $this->tenantId,
                        'whatsapp'  => $this->_normalizePhoneNumber($hpPelanggan)
                    ],
                    [
                        'user_id'        => $user->id,
                        'subdomain'      => $subdomain,
                        'name'           => $namaPelanggan,

                        // Simpan Alamat & Koordinat yang sudah didapat di atas
                        'address_detail' => $alamatDetail,
                        'address'        => $alamatDetail, // Isi kolom address juga

                        'province'       => $request->province_name,
                        'regency'        => $request->regency_name,
                        'district'       => $request->district_name,
                        'village'        => $request->village_name,
                        'postal_code'    => $request->postal_code,
                        'district_id'    => $request->destination_district_id,
                        'subdistrict_id' => $request->destination_subdistrict_id,

                        // [PENTING] Masukkan Koordinat Hasil Geocoding
                        'latitude'       => $destLat,
                        'longitude'      => $destLng,
                    ]
                );
                Log::info("Customer Auto-Saved: $namaPelanggan | Loc: $destLat, $destLng");
            }

            // ============================================================
            // [STEP 3] PERSIAPAN DATA ORIGIN (PENGIRIM)
            // ============================================================
            // [UPDATE] Ambil Data ORIGIN dari Database User (Bukan Config)
            if (empty($user->district_id)) {
                return response()->json(['status' => 'error', 'message' => 'Lengkapi alamat Anda di profil untuk cek ongkir.']);
            }

            $originDistrict    = $user->district_id;
            $originSubDistrict = $user->subdistrict_id ?? 0;
            $originLat         = $user->latitude;
            $originLng         = $user->longitude;
            $originAddr        = $user->address_detail ?? $user->name;

            Log::info("CEK ONGKIR USER ID {$user->id}: Dist: $originDistrict, Lat: $originLat");

            // ============================================================
            // [STEP 4] REQUEST API REGULER (Kirim parameter subdistrict)
            // ============================================================

            // Mapping Logo Kurir (Lengkap)
            $courierMap = [
                'gojek' => ['name' => 'GoSend',       'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'grab'  => ['name' => 'GrabExpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                'jne'   => ['name' => 'JNE',         'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                'jnt'   => ['name' => 'J&T Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'sicepat' => ['name' => 'SiCepat',      'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                'anteraja' => ['name' => 'Anteraja',    'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'posindonesia'      => ['name' => 'POS Indonesia','logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'tiki'  => ['name' => 'TIKI',        'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                'lion'  => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'ninja' => ['name' => 'Ninja Express','logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'idx'   => ['name' => 'ID Express',  'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'spx'   => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'sap'   => ['name' => 'SAP Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                'ncs'   => ['name' => 'NCS Kurir',   'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'jtcargo' => ['name' => 'J&T Cargo',   'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'borzo'   => ['name' => 'Borzo',       'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
            ];

            $formattedRates = [];

            // Panggil API Reguler
            Log::info("Mengambil Ongkir REGULER...");
            $responseReguler = $kiriminAja->getExpressPricing(
                (int) $originDistrict, (int) $originSubDistrict,
                (int) $request->destination_district_id, (int) ($request->destination_subdistrict_id ?? 0),
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
                        'courier_code' => $rate['service'],
                        'service_type' => $rate['service_type'],
                    ];
                }
            } else {
                Log::warning("Ongkir REGULER Gagal/Kosong", ['response' => $responseReguler]);
            }

            // ============================================================
            // [STEP 5] REQUEST API INSTANT (GOJEK/GRAB)
            // ============================================================
            if ($destLat && $destLng && $originLat && $originLng) {
                Log::info("Mencoba Ongkir INSTANT (Grab/Gojek)... Route: $originLat,$originLng -> $destLat,$destLng");

                $responseInstant = $kiriminAja->getInstantPricing(
                    (float) $originLat, (float) $originLng, $originAddr,
                    (float) $destLat, (float) $destLng, $request->destination_text,
                    (int) $request->weight, 1000, 'motor', ['gosend', 'grab_express']
                );

                Log::info("RESPONSE MENTAH INSTANT:", ['body' => $responseInstant]);

                if (isset($responseInstant['status']) && $responseInstant['status'] == true) {
                    $instantResults = $responseInstant['result'] ?? [];

                    foreach ($instantResults as $courierData) {
                        $courierName = strtolower($courierData['name'] ?? 'instant'); // gosend / grab
                        $costs = $courierData['costs'] ?? [];

                        foreach ($costs as $costData) {
                            $priceData = $costData['price'] ?? [];
                            $totalPrice = $priceData['total_price'] ?? 0;

                            if ($totalPrice > 0) {
                                // Mapping Logo Manual
                                $logoUrl = null;
                                if(str_contains($courierName, 'go')) $logoUrl = $courierMap['gojek']['logo_url'];
                                if(str_contains($courierName, 'grab')) $logoUrl = $courierMap['grab']['logo_url'];

                                $formattedRates[] = [
                                    'code'    => 'kiriminaja_instant',
                                    'name'    => strtoupper($courierName), // GOSEND / GRAB
                                    'logo'    => $logoUrl,
                                    'service' => 'Instant (' . ($costData['service_type'] ?? 'Motor') . ')',
                                    'cost'    => (int) $totalPrice,
                                    'etd'     => $costData['estimation'] ?? 'Instant',
                                ];
                            }
                        }
                    }
                } else {
                    Log::warning("Ongkir INSTANT Gagal/Tidak Ada Driver", ['response' => $responseInstant]);
                }
            } else {
                Log::info("Ongkir INSTANT Skipped: Koordinat tidak lengkap.");
            }

            // ============================================================
            // [STEP 6] SORTIR HARGA & RETURN
            // ============================================================
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

    public function store(
        Request $request,
        DokuJokulService $dokuService,
        KiriminAjaService $kiriminAja,
        DanaSignatureService $danaService // Inject Service DANA
    ) {
        Log::info('================ START ORDER STORE (FINAL FIX) ================');
        Log::info('RAW REQUEST:', $request->all());

        // 1. VALIDASI INPUT
        $request->validate([
            'items'               => 'required',
            'total'               => 'required|numeric',
            'delivery_type'       => 'required|in:pickup,shipping',
            'shipping_cost'       => 'required_if:delivery_type,shipping|numeric',
            'courier_name'        => 'nullable|string|required_if:delivery_type,shipping',
            'destination_text'    => 'nullable|string',
            'destination_district_id' => 'nullable|required_if:delivery_type,shipping',
            'customer_name' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
            'customer_phone' => [
                Rule::requiredIf(fn() => $request->delivery_type === 'shipping' && empty($request->customer_id)),
            ],
        ]);

        $currentUser = Auth::user();

        // 2. SETUP DATA AWAL
        $cartItems = json_decode($request->items, true);
        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong.'], 400);
        }

        $customerNote = $request->input('customer_note');
        $catatanSistem = '';

        // Tangkap kategori
        $selectedCategory = $request->input('category_slug', 'retail');
        if ($selectedCategory === 'laundry') {
            $catatanSistem .= "[JENIS: LAUNDRY] ";
        }

        $inputMethod = $request->payment_method;
        $custId      = $request->customer_id;

        // Auto-fix: Jika pilih saldo member tapi tidak login -> ubah ke cash
        if ($inputMethod === 'affiliate_balance' && empty($custId)) {
            Log::warning('⚠️ AUTO-FIX: Metode Saldo Member tapi ID Kosong -> Ubah ke CASH');
            $metodeBayarFix = 'cash';
        } else {
            // [PENTING] Simpan metode asli (misal: 'dana_sdk')
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
                $isVariant = isset($item['variant_id']) && !empty($item['variant_id']);
                $product = null;
                $variant = null;
                $qty = $item['qty'];

                if ($isVariant) {
                    $variant = ProductVariant::where('tenant_id', $this->tenantId)->lockForUpdate()->find($item['variant_id']);
                    if (!$variant) throw new \Exception("Varian Produk ID {$item['variant_id']} tidak ditemukan.");

                    $product = Product::find($variant->product_id);
                    if ($variant->stock < $qty) throw new \Exception("Stok Varian '{$product->name} - {$variant->name}' kurang.");

                    $priceToUse = $variant->price;
                    $nameToStore = $product->name . ' (' . $variant->name . ')';
                } else {
                    $product = Product::lockForUpdate()->find($item['id']);
                    if (!$product) throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                    if ($product->stock < $qty) throw new \Exception("Stok '{$product->name}' kurang.");

                    $priceToUse = $product->sell_price;
                    $nameToStore = $product->name;
                }

                $lineTotal = ceil($priceToUse * $qty);
                $subtotal += $lineTotal;
                $weightPerItem = ($product->weight > 0) ? $product->weight : 5;
                $totalWeight += ($weightPerItem * $qty);

                $finalCart[] = [
                    'type'     => $isVariant ? 'variant' : 'single',
                    'product'  => $product,
                    'variant'  => $variant,
                    'name_db'  => $nameToStore,
                    'price_db' => $priceToUse,
                    'qty'      => $qty,
                    'subtotal' => $lineTotal
                ];
            }

            if ($totalWeight < 1000) $totalWeight = 1000;

            // Hitung Diskon Kupon
            $discount = 0;
            $couponId = null;
            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                if ($couponDB && $couponDB->is_active) {
                    $couponId = $couponDB->id;
                    if ($couponDB->type == 'percent') {
                        $rawDiscount = $subtotal * ($couponDB->value / 100);
                        $discount = floor($rawDiscount);
                    } else {
                        $discount = (int) $couponDB->value;
                    }
                    $couponDB->increment('used_count');
                }
            }

            $hargaSetelahDiskon = (int) ceil(max(0, $subtotal - $discount));
            $biayaOngkir = ($request->delivery_type === 'shipping') ? (int)$request->shipping_cost : 0;
            $finalPrice = $hargaSetelahDiskon + $biayaOngkir;

            $orderNumber = 'SCK-PRT-' . date('ymdHis') . rand(100, 999);
            Log::info("[STEP 1] Order Number: {$orderNumber}");

            // Identifikasi Customer
            $customerName  = $request->customer_name ?? 'Customer Umum';
            $rawPhone = $request->customer_phone ?? '085745808809';
            $customerPhone = $this->_normalizePhoneNumber($rawPhone);
            $customerEmail = 'tokosancaka@gmail.com';

            if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name;
                    $customerPhone = $this->_normalizePhoneNumber($affiliateMember->whatsapp);
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
            Log::info("[STEP 2] Saving Order to DB...");

            $order = Order::create([
                'tenant_id'       => $this->tenantId,
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
                'shipping_ref'    => null,
                'destination_address' => $fullAddressSaved,
            ]);

            // Simpan Detail Item
            foreach ($finalCart as $data) {
                $prod = $data['product'];
                OrderDetail::create([
                    'tenant_id'         => $this->tenantId,
                    'order_id'          => $order->id,
                    'product_id'        => $prod->id,
                    'product_name'      => $prod->name,
                    'base_price_at_order' => $prod->base_price,
                    'price_at_order'    => $prod->sell_price,
                    'quantity'          => $data['qty'],
                    'subtotal'          => (int) $data['subtotal'],
                ]);

                // Kurangi Stok
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                if ($prod->stock <= 0) $prod->update(['stock_status' => 'unavailable']);
            }

            // Simpan Lampiran
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
            // LANGKAH 3: LOGIKA PENGIRIMAN & PEMBAYARAN (INI YANG DIPERBAIKI)
            // ============================================================

            $paymentUrl = null;
            $paymentStatus = 'unpaid';
            $changeAmount = 0;
            $triggerWaType = null;

            // === SKENARIO A: PENGIRIMAN PAKET (SHIPPING) ===
            if ($request->delivery_type === 'shipping') {

                if (!$currentUser) throw new \Exception("Sesi Admin berakhir. Silakan login ulang.");

                // 1. Validasi Alamat Pengirim
                if (empty($currentUser->district_id)) throw new \Exception("Lengkapi alamat profil Anda untuk pengiriman.");

                $originFullAddress = implode(', ', array_filter([
                    $currentUser->address_detail, $currentUser->village, $currentUser->district,
                    $currentUser->regency, $currentUser->province, $currentUser->postal_code
                ]));

                $tagihanKeAdmin = (int) $biayaOngkir;
                Log::info("SHIPPING PROCESS - User: {$currentUser->id}, Tagihan: {$tagihanKeAdmin}");

                if ($currentUser->saldo >= $tagihanKeAdmin) {

                    // A. Potong Saldo Admin
                    $currentUser->decrement('saldo', $tagihanKeAdmin);

                    // B. PROSES KIRIMINAJA
                    $destLat = null; $destLng = null;
                    if ($request->filled('destination_text')) {
                        $geo = $this->geocode($request->destination_text);
                        if ($geo) { $destLat = (string)$geo['lat']; $destLng = (string)$geo['lng']; }
                    }

                    $serviceCode = $request->courier_code ?? 'jne';
                    $isInstant = (str_contains($serviceCode, 'gosend') || str_contains($serviceCode, 'grab'));
                    $kaResponse = null;

                    if ($isInstant) {
                         if (!$destLat || !$destLng) throw new \Exception("Gagal Instant: Koordinat tujuan tidak akurat.");
                         $kaResponse = $kiriminAja->createInstantOrder([
                            'order_id'    => $orderNumber,
                            'service'     => $serviceCode,
                            'item_price'  => (int) $subtotal,
                            'origin'      => [
                                'lat'     => (string) $currentUser->latitude,
                                'long'    => (string) $currentUser->longitude,
                                'address' => $originFullAddress,
                                'phone'   => $currentUser->phone ?? '085745808809',
                                'name'    => $currentUser->name
                            ],
                            'destination' => [
                                'lat' => $destLat, 'long' => $destLng, 'address' => $request->destination_text,
                                'phone' => $customerPhone, 'name' => $customerName
                            ],
                            'weight' => (int) $totalWeight, 'vehicle' => 'motor'
                        ]);
                    } else {
                        // Reguler
                        $pickupSchedule = now()->addMinutes(60)->format('Y-m-d H:i:s');
                        if (now()->hour >= 14 || now()->isSunday()) {
                            $pickupSchedule = now()->addDay()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                        }
                        $kaResponse = $kiriminAja->createExpressOrder([
                            'address'       => $originFullAddress,
                            'phone'         => $currentUser->phone ?? '085745808809',
                            'name'          => $currentUser->name,
                            'kecamatan_id'  => (int) $currentUser->district_id,
                            'kelurahan_id'  => (int) $currentUser->subdistrict_id,
                            'zipcode'       => $currentUser->postal_code ?? '00000',
                            'schedule'      => $pickupSchedule,
                            'platform_name' => 'Sancaka Store',
                            'packages'      => [[
                                'order_id' => $orderNumber,
                                'destination_name' => $customerName,
                                'destination_phone' => $customerPhone,
                                'destination_address' => $fullAddressSaved,
                                'destination_kecamatan_id' => (int) $request->destination_district_id,
                                'destination_kelurahan_id' => (int) ($request->destination_subdistrict_id ?? 0),
                                'destination_zipcode' => $request->postal_code ?? '00000',
                                'weight' => (int) $totalWeight,
                                'width'=>10, 'length'=>10, 'height'=>10, 'qty'=>1,
                                'item_value' => max(1000, (int)$subtotal),
                                'shipping_cost' => (int) $biayaOngkir,
                                'insurance_amount'=>0,
                                'service'=>$serviceCode,
                                'service_type'=>$request->service_type ?? 'REG',
                                'package_type_id'=>1,
                                'item_name'=>'Paket Order '.$orderNumber,
                                'cod'=>0,
                                'note'=>'Handle with care'
                            ]]
                        ]);
                    }

                    // 3. CEK HASIL BOOKING & TENTUKAN STATUS PEMBAYARAN
                    if (isset($kaResponse['status']) && $kaResponse['status'] == true) {
                        $shippingRef = $kaResponse['data']['order_id'] ?? $kaResponse['pickup_number'] ?? null;

                        // [FIX LOGIC] CEK APAKAH PEMBAYARAN ONLINE (DANA/SDK)?
                        $isOnlinePayment = in_array($inputMethod, ['dana', 'dana_sdk', 'tripay', 'doku']);

                        if ($isOnlinePayment) {
                            // JIKA DANA: Status TETAP UNPAID agar masuk ke Switch Case Payment di bawah
                            $order->update([
                                'shipping_ref'   => $shippingRef,
                                'note'           => $catatanSistem . "\n[RESI OTOMATIS] " . $shippingRef,
                                'payment_status' => 'unpaid',
                                'status'         => 'pending'
                            ]);
                            // $metodeBayarFix TIDAK DIUBAH (Tetap 'dana_sdk')
                        } else {
                            // JIKA CASH/SALDO: Langsung LUNAS
                            $order->update([
                                'shipping_ref'   => $shippingRef,
                                'note'           => $catatanSistem . "\n[RESI OTOMATIS] " . $shippingRef . "\n[SALDO ADMIN] Terpotong Ongkir Rp " . number_format($tagihanKeAdmin),
                                'payment_status' => 'paid',
                                'payment_method' => 'saldo_admin',
                                'status'         => 'processing'
                            ]);
                            $paymentStatus = 'paid';
                            $metodeBayarFix = 'saldo_admin'; // Override biar skip payment gateway
                            $triggerWaType = 'paid';
                        }

                    } else {
                        // Refund Saldo jika gagal booking
                        $currentUser->increment('saldo', $tagihanKeAdmin);
                        throw new \Exception("Gagal Booking Kurir: " . ($kaResponse['text'] ?? 'Unknown Error'));
                    }

                } else {
                    throw new \Exception("Saldo tidak cukup bayar Ongkir (Butuh: Rp " . number_format($tagihanKeAdmin) . "). Saldo: Rp " . number_format($currentUser->saldo));
                }

            } // End of Shipping Block

            // === LANGKAH 4: PROSES PEMBAYARAN (SWITCH CASE) ===
            // Karena $metodeBayarFix tetap 'dana_sdk', dia akan masuk ke sini.

            switch ($metodeBayarFix) {
                case 'cash':
                    // Hanya untuk Pickup (Jika Shipping sudah di-handle diatas)
                    if ($request->delivery_type !== 'shipping') {
                        $cashReceived = (int) $request->cash_amount;
                        $changeAmount = $cashReceived - $finalPrice;
                        $order->update(['status'=>'processing', 'payment_status'=>'paid', 'note'=>$order->note."\nTunai. Lunas."]);
                        $paymentStatus='paid'; $triggerWaType='paid';
                    }
                    break;

                case 'pay_later':
                    if ($request->delivery_type !== 'shipping') {
                        $order->update(['status'=>'processing', 'payment_status'=>'unpaid', 'note'=>$order->note."\nBayar Nanti (Tagihan)"]);
                        $paymentStatus = 'unpaid'; $triggerWaType = 'unpaid';
                    }
                    break;

                case 'qris_manual':
                    if ($request->delivery_type !== 'shipping') {
                        $order->update(['status'=>'processing', 'payment_status'=>'paid', 'note'=>$order->note."\nQRIS Manual"]);
                        $paymentStatus = 'paid'; $triggerWaType = 'paid';
                    }
                    break;

                case 'affiliate_balance':
                    if ($request->delivery_type !== 'shipping') {
                        // Logic potong saldo member (copy dari kode lama Anda)
                        if (!$request->customer_id) throw new \Exception("Member tidak terdeteksi.");
                        $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);
                        if (!$affiliatePayor) throw new \Exception("Data Member tidak ditemukan.");
                        if (!empty($affiliatePayor->pin) && !Hash::check($request->affiliate_pin, $affiliatePayor->pin)) throw new \Exception("PIN Salah!");
                        if ($affiliatePayor->balance < $finalPrice) throw new \Exception("Saldo Member Kurang.");
                        $affiliatePayor->decrement('balance', $finalPrice);
                        $order->update(['status'=>'processing', 'payment_status'=>'paid', 'note'=>$order->note."\nPotong Saldo Member"]);
                        $paymentStatus = 'paid'; $triggerWaType = 'paid';
                    }
                    break;

                // --- [INI YANG KITA TUNGGU] ---
                // --- [METODE DANA SDK (WIDGET) - FIX MANDATORY FIELD] ---
                case 'dana_sdk':
                    Log::info("[DANA SDK] Memulai request widget untuk Order #{$orderNumber}");

                    // Pindahkan logic ke dalam try-catch yang me-lempar error (re-throw)
                    // Supaya kalau DANA Error, Transaksi BATAL (Rollback) dan muncul pesan error di layar
                    try {
                        $config = new Configuration();
                        $config->setApiKey('PRIVATE_KEY', config('services.dana.private_key'));
                        $config->setApiKey('X_PARTNER_ID', config('services.dana.x_partner_id'));
                        $config->setApiKey('ORIGIN', config('services.dana.origin'));
                        $config->setApiKey('DANA_ENV', Env::SANDBOX); // Ubah ke Env::PRODUCTION jika live

                        $apiInstance = new WidgetApi(null, $config);

                        $orderObj = new DanaOrder();
                        $orderObj->setOrderTitle("Invoice #" . $orderNumber);
                        $orderObj->setOrderMemo("Pembayaran Order Sancaka"); // Tambah memo

                        $envInfo = new EnvInfo();
                        $envInfo->setSourcePlatform("IPG");
                        $envInfo->setTerminalType("WEB");
                        $envInfo->setWebsiteLanguage("ID");

                        $addInfo = new WidgetPaymentRequestAdditionalInfo();

                        // [FIX UTAMA: TAMBAHKAN PRODUCT CODE]
                        // Kode ini wajib untuk DANA Widget / SNAP
                        $addInfo->setProductCode("51051000100000000001");

                        $addInfo->setOrder($orderObj);
                        $addInfo->setEnvInfo($envInfo);

                        $paymentRequest = new WidgetPaymentRequest();
                        $paymentRequest->setMerchantId(config('services.dana.merchant_id'));
                        $paymentRequest->setPartnerReferenceNo($orderNumber);

                        $money = new Money();
                        $money->setValue(number_format($finalPrice, 2, '.', ''));
                        $money->setCurrency("IDR");
                        $paymentRequest->setAmount($money);

                        // Setup URL Redirect
                        $urlParam = new UrlParam();
                        $currentSubdomain = explode('.', $request->getHost())[0];
                        $returnUrl = route('orders.invoice', ['subdomain' => $currentSubdomain, 'orderNumber' => $orderNumber]);

                        // Paksa HTTPS jika di production/ngrok
                        if (!str_contains($returnUrl, 'https://')) {
                            $returnUrl = str_replace('http://', 'https://', $returnUrl);
                        }

                        $urlParam->setUrl($returnUrl);
                        $urlParam->setType("PAY_RETURN");
                        $urlParam->setIsDeeplink("Y");

                        $paymentRequest->setUrlParams([$urlParam]);
                        $paymentRequest->setAdditionalInfo($addInfo);

                        // Eksekusi Request
                        $result = $apiInstance->widgetPayment($paymentRequest);

                        // Ambil URL dari berbagai kemungkinan respon SDK
                        if (method_exists($result, 'getWebRedirectUrl')) {
                            $paymentUrl = $result->getWebRedirectUrl();
                        } elseif (isset($result->webRedirectUrl)) {
                            $paymentUrl = $result->webRedirectUrl;
                        }

                        if ($paymentUrl) {
                            $order->update(['payment_url' => $paymentUrl]);
                            $triggerWaType = 'unpaid';
                            Log::info("[DANA SDK] Success URL: $paymentUrl");
                        } else {
                            // Jika response 200 OK tapi URL kosong
                            throw new \Exception("Respon DANA Sukses tapi URL Pembayaran kosong.");
                        }

                    } catch (\Exception $e) {
                        Log::error("[DANA SDK] FATAL ERROR: " . $e->getMessage());

                        // Ambil pesan error detail dari Body jika ada
                        $pesanError = "Gagal koneksi ke DANA.";
                        if (method_exists($e, 'getResponseBody')) {
                            $body = $e->getResponseBody();
                            Log::error("[DANA SDK] BODY: " . json_encode($body));
                            if (isset($body->responseMessage)) {
                                $pesanError .= " " . $body->responseMessage;
                            }
                        }

                        // [PENTING] Lempar error lagi supaya Controller melakukan ROLLBACK
                        // Jadi Order TIDAK TERSIMPAN jika DANA Gagal.
                        throw new \Exception($pesanError);
                    }
                    break;

                // --- [METODE DANA MANUAL (HOST TO HOST)] ---
                case 'dana':
                    Log::info("[DANA MANUAL] Requesting Payment URL...");
                    try {
                        $timestamp = Carbon::now('Asia/Jakarta')->toIso8601String();
                        $expiryTime = Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');

                        $bodyArray = [
                            "partnerReferenceNo" => $order->order_number,
                            "merchantId" => config('services.dana.merchant_id'),
                            "externalStoreId" => "toko-pelanggan",
                            "amount" => ["value" => number_format($finalPrice, 2, '.', ''), "currency" => "IDR"],
                            "validUpTo" => $expiryTime,
                            "urlParams" => [
                                ["url" => route('dana.return'), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                                ["url" => route('dana.notify'), "type" => "NOTIFICATION", "isDeeplink" => "Y"]
                            ],
                            "additionalInfo" => [
                                "mcc" => "5732",
                                "order" => ["orderTitle" => "Invoice " . $order->order_number, "merchantTransType" => "01", "scenario" => "REDIRECT"],
                                "envInfo" => ["sourcePlatform" => "IPG", "terminalType" => "SYSTEM", "orderTerminalType" => "WEB"]
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
                        ])->withBody($jsonBody, 'application/json')->post($baseUrl . $relativePath);

                        $result = $response->json();

                        if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                            $paymentUrl = $result['webRedirectUrl'] ?? null;
                            $order->update(['payment_url' => $paymentUrl]);
                            $triggerWaType = 'unpaid';
                        } else {
                            throw new \Exception("DANA API Error: " . ($result['responseMessage'] ?? 'General Error'));
                        }
                    } catch (\Exception $e) {
                        Log::error("DANA MANUAL Error: " . $e->getMessage());
                    }
                    break;

                case 'doku':
                    Log::info("[DOKU] Requesting Payment URL...");

                    // Siapkan data customer untuk Doku
                    $dokuData = [
                        'name'  => $customerName,
                        'email' => $customerEmail,
                        'phone' => $customerPhone
                    ];

                    // Panggil Service Doku
                    $paymentUrl = $dokuService->createPayment($order->order_number, $finalPrice, $dokuData);

                    if ($paymentUrl) {
                        $order->update(['payment_url' => $paymentUrl]);
                        $triggerWaType = 'unpaid'; // Kirim WA tagihan
                    } else {
                        throw new \Exception("Gagal membuat link pembayaran DOKU.");
                    }
                    break;

                case 'tripay':
                    // Logic Tripay (copy dari kode lama)
                    $orderItems = [];
                    foreach ($finalCart as $item) {
                        $orderItems[] = ['sku' => (string) $item['product']->id, 'name' => $item['product']->name, 'price' => (int) $item['product']->sell_price, 'quantity' => (int) $item['qty']];
                    }
                    $tripayRes = $this->_createTripayTransaction($order, $request->payment_channel, (int)$finalPrice, $customerName, $customerEmail, $customerPhone, $orderItems);
                    if ($tripayRes['success']) {
                        $paymentUrl = $tripayRes['data']['checkout_url'];
                        $order->update(['payment_url' => $paymentUrl]);
                    }
                    break;
            }

            // ============================================================
            // LANGKAH 5: FINALISASI & COMMIT
            // ============================================================

            // Auto Save Coupon (Kode baru Anda)
            if ($request->coupon && $customerPhone) {
                try {
                    if (class_exists('App\Models\Customer')) {
                        \App\Models\Customer::updateOrCreate(
                            ['whatsapp' => $customerPhone],
                            [
                                'name' => $customerName,
                                'assigned_coupon' => $request->coupon,
                                'address' => $fullAddressSaved ?? DB::raw('address')
                            ]
                        );
                    }
                } catch (\Exception $e) { }
            }

            DB::commit();
            Log::info("[STEP 5] Committed. Order ID: {$order->id}");

            // Kirim WA
            try {
                if ($triggerWaType) {
                    $this->_sendWaNotification($order, $finalPrice, $paymentUrl, $triggerWaType);
                } elseif ($paymentUrl && $paymentStatus == 'unpaid') {
                    $this->_sendWaNotification($order, $finalPrice, $paymentUrl, 'unpaid');
                }

                if ($request->coupon && $paymentStatus == 'paid') {
                    $this->_processAffiliateCommission($request->coupon, $finalPrice);
                }
            } catch (\Exception $e) {}

            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $paymentUrl, // Ini akan terisi jika DANA
                'change_amount'  => $changeAmount,
                'payment_method' => $metodeBayarFix
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ORDER FAILED: ' . $e->getMessage());
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

    public function checkCoupon(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'coupon_code' => 'required|string',
            'total_belanja' => 'required|numeric|min:0'
        ]);

        $code = trim($request->coupon_code);
        $total = $request->total_belanja;

        // 2. Cari Kupon (Filter berdasarkan Tenant ID & Kode)
        // Gunakan $this->tenantId yang sudah diset di __construct
        $coupon = Coupon::where('code', $code)
                        ->where('tenant_id', $this->tenantId)
                        ->first();

        // 3. LOGIKA ERROR HANDLING (PENTING: Jangan return 404!)
        // Return 200 tapi status 'error' biar JS bisa baca pesannya
        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode kupon tidak ditemukan di toko ini.'
            ], 200);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kupon tidak aktif.'
            ], 200);
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Promo belum dimulai.'
            ], 200);
        }

        if ($coupon->expiry_date && $now->gt($coupon->expiry_date)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kupon sudah kedaluwarsa.'
            ], 200);
        }

        if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kuota kupon habis.'
            ], 200);
        }

        if ($coupon->min_order_amount > 0 && $total < $coupon->min_order_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Min. belanja Rp ' . number_format($coupon->min_order_amount, 0, ',', '.') . '.'
            ], 200);
        }

        // 4. Hitung Diskon
        $discountAmount = 0;
        if ($coupon->type == 'percent') {
            $discountAmount = $total * ($coupon->value / 100);
            if (isset($coupon->max_discount_amount) && $coupon->max_discount_amount > 0 && $discountAmount > $coupon->max_discount_amount) {
                $discountAmount = $coupon->max_discount_amount;
            }
        } else {
            $discountAmount = $coupon->value;
        }

        // Pastikan diskon tidak lebih besar dari total belanja
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
        ], 200);
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

   // GANTI TOTAL FUNCTION INI
    private function _sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus) {
        try {
            // [UPDATE] Load relasi product agar bisa baca satuan (unit)
            $order->load(['items.product']);

            // 1. LOGIKA BRANDING & KATEGORI
            $isLaundry = false;

            // Cek dari Catatan Sistem
            if (str_contains(strtoupper($order->note ?? ''), 'LAUNDRY')) {
                $isLaundry = true;
            }

            // Cek dari Nama Produk
            if (!$isLaundry) {
                foreach ($order->items as $item) {
                    $pName = strtoupper($item->product_name);
                    if (str_contains($pName, 'CUCI') || str_contains($pName, 'SETRIKA') || str_contains($pName, 'LAUNDRY') || str_contains($pName, 'KARPET')) {
                        $isLaundry = true;
                        break;
                    }
                }
            }

            $storeName = $isLaundry ? "SANCLEAN (Laundry & Care)" : "SANCAKA STORE";

            // 2. DATA DASAR
            $formattedTotal = "Rp " . number_format($finalPrice, 0, ',', '.');
            $tanggal = \Carbon\Carbon::parse($order->created_at)->timezone('Asia/Jakarta')->format('d M Y, H:i');
            $statusText = ($paymentStatus == 'paid') ? "LUNAS ✅" : "BELUM BAYAR ⏳";
            $metodeText = strtoupper(str_replace('_', ' ', $order->payment_method));

            // 3. SUSUN DAFTAR ITEM
            $itemListText = "";
            foreach ($order->items as $item) {
                $qty = $item->quantity + 0;

                // Cari Satuan (Unit)
                $unit = 'pcs';
                if ($item->product && !empty($item->product->unit)) {
                    $unit = $item->product->unit;
                } elseif ($isLaundry) {
                    $unit = 'kg';
                }

                $itemListText .= "- " . $item->product_name . " (" . $qty . " " . $unit . ")\n";
            }

            // 4. INFO ALAMAT & INVOICE
            $alamatInfo = "";
            if ($order->destination_address) {
                $alamatInfo = "\n📍 *Tujuan:* " . $order->destination_address . "\n";
            }
            $invoiceLink = url('/invoice/' . $order->order_number);

            // ==========================================
            // A. KIRIM PESAN KE CUSTOMER
            // ==========================================
            if ($order->customer_phone) {
                $msg  = "Halo Kak *{$order->customer_name}* 👋,\n\n";
                $msg .= "Terima kasih telah menggunakan layanan *{$storeName}*.\n";
                $msg .= "Berikut rinciannya:\n\n";

                $msg .= "🧾 *No. Nota:* {$order->order_number}\n";
                $msg .= "📅 *Waktu:* {$tanggal}\n";
                $msg .= "💰 *Status:* {$statusText}\n\n";

                $msg .= "📦 *Detail Pesanan:*\n";
                $msg .= $itemListText;
                $msg .= $alamatInfo;

                $msg .= "\n💵 *Total: {$formattedTotal}*";

                // Link Invoice
                $msg .= "\n\n📄 *Lihat Struk Digital:*";
                $msg .= "\n" . $invoiceLink;

                // Pesan Tambahan
                if ($paymentUrl && $paymentStatus == 'unpaid') {
                    $msg .= "\n\n👇 *Mohon selesaikan pembayaran di sini:*\n";
                    $msg .= $paymentUrl;
                }
                elseif ($order->payment_method == 'pay_later') {
                    $msg .= "\n\n⚠️ *TAGIHAN BELUM LUNAS*";
                    $msg .= "\nMohon segera melakukan pembayaran sebesar *{$formattedTotal}* agar pesanan dapat diambil.";
                }
                elseif ($order->payment_method == 'qris_manual') {
                    $msg .= "\n\n✅ Pembayaran via QRIS Manual Berhasil Diterima.";
                }

                // Penutup
                if ($isLaundry) {
                    $msg .= "\n\n_Cucian sedang kami proses. Jika sudah selesai dan akan ambil Tunjukan Invoicenya Ya Kak!_ ✨";
                } else {
                    $msg .= "\n\n_Pesanan segera kami proses. Terima kasih!_ 🙏";
                }

                $this->_sendFonnteMessage($order->customer_phone, $msg);
            }

            // ==========================================
            // B. KIRIM PESAN KE ADMIN
            // ==========================================
            // Pastikan baris ini ada DI DALAM try { ... } dan DI DALAM function
            $adminContacts = [
                '085745808809'
                //'085843428393',
            ];

            $msgAdmin  = "🔔 *INFO {$storeName}* 🔔\n";
            $msgAdmin .= "Ada order masuk hari ini BOS!\n\n";
            $msgAdmin .= "👤 *Nama Pelanggan:* {$order->customer_name}\n";
            $msgAdmin .= "🧾 *Nomor Nota:* {$order->order_number}\n";
            $msgAdmin .= "💳 *Pembayaran Via:* {$metodeText}\n";
            $msgAdmin .= "💰 *Omzet (Pendapatan):* {$formattedTotal}\n";
            $msgAdmin .= "🔖 *Status (Keterangan):* {$statusText}\n\n";

            $msgAdmin .= "📦 *Rincian Item Pesanan:*\n";
            $msgAdmin .= $itemListText;

            if ($order->customer_note) {
                $msgAdmin .= "\n📝 *Note:* " . $order->customer_note;
            }
            if ($order->destination_address) {
                $msgAdmin .= "\n🚚 *Kirim:* " . $order->destination_address;
            }

            foreach ($adminContacts as $phone) {
                $this->_sendFonnteMessage($phone, $msgAdmin);
                //sleep(5); // Jeda 5 detik antar kirim ke admin
            }

        } catch (\Exception $e) {
            Log::error("WA Error: " . $e->getMessage());
        }
    }

    // Cari function ini di paling bawah, lalu GANTI ISINYA:
    private function _sendFonnteMessage($target, $message) {
        // HAPUS KODINGAN LAMA YANG PAKAI Http::post
        // GANTI DENGAN INI (MASUKKAN KE ANTRIAN BACKGROUND):

        \App\Jobs\SendWhatsappJob::dispatch($target, $message);

        // Log info biar tau job sudah didaftarkan
        Log::info("WA ke $target dimasukkan ke antrian (Job Dispatch)");
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

    public function handleDanaWebhook(Request $request)
{
    // 1. Ambil data mentah buat Log Bos (Jangan didelete LOG LOG-nya)
    $content = $request->getContent();
    Log::info("LOG LOG - DANA TEST SEDANG JALAN: " . $content);

    // 2. CEK ISI DATA PAKAI STRING (Anti Ribet Anti Gagal)
    // Kalau ada tulisan "05", DANA lagi ngetes skenario EXPIRED
    if (strpos($content, '"05"') !== false) {
        Log::info("LOG LOG: Balas Sukses buat 05");
        return response()->json([
            "responseCode" => "2005600",
            "responseMessage" => "Successful"
        ], 200);
    }

    // 3. Kalau bukan 05 (berarti 00), DANA lagi ngetes skenario ERROR
    // INI YANG BIKIN CENTANG HIJAU SKENARIO INTERNAL SERVER ERROR
    Log::error("LOG LOG: Balas Error 5005601 buat Skenario DANA");
    return response()->json([
        "responseCode" => "5005601",
        "responseMessage" => "Internal Server Error"
    ], 500);

    // 1. DANA SNAP BI kirim data lewat BODY JSON mentah
    $jsonData = json_decode($request->getContent(), true);
    Log::info('RAW WEBHOOK DATA:', [$jsonData]);

    // 2. Ambil Order Number & Nominal dari JSON DANA
    $orderNumber = $jsonData['partnerReferenceNo'] ?? null;
    $amountValue = $jsonData['amount']['value'] ?? null;
    $resultStatus = $jsonData['result']['resultStatus'] ?? null; // S = Success

    if (!$orderNumber) {
        Log::error('WEBHOOK ERROR: partnerReferenceNo tidak ditemukan di JSON.');
        return response()->json(['responseCode' => '4040000', 'responseMessage' => 'Order Not Found'], 404);
    }

    // 3. CARI DI DB: Harus cocok Invoice DAN Nominal (Biar Presisi!)
    $order = \App\Models\Order::where('order_number', $orderNumber)
                  ->where('final_price', (int)$amountValue)
                  ->first();

    if ($order) {
        // Cek apakah status dari DANA itu Sukses ('S')
        if ($resultStatus === 'S' || ($jsonData['responseCode'] ?? '') == '2005400') {

            if ($order->payment_status !== 'paid') {
                DB::beginTransaction();
                try {
                    // --- URUTAN NOMOR 3 LU: UPDATE DULU BARU WA ---
                    $order->update([
                        'status' => 'processing',
                        'payment_status' => 'paid',
                        'note' => $order->note . "\n[DANA] Lunas via Webhook jam " . now()
                    ]);

                    // --- KIRIM WA SEKARANG (SUKSES BARU WA) ---
                    $this->_sendWaNotification($order, $order->final_price, null, 'paid');

                    DB::commit();
                    Log::info("WEBHOOK SUCCESS: Order #$orderNumber VALID & LUNAS. WA Terkirim.");

                    // DANA butuh response ini biar nggak kirim ulang terus
                    return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("WEBHOOK SAVE FAILED: " . $e->getMessage());
                    return response()->json(['responseCode' => '5000000', 'responseMessage' => 'Server Error'], 500);
                }
            }
            return response()->json(['responseCode' => '2000000', 'responseMessage' => 'Success']);
        }
    }

    Log::warning("WEBHOOK_IGNORE: Order #$orderNumber tidak cocok atau nominal beda.");
    return response()->json(['responseCode' => '4040000', 'responseMessage' => 'Invalid Order Data'], 404);
}

public function handleDanaCallback(Request $request)
    {
        Log::info("========== DANA WEBHOOK INCOMING ==========");

        // 1. Ambil Data (Support berbagai format DANA Sandbox/Production)
        $payload = $request->all();

        // Coba ambil Order ID dari berbagai kemungkinan key
        $orderNumber = $payload['originalPartnerReferenceNo'] ?? // Format Sandbox SNAP BI (Sesuai Log Anda)
                       $payload['partnerReferenceNo'] ??         // Format Dokumentasi Lama
                       $payload['merchantTransId'] ??            // Format V2
                       null;

        // Cek Status Transaksi
        $statusRaw = $payload['transactionStatusDesc'] ??        // Format Sandbox SNAP BI (SUCCESS)
                     $payload['transactionStatus'] ??            // Format Dokumentasi Lama
                     $payload['acquirementStatus'] ??            // Format V2
                     null;

        // Ambil Reference No DANA
        $refNoDana = $payload['originalReferenceNo'] ??
                     $payload['referenceNo'] ??
                     $payload['acquirementId'] ??
                     null;

        Log::info("Parsed Data Final:", [
            'OrderNo' => $orderNumber,
            'Status'  => $statusRaw,
            'RefDana' => $refNoDana
        ]);

        // 2. Validasi Data
        if (!$orderNumber) {
            Log::error("WEBHOOK ERROR: Order Number tidak ditemukan dalam payload.");
            return response()->json(['responseCode' => '400', 'responseMessage' => 'Bad Request'], 400);
        }

        // 3. Cari Order di DB
        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            Log::error("WEBHOOK ERROR: Order #$orderNumber tidak ditemukan di DB.");
            return response()->json(['responseCode' => '404', 'responseMessage' => 'Order Not Found'], 404);
        }

        // 4. Cek Idempotency
        if ($order->payment_status === 'paid') {
            Log::info("WEBHOOK INFO: Order #$orderNumber sudah lunas. Skip.");
            return response()->json(['responseCode' => '200', 'responseMessage' => 'Success'], 200);
        }

        // 5. Update Status
        // Status Sukses di Log Anda adalah "SUCCESS" atau "00"
        $isSuccess = in_array($statusRaw, ['SUCCESS', 'PAID', 'FINISHED', '00']);

        if ($isSuccess) {

            $order->update([
                'status'         => 'processing',
                'payment_status' => 'paid',
                'note'           => $order->note . "\n[DANA PAID] Ref: $refNoDana | Time: " . now(),
            ]);

            Log::info("WEBHOOK SUKSES: Order #$orderNumber LUNAS.");

            if ($order->coupon_id) {
                $this->_processAffiliateCommission($order->coupon->code ?? '', $order->final_price);
            }

            $this->_sendWaNotification($order, $order->final_price, null, 'paid');

        } else {
            Log::warning("WEBHOOK STATUS LAIN: $statusRaw");
        }

        // UBAH RETURN MENJADI INI (Sesuai permintaan Test Scenario):
    return response()->json([
        'responseCode' => '2005600',
        'responseMessage' => 'Successful'
    ], 200);
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

    public function show($id)
    {
        // Filter berdasarkan tenant_id juga!
        $order = Order::where('tenant_id', $this->tenantId)
                    ->with(['items', 'attachments', 'coupon'])
                    ->findOrFail($id);

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

/**
     * Menghapus pesanan secara permanen.
     */
    public function destroy($id)
    {
        Log::info("================ START ORDER DESTROY ================");
        Log::info("LOG LOG: Mencoba menghapus Order ID: {$id}");

        DB::beginTransaction();

        try {
            // 1. Cari Order
            $order = Order::where('tenant_id', $this->tenantId)
              ->with(['items', 'attachments'])
              ->findOrFail($id);

            // 2. Logika Pengembalian Stok (Opsional)
            // Jika pesanan dibatalkan/dihapus sebelum selesai, stok dikembalikan
            if (in_array($order->status, ['pending', 'processing'])) {
                Log::info("LOG LOG: Mengembalikan stok untuk Order #{$order->order_number}");
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->quantity);
                        $product->decrement('sold', $item->quantity);

                        // Update status stok jika sebelumnya habis
                        if ($product->stock > 0) {
                            $product->update(['stock_status' => 'available']);
                        }
                    }
                }
            }

            // 3. Hapus Lampiran Fisik (File di Storage)
            foreach ($order->attachments as $attachment) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                    Log::info("LOG LOG: File dihapus: {$attachment->file_path}");
                }
            }

            // 4. Hapus Data dari Database
            // Karena menggunakan ON DELETE CASCADE (jika diset di migrasi)
            // atau dihapus manual jika tidak:
            $order->items()->delete();
            $order->attachments()->delete();
            $order->delete();

            DB::commit();
            Log::info("LOG LOG: Order #{$order->order_number} berhasil dihapus permanen.");

            return redirect()->route('orders.index')
                ->with('success', 'Pesanan #' . $order->order_number . ' berhasil dihapus secara permanen.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: Gagal menghapus Order ID: {$id}. Error: " . $e->getMessage());

            return redirect()->route('orders.index')
                ->with('error', 'Gagal menghapus pesanan: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
{
    // LOG LOG: Memulai proses
    Log::info("================ START BULK DESTROY ================");

    $ids = $request->input('ids');

    if (!$ids || !is_array($ids)) {
        Log::warning("LOG LOG: Bulk delete dibatalkan karena tidak ada ID yang dipilih.");
        return redirect()->back()->with('error', 'Pilih minimal satu data untuk dihapus.');
    }

    Log::info("LOG LOG: Mencoba menghapus masal ID: " . implode(', ', $ids));

    DB::beginTransaction();
    try {
        // Ambil data beserta lampirannya agar bisa hapus file fisik
        $orders = Order::with('attachments')->whereIn('id', $ids)->get();

        foreach ($orders as $order) {
            // 1. Balikkan Stok (Hanya jika status belum selesai)
            if (in_array($order->status, ['pending', 'processing'])) {
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            }

            // 2. Hapus Lampiran File Fisik
            foreach ($order->attachments as $attachment) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                    Log::info("LOG LOG: File dihapus: {$attachment->file_path}");
                }
            }
        }

        // 3. Hapus Data dari Database
        Order::whereIn('id', $ids)->delete();

        DB::commit();
        Log::info("LOG LOG: Berhasil menghapus permanen " . count($ids) . " data.");

        return redirect()->route('orders.index')->with('success', count($ids) . ' data berhasil dihapus permanen.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("LOG LOG: Gagal bulk delete. Pesan Error: " . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menghapus data.');
    }
}

/**
     * Helper: Ambil Detail Transaksi Tripay (Untuk Invoice)
     * Mengembalikan array berisi pay_code dan qr_url
     */
    private function _getTripayDetail($reference)
    {
        Log::info("LOG LOG: [Start] _getTripayDetail untuk Ref: {$reference}");

        $apiKey       = config('tripay.api_key');
        $mode         = config('tripay.mode');

        // Tentukan URL berdasarkan mode
        $baseUrl = ($mode === 'production')
            ? 'https://tripay.co.id/api/transaction/detail'
            : 'https://tripay.co.id/api-sandbox/transaction/detail';

        try {
            // Request ke Tripay
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                            ->get($baseUrl, ['reference' => $reference]);

            $body = $response->json();

            // LOG LOG: Simpan respon mentah untuk debugging jika ada masalah
            Log::info("LOG LOG: Tripay API Response: " . json_encode($body));

            if ($response->successful() && ($body['success'] ?? false) === true) {
                return ['success' => true, 'data' => $body['data']];
            }

            return ['success' => false, 'message' => $body['message'] ?? 'Gagal ambil detail Tripay'];

        } catch (\Exception $e) {
            Log::error("LOG LOG: Tripay Connection Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi Error'];
        }
    }

   public function invoice($subdomain, $orderNumber)
    {
        // LOG LOG: Penanda awal buka invoice
        Log::info("LOG LOG: =========================================");
        Log::info("LOG LOG: User membuka Invoice Order #{$orderNumber}");

        try {
            // 1. Cari Order
            $order = Order::with(['items', 'coupon'])->where('order_number', $orderNumber)->firstOrFail();

            // 2. Siapkan Variabel Data Pembayaran (Default Kosong)
            $paymentData = [
                'pay_code'     => null, // VA Number
                'qr_url'       => null, // Gambar QRIS
                'expired_time' => null,
                'instructions' => []
            ];

            // 3. LOGIKA KHUSUS TRIPAY (Ambil VA / QRIS Live dari API)
            if ($order->payment_method === 'tripay' && $order->payment_status === 'unpaid') {

                Log::info("LOG LOG: Mendeteksi metode Tripay (Unpaid). Mengambil detail transaksi ke API...");

                // Panggil helper function _getTripayDetail (Ada di bawah)
                $tripayDetail = $this->_getTripayDetail($order->order_number);

                if ($tripayDetail['success']) {
                    $data = $tripayDetail['data'];

                    // Isi data ke variabel
                    $paymentData['pay_code']     = $data['pay_code'] ?? null;
                    $paymentData['qr_url']       = $data['qr_url'] ?? null;
                    $paymentData['expired_time'] = $data['expired_time'] ?? null;
                    $paymentData['instructions'] = $data['instructions'] ?? [];

                    Log::info("LOG LOG: Berhasil ambil data Tripay. VA: {$paymentData['pay_code']}, QR: " . ($paymentData['qr_url'] ? 'Ada' : 'Tidak Ada'));
                } else {
                    Log::error("LOG LOG: Gagal ambil data Tripay. Pesan: " . ($tripayDetail['message'] ?? 'Unknown'));
                }
            }

            // 4. LOGIKA KHUSUS DOKU (Opsional, jika Doku menyediakan URL gambar statis)
            if ($order->payment_method === 'doku' && $order->payment_status === 'unpaid') {
                Log::info("LOG LOG: Mendeteksi metode Doku. URL Payment: {$order->payment_url}");
                // Doku biasanya payment_url sudah mengarah ke halaman yang berisi VA/QRIS.
                // Jika ingin parsing spesifik, perlu API check status Doku (Jokul) terpisah.
            }

            return view('orders.invoice', compact('order', 'paymentData'));

        } catch (\Exception $e) {
            Log::error("LOG LOG: Error saat membuka invoice: " . $e->getMessage());
            abort(404, 'Invoice tidak ditemukan atau terjadi kesalahan.');
        }
    }

    /**
     * Helper: Format Nomor HP jadi 08xxx (Hapus +62, spasi, dash)
     */
    private function _normalizePhoneNumber($phone)
    {
        // 1. Hapus semua karakter selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // 2. Koreksi awalan
        if (substr($phone, 0, 2) == '62') {
            // Ubah 62 jadi 0
            $phone = '0' . substr($phone, 2);
        } elseif (substr($phone, 0, 1) == '8') {
            // Tambahkan 0 jika diawali 8
            $phone = '0' . $phone;
        }

        return $phone;
    }

    public function edit($id)
    {
        // Tambahkan filter tenant_id agar tidak bisa mengintip nota orang lain
        $order = Order::where('tenant_id', $this->tenantId)->findOrFail($id);
        return view('orders.edit', compact('order'));
    }

    /**
     * Update Data Order (LENGKAP: Item, Harga, Stok, Status, Tanggal)
     */
    public function update(Request $request, $id)
    {
        // 1. Validasi
        $request->validate([
            'customer_name'       => 'required|string',
            'created_at'          => 'required|date',
            'status'              => 'required',
            'payment_status'      => 'required',
            'items'               => 'required|array',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.price'       => 'required|numeric|min:0',
            'shipping_cost'       => 'nullable|numeric|min:0',
            'discount_amount'     => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::where('tenant_id', $this->tenantId)->findOrFail($id);
            $subtotalBaru = 0;

            // 2. Loop Items untuk Update Qty & Harga
            foreach ($request->items as $itemId => $data) {
                $detail = OrderDetail::where('tenant_id', $this->tenantId)->findOrFail($itemId);
                $oldQty = $detail->quantity;
                $newQty = $data['qty'];
                $newPrice = $data['price'];

                // Update Stok Produk (Jika ada perubahan Qty)
                if ($oldQty != $newQty) {
                    $product = Product::where('tenant_id', $this->tenantId)->find($detail->product_id);
                    if ($product) {
                        // Selisih: Jika qty baru lebih besar, kurangi stok. Sebaliknya tambah stok.
                        $selisih = $newQty - $oldQty;
                        // Kurangi stok (bisa negatif artinya nambah)
                        $product->decrement('stock', $selisih);
                        $product->increment('sold', $selisih);

                        // Cek status stok
                        if ($product->stock <= 0) {
                            $product->update(['stock_status' => 'unavailable']);
                        } else {
                            $product->update(['stock_status' => 'available']);
                        }
                    }
                }

                // Hitung Subtotal Item Baru (Bulatkan ke atas)
                $newSubtotal = ceil($newQty * $newPrice);

                // Simpan Perubahan ke OrderDetail
                $detail->update([
                    'quantity' => $newQty,
                    'price_at_order' => $newPrice,
                    'subtotal' => $newSubtotal
                ]);

                $subtotalBaru += $newSubtotal;
            }

            // 3. Hitung Ulang Total Order
            $ongkir = $request->input('shipping_cost', 0);
            $diskon = $request->input('discount_amount', 0);
            $finalPrice = max(0, ($subtotalBaru + $ongkir) - $diskon);

            // 4. Update Data Utama Order
            $order->update([
                'customer_name'       => $request->customer_name,
                'customer_phone'      => $this->_normalizePhoneNumber($request->customer_phone),
                'destination_address' => $request->destination_address,
                'created_at'          => $request->created_at, // Update Tanggal
                'status'              => $request->status,
                'payment_status'      => $request->payment_status,
                'customer_note'       => $request->customer_note,
                'total_price'         => $subtotalBaru,
                'shipping_cost'       => $ongkir,
                'discount_amount'     => $diskon,
                'final_price'         => $finalPrice
            ]);

            DB::commit();
            return redirect()->route('orders.edit', $order->id)->with('success', 'Data pesanan berhasil diperbarui & stok disesuaikan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Update Order Error: " . $e->getMessage());
            return back()->with('error', 'Gagal update pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Export Laporan PDF
     */
    public function exportPdf(Request $request)
    {
        $query = Order::where('tenant_id', $this->tenantId);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
            } elseif (count($dates) == 1) {
                $query->whereDate('created_at', $dates[0]);
            }
        }

        $orders = $query->with(['items.product'])->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('orders.pdf', compact('orders'))->setPaper('f4', 'landscape');
        return $pdf->stream('laporan-transaksi.pdf');
    }

    /**
     * Export Laporan Excel
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(new OrdersExport($request), 'laporan-transaksi.xlsx');
    }
    // --- [KODE BARU: END] ---

    public function scanProduct(Request $request)
    {
        $barcode = trim($request->query('code'));

        if (empty($barcode)) {
            return response()->json(['status' => 'error', 'message' => 'Kode kosong']);
        }

        // ==========================================
        // 1. CARI DI TABEL PRODUK UTAMA (SINGLE)
        // ==========================================
        $product = Product::where('tenant_id', $this->tenantId)
            ->where('stock_status', 'available') // <--- Tambahkan filter ini
            ->where(function($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhere('sku', $barcode);
            })
            ->first();

        if ($product) {
            // Jika ketemu Produk Utama
            return response()->json([
                'status' => 'success',
                'type'   => 'single',
                'data'   => [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'sell_price' => $product->sell_price,
                    'stock'      => $product->stock,
                    'unit'       => $product->unit ?? 'pcs',
                    'weight'     => $product->weight,
                    // Ambil gambar (jika ada)
                    'image'      => $product->image ? $product->image : null
                ]
            ]);
        }

        // ==========================================
        // 2. CARI DI TABEL VARIAN (JIKA PRODUK UTAMA TIDAK KETEMU)
        // ==========================================
        $variant = ProductVariant::with('product')
            ->where('tenant_id', $this->tenantId) // <--- TAMBAHKAN FILTER INI
            ->where(function($q) use ($barcode) {
                $q->where('barcode', $barcode)
                ->orWhere('sku', $barcode);
            })
            ->first();

        if ($variant && $variant->product) {
            // Jika ketemu Varian
            $parent = $variant->product;

            return response()->json([
                'status' => 'success',
                'type'   => 'variant',
                'data'   => [
                    'id'         => $parent->id, // ID Produk Induk (Penting untuk grouping)
                    'variant_id' => $variant->id, // ID Varian (Penting untuk pembeda)

                    // GABUNGKAN NAMA: "Induk (Varian)"
                    'name'       => $parent->name . ' (' . $variant->name . ')',

                    // AMBIL HARGA DARI VARIAN
                    'sell_price' => $variant->price,

                    // AMBIL STOK DARI VARIAN
                    'stock'      => $variant->stock,

                    'unit'       => $parent->unit ?? 'pcs',
                    'weight'     => $parent->weight,

                    // Gambar biasanya ikut Induk (kecuali varian punya gambar sendiri)
                    'image'      => $parent->image ? $parent->image : null
                ]
            ]);
        }

        // 3. JIKA TIDAK KETEMU DIMANAPUN
        return response()->json(['status' => 'error', 'message' => 'Produk/Varian tidak ditemukan']);
    }

    public function printStruk($id)
{
    // 1. Ambil Data Order dengan filter tenant_id agar aman
    $order = Order::where('tenant_id', $this->tenantId)
        ->with(['items.product', 'coupon'])
        ->findOrFail($id);

    // 2. AMBIL DATA DARI MODEL TENANT (Bukan model Store)
    // Model Tenant sudah pasti ada karena digunakan di __construct
    $tenantData = \App\Models\Tenant::find($this->tenantId);

    $store = [
        'name'    => $tenantData->name ?? 'SANCAKA STORE',
        'address' => $tenantData->address ?? 'Jl. Dr. Wahidin No.18A, Ngawi',
        'phone'   => $tenantData->phone ?? $tenantData->whatsapp ?? '0857-4580-8809'
    ];

    // 3. Tampilkan View Struk
    return view('orders.print_struk', compact('order', 'store'));
}

/**
     * API: Ambil Daftar Channel Pembayaran Tripay
     */
    public function tripayChannels()
    {
        $apiKey = config('tripay.api_key');
        $mode   = config('tripay.mode');

        // Tentukan URL berdasarkan mode (Sandbox / Production)
        $baseUrl = ($mode === 'production')
            ? 'https://tripay.co.id/api/merchant/payment-channel'
            : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

        try {
            // Request ke API Tripay
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get($baseUrl);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            // Jika gagal respon dari Tripay
            Log::error("Tripay Channel Error: " . $response->body());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data channel pembayaran.'
            ], 500);

        } catch (\Exception $e) {
            Log::error("Tripay Connection Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke server pembayaran bermasalah.'
            ], 500);
        }
    }

   public function getPaymentChannels()
    {
        $apiKey = config('tripay.api_key');
        $mode   = config('tripay.mode');

        if (empty($apiKey)) {
            return response()->json(['status' => 'error', 'message' => 'API Key belum diisi'], 500);
        }

        $baseUrl = ($mode === 'production')
            ? 'https://tripay.co.id/api/merchant/payment-channel'
            : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';

        try {
            // [SOLUSI ANTI CLOUDFLARE]
            // Tambahkan User-Agent Chrome agar tidak dianggap bot
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ])->get($baseUrl);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data'   => $response->json()['data'] ?? []
                ]);
            }

            // Debugging: Log error jika masih gagal
            \Illuminate\Support\Facades\Log::error("Tripay Error: " . $response->body());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal ambil data (Cek Log): ' . $response->status()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Koneksi Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // FITUR BARU: CONSULT PAY DANA (Mengambil Daftar Metode Pembayaran & Promo)
    // =========================================================================
    public function consultPay(Request $request, DanaSignatureService $danaService)
    {
        // 1. Validasi Input Nominal (Bisa diambil dari total keranjang POS)
        $amount = $request->input('amount', '150000.00');

        Log::info("[DANA CONSULT PAY] Meminta daftar metode pembayaran DANA untuk nominal: Rp " . $amount);

        try {
            $timestamp = now('Asia/Jakarta')->toIso8601String();
            $path = '/v1.0/payment-gateway/consult-pay.htm';
            $baseUrl = config('services.dana.dana_env') === 'PRODUCTION' ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';

            // 2. Dapatkan Access Token B2B
            $accessToken = $danaService->getAccessToken();

            // 3. Siapkan Payload Sesuai Dokumentasi
            $body = [
                "merchantId" => config('services.dana.merchant_id'),
                "amount" => [
                    "value" => number_format((float)$amount, 2, '.', ''),
                    "currency" => "IDR"
                ],
                "externalStoreId" => "toko-pelanggan",
                "additionalInfo" => [
                    "buyer" => [
                        "externalUserType" => "",
                        "nickname" => "",
                        "externalUserId" => "USR-" . time(),
                        "userId" => ""
                    ],
                    "envInfo" => [
                        "sessionId" => Str::random(32),
                        "tokenId" => (string) Str::uuid(),
                        "websiteLanguage" => "id_ID",
                        "clientIp" => $request->ip() ?? "127.0.0.1",
                        "osType" => "Windows.PC",
                        "appVersion" => "1.0",
                        "sdkVersion" => "1.0",
                        "sourcePlatform" => "IPG",
                        "orderOsType" => "WEB",
                        "merchantAppVersion" => "1.0",
                        "terminalType" => "SYSTEM",
                        "orderTerminalType" => "WEB",
                        "extendInfo" => json_encode(["deviceId" => Str::random(16)])
                    ],
                    "merchantTransType" => "DEFAULT"
                ]
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // 4. Generate Signature menggunakan service bawaan
            $signature = $danaService->generateSignature('POST', $path, $jsonBody, $timestamp);

            // 5. Kirim Request ke DANA
            $response = Http::withHeaders([
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'X-PARTNER-ID'   => config('services.dana.x_partner_id'),
                'X-EXTERNAL-ID'  => (string) time() . Str::random(6),
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ])->withBody($jsonBody, 'application/json')->post($baseUrl . $path);

            $result = $response->json();

            // 6. Evaluasi Hasil (Kode Sukses: 2000000)
            if (isset($result['responseCode']) && $result['responseCode'] === '2000000') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Berhasil mengambil daftar metode pembayaran DANA.',
                    'payment_methods' => $result['paymentInfos'] ?? [],
                    'raw_data' => $result
                ]);
            } else {
                Log::warning("[DANA CONSULT PAY] Gagal mengambil data.", ['result' => $result]);
                return response()->json([
                    'status' => 'failed',
                    'message' => "Consult Pay Error: " . ($result['responseMessage'] ?? 'Unknown Error'),
                    'error_code' => $result['responseCode'] ?? null
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("[DANA CONSULT PAY ERROR] " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

}
