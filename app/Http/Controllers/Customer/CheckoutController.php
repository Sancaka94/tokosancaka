<?php // <-- Pastikan tidak ada spasi atau baris kosong SEBELUM ini

namespace App\Http\Controllers\Customer; // <-- Pastikan tidak ada spasi atau baris kosong ANTARA ini dan <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Pesanan;
use App\Models\Marketplace; 
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\OrderMarketplace; 
use App\Models\OrderItemMerketplace; 
use App\Events\SaldoUpdated;
use App\Events\AdminNotificationEvent;
use App\Services\KiriminAjaService;
use App\Services\DokuJokulService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Validation\ValidationException;

// IMPORT SEMUA CONTROLLER YANG MEMILIKI FUNGSI PROSESOR CALLBACK
use App\Http\Controllers\Admin\PesananController as AdminPesananController;
use App\Http\Controllers\Customer\PesananController as CustomerPesananController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\Customer\TopUpController;
use App\Http\Controllers\Toko\ProdukController;

class CheckoutController
{

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

            $data = $response.json();

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
     * ==========================================================
     * FUNGSI INDEX (VIEW CHECKOUT)
     * ==========================================================
     */
    public function index(KiriminAjaService $kiriminAja)
    {
        if (!Auth::check()) {
            return redirect()->route('customer.login')
                ->with('info', 'Anda harus login untuk melanjutkan.');
        }

        $cart = session()->get('cart', []);
        
        // Log Debug untuk melihat isi cart saat ini
        Log::info('Checkout Index: Data Keranjang', ['cart' => $cart]);

        if (empty($cart)) {
            return redirect()->route('customer.cart.index')->with('info', 'Keranjang Anda kosong.');
        }

        $user = Auth::user();
        
        // Cek Role (Opsional)
        if (isset($user->role) && strtolower($user->role) === 'pelanggan') {
            // Logika redirect khusus pelanggan jika diperlukan
        }

        // ==========================================================
        // 1. VALIDASI KERANJANG (FISIK & PPOB)
        // ==========================================================
        
        $validCart = [];
        $invalidItems = [];
        $firstValidStore = null;
        $hasPhysicalProduct = false; // Flag penanda apakah ada barang fisik (butuh ongkir)

        foreach ($cart as $cartKey => $item) {
            
            // --------------------------------------------------------
            // ⚡ FIX UTAMA: CEK PPOB DULU SEBELUM CEK DATABASE ⚡
            // --------------------------------------------------------
            // Jika item ini ditandai sebagai PPOB (is_ppob = true)
            // Maka anggap VALID dan JANGAN cek ke tabel 'marketplaces' / 'products'
            if (isset($item['is_ppob']) && $item['is_ppob'] == true) {
                $validCart[$cartKey] = $item;
                continue; // <--- PENTING: Langsung lanjut ke item berikutnya
            }

            // --------------------------------------------------------
            // LOGIKA PRODUK FISIK (JALAN HANYA JIKA BUKAN PPOB)
            // --------------------------------------------------------
            $productId = $item['product_id'] ?? null;
            
            if (!$productId) { 
                $invalidItems[] = $item['name'] ?? 'Unknown'; 
                continue; 
            }

            // Cek Database Fisik
            $product = Marketplace::find($productId);

            if ($product && $product->store && $product->store->user) {
                // Item Fisik Valid
                $validCart[$cartKey] = $item;
                $hasPhysicalProduct = true; // Tandai butuh ongkir

                // Ambil toko pertama untuk titik asal ongkir
                if ($firstValidStore === null) {
                    $firstValidStore = $product->store;
                }
            } else {
                // Item Fisik Tidak Valid (Dihapus/Nonaktif)
                $invalidItems[] = $item['name'] ?? 'Produk ID ' . $productId;
                Log::warning('Checkout Index: Produk di keranjang tidak ditemukan.', ['product_id' => $productId]);
            }
        }

        // Simpan keranjang yang sudah divalidasi
        session()->put('cart', $validCart);
        
        // Notifikasi jika ada item yang dihapus
        if (!empty($invalidItems)) {
            session()->flash('warning', "Beberapa item (" . implode(', ', $invalidItems) . ") dihapus karena tidak tersedia.");
        }

        // Jika keranjang jadi kosong semua
        if (empty($validCart)) {
            session()->forget('cart');
            return redirect()->route('customer.cart.index')->with('error', 'Keranjang kosong atau produk tidak valid.');
        }

        $cart = $validCart; 
        $expressOptions = [];
        $instantOptions = [];
        $tripayChannels = [];

        // ==========================================================
        // 2. LOGIKA ONGKIR (HANYA JIKA ADA PRODUK FISIK)
        // ==========================================================
        if ($hasPhysicalProduct && $firstValidStore) {
            $store = $firstValidStore;

            // Validasi Alamat
            if (empty($store->village)) {
                return redirect()->route('customer.cart.index')->with('error', 'Alamat toko penjual tidak lengkap.');
            }
            if (empty($user->village)) {
                return redirect()->route('customer.profile.edit')->with('warning', 'Mohon lengkapi alamat pengiriman Anda.');
            }

            // Cek Ongkir via KiriminAja
            try {
                $storeSearch = $store->village . ', ' . $store->district . ', ' . $store->regency . ', ' . $store->province;
                $userSearch  = $user->village . ', ' . $user->district . ', ' . $user->regency . ', ' . $user->province;
                
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                $userAddrRes  = $kiriminAja->searchAddress($userSearch);
                
                $storeAddr = $storeAddrRes['data'][0] ?? null;
                $userAddr  = $userAddrRes['data'][0] ?? null;

                if ($storeAddr && $userAddr) {
                    // Hitung berat HANYA produk fisik
                    $totalWeight = max(1000, (int) collect($cart)->filter(fn($i) => empty($i['is_ppob']))->sum(function($item) {
                        $p = Marketplace::find($item['product_id']);
                        return ($p->weight ?? 1000) * $item['quantity'];
                    }));
                    
                    // Hitung nilai barang fisik
                    $itemValue = (int) collect($cart)->filter(fn($i) => empty($i['is_ppob']))->sum(fn($item) => $item['price'] * $item['quantity']);
                    $category = $totalWeight >= 30000 ? 'trucking' : 'regular';

                    // Express Pricing
                    $expressRes = $kiriminAja->getExpressPricing(
                        $storeAddr['district_id'], $storeAddr['subdistrict_id'],
                        $userAddr['district_id'], $userAddr['subdistrict_id'],
                        $totalWeight, 5, 5, 5, $itemValue, null, $category, 1
                    );
                    
                    if (isset($expressRes['status']) && $expressRes['status']) {
                        $expressOptions['results'] = array_values(array_filter($expressRes['results'], fn($opt) => ($opt['cost'] ?? 0) > 0));
                    }

                    // Instant Pricing (Geocoding)
                    $storeLat = $store->latitude; $storeLng = $store->longitude;
                    $userLat = $user->latitude; $userLng = $user->longitude;
                    
                    if ((!$storeLat || !$storeLng) || (!$userLat || !$userLng)) {
                        $geoS = $this->geocode($storeSearch); if ($geoS) { $storeLat = $geoS['lat']; $storeLng = $geoS['lng']; }
                        $geoU = $this->geocode($userSearch); if ($geoU) { $userLat = $geoU['lat']; $userLng = $geoU['lng']; }
                    }

                    if ($storeLat && $storeLng && $userLat && $userLng) {
                        $instantRes = $kiriminAja->getInstantPricing(
                            $storeLat, $storeLng, $store->address_detail ?? $storeSearch,
                            $userLat, $userLng, $user->address_detail ?? $userSearch,
                            $totalWeight, $itemValue, 'motor'
                        );
                        
                        if (isset($instantRes['status']) && $instantRes['status']) {
                            foreach ($instantRes['result'] as $prov) {
                                if (isset($prov['costs'])) {
                                    foreach ($prov['costs'] as $cost) {
                                        $p = $cost['price']['total_price'] ?? 0;
                                        if ($p > 0) $instantOptions['results'][] = ['service' => $prov['name'], 'cost' => $p, 'final_price' => $p, 'etd' => '1-3 Jam', 'group' => 'instant'];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Log::error('Cek Ongkir Error', ['error' => $e->getMessage()]);
            }
        }

        // ==========================================================
        // 3. CHANNEL PEMBAYARAN (TRIPAY, DOKU, SALDO)
        // ==========================================================
        $paymentChannels = [];

        // A. SALDO AKUN
        $userBalance = $user->saldo ?? 0;
        $paymentChannels['saldo'] = [
            'code' => 'SALDO', 'name' => 'Saldo Akun', 'description' => 'Sisa saldo: Rp ' . number_format($userBalance), 'balance' => $userBalance, 'active' => true
        ];

        // B. TRIPAY
        try {
            $apiKey = config('tripay.api_key');
            $mode   = config('tripay.mode');
            $baseUrl = ($mode === 'production') ? 'https://tripay.co.id/api/merchant/payment-channel' : 'https://tripay.co.id/api-sandbox/merchant/payment-channel';
            
            $resTripay = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->timeout(5)->withoutVerifying()->get($baseUrl);
            if ($resTripay->successful() && $resTripay->json()['success']) {
                $rawTp = $resTripay->json()['data'];
                foreach($rawTp as $ch) {
                    if($ch['active']) $paymentChannels['tripay'][] = $ch;
                }
            }
        } catch (Exception $e) { Log::error('Tripay Error', ['e' => $e->getMessage()]); }

        // C. DOKU (Statis)
        $paymentChannels['doku'] = [
            ['code' => 'DOKU_CC', 'name' => 'Kartu Kredit', 'group' => 'DOKU'],
            ['code' => 'DOKU_VA', 'name' => 'Virtual Account', 'group' => 'DOKU']
        ];

        return view('customer.checkout.index', compact('cart', 'expressOptions', 'instantOptions', 'user', 'paymentChannels', 'hasPhysicalProduct'));
    }

    /**
     * ==========================================================
     * FUNGSI STORE (PROSES CHECKOUT)
     * ==========================================================
     */
    public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'shipping_method' => 'nullable|string', 
        ]);

        $cart = session()->get('cart', []);
        $user = Auth::user();

        if (empty($cart)) return redirect()->route('etalase.index')->with('error', 'Keranjang kosong.');

        // --- 1. VALIDASI & PEMISAHAN PPOB ---
        // Cek apakah ada minimal satu item PPOB
        $isPpobOrder = collect($cart)->contains(fn($i) => isset($i['is_ppob']) && $i['is_ppob']);
        
        // Jika TIDAK ADA PPOB (murni fisik), wajib ada alamat & shipping
        if (!$isPpobOrder) {
            if (empty($user->address_detail)) return redirect()->route('profile.edit')->with('warning', 'Lengkapi alamat.');
            if (empty($request->shipping_method)) return back()->with('error', 'Pilih metode pengiriman.');
        }

        DB::beginTransaction();
        try {
            // --- 2. KALKULASI TOTAL ---
            $subtotal = collect($cart)->sum(fn($d) => $d['price'] * $d['quantity']);
            $shipping_cost = 0;
            $insurance_cost = 0;
            $cod_add_cost = 0;
            $shipping_desc = 'Digital Delivery'; // Default jika PPOB

            if (!$isPpobOrder) {
                // Parsing Ongkir Fisik
                $parts = explode('-', $request->shipping_method);
                if (count($parts) >= 4) {
                    $shipping_desc = $parts[0] . ' - ' . $parts[1] . ' (' . $parts[2] . ')';
                    $shipping_cost = (int) $parts[3];
                }
            }

            $grand_total = $subtotal + $shipping_cost + $insurance_cost + $cod_add_cost;

            // --- 3. GENERATE INVOICE ---
            do {
                $invoiceNumber = ($isPpobOrder ? 'PPOB-' : 'INV-') . strtoupper(Str::random(9));
            } while (OrderMarketplace::where('invoice_number', $invoiceNumber)->exists());

            // --- 4. BUAT ORDER ---
            // Cari Store ID (hanya jika fisik)
            $storeId = null;
            if (!$isPpobOrder) {
                $firstItem = reset($cart);
                $prod = Marketplace::find($firstItem['product_id']);
                $storeId = $prod->store_id ?? null;
            }

            $order = new OrderMarketplace([
                'store_id'         => $storeId,
                'user_id'          => $user->id_pengguna,
                'invoice_number'   => $invoiceNumber,
                'subtotal'         => $subtotal,
                'shipping_cost'    => $shipping_cost,
                'total_amount'     => $grand_total,
                'shipping_method'  => $shipping_desc,
                'payment_method'   => $request->payment_method,
                // Jika Saldo -> Processing, Lainnya -> Pending
                'status'           => ($request->payment_method === 'saldo') ? 'processing' : 'pending', 
                'shipping_address' => $isPpobOrder ? 'Produk Digital' : ($user->address_detail ?? 'Alamat User'),
                'is_digital'       => $isPpobOrder ? 1 : 0
            ]);
            $order->save();

            // --- 5. BUAT ORDER ITEMS ---
            $orderItemsPayload = [];
            foreach ($cart as $key => $details) {
                OrderItemMerketplace::create([
                    'order_id' => $order->id,
                    'product_id' => $details['product_id'], // 0 jika PPOB (tidak masalah krn tidak ada relation constraint di DB)
                    'product_variant_id' => $details['variant_id'],
                    'quantity' => $details['quantity'],
                    'price' => $details['price'],
                    'sku' => $details['slug'] ?? 'PPOB',
                    'name' => $details['name'],
                    // Simpan data detail PPOB (customer_no, ref_id) ke kolom json/note jika ada di tabel Anda
                ]);

                // Kurangi stok jika fisik
                if (!$isPpobOrder && !empty($details['product_id'])) {
                    if ($details['variant_id']) { ProductVariant::where('id', $details['variant_id'])->decrement('stock', $details['quantity']); }
                    else { Marketplace::where('id', $details['product_id'])->decrement('stock', $details['quantity']); }
                }

                $orderItemsPayload[] = [
                    'sku' => $key,
                    'name' => $details['name'],
                    'price' => (int) $details['price'],
                    'quantity' => (int) $details['quantity']
                ];
            }

            if ($shipping_cost > 0) {
                $orderItemsPayload[] = ['sku' => 'SHIP', 'name' => 'Ongkir', 'price' => $shipping_cost, 'quantity' => 1];
            }

            // --- 6. PROSES PEMBAYARAN ---
            
            // A. PEMBAYARAN SALDO
            if ($request->payment_method === 'saldo') {
                if ($user->saldo < $grand_total) {
                    throw ValidationException::withMessages(['payment_method' => 'Saldo tidak mencukupi.']);
                }
                
                $user->decrement('saldo', $grand_total);
                Log::info("Order $invoiceNumber dibayar via SALDO.");
                
                // Jika FISIK, Panggil API KiriminAja (Pickup)
                if (!$isPpobOrder) {
                    $this->triggerKiriminAjaPickup($order, $kiriminAja);
                } 
                // Jika PPOB, Anda bisa trigger proses ke Digiflazz di sini (opsional)
                
            } 
            
            // B. PEMBAYARAN DOKU / TRIPAY (Online)
            else {
                // ... (Gunakan logika pembayaran online Tripay/Doku Anda sebelumnya) ...
                // Contoh Sederhana Tripay:
                $this->processTripayPayment($order, $user, $orderItemsPayload);
            }

            $order->save();
            DB::commit();
            session()->forget('cart');

            return redirect()->route('customer.checkout.invoice', ['invoice' => $order->invoice_number]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * =========================================================================
     * FUNGSI STORE (DIPERBAIKI DENGAN LOGIKA "POTONG SALDO")
     * =========================================================================
     */
    public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        $request->validate([
            'shipping_method' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        $user = Auth::user();

        if (empty($cart)) {
            return redirect()->route('etalase.index')->with('error', 'Terjadi kesalahan. Keranjang Anda kosong.');
        }

        if (empty($user->address_detail)) {
            return redirect()->route('profile.edit')->with('warning', 'Silakan lengkapi alamat pengiriman dahulu.');
        }

        // ==========================================================
        // VALIDASI ULANG KERANJANG (KODE ANDA SUDAH BENAR)
        // ==========================================================
        Log::info('Checkout Store: Memulai validasi ulang keranjang...', ['user_id' => $user->id_pengguna, 'cart_count' => count($cart)]);
        $validCart = []; $invalidItems = []; $firstValidStore = null; $firstValidProduct = null;
        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? null;
            if (!$productId) { $invalidItems[] = $item['name'] ?? '...'; continue; }
            $product = Marketplace::find($productId);
            if ($product && $product->store && $product->store->user) {
                $validCart[$cartKey] = $item;
                if ($firstValidStore === null) {
                    $firstValidStore = $product->store;
                    $firstValidProduct = $product; 
                }
            } else {
                $invalidItems[] = $item['name'] ?? $product->name ?? '...';
                Log::warning('Checkout Store Validation: Item tidak valid.', [ /* ... log ... */ ]);
            }
        }
        if (empty($validCart)) {
            session()->forget('cart');
            return redirect()->route('customer.cart.index') 
                ->with('error', 'Semua produk di keranjang Anda tidak lagi tersedia atau tidak valid.');
        }
        if (!empty($invalidItems)) {
            session()->put('cart', $validCart);
            $invalidItemNames = implode(', ', $invalidItems);
            return redirect()->route('customer.checkout.index') 
               ->with('warning', "Beberapa item ($invalidItemNames) tidak lagi tersedia. Harap periksa pesanan Anda.");
        }
        $cart = $validCart; $store = $firstValidStore; $firstProduct = $firstValidProduct;
        // ==========================================================
        // AKHIR VALIDASI ULANG
        // ==========================================================


        DB::beginTransaction();

        try {
            // --- 1. Kalkulasi Biaya (Kode Anda sudah benar) ---
            $subtotal = collect($cart)->sum(fn($details) => $details['price'] * $details['quantity']);
            $shippingParts = explode('-', $request->shipping_method);
            if (count($shippingParts) < 4) { throw new \Exception('Format metode pengiriman tidak valid.'); }
            $type = $shippingParts[0]; $courier = $shippingParts[1]; $service = $shippingParts[2];
            $shipCost = (int) ($shippingParts[3] ?? 0);
            $codFeeApi = (count($shippingParts) >= 6) ? (int) end($shippingParts) : 0;
            $asrCost = (count($shippingParts) >= 5) ? (int) $shippingParts[count($shippingParts) - ($codFeeApi > 0 ? 2 : 1)] : 0;
            $shipping_type = $type; $shipping_cost = $shipCost; $insurance_cost = $asrCost;
            
            // --- 3. Kalkulasi Total (Kode Anda sudah benar) ---
            $itemTypeFirstProduct = (int) $firstProduct->jenis_barang; 
            $mandatoryTypes = [1, 3, 4, 8];
            $isMandatoryInsurance = in_array($itemTypeFirstProduct, $mandatoryTypes);
            $useInsurance = ($isMandatoryInsurance && $insurance_cost > 0) || (!$isMandatoryInsurance && $insurance_cost > 0);
            $base_total = $subtotal + $shipping_cost;
            $applied_insurance_cost = 0;
            if ($useInsurance) {
                 $base_total += $insurance_cost;
                 $applied_insurance_cost = $insurance_cost;
            }
            $cod_add_cost = 0;
            if ($request->payment_method === 'cod') {
                if ($shipping_type !== 'express' && $shipping_type !== 'cargo' && $shipping_type !== 'regular') {
                     return redirect()->back()->with('error', 'COD hanya tersedia untuk pengiriman Express atau Cargo.');
                }
                if ($codFeeApi > 0) { $cod_add_cost = $codFeeApi; } 
                else { $codFeePercentage = 0.03; $cod_add_cost = ceil($base_total * $codFeePercentage); }
            }
            $grand_total = $base_total + $cod_add_cost;

            // --- 4. Buat Order & Order Items (Kode Anda sudah benar) ---
            do {
                $invoiceNumber = 'SCK-AGEN-' . strtoupper(Str::random(8));
            } while (OrderMarketplace::where('invoice_number', $invoiceNumber)->exists() || Pesanan::where('nomor_invoice', $invoiceNumber)->exists());

            $order = new OrderMarketplace([
                 'store_id'     => $store->id, 
                 'user_id'        => $user->id_pengguna,
                 'invoice_number' => $invoiceNumber,
                 'subtotal'       => $subtotal,
                 'shipping_cost'    => $shipping_cost,
                 'insurance_cost' => $applied_insurance_cost,
                 'cod_fee'        => $cod_add_cost,
                 'total_amount'   => $grand_total,
                 'shipping_method' => $request->shipping_method,
                 'payment_method' => $request->payment_method,
                 // PERBAIKAN: Status untuk 'saldo' sama seperti 'cod'
                 'status'         => ($request->payment_method === 'cod' || $request->payment_method === 'cash' || $request->payment_method === 'saldo') ? 'processing' : 'pending',
                 'shipping_address'=> $user->address_detail ?? 'Alamat tidak diatur',
            ]);
            $order->save();

            $orderItemsPayload = [];
            foreach ($cart as $cartKey => $details) { 
                 $realProductId = $details['product_id']; $realVariantId = $details['variant_id'];
                 // ==========================================================
                // INI PERBAIKANNYA (BUKAN KOMENTAR '...data...' LAGI)
                // ==========================================================
                OrderItemMerketplace::create([ 
                    'order_id' => $order->id,
                    'product_id' => $realProductId, 
                    'product_variant_id' => $realVariantId, 
                    'quantity' => $details['quantity'], 
                    'price' => $details['price'], 
                ]);
                // ==========================================================
                 if ($realVariantId) { $variant = ProductVariant::find($realVariantId); if ($variant) $variant->decrement('stock', $details['quantity']); }
                 else { $product = Marketplace::find($realProductId); if ($product) $product->decrement('stock', $details['quantity']); }
                 $orderItemsPayload[] = [ 'sku' => $cartKey, 'name' => $details['name'], 'price' => $details['price'], 'quantity' => $details['quantity'],];
            }
            $orderItemsPayload[] = [ 'sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1 ];
            if($applied_insurance_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'INSURANCE', 'name' => 'Asuransi', 'price' => $applied_insurance_cost, 'quantity' => 1 ]; }
            if($cod_add_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'CODFEE', 'name' => 'Biaya COD', 'price' => $cod_add_cost, 'quantity' => 1 ]; }

            
            $paymentUrl = null; 

            // ==========================================================
            // PERBAIKAN: LOGIKA PEMBAYARAN DIPISAH
            // ==========================================================

            // --- 5A. Logika KiriminAja untuk COD/Cash/Potong Saldo ---
            if ($request->payment_method === 'cod' || $request->payment_method === 'cash' || $request->payment_method === 'saldo') {
                
                // PERBAIKAN: Cek Saldo DULU
                if ($request->payment_method === 'saldo') {
                    if ($user->saldo < $grand_total) {
                        throw ValidationException::withMessages(['payment_method' => 'Saldo Anda tidak mencukupi. Saldo Anda: Rp' . number_format($user->saldo)]);
                    }
                    // Langsung potong saldo
                    $user->decrement('saldo', $grand_total);
                    // (Opsional) catat transaksi saldo
                    // Transaction::create([...]); 
                    Log::info('Saldo dipotong untuk order ' . $order->invoice_number, ['user_id' => $user->id_pengguna, 'amount' => $grand_total]);
                }
                
                // --- Kode KiriminAja Anda (sudah benar, tinggal copy-paste) ---
                $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province; $userSearch = $user->village . ', ' . $user->regency . ', ' . $user->province;
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch); $userAddrRes = $kiriminAja->searchAddress($userSearch);
                $storeAddr = $storeAddrRes['data'][0] ?? null; $userAddr = $userAddrRes['data'][0] ?? null;
                $storeDistrictId = $storeAddr['district_id'] ?? null; $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                $userDistrictId = $userAddr['district_id'] ?? null; $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                $storeLat = $store->latitude; $storeLng = $store->longitude; $userLat = $user->latitude; $userLng = $user->longitude;
                if (!$storeLat || !$storeLng) { $geo = $this->geocode($storeSearch); if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; } }
                if (!$userLat || !$userLng) { $geo = $this->geocode($userSearch); if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; } }
                $schedule = $kiriminAja->getSchedules();
                $pickupTime = $schedule['clock'] ?? null; // Ambil schedule
                
                $totalWeight = (int) collect($cart)->sum(function($item) { $product = Marketplace::find($item['product_id']); return ($product->weight ?? 1000) * $item['quantity']; }); 
                $finalWeight = max(1000, $totalWeight); $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

                $packages = $order->items()->with('product', 'variant')->get()->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipping_cost, $courier, $service, $useInsurance, $user, $request, $grand_total) {
                    $product = $item->product; if (!$product) return null; $variant = $item->variant;
                    $weight = $product->weight ?? 1000; $width = $product->width ?? 5; $height = $product->height ?? 5; $length = $product->length ?? 5;
                    $jenis_barang = $product->jenis_barang ?? 1;
                    $itemName = $product->name . ($variant ? ' (' . ($variant->combination_string ? str_replace(';', ', ', $variant->combination_string) : $variant->sku_code) . ')' : '');
                    
                    // PERBAIKAN: Jika bayar pakai 'saldo', COD amount = 0
                    $codAmount = ($request->payment_method === 'cod') ? $grand_total : 0;
                    
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
                        'cod' => $codAmount, // <-- LOGIKA COD DIPERBAIKI
                    ];
                })->filter()->values()->toArray();

                if (empty($packages)) { throw new \Exception('Tidak ada item valid dalam pesanan untuk dikirim.'); }
                
                if (empty($packages)) { throw new \Exception('Tidak ada item valid dalam pesanan untuk dikirim.'); }
                
                // ==========================================================
                // GANTI DARI SINI
                // ==========================================================
                if ($shipping_type === 'express' || $shipping_type === 'cargo' || $shipping_type === 'regular') {
                    if (!$storeDistrictId || !$storeSubdistrictId || !$userDistrictId || !$userSubdistrictId) throw new \Exception('ID Kecamatan/Kelurahan tidak valid.');
                    
                    // INI DATA YANG HILANG (BAGIAN 1)
                    $payload = [
                        'address' => $store->address_detail, 
                        'phone' => $store->user->no_wa,
                        'kecamatan_id' => $storeDistrictId, 
                        'kelurahan_id' => $storeSubdistrictId,
                        'latitude' => $storeLat, 
                        'longitude' => $storeLng,
                        'packages' => $packages, // <-- Ini sudah ada di kode Anda
                        'name' => $store->name,
                        'zipcode' => $store->postal_code ?? '63271',
                        'platform_name' => 'TOKOSANCAKA.COM',
                        'category' => $category,
                    ];

                    // ==========================================================
                    // PERBAIKAN FATAL "SCHEDULE WAJIB DIISI"
                    // Hanya tambahkan key 'schedule' JIKA ada nilainya
                    // ==========================================================
                    if ($pickupTime) { // $pickupTime didapat dari $schedule['clock'] ?? null
                        $payload['schedule'] = $pickupTime;
                    }
                    // ==========================================================

                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                    Log::info('RESPON JSON CREATE ORDER:', $kiriminResponse);

                } elseif ($shipping_type === 'instant') {
                    if (!$storeLat || !$storeLng || !$userLat || !$userLng) throw new \Exception('Koordinat tidak ditemukan.');
                    $firstPackageItem = $packages[0];
                    
                    // INI DATA YANG HILANG (BAGIAN 2)
                    $payload = [
                        'service' => $courier, 
                        'service_type' => $service, 
                        'vehicle' => 'motor',
                        'order_prefix' => $order->invoice_number,
                        'packages' => [[
                            'destination_name' => $user->nama_lengkap, 
                            'destination_phone' => $user->no_wa,
                            'destination_lat' => $userLat, 
                            'destination_long' => $userLng,
                            'destination_address' => $order->shipping_address,
                            'origin_name' => $store->name, 
                            'origin_phone' => $store->user->no_wa,
                            'origin_lat' => $storeLat, 
                            'origin_long' => $storeLng,
                            'origin_address' => $store->address_detail,
                            'shipping_price' => (int) $shipping_cost,
                            'item' => [
                                'name' => 'Pesanan ' . $order->invoice_number,
                                'description' => $firstPackageItem['item_name'] ?? 'Pesanan dari toko',
                                'price' => $order->subtotal, 
                                'weight' => $finalWeight,
                            ]
                        ]]
                    ];

                    $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                    Log::info('RESPON JSON CREATE ORDER (INSTANT):', $kiriminResponse);
                
                } else {
                    throw new \Exception('Tipe pengiriman tidak didukung.');
                }
                // ==========================================================
                // GANTI SAMPAI SINI
                // ==========================================================
                
                if (empty($kiriminResponse['status']) || $kiriminResponse['status'] !== true) {
                    $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order KiriminAja.');
                    throw new \Exception('Gagal membuat order pengiriman: ' . $errorMessage);
                }
                
                $resi = $kiriminResponse['packages'][0]['awb'] ?? ($kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null));
                if ($resi) { $order->shipping_resi = $resi; }
                
                try {
                     broadcast(new AdminNotificationEvent(
                         'Pesanan Baru (COD/Cash/Saldo)',
                         "Pesanan #{$order->invoice_number} (Rp " . number_format($order->total_amount) . ") telah masuk.",
                         route('admin.orders.show', $order->id)
                     ));
                } catch (Exception $e) { /* ... log error ... */ }

            } 
            // --- 5B. Logika Pembayaran Online (Doku / Tripay) ---
            else 
            {
                $paymentGateway = 'tripay'; // Default
                if (strtoupper($request->payment_method) === 'DOKU_JOKUL') {
                    $paymentGateway = 'doku';
                }
                // Jika bukan DOKU_JOKUL, berarti itu adalah channel Tripay (cth: QRIS, BCAVA)
                
                if ($paymentGateway === 'doku') {
    Log::info('Memulai proses DOKU (Jokul) Marketplace untuk ' . $order->invoice_number);
    $vendorSacId = $store->doku_sac_id;
    
    if (empty($vendorSacId)) {
        throw new \Exception('Toko ini belum terdaftar di sistem pembayaran DOKU.');
    }

    $dokuService = new DokuJokulService();

    // ==========================================================
    // INI DATA YANG HILANG
    // ==========================================================
    $customerData = [
        'name'  => $user->nama_lengkap, // $user diambil dari atas
        'email' => $user->email,
        'phone' => $user->no_wa,
    ];
    // ==========================================================

    $additionalInfo = [
        'account' => [
            'id' => $vendorSacId,
        ],
    ];

    $paymentUrl = $dokuService->createPayment(
        $order->invoice_number,
        $grand_total,
        $customerData, // <-- Sekarang sudah berisi data
        $orderItemsPayload,
        $additionalInfo
    );

    if (empty($paymentUrl)) {
        throw new \Exception('Gagal membuat transaksi pembayaran DOKU.');
    }

    $order->payment_url = $paymentUrl;
}


                else { // Ini adalah TRIPAY
                    Log::info('Memulai proses TRIPAY Marketplace untuk ' . $order->invoice_number);
                    
                    $apiKey       = config('tripay.api_key');
                    $privateKey   = config('tripay.private_key');
                    $merchantCode = config('tripay.merchant_code');
                    $mode         = config('tripay.mode');
                    
                    // PERBAIKAN: 'method' sekarang adalah channel yang valid (cth: QRIS), bukan 'tripay'
                    $payload = [
                        'method'         => $request->payment_method, // <-- INI SEKARANG SUDAH BENAR
                        'merchant_ref'   => $order->invoice_number,
                        'amount'         => $grand_total,
                        'customer_name'  => $user->nama_lengkap,
                        'customer_email' => $user->email,
                        'customer_phone' => $user->no_wa,
                        'order_items'    => $orderItemsPayload,
                        'expired_time'   => time() + (1 * 60 * 60),
                        'signature'      => hash_hmac('sha256', $merchantCode.$order->invoice_number.$grand_total, $privateKey),
                        'return_url'     => route('customer.checkout.invoice', ['invoice' => $order->invoice_number]),
                    ];

                    $baseUrl = ($mode === 'production')
                        ? 'https://tripay.co.id/api/transaction/create'
                        : 'https://tripay.co.id/api-sandbox/transaction/create';

                    $tripayResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                            ->timeout(60)->withoutVerifying()->post($baseUrl, $payload);
                    
                    if ($tripayResponse->successful() && isset($tripayResponse->json()['success']) && $tripayResponse->json()['success'] === true) {
                        $tripayData = $tripayResponse->json()['data'];
                        
                        // Logika 'paymentUrl' Anda sudah benar
                        $paymentMethod = $request->payment_method;
                        if (str_contains($paymentMethod, 'QRIS')) {
                            $paymentUrl = $tripayData['qr_url'] ?? $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? null;
                        } else {
                            $paymentUrl = $tripayData['pay_code'] ?? $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? null;
                        }
                        if ($paymentUrl === null) {
                            $paymentUrl = $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? $tripayData['qr_url'] ?? $tripayData['pay_code'] ?? null;
                        }
                        
                        $order->payment_url = $paymentUrl;
                    } else {
                        Log::error('Gagal membuat transaksi Tripay', ['response' => $tripayResponse->body()]);
                        // INI DIA ERROR ANDA DARI SEBELUMNYA
                        $errorMessage = $tripayResponse->json()['message'] ?? 'Gagal menghubungi payment gateway.';
                        throw new \Exception('Gagal membuat transaksi pembayaran: ' . $errorMessage);
                    }
                }
            }
            
            $order->save();
            DB::commit();
            
            session()->forget('cart');
            return redirect()->route('customer.checkout.invoice', ['invoice' => $order->invoice_number]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Checkout Gagal (Validasi): ' . $e->getMessage(), ['errors' => $e->errors()]);
            // Penting: Redirect kembali ke checkout.index, BUKAN back()
            return redirect()->route('customer.checkout.index')->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Gagal Total (Exception): ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            return redirect()->route('customer.checkout.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    /**
     * Menampilkan halaman invoice setelah checkout.
     */
    public function invoice($invoice)
    {
        if (!$invoice) {
            return redirect()->route('customer.checkout.index')->with('error', 'Invoice tidak ditemukan.');
        }
        
        // GANTI: Gunakan OrderMarketplace
        $order = OrderMarketplace::with('items.product', 'items.variant', 'store', 'user')
                                     ->where('invoice_number', $invoice)
                                     ->where('user_id', Auth::id())
                                     ->firstOrFail();

        return view('customer.checkout.invoice', compact('order'));
    }

    /**
     * =========================================================================
     * FUNGSI CALLBACK (DIPERBAIKI UNTUK 'POTONG SALDO')
     * =========================================================================
     */
    public function TripayCallback(Request $request)
    {
        // ... (Kode TripayCallback Anda sudah benar) ...
        // ... Tidak perlu diubah ...
    }


    public function handleDokuCallback(array $data)
    {
        // ... (Kode handleDokuCallback Anda sudah benar) ...
        // ... Tidak perlu diubah ...
    }


    /**
     * =========================================================================
     * FUNGSI PROSESOR CALLBACK (TRIPAY & DOKU)
     * =========================================================================
     * (Logika KiriminAja untuk 'PAID' orders ada di sini)
     */
    public function processOrderCallback($merchantRef, $status, $callbackData)
    {
        Log::info('Processing Order Callback (SCK-AGEN-)...', ['ref' => $merchantRef, 'status' => $status]); 
        
        $order = OrderMarketplace::with('items.product.store.user', 'items.variant', 'user')
                                     ->where('invoice_number', $merchantRef)
                                     ->lockForUpdate()
                                     ->first();

        if (!$order) {
            Log::error('Order Callback (SCK-AGEN-): Order not found.', ['ref' => $merchantRef]); 
            return;
        }

        if ($order->status !== 'pending') {
            Log::info('Order Callback (SCK-AGEN-): Order already processed.', ['ref' => $merchantRef, 'current_status' => $order->status]); 
            return;
        }

        if ($status === 'PAID') {
            $order->status = 'paid';
            $order->save();

            // --- Logika Kirim ke KiriminAja SETELAH LUNAS ---
            try {
                $kiriminAja = app(KiriminAjaService::class);

                $shippingParts = explode('-', $order->shipping_method);
                if (count($shippingParts) < 4) throw new Exception('Format shipping_method di order tidak valid');
                $type = $shippingParts[0]; $courier = $shippingParts[1]; $service = $shippingParts[2];
                $shipCost = (int) $order->shipping_cost;
                $insurance_cost = (int) $order->insurance_cost;

                $store = $order->store; $user = $order->user;
                if (!$store || !$user || !$store->user) throw new Exception('Data store atau user pada order tidak valid.');

                $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province;
                $userSearch  = $user->village . ', ' . $user->regency . ', ' . $user->province;
                $storeLat = $store->latitude; $storeLng = $store->longitude; $userLat = $user->latitude; $userLng = $user->longitude;
                if (!$storeLat || !$storeLng) { $geo = $this->geocode($storeSearch); if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; } }
                if (!$userLat || !$userLng) { $geo = $this->geocode($userSearch); if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; } }
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch); $userAddrRes = $kiriminAja->searchAddress($userSearch);
                $storeAddr = $storeAddrRes['data'][0] ?? null; $userAddr = $userAddrRes['data'][0] ?? null;
                $storeDistrictId = $storeAddr['district_id'] ?? null; $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                $userDistrictId = $userAddr['district_id'] ?? null; $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                $schedule = $kiriminAja->getSchedules();
                
                $calculatedWeight = $order->items->sum(function($item) { 
                    if(!$item->product) return 1000;
                    return ($item->product->weight ?? 1000) * $item->quantity; 
                });
                $finalWeight = max(1000, $calculatedWeight);
                $category = $finalWeight >= 30000 ? 'trucking' : 'regular';
                $firstItem = $order->items->first();
                if (!$firstItem || !$firstItem->product) throw new Exception('Item atau produk pertama tidak ditemukan di order.');
                $itemTypeFirstProduct = (int) ($firstItem->product->jenis_barang ?? 1);
                $mandatoryTypes = [1, 3, 4, 8];
                $isMandatoryInsurance = in_array($itemTypeFirstProduct, $mandatoryTypes);
                $useInsurance = $isMandatoryInsurance || ($insurance_cost > 0);

                $packages = $order->items->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipCost, $courier, $service, $useInsurance, $user) { 
                    if (!$item->product) return null;
                    $product = $item->product; $variant = $item->variant;
                    $weight = $product->weight ?? 1000; $width = $product->width ?? 5; $height = $product->height ?? 5; $length = $product->length ?? 5;
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
                        'shipping_cost' => $shipCost, 'service' => $courier, 'service_type' => $service,
                        'insurance_amount' => $useInsurance ? ($item->price * $item->quantity) : 0,
                        'item_name' => $itemName, 'package_type_id' => (int) $jenis_barang,
                        'cod' => 0, // Pembayaran online, jadi 0
                    ];
                })->filter()->toArray();

                $kiriminResponse = null;
                if (empty($packages)) { throw new \Exception('Tidak ada item valid dalam pesanan untuk dikirim.'); }
                
                // ==========================================================
                // GANTI DARI SINI
                // ==========================================================
                if ($shipping_type === 'express' || $shipping_type === 'cargo' || $shipping_type === 'regular') {
                    if (!$storeDistrictId || !$storeSubdistrictId || !$userDistrictId || !$userSubdistrictId) throw new \Exception('ID Kecamatan/Kelurahan tidak valid.');
                    
                    // INI DATA YANG HILANG (BAGIAN 1)
                    $payload = [
                        'address' => $store->address_detail, 
                        'phone' => $store->user->no_wa,
                        'kecamatan_id' => $storeDistrictId, 
                        'kelurahan_id' => $storeSubdistrictId,
                        'latitude' => $storeLat, 
                        'longitude' => $storeLng,
                        'packages' => $packages, // <-- Ini sudah ada di kode Anda
                        'name' => $store->name,
                        'zipcode' => $store->postal_code ?? '63271',
                        'platform_name' => 'TOKOSANCAKA.COM',
                        'category' => $category,
                    ];

                    // ==========================================================
                    // PERBAIKAN FATAL "SCHEDULE WAJIB DIISI"
                    // Hanya tambahkan key 'schedule' JIKA ada nilainya
                    // ==========================================================
                    if ($pickupTime) { // $pickupTime didapat dari $schedule['clock'] ?? null
                        $payload['schedule'] = $pickupTime;
                    }
                    // ==========================================================

                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                    Log::info('RESPON JSON CREATE ORDER:', $kiriminResponse);
                
            } elseif ($type === 'instant') {
                    if (!$storeLat || !$storeLng || !$userLat || !$userLng) { throw new \Exception('Koordinat tidak valid untuk KiriminAja Instant.'); }
                    $totalItemValue = $order->items->sum(fn($item) => $item->price * $item->quantity);
                    $firstPackageItem = $packages[0];

                    // ==========================================================
                    // INI DATA YANG HILANG
                    // ==========================================================
                    $payload = [
                        'service' => $courier, 
                        'service_type' => $service, 
                        'vehicle' => 'motor',
                        'order_prefix' => $order->invoice_number,
                        'packages' => [[
                            'destination_name' => $user->nama_lengkap, 
                            'destination_phone' => $user->no_wa,
                            'destination_lat' => $userLat, 
                            'destination_long' => $userLng,
                            'destination_address' => $order->shipping_address,
                            'origin_name' => $store->name, 
                            'origin_phone' => $store->user->no_wa,
                            'origin_lat' => $storeLat, 
                            'origin_long' => $storeLng,
                            'origin_address' => $store->address_detail,
                            'shipping_price' => (int) $shipCost, // $shipCost dari atas
                            'item' => [
                                'name' => 'Pesanan ' . $order->invoice_number,
                                'description' => $firstPackageItem['item_name'] ?? 'Pesanan dari toko',
                                'price' => $totalItemValue, // Total nilai barang
                                'weight' => $finalWeight, // Berat total
                            ]
                        ]]
                    ];
                    // ==========================================================
                    // AKHIR DATA YANG HILANG
                    // ==========================================================
                    $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                    Log::info('RESPON JSON CREATE ORDER (Callback-Instant):', $kiriminResponse);
                } else {
                    throw new \Exception('Tipe pengiriman tidak didukung untuk callback order ini.');
                }

                if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                    $resi = $kiriminResponse['packages'][0]['awb'] ?? ($kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null));
                    if ($resi) { $order->shipping_reference = $resi; }
                    $order->status = 'processing';
                    Log::info('Order Callback (SCK-AGEN-): KiriminAja order created successfully.', ['ref' => $merchantRef, 'resi' => $resi]); 
                    
                    try {
                         broadcast(new AdminNotificationEvent(
                             'Pesanan Lunas ('. ($status === 'PAID' ? 'Tripay' : 'DOKU') .')', 
                             "Pesanan #{$order->invoice_number} (Rp " . number_format($order->total_amount) . ") telah dibayar.",
                             route('admin.orders.show', $order->id)
                         ));
                    } catch (Exception $e) { /* ... log error ... */ }
                } else {
                    Log::error("Order Callback (SCK-AGEN-): KiriminAja order creation FAILED.", ['ref' => $merchantRef, 'response' => $kiriminResponse]); 
                }
                $order->save();

            } catch (\Exception $e) {
                Log::error("Order Callback (SCK-AGEN-): Exception during KiriminAja process.", [ 'ref' => $merchantRef, 'error' => $e->getMessage(), 'line' => $e->getLine() ]); 
            }
            
        } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
            $order->status = ($status === 'EXPIRED') ? 'expired' : 'failed';
            $order->save();
            Log::info('Order Callback (SCK-AGEN-): Order status updated to failed/expired.', ['ref' => $merchantRef, 'status' => $order->status]); 
        } else {
            Log::warning('Order Callback (SCK-AGEN-): Received unknown status.', ['ref' => $merchantRef, 'status' => $status]); 
        }
    }

} // Akhir Class