<?php // <-- Pastikan tidak ada spasi atau baris kosong SEBELUM ini

namespace App\Http\Controllers; // <-- Pastikan tidak ada spasi atau baris kosong ANTARA ini dan <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;
use App\Events\AdminNotificationEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\Product;
use App\Models\Koli;
use App\Models\ProductVariant;
use App\Models\Store; // <-- Pastikan Model Store di-import
use App\Events\SaldoUpdated;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use App\Services\DanaSignatureService; // Ensure this service is imported
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Validation\ValidationException;
use App\Services\FonnteService; // ðŸ”‘ TAMBAHKAN INI
use Carbon\Carbon; // Digunakan untuk waktu

// IMPORT SEMUA CONTROLLER YANG MEMILIKI FUNGSI PROSESOR CALLBACK
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Customer\PesananController as CustomerPesananController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Toko\ProdukController;
use App\Http\Controllers\Customer\KoliController;
use App\Http\Controllers\Toko\CheckoutController as TokoCheckoutController;

//Pengiriman notif ke email user saat webhook sukses
use App\Traits\TransactionEmailTrait;



class CheckoutController extends Controller
{
    use TransactionEmailTrait;

    protected $danaSignature;

    // Inject DanaSignatureService
    public function __construct(DanaSignatureService $danaSignature)
    {
        $this->danaSignature = $danaSignature;
    }

    public function geocode($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MyLaravelApp/1.0 (support@tokosancaka.com)',
                'Accept'     => 'application/json',
            ])->timeout(10)->get($url, [
                'q'          => $address,
                'format'     => 'json',
                'limit'      => 1,
                'countrycodes' => 'id'
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data) && isset($data[0])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                ];
            }

            Log::warning('Geocoding failed or returned empty', [
                'address' => $address,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Geocoding Exception', [
                'address' => $address,
                'error'   => $e->getMessage()
            ]);
            return null;
        }
    }


    public function index(KiriminAjaService $kiriminAja)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('info', 'Keranjang Anda kosong. Silakan belanja terlebih dahulu.');
        }

     // === PERBAIKAN PERFORMA & HYBRID CART ===
        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->toArray();
        $productsCache = \App\Models\Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        // Pisahkan menjadi 2 bendera (flags)
        $hasDigital = false;
        $hasPhysical = false;

        foreach ($cart as $item) {
            $isThisItemDigital = false;

            // 1. Cek dari session cart
            if (isset($item['type']) && (str_contains(strtolower($item['type']), 'digital') || in_array(strtolower($item['type']), ['eticket', 'jasa']))) {
                $isThisItemDigital = true;
            }

            // 2. Cek dari database & kategori
            $productCheck = $productsCache[$item['product_id'] ?? null] ?? null;
            if ($productCheck) {
                if (isset($productCheck->is_digital) && $productCheck->is_digital) {
                    $isThisItemDigital = true;
                }

                $kategoriData = $productCheck->category;
                $kategoriGrup = is_object($kategoriData) ? ($kategoriData->category_group ?? $kategoriData->name ?? null) : (is_array($kategoriData) ? ($kategoriData['category_group'] ?? $kategoriData['name'] ?? null) : $kategoriData);

                if ($kategoriGrup && (str_contains(strtolower($kategoriGrup), 'digital') || str_contains(strtolower($kategoriGrup), 'jasa') || str_contains(strtolower($kategoriGrup), 'tiket') || str_contains(strtolower($kategoriGrup), 'ticket') || str_contains(strtolower($kategoriGrup), 'eticket'))) {
                    $isThisItemDigital = true;
                }
            }

            // Tentukan status komponen keranjang
            if ($isThisItemDigital) {
                $hasDigital = true;
            } else {
                $hasPhysical = true;
            }
        }

        // Variabel khusus untuk mengunci Ongkir Rp0 (Hanya jika 100% digital)
        $isStrictlyDigital = $hasDigital && !$hasPhysical;

        $isDigital = $isStrictlyDigital;
        $user = Auth::user();

        // 1. DETEKSI MAKANAN (LOKAL) SEBELUM VALIDASI LOGIN
        $isLocalFood = false;
        foreach ($cart as $item) {
            $productCheck = $productsCache[$item['product_id'] ?? null] ?? null;
            if ($productCheck) {
                $kategoriGroup = $productCheck->category->category_group ?? '';
                $kategoriName  = $productCheck->category->name ?? '';
                if (str_contains(strtolower($kategoriGroup), 'food') ||
                    str_contains(strtolower($kategoriGroup), 'makanan') ||
                    str_contains(strtolower($kategoriName), 'makanan')) {
                    $isLocalFood = true;
                }
            }
        }

        // 2. JIKA BUKAN DIGITAL & BUKAN MAKANAN, BARU WAJIB LOGIN
        if (!$isDigital && !$isLocalFood) {
            if (!$user) {
                return redirect()->route('login')->with('warning', 'Keranjang Anda berisi produk fisik reguler. Silakan login untuk memilih opsi pengiriman kurir.');
            }
            if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province) || empty($user->address_detail)) {
                return redirect()->route('profile.edit')->with('warning', 'Alamat pengiriman Anda belum lengkap. Mohon lengkapi data lokasi Anda terlebih dahulu.');
            }
        }
        // Ambil mode dari Database (Bukan Config/Env)
        $currentMode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');
        $cacheKey = 'tripay_channels_list_' . $currentMode;

        // Cache list channel biar cepat loadingnya
        $tripayChannels = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 24, function () use ($currentMode) {

            // Tentukan URL & Key berdasarkan mode Database
            if ($currentMode === 'production') {
                $baseUrl = 'https://tripay.co.id/api';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            } else {
                $baseUrl = 'https://tripay.co.id/api-sandbox';
                $apiKey  = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            }

            try {
                $response = Http::withToken($apiKey)->timeout(10)->get($baseUrl . '/merchant/payment-channel');
                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                // Silent error
            }
            return [];
        });

        $user = Auth::user();

        /*if (!$isDigital) {
            if (!$user) {
                return redirect()->route('login')->with('error', 'Akses ditolak. Silakan login terlebih dahulu untuk checkout produk fisik.');
            }
            if (empty($user->address_detail) || empty($user->village)) {
                return redirect()->route('profile.edit')->with('warning', 'Silakan lengkapi alamat pengiriman Anda dahulu.');
            }
        }*/


        $firstCartItemData = reset($cart);
        $productId = $firstCartItemData['product_id'] ?? null;
        $firstProduct = $productId ? Product::find($productId) : null;

        if (!$firstProduct || !$firstProduct->store || !$firstProduct->store->user) {
            session()->forget('cart');

            if (!$firstProduct) {
                Log::warning('Checkout Index: Produk di keranjang tidak ditemukan.', ['product_id' => $productId]);
            } else if (!$firstProduct->store) {
                Log::warning('Checkout Index: Produk ada, tapi relasi store tidak ada.', ['product_id' => $productId]);
            } else if (!$firstProduct->store->user) {
                Log::warning('Checkout Index: Produk dan store ada, tapi relasi store->user tidak ada.', ['store_id' => $firstProduct->store->id]);
            }

            return redirect()->route('cart.index')
                ->with('error', 'Produk atau data toko di keranjang Anda tidak lagi tersedia atau tidak valid. Keranjang telah dikosongkan.');
        }

        $store = $firstProduct->store;

        // Validasi kelengkapan alamat toko (Hanya eksekusi jika produk fisik)
        if (!$isDigital && $store) {
            if (empty($store->village) || empty($store->district) || empty($store->regency) || empty($store->province)) {
                 Log::error('Alamat toko tidak lengkap', ['store_id' => $store->id]);
                return redirect()->route('cart.index')
                    ->with('error', 'Alamat toko asal pengiriman tidak lengkap. Silakan hubungi penjual.');
            }
        }

        $expressOptions = null;
        $instantOptions = null;
        $isLocalFood = false;
        $routeResult = null;

        // ========================================================
        // BLOK API KIRIMINAJA & NOMINATIM (HANYA UNTUK PRODUK FISIK)
        // ========================================================
        if (!$isDigital) {
            $storeSearch = $store ? ($store->village . ', ' . $store->district . ', ' . $store->regency . ', ' . $store->province) : '';

            // Perbaikan Keamanan Zero Bugs: Cegah query string kosong
            $userSearch = '';
            if ($user && !empty($user->village)) {
                $userSearch = $user->village . ', ' . $user->district . ', ' . $user->regency . ', ' . $user->province;
            }

            // Set opsi ongkir default agar file Blade.php tidak error (Menunggu input Guest via AJAX)
            $expressOptions = ['status' => true, 'results' => []];
            $instantOptions = ['status' => true, 'results' => []];

            try {
                // 1. Validasi Alamat Toko (Wajib)
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                $storeAddr = $storeAddrRes['data'][0] ?? null;

                if (!$storeAddr) {
                    Log::error('Alamat Toko tidak valid', ['store_search' => $storeSearch]);
                    return redirect()->route('cart.index')->with('error', 'Alamat toko asal pengiriman tidak dapat divalidasi oleh sistem ekspedisi.');
                }

                // 2. HANYA tembak API KiriminAja JIKA pengunjung sudah punya alamat di database
                $cleanSearch = trim(str_replace(',', '', $userSearch));
                if (!empty($cleanSearch)) {
                    $userAddrRes  = $kiriminAja->searchAddress($userSearch);
                    $userAddr  = $userAddrRes['data'][0] ?? null;

                    if ($userAddr) {
                        $storeLat = ($store && $store->latitude) ? (float) $store->latitude : null;
                        $storeLng = ($store && $store->longitude) ? (float) $store->longitude : null;
                        $userLat  = ($user && $user->latitude) ? (float) $user->latitude : null;
                        $userLng  = ($user && $user->longitude) ? (float) $user->longitude : null;

                        $totalWeight = (int) collect($cart)->sum(function($item) {
                            $product = \App\Models\Product::find($item['product_id']);
                            $kategoriGroup = $product->category->category_group ?? '';
                            $isItemDigital = (isset($item['type']) && in_array(strtolower($item['type']), ['eticket', 'digital', 'jasa'])) || in_array($kategoriGroup, ['produk_digital', 'jasa']);
                            if ($isItemDigital) return 0;
                            return ($product->weight ?? 1000) * $item['quantity'];
                        });
                        $finalWeight = max(1000, $totalWeight);

                        $itemValue   = (int) collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
                        $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

                        $defaultLength = $firstProduct->length ?? 5;
                        $defaultWidth  = $firstProduct->width  ?? 5;
                        $defaultHeight = $firstProduct->height ?? 5;

                        // Tarik Data Express
                        try {
                             $expressRaw = $kiriminAja->getExpressPricing(
                                 $storeAddr['district_id'], $storeAddr['subdistrict_id'],
                                 $userAddr['district_id'], $userAddr['subdistrict_id'],
                                 $finalWeight, $defaultLength, $defaultWidth, $defaultHeight,
                                 $itemValue, null, $category, 1
                             );

                             if (isset($expressRaw['status']) && $expressRaw['status'] === true && isset($expressRaw['results'])) {
                                 $cleanedExpress = [];
                                 foreach ($expressRaw['results'] as $opt) {
                                     $cost = (int) ($opt['cost'] ?? 0);
                                     if ($cost > 0) {
                                         $opt['final_price'] = $cost;
                                         $opt['group'] = $opt['group'] ?? 'regular';
                                         $opt['insurance_cost'] = (int) ($opt['insurance'] ?? 0);
                                         $opt['cod_available'] = $opt['cod'] ?? false;
                                         $opt['cod_fee'] = (int) ($opt['setting']['cod_fee_amount'] ?? 0);
                                         $cleanedExpress[] = $opt;
                                     }
                                 }
                                 $expressOptions['results'] = $cleanedExpress;
                             }
                        } catch (Exception $e) {
                             Log::error('Gagal mendapatkan ongkir Express/Cargo', ['error' => $e->getMessage()]);
                        }

                        // Tarik Data Instant
                        if (!$storeLat || !$storeLng) {
                            $geo = $this->geocode($storeSearch);
                            if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; }
                        }
                        if (!$userLat || !$userLng) {
                            $geo = $this->geocode($userSearch);
                            if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; }
                        }

                        if ($storeLat && $storeLng && $userLat && $userLng) {
                            try {
                                 $instantRaw = $kiriminAja->getInstantPricing(
                                     $storeLat, $storeLng, $store->address_detail ?? $storeSearch,
                                     $userLat, $userLng, $user->address_detail ?? $userSearch,
                                     $finalWeight, $itemValue, 'motor'
                                 );

                                 if (isset($instantRaw['status']) && $instantRaw['status'] === true && isset($instantRaw['result'])) {
                                     $parsedInstantOptions = [];
                                     foreach ($instantRaw['result'] as $provider) {
                                         if (isset($provider['costs']) && is_array($provider['costs'])) {
                                             foreach ($provider['costs'] as $cost) {
                                                 $price = $cost['price']['total_price'] ?? 0;
                                                 if ($price > 0) {
                                                     $parsedInstantOptions[] = [
                                                         'service' => $provider['name'],
                                                         'service_name' => ucfirst($provider['name']) . ' ' . ucfirst($cost['service_type']),
                                                         'service_type' => $cost['service_type'],
                                                         'cost' => $cost['price']['shipping_costs'] ?? $price,
                                                         'insurance_cost' => $cost['price']['insurance_fee'] ?? 0,
                                                         'final_price' => $price,
                                                         'etd' => $cost['estimation'] ?? '1-3 Jam',
                                                         'cod_available' => false,
                                                         'cod' => false,
                                                         'cod_fee' => 0,
                                                         'group' => 'instant',
                                                     ];
                                                 }
                                             }
                                         }
                                     }
                                     $instantOptions['results'] = $parsedInstantOptions;
                                 }
                            } catch (Exception $e) {
                                 Log::error('Gagal mendapatkan ongkir Instant', ['error' => $e->getMessage()]);
                            }
                            // ========================================================
                            // 🔥 TAMBAHAN 1: INJEKSI TARIF KURIR LOKAL (MAPBOX) 🔥
                            // ========================================================
                            // Deteksi apakah produk ini makanan/minuman lokal (Ganti keyword 'food' sesuai nama kategori di DB Anda)
                           $kategoriGroup = $firstProduct->category->category_group ?? '';
                            $kategoriName = $firstProduct->category->name ?? '';

                            // Cek dari nama grup ATAU nama kategori langsung
                            $isLocalFood = str_contains(strtolower($kategoriGroup), 'food')
                                        || str_contains(strtolower($kategoriGroup), 'makanan')
                                        || str_contains(strtolower($kategoriName), 'makanan');

                            if ($isLocalFood && $storeLat && $storeLng && $userLat && $userLng) {
                                try {
                                    $apiMapbox = app(\App\Http\Controllers\ApiMapboxController::class);
                                    $reqMapbox = new \Illuminate\Http\Request();
                                    $reqMapbox->replace([
                                        'origin_lat' => $storeLat,
                                        'origin_lng' => $storeLng,
                                        'dest_lat'   => $userLat,
                                        'dest_lng'   => $userLng,
                                    ]);

                                    $routeResult = $apiMapbox->calculateRoute($reqMapbox)->getData(true);

                                    if ($routeResult['success']) {
                                        $costLocal = $routeResult['data']['estimated_cost'];

                                        // Suntikkan Opsi Kurir Lokal ke dalam list Instant Options agar bisa dipilih user
                                        $instantOptions['results'][] = [
                                            'service' => 'sancaka_local',
                                            'service_name' => 'Kurir Lokal Sancaka (Motor)',
                                            'service_type' => 'FOOD',
                                            'cost' => $costLocal,
                                            'insurance_cost' => 0,
                                            'final_price' => $costLocal,
                                            'etd' => '15 - 45 Menit',
                                            'cod_available' => true,
                                            'cod' => true,
                                            'cod_fee' => 0,
                                            'group' => 'instant',
                                        ];
                                        $instantOptions['status'] = true;
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Gagal menghitung tarif kurir lokal Mapbox: ' . $e->getMessage());
                                }
                            }
                            // ========================================================
                        }
                    } else {
                        Log::warning('Alamat user tidak dikenali KiriminAja, wajib input manual.', ['user_search' => $userSearch]);
                    }
                }
            } catch (Exception $e) {
                Log::error('API Validation Bypass Executed', ['error' => $e->getMessage()]);
            }
        }
        // ========================================================
        // JIKA PRODUK DIGITAL / JASA: BERIKAN FAKE RESPONSE AGAR BLADE AMAN
        // ========================================================
        else {
            $expressOptions = [
                'status' => true,
                'results' => [
                    [
                        'service_name' => 'Pengiriman Digital / E-Ticket',
                        'service' => 'eticket',
                        'service_type' => 'noncod',
                        'cost' => 0,
                        'final_price' => 0,
                        'etd' => 'Otomatis (1 Detik)',
                        'group' => 'Digital',
                        'insurance_cost' => 0,
                        'cod_available' => false,
                        'cod_fee' => 0
                    ]
                ]
            ];
            $instantOptions = ['status' => false, 'results' => []];
        }

        // PASTIKAN VARIABLE 'isDigital' DILEMPAR KE COMPACT
        //return view('checkout.index', compact('cart', 'expressOptions', 'instantOptions', 'user', 'tripayChannels', 'hasDigital', 'hasPhysical', 'isStrictlyDigital'));

        return view('checkout.index', compact('cart', 'expressOptions', 'instantOptions', 'user', 'tripayChannels', 'hasDigital', 'hasPhysical', 'isStrictlyDigital', 'isLocalFood', 'storeLat', 'storeLng'));
        }


   public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'nama_penerima' => 'nullable|string|max:255',
            'no_wa_penerima' => 'nullable|string|max:20',
            'alamat_lengkap_penerima' => 'nullable|string',
        ]);


        $cart = session()->get('cart', []);
        $user = Auth::user();

        if (empty($cart)) {
            return redirect()->route('etalase.index')->with('error', 'Terjadi kesalahan. Keranjang Anda kosong.');
        }

        // =========================================================================
        // 🔥 ATURAN KETAT: SEMUA PRODUK TIDAK BOLEH CASH KECUALI USER ID 4 (ADMIN)
        // =========================================================================
        $paymentMethodRaw = strtoupper(trim($request->payment_method));

        // Tentukan keyword metode pembayaran apa saja yang dianggap "Cash" di sistem Anda
        $cashMethods = ['CASH', 'COD', 'CODBARANG'];

        if (in_array($paymentMethodRaw, $cashMethods)) {
            // Cek apakah user sedang login DAN apakah ID-nya adalah 4
            if (!$user || $user->id_pengguna != 4) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Mohon maaf, metode pembayaran Cash/COD hanya diperbolehkan khusus untuk akun Administrator Sancaka.');
            }
        }
        // =========================================================================

       // Deteksi apakah keranjang ini 100% digital atau ada barang fisiknya
        /* $isStrictlyDigital = true; // Asumsikan true dulu

        foreach ($cart as $item) {
            $isThisItemDigital = false;

            if (isset($item['type']) && in_array(strtolower($item['type']), ['eticket', 'digital', 'jasa'])) {
                $isThisItemDigital = true;
            }

            $productCheck = Product::find($item['product_id'] ?? null);
            if ($productCheck) {
                $kategoriObj = $productCheck->category()->first();
                if ($kategoriObj && in_array($kategoriObj->category_group, ['produk_digital', 'jasa'])) {
                    $isThisItemDigital = true;
                }
            }

            // JIKA KETEMU 1 SAJA BARANG FISIK, MAKA KERANJANG BUKAN 100% DIGITAL
            if (!$isThisItemDigital) {
                $isStrictlyDigital = false;
                break; // Hentikan pengecekan karena sudah pasti butuh pengiriman fisik
            }
        } */

      // === PERBAIKAN PERFORMA & HYBRID CART ===
        $productIds = collect($cart)->pluck('product_id')->filter()->unique()->toArray();
        $productsCache = \App\Models\Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        // Pisahkan menjadi 2 bendera (flags)
        $hasDigital = false;
        $hasPhysical = false;

        foreach ($cart as $item) {
            $isThisItemDigital = false;

            // 1. Cek dari session cart
            if (isset($item['type']) && (str_contains(strtolower($item['type']), 'digital') || in_array(strtolower($item['type']), ['eticket', 'jasa']))) {
                $isThisItemDigital = true;
            }

            // 2. Cek dari database & kategori
            $productCheck = $productsCache[$item['product_id'] ?? null] ?? null;
            if ($productCheck) {
                if (isset($productCheck->is_digital) && $productCheck->is_digital) {
                    $isThisItemDigital = true;
                }

                $kategoriData = $productCheck->category;
                $kategoriGrup = is_object($kategoriData) ? ($kategoriData->category_group ?? $kategoriData->name ?? null) : (is_array($kategoriData) ? ($kategoriData['category_group'] ?? $kategoriData['name'] ?? null) : $kategoriData);

                if ($kategoriGrup && (str_contains(strtolower($kategoriGrup), 'digital') || str_contains(strtolower($kategoriGrup), 'jasa') || str_contains(strtolower($kategoriGrup), 'tiket') || str_contains(strtolower($kategoriGrup), 'ticket') || str_contains(strtolower($kategoriGrup), 'eticket'))) {
                    $isThisItemDigital = true;
                }
            }

            // Tentukan status komponen keranjang
            if ($isThisItemDigital) {
                $hasDigital = true;
            } else {
                $hasPhysical = true;
            }
        }

        // Variabel khusus untuk mengunci Ongkir Rp0 (Hanya jika 100% digital)
        $isStrictlyDigital = $hasDigital && !$hasPhysical;

        $isDigital = $isStrictlyDigital;

       // 1. TAMBAHKAN DETEKSI MAKANAN DI SINI
        $isLocalFood = false;
        foreach ($cart as $item) {
            $productCheck = $productsCache[$item['product_id'] ?? null] ?? null;
            if ($productCheck) {
                $kategoriGroup = $productCheck->category->category_group ?? '';
                $kategoriName  = $productCheck->category->name ?? '';
                if (str_contains(strtolower($kategoriGroup), 'food') ||
                    str_contains(strtolower($kategoriGroup), 'makanan') ||
                    str_contains(strtolower($kategoriName), 'makanan')) {
                    $isLocalFood = true;
                }
            }
        }

        // 2. UBAH ATURAN BYPASS (Izinkan Digital ATAU Makanan lolos tanpa login)
        if (!$isStrictlyDigital && !$isLocalFood) {
            if (!$user) {
                return redirect()->route('login')->with('warning', 'Keranjang Anda berisi produk fisik reguler. Silakan login untuk memilih opsi kurir ekspedisi.');
            }
            if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province) || empty($user->address_detail)) {
                return redirect()->route('profile.edit')->with('warning', 'Alamat pengiriman Anda belum lengkap.');
            }
        }

        // Dapatkan koordinat Toko untuk dikirim ke JS Frontend
        $storeLat = $store ? $store->latitude : '-7.3998307'; // Default koordinat Ngawi jika kosong
        $storeLng = $store ? $store->longitude : '111.4511975';

       $isStrictlyDigital = $hasDigital && !$hasPhysical;
        $isDigital = $isStrictlyDigital;

        // DETEKSI MAKANAN (LOKAL) SAAT PROSES SIMPAN
        $isLocalFood = false;
        foreach ($cart as $item) {
            $productCheck = $productsCache[$item['product_id'] ?? null] ?? null;
            if ($productCheck) {
                $kategoriGroup = $productCheck->category->category_group ?? '';
                $kategoriName  = $productCheck->category->name ?? '';
                if (str_contains(strtolower($kategoriGroup), 'food') ||
                    str_contains(strtolower($kategoriGroup), 'makanan') ||
                    str_contains(strtolower($kategoriName), 'makanan')) {
                    $isLocalFood = true;
                }
            }
        }

        // JIKA BUKAN DIGITAL & BUKAN MAKANAN, WAJIB ALAMAT PROFIL
        if (!$isStrictlyDigital && !$isLocalFood && (!$user || empty($user->address_detail))) {
            return redirect()->route('profile.edit')->with('warning', 'Silakan lengkapi alamat pengiriman dahulu.');
        }

        // ====================================================================
        // 🔥 KEAMANAN & UX: SILENT REGISTRATION UNTUK GUEST CHECKOUT
        // ====================================================================
        // Izinkan Digital ATAU Makanan untuk didaftarkan diam-diam
        if (!$user && ($isStrictlyDigital || $isLocalFood)) {
            // Ambil data kontak dari form checkout guest
            $emailGuest = $request->email ?? 'guest_' . time() . '@tokosancaka.com';
            $waGuest = $request->no_wa_penerima ?? '081234567890';

            // Cek apakah email atau WA sudah terdaftar sebelumnya
            $user = \App\Models\User::where('no_wa', $waGuest)->orWhere('email', $emailGuest)->first();

            if (!$user) {
                // Zero Bugs: Buat akun diam-diam agar pesanan digital punya pemilik
                // Sehingga fungsi pengiriman E-Ticket via Email/WA di bawah tidak crash/gagal
                $user = \App\Models\User::create([
                    'nama_lengkap' => $request->nama_penerima ?? 'Guest Customer',
                    'email'        => $emailGuest,
                    'no_wa'        => preg_replace('/[^0-9]/', '', $waGuest),
                    'password'     => bcrypt(\Illuminate\Support\Str::random(12)),
                    'role'         => 'Pelanggan'
                ]);
                Log::info("LOG LOG - Akun otomatis dibuat via Guest Checkout: {$waGuest}");
            }
        }
        // ====================================================================

        DB::beginTransaction();

        try {
            // --- 1. Kalkulasi Biaya ---
            $subtotal = collect($cart)->sum(fn($details) => $details['price'] * $details['quantity']);
            $shippingParts = explode('-', $request->shipping_method);
            if (count($shippingParts) < 4) {
                 throw new \Exception('Format metode pengiriman tidak valid.');
            }
            $type = $shippingParts[0]; $courier = $shippingParts[1]; $service = $shippingParts[2];
            $shipCost = (int) ($shippingParts[3] ?? 0);
            $codFeeApi = (count($shippingParts) >= 6) ? (int) end($shippingParts) : 0;
            $asrCost = (count($shippingParts) >= 5) ? (int) $shippingParts[count($shippingParts) - ($codFeeApi > 0 ? 2 : 1)] : 0;
            $shipping_type = $type; $shipping_cost = $shipCost; $insurance_cost = $asrCost;

            // --- 2. Validasi Produk ---
            $firstCartItemData = reset($cart);
            $productId = $firstCartItemData['product_id'] ?? null;
            $firstProduct = $productId ? Product::find($productId) : null;

            if (!$firstProduct) {
                throw ValidationException::withMessages(['cart' => 'Produk di keranjang Anda (ID: '.$productId.') tidak dapat ditemukan.']);
            }
            if (!$firstProduct->store) {
                throw ValidationException::withMessages(['cart' => 'Produk ('.$firstProduct->name.') tidak memiliki relasi ke toko yang valid.']);
            }
            if (!$firstProduct->store->user) {
                throw ValidationException::withMessages(['cart' => 'Toko ('.$firstProduct->store->name.') tidak memiliki data penjual (user) yang valid.']);
            }

            $store = $firstProduct->store;

            // --- 3. Kalkulasi Total ---
            $itemTypeFirstProduct = (int) $firstProduct->jenis_barang;
            $mandatoryTypes = [1, 3, 4, 8];
            $isMandatoryInsurance = in_array($itemTypeFirstProduct, $mandatoryTypes);

            // ======================================================
            // ==== LOGIKA ASURANSI FIX UNTUK REACT NATIVE ====
            // ======================================================
            // Tangkap boolean (true/false) dari JSON API React Native
            $userWantsInsurance = filter_var($request->use_insurance, FILTER_VALIDATE_BOOLEAN);
            $useInsurance = ($userWantsInsurance || $isMandatoryInsurance);
            // ======================================================

            $base_total = $subtotal + $shipping_cost;
            $applied_insurance_cost = 0;
            if ($useInsurance) {
                 $base_total += $insurance_cost;
                 $applied_insurance_cost = $insurance_cost;
            }
            $cod_add_cost = 0;
            if (in_array($request->payment_method, ['cod', 'CODBARANG'])) {
                if ($shipping_type !== 'express' && $shipping_type !== 'cargo' && $shipping_type !== 'regular') {
                    return redirect()->back()->with('error', 'COD hanya tersedia untuk pengiriman Express, Cargo, atau Regular.');
                }
                if ($codFeeApi > 0) { $cod_add_cost = $codFeeApi; }
                else { $codFeePercentage = 0.03; $cod_add_cost = ceil($base_total * $codFeePercentage); }
            }
            $grand_total = $base_total + $cod_add_cost;

            // 🔥 TAMBAHAN 1: Deteksi dan Validasi Saldo
            $isPayWithSaldo = in_array(strtoupper($request->payment_method), ['POTONG SALDO', 'SALDO']);

            if ($isPayWithSaldo) {
                // Pastikan 'saldo' diganti dengan nama kolom dompet/saldo di tabel users Anda (misal: balance, dompet, dll)
                if ($user->saldo < $grand_total) {
                    throw ValidationException::withMessages(['payment_method' => 'Saldo Anda tidak mencukupi. Sisa saldo: Rp ' . number_format($user->saldo, 0, ',', '.')]);
                }
            }

            // --- 4. Buat Order & Order Items ---

            // --- 4. Buat Order & Order Items ---

            $storeDistrictId = null;
            $storeSubdistrictId = null;
            $userDistrictId = null;
            $userSubdistrictId = null;

            // HANYA TEMBAK API KIRIMINAJA JIKA BUKAN PRODUK DIGITAL & USER LOGIN
            if (!$isDigital && $user && $store) {
                // Gunakan operator ?-> (nullsafe) untuk mencegah error on null
                $storeSearch = $store?->village . ', ' . $store?->district . ', ' . $store?->regency . ', ' . $store?->province;
                $userSearch = $user?->village . ', ' . $user?->district . ', ' . $user?->regency . ', ' . $user?->province;

                $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                $userAddrRes = $kiriminAja->searchAddress($userSearch);

                $storeAddr = $storeAddrRes['data'][0] ?? null;
                $userAddr = $userAddrRes['data'][0] ?? null;

                $storeDistrictId = $storeAddr['district_id'] ?? null;
                $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                $userDistrictId = $userAddr['district_id'] ?? null;
                $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
            }

            // Generate Invoice
            /* do {
                 $invoiceNumber = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists() || Pesanan::where('nomor_invoice', $invoiceNumber)->exists());

            // ==========================================================
            // 🔥 MERAKIT ALAMAT LENGKAP JIKA MENGGUNAKAN FORM GUEST
            // ==========================================================
            $finalAddress = null;

            if ($isDigital && !$user) {
                // Jika Guest, rangkai semua input dari form manual
                $arrAlamat = array_filter([
                    $request->alamat_lengkap_penerima,
                    $request->kelurahan_penerima,
                    $request->kecamatan_penerima,
                    $request->kota_penerima,
                    $request->provinsi_penerima,
                    $request->kode_pos_penerima
                ]);

                // Gabungkan menjadi string: "Jalan Mawar, Margomulyo, Ngawi, Jawa Timur, 63211"
                $finalAddress = implode(', ', $arrAlamat);
            } else {
                // Jika Auth User, utamakan alamat dari profile
                $finalAddress = $user ? $user->address_detail : 'Alamat Tidak Valid';

                // Fallback jika profile kosong tapi user isi form manual di checkout digital
                if(empty($finalAddress) && $isDigital) {
                     $arrAlamat = array_filter([
                        $request->alamat_lengkap_penerima,
                        $request->kelurahan_penerima,
                        $request->kecamatan_penerima,
                        $request->kota_penerima,
                        $request->provinsi_penerima,
                        $request->kode_pos_penerima
                    ]);
                    $finalAddress = implode(', ', $arrAlamat);
                }
            }

            // Pembuatan Order sekarang tidak akan error karena variabel sudah dideklarasikan
            $order = new Order([
                 'store_id'                => $store ? $store->id : null,
                 'user_id'                 => $user ? $user->id_pengguna : null, // Aman jika Guest (null)
                 'invoice_number'          => $invoiceNumber,
                 'subtotal'                => $subtotal,
                 'shipping_cost'           => $shipping_cost,
                 'insurance_cost'          => $applied_insurance_cost,
                 'cod_fee'                 => $cod_add_cost,
                 'total_amount'            => $grand_total,
                 'shipping_method'         => $request->shipping_method,
                 'payment_method'          => $request->payment_method,
                 'status'                  => (in_array($request->payment_method, ['cod', 'cash', 'CODBARANG'])) ? 'processing' : 'pending',
                 'customer_latitude'       => $request->latitude ?? null,
                 'customer_longitude'      => $request->longitude ?? null,

                 // 🔥 MENGGUNAKAN VARIABEL YANG SUDAH DIRAKIT
                 'shipping_address'        => $finalAddress,
                 'receiver_name'           => $request->nama_penerima ?? ($user ? $user->nama_lengkap : 'Guest Customer'),
                 'receiver_phone'          => $request->no_wa_penerima ?? ($user ? $user->no_wa : '081234567890'),
                 'nik_penerima'            => $request->nik_penerima ?? null,

                 'receiver_district_id'    => $userDistrictId,
                 'receiver_subdistrict_id' => $userSubdistrictId,
                 'sender_district_id'      => $storeDistrictId,
                 'sender_subdistrict_id'   => $storeSubdistrictId,
            ]);
            $order->save();

            $orderItemsPayload = [];
            foreach ($cart as $cartKey => $details) {
                 $realProductId = $details['product_id']; $realVariantId = $details['variant_id'];
                 OrderItem::create([ 'order_id' => $order->id, 'product_id' => $realProductId, 'product_variant_id' => $realVariantId, 'quantity' => $details['quantity'], 'price' => $details['price'], ]);
                 if ($realVariantId) { $variant = ProductVariant::find($realVariantId); if ($variant) $variant->decrement('stock', $details['quantity']); }
                 else { $product = Product::find($realProductId); if ($product) $product->decrement('stock', $details['quantity']); }
                $orderItemsPayload[] = [ 'sku' => $cartKey, 'name' => $details['name'], 'price' => (int) $details['price'], 'quantity' => $details['quantity'],];

            }
            $orderItemsPayload[] = [ 'sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1 ];
            if($applied_insurance_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'INSURANCE', 'name' => 'Asuransi', 'price' => $applied_insurance_cost, 'quantity' => 1 ]; }
            if($cod_add_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'CODFEE', 'name' => 'Biaya COD', 'price' => $cod_add_cost, 'quantity' => 1 ]; }
            */

            // --- 1. BUAT INVOICE INDUK (TAGIHAN TRIPAY) ---
            do {
                 $parentInvoice = 'SCK-ORD-' . strtoupper(Str::random(8));
            } while (Order::where('parent_invoice', $parentInvoice)->exists());

            // --- 2. MERAKIT ALAMAT LENGKAP (GUEST / USER) ---
            $finalAddress = null;
            if ($isDigital && !$user) {
                $arrAlamat = array_filter([$request->alamat_lengkap_penerima, $request->kelurahan_penerima, $request->kecamatan_penerima, $request->kota_penerima, $request->provinsi_penerima, $request->kode_pos_penerima]);
                $finalAddress = implode(', ', $arrAlamat);
            } else {
                $finalAddress = $user ? $user->address_detail : 'Alamat Tidak Valid';
                if(empty($finalAddress) && $isDigital) {
                     $arrAlamat = array_filter([$request->alamat_lengkap_penerima, $request->kelurahan_penerima, $request->kecamatan_penerima, $request->kota_penerima, $request->provinsi_penerima, $request->kode_pos_penerima]);
                    $finalAddress = implode(', ', $arrAlamat);
                }
            }

            // --- 3. PISAHKAN ISI KERANJANG (FISIK VS DIGITAL) ---
            $itemsFisik = [];
            $itemsDigital = [];
            $orderItemsPayload = []; // Untuk payload Tripay

            foreach ($cart as $cartKey => $details) {
                // Siapkan payload untuk dikirim ke Tripay (semua item digabung)
                $orderItemsPayload[] = [ 'sku' => $cartKey, 'name' => $details['name'], 'price' => (int) $details['price'], 'quantity' => $details['quantity'] ];

                // Cek tipe produk untuk dipisah ke order anak
                $produkCek = Product::find($details['product_id']);
                $kategoriGroup = $produkCek->category->category_group ?? '';

                if (in_array($kategoriGroup, ['produk_digital', 'jasa']) || (isset($details['type']) && in_array(strtolower($details['type']), ['eticket', 'digital', 'jasa']))) {
                    $itemsDigital[$cartKey] = $details;
                } else {
                    $itemsFisik[$cartKey] = $details;
                }
            }

            // Tambahan Biaya untuk Payload Tripay
            $orderItemsPayload[] = [ 'sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1 ];
            if($applied_insurance_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'INSURANCE', 'name' => 'Asuransi', 'price' => $applied_insurance_cost, 'quantity' => 1 ]; }
            if($cod_add_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'CODFEE', 'name' => 'Biaya COD', 'price' => $cod_add_cost, 'quantity' => 1 ]; }

            $order = null; // Sebagai trigger variabel global di bawahnya

            // --- 4. SIMPAN ORDER ANAK: FISIK ---
            if (count($itemsFisik) > 0) {
                $subtotalFisik = collect($itemsFisik)->sum(fn($details) => $details['price'] * $details['quantity']);
                $totalFisik = $subtotalFisik + $shipping_cost + $applied_insurance_cost + $cod_add_cost;

                $orderFisik = new Order([
                    'parent_invoice'          => $parentInvoice, // PENGIKAT KE INDUK
                    'invoice_number'          => 'SCK-ORD-' . strtoupper(Str::random(6)),
                    'store_id'                => $store ? $store->id : null,
                    'user_id'                 => $user ? $user->id_pengguna : null,
                    'subtotal'                => $subtotalFisik,
                    'shipping_cost'           => $shipping_cost,
                    'insurance_cost'          => $applied_insurance_cost,
                    'cod_fee'                 => $cod_add_cost,
                    'total_amount'            => $totalFisik,
                    'shipping_method'         => $request->shipping_method,
                    'payment_method'          => $request->payment_method,
                    'status'                  => (in_array($request->payment_method, ['cod', 'cash', 'CODBARANG'])) ? 'processing' : 'pending',
                    'customer_latitude'       => $request->latitude ?? null,
                    'customer_longitude'      => $request->longitude ?? null,
                    'shipping_address'        => $finalAddress,
                    'receiver_name'           => $request->nama_penerima ?? ($user ? $user->nama_lengkap : 'Guest Customer'),
                    'receiver_phone'          => $request->no_wa_penerima ?? ($user ? $user->no_wa : '081234567890'),
                    'nik_penerima'            => $request->nik_penerima ?? null,
                    'receiver_district_id'    => $userDistrictId,
                    'receiver_subdistrict_id' => $userSubdistrictId,
                    'sender_district_id'      => $storeDistrictId,
                    'sender_subdistrict_id'   => $storeSubdistrictId,
                ]);
                $orderFisik->save();

                foreach ($itemsFisik as $cartKey => $details) {
                    OrderItem::create([ 'order_id' => $orderFisik->id, 'product_id' => $details['product_id'], 'product_variant_id' => $details['variant_id'] ?? null, 'quantity' => $details['quantity'], 'price' => $details['price'] ]);
                    if (!empty($details['variant_id'])) { ProductVariant::find($details['variant_id'])?->decrement('stock', $details['quantity']); }
                    else { Product::find($details['product_id'])?->decrement('stock', $details['quantity']); }
                }

                $order = $orderFisik; // Default order untuk dikirim ke gateway
            }

            // --- 5. SIMPAN ORDER ANAK: DIGITAL ---
            if (count($itemsDigital) > 0) {
                $subtotalDigital = collect($itemsDigital)->sum(fn($details) => $details['price'] * $details['quantity']);

                $orderDigital = new Order([
                    'parent_invoice'          => $parentInvoice, // PENGIKAT KE INDUK
                    'invoice_number'          => 'SCK-DIG-' . strtoupper(Str::random(6)),
                    'store_id'                => $store ? $store->id : null,
                    'user_id'                 => $user ? $user->id_pengguna : null,
                    'subtotal'                => $subtotalDigital,
                    'shipping_cost'           => 0, // Digital ga pake ongkir
                    'insurance_cost'          => 0,
                    'cod_fee'                 => 0,
                    'total_amount'            => $subtotalDigital,
                    'shipping_method'         => 'digital_delivery-eticket-noncod-0-0-0',
                    'payment_method'          => $request->payment_method,
                    'status'                  => 'pending',
                    'shipping_address'        => 'Pengiriman Digital / E-Ticket',
                    'receiver_name'           => $request->nama_penerima ?? ($user ? $user->nama_lengkap : 'Guest Customer'),
                    'receiver_phone'          => $request->no_wa_penerima ?? ($user ? $user->no_wa : '081234567890'),
                ]);
                $orderDigital->save();

                foreach ($itemsDigital as $cartKey => $details) {
                    OrderItem::create([ 'order_id' => $orderDigital->id, 'product_id' => $details['product_id'], 'product_variant_id' => $details['variant_id'] ?? null, 'quantity' => $details['quantity'], 'price' => $details['price'] ]);
                    if (!empty($details['variant_id'])) { ProductVariant::find($details['variant_id'])?->decrement('stock', $details['quantity']); }
                    else { Product::find($details['product_id'])?->decrement('stock', $details['quantity']); }
                }

                if (!$order) { $order = $orderDigital; } // Jika ga ada fisik, jadikan digital trigger utama
            }

            // Ganti nama invoice di memory (sementara) jadi parentInvoice biar Tripay nerimanya tagihan gabungan
            $order->invoice_number = $parentInvoice;
            $order->total_amount = $grand_total; // Gunakan grand total asli dari kalkulasi lu sebelumnya

            $paymentUrl = null;

            // --- 5. Logika KiriminAja untuk COD/Cash ---
            if (in_array($request->payment_method, ['cod', 'cash', 'CODBARANG'])) {

                $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province; $userSearch = $user->village . ', ' . $user->regency . ', ' . $user->province;
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch); $userAddrRes = $kiriminAja->searchAddress($userSearch);
                $storeAddr = $storeAddrRes['data'][0] ?? null; $userAddr = $userAddrRes['data'][0] ?? null;
                $storeDistrictId = $storeAddr['district_id'] ?? null; $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                $userDistrictId = $userAddr['district_id'] ?? null; $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                $storeLat = $store->latitude; $storeLng = $store->longitude; $userLat = $user->latitude; $userLng = $user->longitude;
                if (!$storeLat || !$storeLng) { $geo = $this->geocode($storeSearch); if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; } }
                if (!$userLat || !$userLng) { $geo = $this->geocode($userSearch); if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; } }
                $schedule = $kiriminAja->getSchedules();
                $totalWeight = (int) collect($cart)->sum(function($item) { $product = Product::find($item['product_id']); return ($product->weight ?? 1000) * $item['quantity']; });
                $finalWeight = max(1000, $totalWeight); $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

                $packages = $order->items()->with('product', 'variant')->get()->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipping_cost, $courier, $service, $useInsurance, $user, $request, $grand_total) {
                    $product = $item->product; if (!$product) return null; $variant = $item->variant;
                    $weight = $product->weight ?? 1000;
                    $width = $product->width ?? 5; $height = $product->height ?? 5; $length = $product->length ?? 5;
                    $jenis_barang = $product->jenis_barang ?? 1;
                    $itemName = $product->name . ($variant ? ' (' . ($variant->combination_string ? str_replace(';', ', ', $variant->combination_string) : $variant->sku_code) . ')' : '');
                    return [
                        'order_id' => $order->invoice_number,
                        'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                        'destination_address' => $order->shipping_address,
                        'destination_kecamatan_id' => $userDistrictId, 'destination_kelurahan_id' => $userSubdistrictId,
                        'destination_zipcode' => $user->postal_code ?? 55598,
                        'weight' => $weight * $item->quantity, 'width' => $width, 'height' => $height, 'length' => $length,
                        'item_value' => $item->price * $item->quantity,
                        'shipping_cost' => $shipping_cost, 'service' => $courier, 'service_type' => $service,
                        'insurance_amount' => $useInsurance ? ($item->price * $item->quantity) : 0,
                        'item_name' => $itemName, 'package_type_id' => (int) $jenis_barang,
                        'cod' => in_array($request->payment_method, ['cod', 'CODBARANG']) ? $grand_total : 0,
                    ];
                })->filter()->values()->toArray();

                if (empty($packages)) { throw new \Exception('Tidak ada item valid dalam pesanan untuk dikirim.'); }

                if ($shipping_type === 'express' || $shipping_type === 'cargo' || $shipping_type === 'regular') {
                    if (!$storeDistrictId || !$storeSubdistrictId || !$userDistrictId || !$userSubdistrictId) throw new \Exception('ID Kecamatan/Kelurahan tidak valid.');
                    $payload = [
                        'address' => $store->address_detail, 'phone' => $store->user->no_wa,
                        'kecamatan_id' => $storeDistrictId, 'kelurahan_id' => $storeSubdistrictId,
                        'latitude' => $storeLat, 'longitude' => $storeLng,
                        'packages' => $packages, 'name' => $store->name,
                        'zipcode' => $store->postal_code ?? '63271',
                        'platform_name' => 'TOKOSANCAKA.COM',
                        'schedule' => $schedule['clock'] ?? null,
                        'category' => $category,
                    ];

                    Log::info('PAYLOAD KE KIRIMINAJA:', $payload);

                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);

                    // ======================================================
                    // ==== INI SATU-SATUNYA JEBAKAN LOG YANG KITA PERLU ====
                    // ======================================================
                    Log::info('RESPON JSON CREATE ORDER:', $kiriminResponse);
                    // ======================================================

                } elseif ($shipping_type === 'instant') {
                    if (!$storeLat || !$storeLng || !$userLat || !$userLng) throw new \Exception('Koordinat tidak ditemukan.');
                    $firstPackageItem = $packages[0];
                    $payload = [
                        'service' => $courier, 'service_type' => $service, 'vehicle' => 'motor',
                        'order_prefix' => $order->invoice_number,
                        'packages' => [[
                            'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                            'destination_lat' => $userLat, 'destination_long' => $userLng,
                            'destination_address' => $order->shipping_address,
                            'origin_name' => $store->name, 'origin_phone' => $store->user->no_wa,
                            'origin_lat' => $storeLat, 'origin_long' => $storeLng,
                            'origin_address' => $store->address_detail,
                            'shipping_price' => (int) $shipping_cost,
                            'item' => [
                                'name' => 'Pesanan ' . $order->invoice_number,
                                'description' => $firstPackageItem['item_name'] ?? 'Pesanan dari toko',
                                'price' => $order->subtotal, 'weight' => $finalWeight,
                            ]
                        ]]
                    ];
                    $kiriminResponse = $kiriminAja->createInstantOrder($payload);

                    // ======================================================
                    // ==== KITA TAMBAHKAN JUGA DI SINI (JAGA-JAGA) ====
                    // ======================================================
                    Log::info('RESPON JSON CREATE ORDER (INSTANT):', $kiriminResponse);
                    // ======================================================
                } elseif ($shipping_type === 'digital_delivery') {
                    // ======================================================
                    // BYPASS API KIRIMINAJA UNTUK E-TICKET / JASA
                    // ======================================================
                    Log::info('Pesanan Digital/Jasa terdeteksi, bypass API KiriminAja.');

                    // Buat fake response agar script di bawahnya tidak error
                    $kiriminResponse = [
                        'status' => true,
                        'pickup_number' => 'DIGITAL-' . strtoupper(Str::random(6))
                    ];

                    // ======================================================
                // 🔥 TAMBAHAN 2: BYPASS API UNTUK KURIR LOKAL 🔥
                // ======================================================
                } elseif ($shipping_type === 'sancaka_local') {
                    Log::info('Pesanan Kurir Lokal / Food terdeteksi, bypass API KiriminAja.');

                    // Beri resi dummy sementara, status order Anda bisa diubah jadi 'mencari_driver' nanti
                    $kiriminResponse = [
                        'status' => true,
                        'pickup_number' => 'LOKAL-' . strtoupper(Str::random(6))
                    ];
                // ======================================================

                } else {
                    throw new \Exception('Tipe pengiriman tidak didukung: ' . $shipping_type);
                }

                if (empty($kiriminResponse['status']) || $kiriminResponse['status'] !== true) {
                    $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order KiriminAja.');
                    throw new \Exception('Gagal membuat order pengiriman: ' . $errorMessage);
                }

                // INI BARIS YANG MASIH SALAH, TAPI KITA BIARKAN DULU
                $resi = $kiriminResponse['packages'][0]['awb'] ?? ($kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null));

                if ($resi) { $order->shipping_resi = $resi; }

                //try {
                //     broadcast(new AdminNotificationEvent(
                //         'Pesanan COD/Cash Baru',
                //         "Pesanan #{$order->invoice_number} (Rp " . number_format($order->total_amount) . ") telah masuk.",
                //         route('admin.orders.show', $order->id)
                //     ));
                //} catch (Exception $e) {
                //     Log::error('Gagal broadcast AdminNotificationEvent untuk COD/Cash', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                //}

            }
            // 🔥 TAMBAHAN 2: Logika Eksekusi Potong Saldo
            elseif ($isPayWithSaldo) {
                Log::info("Memulai proses POTONG SALDO untuk {$order->invoice_number}");

                // 1. Kurangi saldo user (Sesuaikan nama kolom 'saldo' dengan database Anda)
                $user->saldo -= $grand_total;
                $user->save();

                // 2. Simulasikan callback agar sistem otomatis mengurus resi KiriminAja & Notif WA
                // Memanfaatkan fungsi Anda yang sudah sangat lengkap di bawah
                $this->processOrderCallback($order->invoice_number, 'PAID', []);
                $order->refresh();
            }
           else
            {
                // --- 6. Logika Pembayaran Online (Midtrans, Tripay, ATAU Doku) ---

                $order->invoice_number = $parentInvoice;
                $order->total_amount = $grand_total;

                $custName  = $request->nama_penerima ?? ($user ? $user->nama_lengkap : 'Guest Customer');
                $custPhone = $request->no_wa_penerima ?? ($user ? $user->no_wa : '081234567890');
                // Payment Gateway (DOKU/Tripay) biasanya mewajibkan email
                $custEmail = $user ? $user->email : 'guest@tokosancaka.com';
                // ==========================================================

                $paymentGateway = 'tripay'; // Default
                $paymentMethodRaw = strtoupper($request->payment_method);

                // Tangkap semua metode yang diawali DOKU_ (e.g., DOKU_BCA_VA, DOKU_QRIS)
                if (\Illuminate\Support\Str::startsWith($paymentMethodRaw, 'DOKU_')) {
                    $paymentGateway = 'doku';
                } elseif ($paymentMethodRaw === 'MIDTRANS') {
                    $paymentGateway = 'midtrans';
                } elseif (in_array($paymentMethodRaw, ['DANA', 'NETWORK_PAY_PG_DANA', 'DANA_BINDING'])) {
                    $paymentGateway = 'dana_direct';
                } elseif ($paymentMethodRaw === 'PAYPAL') {
                    $paymentGateway = 'paypal';
                }

                // ==========================================================
                // PROSES VIA MIDTRANS
                // ==========================================================
                if ($paymentGateway === 'midtrans') {
                    Log::info('Memulai proses MIDTRANS Marketplace untuk ' . $order->invoice_number);
                    $paymentUrl = $this->createPaymentMidtransSnap($order); // Akan mengambil Auth::user di dalam fungsinya, tapi jika guest kita sarankan kirim parameter
                    $order->payment_url = $paymentUrl;
                }

                // ==========================================================
                // PROSES VIA DOKU
                // ==========================================================
                elseif ($paymentGateway === 'doku_jokul') {
                    Log::info('Memulai proses DOKU (Jokul) Marketplace untuk ' . $order->invoice_number);

                    $targetSacId = null; // DANA SELALU MASUK KE ADMIN DULU

                    if (!empty($store->doku_sac_id)) {
                        Log::info("DOKU Routing: Toko punya SAC ID ({$store->doku_sac_id}), TAPI dana ditahan di Admin.");
                    }

                    $dokuService = new DokuJokulService();

                    // Menggunakan variabel pengaman
                    $customerData = [
                        'name'  => $custName,
                        'email' => $custEmail,
                        'phone' => $custPhone
                    ];

                    $additionalInfo = [];
                    if (!empty($targetSacId)) {
                        $additionalInfo = [
                            'account' => [ 'id' => $targetSacId ]
                        ];
                    }

                    $paymentUrl = $dokuService->createPayment(
                        $order->invoice_number,
                        $grand_total,
                        $customerData,
                        $orderItemsPayload,
                        $additionalInfo
                    );

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi pembayaran DOKU.');
                    }

                    $order->payment_url = $paymentUrl;
                }

               // ==========================================================
                // PROSES VIA DOKU (CHECKOUT API DENGAN REDIRECT)
                // ==========================================================
                elseif ($paymentGateway === 'doku') {
                    Log::info('Memulai proses DOKU Checkout API Marketplace untuk ' . $order->invoice_number);

                    $targetSacId = !empty($store->doku_sac_id) ? $store->doku_sac_id : null;
                    $dokuService = new \App\Services\DokuJokulService();

                    $customerData = [
                        'name'  => $custName,
                        'email' => $custEmail,
                        'phone' => $custPhone
                    ];

                    // URL kembali otomatis ke web Sancaka jika user selesai di DOKU
                    $returnUrl = url('/customer/pesanan/riwayat-belanja');

                    // Tembak Fungsi Baru
                    $dokuResult = $dokuService->createSpecificCheckoutPayment(
                        $order->invoice_number,
                        $grand_total,
                        $customerData,
                        $paymentMethodRaw,
                        $targetSacId,
                        $returnUrl
                    );

                    if ($dokuResult['success'] && !empty($dokuResult['payment_url'])) {
                        // Simpan URL Kasir DOKU ke database
                        $order->payment_url = $dokuResult['payment_url'];
                    } else {
                        throw new \Exception($dokuResult['message']);
                    }
                }

                // ==========================================================
                // PROSES VIA TRIPAY
                // ==========================================================
                elseif ($paymentGateway === 'tripay') {
                    Log::info('Memulai proses TRIPAY Marketplace untuk ' . $order->invoice_number);

                    $tripayResult = $this->_createTripayTransaction(
                        $order,
                        $request->payment_method,
                        $grand_total,
                        $custName,  // Menggunakan variabel pengaman
                        $custEmail, // Menggunakan variabel pengaman
                        $custPhone, // Menggunakan variabel pengaman
                        $orderItemsPayload
                    );

                    if ($tripayResult['success']) {
                        $tripayData = $tripayResult['data'];
                        $order->payment_url = $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? null;
                        $order->pay_code = $tripayData['pay_code'] ?? null;
                        $order->qr_url = $tripayData['qr_url'] ?? null;
                        $order->save();
                    } else {
                        throw new \Exception($tripayResult['message']);
                    }
                }

                // ==========================================================
                // PROSES VIA PAYPAL
                // ==========================================================
                elseif ($paymentGateway === 'paypal') {
                    Log::info('Memulai proses PAYPAL Marketplace untuk ' . $order->invoice_number);
                    $paymentUrl = $this->createPaymentPayPal($order);
                    $order->payment_url = $paymentUrl;
                }

            // --- 7. Selesai Semua, Commit Transaksi ---

            }

            // ==========================================================
            // 4. PROSES REDIRECT (UPDATE SESUAI INSTRUKSI)
            // ==========================================================

            // Simpan status akhir & clear session
            // UPDATE SEMUA ANAK AGAR PUNYA PAYMENT URL YANG SAMA
            Order::where('parent_invoice', $parentInvoice)->update([
                'payment_url' => $order->payment_url,
                'pay_code'    => $order->pay_code ?? null,
                'qr_url'      => $order->qr_url ?? null,
            ]);

            DB::commit();
            session()->forget('cart');

            // --- A1. JIKA METODE MIDTRANS, PAYPAL ATAU DOKU (AUTO REDIRECT KE PAYMENT GATEWAY) ---
            if (isset($paymentGateway) && in_array($paymentGateway, ['midtrans', 'paypal', 'doku']) && !empty($order->payment_url)) {
                return redirect()->away($order->payment_url);
            }

            // --- A2. JIKA METODE DANA (GAPURA & BINDING) ---
			$danaMethodRaw = strtoupper($request->payment_method);
			if ($danaMethodRaw === 'DANA' || $danaMethodRaw === 'NETWORK_PAY_PG_DANA') {
			    // Gapura Standard Checkout
			    return $this->createPaymentDANA($order);
			} elseif ($danaMethodRaw === 'DANA_BINDING') {
			    // Direct Debit / Akun Terhubung
			    return $this->createPaymentDanaBinding($order, Auth::user());
			}

           // --- B. JIKA COD / CASH / TRIPAY / DOKU (REDIRECT KE HALAMAN TOKO) ---

            // Cek Role User untuk menentukan tujuan redirect
            $currentUser = Auth::user();

            // 1. Jika ADMIN -> Ke Halaman Admin Orders
            if ($currentUser && $currentUser->role === 'Admin') {
                if (in_array($request->payment_method, ['cod', 'cash'])) {
                    $this->kirimNotifikasiPesananLengkap($order, 'Baru (COD/Cash)');
                }
                return redirect()->to('https://tokosancaka.com/admin/orders')
                    ->with('success', 'Pesanan berhasil dibuat (Mode Admin).');
            }
            // 2. Jika CUSTOMER / SELLER / GUEST
            else {
                if (in_array($request->payment_method, ['cod', 'cash'])) {
                    $this->kirimNotifikasiPesananLengkap($order, 'Baru (COD/Cash)');
                }

                // 🔥 SMART ROUTING: Jika user_id kosong, berarti dia GUEST
                if (empty($order->user_id)) {
                    return redirect()->route('guest.history_belanja', ['invoice' => $order->invoice_number])
                        ->with('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran Anda.');
                }

                // Jika user_id ada, berarti punya akun
                return redirect()->route('customer.pesanan.riwayat_belanja')
                    ->with('success', 'Pesanan berhasil! Silakan cek status pembayaran Anda.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses order: ' . $e->getMessage());
        }
    }

   public function createPaymentDANA(Order $order)
    {
        // ====================================================================
        // 1. DYNAMIC CONFIGURATION DARI DATABASE
        // ====================================================================
        // Cek Mode Global (0 = Sandbox, 1 = Production)
        $danaMode = \App\Models\Api::getValue('dana_production_mode', 'global', '0');
        $isProduction = ($danaMode == '1');

        if ($isProduction) {
            \Illuminate\Support\Facades\Log::info('LOG LOG: Checkout DANA Menggunakan Mode PRODUCTION');
            $merchantIdConf = \App\Models\Api::getValue('dana_prod_merchant_id', 'production');
            $partnerIdConf  = \App\Models\Api::getValue('dana_prod_client_id', 'production');
            $privateKey     = \App\Models\Api::getValue('dana_prod_private_key', 'production');
            $clientSecret   = \App\Models\Api::getValue('dana_prod_client_secret', 'production');
            $publicKey      = \App\Models\Api::getValue('dana_prod_public_key', 'production');
            $baseUrl        = 'https://api.saas.dana.id';
        } else {
            \Illuminate\Support\Facades\Log::info('LOG LOG: Checkout DANA Menggunakan Mode SANDBOX');
            $merchantIdConf = \App\Models\Api::getValue('dana_sandbox_merchant_id', 'sandbox');
            $partnerIdConf  = \App\Models\Api::getValue('dana_sandbox_client_id', 'sandbox');
            $privateKey     = \App\Models\Api::getValue('dana_sandbox_private_key', 'sandbox');
            $clientSecret   = \App\Models\Api::getValue('dana_sandbox_client_secret', 'sandbox');
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
            'services.dana.client_secret' => $clientSecret,
            'services.dana.base_url'      => $baseUrl,
            'services.dana.dana_env'      => $isProduction ? 'PRODUCTION' : 'SANDBOX',
            'services.dana.origin'        => url('/')
        ]);

        $originDomain = url('/');

        // ====================================================================
        // 2. DATA PREPARATION
        // ====================================================================
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $order->invoice_number);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$order->total_amount, 2, '.', '');
        // 🔥 LOGIKA SMART ROUTING
        $returnUrl = $order->user_id
            ? route('customer.pesanan.riwayat_belanja')
            : route('guest.history_belanja', ['invoice' => $order->invoice_number]);

      // ====================================================================
        // 3. BODY REQUEST (TANPA payOptionDetails)
        // ====================================================================
        $bodyArray = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "amount"             => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "validUpTo"          => $expiryTime,
            "urlParams"          => [
            [
                "url"        => $returnUrl, // <--- UBAH MENJADI VARIABLE INI
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
                        "externalUserId"   => (string) ($order->user_id ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $order->user->nama_lengkap ?? 'Guest'), 0, 64),
                    ],
                    "goods" => [
                        [
                            "name"            => "Pembayaran Order",
                            "merchantGoodsId" => substr("ITEM" . $cleanInvoice, 0, 64),
                            "description"     => "Pembayaran Order Marketplace",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // ====================================================================
        // INI TERSANGKA UTAMANYA! UBAH RELATIVE PATH MENJADI SEPERTI INI:
        // ====================================================================
        $relativePath = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';

        try {
            // Lanjut ke proses pembuatan signature...
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf,
                'X-EXTERNAL-ID'  => \Illuminate\Support\Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => $originDomain,
            ];

            // ====================================================================
            // 5. LOGGING REQUEST
            // ====================================================================
            \Illuminate\Support\Facades\Log::info('DANA_REQ_START (Dynamic Mode Checkout)', [
                'Mode'    => $isProduction ? 'PRODUCTION' : 'SANDBOX',
                'Invoice' => $cleanInvoice,
                'URL'     => $baseUrl . $relativePath,
                'Headers' => $headers,
                'Body'    => $bodyArray
            ]);

            // ====================================================================
            // 6. SEND REQUEST
            // ====================================================================
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $relativePath);

            $result = $response->json();

            // ====================================================================
            // 7. LOGGING RESPONSE
            // ====================================================================
            \Illuminate\Support\Facades\Log::info('DANA_RES_END', [
                'Invoice'     => $cleanInvoice,
                'Status_Code' => $response->status(),
                'Result'      => $result
            ]);

            // ====================================================================
            // 8. HANDLE SUCCESS / REDIRECT
            // ====================================================================
            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    $order->payment_url = substr($redirectUrl, 0, 255);
                    $order->save();

                    session()->forget('cart');

                    // Simpan ke session sebagai cadangan
                    session()->put('last_dana_ref', $order->invoice_number);

                    return redirect()->away($redirectUrl);
                }
            }

            \Illuminate\Support\Facades\Log::error('DANA_FAIL_CHECKOUT', ['Result' => $result]);
            return redirect()->route('checkout.index')->with('error', 'Gagal memproses pembayaran DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DANA_EXCEPTION_CHECKOUT', ['Error' => $e->getMessage()]);
            return redirect()->route('checkout.index')->with('error', 'Terjadi kesalahan koneksi ke DANA.');
        }
    }


    public function invoice($invoice)
    {
        // 1. Ambil data Order dari Database
        $order = Order::with('items.product', 'items.variant', 'store', 'user')
            ->where('invoice_number', $invoice)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 2. Siapkan variabel untuk data Tripay
        $tripayDetail = null;

        // 3. Cek apakah perlu ambil data ke Tripay?
        // (Jangan ambil jika COD, DANA Direct, atau sudah Lunas)
        $excludeMethods = ['cod', 'CODBARANG', 'cash', 'DANA'];

        if (!in_array($order->payment_method, $excludeMethods) && $order->status !== 'paid') {

            try {
                $apiKey = config('tripay.api_key');
                $mode   = config('tripay.mode');
                $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';

                // PANGGIL API DETAIL TRANSAKSI TRIPAY
                $response = Http::withToken($apiKey)
                    ->get($baseUrl . '/transaction/detail', [
                        'reference' => $invoice // Kirim No Invoice
                    ]);

                if ($response->successful()) {
                    $tripayDetail = $response->json()['data'];
                }
            } catch (\Exception $e) {
                Log::error("Gagal ambil detail Tripay: " . $e->getMessage());
            }
        }

        // 4. Kirim data $tripayDetail ke View
        return view('checkout.invoice', compact('order', 'tripayDetail'));
    }

    /**
     * =========================================================================
     * INI ADALAH "GERBANG UTAMA" CALLBACK TRIPAY ANDA
     * =========================================================================
     */
    public function TripayCallback(Request $request)
    {
        $json = $request->getContent();
        $data = json_decode($json, true);
        Log::info('CheckoutController Tripay Callback Received:', $data ?? ['raw' => $json]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('CheckoutController Callback: Invalid JSON received.');
            return response()->json(['success' => false, 'message' => 'Invalid JSON'], 400);
        }

        $privateKey = config('tripay.private_key');
        $callbackSignature = $request->header('X-Callback-Signature');
        $expectedSignature = hash_hmac('sha256', $json, $privateKey);

        if (config('tripay.skip_signature_check') !== true) {
             if (!$callbackSignature || !hash_equals($expectedSignature, $callbackSignature)) {
                 Log::warning('CheckoutController Callback: Invalid Signature.', [
                     'received' => $callbackSignature, 'expected' => $expectedSignature
                 ]);
                 return response()->json(['success' => false, 'message' => 'Invalid Signature'], 401);
             }
        } else {
             Log::warning('!!! Tripay Signature Check Skipped (DEBUG MODE) !!!');
        }


        $merchantRef = $data['merchant_ref'] ?? null;
        $status = $data['status'] ?? null;
        $amount = $data['amount_received'] ?? ($data['amount'] ?? 0);

        if (!$merchantRef || !$status) {
            return response()->json(['success' => false, 'message' => 'Missing data'], 400);
        }

        DB::beginTransaction();
        try {
            // === LOGIKA BARU: TANGKAP INVOICE INDUK (PARENT INVOICE) ===
            if (Str::startsWith($merchantRef, 'INV-PAY-')) {
                Log::info('Routing callback to Process Parent Invoice', ['ref' => $merchantRef]);

                // Jika statusnya LUNAS dari Tripay
                if ($status === 'PAID') {
                    // Ambil SEMUA order anak (Fisik dan Digital) yang nyantol ke Induk ini
                    $anakOrders = Order::where('parent_invoice', $merchantRef)->get();

                    foreach ($anakOrders as $anakOrder) {
                        Log::info('Memproses Order Anak: ' . $anakOrder->invoice_number);

                        // Lempar order anak ke fungsi andalan lu buat hit KiriminAja & Auto-Email
                        $this->processOrderCallback($anakOrder->invoice_number, 'PAID', $data);
                    }
                }
                // Jika status gagal/expired
                else if (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
                    $statusGagal = ($status === 'EXPIRED') ? 'expired' : 'failed';
                    Order::where('parent_invoice', $merchantRef)->update(['status' => $statusGagal]);
                }

                DB::commit();
                return response()->json(['success' => true]);
            }

            // 1. Prioritaskan Order Baru (Format: SCK-ORD-XXXX)
            // Ini harus dicek DULUAN sebelum 'SCK-' biasa
            if (Str::startsWith($merchantRef, 'SCK-ORD-') || Str::startsWith($merchantRef, 'ORD-')) {
                Log::info('Routing callback to processOrderCallback (Marketplace)', ['ref' => $merchantRef]);
                // Panggil fungsi prosesor di controller ini (Tabel orders)
                $this->processOrderCallback($merchantRef, $status, $data);

            // 2. Baru cek Format Lama / Manual (Format: SCK-XXXX)
            } elseif (Str::startsWith($merchantRef, 'SCK-')) {
                Log::info('Routing callback to AdminPesananController (Legacy)', ['ref' => $merchantRef]);
                // Panggil controller lama (Tabel pesanan)
                AdminPesananController::processPesananCallback($merchantRef, $status, $data);

            // 3. TopUp
            } elseif (Str::startsWith($merchantRef, 'TOPUP-')) {
                Log::info('Routing callback to TopUpController', ['ref' => $merchantRef]);
                TopUpController::processTopUpCallback($merchantRef, $status, $amount, $data);

            } elseif (Str::startsWith($merchantRef, 'DANATOPUP-')) {
                Log::info('Routing callback to TopupDanaController', ['ref' => $merchantRef]);
                return app(\App\Http\Controllers\Customer\TopupDanaController::class)->handlePaymentCallback($request);

            // 4. PPOB DANA TOKOSANCAKA (Format: PPOBDANA-XXXX)
            } elseif (Str::startsWith($merchantRef, 'PPOBDANA-')) {
                Log::info('LOG LOG - Routing callback to DanaPpobDigitalGoodsController', ['ref' => $merchantRef]);

                // Update status lunas di DB
                if ($status === 'PAID') {
                    \App\Models\OrderPpobDana::where('order_id', $merchantRef)
                        ->update(['status_status' => 'PAID', 'status_message' => 'Lunas, Memproses TopUp']);

                    // TODO: Panggil fungsi topup otomatis ke vendor Anda di sini
                }

            // 5. PPOB Mobile (Format: PXXXXX) ---> INI LOGIKA LAMA ANDA
            } elseif (Str::startsWith($merchantRef, 'P')) {
                Log::info('Routing callback to PPOB Controller', ['ref' => $merchantRef]);
                \App\Http\Controllers\Api\Mobile\PpobMobileController::processPpobCallback($merchantRef, $status, $data);

            // ====================================================================
            // 6. TIKET PESAWAT (Format: FLT-{order_id}-{pnr}) ---> TAMBAHKAN INI
            // ====================================================================
            } elseif (Str::startsWith($merchantRef, 'FLT-')) {
                Log::info('Routing callback to TicketingController (Tiket Pesawat)', ['ref' => $merchantRef]);

                // Pecah merchantRef "FLT-123-PNRXYZ" untuk mengambil angka 123 (order_id)
                $parts = explode('-', $merchantRef);
                $orderId = $parts[1] ?? null;

                if ($orderId && $status === 'PAID') {
                    $orderFlight = DB::table('flight_orders')->where('id', $orderId)->first();

                    // Cek order ada dan belum terlanjur Issued
                    if ($orderFlight && $orderFlight->status !== 'ISSUED') {
                        Log::info("Memulai Eksekusi Auto-Issued Pesawat untuk Order ID: {$orderId}");

                        $ticketingController = new \App\Http\Controllers\Api\Mobile\TicketingController();

                        // Buat Request buatan (Mock Request) untuk menipu Controller seolah-olah ditekan dari HP
                        $reqIssued = new \Illuminate\Http\Request();
                        $reqIssued->replace(['order_id' => $orderId]);

                        // Cari data User untuk di-inject ke dalam Request
                        $user = \App\Models\User::where('id_pengguna', $orderFlight->user_id)->first();

                        if ($user) {
                            $reqIssued->setUserResolver(function () use ($user) {
                                return $user;
                            });

                            // TEMBAK! Eksekusi pencetakan tiket ke maskapai
                            $ticketingController->airlineIssued($reqIssued);
                        } else {
                            Log::error("User tidak ditemukan untuk order penerbangan ID {$orderId}");
                        }
                    }
                }

            // ====================================================================
            // 6. SEMUA TRANSAKSI DARMAWISATA (PPOB, TOPUP, KAI, BUS, KAPAL)
            // ====================================================================
            } elseif (
                Str::startsWith($merchantRef, 'PPOBD-') ||
                Str::startsWith($merchantRef, 'TOPUPD-') ||
                Str::startsWith($merchantRef, 'KAI-') ||
                Str::startsWith($merchantRef, 'BUS-') ||
                Str::startsWith($merchantRef, 'SHP-') ||
                Str::startsWith($merchantRef, 'SHPDLU-')
            ) {
                // Hanya proses jika status dari Tripay adalah LUNAS (PAID)
                if ($status === 'PAID') {
                    // TRIK ADAPTER: Ubah format Tripay menyerupai format Webhook DOKU
                    // agar bisa menggunakan ulang fungsi handleDokuCallback yang sudah kita buat.
                    $mockDokuData = [
                        'order' => ['invoice_number' => $merchantRef],
                        'transaction' => ['status' => 'SUCCESS'] // Tripay PAID = DOKU SUCCESS
                    ];

                    if (Str::startsWith($merchantRef, 'PPOBD-')) {
                        Log::info('Routing Tripay Callback to PpobDarmawisataController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\PpobDarmawisataController())->handleDokuCallback($mockDokuData);

                    } elseif (Str::startsWith($merchantRef, 'TOPUPD-')) {
                        Log::info('Routing Tripay Callback to PpobDarmaTopupController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\PpobDarmaTopupController())->handleDokuCallback($mockDokuData);

                    } elseif (Str::startsWith($merchantRef, 'KAI-')) {
                        Log::info('Routing Tripay Callback to TrainTicketingController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\TrainTicketingController())->handleDokuCallback($mockDokuData);

                    } elseif (Str::startsWith($merchantRef, 'BUS-')) {
                        Log::info('Routing Tripay Callback to BusTicketingController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\BusTicketingController())->handleDokuCallback($mockDokuData);

                    } elseif (Str::startsWith($merchantRef, 'SHP-')) {
                        Log::info('Routing Tripay Callback to ShipTicketingController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\ShipTicketingController())->handleDokuCallback($mockDokuData);

                    } elseif (Str::startsWith($merchantRef, 'SHPDLU-')) {
                        Log::info('Routing Tripay Callback to ShipDluTicketingController', ['ref' => $merchantRef]);
                        (new \App\Http\Controllers\Api\Mobile\ShipDluTicketingController())->handleDokuCallback($mockDokuData);
                    }
                }

            } elseif (Str::startsWith($merchantRef, 'ORD-')) {
                Log::info('Routing callback to processOrderCallback (this controller)', ['ref' => $merchantRef]);
                $this->processOrderCallback($merchantRef, $status, $data);

            } elseif (Str::startsWith($merchantRef, 'CUSTP-')) {
                Log::info('Routing callback to CustomerPesananController', ['ref' => $merchantRef]);
                // CustomerPesananController::processCallback($merchantRef, $status, $data);

            } elseif (Str::startsWith($merchantRef, 'CUSTO-')) {
                Log::info('Routing callback to CustomerOrderController', ['ref' => $merchantRef]);
                // CustomerOrderController::processCallback($merchantRef, $status, $data);

            } else {
                Log::warning('CheckoutController Callback: Unrecognized merchant_ref prefix.', ['merchant_ref' => $merchantRef]);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::critical('CheckoutController Callback: CRITICAL ERROR in processing.', [
                'merchant_ref' => $merchantRef,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'data' => $data
            ]);
             return response()->json(['success' => false, 'message' => 'Internal Server Error during processing'], 500);
        }
    }


   /**
     * =========================================================================
     * HANDLER WEBHOOK DOKU (JOKUL) - FULL VERSION DENGAN HYBRID DATABASE
     * =========================================================================
     */
    public function handleDokuCallback(array $data)
    {
        // 1. Ambil data referensi & status dari payload DOKU
        $merchantRef = $data['order']['invoice_number'] ?? null;
        $status = $data['transaction']['status'] ?? null;

        // Validasi dasar jika payload tidak lengkap
        if (!$merchantRef || !$status) {
            Log::warning('DOKU Callback: Data tidak lengkap', $data);
            return response()->json(['message' => 'Invalid payload data'], 400);
        }

        Log::info('Processing DOKU Callback...', ['ref' => $merchantRef, 'status' => $status]);

        // 2. Mapping status DOKU ke status Internal
        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';

        DB::beginTransaction();
        try {
            // === LOGIKA TANGKAP TAGIHAN INDUK (PARENT INVOICE) ===
            if (\Illuminate\Support\Str::startsWith($merchantRef, 'SCK-PAY-') || \Illuminate\Support\Str::startsWith($merchantRef, 'INV-PAY-')) {
                Log::info('Routing DOKU callback to Process Parent Invoice', ['ref' => $merchantRef]);

                if ($internalStatus === 'PAID') {
                    // HYBRID SEARCH: Cari anak order di database utama (mysql)
                    $anakOrders = \App\Models\Order::on('mysql')->where('parent_invoice', $merchantRef)->get();

                    // Jika tidak ketemu, cari di database kedua (mysql_second)
                    if ($anakOrders->isEmpty()) {
                        $anakOrders = \App\Models\Order::on('mysql_second')->where('parent_invoice', $merchantRef)->get();
                    }

                    if ($anakOrders->isEmpty()) {
                        Log::warning("DOKU Callback: Order anak untuk Parent Invoice {$merchantRef} tidak ditemukan di database mana pun.");
                    }

                    // Proses satu per satu anak order yang ditemukan
                    foreach ($anakOrders as $anak) {
                        Log::info("Memproses Order Anak: {$anak->invoice_number} dari Parent: {$merchantRef}");
                        $this->processOrderCallback($anak->invoice_number, 'PAID', $data);
                    }
                } else {
                    // Update status gagal di KEDUA database sekaligus untuk memastikan data sinkron
                    \App\Models\Order::on('mysql')->where('parent_invoice', $merchantRef)->update(['status' => 'failed']);
                    \App\Models\Order::on('mysql_second')->where('parent_invoice', $merchantRef)->update(['status' => 'failed']);
                }

                DB::commit();
                return response()->json(['message' => 'Webhook parent invoice processed successfully.'], 200);
            }

            // === LOGIKA ROUTING UNTUK INVOICE TUNGGAL LAINNYA ===
            if (\Illuminate\Support\Str::startsWith($merchantRef, 'TOPUP-')) {
                Log::info('Routing DOKU callback to TopUpController', ['ref' => $merchantRef]);
                \App\Http\Controllers\Customer\TopUpController::processTopUpCallback($merchantRef, $internalStatus, $data['order']['amount'] ?? 0, $data);

            } elseif (\Illuminate\Support\Str::startsWith($merchantRef, 'DANATOPUP-')) {
                Log::info('Routing DOKU callback to TopupDanaController', ['ref' => $merchantRef]);
                app(\App\Http\Controllers\Customer\TopupDanaController::class)->handleDokuCallback($data);

            } elseif (\Illuminate\Support\Str::startsWith($merchantRef, 'ORD-') || \Illuminate\Support\Str::startsWith($merchantRef, 'SCK-ORD-') || \Illuminate\Support\Str::startsWith($merchantRef, 'SCK-')) {
                Log::info('Routing DOKU callback to processOrderCallback (Single)', ['ref' => $merchantRef]);
                // Langsung lempar ke fungsi prosesor utama
                $this->processOrderCallback($merchantRef, $internalStatus, $data);

            } else {
                Log::warning('DOKU Callback: Unrecognized merchant_ref prefix.', ['merchant_ref' => $merchantRef]);
            }

            DB::commit();
            return response()->json(['message' => 'Webhook processed successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DOKU Callback Exception", [
                'ref' => $merchantRef,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }

    public function processOrderCallback($merchantRef, $status, $callbackData)
    {
        Log::info('Processing Order Callback (ORD-/SCK-)...', ['ref' => $merchantRef, 'status' => $status]);
        $fonnteService = app(FonnteService::class);
        $cleanRef = trim($merchantRef);

        // ====================================================================
        // 1. PENCARIAN CERDAS (HANYA MYSQL UTAMA)
        // mysql_second dihapus karena sudah dieksekusi tuntas di DokuWebhookController
        // ====================================================================
        $isLegacy = false;
        $order = null;
        $dbConnection = 'mysql';

        // A. Coba cari di model Order (Tabel Baru) - Cari dari Invoice ATAU Parent Invoice
        $order = Order::on('mysql')->with('items.product.store.user', 'items.variant', 'user')
                    ->where('invoice_number', $cleanRef)
                    ->orWhere('parent_invoice', $cleanRef) // <--- INI KUNCI PENYELAMATNYA
                    ->first();

        if ($order) {
            \Illuminate\Support\Facades\Log::info("LOG LOG - ➡️ Order {$cleanRef} terdeteksi di database (mysql) tabel Orders.");
        }

        // B. Coba cari di model Pesanan (Tabel Lama / Legacy)
        if (!$order && class_exists(\App\Models\Pesanan::class)) {
            $order = \App\Models\Pesanan::on('mysql')
                        ->where('nomor_invoice', $cleanRef)
                        ->first();

            if ($order) {
                $isLegacy = true;
                \Illuminate\Support\Facades\Log::info("LOG LOG - ➡️ Order {$cleanRef} terdeteksi di database (mysql) tabel Pesanan.");
            }
        }

        // Jika sudah dicari di koneksi utama tapi tetap kosong
        if (!$order) {
            \Illuminate\Support\Facades\Log::error("LOG LOG - ❌ FATAL: Webhook Gagal! Order {$cleanRef} tidak ditemukan di mysql!");
            return;
        }

        // ====================================================================
        // 2. KUNCI KONEKSI (SANGAT KRUSIAL)
        // ====================================================================
        $order->setConnection($dbConnection);

        // -----------------------------------------------------------
        // 3. VALIDASI STATUS
        // -----------------------------------------------------------
        $statusBoleh = ['pending', 'menunggu pembayaran', 'unpaid', 'menunggu_pembayaran', 'paid', 'processing'];

        if (!in_array(strtolower($order->status), $statusBoleh)) {
            Log::warning("Callback Ditolak! Status order saat ini adalah: " . $order->status);
            return; // Order sudah diproses sebelumnya
        }

        // -----------------------------------------------------------
        // 4. PROSES UTAMA (LUNAS)
        // -----------------------------------------------------------
        if ($status === 'PAID' || $status === 'SUCCESS') {

            // A. Update Status Database
            $order->status = 'paid';
            $order->save();
            \Illuminate\Support\Facades\Log::info("LOG LOG - ✅ Status pesanan {$cleanRef} berhasil diupdate menjadi PAID di database {$dbConnection}.");

            // ==========================================================
            // 🔥 TAMBAHAN BARU: AUTO SYNC SALDO TOKO (REALTIME) 🔥
            // ==========================================================
            try {
                $store = $order->store;
                // Cek apakah order ini menggunakan DOKU dan Toko punya SAC ID
                if ($store && !empty($store->doku_sac_id)) {
                    Log::info("Webhook: Mencoba sync saldo terbaru untuk Toko ID: {$store->id} (SAC: {$store->doku_sac_id})");

                    // Panggil Service DOKU
                    $dokuService = new \App\Services\DokuJokulService(); // Pastikan Service SAC diload
                    $balance = $dokuService->getBalance($store->doku_sac_id);

                    if ($balance['success'] ?? false) {
                        $store->doku_balance_available = $balance['data']['balance']['available'] ?? 0;
                        $store->doku_balance_pending = $balance['data']['balance']['pending'] ?? 0;
                        $store->doku_balance_last_updated = now();
                        $store->save();
                        Log::info("Webhook: Saldo toko berhasil diupdate. Available: {$store->doku_balance_available}");
                    }
                }
            } catch (Exception $e) {
                Log::warning("Webhook: Gagal auto-sync saldo toko. User perlu refresh manual. Error: " . $e->getMessage());
            }
            // ==========================================================

            // B. Proses Logika Pengiriman (KiriminAja)
            try {
                $kiriminAja = app(KiriminAjaService::class);

                // --- SMART PARSER (Format Lama & Baru) ---
                $rawShipping = !empty($order->expedition) ? $order->expedition : $order->shipping_method;
                $parts = explode('-', $rawShipping ?? '');

                $type = 'regular'; $courier = 'jne'; $service = 'REG'; // Default

                if (count($parts) >= 3) {
                    if ($parts[0] === 'mix') { // Format Legacy: mix-jtcargo-REG...
                        $courier = $parts[1]; $service = $parts[2];
                        $type = (str_contains(strtolower($courier), 'cargo') || str_contains(strtolower($service), 'trc')) ? 'cargo' : 'regular';
                    } else { // Format Baru
                        $type = $parts[0]; $courier = $parts[1]; $service = $parts[2];
                    }
                } elseif (count($parts) == 2) {
                    $courier = $parts[0]; $service = $parts[1];
                } else {
                    Log::warning("Format pengiriman tidak valid, skip booking.", ['raw' => $rawShipping]);
                    goto skip_kiriminaja;
                }

                //$validTypes = ['regular', 'cargo', 'instant', 'trucking'];
                //if (!in_array($type, $validTypes)) $type = 'regular';
                //$service = strtoupper(trim($service));

                //$validTypes = ['regular', 'cargo', 'instant', 'trucking', 'digital_delivery'];
                //if (!in_array($type, $validTypes)) $type = 'regular';
                //$service = trim($service); // Biarkan huruf besar/kecilnya asli bawaan dari database

                // Tambahkan 'sancaka_local' ke dalam array $validTypes
                $validTypes = ['regular', 'cargo', 'instant', 'trucking', 'digital_delivery', 'sancaka_local'];
                if (!in_array($type, $validTypes)) $type = 'regular';
                $service = trim($service);

                // ========================================================
                // BYPASS BOOKING KURIR & EKSEKUSI AUTO-DELIVERY DIGITAL
                // ========================================================
                if ($type === 'digital_delivery') {
                    Log::info("Pesanan {$order->invoice_number} adalah produk digital. Mengeksekusi pengecekan Auto-Delivery.");

                    // 1. Cari File / URL dari database produk yang dibeli
                    $digitalAccess = null;
                    $rincianProduk = '';

                    foreach($order->items as $item) {
                        $namaProduk = $item->product ? $item->product->name : 'Produk Digital';
                        if ($item->variant) {
                            $namaProduk .= ' (' . str_replace(';', ', ', $item->variant->combination_string) . ')';
                        }
                        $rincianProduk .= "<li>{$namaProduk} <b>(x{$item->quantity})</b></li>";

                        // Cek apakah ada aset digital yang sudah disiapkan oleh Penjual
                        if (!empty($item->product->digital_url)) {
                            $digitalAccess = $item->product->digital_url;
                        } elseif (!empty($item->product->digital_file_path)) {
                            $digitalAccess = asset('public/storage/' . $item->product->digital_file_path);
                        } elseif (!empty($item->product->digital_sn_list)) {
                            // Ambil 1 SN, lalu update sisa SN di database
                            $snArray = array_map('trim', explode(',', $item->product->digital_sn_list));
                            if (count($snArray) > 0 && !empty($snArray[0])) {
                                $digitalAccess = array_shift($snArray);
                                $item->product->digital_sn_list = implode(', ', $snArray);
                                $item->product->save();
                            }
                        }
                    }

                    $customer = $order->user;
                    $tujuanEmail = $customer->email ?? null;

                    // 2. LOGIKA CERDAS: AUTO VS MANUAL
                    if (!empty($digitalAccess)) {
                        // [SKENARIO A] Penjual sudah siap barang -> KIRIM OTOMATIS & CAIRKAN DANA

                        $order->shipping_reference = $digitalAccess;
                        $order->status = 'completed'; // Selesai otomatis! Dana siap cair ke penjual.
                        $order->save();

                        Log::info("Auto-Delivery berhasil untuk {$order->invoice_number}. Status => COMPLETED.");

                        // Kirim Email E-Ticket/File ke Pembeli
                        if ($tujuanEmail) {
                            $emailData = [
                                'to' => $tujuanEmail,
                                'subject' => 'Akses Produk Digital Anda: ' . $order->invoice_number,
                                'body' => "
                                    <div style='font-family: Arial, sans-serif; color: #333;'>
                                        <h2>Terima Kasih Telah Berbelanja! 🎉</h2>
                                        <p>Halo <b>{$customer->nama_lengkap}</b>,</p>
                                        <p>Pembayaran pesanan <b>{$order->invoice_number}</b> telah berhasil. Berikut adalah produk digital Anda:</p>
                                        <ul>{$rincianProduk}</ul>
                                        <div style='margin: 20px 0; padding: 15px; background-color: #f3f4f6; border-left: 4px solid #4f46e5;'>
                                            <p style='margin: 0; font-size: 12px; color: #6b7280;'>Akses / Serial Number / Link Download:</p>
                                            <p style='margin: 5px 0 0 0; font-weight: bold; font-size: 16px; word-break: break-all;'>
                                                <a href='{$digitalAccess}' target='_blank' style='color: #4f46e5;'>{$digitalAccess}</a>
                                            </p>
                                        </div>
                                        <p>Silakan simpan email ini sebagai bukti. Salam hangat,<br><b>Tim Sancaka Express</b></p>
                                    </div>
                                "
                            ];

                            try {
                                $emailController = app(\App\Http\Controllers\Admin\EmailController::class);
                                $emailRequest = new \Illuminate\Http\Request();
                                $emailRequest->replace($emailData);
                                $emailController->send($emailRequest);
                                Log::info("E-Ticket otomatis dikirim ke: " . $tujuanEmail);
                            } catch (\Exception $e) {
                                Log::error("Gagal mengirim E-Ticket via EmailController: " . $e->getMessage());
                            }
                        }
                    } else {
                        // [SKENARIO B] Penjual belum upload barang -> TAHAN DANA & MINTA UPLOAD MANUAL

                        $order->shipping_reference = 'Menunggu Penjual';
                        $order->status = 'processing'; // Dana tertahan karena status belum 'completed'
                        $order->save();

                        Log::info("Aset digital kosong untuk {$order->invoice_number}. Menunggu penjual upload manual. Status => PROCESSING.");

                        // Optional: Beritahu pembeli bahwa barang sedang disiapkan penjual
                        if ($tujuanEmail) {
                            $emailData = [
                                'to' => $tujuanEmail,
                                'subject' => 'Pembayaran Berhasil: ' . $order->invoice_number,
                                'body' => "
                                    <div style='font-family: Arial, sans-serif; color: #333;'>
                                        <h2>Pembayaran Berhasil! 🎉</h2>
                                        <p>Halo <b>{$customer->nama_lengkap}</b>,</p>
                                        <p>Pembayaran pesanan <b>{$order->invoice_number}</b> telah kami terima. Saat ini penjual sedang menyiapkan file/akses produk Anda.</p>
                                        <ul>{$rincianProduk}</ul>
                                        <p>Anda akan menerima notifikasi email beserta link akses setelah penjual mengirimkannya.</p>
                                        <p>Salam hangat,<br><b>Tim Sancaka Express</b></p>
                                    </div>
                                "
                            ];
                            try {
                                $emailController = app(\App\Http\Controllers\Admin\EmailController::class);
                                $emailRequest = new \Illuminate\Http\Request();
                                $emailRequest->replace($emailData);
                                $emailController->send($emailRequest);
                            } catch (\Exception $e) {}
                        }
                    }

                    // Langsung lompat ke bawah (skip_kiriminaja) untuk kirim WA "Lunas" umum
                    goto skip_kiriminaja;
                }

                // ========================================================
                // 🔥 TAMBAHAN 3: LOMPATI KIRIMINAJA UNTUK KURIR LOKAL 🔥
                // ========================================================
                elseif ($type === 'sancaka_local') {
                    Log::info("Pesanan {$order->invoice_number} adalah Food Delivery. Bypass KiriminAja.");

                    // Ubah referensi pengiriman jadi ID Resi Lokal
                    $order->shipping_reference = 'LOKAL-' . strtoupper(Str::random(6));

                    // TODO NANTI: Di sinilah tempat Anda akan menembak broadcast(new OrderMasukKeDriver());
                    // $order->status = 'mencari_driver';

                    $order->save();

                    // Lompat ke bawah agar tidak mengeksekusi script API KiriminAja
                    goto skip_kiriminaja;
                }
                // ========================================================

                // --- BUILD PAYLOAD BOOKING ---
                $payload = [];
                $kiriminResponse = null;

                // ========================================================
                // LOGIKA PENENTUAN JAM PICKUP KIRIMINAJA
                // ========================================================
                $now = \Carbon\Carbon::now('Asia/Jakarta');

                if ($now->hour >= 17) {
                    // Jika pesanan masuk jam 17:00 (5 Sore) ke atas, jadwalkan besok jam 09:00 Pagi
                    $finalSchedule = $now->copy()->addDay()->format('Y-m-d 09:00:00');
                } else {
                    // Jika pesanan masuk sebelum jam 5 sore, jadwalkan hari ini (+1 jam dari sekarang untuk persiapan toko)
                    $finalSchedule = $now->copy()->addHour()->format('Y-m-d H:i:s');
                }
                // ========================================================

                // ========================================================
                // SKENARIO 1: DATA LAMA (TABEL PESANAN)
                // ========================================================
                if ($isLegacy) {

                    if (!$order->sender_district_id || !$order->receiver_district_id) {
                        Log::warning("Data wilayah tidak lengkap.", ['id' => $order->id_pesanan]);
                        goto skip_kiriminaja;
                    }

                    $estimasiDimensi = ($order->weight > 10000) ? 40 : 10;

                    $packageData = [
                        'order_id' => $order->nomor_invoice,
                        'destination_name' => $order->nama_pembeli ?? $order->receiver_name,
                        'destination_phone' => $order->telepon_pembeli ?? $order->receiver_phone,
                        'destination_address' => $order->alamat_pengiriman ?? $order->receiver_address,
                        'destination_kecamatan_id' => $order->receiver_district_id,
                        'destination_kelurahan_id' => $order->receiver_subdistrict_id,
                        'weight' => (int) $order->weight,
                        'width' => (int) ($order->width ?? $estimasiDimensi),
                        'height' => (int) ($order->height ?? $estimasiDimensi),
                        'length' => (int) ($order->length ?? $estimasiDimensi),
                        'item_value' => (int) ($order->total_harga_barang ?? 1000),
                        'item_name' => $order->item_description ?? 'Paket',
                        'package_type_id' => (int) ($order->item_type ?? 1),
                        'service' => $courier,
                        'service_type' => $service,
                        'shipping_cost' => (int) $order->shipping_cost,
                        'cod' => 0
                    ];

                    $payload = [
                        'kecamatan_id' => $order->sender_district_id,
                        'kelurahan_id' => $order->sender_subdistrict_id,
                        'address' => $order->sender_address,
                        'phone' => $order->sender_phone,
                        'name' => $order->sender_name,
                        'zipcode' => $order->sender_postal_code ?? '',
                        'latitude' => $order->sender_lat ?? 0,
                        'longitude' => $order->sender_lng ?? 0,
                        'packages' => [$packageData],
                        'category' => ($type == 'cargo') ? 'trucking' : 'regular',
                        'schedule' => $finalSchedule,
                        'platform_name' => 'TOKOSANCAKA.COM'
                    ];

                    // 1. PERCOBAAN PERTAMA: Set Jadwal HARI INI (Sekarang)
                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);

					Log::debug('🧐 [DEBUG] RAW RESPONSE KIRIMINAJA:', [
					    'invoice'   => $order->invoice_number,
					    'tipe_data' => gettype($kiriminResponse),
					    'response'  => $kiriminResponse
					]);

                    // 2. AUTO RETRY JIKA GAGAL JADWAL (Coba Besok)
                    if (isset($kiriminResponse['status']) && $kiriminResponse['status'] === false) {
                        $pesanError = strtolower($kiriminResponse['text'] ?? '');
                        if (str_contains($pesanError, 'jadwal') || str_contains($pesanError, 'schedule')) {
                            Log::info("Booking hari ini gagal ({$pesanError}). Mencoba booking ulang untuk BESOK PAGI...");
                            $payload['schedule'] = \Carbon\Carbon::now()->addDay()->format('Y-m-d 09:00:00');
                            $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                        }
                    }
                }

                // ========================================================
                // SKENARIO 2: DATA BARU (TABEL ORDERS)
                // ========================================================
                else {
                    $store = $order->store;
                    $user = $order->user;
                    if (!$store || !$user) goto skip_kiriminaja;

                    // 🔥 PERBAIKAN 1: Baca langsung ID Kecamatan dari Database!
                    $originDistId = $order->sender_district_id ?? 4354;
                    $originSubId  = $order->sender_subdistrict_id ?? 40343;
                    $destDistId   = $order->receiver_district_id;
                    $destSubId    = $order->receiver_subdistrict_id;

                    $packagesPayload = [];
                    $totalWeight = 0;

                    foreach($order->items as $item) {
                        // 1. Cek apakah ini produk digital / E-ticket
                        $katObj = $item->product ? $item->product->category()->first() : null;
                        $catGroup = $katObj ? strtolower($katObj->category_group ?? '') : '';

                        $isItemDigital = ($item->product && $item->product->is_digital) ||
                                        in_array($catGroup, ['produk_digital', 'jasa']) ||
                                        str_contains(strtolower($item->type ?? ''), 'digital');

                        if ($isItemDigital) {
                            // Amankan link download
                            $digitalAccess = $item->product->digital_url ??
                                            ($item->product->digital_file_path ? asset('public/storage/' . $item->product->digital_file_path) : null);
                            $item->download_link = $digitalAccess;
                            $item->save();

                            continue; // LOMPATI BARANG INI AGAR TIDAK DIKIRIM KE KIRIMINAJA
                        }

                        // 2. Masukkan ke payload fisik KiriminAja
                        $w = $item->product->weight ?? 1000;
                        $jenisBarang = $item->product->jenis_barang ?? 1;
                        $totalWeight += ($w * $item->quantity);

                        $packagesPayload[] = [
                            'order_id'                 => $order->invoice_number,
                            'destination_name'         => $user->nama_lengkap,
                            'destination_phone'        => $user->no_wa,
                            'destination_address'      => $order->shipping_address,
                            'destination_kecamatan_id' => $destDistId,
                            'destination_kelurahan_id' => $destSubId,
                            'weight'                   => $w * $item->quantity,
                            'width'                    => 10, 'height' => 10, 'length' => 10,
                            'item_value'               => $item->price * $item->quantity,
                            'item_name'                => $item->product->name,
                            'service'                  => $courier,
                            'service_type'             => $service,
                            'shipping_cost'            => (int) $order->shipping_cost,
                            'package_type_id'          => (int) $jenisBarang,
                            'cod'                      => 0,
                            'insurance_amount'         => ($order->insurance_cost > 0) ? ($item->price * $item->quantity) : 0,
                        ];
                    }

                    // 3. PENCEGAHAN CRASH JIKA ISINYA DIGITAL SEMUA
                    if (empty($packagesPayload)) {
                        goto skip_kiriminaja;
                    }

                    // 4. CEK WILAYAH HANYA JIKA ADA BARANG FISIK
                    if (!$destDistId || !$destSubId) {
                        Log::error("Wilayah tujuan kosong! Tidak bisa booking KiriminAja.");
                        goto skip_kiriminaja;
                    }

                    $isCargo = ($type === 'cargo' || str_contains(strtolower($service), 'gokil') || str_contains(strtolower($service), 'trc'));

                    $finalBookingWeight = $totalWeight;
                    if ($finalBookingWeight < 1000) {
                        $finalBookingWeight = 1000; // Minimal 1kg untuk reguler
                    }
                    if ($isCargo && $finalBookingWeight < 10000) {
                        $finalBookingWeight = 10000; // Minimal 10kg untuk Kargo
                    }


                    if ($type === 'instant') {
                        $payload = [
                            'service' => $courier, 'service_type' => $service, 'vehicle' => 'motor',
                            'order_prefix' => $order->invoice_number,
                            'packages' => [[
                                'origin_lat' => $store->latitude, 'origin_long' => $store->longitude,
                                'origin_name' => $store->name, 'origin_phone' => $store->user->no_wa,
                                'origin_address' => $store->address_detail,
                                'destination_lat' => $user->latitude, 'destination_long' => $user->longitude,
                                'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                                'destination_address' => $order->shipping_address,
                                'item' => ['name' => 'Pesanan '.$order->invoice_number, 'price' => $order->subtotal, 'weight' => $finalBookingWeight,],
                                'shipping_price' => (int) $order->shipping_cost
                            ]]
                        ];
                        $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                    } else {
                        $payload = [
                            'kecamatan_id' => $originDistId,
                            'kelurahan_id' => $originSubId,
                            'address' => $store->address_detail,
                            'phone' => $store->user->no_wa,
                            'name' => $store->name,
                            'zipcode' => $store->postal_code ?? '00000',
                            'latitude' => $store->latitude ?? 0,
                            'longitude' => $store->longitude ?? 0,
                            'packages' => $packagesPayload,
                            'category' => ($type == 'cargo') ? 'trucking' : 'regular',
                            'schedule' => $finalSchedule,
                            'platform_name' => 'TOKOSANCAKA.COM'
                        ];
                        $kiriminResponse = $kiriminAja->createExpressOrder($payload);

						Log::debug('🧐 [DEBUG] RAW RESPONSE KIRIMINAJA:', [
						    'invoice'   => $order->invoice_number,
						    'tipe_data' => gettype($kiriminResponse),
						    'response'  => $kiriminResponse
						]);

                        if (isset($kiriminResponse['status']) && $kiriminResponse['status'] === false) {
                            $pesanError = strtolower($kiriminResponse['text'] ?? '');
                            if (str_contains($pesanError, 'jadwal') || str_contains($pesanError, 'schedule')) {
                                $payload['schedule'] = \Carbon\Carbon::now()->addDay()->format('Y-m-d 09:00:00');
                                $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                            }
                        }
                    }
                }

                Log::info('KiriminAja Response:', ['res' => $kiriminResponse]);

                // --- CEK STATUS BOOKING ---
               // --- CEK STATUS BOOKING (SESUAI DOKUMENTASI KIRIMINAJA) ---
				if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {

				    // ✅ AMBIL PICKUP_NUMBER SEBAGAI BOOKING REFERENCE UTAMA
				    $bookingId = $kiriminResponse['pickup_number'] ??
				                 ($kiriminResponse['details'][0]['kj_order_id'] ??
				                 ($kiriminResponse['details'][0]['awb'] ?? null));

				    if ($bookingId) {
				        $order->shipping_reference = $bookingId;
				        if ($isLegacy) $order->resi = $bookingId;
				        $order->status = 'processing';
				        $order->save();
				        Log::info("Booking Sukses! Reference/Pickup Number: {$bookingId}");
				    }
				} else {
                    // GAGAL -> NOTIF CUSTOMER
                    $failReason = $kiriminResponse['text'] ?? 'Sedang diproses manual.';
                    Log::error("Booking Gagal (Status tetap PAID). Alasan: " . $failReason);

                    try {
                        $customer = $order->user;
                        if (!$customer) {
                            $userId = $order->user_id ?? ($order->id_pengguna_pembeli ?? null);
                            if ($userId) $customer = \App\Models\User::find($userId);
                        }

                        if ($customer) {
                            $pesanCustomer = "Pembayaran diterima! Pengiriman sedang diproses manual oleh Admin karena: {$failReason}. Mohon ditunggu.";
                            $customer->notify(new \App\Notifications\NotifikasiUmum([
                                'tipe' => 'Info Pesanan', 'judul' => "Pembayaran Berhasil",
                                'pesan_utama' => $pesanCustomer,
                                'url' => route('customer.pesanan.index'), 'icon' => 'fas fa-box-open'
                            ]));
                            Log::info("Notifikasi kegagalan booking terkirim ke Customer.");
                        }
                    } catch (\Exception $e) {
                        Log::error("Gagal mengirim notif ke Customer: " . $e->getMessage());
                    }
                }

            } catch (\Exception $e) {
                Log::error("KiriminAja Error (Status tetap PAID): " . $e->getMessage());
            }

            skip_kiriminaja:

            // 3. KIRIM NOTIF WA
            $this->kirimNotifikasiPesananLengkap($order, 'Lunas');
            $this->_sendExpoPushNotification($order);

          // ==========================================================
            // 🔥 TAMBAHAN EMAIL KE USER (SEMUA GATEWAY & SALDO MASUK KESINI)
            // ==========================================================
            try {
                if ($order->user && !empty($order->user->email)) {

                    // 1. Kumpulkan aset produk digital (URL, File Download, atau Gambar)
                    $rincianDigitalHtml = "";
                    $adaProdukDigital = false;

                    foreach ($order->items as $item) {
                        $katObj = $item->product ? $item->product->category()->first() : null;
                        $catGroup = $katObj ? strtolower($katObj->category_group ?? '') : '';
                        $isItemDigital = ($item->product && $item->product->is_digital) ||
                                         in_array($catGroup, ['produk_digital', 'jasa']) ||
                                         str_contains(strtolower($item->type ?? ''), 'digital');

                        if ($isItemDigital && $item->product) {
                            $adaProdukDigital = true;
                            $namaProduk = $item->product->name;
                            $aksesLink = "";

                            // Prioritas penarikan aset: URL Eksternal -> Path File Upload -> Link Backup -> Gambar Produk
                            if (!empty($item->product->digital_url)) {
                                $aksesLink = $item->product->digital_url;
                            } elseif (!empty($item->product->digital_file_path)) {
                                $aksesLink = asset('public/storage/' . $item->product->digital_file_path);
                            } elseif (!empty($item->download_link)) {
                                $aksesLink = $item->download_link;
                            } elseif (!empty($item->product->image)) {
                                $aksesLink = asset('public/storage/' . $item->product->image);
                            }

                            // Rakit HTML List
                            if (!empty($aksesLink)) {
                                $rincianDigitalHtml .= "<li style='margin-bottom: 15px;'>
                                    <strong style='font-size: 16px;'>{$namaProduk}</strong> (x{$item->quantity})<br>
                                    <a href='{$aksesLink}' target='_blank' style='display: inline-block; margin-top: 5px; padding: 8px 15px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 14px;'>📥 Unduh / Akses Produk</a>
                                </li>";
                            } else {
                                $rincianDigitalHtml .= "<li style='margin-bottom: 15px;'>
                                    <strong style='font-size: 16px;'>{$namaProduk}</strong> (x{$item->quantity})<br>
                                    <i style='color: #6b7280; font-size: 14px;'>Akses file/link sedang disiapkan oleh penjual. Anda akan dihubungi lebih lanjut.</i>
                                </li>";
                            }
                        }
                    }

                    // 2. Eksekusi Pengiriman Email Berdasarkan Tipe Produk
                    if ($adaProdukDigital && !empty($rincianDigitalHtml)) {
                        // Jika ADA produk digital: Gunakan EmailController untuk mengirim custom HTML berisi link
                        $emailData = [
                            'to' => $order->user->email,
                            'subject' => 'Akses Produk Digital Anda (LUNAS): ' . $order->invoice_number,
                            'body' => "
                                <div style='font-family: Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto;'>
                                    <h2 style='color: #4f46e5; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;'>Pembayaran Berhasil! 🎉</h2>
                                    <p>Halo <b>{$order->user->nama_lengkap}</b>,</p>
                                    <p>Terima kasih! Pembayaran untuk pesanan <b>{$order->invoice_number}</b> senilai <b>Rp " . number_format($order->total_amount, 0, ',', '.') . "</b> telah berhasil dikonfirmasi.</p>
                                    <p>Berikut adalah akses eksklusif ke produk digital yang Anda beli:</p>

                                    <ul style='background-color: #f9fafb; padding: 20px 20px 20px 40px; border-radius: 6px; border-left: 5px solid #4f46e5; list-style-type: none;'>
                                        {$rincianDigitalHtml}
                                    </ul>

                                    <p style='margin-top: 20px; font-size: 13px; color: #6b7280;'>Simpan email ini sebagai bukti transaksi. Jika Anda mengalami kendala saat mengakses file atau URL di atas, silakan hubungi penjual terkait di platform Sancaka.</p>
                                    <p>Salam hangat,<br><b>Tim Sancaka Express</b></p>
                                </div>
                            "
                        ];

                        $emailController = app(\App\Http\Controllers\Admin\EmailController::class);
                        $emailRequest = new \Illuminate\Http\Request();
                        $emailRequest->replace($emailData);
                        $emailController->send($emailRequest);

                        \Illuminate\Support\Facades\Log::info("Email link/file produk digital terkirim ke: " . $order->user->email);

                    } else {
                        // Jika TIDAK ADA produk digital (Pesanan Fisik), gunakan Trait bawaan
                        $this->sendTransactionSuccessEmail(
                            $order->user->email,
                            $order->user->nama_lengkap,
                            $order->invoice_number,
                            'Pesanan Marketplace Sancaka',
                            $order->total_amount
                        );
                    }
                }
            } catch (\Exception $e) {
                // Kalau email gagal/lemot, database order tetap aman tersimpan
                \Illuminate\Support\Facades\Log::error("Gagal kirim email lunas: " . $e->getMessage());
            }
            // ==========================================================

        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
            $order->status = ($status === 'EXPIRED') ? 'expired' : 'failed';
            $order->save();
        }
    }

private function kirimNotifikasiPesananLengkap($order, string $tipeNotifikasi)
    {
        // Pastikan app(\App\Services\FonnteService::class) di-resolve dengan benar
        $fonnteService = app(\App\Services\FonnteService::class);

        try {
            // Eager load semua relasi
            // Cek dulu apakah ini model Order (baru) atau Pesanan (lama) agar tidak error
            if (get_class($order) === 'App\Models\Order') {
                $order->loadMissing('user', 'store.user', 'items.product', 'items.variant');
            }

            // 1. Dapatkan semua penerima & data dasar
            $customer = $order->user;
            $sellerUser = $order->store ? $order->store->user : null;
            $admins = User::where('role', 'admin')->get();
            $nomorAdminKhusus = '6285745808809';

            // 2. Rakit daftar produk (Support Order & Pesanan)
            if (get_class($order) === 'App\Models\Order') {
                $produkList = $order->items->map(function ($item) {
                    $namaProduk = $item->product ? $item->product->name : 'Produk Dihapus';
                    if ($item->variant) {
                        $namaProduk .= ' (' . str_replace(';', ', ', $item->variant->combination_string) . ')';
                    }
                    return $namaProduk . ' x ' . $item->quantity;
                })->implode('; ');
            } else {
                // Fallback untuk data legacy (Pesanan)
                $produkList = $order->item_description ?? 'Paket';
            }

            // 3. OLAH VARIABEL UNTUK PESAN WHATSAPP
            $invoiceNumber = $order->invoice_number ?? $order->nomor_invoice;
            $totalAmount = $order->total_amount ?? $order->total_harga_barang;
            $totalAmountFormatted = number_format($totalAmount, 0, ',', '.');
            $shippingAddress = $order->shipping_address ?? $order->alamat_pengiriman;

            $statusTeks = ($tipeNotifikasi === 'Lunas') ? 'LUNAS (Siap Diproses)' : 'BARU DIBUAT (Menunggu Bayar)';
            $judulPesanan = ($tipeNotifikasi === 'Lunas') ? 'PESANAN LUNAS' : 'PESANAN BARU';

            // --- PERBAIKAN LOGIKA NAMA METODE BAYAR ---
            $rawMethod = strtoupper(trim($order->payment_method));

            // Mapping nama metode bayar sesuai request
            if ($rawMethod === 'POTONG SALDO') {
                $paymentDisplay = 'CASH / SALDO';
            } elseif ($rawMethod === 'DOKU_JOKUL') {
                $paymentDisplay = 'DOMPET SANCAKA';
            } elseif ($rawMethod === 'COD') {
                $paymentDisplay = 'COD ONGKIR';
            } elseif ($rawMethod === 'CODBARANG') {
                $paymentDisplay = 'COD BARANG';
            } else {
                $paymentDisplay = $rawMethod; // Default (misal: TRANSFER, OVO, dll)
            }
            // -------------------------------------------

            // Data Penjual & Pembeli
            $sellerStoreName = $order->store->name ?? 'Sancaka Store';
            $sellerNoWa = $sellerUser->no_wa ?? ($order->sender_phone ?? '-');

            $customerName = $customer->nama_lengkap ?? ($order->nama_pembeli ?? 'Pelanggan');
            $customerNoWa = $customer->no_wa ?? ($order->telepon_pembeli ?? '-');

            // Susun Pesan (Gunakan variabel $paymentDisplay yang sudah diolah tadi)
            $waMessage = <<<TEXT
*🔔 {$judulPesanan} (ID: {$invoiceNumber})*

Halo! Pesanan *{$invoiceNumber}* telah {$statusTeks}.

*— Detail Order —*
- *Total Tagihan:* Rp {$totalAmountFormatted}
- *Metode Bayar:* {$paymentDisplay}
- *Status:* {$statusTeks}
- *Produk:* {$produkList}

*— Pengiriman —*
- *Penjual:* {$sellerStoreName} ({$sellerNoWa})
- *Pembeli:* {$customerName} ({$customerNoWa})
- *Alamat:* {$shippingAddress}

Hormat kami,
*Sancaka Express*
TEXT;

            // --- 4. KIRIM NOTIFIKASI INTERNAL & WHATSAPP ---

            // a. Ke CUSTOMER (Pembeli)
            if ($customer) {
                $dataNotifCustomer = [
                    'tipe' => $tipeNotifikasi, 'judul' => ($tipeNotifikasi === 'Lunas') ? 'Pembayaran Berhasil' : 'Pesanan Dibuat',
                    'pesan_utama' => 'Pesanan Anda ' . $invoiceNumber . ' telah ' . ($tipeNotifikasi === 'Lunas' ? 'lunas.' : 'dibuat. Segera bayar.'),
                    'url' => route('checkout.invoice', ['invoice' => $invoiceNumber]),
                    'icon' => 'fas fa-check-circle',
                ];
                $customer->notify(new \App\Notifications\NotifikasiUmum($dataNotifCustomer));

                if($customerNoWa && $customerNoWa !== '-') {
                     $fonnteService->sendMessage(preg_replace('/^0/', '62', $customerNoWa), $waMessage);
                }
            }

            // b. Ke SELLER (Penjual)
            if ($sellerUser) {
                $dataNotifSeller = [
                    'tipe' => $tipeNotifikasi, 'judul' => ($tipeNotifikasi === 'Lunas') ? 'Pesanan Lunas!' : 'Pesanan Baru!',
                    'pesan_utama' => "Pesanan {$invoiceNumber} dari {$customerName} telah " . ($tipeNotifikasi === 'Lunas' ? 'lunas.' : 'dibuat.'),
                    'url' => url('seller/pesanan-marketplace'),
                    'icon' => 'fas fa-money-check-alt',
                ];
                $sellerUser->notify(new \App\Notifications\NotifikasiUmum($dataNotifSeller));

                if($sellerNoWa && $sellerNoWa !== '-') {
                    $fonnteService->sendMessage(preg_replace('/^0/', '62', $sellerNoWa), $waMessage);
                }
            }

            // c. Ke ADMIN (Semua Admin + Admin Khusus)
            if ($admins->count() > 0) {
                $dataNotifAdmin = [
                    'tipe' => $tipeNotifikasi, 'judul' => ($tipeNotifikasi === 'Lunas') ? 'ORDER LUNAS (MarketPlace)!' : 'ORDER BARU (MarketPlace)!',
                    'pesan_utama' => "Pesanan {$invoiceNumber} dari {$customerName}. Total: Rp {$totalAmountFormatted}.",
                    'url' => route('admin.orders.show', $order->id ?? 0),
                    'icon' => 'fas fa-money-check-alt',
                ];
                // Kirim notifikasi database ke semua admin
                Notification::send($admins, new \App\Notifications\NotifikasiUmum($dataNotifAdmin));
            }

            // d. Nomor Khusus (085745808809)
            $fonnteService->sendMessage($nomorAdminKhusus, $waMessage);

        } catch (Exception $e) {
            // Catat error
            Log::error('Gagal mengirim Notifikasi Pesanan Lengkap: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * PRIVATE HELPER: Menangani Transaksi Tripay
     * VERSI FIX: MENGGUNAKAN DATABASE (Bukan Config/Env)
     */
    private function _createTripayTransaction($order, $methodChannel, $amount, $custName, $custEmail, $custPhone, $items)
    {
        // ==========================================================
        // 🔥 PERBAIKAN: LOGIKA SWITCHING MODE DARI DATABASE 🔥
        // ==========================================================

        // 1. Cek Mode Apa yang Aktif di Database (Sandbox / Production)
        $mode = \App\Models\Api::getValue('TRIPAY_MODE', 'global', 'sandbox');

        // 2. Siapkan variabel wadah
        $baseUrl      = '';
        $apiKey       = '';
        $privateKey   = '';
        $merchantCode = '';

        // 3. Isi Kredensial Berdasarkan Mode
        if ($mode === 'production') {
            // MODE LIVE (PRODUCTION)
            $baseUrl      = 'https://tripay.co.id/api/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'production');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'production');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'production');
        } else {
            // MODE TEST (SANDBOX)
            $baseUrl      = 'https://tripay.co.id/api-sandbox/transaction/create';
            $apiKey       = \App\Models\Api::getValue('TRIPAY_API_KEY', 'sandbox');
            $privateKey   = \App\Models\Api::getValue('TRIPAY_PRIVATE_KEY', 'sandbox');
            $merchantCode = \App\Models\Api::getValue('TRIPAY_MERCHANT_CODE', 'sandbox');
        }

        // Validasi: Pastikan data tidak kosong sebelum request
        if (empty($apiKey) || empty($privateKey) || empty($merchantCode)) {
            Log::error('TRIPAY CONFIG MISSING (Mode: ' . $mode . ') - Cek Database Tabel API');
            return ['success' => false, 'message' => 'Konfigurasi Tripay belum lengkap untuk mode ' . strtoupper($mode) . '.'];
        }

        // ==========================================================
        // 🔥 LOGIKA TRANSAKSI 🔥
        // ==========================================================

        // 4. Validasi Hitungan Total (Safety Net)
        $calculatedTotalItems = 0;
        foreach ($items as $item) {
            $calculatedTotalItems += ($item['price'] * $item['quantity']);
        }
        $amount = (int) $amount;

        // Jika ada selisih (misal karena pembulatan), ganti detail item jadi 1 baris invoice
        // Ini mencegah error "Total amount mismatch" dari Tripay
        if ($calculatedTotalItems !== $amount) {
            $items = [[
                'sku'      => 'INV-' . $order->invoice_number,
                'name'     => 'Pembayaran Invoice #' . $order->invoice_number,
                'price'    => $amount,
                'quantity' => 1
            ]];
        }

        // 5. Buat Signature
        $signature = hash_hmac('sha256', $merchantCode . $order->invoice_number . $amount, $privateKey);

        // 🔥 LOGIKA SMART ROUTING
        $returnUrl = $order->user_id
            ? route('customer.pesanan.riwayat_belanja')
            : route('guest.history_belanja', ['invoice' => $order->invoice_number]);

        // 6. Siapkan Payload
        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->invoice_number,
            'amount'         => $amount,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'customer_phone' => $custPhone,
            'order_items'    => $items,
            'return_url'     => $returnUrl, // <--- UBAH BAGIAN INI
            'expired_time'   => (time() + (24 * 60 * 60)), // Expired 24 Jam
            'signature'      => $signature
        ];

        // 7. Eksekusi Request ke Tripay
        try {
            Log::info("Mengirim Request Tripay (Mode: $mode)...", ['url' => $baseUrl]);

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                            ->timeout(30)
                            ->withoutVerifying()
                            ->post($baseUrl, $payload);

            $body = $response->json();

            // Cek sukses dari Tripay
            if ($response->successful() && ($body['success'] ?? false) === true) {
                return ['success' => true, 'data' => $body['data']];
            }

            // Jika gagal
            Log::error('Tripay API Error:', ['response' => $body]);
            return ['success' => false, 'message' => $body['message'] ?? 'Gagal membuat transaksi Tripay.'];

        } catch (\Exception $e) {
            Log::error("Tripay Connection Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Koneksi ke payment gateway bermasalah.'];
        }
    }

    public function downloadPDF($invoice)
    {
        // 1. Cari data order beserta relasinya
        $order = Order::with('items.product', 'items.variant', 'store', 'user')
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        // 2. Load View khusus PDF (sekarang cukup pakai tulisan 'Pdf::')
        $pdf = Pdf::loadView('checkout.invoice_pdf', compact('order'))
                ->setPaper('a4', 'portrait');

        // 3. Download filenya
        return $pdf->download('Invoice-' . $order->invoice_number . '.pdf');
    }

    // =========================================================================
    // FUNGSI KHUSUS UNTUK KIRIM NOTIFIKASI PUSH EXPO (MOBILE APP)
    // =========================================================================
    private function _sendExpoPushNotification($order)
    {
        try {
            $pushMessages = [];

            // Identifikasi Nomor Invoice (Support tabel Order baru & Pesanan lama)
            $invoiceNumber = $order->invoice_number ?? $order->nomor_invoice ?? 'Unknown';

            // 1. CARI PEMILIK ORDER (Customer)
            $buyerId = null;
            if (get_class($order) === 'App\Models\Order') {
                $buyerId = $order->user_id; // Dari tabel orders
            } else {
                $buyerId = $order->customer_id ?? $order->id_pengguna_pembeli ?? null; // Dari tabel Pesanan
            }

            if ($buyerId) {
                $customer = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', $buyerId)->first();
                if ($customer && !empty($customer->expo_token)) {
                    $pushMessages[] = [
                        'to' => $customer->expo_token,
                        'title' => 'Pembayaran Berhasil! 🎉',
                        'body' => "Yey! Pembayaran untuk pesanan {$invoiceNumber} telah berhasil dikonfirmasi.",
                        'sound' => 'default',
                    ];
                }
            }

            // 2. SIAPKAN PESAN UNTUK ADMIN UTAMA (ID 4)
            $admin = \Illuminate\Support\Facades\DB::table('Pengguna')->where('id_pengguna', 4)->first();
            if ($admin && !empty($admin->expo_token)) {
                $pushMessages[] = [
                    'to' => $admin->expo_token,
                    'title' => 'Dana Masuk (Tripay)! 💰',
                    'body' => "Invoice {$invoiceNumber} baru saja lunas oleh customer.",
                    'sound' => 'default',
                ];
            }

            // 3. TEMBAK SEMUA NOTIFIKASI KE EXPO SEKALIGUS
            if (!empty($pushMessages)) {
                \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', $pushMessages);

                Log::info("✅ [EXPO PUSH] Notifikasi pembayaran berhasil dikirim untuk Invoice: {$invoiceNumber}");
            }
        } catch (\Exception $e) {
            Log::error("❌ [EXPO PUSH] Gagal kirim notifikasi: " . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN MIDTRANS SNAP UNTUK MARKETPLACE
     * =========================================================================
     */
    private function createPaymentMidtransSnap(Order $order)
    {
        Log::info('LOG LOG: Generate Snap Token Midtrans Marketplace untuk ' . $order->invoice_number);
        $user = Auth::user();

        try {
            $mode = \App\Models\Api::getValue('MIDTRANS_MODE', 'global', 'sandbox');
            $serverKey = \App\Models\Api::getValue('MIDTRANS_SERVER_KEY', $mode);
            $isProduction = ($mode === 'production');

            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $customerEmail = $user->email ?? 'customer@tokosancaka.com';

            $payload = [
                'transaction_details' => [
                    'order_id'     => $order->invoice_number,
                    'gross_amount' => (int) $order->total_amount, // Nominal dari Marketplace
                ],
                'customer_details' => [
                    'first_name' => $user->nama_lengkap ?? 'Customer',
                    'email'      => $customerEmail,
                    'phone'      => $user->no_wa ?? '',
                ],
                // KEMBALI KE RIWAYAT BELANJA MARKETPLACE
                'callbacks' => [
                    'finish' => url('/customer/pesanan/riwayat-belanja')
                ]
            ];

            $response = Http::withBasicAuth($serverKey, '')->post($baseUrl, $payload);
            $result = $response->json();

            if (isset($result['redirect_url'])) {
                return $result['redirect_url']; // Hanya mengembalikan URL
            }

            Log::error('Midtrans Snap Error (Marketplace)', $result);
            throw new \Exception('Gagal mendapatkan link pembayaran Midtrans: ' . ($result['error_messages'][0] ?? 'Kesalahan dari server.'));
        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception Midtrans Snap (Marketplace): ' . $e->getMessage());
            throw new \Exception('Terjadi kesalahan sistem saat menghubungi Midtrans.');
        }
    }

   	/**
     * =========================================================================
     * PENERIMA WEBHOOK DANA UNTUK CHECKOUT MARKETPLACE
     * =========================================================================
     */
    /* public function handleDanaCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'] ?? null;
        $status = $data['transaction']['status'] ?? null;

        Log::info('LOG LOG: CheckoutController menerima Webhook DANA', ['ref' => $merchantRef, 'status' => $status]);

        if (!$merchantRef || !$status) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';

        try {

            if (Str::startsWith($merchantRef, 'INV-PAY-')) {
                Log::info('Routing DANA callback to Process Parent Invoice', ['ref' => $merchantRef]);
                if ($internalStatus === 'PAID') {
                    $anakOrders = Order::where('parent_invoice', $merchantRef)->get();
                    foreach ($anakOrders as $anak) {
                        $this->processOrderCallback($anak->invoice_number, 'PAID', $data);
                    }
                } else {
                    Order::where('parent_invoice', $merchantRef)->update(['status' => 'failed']);
                }
                return response()->json(['message' => 'Webhook DANA processed successfully.'], 200);
            }
            // Langsung teruskan ke mesin utama untuk memproses KiriminAja dsb.
            Log::info("LOG LOG: Meneruskan Webhook $merchantRef ke processOrderCallback");
            $this->processOrderCallback($merchantRef, $internalStatus, $data);

            return response()->json(['message' => 'Webhook DANA processed successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("LOG LOG: Webhook CheckoutController Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }*/

    /**
     * =========================================================================
     * PENERIMA WEBHOOK DANA UNTUK CHECKOUT MARKETPLACE
     * =========================================================================
     */
    public function handleDanaCallback(array $data)
    {
        $merchantRef = $data['order']['invoice_number'] ?? null;
        $status = $data['transaction']['status'] ?? null;

        Log::info('LOG LOG: CheckoutController menerima Webhook DANA', ['ref' => $merchantRef, 'status' => $status]);

        if (!$merchantRef || !$status) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';

        DB::beginTransaction();
        try {
            // === LOGIKA TANGKAP TAGIHAN INDUK (PARENT INVOICE) ===
            if (\Illuminate\Support\Str::startsWith($merchantRef, 'SCK-PAY-') || \Illuminate\Support\Str::startsWith($merchantRef, 'INV-PAY-')) {
                Log::info('Routing DANA callback to Process Parent Invoice', ['ref' => $merchantRef]);

                if ($internalStatus === 'PAID') {
                    // HYBRID SEARCH: Cari anak order di database utama (mysql)
                    $anakOrders = \App\Models\Order::on('mysql')->where('parent_invoice', $merchantRef)->get();

                    // Jika tidak ketemu, cari di database kedua (mysql_second)
                    if ($anakOrders->isEmpty()) {
                        $anakOrders = \App\Models\Order::on('mysql_second')->where('parent_invoice', $merchantRef)->get();
                    }

                    if ($anakOrders->isEmpty()) {
                        Log::warning("DANA Callback: Order anak untuk Parent Invoice {$merchantRef} tidak ditemukan di database mana pun.");
                    }

                    // Proses satu per satu anak order yang ditemukan
                    foreach ($anakOrders as $anak) {
                        Log::info("Memproses Order Anak DANA: {$anak->invoice_number} dari Parent: {$merchantRef}");
                        $this->processOrderCallback($anak->invoice_number, 'PAID', $data);
                    }
                } else {
                    // Update status gagal di KEDUA database sekaligus untuk memastikan data sinkron
                    \App\Models\Order::on('mysql')->where('parent_invoice', $merchantRef)->update(['status' => 'failed']);
                    \App\Models\Order::on('mysql_second')->where('parent_invoice', $merchantRef)->update(['status' => 'failed']);
                }

                DB::commit();
                return response()->json(['message' => 'Webhook DANA parent invoice processed successfully.'], 200);
            }

            // === LOGIKA ROUTING UNTUK INVOICE TUNGGAL ===
            Log::info("LOG LOG: Meneruskan Webhook DANA $merchantRef ke processOrderCallback (Single)");
            $this->processOrderCallback($merchantRef, $internalStatus, $data);

            DB::commit();
            return response()->json(['message' => 'Webhook DANA processed successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LOG LOG: Webhook DANA Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }


	/**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN DANA BINDING (AUTO-DEBIT / DIRECT DEBIT)
     * =========================================================================
     */
    /* public function createPaymentDanaBinding(Order $order, $userAccount)
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
            return redirect()->route('checkout.index')->with('error', 'Akun DANA Anda belum terhubung. Silakan hubungkan di profil terlebih dahulu.');
        }

        // 2. DATA PREPARATION
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $order->invoice_number);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo    = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$order->total_amount, 2, '.', '');
        $path         = '/rest/redirection/v1.0/debit/payment-host-to-host';

        // 3. BODY REQUEST (DANA SNAP BI B2B2C)
        $body = [
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
                        "externalUserId"   => (string) $userAccount->id_pengguna,
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

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            // 4. GENERATE TOKEN B2B & SIGNATURE
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            $signature      = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

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

            \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA BINDING] Menyiapkan Request API (Marketplace).', ['URL' => $baseUrl . $path]);

            // 5. SEND REQUEST
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA BINDING] Respon API DANA: ', [
                'HTTP_Status' => $response->status(),
                'Result'      => $result
            ]);

            // 6. HANDLE RESPONSE
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // Skenario 1: Dana mengembalikan URL (Butuh PIN / Validasi tambahan)
                if (!empty($result['webRedirectUrl'])) {
                    \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA BINDING] User perlu diarahkan ke Web DANA untuk PIN.');

                    $order->payment_url = substr($result['webRedirectUrl'], 0, 255);
                    $order->save();

                    session()->forget('cart');
                    session()->put('last_dana_ref', $order->invoice_number);

                    return redirect()->away($result['webRedirectUrl']);
                }

                // Skenario 2: Instant Success (Auto-Debit Berhasil Seketika tanpa PIN)
                \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA BINDING] Auto-Debit Berhasil seketika! Memicu API KiriminAja...');

                // MENGGUNAKAN MESIN YANG SAMA DENGAN WEBHOOK AGAR AMAN & MENGHINDARI KODE BERULANG
                // Kita panggil langsung prosesor utama seolah-olah Webhook datang duluan
                $this->processOrderCallback($order->invoice_number, 'PAID', $result);

                return redirect()->route('customer.pesanan.riwayat_belanja')
                    ->with('success', 'Pembayaran via DANA Auto-Debit Berhasil! Pesanan sedang diproses.');
            }

            // Jika Gagal
            $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
            $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan sistem.';
            \Illuminate\Support\Facades\Log::error("LOG LOG: [DANA BINDING] Gagal. Code: $errorCode | Msg: $pesanGagal");

            return redirect()->route('checkout.index')->with('error', "Pembayaran DANA Gagal [$errorCode]: $pesanGagal");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LOG LOG: [DANA BINDING] Exception: ' . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Koneksi ke DANA terputus. Silakan coba metode pembayaran lain.');
        }
    } */

    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN DANA (CUSTOM CHECKOUT - METODE BALANCE)
     * Nama fungsi dipertahankan, namun logika menggunakan Custom Checkout
     * =========================================================================
     */
    public function createPaymentDanaBinding(Order $order, $userAccount)
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

        // 2. DATA PREPARATION
        // Catatan: Endpoint Custom Checkout diakhiri dengan .htm
        $path         = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $order->invoice_number);
        $timestamp    = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $validUpTo    = \Carbon\Carbon::now('Asia/Jakarta')->addMinutes(29)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$order->total_amount, 2, '.', '');
        // 🔥 LOGIKA SMART ROUTING
        $returnUrl = $order->user_id
            ? route('customer.pesanan.riwayat_belanja')
            : route('guest.history_belanja', ['invoice' => $order->invoice_number]);

        // 3. BODY REQUEST (CUSTOM CHECKOUT - BALANCE)
        $body = [
            "partnerReferenceNo" => $cleanInvoice,
            "merchantId"         => $merchantIdConf,
            "validUpTo"          => $validUpTo,
            "amount" => [
                "value"    => $amountValue,
                "currency" => "IDR"
            ],
            "urlParams"          => [
            [
                "url"        => $returnUrl, // <--- UBAH MENJADI VARIABLE INI
                "type"       => "PAY_RETURN",
                "isDeeplink" => "N"
            ],
            [
                "url"        => url('/dana/notify'),
                "type"       => "NOTIFICATION",
                "isDeeplink" => "N"
            ]
        ],
            // Mengunci metode ke Saldo DANA
            "payOptionDetails" => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "",
                    "transAmount" => [
                        "value"    => $amountValue,
                        "currency" => "IDR"
                    ]
                ]
            ],
            "additionalInfo" => [
                "order" => [
                    "orderTitle" => substr("Checkout " . $cleanInvoice, 0, 64),
                    "scenario"   => "API" // Wajib "API" untuk Custom Checkout
                ],
                "mcc"     => "5732",
                "envInfo" => [
                    "sourcePlatform" => "IPG",
                    "terminalType"   => "SYSTEM"
                ]
            ]
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            // 4. GENERATE TOKEN B2B & SIGNATURE
            $accessTokenB2B = $this->danaSignature->getAccessToken();
            $signature      = $this->danaSignature->generateSignature('POST', $path, $jsonBody, $timestamp);

            $headers = [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $accessTokenB2B,
                // 'Authorization-Customer' Dihapus karena Custom Checkout tidak butuh token binding user
                'X-TIMESTAMP'   => $timestamp,
                'X-SIGNATURE'   => $signature,
                'ORIGIN'        => url('/'),
                'X-PARTNER-ID'  => $partnerIdConf,
                'X-EXTERNAL-ID' => (string) time() . \Illuminate\Support\Str::random(6),
                'CHANNEL-ID'    => '95221'
            ];

            \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA CUSTOM CHECKOUT] Menyiapkan Request API (Marketplace).', ['URL' => $baseUrl . $path]);
            \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA CUSTOM CHECKOUT] Payload:', $body);

            // 5. SEND REQUEST
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post($baseUrl . $path);

            $result = $response->json();

            \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA CUSTOM CHECKOUT] Respon API DANA: ', [
                'HTTP_Status' => $response->status(),
                'Result'      => $result
            ]);

            // 6. HANDLE RESPONSE (2005400 = SUCCESS)
            if (isset($result['responseCode']) && $result['responseCode'] === '2005400') {

                // Custom Checkout pasti mereturn webRedirectUrl
                if (!empty($result['webRedirectUrl'])) {
                    \Illuminate\Support\Facades\Log::info('LOG LOG: [DANA CUSTOM CHECKOUT] Berhasil! Mengarahkan user ke Web Kasir DANA.');

                    $order->payment_url = substr($result['webRedirectUrl'], 0, 255);
                    $order->save();

                    session()->forget('cart');
                    session()->put('last_dana_ref', $order->invoice_number);

                    return redirect()->away($result['webRedirectUrl']);
                }

                \Illuminate\Support\Facades\Log::error('LOG LOG: [DANA CUSTOM CHECKOUT] Transaksi menggantung. URL Kasir tidak diterbitkan.', $result);
                return redirect()->route('checkout.index')->with('error', 'Gagal: URL Pembayaran DANA tidak ditemukan.');
            }

            // Jika Gagal (Respon DANA selain 2005400)
            $errorCode  = $result['responseCode'] ?? 'UNKNOWN';
            $pesanGagal = $result['responseMessage'] ?? 'Terjadi kesalahan sistem.';
            \Illuminate\Support\Facades\Log::error("LOG LOG: [DANA CUSTOM CHECKOUT] Gagal. Code: $errorCode | Msg: $pesanGagal");

            return redirect()->route('checkout.index')->with('error', "Pembayaran DANA Gagal [$errorCode]: $pesanGagal");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LOG LOG: [DANA CUSTOM CHECKOUT] Exception: ' . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Koneksi ke DANA terputus. Silakan coba metode pembayaran lain.');
        }
    }

    /**
     * =========================================================================
     * 1. EKSEKUTOR CREATE PAYMENT PAYPAL
     * =========================================================================
     */
    private function createPaymentPayPal(Order $order)
    {
        Log::info('LOG LOG: Generate Link PayPal Marketplace untuk ' . $order->invoice_number);

        try {
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);

            // PENTING: PayPal WAJIB menggunakan USD.
            // Ubah IDR ke USD. Di sini kita menggunakan kurs manual statis (misal Rp 16.000)
            $rate = 16000;
            $usdAmount = round($order->total_amount / $rate, 2);

            $items = [[
                'name' => 'Pesanan ' . $order->invoice_number,
                'quantity' => '1',
                'unit_amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($usdAmount, 2, '.', '')
                ]
            ]];

            // Panggil API PayPal
            $response = $paypalService->createOrder(
                $items,
                $usdAmount,
                $order->invoice_number, // custom_id
                'CAPTURE',
                route('paypal.capture.return', ['invoice' => $order->invoice_number]), // URL Redirect sukses
                route('checkout.index') // URL Redirect batal
            );

            $result = $response->getData(true);

            if (isset($result['success']) && $result['success'] === true && !empty($result['approve_url'])) {
                return $result['approve_url'];
            }

            Log::error('PayPal Create Order Error', $result);
            throw new \Exception('Gagal mendapatkan link pembayaran PayPal.');

        } catch (\Exception $e) {
            Log::error('LOG LOG: Exception PayPal (Marketplace): ' . $e->getMessage());
            throw new \Exception('Terjadi kesalahan sistem saat menghubungi PayPal.');
        }
    }

    /**
     * =========================================================================
     * 2. PENERIMA REDIRECT PAYPAL (DARI FRONTEND)
     * =========================================================================
     * Ketika user klik "Approve" di PayPal, mereka dilempar ke sini
     */
    /* public function capturePaypalReturn(Request $request, $invoice)
    {
        $token = $request->query('token'); // Order ID dari PayPal

        if (!$token) {
            return redirect()->route('checkout.index')->with('error', 'Sesi PayPal tidak valid.');
        }

        try {
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
            $response = $paypalService->captureOrder($token);
            $result = $response->getData(true);

            if ($result['success'] === true && $result['status'] === 'COMPLETED') {

                // MENGAKALI RACE CONDITION: Tembak KiriminAja dari sini jika webhook terlambat
                $this->processOrderCallback($invoice, 'PAID', $result);

                return redirect()->route('customer.pesanan.riwayat_belanja')
                    ->with('success', 'Pembayaran via PayPal Berhasil! Pesanan sedang diproses dan kurir KiriminAja telah dipanggil.');
            }

            return redirect()->route('checkout.index')->with('error', 'Dana belum berhasil ditarik oleh PayPal.');
        } catch (\Exception $e) {
            Log::error("PayPal Capture Error: " . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Terjadi kesalahan saat memverifikasi PayPal.');
        }
    }*/

    /**
     * =========================================================================
     * 2. PENERIMA REDIRECT PAYPAL (DARI FRONTEND)
     * =========================================================================
     * Ketika user klik "Approve" di PayPal, mereka dilempar ke sini
     */
    public function capturePaypalReturn(Request $request, $invoice)
    {
        $token = $request->query('token'); // Order ID dari PayPal

        if (!$token) {
            return redirect()->route('checkout.index')->with('error', 'Sesi PayPal tidak valid.');
        }

        try {
            $paypalService = app(\App\Http\Controllers\Api\PayPalGatewayController::class);
            $response = $paypalService->captureOrder($token);
            $result = $response->getData(true);

            if ($result['success'] === true && $result['status'] === 'COMPLETED') {

                // === LOGIKA TANGKAP TAGIHAN INDUK (PARENT INVOICE) ===
                if (\Illuminate\Support\Str::startsWith($invoice, 'SCK-PAY-') || \Illuminate\Support\Str::startsWith($invoice, 'INV-PAY-')) {
                    Log::info('Routing PayPal capture to Process Parent Invoice', ['ref' => $invoice]);

                    // HYBRID SEARCH
                    $anakOrders = \App\Models\Order::on('mysql')->where('parent_invoice', $invoice)->get();
                    if ($anakOrders->isEmpty()) {
                        $anakOrders = \App\Models\Order::on('mysql_second')->where('parent_invoice', $invoice)->get();
                    }

                    foreach ($anakOrders as $anak) {
                        Log::info("Memproses Order Anak PayPal: {$anak->invoice_number} dari Parent: {$invoice}");
                        $this->processOrderCallback($anak->invoice_number, 'PAID', $result);
                    }
                }
                // === LOGIKA INVOICE TUNGGAL ===
                else {
                    // MENGAKALI RACE CONDITION: Tembak KiriminAja dari sini
                    $this->processOrderCallback($invoice, 'PAID', $result);
                }

                return redirect()->route('customer.pesanan.riwayat_belanja')
                    ->with('success', 'Pembayaran via PayPal Berhasil! Pesanan sedang diproses dan kurir KiriminAja telah dipanggil.');
            }

            return redirect()->route('checkout.index')->with('error', 'Dana belum berhasil ditarik oleh PayPal.');
        } catch (\Exception $e) {
            Log::error("PayPal Capture Error: " . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Terjadi kesalahan saat memverifikasi PayPal.');
        }
    }

    public function searchAddressAjax(Request $request, \App\Services\KiriminAjaService $kiriminAja)
    {
        $keyword = $request->query('q');
        if (strlen($keyword) < 3) return response()->json(['results' => []]);

        try {
            $response = $kiriminAja->searchAddress($keyword);

            // Logika baru: Ambil data dari response['response']['data'] berdasarkan log Anda
            $data = $response['response']['data'] ?? $response['data'] ?? [];

            $formatted = array_map(function($item) {
                // Gunakan full_address sebagai fallback utama jika field spesifik tidak ada
                $displayText = $item['full_address'] ?? 'Alamat Ditemukan';

                return [
                    'id'    => $item['subdistrict_id'] ?? $item['id'] ?? '',
                    'text'  => $displayText,
                    // Karena API tidak kasih nama per field, kita kirim full_address ke JS
                    // agar nanti di-split/dipecah di sisi JavaScript
                    'raw_address' => $displayText,
                    'provinsi'    => '', // Akan diisi di JS
                    'kota'        => '',
                    'kecamatan'   => '',
                    'kelurahan'   => '',
                    'kode_pos'    => ''
                ];
            }, $data);

            return response()->json(['results' => $formatted]);
        } catch (\Exception $e) {
            Log::error('AJAX KiriminAja Error: ' . $e->getMessage());
            return response()->json(['results' => []]);
        }
    }

    public function guestHistory($invoice)
{
        // Cari order berdasarkan nomor invoice
        // Tidak butuh validasi user_id karena ini halaman public
        $order = \App\Models\Order::with('items.product', 'store')
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        return view('customer.pesanan.history-belanja', compact('order'));
    }


    public function downloadGuestPDF($invoice)
    {
        // 1. Cari data order (Tanpa auth check)
        $order = Order::with('items.product', 'items.variant', 'store', 'user')
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        // 2. Load View PDF
        $pdf = Pdf::loadView('checkout.invoice_pdf', compact('order'))
                ->setOption(['isRemoteEnabled' => true])
                ->setPaper('a4', 'portrait');

        // 3. Format Nama File (nama_id transaksi.pdf)
        $namaPembeli = $order->receiver_name ?? ($order->user->nama_lengkap ?? 'Guest');
        $namaAman = \Illuminate\Support\Str::slug($namaPembeli, '_');
        $namaFile = $namaAman . '_' . $order->invoice_number . '.pdf';

        // 4. Eksekusi Download
        return $pdf->download($namaFile);
    }

    public function sendGuestWA($invoice)
    {
        try {
            $order = Order::with('items.product')->where('invoice_number', $invoice)->firstOrFail();
            $fonnteService = app(\App\Services\FonnteService::class);

            // Ambil nomor WA (Prioritas dari form checkout Guest, jika tidak ada baru ambil dari User)
            $noWa = $order->receiver_phone ?? ($order->user->no_wa ?? null);

            if (empty($noWa) || $noWa === '-' || $noWa === '081234567890') {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp pembeli tidak valid atau tidak ditemukan.']);
            }

            $namaPembeli = $order->receiver_name ?? ($order->user->nama_lengkap ?? 'Pelanggan');
            $linkAkses = route('guest.history_belanja', ['invoice' => $order->invoice_number]);

            // Buat List Produk
            $rincian = "";
            foreach($order->items as $item) {
                $rincian .= "- " . ($item->product->name ?? 'Produk Digital') . " (x{$item->quantity})\n";
            }

            // Rakit Pesan
            $pesan = "*Halo {$namaPembeli}!*\n\n";
            $pesan .= "Berikut adalah rincian pesanan Anda di *Sancaka Express*:\n\n";
            $pesan .= "*ID Transaksi:* {$order->invoice_number}\n";
            $pesan .= "*Status:* " . strtoupper($order->status) . "\n";
            $pesan .= "*Total:* Rp " . number_format($order->total_amount, 0, ',', '.') . "\n\n";
            $pesan .= "*Item:*\n{$rincian}\n";
            $pesan .= "Gunakan tautan di bawah ini untuk melihat rincian lengkap, mengunduh invoice (PDF), dan mengakses *E-Ticket / Produk Digital* Anda sewaktu-waktu:\n\n";
            $pesan .= $linkAkses . "\n\n";
            $pesan .= "Terima kasih telah berbelanja!";

            // Pastikan format nomor diawali '62'
            $noWaFormatted = preg_replace('/^0/', '62', $noWa);
            $fonnteService->sendMessage($noWaFormatted, $pesan);

            return response()->json(['success' => true, 'message' => 'Tautan rincian belanja berhasil dikirim ke WhatsApp (' . $noWa . ') Anda.']);

        } catch (\Exception $e) {
            Log::error('Gagal kirim resi WA Guest: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan internal.']);
        }
    }

    /**
     * Fungsi untuk public/guest menyelesaikan pesanan digital
     */
    public function completeOrder($id)
    {
        try {
            // Cari data pesanan
            $order = \App\Models\Order::findOrFail($id);

            // Pastikan hanya pesanan yang sudah dibayar (paid/processing) yang bisa diselesaikan
            $validStatuses = ['paid', 'processing', 'lunas', 'diproses'];

            if (in_array(strtolower($order->status), $validStatuses)) {

                // 1. Ubah status pesanan menjadi completed
                $order->status = 'completed';
                $order->save();

                // 2. LOGIKA PENCAIRAN ESCROW KE PENJUAL (Disesuaikan dengan Model Anda)
                $escrow = \App\Models\Escrow::where('order_id', $order->id)->first();

                if ($escrow && $escrow->status_dana !== 'dicairkan') {
                    // Update status escrow dan catat waktu pencairan
                    $escrow->status_dana = 'dicairkan';
                    $escrow->dicairkan_pada = now(); // Mengisi field datetime dicairkan_pada
                    $escrow->save();

                    // 3. TERUSKAN DANA KE SALDO PENJUAL
                    // Mengambil data user penjual (seller) melalui relasi store
                    $seller = $order->store->user;

                    if ($seller) {
                        // Tambahkan 'nominal_ditahan' ke saldo penjual
                        // NOTE: Pastikan 'saldo' adalah nama kolom yang benar di tabel 'penggunas'
                        $seller->saldo += $escrow->nominal_ditahan;
                        $seller->save();
                    }
                }

                // LOG LOG tidak diubah / dihapus (Aman!)
                \Illuminate\Support\Facades\Log::info("Pesanan {$order->invoice_number} diselesaikan oleh Publik/Guest. Escrow berhasil dicairkan.");

                return redirect()->back()->with('success', 'Terima kasih! Pesanan telah dikonfirmasi selesai dan dana diteruskan ke Penjual.');
            }

            return redirect()->back()->with('error', 'Gagal! Status pesanan saat ini tidak dapat diselesaikan.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Complete Order Digital: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat memproses penyelesaian pesanan.');
        }
    }

    /**
     * =========================================================================
     * EKSEKUTOR PEMBAYARAN KHUSUS PPOB DANA (TRIPAY & SALDO)
     * =========================================================================
     */
    public function storePpobDanaPayment(Request $request)
    {
        Log::info('LOG LOG - Memulai Pembayaran PPOB DANA', $request->all());

        $request->validate([
            'primary_param'  => 'required|numeric|min_digits:9',
            'product_id'     => 'required|string|exists:products_dana_ppob,product_id',
            'payment_method' => 'required|string',
        ], [
            'payment_method.required' => 'Silakan pilih metode pembayaran terlebih dahulu.'
        ]);

        $product = \App\Models\ProductDanaPpob::where('product_id', $request->product_id)->first();
        if (!$product || !$product->is_available) {
            return back()->with('error', 'Produk tidak tersedia saat ini.');
        }

        //$user = Auth::user();
        //if (!$user) {
        //    return redirect()->route('customer.login')->with('info', 'Anda harus login untuk bertransaksi PPOB.');
        // }

        $user = Auth::user();

        // ====================================================================
        // 🔥 FLEKSIBILITAS PPOB: SILENT REGISTRATION TANPA WAJIB LOGIN
        // ====================================================================
        if (!$user) {
            // Kita manfaatkan primary_param (contoh: nomor HP tujuan pulsa) sebagai nomor WA pendaftaran
            $waGuest = $request->no_wa ?? $request->primary_param;
            $emailGuest = $request->email ?? 'guest_' . time() . '@tokosancaka.com';

            // Zero Trust: Cek apakah nomor/email ini sudah ada di database
            $user = \App\Models\User::where('no_wa', $waGuest)->orWhere('email', $emailGuest)->first();

            if (!$user) {
                $user = \App\Models\User::create([
                    'nama_lengkap' => $request->nama_lengkap ?? 'Guest PPOB ' . rand(100, 999),
                    'email'        => $emailGuest,
                    'no_wa'        => preg_replace('/[^0-9]/', '', $waGuest),
                    'password'     => bcrypt(\Illuminate\Support\Str::random(12)),
                    'role'         => 'Pelanggan'
                ]);
                Log::info("LOG LOG - Akun PPOB dibuat otomatis untuk Guest: {$waGuest}");
            }
        }
        // ====================================================================

        $grand_total = $product->price_value;
        $invoiceNumber = 'PPOBDANA-' . strtoupper(Str::random(8));

        DB::beginTransaction();
        try {
            // 1. Catat ke tabel orders_ppob_dana
            \App\Models\OrderPpobDana::create([
                'order_id'      => $invoiceNumber,
                'request_id'    => $invoiceNumber,
                'product_id'    => $product->product_id,
                'primary_param' => $request->primary_param,
                'dana_price_value' => $grand_total,
                'status_code'   => '20', // Pending
                'status_status' => 'PENDING_PAYMENT',
                'status_message'=> 'Menunggu Pembayaran Customer'
            ]);

            $paymentMethodRaw = strtoupper($request->payment_method);

            // 2. LOGIKA POTONG SALDO INTERNAL
            if (in_array($paymentMethodRaw, ['POTONG SALDO', 'SALDO'])) {
                if ($user->saldo < $grand_total) {
                    throw new Exception('Saldo akun Anda tidak mencukupi.');
                }
                $user->saldo -= $grand_total;
                $user->save();

                DB::commit();
                Log::info("LOG LOG - PPOB DANA Lunas via Saldo: {$invoiceNumber}");

                // TODO: Panggil fungsi di DanaPpobDigitalGoodsController untuk eksekusi top-up ke Provider (Misal Digiflazz)
                // \App\Http\Controllers\DanaPpobDigitalGoodsController::processCallback($invoiceNumber, 'PAID');

                return redirect()->route('customer.pesanan.riwayat_belanja')
                    ->with('success', 'Pembayaran via Saldo Berhasil! Transaksi PPOB sedang diproses.');
            }

            // 3. LOGIKA TRIPAY / PAYMENT GATEWAY
            $orderItemsPayload = [[
                'sku'      => $product->product_id,
                'name'     => 'Topup ' . strtoupper($product->provider) . ' - ' . $request->primary_param,
                'price'    => (int) $grand_total,
                'quantity' => 1
            ]];

            // Buat Dummy Object Order agar fungsi _createTripayTransaction tidak error
            $dummyOrder = new \stdClass();
            $dummyOrder->invoice_number = $invoiceNumber;
            $dummyOrder->user_id = $user->id_pengguna;

            // Generate Link Tripay
            $tripayResult = $this->_createTripayTransaction(
                $dummyOrder,
                $request->payment_method, // Kode dari form (contoh: QRISC, MYBVA)
                $grand_total,
                $user->nama_lengkap ?? 'Customer Sancaka',
                $user->email ?? 'customer@tokosancaka.com',
                $user->no_wa ?? '081234567890',
                $orderItemsPayload
            );

            if ($tripayResult['success']) {
                $paymentUrl = $tripayResult['data']['checkout_url'] ?? $tripayResult['data']['pay_url'];

                // Simpan URL pembayaran ke kolom token (opsional)
                \App\Models\OrderPpobDana::where('order_id', $invoiceNumber)->update(['token' => $paymentUrl]);

                DB::commit();
                Log::info("LOG LOG - PPOB DANA Redirect ke Tripay: {$invoiceNumber}");
                return redirect()->away($paymentUrl);
            } else {
                throw new Exception($tripayResult['message']);
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('LOG LOG - PPOB Payment Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

} // Akhir Class
