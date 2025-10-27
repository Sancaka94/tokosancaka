 <?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
// use App\Http\Controllers\DanaController; // Komentar jika tidak dipakai
use Illuminate\Support\Facades\Log;
use App\Models\TopUp;
use App\Models\User;
use App\Events\SaldoUpdated;
use App\Events\AdminNotificationEvent;
use App\Services\KiriminAjaService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use App\Models\Pesanan; // <-- Tetap import Model Pesanan jika diperlukan di tempat lain
use Illuminate\Support\Str;
use Exception; // <-- Tambahkan Exception

// IMPORT SEMUA CONTROLLER YANG MEMILIKI FUNGSI PROSESOR CALLBACK
use App\Http\Controllers\Admin\PesananController as AdminPesananController; // <-- Alias agar tidak bentrok
use App\Http\Controllers\Customer\PesananController as CustomerPesananController; // <-- PENTING
use App\Http\Controllers\CustomerOrderController; // <-- PENTING
use App\Http\Controllers\Customer\TopUpController; // <-- PENTING

class CheckoutController extends Controller
{

    public function geocode($address){
        // ... (fungsi geocode tetap sama) ...
        $url = "https://nominatim.openstreetmap.org/search";

        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (support@tokosancaka.com)',
            'Accept'     => 'application/json',
        ])->get($url, [
            'q'      => $address,
            'format' => 'json',
            'limit'  => 1,
            'countrycodes' => 'id' // Tambahkan filter negara jika perlu
        ]);

        if ($response->successful() && !empty($response[0])) {
            return [
                'lat' => (float) $response[0]['lat'],
                'lng' => (float) $response[0]['lon'],
            ];
        }
         Log::warning('Geocoding failed or returned empty', ['address' => $address, 'response' => $response->body()]);
        return null;
    }


    /**
     * Menampilkan halaman checkout.
     */
    public function index(KiriminAjaService $kiriminAja)
    {
        // ... (fungsi index tetap sama) ...
        if (!Auth::check()) {
            return redirect()->route('customer.login')
                ->with('info', 'Anda harus login untuk melanjutkan ke checkout.');
        }

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('info', 'Keranjang Anda kosong. Silakan belanja terlebih dahulu.');
        }

        $user = Auth::user();

        $firstCartItemData = reset($cart);
        $productId = $firstCartItemData['product_id'] ?? null;
        $firstProduct = $productId ? Product::find($productId) : null;

        if (!$firstProduct || !$firstProduct->store) { // Cek juga relasi store
            session()->forget('cart');
            return redirect()->route('cart.index')
                ->with('error', 'Produk atau toko di keranjang Anda tidak lagi tersedia. Keranjang telah dikosongkan.');
        }

        $store = $firstProduct->store;

        // Validasi Alamat Toko
        if (empty($store->village) || empty($store->district) || empty($store->regency) || empty($store->province)) {
             Log::error('Alamat toko tidak lengkap', ['store_id' => $store->id]);
            return redirect()->route('cart.index')
                ->with('error', 'Alamat toko asal pengiriman tidak lengkap. Silakan hubungi penjual.');
        }

        // Validasi Alamat User
        if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province)) {
             Log::warning('Alamat user tidak lengkap', ['user_id' => $user->id_pengguna]);
            return redirect()->route('profile.edit') // Arahkan ke edit profil
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
            $weight = $product->weight ?? 1000; // Default 1kg jika tidak ada
            return $weight * $item['quantity'];
        });
        $finalWeight = max(1000, $totalWeight); // Minimal 1kg

        $itemValue   = (int) collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

        $defaultLength = $firstProduct->length ?? 10;
        $defaultWidth  = $firstProduct->width  ?? 10;
        $defaultHeight = $firstProduct->height ?? 10;

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
                 null, // Biarkan KiriminAja return semua kurir
                 $category,
                 1 // Asumsikan cek ongkir dengan asuransi (nilai barang akan menentukan perlu/tidaknya)
             );
              Log::info('Express Pricing Result:', ['options' => $expressOptions]); // Log hasil
        } catch (Exception $e) {
             Log::error('Gagal mendapatkan ongkir Express/Cargo', ['error' => $e->getMessage()]);
             // Jangan redirect, biarkan halaman tampil tanpa opsi express
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
                     $finalWeight, $itemValue, 'motor' // Asumsi motor
                 );
                  Log::info('Instant Pricing Result:', ['options' => $instantOptions]); // Log hasil
            } catch (Exception $e) {
                 Log::error('Gagal mendapatkan ongkir Instant/Sameday', ['error' => $e->getMessage()]);
                 // Jangan redirect, biarkan halaman tampil tanpa opsi instant
            }
        } else {
             Log::warning('Koordinat tidak lengkap untuk cek ongkir Instant', ['storeLL' => "$storeLat,$storeLng", 'userLL' => "$userLat,$userLng"]);
        }

        // Filter opsi yang tidak valid (jika API return error di dalam result atau harga 0)
        if (isset($expressOptions['status']) && $expressOptions['status'] === true && isset($expressOptions['results'])) {
             $expressOptions['results'] = array_filter($expressOptions['results'], fn($opt) => !empty($opt['final_price']) && $opt['final_price'] > 0);
        } else {
            $expressOptions = ['status' => false, 'text' => 'Gagal mengambil opsi Express/Cargo.', 'results' => []]; // Set default jika gagal total
             Log::error('Hasil API Express Pricing tidak valid', ['response' => $expressOptions]);
        }

        if (isset($instantOptions['status']) && $instantOptions['status'] === true && isset($instantOptions['results'])) {
             $instantOptions['results'] = array_filter($instantOptions['results'], fn($opt) => !empty($opt['final_price']) && $opt['final_price'] > 0);
        } else {
             $instantOptions = ['status' => false, 'text' => 'Gagal mengambil opsi Instant/Sameday.', 'results' => []]; // Set default jika gagal total
             Log::error('Hasil API Instant Pricing tidak valid atau koordinat tidak ada', ['response' => $instantOptions]);
        }

        return view('checkout.index', compact('cart', 'expressOptions', 'instantOptions', 'user')); // Kirim juga $user
    }


    /**
     * Memproses dan menyimpan pesanan baru.
     */
    public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        // ... (fungsi store - REVISI PENTING DI BAGIAN COMMIT DAN REDIRECT) ...
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

        DB::beginTransaction();

        try {
            $subtotal = collect($cart)->sum(fn($details) => $details['price'] * $details['quantity']);

            // Perbaikan Parsing Shipping Method
            $shippingParts = explode('-', $request->shipping_method);
            if (count($shippingParts) < 4) {
                 throw new \Exception('Format metode pengiriman tidak valid.');
            }
            $type = $shippingParts[0];
            $courier = $shippingParts[1];
            $service = $shippingParts[2];
            $shipCost = (int) ($shippingParts[3] ?? 0);
             // Ambil biaya dari BELAKANG untuk fleksibilitas
            $codFeeApi = (count($shippingParts) >= 6) ? (int) end($shippingParts) : 0;
            $asrCost = (count($shippingParts) >= 5) ? (int) $shippingParts[count($shippingParts) - ($codFeeApi > 0 ? 2 : 1)] : 0; // Asuransi sebelum COD fee atau sebelum akhir

            $shipping_type = $type;
            $shipping_cost = $shipCost;
            $insurance_cost = $asrCost;

            $firstCartItemData = reset($cart);
            $productId = $firstCartItemData['product_id'] ?? null;
            $firstProduct = $productId ? Product::find($productId) : null;

            if (!$firstProduct || !$firstProduct->store || !$firstProduct->store->user) { // Pastikan relasi ada
                throw new \Exception('Produk atau data penjual di keranjang tidak valid.');
            }

            $itemTypeFirstProduct = (int) $firstProduct->jenis_barang;
            $mandatoryTypes = [1, 3, 4, 8];
            $isMandatoryInsurance = in_array($itemTypeFirstProduct, $mandatoryTypes);
            // Gunakan asuransi HANYA jika wajib DAN ada biayanya di API, ATAU jika tidak wajib tapi ADA biayanya di API
            $useInsurance = ($isMandatoryInsurance && $insurance_cost > 0) || (!$isMandatoryInsurance && $insurance_cost > 0);

            $base_total = $subtotal + $shipping_cost;
            $applied_insurance_cost = 0; // Biaya asuransi yang benar-benar diterapkan
            if ($useInsurance) {
                 $base_total += $insurance_cost;
                 $applied_insurance_cost = $insurance_cost;
            }

            $cod_add_cost = 0; // Biaya COD yang benar-benar diterapkan
            if ($request->payment_method === 'cod') {
                if ($shipping_type !== 'express' && $shipping_type !== 'cargo') {
                    return redirect()->back()->with('error', 'COD hanya tersedia untuk pengiriman Express atau Cargo.');
                }
                if ($codFeeApi > 0) {
                     $cod_add_cost = $codFeeApi;
                } else {
                     $codFeePercentage = 0.03;
                     $cod_add_cost = ceil($base_total * $codFeePercentage);
                     Log::warning('COD Fee dari API tidak ditemukan, menggunakan perhitungan manual.', ['shipping_method' => $request->shipping_method]);
                }
            }

            $grand_total = $base_total + $cod_add_cost;
            $store = $firstProduct->store;

            do {
                $invoiceNumber = 'ORD-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists() || Pesanan::where('nomor_invoice', $invoiceNumber)->exists());

            // Buat Order DULU (tanpa commit)
            $order = new Order([ // Gunakan new bukan create
                'store_id'        => $store->id,
                'user_id'         => $user->id_pengguna,
                'invoice_number'  => $invoiceNumber,
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shipping_cost,
                'insurance_cost'  => $applied_insurance_cost,
                'cod_fee'         => $cod_add_cost,
                'total_amount'    => $grand_total,
                'shipping_method' => $request->shipping_method,
                'payment_method'  => $request->payment_method,
                'status'          => ($request->payment_method === 'cod' || $request->payment_method === 'cash') ? 'processing' : 'pending',
                'shipping_address'=> $user->address_detail ?? 'Alamat tidak diatur',
            ]);
             $order->save(); // Simpan order utama

            $orderItemsPayload = [];

            // Simpan Order Items & Kurangi Stok
            foreach ($cart as $cartKey => $details) {
                // ... (logika simpan order item & kurangi stok tetap sama) ...
                $realProductId = $details['product_id']; $realVariantId = $details['variant_id'];
                OrderItem::create([ /* ... */ 'order_id' => $order->id, 'product_id' => $realProductId, 'product_variant_id' => $realVariantId, 'quantity' => $details['quantity'], 'price' => $details['price'], ]);
                if ($realVariantId) { $variant = ProductVariant::find($realVariantId); if ($variant) $variant->decrement('stock', $details['quantity']); }
                else { $product = Product::find($realProductId); if ($product) $product->decrement('stock', $details['quantity']); }
                $orderItemsPayload[] = [ /* ... */ 'sku' => $cartKey, 'name' => $details['name'], 'price' => $details['price'], 'quantity' => $details['quantity'],];
            }

            // Tambahkan item biaya ke payload Tripay
            $orderItemsPayload[] = [ 'sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1 ];
            if($applied_insurance_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'INSURANCE', 'name' => 'Asuransi', 'price' => $applied_insurance_cost, 'quantity' => 1 ]; }
            if($cod_add_cost > 0) { $orderItemsPayload[] = [ 'sku' => 'CODFEE', 'name' => 'Biaya COD', 'price' => $cod_add_cost, 'quantity' => 1 ]; }

            // --- Logika KiriminAja untuk COD/Cash (setelah order dibuat) ---
            if ($request->payment_method === 'cod' || $request->payment_method === 'cash') {
                 // ... (Kode persiapan alamat, lat/lng, schedule, weight, category sama seperti sebelumnya) ...
                 $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province; $userSearch = $user->village . ', ' . $user->regency . ', ' . $user->province;
                 $storeAddrRes = $kiriminAja->searchAddress($storeSearch); $userAddrRes = $kiriminAja->searchAddress($userSearch);
                 $storeAddr = $storeAddrRes['data'][0] ?? null; $userAddr = $userAddrRes['data'][0] ?? null;
                 $storeDistrictId = $storeAddr['district_id'] ?? null; $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                 $userDistrictId = $userAddr['district_id'] ?? null; $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                 $storeLat = $store->latitude; $storeLng = $store->longitude; $userLat = $user->latitude; $userLng = $user->longitude;
                 if (!$storeLat || !$storeLng) { $geo = $this->geocode($storeSearch); if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; /* update store */ } }
                 if (!$userLat || !$userLng) { $geo = $this->geocode($userSearch); if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; /* update user */ } }
                 $schedule = $kiriminAja->getSchedules();
                 $totalWeight = (int) collect($cart)->sum(function($item) { /* ... */ return (Product::find($item['product_id'])->weight ?? 1000) * $item['quantity']; });
                 $finalWeight = max(1000, $totalWeight); $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

                 // --- PERBAIKAN: Payload Packages untuk COD/Cash ---
                 $packages = $order->items()->with('product', 'variant')->get()->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipping_cost, $courier, $service, $useInsurance, $user, $request, $grand_total) {
                     // ... (Logika membuat $itemName sama seperti di processOrderCallback) ...
                      $product = $item->product; $variant = $item->variant;
                       // Validasi product sebelum ambil properti
                      if (!$product) {
                           Log::error('Product not found for order item', ['order_item_id' => $item->id, 'product_id' => $item->product_id]);
                           return null; // Skip item ini
                      }
                      $weight = $product->weight ?? 1000; // Default jika null
                      $width = $product->width ?? 5; $height = $product->height ?? 5; $length = $product->length ?? 5;
                      $jenis_barang = $product->jenis_barang ?? 1; // Default
                      $itemName = $product->name . ($variant ? ' (' . ($variant->combination_string ? str_replace(';', ', ', $variant->combination_string) : $variant->sku_code) . ')' : '');
                     return [
                         'order_id' => $order->invoice_number,
                         'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                         'destination_address' => $order->shipping_address, // Ambil dari order
                         'destination_kecamatan_id' => $userDistrictId, 'destination_kelurahan_id' => $userSubdistrictId,
                         'destination_zipcode' => $user->postal_code ?? 55598,
                         'weight' => $weight * $item->quantity, 'width' => $width, 'height' => $height, 'length' => $length,
                         'item_value' => $item->price * $item->quantity, // Nilai item ini saja
                         'shipping_cost' => $shipping_cost, 'service' => $courier, 'service_type' => $service,
                         'insurance_amount' => $useInsurance ? ($item->price * $item->quantity) : 0, // Nilai item jika asuransi
                         'item_name' => $itemName, 'package_type_id' => (int) $jenis_barang,
                         'cod' => $request->payment_method === 'cod' ? $grand_total : 0, // Total tagihan jika COD
                     ];
                 })->filter()->values()->toArray(); // filter() hapus null, values() reindex
                 // --- Akhir Perbaikan Packages ---

                  if (empty($packages)) {
                       throw new \Exception('Tidak ada item valid dalam pesanan untuk dikirim.');
                  }

                 // ... (Kode memanggil KiriminAja Express/Instant sama seperti sebelumnya) ...
                  if ($shipping_type === 'express' || $shipping_type === 'cargo') {
                       if (!$storeDistrictId || !$storeSubdistrictId || !$userDistrictId || !$userSubdistrictId) throw new \Exception('ID Kecamatan/Kelurahan tidak valid.');
                      $payload = [
                           'address' => $store->address_detail, 'phone' => $store->user->no_wa, // Ambil dari relasi store->user
                           'kecamatan_id' => $storeDistrictId, 'kelurahan_id' => $storeSubdistrictId,
                           'latitude' => $storeLat, 'longitude' => $storeLng,
                           'packages' => $packages, 'name' => $store->name,
                           'zipcode' => $store->postal_code ?? '63271',
                           'platform_name' => 'TOKOSANCAKA.COM',
                           'schedule' => $schedule['clock'] ?? null,
                           'category' => $category,
                       ];
                      $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                  } elseif ($shipping_type === 'instant') {
                      if (!$storeLat || !$storeLng || !$userLat || !$userLng) throw new \Exception('Koordinat tidak ditemukan.');
                       // Ambil item pertama dari $packages yg sudah difilter
                       $firstPackageItem = $packages[0];
                      $payload = [
                          'service' => $courier, 'service_type' => $service, 'vehicle' => 'motor',
                          'order_prefix' => $order->invoice_number,
                          'packages' => [[
                              'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                              'destination_lat' => $userLat, 'destination_long' => $userLng,
                              'destination_address' => $order->shipping_address,
                              'origin_name' => $store->name, 'origin_phone' => $store->user->no_wa, // Ambil dari relasi store->user
                              'origin_lat' => $storeLat, 'origin_long' => $storeLng,
                              'origin_address' => $store->address_detail,
                              'shipping_price' => (int) $shipping_cost,
                              'item' => [
                                  'name' => 'Pesanan ' . $order->invoice_number,
                                  'description' => $firstPackageItem['item_name'] ?? 'Pesanan dari toko', // Deskripsi dari item pertama
                                  'price' => $order->subtotal, 'weight' => $finalWeight,
                              ]
                          ]]
                      ];
                      $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                  } else {
                      throw new \Exception('Tipe pengiriman tidak didukung.');
                  }


                 if (empty($kiriminResponse['status']) || $kiriminResponse['status'] !== true) {
                     // Jika KiriminAja GAGAL, rollback order yg sudah dibuat
                     DB::rollBack();
                     $errorMessage = $kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order KiriminAja.');
                     throw new \Exception('Gagal membuat order pengiriman: ' . $errorMessage);
                 }

                 // Jika KiriminAja SUKSES, simpan resi
                 $resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                 if ($resi) {
                      $order->shipping_resi = $resi;
                      $order->save(); // Simpan resi ke order yg sudah ada
                 }

                 // Commit dan redirect (karena COD/Cash sudah selesai di sini)
                 DB::commit();
                 session()->forget('cart');
                 return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number]);
            }
            // --- Akhir Logika KiriminAja untuk COD/Cash ---


            // --- Logika Tripay (Hanya jika payment_method bukan COD/Cash) ---
            $apiKey       = config('tripay.api_key');
            $privateKey   = config('tripay.private_key');
            $merchantCode = config('tripay.merchant_code');
            $mode = config('tripay.mode');

            $payload = [
                'method'         => $request->payment_method,
                'merchant_ref'   => $order->invoice_number,
                'amount'         => $grand_total,
                'customer_name'  => $user->nama_lengkap,
                'customer_email' => $user->email,
                'customer_phone' => $user->no_wa,
                'order_items'    => $orderItemsPayload,
                'expired_time'   => time() + (1 * 60 * 60),
                'signature'      => hash_hmac('sha256', $merchantCode.$order->invoice_number.$grand_total, $privateKey),
                'return_url'     => route('checkout.invoice', ['invoice' => $order->invoice_number]),
            ];

            $baseUrl = $mode === 'production'
                ? 'https://tripay.co.id/api/transaction/create'
                : 'https://tripay.co.id/api-sandbox/transaction/create';

            $tripayResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                ->timeout(30)
                                ->withoutVerifying()
                                ->post($baseUrl, $payload);

            if ($tripayResponse->successful() && isset($tripayResponse->json()['success']) && $tripayResponse->json()['success'] === true) {
                $tripayData = $tripayResponse->json()['data'];
                $order->payment_url = $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? $tripayData['qr_url'] ?? $tripayData['pay_code'] ?? null;
                $order->save(); // Simpan payment_url

                // Jika Tripay SUKSES, baru commit DB
                DB::commit();
                session()->forget('cart');
                // Redirect ke Invoice (nanti di invoice bisa tampilkan tombol bayar/QR)
                return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number]);
            } else {
                // Jika Tripay GAGAL, rollback DB
                DB::rollBack();
                Log::error('Gagal membuat transaksi Tripay', ['response' => $tripayResponse->body()]); // Log body mentah
                $errorMessage = $tripayResponse->json()['message'] ?? 'Gagal menghubungi payment gateway.';
                throw new \Exception('Gagal membuat transaksi pembayaran: ' . $errorMessage);
            }
            // --- Akhir Logika Tripay ---

        } catch (\Exception $e) {
            DB::rollBack(); // Pastikan rollback jika ada error di mana pun
            Log::error('Checkout Gagal Total: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
             // Tampilkan pesan error yang lebih deskriptif ke user jika memungkinkan
             $userMessage = 'Terjadi kesalahan saat checkout. Silakan coba lagi.';
             if (str_contains($e->getMessage(), 'KiriminAja') || str_contains($e->getMessage(), 'pengiriman')) {
                 $userMessage = 'Terjadi kesalahan pada sistem pengiriman: ' . $e->getMessage();
             } elseif (str_contains($e->getMessage(), 'Tripay') || str_contains($e->getMessage(), 'pembayaran')) {
                 $userMessage = 'Terjadi kesalahan pada sistem pembayaran: ' . $e->getMessage();
             }
            return redirect()->route('checkout.index')->with('error', $userMessage);
        }
    }


    /**
     * Menampilkan halaman invoice setelah checkout.
     */
    public function invoice($invoice)
    {
        // ... (fungsi invoice tetap sama) ...
        if (!$invoice) {
            return redirect()->route('checkout.index')->with('error', 'Invoice tidak ditemukan.');
        }
        // Eager load relasi yang mungkin dibutuhkan di view invoice
        $order = Order::with('items.product', 'items.variant', 'store', 'user')
                     ->where('invoice_number', $invoice)
                     ->where('user_id', Auth::id()) // Pastikan user hanya bisa lihat ordernya sendiri
                     ->firstOrFail();

        return view('checkout.invoice', compact('order'));
    }

    /**
     * =========================================================================
     * INI ADALAH "GERBANG UTAMA" CALLBACK TRIPAY ANDA
     * =========================================================================
     * Menerima SEMUA callback dari Tripay.
     */
    public function TripayCallback(Request $request)
    {
        // 1. Validasi & Ambil Data
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

        if (config('tripay.skip_signature_check') !== true) { // Tambahkan opsi skip jika perlu (HANYA UNTUK DEBUG)
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
        $status = $data['status'] ?? null; // Ambil status dari data JSON
        $amount = $data['amount_received'] ?? ($data['amount'] ?? 0);

        if (!$merchantRef || !$status) {
            Log::warning('CheckoutController Callback: Missing merchant_ref or status.', ['data' => $data]);
            return response()->json(['success' => false, 'message' => 'Missing required data'], 400);
        }

        DB::beginTransaction();
        try {

            // --- LOGIKA PEMBAGI TUGAS BERDASARKAN PREFIX ---
            if (Str::startsWith($merchantRef, 'SCK-')) {
                // Panggil prosesor Pesanan dari Admin (MEMERLUKAN KIRIMINAJA)
                Log::info('Routing callback to AdminPesananController', ['ref' => $merchantRef]);
                AdminPesananController::processPesananCallback($merchantRef, $status, $data);

            } elseif (Str::startsWith($merchantRef, 'TOPUP-')) {
                // Panggil prosesor Top Up (TIDAK PERLU KIRIMINAJA)
                Log::info('Routing callback to TopUpController', ['ref' => $merchantRef]);
                // Pastikan Anda sudah membuat method ini di TopUpController
                TopUpController::processTopUpCallback($merchantRef, $status, $amount, $data);

            } elseif (Str::startsWith($merchantRef, 'ORD-')) {
                // Panggil prosesor Order dari controller ini (MEMERLUKAN KIRIMINAJA)
                Log::info('Routing callback to processOrderCallback (this controller)', ['ref' => $merchantRef]);
                $this->processOrderCallback($merchantRef, $status, $data);

            } elseif (Str::startsWith($merchantRef, 'CUSTP-')) { // <-- PREFIX BARU
                // Panggil prosesor Pesanan dari Customer (MEMERLUKAN KIRIMINAJA)
                Log::info('Routing callback to CustomerPesananController', ['ref' => $merchantRef]);
                // Pastikan Anda sudah membuat method ini di Customer\PesananController
                // CustomerPesananController::processCallback($merchantRef, $status, $data);
                 // !! ANDA HARUS MEMBUAT FUNGSI CustomerPesananController::processCallback !!
                 // Contoh pemanggilan sementara:
                 // throw new Exception('Handler untuk CUSTP- belum dibuat di CustomerPesananController');


            } elseif (Str::startsWith($merchantRef, 'CUSTO-')) { // <-- PREFIX BARU
                // Panggil prosesor Order dari CustomerOrderController (MEMERLUKAN KIRIMINAJA)
                Log::info('Routing callback to CustomerOrderController', ['ref' => $merchantRef]);
                // Pastikan Anda sudah membuat method ini di CustomerOrderController
                // CustomerOrderController::processCallback($merchantRef, $status, $data);
                 // !! ANDA HARUS MEMBUAT FUNGSI CustomerOrderController::processCallback !!
                 // Contoh pemanggilan sementara:
                 // throw new Exception('Handler untuk CUSTO- belum dibuat di CustomerOrderController');

            } else {
                Log::warning('CheckoutController Callback: Unrecognized merchant_ref prefix.', ['merchant_ref' => $merchantRef]);
            }
            // --- AKHIR LOGIKA PEMBAGI TUGAS ---

            DB::commit();
            return response()->json(['success' => true]); // Beri tahu Tripay OK

        } catch (Exception $e) {
            DB::rollBack();
            Log::critical('CheckoutController Callback: CRITICAL ERROR in processing.', [
                'merchant_ref' => $merchantRef,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(), // Aktifkan jika perlu detail trace
                'data' => $data
            ]);
             // Kembalikan error 500 agar Tripay bisa mencoba lagi (jika dikonfigurasi)
            return response()->json(['success' => false, 'message' => 'Internal Server Error during processing'], 500);
        }
    }


    /**
     * Fungsi private untuk memproses callback khusus untuk Order (dari controller ini).
     * Dipanggil oleh TripayCallback jika merchant_ref diawali "ORD-"
     */
    private function processOrderCallback($merchantRef, $status, $callbackData)
    {
         Log::info('Processing Order Callback (ORD-)...', ['ref' => $merchantRef, 'status' => $status]);
         $order = Order::with('items.product.store.user', 'items.variant', 'user') // Eager load lebih dalam
                     ->where('invoice_number', $merchantRef)
                     ->lockForUpdate()
                     ->first();

         if (!$order) {
              Log::error('Order Callback (ORD-): Order not found.', ['ref' => $merchantRef]);
              return;
         }

         // Hanya proses jika status masih 'pending'
         if ($order->status !== 'pending') {
              Log::info('Order Callback (ORD-): Order already processed or not pending.', ['ref' => $merchantRef, 'current_status' => $order->status]);
              return;
         }

         if ($status === 'PAID') {
              $order->status = 'paid';
              $order->save();

              // --- Logika Kirim ke KiriminAja SETELAH LUNAS ---
              try {
                   $kiriminAja = app(KiriminAjaService::class);

                   // Perbaikan parsing, ambil dari $order->shipping_method
                   $shippingParts = explode('-', $order->shipping_method);
                   if (count($shippingParts) < 4) throw new Exception('Format shipping_method di order tidak valid');
                   $type = $shippingParts[0]; $courier = $shippingParts[1]; $service = $shippingParts[2];
                   $shipCost = (int) $order->shipping_cost; // Ambil dari order
                   $insurance_cost = (int) $order->insurance_cost; // Ambil dari order

                   $store = $order->store; $user = $order->user;
                   if (!$store || !$user || !$store->user) throw new Exception('Data store atau user pada order tidak valid.'); // Cek relasi store->user

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
                    $calculatedWeight = $order->items->sum(function($item) { return ($item->product->weight ?? 1000) * $item->quantity; });
                    $finalWeight = max(1000, $calculatedWeight);
                    $category = $finalWeight >= 30000 ? 'trucking' : 'regular';

                   $firstItem = $order->items->first();
                   if (!$firstItem || !$firstItem->product) throw new Exception('Item atau produk pertama tidak ditemukan di order.'); // Validasi item pertama
                   $itemTypeFirstProduct = (int) ($firstItem->product->jenis_barang ?? 1); // Default ke 1 (General)
                   $mandatoryTypes = [1, 3, 4, 8];
                   $isMandatoryInsurance = in_array($itemTypeFirstProduct, $mandatoryTypes);
                   $useInsurance = $isMandatoryInsurance || ($insurance_cost > 0);

                   // Siapkan $packages payload (ambil dari DB order)
                   $packages = $order->items->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipping_cost, $courier, $service, $useInsurance, $user) {
                        if (!$item->product) return null; // Skip jika produk tidak ada
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
                           'shipping_cost' => $shipping_cost, 'service' => $courier, 'service_type' => $service,
                           'insurance_amount' => $useInsurance ? ($item->price * $item->quantity) : 0,
                           'item_name' => $itemName, 'package_type_id' => (int) $jenis_barang,
                           'cod' => 0,
                       ];
                   })->filter()->toArray(); // filter() untuk menghapus null jika produk tidak ada

                   if (empty($packages)) {
                        throw new Exception('Tidak ada item valid untuk dikirim ke KiriminAja.');
                   }

                   // Panggil API KiriminAja
                   $kiriminResponse = null;
                   if ($type === 'express' || $type === 'cargo') {
                        if (!$storeDistrictId || !$storeSubdistrictId || !$userDistrictId || !$userSubdistrictId) {
                             throw new \Exception('ID Kecamatan/Kelurahan tidak valid untuk KiriminAja Express/Cargo.');
                        }
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
                   } elseif ($type === 'instant') {
                        if (!$storeLat || !$storeLng || !$userLat || !$userLng) {
                             throw new \Exception('Koordinat tidak valid untuk KiriminAja Instant.');
                        }
                        // Payload Instant hanya mendukung 1 item, kita gabungkan nilainya
                        $totalItemValue = $order->items->sum(fn($item) => $item->price * $item->quantity);
                        $firstPackageItem = $packages[0]; // Ambil data dari item pertama untuk nama dll.

                       $payload = [
                           'service' => $courier, 'service_type' => $service, 'vehicle' => 'motor',
                           'order_prefix' => $order->invoice_number,
                           'packages' => [
                               [
                                   'destination_name' => $user->nama_lengkap, 'destination_phone' => $user->no_wa,
                                   'destination_lat' => $userLat, 'destination_long' => $userLng,
                                   'destination_address' => $order->shipping_address,
                                   'origin_name' => $store->name, 'origin_phone' => $store->user->no_wa,
                                   'origin_lat' => $storeLat, 'origin_long' => $storeLng,
                                   'origin_address' => $store->address_detail,
                                   'shipping_price' => (int) $shipping_cost,
                                   'item' => [
                                       'name' => 'Pesanan ' . $order->invoice_number, // Nama generik
                                       'description' => $firstPackageItem['item_name'] ?? 'Pesanan dari toko', // Deskripsi dari item pertama
                                       'price' => $totalItemValue, // Total nilai semua barang
                                       'weight' => $finalWeight,
                                   ]
                               ]
                           ]
                       ];
                       $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                   } else {
                        throw new \Exception('Tipe pengiriman tidak didukung untuk callback order ini.');
                   }

                   // Proses Response KiriminAja
                   if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                       $resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
                       if ($resi) { $order->shipping_resi = $resi; }
                       $order->status = 'processing'; // Update jadi processing
                       Log::info('Order Callback (ORD-): KiriminAja order created successfully.', ['ref' => $merchantRef, 'resi' => $resi]);
                   } else {
                       // KiriminAja GAGAL, status tetap 'paid', log error
                       Log::error("Order Callback (ORD-): KiriminAja order creation FAILED for PAID order.", ['ref' => $merchantRef, 'response' => $kiriminResponse]);
                       // Status tetap 'paid', mungkin perlu notif admin
                   }
                   $order->save();

              } catch (\Exception $e) {
                   Log::error("Order Callback (ORD-): Exception during KiriminAja process for PAID order.", [ 'ref' => $merchantRef, 'error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile() ]);
                   // Status order tetap 'paid', butuh penanganan manual. JANGAN rollback DB utama.
              }
              // --- Akhir Logika Kirim ke KiriminAja ---

         } elseif (in_array($status, ['EXPIRED', 'FAILED', 'UNPAID'])) {
              $order->status = ($status === 'EXPIRED') ? 'expired' : 'failed';
              $order->save();
              Log::info('Order Callback (ORD-): Order status updated to failed/expired.', ['ref' => $merchantRef, 'status' => $order->status]);
              // Pertimbangkan kembalikan stok di sini jika perlu
              // foreach ($order->items as $item) { /* ... kembalikan stok ... */ }
         } else {
              Log::warning('Order Callback (ORD-): Received unknown status.', ['ref' => $merchantRef, 'status' => $status]);
         }
    }

} // Akhir Class

