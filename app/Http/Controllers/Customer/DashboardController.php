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
        // [LOG 1] START REQUEST
        Log::info('[DANA CREATE SHOP] 1. Memulai Proses', [
            'user_id' => auth()->id(),
            'external_shop_id' => $request->externalShopId 
        ]);

        // 1. VALIDASI INPUT
        $request->validate([
            'merchantId' => 'required',
            'mainName' => 'required|string|max:255',
            'externalShopId' => 'required',
            'shop_logo' => 'required|image|mimes:png|max:2048',
            'business_doc_file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
            'lat' => 'required',
            'ln' => 'required',
            'shopAddress.province' => 'required',
            'shopAddress.city' => 'required',
        ]);

        DB::beginTransaction();

        try {
            // [LOG 2] FILE PROCESSING
            Log::info('[DANA CREATE SHOP] 2. Memproses File Upload...');
            
            // A. Simpan file fisik
            $logoPath = $request->file('shop_logo')->store('public/uploads/dana/logos');
            $docPath  = $request->file('business_doc_file')->store('public/uploads/dana/docs');

            // B. Konversi ke Base64
            $base64Logo = base64_encode(file_get_contents($request->file('shop_logo')->getRealPath()));
            $base64Doc  = base64_encode(file_get_contents($request->file('business_doc_file')->getRealPath()));

            // --- 3. PERSIAPAN DB DATA (UPDATE OR INSERT) ---
            
            // Susun data dalam array agar rapi
            $dbData = [
                'user_id' => auth()->id(),
                'merchant_id' => $request->merchantId,
                'parent_division_id' => $request->parentDivisionId,
                'main_name' => $request->mainName,
                // 'external_shop_id' -> Kita set nanti di logika insert
                'shop_desc' => $request->shopDesc,
                'shop_parent_type' => $request->shopParentType,
                'size_type' => $request->sizeType,
                'shop_owning' => $request->shopOwning,
                'shop_biz_type' => $request->shopBizType,
                'loyalty' => $request->loyalty ?? 'true',
                'lat' => $request->lat,
                'ln' => $request->ln,
                'shop_address' => json_encode($request->shopAddress),
                'ext_info' => json_encode($request->extInfo),
                'owner_first_name' => $request->input('ownerName.firstName'),
                'owner_last_name' => $request->input('ownerName.lastName'),
                'owner_phone' => $request->input('ownerPhoneNumber.mobileNo'),
                'owner_id_type' => $request->ownerIdType,
                'owner_id_no' => $request->ownerIdNo,
                'owner_address' => json_encode($request->ownerAddress),
                'business_entity' => $request->businessEntity,
                'mcc_codes' => json_encode($request->mccCodes),
                'brand_name' => $request->brandName,
                'tax_no' => $request->taxNo,
                'tax_address' => json_encode($request->taxAddress),
                'director_pics' => json_encode($request->directorPics ?? []),
                'non_director_pics' => json_encode($request->nonDirectorPics ?? []),
                'logo_path' => $logoPath,
                'doc_path' => $docPath,
                'dana_status' => 'PENDING', // Reset status jadi PENDING saat update
                'updated_at' => now(),
            ];

            // [LOGIC PENTING] Cek apakah data sudah ada?
            $existingShop = DB::table('dana_shops')
                ->where('external_shop_id', $request->externalShopId)
                ->first();

            if ($existingShop) {
                // --- UPDATE (Jika Duplicate) ---
                DB::table('dana_shops')->where('id', $existingShop->id)->update($dbData);
                $shopIdLocal = $existingShop->id;
                Log::info("[DANA CREATE SHOP] 3. Data Diupdate (Overwrite). ID: $shopIdLocal");
            } else {
                // --- INSERT (Jika Baru) ---
                $dbData['external_shop_id'] = $request->externalShopId; // Tambahkan Key Unik
                $dbData['created_at'] = now();
                
                $shopIdLocal = DB::table('dana_shops')->insertGetId($dbData);
                Log::info("[DANA CREATE SHOP] 3. Data Baru Disimpan. ID: $shopIdLocal");
            }

            // --- 4. PERSIAPAN API REQUEST ---
            $reqTime = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $reqMsgId = (string) Str::uuid();
            $clientId = config('services.dana.client_id');
            $clientSecret = config('services.dana.client_secret');
            $baseUrl = config('services.dana.base_url') ?? 'https://api.sandbox.dana.id';
            $baseUrl = rtrim($baseUrl, '/');

            // --- 5. SUSUN PAYLOAD API DANA ---
            $payload = [
                "request" => [
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
                        "shopAddress"      => $request->shopAddress, 
                        "shopDesc"         => $request->shopDesc ?? '-',
                        "externalShopId"   => $request->externalShopId,
                        "logoUrlMap"       => [ "PC_LOGO" => $base64Logo ],
                        "extInfo"          => $request->extInfo,
                        "sizeType"         => $request->sizeType,
                        "ln"               => $request->ln,
                        "lat"              => $request->lat,
                        "loyalty"          => "true",
                        "ownerAddress"     => $request->ownerAddress,
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
                        "shopOwning"       => $request->shopOwning,
                        "shopBizType"      => $request->shopBizType,
                        "businessDocs"     => [
                            [
                                "docType" => ($request->businessEntity == 'individu') ? 'KTP' : 'SIUP',
                                "docId"   => $request->ownerIdNo,
                                "docFile" => $base64Doc
                            ]
                        ],
                        "taxNo"            => $request->taxNo,
                        "taxAddress"       => $request->taxAddress,
                        "brandName"        => $request->brandName,
                        "directorPics"     => $request->directorPics ?? [],
                        "nonDirectorPics"  => $request->nonDirectorPics ?? []
                    ]
                ]
            ];

            // Log Payload (Hidden Base64)
            $debugPayload = $payload;
            $debugPayload['request']['body']['logoUrlMap']['PC_LOGO'] = 'HIDDEN';
            $debugPayload['request']['body']['businessDocs'][0]['docFile'] = 'HIDDEN';
            Log::info('[DANA CREATE SHOP] 4. Payload Ready', ['payload' => $debugPayload]);

            // --- 6. SIGNATURE & REQUEST ---
            $endpointPath = '/dana/merchant/shop/createShop.htm';
            $signatureString = $danaService->generateSignature('POST', $endpointPath, $payload['request'], $reqTime);
            
            $finalPayload = $payload;
            $finalPayload['signature'] = $signatureString;

            Log::info('[DANA CREATE SHOP] 5. Sending Request...');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post($baseUrl . $endpointPath, $finalPayload);

            $result = $response->json();
            Log::info('[DANA CREATE SHOP] 6. Response Received', ['result' => $result]);

            // --- 7. HANDLING RESPON ---
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
                
                return redirect()->route('customer.dashboard')->with('success', "Toko Berhasil Dibuat/Diupdate! Shop ID: $danaShopId");

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
            Log::error('[DANA CREATE SHOP] ERROR', ['msg' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}

