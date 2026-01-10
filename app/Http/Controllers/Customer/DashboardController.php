<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Jangan lupa import Cache
use App\Models\Pesanan;
use App\Models\ScannedPackage;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\View\Composers\CustomerLayoutComposer;

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

    /**
     * 2. Proses Submit ke API DANA
     */
    public function storeShop(Request $request, DanaSignatureService $danaService)
    {
        // Validasi Sederhana
        $request->validate([
            'mainName' => 'required|string',
            'shop_logo' => 'required|image|max:2048', // Max 2MB
            'business_doc_file' => 'required|mimes:pdf,jpg,png|max:2048',
        ]);

        try {
            // --- A. PERSIAPAN FILE (BASE64) ---
            $base64Logo = base64_encode(file_get_contents($request->file('shop_logo')->getRealPath()));
            $base64Doc  = base64_encode(file_get_contents($request->file('business_doc_file')->getRealPath()));

            // --- B. PERSIAPAN VARIABEL HEADER & TIMESTAMP ---
            // Format waktu wajib: YYYY-MM-DDTHH:mm:ss+07:00
            $reqTime = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'); 
            $reqMsgId = (string) Str::uuid();
            $clientId = config('services.dana.client_id'); // Atau ambil dari DB
            
            // --- C. SUSUN REQUEST BODY (SESUAI DOKUMENTASI) ---
            $payload = [
                "request" => [
                    "head" => [
                        "version"      => "2.0",
                        "function"     => "dana.merchant.shop.createShop",
                        "clientId"     => $clientId,
                        "clientSecret" => config('services.dana.client_secret'),
                        "reqTime"      => $reqTime,
                        "reqMsgId"     => $reqMsgId,
                        "reserve"      => "{}"
                    ],
                    "body" => [
                        "apiVersion"       => "3", // Wajib versi 3 sesuai docs
                        "merchantId"       => $request->merchantId,
                        "parentDivisionId" => $request->parentDivisionId,
                        "shopParentType"   => $request->shopParentType, // MERCHANT / DIVISION
                        "mainName"         => $request->mainName,
                        "shopAddress"      => [
                            "country"     => $request->input('shopAddress.country', 'Indonesia'),
                            "province"    => $request->input('shopAddress.province'),
                            "city"        => $request->input('shopAddress.city'),
                            "area"        => $request->input('shopAddress.area'),
                            "address1"    => $request->input('shopAddress.address1'),
                            "address2"    => $request->input('shopAddress.address2'),
                            "postcode"    => $request->input('shopAddress.postcode'),
                            "subDistrict" => $request->input('shopAddress.subDistrict'),
                        ],
                        "shopDesc"         => $request->shopDesc ?? '-',
                        "externalShopId"   => $request->externalShopId,
                        "logoUrlMap"       => [
                            "PC_LOGO" => $base64Logo // Base64 Image String
                        ],
                        "extInfo"          => [
                            "PIC_EMAIL"       => $request->input('extInfo.PIC_EMAIL'),
                            "PIC_PHONENUMBER" => $request->input('extInfo.PIC_PHONENUMBER'),
                            "SUBMITTER_EMAIL" => $request->input('extInfo.SUBMITTER_EMAIL'),
                            "GOODS_SOLD_TYPE" => $request->input('extInfo.GOODS_SOLD_TYPE'), // DIGITAL/PHYSICAL
                            "USECASE"         => $request->input('extInfo.USECASE'), // QRIS_DIGITAL
                            "USER_PROFILING"  => $request->input('extInfo.USER_PROFILING'), // B2B/B2C
                            "AVG_TICKET"      => $request->input('extInfo.AVG_TICKET'),
                            "OMZET"           => $request->input('extInfo.OMZET'),
                            "EXT_URLS"        => $request->input('extInfo.EXT_URLS'),
                        ],
                        "sizeType"         => $request->sizeType, // UMI, UKE, dll
                        "ln"               => $request->ln, // Longitude
                        "lat"              => $request->lat, // Latitude
                        "loyalty"          => "true",
                        "ownerAddress"     => [
                            "country"     => $request->input('ownerAddress.country', 'Indonesia'),
                            "province"    => $request->input('ownerAddress.province'),
                            "city"        => $request->input('ownerAddress.city'),
                            "area"        => "area", // Seringkali default jika tidak ada input spesifik
                            "address1"    => $request->input('ownerAddress.address1'),
                            "address2"    => "-",
                            "postcode"    => $request->input('ownerAddress.postcode'),
                            "subDistrict" => "-"
                        ],
                        "ownerName"        => [
                            "firstName" => $request->input('ownerName.firstName'),
                            "lastName"  => $request->input('ownerName.lastName'),
                        ],
                        "ownerPhoneNumber" => [
                            "mobileNo" => $request->input('ownerPhoneNumber.mobileNo'),
                            "mobileId" => Str::random(10), // Unique ID
                            "verified" => "true"
                        ],
                        "ownerIdType"      => $request->ownerIdType, // KTP, PASSPORT
                        "ownerIdNo"        => $request->ownerIdNo,
                        "deviceNumber"     => "0",
                        "posNumber"        => "0",
                        "mccCodes"         => $request->mccCodes ?? ["0783"],
                        "businessEntity"   => $request->businessEntity, // individu, pt, cv
                        "shopOwning"       => $request->shopOwning, // DIRECT_OWNED
                        "shopBizType"      => $request->shopBizType, // ONLINE/OFFLINE
                        "businessDocs"     => [
                            [
                                "docType" => "KTP", // Sesuaikan logic jika entity PT (harus SIUP)
                                "docId"   => $request->ownerIdNo,
                                "docFile" => $base64Doc // Base64 PDF/Image
                            ]
                        ],
                        "taxNo"            => $request->taxNo,
                        "taxAddress"       => [
                            "country"     => "Indonesia",
                            "province"    => $request->input('taxAddress.province', 'DKI Jakarta'),
                            "city"        => $request->input('taxAddress.city', 'Jakarta'),
                            "area"        => "area",
                            "address1"    => $request->input('taxAddress.address1'),
                            "address2"    => "-",
                            "postcode"    => $request->input('taxAddress.postcode'),
                            "subDistrict" => "-"
                        ],
                        "brandName"        => $request->brandName,
                        "directorPics"     => [
                            [
                                "picName"     => $request->input('directorPics.0.picName'),
                                "picPosition" => $request->input('directorPics.0.picPosition')
                            ]
                        ],
                        "nonDirectorPics"  => [
                            [
                                "picName"     => $request->input('nonDirectorPics.0.picName'),
                                "picPosition" => $request->input('nonDirectorPics.0.picPosition')
                            ]
                        ]
                    ]
                ]
            ];

            // --- D. SIGNATURE GENERATION ---
            // Menggunakan service yang sama dengan fitur Topup
            // Pastikan method generateSignature menerima ($httpMethod, $urlPath, $bodyArray, $timestamp)
            $path = '/dana/merchant/shop/createShop.htm';
            
            // Signature biasanya ditaruh di root JSON payload untuk endpoint ini (berdasarkan sample request Anda)
            // Namun, kadang ditaruh di Header. Kita ikuti sample request: field "signature" ada di root JSON.
            // Kita perlu generate string signature-nya dulu.
            $signatureString = $danaService->generateSignature('POST', $path, $payload['request'], $reqTime);
            
            // Masukkan signature ke payload final
            $finalPayload = $payload;
            $finalPayload['signature'] = $signatureString;


            // --- E. KIRIM REQUEST ---
            $baseUrl = config('services.dana.base_url'); // https://api.sandbox.dana.id atau Production
            
            Log::info('[DANA CREATE SHOP] Sending Request...', ['reqMsgId' => $reqMsgId]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post($baseUrl . $path, $finalPayload);

            $result = $response->json();
            
            // Log Response
            Log::info('[DANA CREATE SHOP] Response:', $result);

            // --- F. HANDLE RESPONSE ---
            // Cek resultStatus di dalam resultInfo
            $status = $result['response']['body']['resultInfo']['resultStatus'] ?? 'F';
            $msg    = $result['response']['body']['resultInfo']['resultMsg'] ?? 'Unknown Error';
            $shopId = $result['response']['body']['shopId'] ?? null;

            if ($status === 'S') {
                // SUCCESS: Simpan shopId ke database toko Anda jika perlu
                // Store::where('user_id', Auth::id())->update(['dana_shop_id' => $shopId]);

                return redirect()->route('customer.dashboard')
                    ->with('success', "Toko Berhasil Dibuat di DANA! Shop ID: $shopId");
            } else {
                // FAILED
                return back()
                    ->withInput()
                    ->with('error', "Gagal membuat toko: $msg (" . ($result['response']['body']['resultInfo']['resultCode'] ?? '') . ")");
            }

        } catch (\Exception $e) {
            Log::error('[DANA CREATE SHOP] Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}

