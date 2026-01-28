<?php // <-- Pastikan tidak ada spasi atau baris kosong SEBELUM ini

namespace App\Http\Controllers; // <-- Pastikan tidak ada spasi atau baris kosong ANTARA ini dan <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification; // <-- TAMBAHKAN INI
use App\Notifications\NotifikasiUmum;      // <-- TAMBAHKAN INI
use App\Events\AdminNotificationEvent; // <-- TAMBAHKAN INI
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



class CheckoutController extends Controller
{
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


    /**
     * Menampilkan halaman checkout.
     */
    public function index(KiriminAjaService $kiriminAja)
    {
        if (!Auth::check()) {
            return redirect()->route('customer.login')
                ->with('info', 'Anda harus login untuk melanjutkan ke checkout.');
        }

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('info', 'Keranjang Anda kosong. Silakan belanja terlebih dahulu.');
        }

       // === [1] GANTI BAGIAN INI DI FUNCTION INDEX() ===

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

        if (empty($store->village) || empty($store->district) || empty($store->regency) || empty($store->province)) {
             Log::error('Alamat toko tidak lengkap', ['store_id' => $store->id]);
            return redirect()->route('cart.index')
                ->with('error', 'Alamat toko asal pengiriman tidak lengkap. Silakan hubungi penjual.');
        }

        if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province)) {
             Log::warning('Alamat user tidak lengkap', ['user_id' => $user->id_pengguna]);
            return redirect()->route('profile.edit')
                ->with('warning', 'Alamat pengiriman Anda belum lengkap. Mohon lengkapi data lokasi Anda terlebih dahulu.');
        }

        $storeSearch = $store->village . ', ' . $store->district . ', ' . $store->regency . ', ' . $store->province;
        $userSearch  = $user->village . ', ' . $user->district . ', ' . $user->regency . ', ' . $user->province;

        try {
            $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
            $userAddrRes  = $kiriminAja->searchAddress($userSearch);
        } catch (Exception $e) {
             Log::error('Gagal mencari alamat KiriminAja di Checkout Index', ['error' => $e->getMessage()]);
             return redirect()->route('cart.index')->with('error', 'Gagal memvalidasi alamat pengiriman. Silakan coba lagi nanti.');
        }


        $storeAddr = $storeAddrRes['data'][0] ?? null;
        $userAddr  = $userAddrRes['data'][0] ?? null;

        if (!$storeAddr || !$userAddr) {
             Log::error('Alamat tidak ditemukan oleh KiriminAja', ['store_search' => $storeSearch, 'user_search' => $userSearch, 'store_res' => $storeAddrRes, 'user_res' => $userAddrRes]);
            return redirect()->route('cart.index')
                ->with('error', 'Alamat pengiriman atau alamat toko tidak dapat divalidasi oleh sistem ekspedisi.');
        }

        $storeLat = $store->latitude ? (float) $store->latitude : null;
        $storeLng = $store->longitude ? (float) $store->longitude : null;
        $userLat  = $user->latitude ? (float) $user->latitude : null;
        $userLng  = $user->longitude ? (float) $user->longitude : null;

        $totalWeight = (int) collect($cart)->sum(function($item) {
            $product = Product::find($item['product_id']);
            $weight = $product->weight ?? 1000;
            return $weight * $item['quantity'];
        });
        $finalWeight = max(1000, $totalWeight);

        $itemValue   = (int) collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

        // Perbaikan: Gunakan default 5cm jika data tidak ada, BUKAN 10
        $defaultLength = $firstProduct->length ?? 5;
        $defaultWidth  = $firstProduct->width  ?? 5;
        $defaultHeight = $firstProduct->height ?? 5;

        $expressOptions = null;
        try {
             $expressOptions = $kiriminAja->getExpressPricing(
                 $storeAddr['district_id'],
                 $storeAddr['subdistrict_id'],
                 $userAddr['district_id'],
                 $userAddr['subdistrict_id'],
                 $finalWeight,
                 $defaultLength, $defaultWidth, $defaultHeight,
                 $itemValue,
                 null,
                 $category,
                 1
             );
             Log::info('Express Pricing Result:', ['options' => $expressOptions]);
        } catch (Exception $e) {
             Log::error('Gagal mendapatkan ongkir Express/Cargo', ['error' => $e->getMessage()]);
        }


        if (!$storeLat || !$storeLng) {
            $geo = $this->geocode($storeSearch);
            if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; }
        }

        if (!$userLat || !$userLng) {
            $geo = $this->geocode($userSearch);
            if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; }
        }

        $instantOptions = null;
        if ($storeLat && $storeLng && $userLat && $userLng) {
            try {
                 $instantOptions = $kiriminAja->getInstantPricing(
                     $storeLat, $storeLng, $store->address_detail ?? $storeSearch,
                     $userLat, $userLng, $user->address_detail ?? $userSearch,
                     $finalWeight, $itemValue, 'motor'
                 );
                 Log::info('Instant Pricing Result:', ['options' => $instantOptions]);
            } catch (Exception $e) {
                 Log::error('Gagal mendapatkan ongkir Instant/Sameday', ['error' => $e->getMessage()]);
            }
        } else {
             Log::warning('Koordinat tidak lengkap untuk cek ongkir Instant', ['storeLL' => "$storeLat,$storeLng", 'userLL' => "$userLat,$userLng"]);
        }


        // Filter opsi "Express"
        if (isset($expressOptions['status']) && $expressOptions['status'] === true && isset($expressOptions['results'])) {
            $cleanedExpress = [];
            foreach ($expressOptions['results'] as $opt) {
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
        } else {
            $errorMessage = 'Gagal mengambil opsi Express/Cargo.';
            Log::error('Hasil API Express Pricing tidak valid', ['response' => $expressOptions]);
            $expressOptions = ['status' => false, 'text' => $errorMessage, 'results' => []];

            try {
                broadcast(new AdminNotificationEvent(
                    'ERROR KRITIS: Ongkir Checkout Gagal',
                    'Gagal memuat ongkir Express/Cargo. Cek log KiriminAja.',
                    route('admin.dashboard')
                ));
            } catch (Exception $e) {
                Log::error('Gagal broadcast AdminNotificationEvent untuk error ongkir', ['error' => $e->getMessage()]);
            }
        }

        // Filter opsi "Instant"
        if (isset($instantOptions['status']) && $instantOptions['status'] === true && isset($instantOptions['result'])) {
            $parsedInstantOptions = [];
            foreach ($instantOptions['result'] as $provider) {
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
                                'cod_available' => false, // API Instant tidak mendukung COD di log Anda
                                'cod' => false,
                                'cod_fee' => 0,
                                'group' => 'instant',
                            ];
                        }
                    }
                }
            }
            $instantOptions['results'] = $parsedInstantOptions;
        } else {
            $errorMessage = $instantOptions['text'] ?? 'Gagal mengambil opsi Instant/Sameday.';
            Log::error('Hasil API Instant Pricing tidak valid atau koordinat tidak ada', ['response' => $instantOptions]);
            $instantOptions = ['status' => false, 'text' => $errorMessage, 'results' => []];

            if (empty($expressOptions['results'])) {
                try {
                    broadcast(new AdminNotificationEvent(
                        'ERROR KRITIS: Ongkir Checkout Gagal',
                        'Gagal memuat ongkir Instant DAN Express. Cek log KiriminAja.',
                        route('admin.dashboard')
                    ));
                } catch (Exception $e) {
                    Log::error('Gagal broadcast AdminNotificationEvent untuk error ongkir', ['error' => $e->getMessage()]);
                }
            }
        }

        return view('checkout.index', compact('cart', 'expressOptions', 'instantOptions', 'user', 'tripayChannels'));
    }


    /**
     * =========================================================================
     * FUNGSI STORE (DENGAN 1 JEBAKAN LOG YANG BENAR)
     * =========================================================================
     */
    public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
              // DITAMBAHKAN: Validasi opsional untuk GPS
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $cart = session()->get('cart', []);
        $user = Auth::user();

        if (empty($cart)) {
            return redirect()->route('etalase.index')->with('error', 'Terjadi kesalahan. Keranjang Anda kosong.');
        }

        if (empty($user->address_detail)) {
            return redirect()->route('profile.edit')->with('warning', 'Silakan lengkapi alamat pengiriman dahulu.');
        }

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
            // ==== PERBAIKAN 3: Logika $useInsurance yang benar ====
            // Cek apakah user mencentang box 'use_insurance' ATAU apakah asuransi wajib, DAN pastikan biaya asuransi ada.
            $userWantsInsurance = $request->has('use_insurance') && $request->use_insurance == 'on';
            $useInsurance = ($userWantsInsurance || $isMandatoryInsurance) && $insurance_cost > 0;
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

            // --- 4. Buat Order & Order Items ---
            do {
                 $invoiceNumber = 'SCK-ORD-' . strtoupper(Str::random(8)); // Ini sudah benar
            } while (Order::where('invoice_number', $invoiceNumber)->exists() || Pesanan::where('nomor_invoice', $invoiceNumber)->exists());

            $order = new Order([
                 'store_id'      => $store->id,
                 'user_id'         => $user->id_pengguna,
                 'invoice_number'  => $invoiceNumber,
                 'subtotal'        => $subtotal,
                 'shipping_cost'   => $shipping_cost,
                 'insurance_cost'  => $applied_insurance_cost,
                 'cod_fee'         => $cod_add_cost,
                 'total_amount'    => $grand_total,
                 'shipping_method' => $request->shipping_method,
                 'payment_method'  => $request->payment_method,
                 'status'          => (in_array($request->payment_method, ['cod', 'cash', 'CODBARANG'])) ? 'processing' : 'pending',
                 'shipping_address'=> $user->address_detail ?? 'Alamat tidak diatur',
                 // DITAMBAHKAN: Menyimpan data GPS dari request ke database
                'customer_latitude' => $request->latitude ?? null,
                'customer_longitude' => $request->longitude ?? null,
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
                } else {
                    throw new \Exception('Tipe pengiriman tidak didukung.');
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
            else
            {
                // --- 6. Logika Pembayaran Online (Tripay ATAU Doku) ---

                $paymentGateway = 'tripay';
                if (strtoupper($request->payment_method) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }

                if ($paymentGateway === 'doku') {
                    Log::info('Memulai proses DOKU (Jokul) Marketplace untuk ' . $order->invoice_number);

                    // ==============================================================
                    // 🔥🔥 LOGIKA ROUTING DANA (UPDATE: CUKUP CEK ID SAJA) 🔥🔥
                    // ==============================================================
                    $targetSacId = null;

                    // 1. Cek apakah Toko punya ID Sub Account DOKU
                    // (HAPUS cek status 'ACTIVE' sesuai info dari DOKU)
                    if (!empty($store->doku_sac_id)) {
                        // KONDISI A: Toko SUDAH punya SAC ID
                        // Dana akan langsung masuk ke dompet DOKU Toko Penjual
                        // Status akun akan otomatis aktif saat terima dana pertama
                        $targetSacId = $store->doku_sac_id;
                        Log::info("DOKU Routing: Dana diarahkan ke Toko (SAC: {$targetSacId})");
                    } else {
                        // KONDISI B: Toko BELUM punya akun DOKU sama sekali
                        // Dana masuk ke Master Account (Rekening Admin Sancaka)
                        $targetSacId = null;
                        Log::info("DOKU Routing: Toko tidak punya SAC ID. Dana cair ke Admin Sancaka (Master Account).");
                    }

                    $dokuService = new DokuJokulService();
                    $customerData = [
                        'name'  => $user->nama_lengkap,
                        'email' => $user->email,
                        'phone' => $user->no_wa
                    ];

                    // 2. Siapkan Data Routing (Additional Info)
                    $additionalInfo = [];
                    if (!empty($targetSacId)) {
                        $additionalInfo = [
                            'account' => [ 'id' => $targetSacId ]
                        ];
                    }

                    // 3. Panggil Service Create Payment
                    $paymentUrl = $dokuService->createPayment(
                        $order->invoice_number,
                        $grand_total,
                        $customerData,
                        $orderItemsPayload,
                        $additionalInfo     // <-- Data routing dikirim di sini
                    );

                    if (empty($paymentUrl)) {
                        throw new Exception('Gagal membuat transaksi pembayaran DOKU.');
                    }

                    $order->payment_url = $paymentUrl;

                } else {
                Log::info('Memulai proses TRIPAY Marketplace untuk ' . $order->invoice_number);

                // 1. Buat Transaksi ke Tripay
                $tripayResult = $this->_createTripayTransaction(
                    $order,
                    $request->payment_method,
                    $grand_total,
                    $user->nama_lengkap,
                    $user->email,
                    $user->no_wa,
                    $orderItemsPayload
                );

                if ($tripayResult['success']) {
                    $tripayData = $tripayResult['data'];

                    // 2. SIMPAN DATA PENTING KE DATABASE (INI KUNCINYA)
                    // Simpan Link Redirect (Shopee/OVO/Dana)
                    $order->payment_url = $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? null;

                    // Simpan Nomor VA / Kode Bayar (BCA/BRI/Alfa) -> Masuk ke kolom baru
                    $order->pay_code = $tripayData['pay_code'] ?? null;

                    // Simpan URL QRIS (Jika QRIS) -> Masuk ke kolom baru
                    $order->qr_url = $tripayData['qr_url'] ?? null;

                    $order->save(); // Simpan perubahan ke DB

                } else {
                    throw new \Exception($tripayResult['message']);
                }
            }
            // --- 7. Selesai Semua, Commit Transaksi ---
            }

            // ==========================================================
            // 4. PROSES REDIRECT (UPDATE SESUAI INSTRUKSI)
            // ==========================================================

            // Simpan status akhir & clear session
            $order->save();
            DB::commit();
            session()->forget('cart');

            // --- A. JIKA METODE DANA (AUTO REDIRECT KE PAYMENT GATEWAY) ---
            if ($request->payment_method === 'DANA') {
                return $this->createPaymentDANA($order);
            }

            // --- B. JIKA COD / CASH / TRIPAY / DOKU (REDIRECT KE HALAMAN TOKO) ---

            // Cek Role User untuk menentukan tujuan redirect
            $currentUser = Auth::user();

            // 1. Jika ADMIN -> Ke Halaman Admin Orders
            if ($currentUser && $currentUser->role === 'Admin') {

                // Kirim notifikasi jika COD/Cash
                if (in_array($request->payment_method, ['cod', 'cash'])) {
                    $this->kirimNotifikasiPesananLengkap($order, 'Baru (COD/Cash)');
                }

                return redirect()->to('https://tokosancaka.com/admin/orders')
                    ->with('success', 'Pesanan berhasil dibuat (Mode Admin).');
            }

            // 2. Jika CUSTOMER / SELLER -> Ke Halaman Riwayat Belanja
            else {

                // Kirim notifikasi jika COD/Cash
                if (in_array($request->payment_method, ['cod', 'cash'])) {
                    $this->kirimNotifikasiPesananLengkap($order, 'Baru (COD/Cash)');
                }

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
        // 1. CONFIGURATION (SYNC ID)
        // ====================================================================
        // Menggunakan ID Valid (2166...) untuk Header & Body agar sinkron
        $validId = "216620080014040009735";
        $merchantIdConf = $validId;
        $partnerIdConf  = "2025081520100641466855"; // Partner ID yang sinkron dengan Merchant ID di atas

        // ====================================================================
        // 2. DATA PREPARATION
        // ====================================================================
        $cleanInvoice = preg_replace('/[^a-zA-Z0-9]/', '', $order->invoice_number);
        $timestamp    = Carbon::now('Asia/Jakarta')->toIso8601String();
        $expiryTime   = Carbon::now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d\TH:i:sP');
        $amountValue  = number_format((float)$order->total_amount, 2, '.', '');

        // ====================================================================
        // 3. BODY REQUEST
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
                ["url" => route('dana.return'), "type" => "PAY_RETURN", "isDeeplink" => "Y"],
                ["url" => route('dana.notify'), "type" => "NOTIFICATION", "isDeeplink" => "Y"]
            ],
            // Opsi Pembayaran (Wajib BALANCE/Saldo agar aman tanpa Token)
            "payOptionDetails"   => [
                [
                    "payMethod"   => "BALANCE",
                    "payOption"   => "BALANCE",
                    "transAmount" => ["value" => $amountValue, "currency" => "IDR"],
                    "feeAmount"   => ["value" => "0.00", "currency" => "IDR"]
                ]
            ],
            "additionalInfo"     => [
                "productCode" => "51051000100000000001",
                "mcc"         => "5732",
                "order"       => [
                    "orderTitle"        => substr("Pay " . $cleanInvoice, 0, 40),
                    "merchantTransType" => "01",
                    "orderMemo"         => substr("Inv " . $cleanInvoice, 0, 40),
                    "createdTime"       => $timestamp,
                    "buyer"             => [
                        "externalUserId"   => (string) ($order->user_id ?? 'GUEST'.rand(100,999)),
                        "externalUserType" => "MERCHANT_USER",
                        "nickname"         => substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $order->user->nama_lengkap ?? 'Guest'), 0, 20),
                    ],
                    // Goods Wajib Ada
                    "goods" => [
                        [
                            "merchantGoodsId" => substr("ITEM" . $cleanInvoice, 0, 40),
                            "description"     => "Pembayaran Order",
                            "category"        => "DIGITAL_GOODS",
                            "price"           => ["value" => $amountValue, "currency" => "IDR"],
                            "unit"            => "pcs",
                            "quantity"        => "1"
                        ]
                    ]
                ],
                "envInfo" => [
                                    "sourcePlatform" => "IPG",
                                    "terminalType" => "SYSTEM",
                                    "orderTerminalType" => "WEB",
                ]
            ]
        ];

        $jsonBody = json_encode($bodyArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $relativePath = '/rest/redirection/v1.0/debit/payment-host-to-host';

        try {
            // ====================================================================
            // 4. SIGNATURE & HEADERS
            // ====================================================================
            $accessToken = $this->danaSignature->getAccessToken();
            $signature   = $this->danaSignature->generateSignature('POST', $relativePath, $jsonBody, $timestamp);

            $headers = [
                'Authorization'  => 'Bearer ' . $accessToken,
                'X-PARTNER-ID'   => $partnerIdConf, // ID Sinkron dengan Body
                'X-EXTERNAL-ID'  => Str::random(32),
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'Content-Type'   => 'application/json',
                'CHANNEL-ID'     => '95221',
                'ORIGIN'         => config('services.dana.origin'),
            ];

            // ====================================================================
            // 5. LOGGING REQUEST (SEBELUM KIRIM)
            // ====================================================================
            Log::info('DANA_REQ_START', [
                'Invoice' => $cleanInvoice,
                'URL'     => config('services.dana.base_url') . $relativePath,
                'Headers' => $headers,
                'Body'    => $bodyArray
            ]);

            // ====================================================================
            // 6. SEND REQUEST
            // ====================================================================
            $response = Http::withHeaders($headers)
                ->withBody($jsonBody, 'application/json')
                ->post(config('services.dana.base_url') . $relativePath);

            $result = $response->json();

            // ====================================================================
            // 7. LOGGING RESPONSE (SETELAH TERIMA)
            // ====================================================================
            Log::info('DANA_RES_END', [
                'Invoice'     => $cleanInvoice,
                'Status_Code' => $response->status(),
                'Result'      => $result
            ]);

            // ====================================================================
            // 8. HANDLE SUCCESS / REDIRECT
            // ====================================================================
            // Cek Kode Sukses DANA (2005400)
            if (isset($result['responseCode']) && $result['responseCode'] == '2005400') {
                $redirectUrl = $result['webRedirectUrl'] ?? null;
                if($redirectUrl) {
                    // Simpan URL Pembayaran
                    $order->payment_url = $redirectUrl;
                    $order->save();

                    // Kosongkan Keranjang
                    session()->forget('cart');

                    // REDIRECT USER KE DANA
                    return redirect()->away($redirectUrl);
                }
            }

            // Jika Gagal DANA, Log Error dan Kembalikan User
            Log::error('DANA_FAIL', ['Result' => $result]);
            return redirect()->route('checkout.index')->with('error', 'Gagal memproses pembayaran DANA: ' . ($result['responseMessage'] ?? 'Unknown Error'));

        } catch (\Exception $e) {
            // Tangkap Error Koneksi / Koding
            Log::error('DANA_EXCEPTION', ['Error' => $e->getMessage()]);
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
            // === PERBAIKAN LOGIKA ROUTING ===

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
     * 1. HANDLER WEBHOOK DOKU (JOKUL) - FULL VERSION
     * =========================================================================
     */
    public function handleDokuCallback(array $data)
    {
        // Ambil data referensi & status
        $merchantRef = $data['order']['invoice_number'];
        $status = $data['transaction']['status'];

        Log::info('Processing DOKU Callback...', ['ref' => $merchantRef, 'status' => $status]);

        // Mapping status
        $internalStatus = ($status === 'SUCCESS') ? 'PAID' : 'FAILED';

        DB::beginTransaction();
        try {
            // Routing berdasarkan prefix
            if (Str::startsWith($merchantRef, 'TOPUP-')) {
                Log::info('Routing DOKU callback to TopUpController', ['ref' => $merchantRef]);
                TopUpController::processTopUpCallback($merchantRef, $internalStatus, $data['order']['amount'], $data);

            } elseif (Str::startsWith($merchantRef, 'ORD-') || Str::startsWith($merchantRef, 'SCK-ORD-') || Str::startsWith($merchantRef, 'SCK-')) {
                Log::info('Routing DOKU callback to processOrderCallback', ['ref' => $merchantRef]);
                // Panggil fungsi prosesor lengkap
                $this->processOrderCallback($merchantRef, $internalStatus, $data);

            } else {
                Log::warning('DOKU Callback: Unrecognized merchant_ref prefix.', ['merchant_ref' => $merchantRef]);
            }

            DB::commit();
            return response()->json(['message' => 'Webhook processed successfully.'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("DOKU Callback Exception", ['ref' => $merchantRef, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }

    /**
     * =========================================================================
     * 2. FUNGSI PROSESOR UTAMA (LENGKAP DENGAN AUTO RETRY & NOTIF CUSTOMER)
     * =========================================================================
     */
    public function processOrderCallback($merchantRef, $status, $callbackData)
    {
        Log::info('Processing Order Callback (ORD-/SCK-)...', ['ref' => $merchantRef, 'status' => $status]);
        $fonnteService = app(FonnteService::class);
        $cleanRef = trim($merchantRef);

        // -----------------------------------------------------------
        // 1. PENCARIAN HYBRID
        // -----------------------------------------------------------
        $isLegacy = false;

        // Cari di Orders (Baru)
        $order = Order::with('items.product.store.user', 'items.variant', 'user')
                    ->where('invoice_number', $cleanRef)
                    ->lockForUpdate()
                    ->first();

        // Cari di Pesanan (Lama)
        if (!$order) {
            Log::info("Order tidak ditemukan di tabel 'orders', mencari di tabel 'Pesanan'...", ['ref' => $cleanRef]);
            if (class_exists(\App\Models\Pesanan::class)) {
                $order = \App\Models\Pesanan::where('nomor_invoice', $cleanRef)
                            ->lockForUpdate()
                            ->first();
                $isLegacy = true;
            }
        }

        if (!$order) {
            Log::error('FATAL: Order tidak ditemukan.', ['ref' => $cleanRef]);
            return;
        }

        // -----------------------------------------------------------
        // 2. VALIDASI STATUS
        // -----------------------------------------------------------
        $statusBoleh = ['pending', 'menunggu pembayaran', 'unpaid', 'menunggu_pembayaran'];
        if (!in_array(strtolower($order->status), $statusBoleh)) {
            return; // Order sudah diproses sebelumnya
        }

        // -----------------------------------------------------------
        // 3. PROSES UTAMA (LUNAS)
        // -----------------------------------------------------------
        // -----------------------------------------------------------
        // 3. PROSES UTAMA (LUNAS)
        // -----------------------------------------------------------
        if ($status === 'PAID') {

            // A. Update Status Database
            $order->status = 'paid';
            $order->save();
            Log::info("Order {$merchantRef} PAID.");

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

                $validTypes = ['regular', 'cargo', 'instant', 'trucking'];
                if (!in_array($type, $validTypes)) $type = 'regular';
                $service = strtoupper(trim($service));

                // --- BUILD PAYLOAD BOOKING ---
                $payload = [];
                $kiriminResponse = null;

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
                        'schedule' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'platform_name' => 'TOKOSANCAKA.COM'
                    ];

                    // 1. PERCOBAAN PERTAMA: Set Jadwal HARI INI (Sekarang)
                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);

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

                    $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province;
                    $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                    $storeData = $storeAddrRes['data'][0] ?? null;

                    $userSearch = $user->village . ', ' . $user->regency . ', ' . $user->province;
                    $userAddrRes = $kiriminAja->searchAddress($userSearch);
                    $userData = $userAddrRes['data'][0] ?? null;

                    if (!$storeData || !$userData) goto skip_kiriminaja;

                    $packagesPayload = [];
                    $totalWeight = 0;
                    $jenisBarang = $item->product->jenis_barang ?? 1;
                    foreach($order->items as $item) {
                        $w = $item->product->weight ?? 1000;
                        $totalWeight += ($w * $item->quantity);
                        $packagesPayload[] = [
                            'order_id' => $order->invoice_number,
                            'destination_name' => $user->nama_lengkap,
                            'destination_phone' => $user->no_wa,
                            'destination_address' => $order->shipping_address,
                            'destination_kecamatan_id' => $userData['district_id'],
                            'destination_kelurahan_id' => $userData['subdistrict_id'],
                            'weight' => $w * $item->quantity,
                            'width' => 10, 'height' => 10, 'length' => 10,
                            'item_value' => $item->price * $item->quantity,
                            'item_name' => $item->product->name,
                            'service' => $courier, 'service_type' => $service,
                            'shipping_cost' => (int) $order->shipping_cost,
                            'package_type_id' => (int) $jenisBarang,
                            'cod' => 0
                        ];
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
                                'item' => ['name' => 'Pesanan '.$order->invoice_number, 'price' => $order->subtotal, 'weight' => max(1000, $totalWeight)],
                                'shipping_price' => (int) $order->shipping_cost
                            ]]
                        ];
                        $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                    } else {
                        $payload = [
                            'kecamatan_id' => $storeData['district_id'],
                            'kelurahan_id' => $storeData['subdistrict_id'],
                            'address' => $store->address_detail,
                            'phone' => $store->user->no_wa,
                            'name' => $store->name,
                            'zipcode' => $store->postal_code,
                            'latitude' => $store->latitude,
                            'longitude' => $store->longitude,
                            'packages' => $packagesPayload,
                            'category' => ($type == 'cargo') ? 'trucking' : 'regular',

                            // FIX: Tambahkan Schedule (Wajib)
                            'schedule' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                            'platform_name' => 'TOKOSANCAKA.COM'
                        ];
                        $kiriminResponse = $kiriminAja->createExpressOrder($payload);

                        // AUTO RETRY LOGIC (Order Baru)
                        if (isset($kiriminResponse['status']) && $kiriminResponse['status'] === false) {
                            $pesanError = strtolower($kiriminResponse['text'] ?? '');
                            if (str_contains($pesanError, 'jadwal') || str_contains($pesanError, 'schedule')) {
                                Log::info("Booking Order Baru hari ini gagal. Mencoba besok...");
                                $payload['schedule'] = \Carbon\Carbon::now()->addDay()->format('Y-m-d 09:00:00');
                                $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                            }
                        }
                    }
                }

                Log::info('KiriminAja Response:', ['res' => $kiriminResponse]);

                // --- CEK STATUS BOOKING ---
                if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                    // SUKSES
                    $resi = $kiriminResponse['packages'][0]['awb'] ??
                            ($kiriminResponse['result']['awb_no'] ??
                            ($kiriminResponse['results'][0]['awb'] ?? null));

                    if ($resi) {
                        $order->shipping_resi = $resi;
                        if ($isLegacy) $order->resi = $resi;
                        $order->status = 'processing';
                        $order->save();
                        Log::info("Booking Sukses! Resi: {$resi}");
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

        // 6. Siapkan Payload
        $payload = [
            'method'         => $methodChannel,
            'merchant_ref'   => $order->invoice_number,
            'amount'         => $amount,
            'customer_name'  => $custName,
            'customer_email' => $custEmail,
            'customer_phone' => $custPhone,
            'order_items'    => $items,
            'return_url'     => route('checkout.invoice', ['invoice' => $order->invoice_number]),
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

} // Akhir Class
