<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\View\Composers\CustomerLayoutComposer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Jangan lupa import Cache
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\Pesanan;
use App\Models\Pengguna;
use App\Models\Users;
use App\Models\ScannedPackage;
use App\Models\Setting;
use App\Models\Store;
use Carbon\Carbon;

use App\Services\DanaSignatureService;

class DashboardController extends Controller
{
public function index()
    {
        $customer = Auth::user();
        $customerId = $customer->id_pengguna; 

        // --- PENGAMBILAN DATA STATISTIK ---
        $saldo = $customer->saldo;
        
        $semuaPesananQuery = Pesanan::where('id_pengguna_pembeli', $customerId);

        $totalPesanan = (clone $semuaPesananQuery)->count();
        $pesananSelesai = (clone $semuaPesananQuery)->where('status_pesanan', 'Tiba di Tujuan')->count();
        $pesananPending = (clone $semuaPesananQuery)->whereIn('status_pesanan', ['pending', 'Menunggu Pembayaran'])->count();
        
        // Hapus 'take(5)' agar semua data terambil
        $recentOrders = (clone $semuaPesananQuery)->latest('tanggal_pesanan')->get();
        $recentSpxScans = ScannedPackage::where('user_id', $customerId)->latest()->take(5)->get();

        // --- PENGAMBILAN DATA UNTUK GRAFIK ---
        $orderChartData = $this->getOrderChartData($customerId);
        $spxChartData = $this->getSpxScanChartData($customerId);

        // --- REKAPITULASI PENGELUARAN EKSPEDISI ---
        $rekapEkspedisi = Cache::remember('cust_dashboard_rekap_exp_' . $customerId . '_v2', 600, function () use ($customerId) {
            
            // 1. MASTER LIST
            $courierMap = [
                'jne' => ['name' => 'JNE', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jne.png'],
                'tiki' => ['name' => 'TIKI', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/tiki.png'],
                'posindonesia' => ['name' => 'POS Indonesia', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/posindonesia.png'],
                'sicepat' => ['name' => 'SiCepat', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sicepat.png'],
                'sap' => ['name' => 'SAP Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/sap.png'],
                'ncs' => ['name' => 'NCS Kurir', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjxj3iyyZEjK2L4A4yCIr_E-4W3hF2lk_yb-t0Oj2oFPErCPCMHie5LHqps02xMb6sNa-Gqz5NSX_P_hzWlYpUpJUlCD4iN6_QxiSG9fzY4bsZ9XvLFDn7HCiORtNvIlPfuQbSSdW96p7x7uN8ek3FWyHW9c2bznrFBQkoLd5A9sVAFVKWLfUhT3Dxh/s320/GKL41_NCS%20Kurir%20-%20Koleksilogo.com.jpg'],
                'idx' => ['name' => 'ID Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/idx.png'],
                'gojek' => ['name' => 'GoSend', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/gosend.png'],
                'grab' => ['name' => 'GrabExpress', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/grab.png'],
                'jnt' => ['name' => 'J&T Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jnt.png'],
                'indah' => ['name' => 'Indah Cargo', 'logo_url' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEicOAaLoH2eElQ93_gbkzhvk4dRhWVlk5wQsGgilihIB58321aHchlJLdjyz1ToS25P_nWrHJ_E4QBiW_OVlI7tQt7cZ5I0HZqk6StS7jZltLVvDXp2d5ZDLB9yklhV4x6z2iXyURURDv_unhf-U6vyiD_8to9OC4PBwMwyU_5wAqOiCl6tKiaTA-ri1Q/s851/Logo%20Indah%20Logistik%20Cargo@0.5x.png'],
                'jtcargo' => ['name' => 'J&T Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/jtcargo.png'],
                'lion' => ['name' => 'Lion Parcel', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/lion.png'],
                'spx' => ['name' => 'SPX Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/spx.png'],
                'ninja' => ['name' => 'Ninja Express', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/ninja.png'],
                'anteraja' => ['name' => 'Anteraja', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/anteraja.png'],
                'sentral' => ['name' => 'Sentral Cargo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/centralcargo.png'],
                'borzo' => ['name' => 'Borzo', 'logo_url' => 'https://tokosancaka.com/public/storage/logo-ekspedisi/borzo.png'],
            ];

            // 2. INISIALISASI WADAH
            $stats = [];
            foreach ($courierMap as $code => $info) {
                $stats[$info['name']] = [
                    'nama' => $info['name'],
                    'logo' => $info['logo_url'],
                    'filter_code' => $code,
                    'total_order' => 0,
                    'biaya_ongkir' => 0,
                    'biaya_asuransi' => 0,
                    'biaya_cod' => 0,
                    'total_pengeluaran' => 0,
                    'senders' => [],
                    'receivers' => [],
                    'cities_origin' => [],
                    'cities_dest' => [],
                    'dest_distribution' => [],
                ];
            }

            // 3. AMBIL DATA PESANAN
            $orders = Pesanan::where('id_pengguna_pembeli', $customerId)
                        ->select(
                            'expedition', 'shipping_cost', 'insurance_cost', 'cod_fee', 'ansuransi',
                            'sender_name', 'receiver_name', 'sender_regency', 'receiver_regency'
                        )
                        ->whereNotNull('expedition')
                        ->get();

            // 4. HITUNG DATA
            foreach ($orders as $order) {
                $parts = explode('-', $order->expedition);
                if (count($parts) >= 2) {
                    $dbCode = strtolower($parts[1]); 
                    if (isset($courierMap[$dbCode])) {
                        $displayName = $courierMap[$dbCode]['name'];

                        $stats[$displayName]['total_order']++;
                        $ongkir = $order->shipping_cost ?? 0;
                        $asuransi = ($order->ansuransi == 'iya' || $order->insurance_cost > 0) ? ($order->insurance_cost ?? 0) : 0;
                        $cod = $order->cod_fee ?? 0;

                        $stats[$displayName]['biaya_ongkir'] += $ongkir;
                        $stats[$displayName]['biaya_asuransi'] += $asuransi;
                        $stats[$displayName]['biaya_cod'] += $cod;
                        $stats[$displayName]['total_pengeluaran'] += ($ongkir + $asuransi + $cod);

                        if ($order->sender_name) $stats[$displayName]['senders'][strtoupper(trim($order->sender_name))] = true;
                        if ($order->receiver_name) $stats[$displayName]['receivers'][strtoupper(trim($order->receiver_name))] = true;
                        if ($order->sender_regency) $stats[$displayName]['cities_origin'][strtoupper(trim($order->sender_regency))] = true;
                        
                        if ($order->receiver_regency) {
                            $city = strtoupper(trim($order->receiver_regency));
                            $stats[$displayName]['cities_dest'][$city] = true;
                            if (!isset($stats[$displayName]['dest_distribution'][$city])) {
                                $stats[$displayName]['dest_distribution'][$city] = 0;
                            }
                            $stats[$displayName]['dest_distribution'][$city]++;
                        }
                    }
                }
            }

            // 5. MAPPING
            return collect($stats)->map(function ($item) {
                $topCities = collect($item['dest_distribution'])->sortDesc()->take(5);

                return (object) [
                    'nama' => $item['nama'],
                    'logo' => $item['logo'],
                    'filter_code' => $item['filter_code'],
                    'url_detail' => route('customer.pesanan.index', ['ekspedisi' => $item['filter_code']]),
                    'total_order' => $item['total_order'],
                    'total_pengeluaran' => $item['total_pengeluaran'],
                    'biaya_ongkir' => $item['biaya_ongkir'],
                    'biaya_asuransi' => $item['biaya_asuransi'],
                    'biaya_cod' => $item['biaya_cod'],
                    'total_pengirim' => count($item['senders']),
                    'total_penerima' => count($item['receivers']),
                    'total_kota_asal' => count($item['cities_origin']),
                    'total_kota_tujuan' => count($item['cities_dest']),
                    'chart_labels' => $topCities->keys()->values()->all(),
                    'chart_data' => $topCities->values()->all(),
                ];
            })->sortByDesc('total_order')->values();
        });

        // Mengambil data slider
        $sliderData = Setting::where('key', 'slider_informasi')->first();
        $slides = $sliderData ? json_decode($sliderData->value, true) : [];
        
        // --- [BAGIAN YANG HILANG SEBELUMNYA] ---
        // Hitung Data Global untuk Grafik Kota & Provinsi
        $allOrders = Pesanan::where('id_pengguna_pembeli', $customerId)
                        ->select('receiver_regency', 'receiver_province')
                        ->get();

        $cityCounts = $allOrders->groupBy(fn($i) => strtoupper(trim($i->receiver_regency ?? 'Lainnya')))
                                ->map->count()
                                ->sortDesc()
                                ->take(5);

        $provinceCounts = $allOrders->groupBy(fn($i) => strtoupper(trim($i->receiver_province ?? 'Lainnya')))
                                    ->map->count()
                                    ->sortDesc()
                                    ->take(5);
        // ----------------------------------------

        $data = [
            'saldo' => $saldo,
            'totalPesanan' => $totalPesanan,
            'pesananSelesai' => $pesananSelesai,
            'pesananPending' => $pesananPending,
            'recentOrders' => $recentOrders,
            'recentSpxScans' => $recentSpxScans,
            'orderChartLabels' => json_encode($orderChartData['labels']),
            'orderChartValues' => json_encode($orderChartData['values']),
            'spxChartLabels' => json_encode($spxChartData['labels']),
            'spxChartValues' => json_encode($spxChartData['values']),
            'slides' => $slides,
            'rekapEkspedisi' => $rekapEkspedisi,
            // Sekarang variabel ini sudah terdefinisi!
            'cityChartLabels' => json_encode($cityCounts->keys()->values()->all()),
            'cityChartValues' => json_encode($cityCounts->values()->all()),
            'provChartLabels' => json_encode($provinceCounts->keys()->values()->all()),
            'provChartValues' => json_encode($provinceCounts->values()->all()),
        ];

        return view('customer.dashboard', $data);
    }

    public function indexShop()
    {
        // Ambil semua toko milik user yang sedang login
        $shops = DB::table('dana_shops')
                    ->where('user_id', auth()->id())
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('customer.merchant.index', compact('shops'));
    }

    /**
     * Menyiapkan data untuk grafik pesanan 7 hari terakhir (Metode Efisien).
     */
    private function getOrderChartData($customerId)
    {
        $orderData = Pesanan::where('id_pengguna_pembeli', $customerId)
            ->where('tanggal_pesanan', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(tanggal_pesanan) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('ddd, D/M');
            $values[] = $orderData->get($dateString)->count ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Menyiapkan data untuk grafik scan SPX 7 hari terakhir (Metode Efisien).
     */
    private function getSpxScanChartData($customerId)
    {
        $spxData = ScannedPackage::where('user_id', $customerId)
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');
            
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->isoFormat('ddd, D/M');
            $values[] = $spxData->get($dateString)->count ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }
    
    // --- LOGIKA PENDAFTARAN SELLER BARU ---

    /**
     * Menampilkan halaman formulir pendaftaran seller.
     */
    public function showSellerRegistrationForm()
    {
        if (Auth::user()->store) {
            return redirect()->route('customer.dashboard')->with('info', 'Anda sudah terdaftar sebagai seller.');
        }
        return view('customer.seller-register');
    }

    /**
     * Menyimpan data pendaftaran toko dari formulir.
     */
    public function registerSeller(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:stores'],
            'description' => ['required', 'string', 'min:20'],
        ]);

        Store::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        $user = Auth::user();
        $user->role = 'Seller';
        $user->save();

        return redirect()->route('customer.dashboard')->with('success', 'Selamat! Toko Anda berhasil dibuat.');
    }

    /**
     * 1. Tampilkan Form Registrasi Merchant (Blade Tailwind)
     */
    public function createShopForm()
    {
        return view('customer.merchant.create-shop');
    }

    public function storeShop(Request $request, DanaSignatureService $danaService)
    {
        Log::info('[DANA CREATE SHOP] 1. Start Process', ['user_id' => auth()->id()]);

        // 1. VALIDASI
        $request->validate([
            'merchantId' => 'required',
            'mainName' => 'required|string|max:255',
            'externalShopId' => 'required',
            'shop_logo' => 'required|image|mimes:png|max:2048',
            'business_doc_file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
            'lat' => 'required', 'ln' => 'required',
            'shopAddress.province' => 'required', 'shopAddress.city' => 'required',
        ]);

        DB::beginTransaction();

        try {
            // [LOG 2] FILE PROCESSING
            $logoPath = $request->file('shop_logo')->store('public/uploads/dana/logos');
            $docPath  = $request->file('business_doc_file')->store('public/uploads/dana/docs');

            $base64Logo = base64_encode(file_get_contents($request->file('shop_logo')->getRealPath()));
            $base64Doc  = base64_encode(file_get_contents($request->file('business_doc_file')->getRealPath()));

            // --- 3. SMART DATA FORMATTING (ADDRESS FIX) ---
            
            // Helper: Auto-Correct Nama Kota (Ngawi -> Kab. Ngawi)
            $formatCity = function($rawCity) {
                $city = Str::title(trim($rawCity));
                if (empty($city)) return '-';
                // Jika sudah ada Kab/Kota, biarkan. Jika belum, tambah Kab.
                if (!Str::startsWith($city, ['Kab.', 'Kota ', 'Kab ', 'Kota'])) {
                    return 'Kab. ' . $city;
                }
                return $city;
            };

            // A. FIX SHOP ADDRESS
            $fixedShopAddress = [
                "country"     => "Indonesia",
                "province"    => Str::title(trim($request->input('shopAddress.province'))),
                "city"        => $formatCity($request->input('shopAddress.city')),
                "area"        => Str::title(trim($request->input('shopAddress.area'))), // Kecamatan
                "address1"    => $request->input('shopAddress.address1'),
                "address2"    => "-", 
                "postcode"    => $request->input('shopAddress.postcode'),
                "subDistrict" => "-"  
            ];

            // B. FIX OWNER ADDRESS (ERROR "Area Can't Be Blank" SOLVED HERE)
            $rawOwnerCity = $request->input('ownerAddress.city');
            $rawOwnerArea = $request->input('ownerAddress.area'); // Kemungkinan null dari form

            // Logic Fallback: Jika Kecamatan Owner kosong, pakai Kecamatan Toko
            if (empty($rawOwnerArea)) {
                $rawOwnerArea = $fixedShopAddress['area']; 
            }

            $fixedOwnerAddress = [
                "country"     => "Indonesia",
                "province"    => Str::title(trim($request->input('ownerAddress.province'))),
                "city"        => $formatCity($rawOwnerCity),
                "area"        => Str::title(trim($rawOwnerArea)), // <--- SUDAH TERISI
                "address1"    => $request->input('ownerAddress.address1'),
                "address2"    => "-", 
                "postcode"    => $request->input('ownerAddress.postcode'),
                "subDistrict" => "-"
            ];

            // C. FIX TAX ADDRESS
            $fixedTaxAddress = [
                "country"     => "Indonesia",
                "province"    => $fixedShopAddress['province'],
                "city"        => $fixedShopAddress['city'],
                "area"        => $fixedShopAddress['area'], 
                "address1"    => $request->input('taxAddress.address1') ?? $fixedShopAddress['address1'],
                "address2"    => "-",
                "postcode"    => $request->input('taxAddress.postcode') ?? $fixedShopAddress['postcode'],
                "subDistrict" => "-"
            ];

            // --- 4. CONFIG & DEFAULTS ---
            $clientId     = config('services.dana.x_partner_id'); 
            $clientSecret = config('services.dana.client_secret');
            $baseUrl      = config('services.dana.base_url') ?? 'https://api.sandbox.dana.id';
            $baseUrl      = rtrim($baseUrl, '/');

            if (empty($clientId)) $clientId = '2014000014442';

            $reqTime  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $reqMsgId = (string) Str::uuid();

            $shopOwning  = $request->shopOwning ?? 'DIRECT_OWNED';
            $shopBizType = $request->shopBizType ?? 'ONLINE';

            // [FIX MISSING PARAMS: USER_PROFILING]
            // Kita gabungkan input dari form dengan parameter wajib DANA yang kurang
            /*$rawExtInfo = $request->input('extInfo', []);
            $fixedExtInfo = array_merge($rawExtInfo, [
                'USER_PROFILING' => 'B2C',            // Wajib: Business to Consumer
                'AVG_TICKET'     => '10000-50000',    // Wajib: Rata-rata transaksi
                'OMZET'          => '100JT-500JT'     // Wajib: Omzet rata-rata
            ]);
            */
            // Versi Final FIXED EXT INFO
            $fixedExtInfo = $request->input('extInfo', []);
            if (!isset($fixedExtInfo['USER_PROFILING'])) {
                $fixedExtInfo['USER_PROFILING'] = 'B2C';
            }
            if (!isset($fixedExtInfo['AVG_TICKET'])) {
                $fixedExtInfo['AVG_TICKET'] = '10000-50000';
            }
            if (!isset($fixedExtInfo['OMZET'])) {
                $fixedExtInfo['OMZET'] = '100JT-500JT';
            }
            
            // --- 5. DB UPDATE/INSERT ---
            $dbData = [
                'user_id' => auth()->id(),
                'merchant_id' => $request->merchantId,
                'parent_division_id' => $request->parentDivisionId,
                'main_name' => $request->mainName,
                'shop_desc' => $request->shopDesc,
                'shop_parent_type' => $request->shopParentType,
                'size_type' => $request->sizeType,
                'shop_owning' => $shopOwning,
                'shop_biz_type' => $shopBizType,
                'loyalty' => $request->loyalty ?? 'true',
                'lat' => $request->lat, 'ln' => $request->ln,
                
                // SIMPAN DATA YANG SUDAH DIBERSIHKAN
                'shop_address' => json_encode($fixedShopAddress),
                'owner_address' => json_encode($fixedOwnerAddress), // <--- FIXED
                'tax_address' => json_encode($fixedTaxAddress),     // <--- FIXED
                
                'ext_info' => json_encode($fixedExtInfo),
                'owner_first_name' => $request->input('ownerName.firstName'),
                'owner_last_name' => $request->input('ownerName.lastName'),
                'owner_phone' => $request->input('ownerPhoneNumber.mobileNo'),
                'owner_id_type' => $request->ownerIdType,
                'owner_id_no' => $request->ownerIdNo,
                'business_entity' => $request->businessEntity,
                'mcc_codes' => json_encode($request->mccCodes),
                'brand_name' => $request->brandName,
                'tax_no' => $request->taxNo,
                'director_pics' => json_encode($request->directorPics ?? []),
                'non_director_pics' => json_encode($request->nonDirectorPics ?? []),
                'logo_path' => $logoPath,
                'doc_path' => $docPath,
                'dana_status' => 'PENDING',
                'updated_at' => now(),
            ];

            $existingShop = DB::table('dana_shops')->where('external_shop_id', $request->externalShopId)->first();
            if ($existingShop) {
                DB::table('dana_shops')->where('id', $existingShop->id)->update($dbData);
                $shopIdLocal = $existingShop->id;
            } else {
                $dbData['external_shop_id'] = $request->externalShopId;
                $dbData['created_at'] = now();
                $shopIdLocal = DB::table('dana_shops')->insertGetId($dbData);
            }

            Log::info("[DANA DB] ID: $shopIdLocal Saved.");

            // --- 6. DATA REQUEST OBJECT ---
            $requestObj = [
                "head" => [
                    "version"      => "2.0",
                    "function"     => "dana.merchant.shop.createShop",
                    "clientId"     => $clientId,
                    "clientSecret" => $clientSecret,
                    "reqTime"      => $reqTime,
                    "reqMsgId"     => $reqMsgId,
                    "reserve"      => "{}"
                ],
                "body" => [
                    "apiVersion"       => "3",
                    "merchantId"       => $request->merchantId,
                    "parentDivisionId" => $request->parentDivisionId,
                    "shopParentType"   => $request->shopParentType,
                    "mainName"         => $request->mainName,
                    
                    // ALAMAT YANG SUDAH VALID
                    "shopAddress"      => $fixedShopAddress, 
                    "ownerAddress"     => $fixedOwnerAddress, // <--- FIXED
                    "taxAddress"       => $fixedTaxAddress,   // <--- FIXED
                    
                    "shopDesc"         => $request->shopDesc ?? '-',
                    "externalShopId"   => $request->externalShopId,
                    "logoUrlMap"       => [ "PC_LOGO" => $base64Logo ],
                    "extInfo"          => $fixedExtInfo,
                    "sizeType"         => $request->sizeType,
                    "ln"               => $request->ln,
                    "lat"              => $request->lat,
                    "loyalty"          => "true",
                    "ownerName"        => $request->ownerName,
                    "ownerPhoneNumber" => [
                        "mobileNo" => $request->input('ownerPhoneNumber.mobileNo'),
                        "mobileId" => Str::random(10),
                        "verified" => "true"
                    ],
                    "ownerIdType"      => $request->ownerIdType,
                    "ownerIdNo"        => $request->ownerIdNo,
                    "deviceNumber"     => "0",
                    "posNumber"        => "0",
                    "mccCodes"         => $request->mccCodes,
                    "businessEntity"   => $request->businessEntity,
                    "shopOwning"       => $shopOwning, 
                    "shopBizType"      => $shopBizType, 
                    "businessDocs"     => [[
                        "docType" => ($request->businessEntity == 'individu') ? 'KTP' : 'SIUP',
                        "docId"   => $request->ownerIdNo,
                        "docFile" => $base64Doc
                    ]],
                    "taxNo"            => $request->taxNo,
                    "brandName"        => $request->brandName,
                    "directorPics"     => $request->directorPics ?? [],
                    "nonDirectorPics"  => $request->nonDirectorPics ?? []
                ]
            ];

            // --- 7. SIGNATURE GENERATION ---
            $jsonRequestString = json_encode($requestObj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stringToSign = $jsonRequestString; // V2.0 Sign JSON Langsung

            Log::info('[DANA SIGN] StringToSign Length: ' . strlen($stringToSign));

            // Generate Signature (Manual Helper)
            $privateKeyStr = config('services.dana.private_key');
            if (file_exists($privateKeyStr)) $privateKeyStr = file_get_contents($privateKeyStr);
            if (!Str::contains($privateKeyStr, 'BEGIN PRIVATE KEY')) {
                $privateKeyStr = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($privateKeyStr, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
            }
            
            $binarySignature = "";
            if (!openssl_sign($stringToSign, $binarySignature, $privateKeyStr, OPENSSL_ALGO_SHA256)) {
                throw new \Exception("Gagal sign OpenSSL");
            }
            $signatureString = base64_encode($binarySignature);

            // --- 8. FINAL PAYLOAD & SEND ---
            $finalJsonString = '{"request":' . $jsonRequestString . ',"signature":"' . $signatureString . '"}';
            $endpointPath = '/dana/merchant/shop/createShop.htm';

            Log::info('[DANA HTTP] Sending...');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->withBody($finalJsonString, 'application/json')
              ->post($baseUrl . $endpointPath);

            $result = $response->json();
            Log::info('[DANA HTTP] Response Received:', ['result' => $result]);

            // --- 9. HANDLE RESPONSE ---
            $danaResultInfo = $result['response']['body']['resultInfo'] ?? [];
            $danaStatus     = $danaResultInfo['resultStatus'] ?? 'F';
            $danaMsg        = $danaResultInfo['resultMsg'] ?? 'Unknown Error';
            $danaCode       = $danaResultInfo['resultCode'] ?? '-';
            $danaShopId     = $result['response']['body']['shopId'] ?? null;

            if ($danaStatus === 'S') {
                DB::table('dana_shops')->where('id', $shopIdLocal)->update([
                    'dana_status'       => 'SUCCESS',
                    'dana_shop_id'      => $danaShopId,
                    'dana_response_msg' => "Success ($danaCode): $danaMsg",
                    'updated_at'        => now()
                ]);
                DB::commit();
                return redirect()->route('customer.dashboard')->with('success', "Toko Berhasil Dibuat! Shop ID: $danaShopId");
            } else {
                DB::table('dana_shops')->where('id', $shopIdLocal)->update([
                    'dana_status'       => 'FAILED',
                    'dana_response_msg' => "$danaMsg (Code: $danaCode)",
                    'updated_at'        => now()
                ]);
                DB::commit(); 
                return back()->withInput()->with('error', "Gagal Mendaftar ke DANA: $danaMsg ($danaCode)");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DANA ERROR] Exception', ['msg' => $e->getMessage(), 'line' => $e->getLine()]);
            return back()->withInput()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }

    /**
     * PRIVATE HELPER: RSA SIGNATURE
     */
    private function generateSignature($stringToSign) 
    {
        $privateKeyStr = config('services.dana.private_key');
        
        if (file_exists($privateKeyStr)) {
            $privateKeyContent = file_get_contents($privateKeyStr);
        } else {
            $privateKeyContent = $privateKeyStr;
        }

        if (!Str::contains($privateKeyContent, 'BEGIN PRIVATE KEY')) {
            $privateKeyContent = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($privateKeyContent, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        }

        $binarySignature = "";
        
        // Gunakan OPENSSL_ALGO_SHA256
        if (!openssl_sign($stringToSign, $binarySignature, $privateKeyContent, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Gagal generate signature OpenSSL.");
        }
        
        return base64_encode($binarySignature);
    }

    /**
     * Tampilkan Form Edit
     */
    public function editShopForm($id)
    {
        $shop = DB::table('dana_shops')->where('id', $id)->where('user_id', auth()->id())->first();
        
        if (!$shop) {
            return back()->with('error', 'Toko tidak ditemukan.');
        }

        // Decode JSON agar bisa dipakai di View
        $shop->shop_address = json_decode($shop->shop_address, true);
        $shop->owner_address = json_decode($shop->owner_address, true);
        $shop->tax_address = json_decode($shop->tax_address, true);
        $shop->ext_info = json_decode($shop->ext_info, true);
        $shop->director_pics = json_decode($shop->director_pics, true);
        $shop->non_director_pics = json_decode($shop->non_director_pics, true);

        return view('customer.merchant.edit-shop', compact('shop')); // Reuse view create
    }

    /**
     * Proses Update Shop ke DANA API
     */
    public function updateShop(Request $request, $id)
    {
        Log::info('[DANA UPDATE SHOP] 1. Start Process', ['local_id' => $id]);

        // Ambil data toko lama untuk referensi file & ID DANA
        $oldShop = DB::table('dana_shops')->where('id', $id)->where('user_id', auth()->id())->first();
        if (!$oldShop || empty($oldShop->dana_shop_id)) {
            return back()->with('error', 'Toko belum terdaftar di DANA (Shop ID kosong), tidak bisa diupdate.');
        }

        // 1. VALIDASI (Sedikit beda: Gambar jadi nullable/optional)
        $request->validate([
            'mainName' => 'required|string|max:255',
            'shopAddress.province' => 'required',
            'shopAddress.city' => 'required',
            // File optional saat update, kalau kosong berarti pakai lama
            'shop_logo' => 'nullable|image|mimes:png|max:2048', 
            'business_doc_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // [LOG 2] FILE PROCESSING (HANDLE NEW OR OLD)
            
            // A. Logo Toko
            if ($request->hasFile('shop_logo')) {
                // Upload Baru
                $logoPath = $request->file('shop_logo')->store('public/uploads/dana/logos');
                $base64Logo = base64_encode(file_get_contents($request->file('shop_logo')->getRealPath()));
            } else {
                // Pakai Lama (Baca dari Storage Lokal)
                $logoPath = $oldShop->logo_path;
                $fullPath = storage_path('app/' . $logoPath); 
                if (file_exists($fullPath)) {
                    $base64Logo = base64_encode(file_get_contents($fullPath));
                } else {
                    throw new \Exception("File Logo lama hilang dari server. Silakan upload logo baru.");
                }
            }

            // B. Dokumen Bisnis
            if ($request->hasFile('business_doc_file')) {
                // Upload Baru
                $docPath = $request->file('business_doc_file')->store('public/uploads/dana/docs');
                $base64Doc = base64_encode(file_get_contents($request->file('business_doc_file')->getRealPath()));
            } else {
                // Pakai Lama
                $docPath = $oldShop->doc_path;
                $fullPath = storage_path('app/' . $docPath);
                if (file_exists($fullPath)) {
                    $base64Doc = base64_encode(file_get_contents($fullPath));
                } else {
                    throw new \Exception("File Dokumen lama hilang dari server. Silakan upload dokumen baru.");
                }
            }

            // --- 3. DATA PREPARATION & SMART FIXES (Sama seperti Create) ---
            
            // A. Fix ExtInfo
            $rawExtInfo = $request->input('extInfo', []);
            // Pastikan field wajib ada
            $fixedExtInfo = array_merge($rawExtInfo, [
                'USER_PROFILING' => $rawExtInfo['USER_PROFILING'] ?? 'B2C',            
                'AVG_TICKET'     => $rawExtInfo['AVG_TICKET'] ?? '10000-50000',    
                'OMZET'          => $rawExtInfo['OMZET'] ?? '100JT-500JT'     
            ]);

            // B. Fix Address Helper
            $formatCity = function($rawCity) {
                $city = Str::title(trim($rawCity));
                if (empty($city)) return '-';
                if (!Str::startsWith($city, ['Kab.', 'Kota ', 'Kab ', 'Kota'])) {
                    return 'Kab. ' . $city;
                }
                return $city;
            };

            // Shop Address
            $fixedShopAddress = [
                "country"     => "Indonesia",
                "province"    => Str::title(trim($request->input('shopAddress.province'))),
                "city"        => $formatCity($request->input('shopAddress.city')),
                "area"        => Str::title(trim($request->input('shopAddress.area'))), 
                "address1"    => $request->input('shopAddress.address1'),
                "address2"    => "-", 
                "postcode"    => $request->input('shopAddress.postcode'),
                "subDistrict" => "-"  
            ];

            // Owner Address
            $rawOwnerArea = $request->input('ownerAddress.area'); 
            if (empty($rawOwnerArea)) $rawOwnerArea = $fixedShopAddress['area']; 

            $fixedOwnerAddress = [
                "country"     => "Indonesia",
                "province"    => Str::title(trim($request->input('ownerAddress.province'))),
                "city"        => $formatCity($request->input('ownerAddress.city')),
                "area"        => Str::title(trim($rawOwnerArea)), 
                "address1"    => $request->input('ownerAddress.address1'),
                "address2"    => "-", 
                "postcode"    => $request->input('ownerAddress.postcode'),
                "subDistrict" => "-"
            ];

            // Tax Address
            $fixedTaxAddress = [
                "country"     => "Indonesia",
                "province"    => $fixedShopAddress['province'],
                "city"        => $fixedShopAddress['city'],
                "area"        => $fixedShopAddress['area'], 
                "address1"    => $request->input('taxAddress.address1') ?? $fixedShopAddress['address1'],
                "address2"    => "-",
                "postcode"    => $request->input('taxAddress.postcode') ?? $fixedShopAddress['postcode'],
                "subDistrict" => "-"
            ];

            // --- 4. CONFIG ---
            $clientId     = config('services.dana.x_partner_id'); 
            $clientSecret = config('services.dana.client_secret');
            $realMerchantId = config('services.dana.merchant_id'); 
            
            if (empty($realMerchantId)) throw new \Exception("DANA_MERCHANT_ID belum diisi di .env");
            
            $baseUrl  = config('services.dana.base_url') ?? 'https://api.sandbox.dana.id';
            $baseUrl  = rtrim($baseUrl, '/');
            if (empty($clientId)) $clientId = '2014000014442';

            $reqTime  = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $reqMsgId = (string) Str::uuid();

            // Ambil dari input form, jika kosong ambil dari data lama, jika masih kosong default 'DIRECT_OWNED'
            $shopOwning  = $request->input('shopOwning') ?: ($oldShop->shop_owning ?? 'DIRECT_OWNED');
            $shopBizType = $request->input('shopBizType') ?: ($oldShop->shop_biz_type ?? 'ONLINE');

            // --- 5. DB UPDATE (Hanya update record yang ada) ---
            DB::table('dana_shops')->where('id', $id)->update([
                'main_name' => $request->mainName,
                'shop_desc' => $request->shopDesc,
                'shop_parent_type' => $request->shopParentType,
                'size_type' => $request->sizeType,
                'lat' => $request->lat, 'ln' => $request->ln,

                'shop_owning' => $shopOwning,    // <--- Ganti $request->shopOwning jadi variabel $shopOwning
                'shop_biz_type' => $shopBizType, // <--- Ganti $request->shopBizType jadi variabel $shopBizType
                
                'shop_address' => json_encode($fixedShopAddress),
                'owner_address' => json_encode($fixedOwnerAddress),
                'tax_address' => json_encode($fixedTaxAddress),
                'ext_info' => json_encode($fixedExtInfo),
                
                'owner_first_name' => $request->input('ownerName.firstName'),
                'owner_last_name' => $request->input('ownerName.lastName'),
                'owner_phone' => $request->input('ownerPhoneNumber.mobileNo'),
                
                'logo_path' => $logoPath, // Path baru atau lama
                'doc_path' => $docPath,   // Path baru atau lama
                'updated_at' => now(),
            ]);

            Log::info("[DANA DB] Data Local ID: $id Updated.");

            // --- 6. API REQUEST OBJECT (UPDATE SHOP STRUCTURE) ---
            $requestObj = [
                "head" => [
                    "version"      => "2.0",
                    "function"     => "dana.merchant.shop.updateShop", // <--- FUNCTION UPDATE
                    "clientId"     => $clientId,
                    "clientSecret" => $clientSecret,
                    "reqTime"      => $reqTime,
                    "reqMsgId"     => $reqMsgId,
                    "reserve"      => "{}"
                ],
                "body" => [
                    // --- IDENTIFIER PENTING ---
                    "shopId"           => $oldShop->dana_shop_id, // ID DANA dari DB
                    "merchantId"       => $realMerchantId,
                    "shopIdType"       => "INNER_ID", // Karena pakai ID dari DANA
                    
                    "apiVersion"       => "3",
                    "parentDivisionId" => $realMerchantId,
                    "shopParentType"   => $request->shopParentType,
                    "mainName"         => $request->mainName,
                    
                    "shopAddress"      => $fixedShopAddress, 
                    "ownerAddress"     => $fixedOwnerAddress,
                    "taxAddress"       => $fixedTaxAddress,
                    "shopDesc"         => $request->shopDesc ?? '-',
                    "newExternalShopId"=> $oldShop->external_shop_id, // Tetap pakai ID lama
                    
                    "logoUrlMap"       => [ "PC_LOGO" => $base64Logo ], // Wajib kirim Base64
                    "extInfo"          => $fixedExtInfo,
                    
                    "sizeType"         => $request->sizeType,
                    "ln"               => $request->ln,
                    "lat"              => $request->lat,
                    "loyalty"          => "true",
                    
                    "ownerName"        => $request->ownerName,
                    "ownerPhoneNumber" => [
                        "mobileNo" => $request->input('ownerPhoneNumber.mobileNo'),
                        "mobileId" => Str::random(10),
                        "verified" => "true"
                    ],
                    "ownerIdType"      => $request->ownerIdType,
                    "ownerIdNo"        => $request->ownerIdNo,
                    "deviceNumber"     => "0",
                    "posNumber"        => "0",
                    "mccCodes"         => $request->mccCodes,
                    "businessEntity"   => $request->businessEntity,
                    "shopOwning"       => $shopOwning,  // <--- Ganti jadi variabel $shopOwning
                    "shopBizType"      => $shopBizType, // <--- Ganti jadi variabel $shopBizType 
                    
                    // UPDATE BAGIAN businessDocs
                    "businessDocs"     => [[
                        "docType" => $request->docType, // Ambil dari input
                        "docId"   => $request->docId,   // Ambil dari input
                        "docFile" => $base64Doc         // File Base64
                    ]],
                    
                    "ownerIdNo"        => ($request->docType == 'KTP') ? $request->docId : $request->ownerIdNo,
                    "taxNo"            => $request->taxNo,
                    "brandName"        => $request->brandName,
                    "directorPics"     => $request->directorPics ?? [],
                    "nonDirectorPics"  => $request->nonDirectorPics ?? []
                ]
            ];

            // --- 7. SIGNATURE (STRING LOCKING) ---
            $jsonRequestString = json_encode($requestObj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stringToSign = $jsonRequestString;

            $privateKeyStr = config('services.dana.private_key');
            if (file_exists($privateKeyStr)) $privateKeyStr = file_get_contents($privateKeyStr);
            if (!Str::contains($privateKeyStr, 'BEGIN PRIVATE KEY')) {
                $privateKeyStr = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($privateKeyStr, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
            }
            
            $binarySignature = "";
            if (!openssl_sign($stringToSign, $binarySignature, $privateKeyStr, OPENSSL_ALGO_SHA256)) {
                throw new \Exception("Gagal sign OpenSSL");
            }
            $signatureString = base64_encode($binarySignature);

            // --- 8. SEND REQUEST ---
            $finalJsonString = '{"request":' . $jsonRequestString . ',"signature":"' . $signatureString . '"}';
            $endpointPath = '/dana/merchant/shop/updateShop.htm'; // <--- ENDPOINT UPDATE

            Log::info('[DANA UPDATE SHOP] Sending Request...');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->withBody($finalJsonString, 'application/json')
              ->post($baseUrl . $endpointPath);

            $result = $response->json();
            Log::info('[DANA UPDATE SHOP] Response:', ['result' => $result]);

            // --- 9. HANDLE RESPONSE ---
            $danaResultInfo = $result['response']['body']['resultInfo'] ?? [];
            $danaStatus     = $danaResultInfo['resultStatus'] ?? 'F';
            $danaMsg        = $danaResultInfo['resultMsg'] ?? 'Unknown Error';
            $danaCode       = $danaResultInfo['resultCode'] ?? '-';

            if ($danaStatus === 'S') {
                // Update Sukses
                DB::table('dana_shops')->where('id', $id)->update([
                    'dana_status' => 'SUCCESS', // Pastikan tetap sukses
                    'dana_response_msg' => "Update Success: $danaMsg",
                    'updated_at' => now()
                ]);
                DB::commit();
                return redirect()->route('customer.merchant.index')->with('success', "Toko Berhasil Diupdate di DANA!");
            } else {
                // Update Gagal
                DB::table('dana_shops')->where('id', $id)->update([
                    'dana_response_msg' => "Update Failed: $danaMsg ($danaCode)",
                    'updated_at' => now()
                ]);
                DB::commit(); 
                return back()->withInput()->with('error', "Gagal Update ke DANA: $danaMsg ($danaCode)");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DANA UPDATE ERROR]', ['msg' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Sistem Error: ' . $e->getMessage());
        }
    }
}