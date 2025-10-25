<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
// use App\Http\Controllers\DanaController; // Hapus jika tidak digunakan
use Illuminate\Support\Facades\Log;
use App\Models\TopUp;
use App\Models\User; // Pastikan model User diimport
use App\Models\Store; // Import model Store
use App\Events\SaldoUpdated;
use App\Events\AdminNotificationEvent;
use App\Services\KiriminAjaService;
use App\Models\Product;
use App\Models\ProductVariant; // Import ProductVariant
use Illuminate\Support\Facades\Http;
use App\Models\Pesanan; // Asumsi model ini ada
use Illuminate\Support\Str;

class CheckoutController extends Controller
{

    public function geocode($address){

        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::timeout(10)->withHeaders([ // Tambahkan timeout
                'User-Agent' => config('app.name', 'MyLaravelApp') . '/1.0 (' . config('mail.from.address', 'support@example.com') . ')', // Gunakan config
                'Accept'     => 'application/json',
            ])->get($url, [
                'q'      => $address,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($response->successful() && $response->json() && !empty($response->json()[0])) {
                return [
                    'lat' => (float) $response->json()[0]['lat'],
                    'lng' => (float) $response->json()[0]['lon'],
                ];
            } else {
                 Log::warning('Geocoding failed or returned empty.', ['address' => $address, 'response_status' => $response->status(), 'response_body' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Geocoding exception: ' . $e->getMessage(), ['address' => $address]);
        }


        return null;
    }



    /**
     * Menampilkan halaman checkout.
     */
    public function index(KiriminAjaService $kiriminAja)
    {
        if (!Auth::check()) {
            return redirect()->route('customer.login') // Asumsi route login customer
                ->with('info', 'Anda harus login untuk melanjutkan ke checkout.');
        }

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('info', 'Keranjang Anda kosong. Silakan belanja terlebih dahulu.');
        }

        $user = Auth::user();

        // --- [PERBAIKAN LOGIKA PENGAMBILAN PRODUK PERTAMA & STORE] ---
        $firstCartItemKey = array_key_first($cart); // Get the first key ('product_X' or 'variant_Y')
        $firstCartItem = $cart[$firstCartItemKey] ?? null; // Get the details of the first item

        if (!$firstCartItem || !isset($firstCartItem['product_id'])) {
            Log::error('Checkout error: Invalid first cart item structure.', ['cart_key' => $firstCartItemKey, 'cart' => $cart]);
            return redirect()->route('cart.index')->with('error', 'Terjadi masalah dengan data keranjang Anda.');
        }

        // Ambil produk berdasarkan product_id dari item pertama, dan eager load relasi store
        $firstProduct = Product::with('store.user')->find($firstCartItem['product_id']); // Eager load store and its user

        if (!$firstProduct) {
            Log::error('Checkout error: Product not found for cart item.', ['item' => $firstCartItem]);
            // Hapus item invalid dari cart?
             unset($cart[$firstCartItemKey]);
             session()->put('cart', $cart);
            return redirect()->route('cart.index')->with('error', 'Salah satu produk di keranjang Anda tidak ditemukan lagi.');
        }

        // Ambil data toko dari relasi produk
        $store = $firstProduct->store; // Relasi 'store' harus ada di model Product

        // Validasi kelengkapan alamat Toko
        if (!$store || empty($store->village) || empty($store->district) || empty($store->regency) || empty($store->province)) {
             $storeIdForLog = $store ? $store->id : 'N/A';
             Log::warning('Checkout warning: Store address incomplete.', ['store_id' => $storeIdForLog, 'product_id' => $firstProduct->id]);
            return redirect()->route('cart.index')
                ->with('error', 'Alamat toko asal (' . ($store->name ?? 'N/A') . ') tidak lengkap. Mohon hubungi penjual.');
        }
        // --- [AKHIR PERBAIKAN] ---


        // Validasi kelengkapan alamat User
         if (empty($user->address_detail) || empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province)) {
             // Redirect ke halaman profil atau setting alamat jika ada
             if (Route::has('customer.profile.edit')) { // Check if profile edit route exists
                return redirect()->route('customer.profile.edit') // Redirect to profile edit
                    ->with('warning', 'Alamat pengiriman Anda belum lengkap. Mohon lengkapi terlebih dahulu.');
             }
             // Fallback redirect ke keranjang jika tidak ada route profil
             return redirect()->route('cart.index')
                 ->with('error', 'Alamat penerima tidak lengkap. Mohon lengkapi data lokasi Anda di profil.');
         }


        // Persiapan data alamat untuk KiriminAja
        // Pastikan nama kolom sesuai dengan model User dan Store Anda
        $storeFullAddress = implode(', ', array_filter([$store->address_detail, $store->village, $store->district, $store->regency, $store->province, $store->postal_code]));
        $userFullAddress = implode(', ', array_filter([$user->address_detail, $user->village, $user->district, $user->regency, $user->province, $user->postal_code]));

        // Gunakan ID unik dari KiriminAja jika sudah disimpan, fallback ke pencarian
        $storeDistrictId = $store->kiriminaja_district_id ?? null;
        $storeSubdistrictId = $store->kiriminaja_subdistrict_id ?? null; // kelurahan_id di KiriminAja
        $userDistrictId = $user->kiriminaja_district_id ?? null;
        $userSubdistrictId = $user->kiriminaja_subdistrict_id ?? null;

         // Cari ID jika belum ada (Ini bisa memakan waktu, idealnya simpan setelah pencarian pertama)
         if (!$storeDistrictId || !$storeSubdistrictId) {
             $storeAddrRes = $kiriminAja->searchAddress($storeFullAddress);
             $storeAddr = $storeAddrRes['data'][0] ?? null;
             if ($storeAddr) {
                 $storeDistrictId = $storeAddr['district_id'];
                 $storeSubdistrictId = $storeAddr['subdistrict_id'];
                 // TODO: Simpan ID ini ke model Store untuk penggunaan berikutnya
                 // $store->update(['kiriminaja_district_id' => $storeDistrictId, 'kiriminaja_subdistrict_id' => $storeSubdistrictId]);
             } else {
                  Log::error('KiriminAja address search failed for store.', ['address' => $storeFullAddress]);
                  return redirect()->route('cart.index')->with('error', 'Gagal memvalidasi alamat toko pengirim.');
             }
         }
         if (!$userDistrictId || !$userSubdistrictId) {
             $userAddrRes = $kiriminAja->searchAddress($userFullAddress);
             $userAddr = $userAddrRes['data'][0] ?? null;
              if ($userAddr) {
                 $userDistrictId = $userAddr['district_id'];
                 $userSubdistrictId = $userAddr['subdistrict_id'];
                 // TODO: Simpan ID ini ke model User untuk penggunaan berikutnya
                 // $user->update(['kiriminaja_district_id' => $userDistrictId, 'kiriminaja_subdistrict_id' => $userSubdistrictId]);
             } else {
                  Log::error('KiriminAja address search failed for user.', ['address' => $userFullAddress]);
                  return redirect()->route('cart.index')->with('error', 'Gagal memvalidasi alamat penerima.');
             }
         }


        // Hitung total berat dan nilai item
        $totalWeight = 0;
        $itemValue = 0;
        foreach($cart as $item) {
             // Cari produk asli atau varian untuk mendapatkan beratnya
             $productWeight = 0;
             if (!empty($item['variant_id'])) {
                 $variant = ProductVariant::find($item['variant_id']);
                 $productWeight = $variant->weight ?? $variant->product->weight ?? 100; // Ambil berat varian, fallback ke produk, default 100g
             } elseif (!empty($item['product_id'])) {
                 $product = Product::find($item['product_id']);
                 $productWeight = $product->weight ?? 100; // Ambil berat produk, default 100g
             }
             $totalWeight += $productWeight * ($item['quantity'] ?? 0);
             $itemValue += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        }

        $finalWeight = max(100, $totalWeight); // Minimal 100 gram untuk KiriminAja? (cek dokumentasi)
        $category = $finalWeight >= 30000 ? 'trucking' : 'regular'; // Tentukan kategori berdasarkan berat

        // Dapatkan opsi pengiriman Express
        $expressOptions = $kiriminAja->getExpressPricing(
            $storeDistrictId,
            $storeSubdistrictId,
            $userDistrictId,
            $userSubdistrictId,
            $finalWeight,
            10, // Default dimensions L,W,H (bisa dibuat lebih dinamis)
            10,
            10,
            $itemValue,
            null, // Dropoff/Pickup flag (opsional)
            $category // Regular atau Trucking
        );
        Log::info('KiriminAja Express Pricing Request:', compact('storeDistrictId', 'storeSubdistrictId', 'userDistrictId', 'userSubdistrictId', 'finalWeight', 'itemValue', 'category'));
        Log::info('KiriminAja Express Pricing Response:', ['response' => $expressOptions]);


        // Dapatkan opsi pengiriman Instant (jika koordinat ada)
        $instantOptions = null;
        $storeLat = $store->latitude ? (float) $store->latitude : null;
        $storeLng = $store->longitude ? (float) $store->longitude : null;
        $userLat  = $user->latitude ? (float) $user->latitude : null;
        $userLng  = $user->longitude ? (float) $user->longitude : null;

        // Coba geocode jika koordinat belum ada
        if (!$storeLat || !$storeLng) {
            $geo = $this->geocode($storeFullAddress);
            if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; /* TODO: Save to Store */ }
        }
        if (!$userLat || !$userLng) {
            $geo = $this->geocode($userFullAddress);
            if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; /* TODO: Save to User */ }
        }

        if ($storeLat && $storeLng && $userLat && $userLng) {
            $instantOptions = $kiriminAja->getInstantPricing(
                $storeLat,
                $storeLng,
                $store->address_detail ?? 'Detail alamat toko',
                $userLat,
                $userLng,
                $user->address_detail ?? 'Detail alamat penerima',
                $finalWeight,
                $itemValue
            );
             Log::info('KiriminAja Instant Pricing Request:', compact('storeLat', 'storeLng', 'userLat', 'userLng', 'finalWeight', 'itemValue'));
             Log::info('KiriminAja Instant Pricing Response:', ['response' => $instantOptions]);
        } else {
             Log::warning('Cannot get Instant Pricing due to missing coordinates.', compact('storeLat', 'storeLng', 'userLat', 'userLng'));
        }


        return view('checkout.index', compact('cart', 'user', 'store', 'expressOptions', 'instantOptions', 'itemValue', 'finalWeight'));
    }



    /**
     * Memproses dan menyimpan pesanan baru.
     */
    public function store(Request $request, KiriminAjaService $kiriminAja)
    {
        // Validasi dasar
        $validated = $request->validate([
            'shipping_method' => 'required|string', // Format: type-courier-service-cost-insuranceCost
            'payment_method' => 'required|string|in:cod,cash,tripay', // Sesuaikan dengan opsi Anda
            'receiver_note' => 'nullable|string|max:255', // Catatan penerima (opsional)
            'sender_note' => 'nullable|string|max:255', // Catatan pengirim (opsional)
             // Validasi alamat pengiriman (jika user bisa mengubah saat checkout)
             'shipping_address_detail' => 'required|string|max:500',
             'shipping_phone' => 'required|string|max:20',
             'shipping_name' => 'required|string|max:100',
             // Anda mungkin perlu validasi ulang ID kecamatan/kelurahan jika user bisa memilih alamat baru
             // 'shipping_district_id' => 'required|integer',
             // 'shipping_subdistrict_id' => 'required|integer',
             // 'shipping_postal_code' => 'required|string|max:10',
             // 'shipping_latitude' => 'nullable|numeric',
             // 'shipping_longitude' => 'nullable|numeric',
        ]);

        $cart = session()->get('cart', []);
        $user = Auth::user();

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        // Ambil produk pertama dan toko (sama seperti di index)
        $firstCartItemKey = array_key_first($cart);
        $firstCartItem = $cart[$firstCartItemKey] ?? null;
        if (!$firstCartItem || !isset($firstCartItem['product_id'])) {
             return redirect()->route('cart.index')->with('error', 'Data keranjang tidak valid.');
        }
        $firstProduct = Product::with('store.user')->find($firstCartItem['product_id']);
        if (!$firstProduct || !$firstProduct->store) {
            return redirect()->route('cart.index')->with('error', 'Data produk atau toko tidak ditemukan.');
        }
        $store = $firstProduct->store;

        // Hitung ulang total berat dan nilai item dari cart
        $totalWeight = 0;
        $subtotal = 0; // Subtotal barang saja
        $itemDetailsForKiriminAja = []; // Untuk payload KiriminAja create order
        $itemDetailsForTripay = []; // Untuk payload Tripay

        foreach ($cart as $cartKey => $item) {
            $productId = $item['product_id'] ?? null;
            $variantId = $item['variant_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            $itemName = $item['name'] ?? 'Unknown Item'; // Nama dari cart

            if ($quantity <= 0) continue; // Skip item invalid

            $productWeight = 0;
            $productWidth = 5; $productHeight = 5; $productLength = 5; // Defaults
            $productJenisBarang = 7; // Default (Lainnya)

            // Dapatkan detail produk/varian untuk berat, dimensi, jenis
            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if ($variant && $variant->product) { // Pastikan varian dan produknya ada
                    $productWeight = $variant->weight ?? $variant->product->weight ?? 100;
                    $productWidth = $variant->product->width ?? 5;
                    $productHeight = $variant->product->height ?? 5;
                    $productLength = $variant->product->length ?? 5;
                    $productJenisBarang = $variant->product->jenis_barang ?? 7;
                } else { continue; } // Skip jika varian tidak ditemukan
            } elseif ($productId) {
                 $product = Product::find($productId);
                 if ($product) { // Pastikan produk ada
                    $productWeight = $product->weight ?? 100;
                    $productWidth = $product->width ?? 5;
                    $productHeight = $product->height ?? 5;
                    $productLength = $product->length ?? 5;
                    $productJenisBarang = $product->jenis_barang ?? 7;
                 } else { continue; } // Skip jika produk tidak ditemukan
            } else { continue; } // Skip jika item tidak punya ID

            $totalWeight += $productWeight * $quantity;
            $subtotal += $price * $quantity;

            // Siapkan detail item untuk payload KiriminAja
             $itemDetailsForKiriminAja[] = [
                 'item_name' => Str::limit($itemName, 100, ''), // Batasi panjang nama
                 'item_quantity' => $quantity,
                 'item_price' => $price,
                 'item_weight' => $productWeight,
                 // Tambahkan dimensi jika diperlukan oleh KiriminAja untuk item
                 // 'item_width' => $productWidth,
                 // 'item_height' => $productHeight,
                 // 'item_length' => $productLength,
                 'package_type_id' => (int) $productJenisBarang, // Pastikan integer
            ];

             // Siapkan detail item untuk payload Tripay
             $itemDetailsForTripay[] = [
                'sku'       => $cartKey, // Gunakan cart key sebagai SKU unik
                'name'      => Str::limit($itemName, 100, ''),
                'price'     => $price,
                'quantity'  => $quantity,
                // 'product_url' => route('products.show', $item['slug'] ?? $productId), // Opsional
                // 'image_url'   => $item['image_url'] ? asset('storage/' . $item['image_url']) : null, // Opsional
             ];
        }

        // Cek lagi jika cart jadi kosong setelah validasi item
        if (empty($itemDetailsForKiriminAja)) {
             return redirect()->route('cart.index')->with('error', 'Tidak ada item valid di keranjang Anda.');
        }

        $finalWeight = max(100, $totalWeight); // Minimal berat (cek KiriminAja)

        // Parsing data shipping method yang dipilih
        $shippingParts = explode('-', $validated['shipping_method']);
         if (count($shippingParts) < 5) { // type-courier-service-cost-insuranceCost
             Log::error('Invalid shipping_method format', ['value' => $validated['shipping_method']]);
             return back()->withInput()->with('error', 'Metode pengiriman tidak valid.');
         }
        $shipping_type = $shippingParts[0]; // instant or express
        $shipping_courier = $shippingParts[1]; // e.g., 'jne', 'grab'
        $shipping_service = $shippingParts[2]; // e.g., 'REG', 'Instant'
        $shipping_cost = (int) $shippingParts[3];
        $insurance_cost = (int) $shippingParts[4]; // Biaya asuransi dari KiriminAja

        // Tentukan apakah asuransi wajib berdasarkan jenis barang (ambil dari item pertama saja?)
        // Ini asumsi semua barang dalam 1 order punya requirement asuransi yg sama, jika tidak perlu logika per item
        $isMandatoryInsurance = false;
        if (!empty($itemDetailsForKiriminAja)) {
            $firstItemJenis = $itemDetailsForKiriminAja[0]['package_type_id'];
            $isMandatoryInsurance = in_array($firstItemJenis, [1, 3, 4, 8]); // Sesuaikan ID jenis barang wajib asuransi
        }
        $actual_insurance_cost = $isMandatoryInsurance ? $insurance_cost : 0; // Hanya tambahkan jika wajib

        // Hitung total dasar (sebelum COD)
        $base_total = $subtotal + $shipping_cost + $actual_insurance_cost;

        // Hitung biaya COD jika dipilih
        $cod_add_cost = 0;
        if ($validated['payment_method'] === 'cod') {
            if ($shipping_type !== 'express') { // COD mungkin hanya untuk express? Cek KiriminAja
                return back()->withInput()->with('error', 'Metode pembayaran COD tidak tersedia untuk jenis pengiriman ini.');
            }
             // Hitung biaya COD (misal 3% dari base_total, atau sesuai aturan KiriminAja jika mereka yg handle)
             $codFeePercentage = 0.03; // Ambil dari config jika memungkinkan
             $cod_add_cost = ceil($base_total * $codFeePercentage);
        }

        $grand_total = $base_total + $cod_add_cost;


        // Dapatkan detail alamat pengirim (toko) dan penerima (user) dari DB/Request
        // Pengirim (Toko)
        $storeFullAddress = implode(', ', array_filter([$store->address_detail, $store->village, $store->district, $store->regency, $store->province, $store->postal_code]));
        $storeDistrictId = $store->kiriminaja_district_id ?? null;
        $storeSubdistrictId = $store->kiriminaja_subdistrict_id ?? null;
        $storeLat = $store->latitude ? (float) $store->latitude : null;
        $storeLng = $store->longitude ? (float) $store->longitude : null;

        // Penerima (dari request - user mungkin mengubah alamat saat checkout)
        $receiverName = $validated['shipping_name'];
        $receiverPhone = formatWaNumber($validated['shipping_phone']); // Gunakan helper format nomor WA
        $receiverAddressDetail = $validated['shipping_address_detail'];
        // Asumsi ID kecamatan/kelurahan dikirim dari form jika user bisa ubah alamat
        $userDistrictId = $request->input('shipping_district_id', $user->kiriminaja_district_id);
        $userSubdistrictId = $request->input('shipping_subdistrict_id', $user->kiriminaja_subdistrict_id);
        $userPostalCode = $request->input('shipping_postal_code', $user->postal_code);
        $userLat = $request->input('shipping_latitude', $user->latitude) ? (float) $request->input('shipping_latitude', $user->latitude) : null;
        $userLng = $request->input('shipping_longitude', $user->longitude) ? (float) $request->input('shipping_longitude', $user->longitude) : null;
        $receiverFullAddress = implode(', ', array_filter([
             $receiverAddressDetail,
             // Ambil nama desa/kec/kab/prov dari database berdasarkan ID jika perlu
             $user->village, $user->district, $user->regency, $user->province, $userPostalCode
        ])); // Buat alamat lengkap untuk disimpan di order

         // Validasi ulang ID KiriminAja jika alamat berubah atau belum ada
         // (Mirip logika di index, idealnya fungsi terpisah)
        if (!$storeDistrictId || !$storeSubdistrictId) { /* ... cari ulang ... */ Log::error('Store KiriminAja ID missing during checkout.'); return back()->with('error', 'Alamat toko tidak valid.'); }
        if (!$userDistrictId || !$userSubdistrictId) { /* ... cari ulang ... */ Log::error('User KiriminAja ID missing during checkout.'); return back()->with('error', 'Alamat penerima tidak valid.'); }
        if (($shipping_type == 'instant') && (!$storeLat || !$storeLng || !$userLat || !$userLng)) {
            // Coba geocode lagi
            if (!$storeLat || !$storeLng) { $geo = $this->geocode($storeFullAddress); if ($geo) { $storeLat = $geo['lat']; $storeLng = $geo['lng']; } }
            if (!$userLat || !$userLng) { $geo = $this->geocode($receiverFullAddress); if ($geo) { $userLat = $geo['lat']; $userLng = $geo['lng']; } }

            if (!$storeLat || !$storeLng || !$userLat || !$userLng) {
                Log::error('Missing coordinates for Instant delivery during checkout.');
                return back()->with('error', 'Koordinat alamat tidak ditemukan untuk pengiriman instan.');
            }
        }


        DB::beginTransaction();

        try {
            // 1. Buat Order di database Anda
            do {
                $invoiceNumber = 'SCK-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists());

            $order = Order::create([
                'store_id'        => $store->id,
                'user_id'         => $user->id_pengguna, // Pastikan kolom user_id sesuai
                'invoice_number'  => $invoiceNumber,
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shipping_cost,
                'insurance_cost'  => $actual_insurance_cost, // Simpan biaya asuransi aktual
                'cod_fee'         => $cod_add_cost,        // Simpan biaya COD
                'total_amount'    => $grand_total,
                'shipping_method' => $validated['shipping_method'], // Simpan string lengkap
                'shipping_courier'=> $shipping_courier, // Simpan kurir
                'shipping_service'=> $shipping_service, // Simpan layanan
                'payment_method'  => $validated['payment_method'],
                'status'          => 'pending', // Status awal: pending
                'shipping_name'   => $receiverName, // Simpan info pengiriman
                'shipping_phone'  => $receiverPhone,
                'shipping_address'=> $receiverAddressDetail,
                'shipping_village'=> $user->village, // Simpan detail alamat terpisah jika perlu
                'shipping_district' => $user->district,
                'shipping_regency' => $user->regency,
                'shipping_province' => $user->province,
                'shipping_postal_code' => $userPostalCode,
                'shipping_latitude' => $userLat,
                'shipping_longitude' => $userLng,
                'receiver_note'   => $validated['receiver_note'], // Simpan catatan
                // Tambahkan field lain jika perlu (misal: total_weight)
                'total_weight'    => $finalWeight,
            ]);

            // 2. Buat Order Items
            foreach ($cart as $cartKey => $item) {
                 if (($item['quantity'] ?? 0) <= 0) continue; // Skip invalid quantity

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_variant_id' => $item['variant_id'] ?? null, // Simpan ID varian
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'] ?? 0,
                    'item_name'  => $item['name'] ?? 'Unknown Item', // Simpan nama item saat itu
                ]);

                // 3. Kurangi Stok Produk/Varian
                 if (!empty($item['variant_id'])) {
                     $variant = ProductVariant::find($item['variant_id']);
                     if ($variant) {
                         // Gunakan decrement untuk atomicity (lebih aman dari race condition)
                         $updatedRows = ProductVariant::where('id', $variant->id)->where('stock', '>=', $item['quantity'])->decrement('stock', $item['quantity']);
                         if ($updatedRows == 0) { // Jika gagal decrement (stok tidak cukup)
                            throw new \Exception("Stok untuk varian {$item['name']} habis saat checkout.");
                         }
                         // Update juga stok produk utama jika perlu (tergantung logika bisnis Anda)
                         // Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                     } else { throw new \Exception("Varian {$item['name']} tidak ditemukan saat mengurangi stok."); }
                 } elseif (!empty($item['product_id'])) {
                     $product = Product::find($item['product_id']);
                     if ($product) {
                         $updatedRows = Product::where('id', $product->id)->where('stock', '>=', $item['quantity'])->decrement('stock', $item['quantity']);
                         if ($updatedRows == 0) {
                              throw new \Exception("Stok untuk produk {$item['name']} habis saat checkout.");
                         }
                     } else { throw new \Exception("Produk {$item['name']} tidak ditemukan saat mengurangi stok."); }
                 }
            }

            // 4. Buat Order Pengiriman di KiriminAja
            $kiriminAjaOrderId = $order->invoice_number; // Gunakan invoice sebagai order ID
            $kiriminResponse = null;

            $packagePayload = [ // Payload dasar untuk KiriminAja
                'origin_name'       => $store->name ?? 'Toko Sancaka',
                'origin_phone'      => $store->user->no_wa ?? config('app.default_phone'), // Ambil no WA dari user pemilik toko
                'origin_address'    => $store->address_detail ?? 'Alamat Toko',
                'origin_kecamatan_id' => $storeDistrictId,
                'origin_kelurahan_id' => $storeSubdistrictId,
                'origin_zipcode'    => $store->postal_code ?? '63271', // Pastikan ada fallback
                'origin_lat'        => $storeLat, // Diperlukan untuk Instant
                'origin_long'       => $storeLng, // Diperlukan untuk Instant
                'destination_name'    => $receiverName,
                'destination_phone'   => $receiverPhone,
                'destination_address' => $receiverAddressDetail,
                'destination_kecamatan_id' => $userDistrictId,
                'destination_kelurahan_id' => $userSubdistrictId,
                'destination_zipcode' => $userPostalCode ?? '55598', // Fallback zipcode
                'destination_lat'     => $userLat, // Diperlukan untuk Instant
                'destination_long'    => $userLng, // Diperlukan untuk Instant
                'destination_address_note' => $validated['receiver_note'], // Catatan utk kurir
                'cod'               => ($validated['payment_method'] === 'cod') ? $grand_total : 0, // Nilai COD jika metode=cod
                'insurance_amount'  => $actual_insurance_cost, // Biaya asuransi jika wajib
                'service'           => $shipping_courier, // Nama kurir
                'service_type'      => $shipping_service, // Nama layanan
                'shipping_cost'     => $shipping_cost,    // Biaya ongkir
                'item_value'        => $subtotal,         // Nilai total barang
                'items'             => $itemDetailsForKiriminAja, // Detail item barang
                'package_type_id'   => $isMandatoryInsurance ? ($itemDetailsForKiriminAja[0]['package_type_id'] ?? 7) : 7, // Ambil dari item pertama jika wajib, else default
                'weight'            => $finalWeight,      // Berat total
                // Tambahkan dimensi jika diperlukan
                // 'length'            => ...,
                // 'width'             => ...,
                // 'height'            => ...,
            ];


             if ($shipping_type === 'express') {
                 $category = $finalWeight >= 30000 ? 'trucking' : 'regular';
                 $schedule = $kiriminAja->getSchedules(); // Dapatkan jadwal pickup

                 $payload = [
                     'origin_contact_name' => $store->name ?? 'Toko Sancaka', // Nama kontak pengirim
                     'origin_contact_phone'=> $packagePayload['origin_phone'],
                     'origin_address'      => $packagePayload['origin_address'],
                     'origin_kecamatan_id' => $packagePayload['origin_kecamatan_id'],
                     'origin_kelurahan_id' => $packagePayload['origin_kelurahan_id'],
                     'origin_zipcode'      => $packagePayload['origin_zipcode'],
                     'origin_lat'          => $packagePayload['origin_lat'],   // Opsional utk Express
                     'origin_long'         => $packagePayload['origin_long'],  // Opsional utk Express
                     'destination_contact_name' => $packagePayload['destination_name'],
                     'destination_contact_phone'=> $packagePayload['destination_phone'],
                     'destination_address'     => $packagePayload['destination_address'],
                     'destination_kecamatan_id'=> $packagePayload['destination_kecamatan_id'],
                     'destination_kelurahan_id'=> $packagePayload['destination_kelurahan_id'],
                     'destination_zipcode'     => $packagePayload['destination_zipcode'],
                     'destination_lat'         => $packagePayload['destination_lat'], // Opsional utk Express
                     'destination_long'        => $packagePayload['destination_long'],// Opsional utk Express
                     'cod'               => $packagePayload['cod'],
                     'is_insurance'      => $actual_insurance_cost > 0 ? 1 : 0, // Kirim flag 1 jika ada asuransi
                     'insurance_amount'  => $subtotal, // Nilai barang untuk asuransi = subtotal
                     'shipping_cost'     => $packagePayload['shipping_cost'],
                     'service'           => $packagePayload['service'],
                     'service_type'      => $packagePayload['service_type'],
                     'items'             => $packagePayload['items'],
                     'weight'            => $packagePayload['weight'],
                     'order_id'          => $kiriminAjaOrderId, // Gunakan invoice number sbg order ID
                     'pickup_schedule'   => $schedule['clock'] ?? null, // Jadwal pickup
                     'category'          => $category,
                     'address_book_id'   => null, // Opsional
                     'platform_name'     => config('app.name', 'TOKOSANCAKA.COM'), // Nama platform Anda
                     'duty_amount'       => 0, // Bea jika ada
                     'package_note'      => $validated['sender_note'], // Catatan paket dari pengirim
                 ];

                 $kiriminResponse = $kiriminAja->createExpressOrder($payload); // Ganti dari createInstantOrder
                 Log::info('KiriminAja Express Create Order Payload:', $payload);

             } elseif ($shipping_type === 'instant') {
                 $payload = [
                      'service' => $packagePayload['service'],
                      'service_type' => $packagePayload['service_type'],
                      'vehicle' => 'motor', // Asumsi motor
                      'order_prefix' => $kiriminAjaOrderId,
                      'packages' => [
                          [ // Instant hanya 1 package
                               'destination_name' => $packagePayload['destination_name'],
                               'destination_phone' => $packagePayload['destination_phone'],
                               'destination_lat' => $packagePayload['destination_lat'],
                               'destination_long' => $packagePayload['destination_long'],
                               'destination_address' => $packagePayload['destination_address'],
                               'destination_address_note' => $packagePayload['destination_address_note'],
                               'origin_name' => $packagePayload['origin_name'],
                               'origin_phone' => $packagePayload['origin_phone'],
                               'origin_lat' => $packagePayload['origin_lat'],
                               'origin_long' => $packagePayload['origin_long'],
                               'origin_address' => $packagePayload['origin_address'],
                               'origin_address_note' => $validated['sender_note'], // Catatan utk pickup
                               'shipping_price' => $packagePayload['shipping_cost'],
                               'item' => [ // Instant hanya 1 item summary
                                   'name' => 'Pesanan ' . $kiriminAjaOrderId,
                                   'description' => 'Pesanan dari ' . ($store->name ?? 'Toko'),
                                   'price' => $subtotal, // Nilai total barang
                                   'weight' => $finalWeight, // Berat total
                                   // Tambahkan dimensi jika diperlukan API Instant
                               ]
                          ]
                     ]
                 ];
                 $kiriminResponse = $kiriminAja->createInstantOrder($payload);
                 Log::info('KiriminAja Instant Create Order Payload:', $payload);
             }

             Log::info('KiriminAja Create Order Response:', ['response' => $kiriminResponse]);

             // Cek respons KiriminAja
             if (!$kiriminResponse || !isset($kiriminResponse['status']) || $kiriminResponse['status'] !== true) {
                 $errorMessage = 'Gagal membuat pesanan pengiriman.';
                 if (!empty($kiriminResponse['errors'])) {
                     $errorMessage .= ' Detail: ' . collect($kiriminResponse['errors'])->flatten()->implode(', ');
                 } elseif (!empty($kiriminResponse['text'])) {
                     $errorMessage .= ' Detail: ' . $kiriminResponse['text'];
                 }
                 throw new \Exception($errorMessage);
             }

             // Simpan AWB/Resi jika ada di respons
             if (!empty($kiriminResponse['awb'])) {
                 $order->shipping_resi = $kiriminResponse['awb'];
             } elseif (!empty($kiriminResponse['pickup_number'])) { // Untuk instant?
                 $order->shipping_resi = $kiriminResponse['pickup_number'];
             }
             $order->kiriminaja_order_id = $kiriminResponse['order_id'] ?? null; // Simpan ID order KiriminAja jika ada
             $order->status = 'processing'; // Update status jadi processing setelah order kiriman dibuat
             $order->save();


            // 5. Proses Pembayaran (jika bukan COD)
            $paymentUrl = null;
            if ($validated['payment_method'] === 'tripay') {
                $apiKey       = config('tripay.api_key');
                $privateKey   = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
                $mode = config('tripay.mode'); // 'sandbox' or 'production'

                // Tambahkan item ongkir dan biaya lain ke payload Tripay
                $itemDetailsForTripay[] = [
                    'sku'       => 'ONGKIR',
                    'name'      => 'Biaya Pengiriman (' . strtoupper($shipping_courier) . ' ' . strtoupper($shipping_service) . ')',
                    'price'     => $shipping_cost,
                    'quantity'  => 1,
                 ];
                 if ($actual_insurance_cost > 0) {
                     $itemDetailsForTripay[] = [ 'sku' => 'ASURANSI', 'name' => 'Biaya Asuransi', 'price' => $actual_insurance_cost, 'quantity' => 1 ];
                 }
                 // Tripay mungkin tidak support COD fee di item, karena amount sudah termasuk

                $tripayPayload = [
                    'method'         => $request->input('tripay_channel_code'), // Ambil kode channel dari form jika user memilih
                    'merchant_ref'   => $order->invoice_number,
                    'amount'         => $grand_total, // Harus integer
                    'customer_name'  => $user->nama_lengkap ?? $receiverName, // Gunakan nama user atau nama pengiriman
                    'customer_email' => $user->email ?? 'email@example.com', // Pastikan user punya email
                    'customer_phone' => $receiverPhone, // Gunakan nomor penerima
                    'order_items'    => $itemDetailsForTripay,
                    'callback_url'   => route('tripay.callback'), // URL callback Anda
                    'return_url'     => route('checkout.invoice', ['invoice' => $order->invoice_number]), // URL setelah bayar
                    'expired_time'   => time() + (1 * 60 * 60), // Expired dalam 1 jam
                    'signature'      => hash_hmac('sha256', $merchantCode.$order->invoice_number.$grand_total, $privateKey),
                ];

                $baseUrl = ($mode === 'production')
                            ? 'https://tripay.co.id/api/transaction/create'
                            : 'https://tripay.co.id/api-sandbox/transaction/create';

                $tripayResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                                      ->asForm() // Kirim sebagai form-data
                                      ->post($baseUrl, $tripayPayload);

                Log::info('Tripay Create Transaction Payload:', $tripayPayload);
                Log::info('Tripay Create Transaction Response:', ['status' => $tripayResponse->status(), 'body' => $tripayResponse->json()]);


                if ($tripayResponse->successful() && $tripayResponse->json()['success'] === true) {
                    $tripayData = $tripayResponse->json()['data'];
                    // Simpan referensi Tripay dan URL pembayaran/QR
                    $order->tripay_reference = $tripayData['reference'] ?? null;
                    $order->payment_url = $tripayData['checkout_url'] ?? $tripayData['pay_url'] ?? $tripayData['qr_url'] ?? null;
                    // Simpan pay_code jika metode VA atau sejenisnya
                    $order->payment_code = $tripayData['pay_code'] ?? null;
                    $order->save();
                    $paymentUrl = $order->payment_url; // URL untuk redirect
                } else {
                     $errorMsg = 'Gagal membuat transaksi pembayaran.';
                     if ($tripayResponse->json() && !empty($tripayResponse->json()['message'])) {
                         $errorMsg .= ' Detail: ' . $tripayResponse->json()['message'];
                     }
                     throw new \Exception($errorMsg);
                }

            } elseif ($validated['payment_method'] === 'cash') {
                 // Pembayaran cash/transfer manual, status tetap 'processing' atau sesuai alur Anda
                 $order->status = 'processing'; // Atau 'waiting_confirmation'
                 $order->save();
                 // Tidak perlu redirect ke payment gateway
            }
             // else: COD sudah dihandle statusnya saat create order


            // 6. Commit transaksi DB
            DB::commit();

            // 7. Hapus keranjang
            session()->forget('cart');

            // 8. Kirim notifikasi jika perlu
            event(new AdminNotificationEvent(
                 'Pesanan Baru Diterima',
                 "Pesanan baru dengan invoice {$order->invoice_number} telah dibuat.",
                 url('/admin/orders/' . $order->id) // Link ke detail order di admin
            ));

             // 9. Redirect ke halaman invoice atau pembayaran
             if ($paymentUrl) {
                 return redirect()->away($paymentUrl); // Redirect ke halaman pembayaran Tripay
             } else {
                 // Redirect ke halaman invoice untuk COD atau Cash
                 return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number])
                     ->with('success', 'Pesanan Anda berhasil dibuat!');
             }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Process Failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString()); // Log trace
            // Kembalikan stok jika order gagal dibuat (lebih kompleks, perlu logika rollback stok)
            return redirect()->route('checkout.index')->with('error', 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage());
        }
    }


    /**
     * Menampilkan halaman invoice setelah checkout.
     */
    public function invoice($invoice)
    {
        if (!$invoice) {
            return redirect()->route('checkout.index')->with('error', 'Nomor invoice tidak valid.');
        }

        // Ambil order beserta relasi yang diperlukan
        $order = Order::where('invoice_number', $invoice)
                      ->with(['items.product', 'items.productVariant.options.productVariantType', 'store', 'user']) // Eager load
                      ->where('user_id', Auth::id()) // Pastikan user hanya bisa lihat order miliknya
                      ->firstOrFail(); // Gagal jika tidak ditemukan atau bukan milik user

        return view('checkout.invoice', compact('order'));
    }

    /**
      * Handle callback dari Tripay.
      */
     public function TripayCallback(Request $request, KiriminAjaService $kiriminAja) // Tambahkan KiriminAjaService
     {
         Log::info('Tripay Callback Received:', $request->all());

         // Validasi Signature (PENTING!)
         $privateKey = config('tripay.private_key');
         $callbackSignature = $request->header('X-Callback-Signature');
         $json = $request->getContent();
         $signature = hash_hmac('sha256', $json, $privateKey);

         if ($callbackSignature !== $signature) {
             Log::error('Tripay Callback: Invalid Signature.', ['received' => $callbackSignature, 'calculated' => $signature]);
             return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
         }

         // Validasi Event
         $event = $request->header('X-Callback-Event');
         if ($event !== 'payment_status') {
              Log::info('Tripay Callback: Ignoring event type.', ['event' => $event]);
             // Hanya proses event payment_status
             return response()->json(['success' => true]); // Beri respons sukses agar Tripay tidak retry
         }

         // Ambil data dari JSON payload
         $data = json_decode($json, true);
         if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
              Log::error('Tripay Callback: Invalid JSON payload.', ['payload' => $json]);
              return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
         }


         $merchantRef = $data['merchant_ref'] ?? null; // Invoice number atau transaction ID
         $tripayRef = $data['reference'] ?? null;
         $status = strtoupper($data['status'] ?? ''); // PAID, EXPIRED, FAILED

         DB::beginTransaction();
         try {
             // Cari berdasarkan merchant_ref (invoice) atau tripay_reference
             $order = Order::where('invoice_number', $merchantRef)
                           ->orWhere('tripay_reference', $tripayRef)
                           ->lockForUpdate() // Kunci row untuk mencegah race condition
                           ->first();

             if ($order) {
                 // Hanya update jika status saat ini 'pending'
                 if ($order->status === 'pending') {
                     if ($status === 'PAID') {
                         $order->status = 'processing'; // Ubah jadi processing setelah dibayar
                         $order->paid_at = now(); // Catat waktu pembayaran

                          // --- KIRIM ORDER KE KIRIMINAJA SETELAH PAID ---
                          // (Pindahkan logika pembuatan order KiriminAja dari 'store' ke sini)
                         // Anda perlu mengambil semua data yang disimpan di $order untuk membuat payload KiriminAja
                          try {
                             // Dapatkan data pengirim (toko) dan penerima dari order
                             $store = $order->store()->with('user')->first(); // Ambil store dan user pemiliknya
                             $user = $order->user; // Relasi user di order

                             if (!$store || !$user) throw new \Exception('Data toko atau user tidak ditemukan untuk order ' . $order->invoice_number);

                             // Dapatkan ID KiriminAja (asumsi sudah divalidasi/disimpan)
                             $storeDistrictId = $store->kiriminaja_district_id ?? null;
                             $storeSubdistrictId = $store->kiriminaja_subdistrict_id ?? null;
                             $userDistrictId = $user->kiriminaja_district_id ?? null; // Idealnya simpan di order juga
                             $userSubdistrictId = $user->kiriminaja_subdistrict_id ?? null; // Idealnya simpan di order juga

                              // Dapatkan Koordinat
                             $storeLat = $store->latitude ? (float) $store->latitude : null;
                             $storeLng = $store->longitude ? (float) $store->longitude : null;
                             $userLat = $order->shipping_latitude ? (float) $order->shipping_latitude : null; // Ambil dari order
                             $userLng = $order->shipping_longitude ? (float) $order->shipping_longitude : null; // Ambil dari order

                              // Parsing shipping method
                             $shippingParts = explode('-', $order->shipping_method);
                             $shipping_type = $shippingParts[0];
                             $shipping_courier = $shippingParts[1];
                             $shipping_service = $shippingParts[2];

                              // Siapkan detail item untuk KiriminAja dari OrderItems
                             $itemDetailsForKiriminAja = [];
                             $isMandatoryInsurance = false; // Cek ulang dari item
                             foreach($order->items as $item) {
                                 $productWeight = 100; // default
                                 $productWidth = 5; $productHeight = 5; $productLength = 5;
                                 $productJenisBarang = 7;
                                 $relatedProduct = $item->productVariant->product ?? $item->product; // Ambil produk utama
                                 if($relatedProduct) {
                                     $productWeight = $item->productVariant->weight ?? $relatedProduct->weight ?? 100;
                                     $productWidth = $relatedProduct->width ?? 5;
                                     $productHeight = $relatedProduct->height ?? 5;
                                     $productLength = $relatedProduct->length ?? 5;
                                     $productJenisBarang = $relatedProduct->jenis_barang ?? 7;
                                     if (in_array($productJenisBarang, [1, 3, 4, 8])) {
                                         $isMandatoryInsurance = true; // Set flag jika ada item yg wajib
                                     }
                                 }
                                 $itemDetailsForKiriminAja[] = [
                                     'item_name' => Str::limit($item->item_name, 100, ''),
                                     'item_quantity' => $item->quantity,
                                     'item_price' => $item->price,
                                     'item_weight' => $productWeight,
                                     'package_type_id' => (int) $productJenisBarang,
                                 ];
                             }

                             // Buat Payload KiriminAja (mirip di method 'store', tapi gunakan data dari $order)
                             $packagePayload = [
                                 'origin_name'       => $store->name ?? 'Toko Sancaka',
                                 'origin_phone'      => $store->user->no_wa ?? config('app.default_phone'),
                                 'origin_address'    => $store->address_detail ?? 'Alamat Toko',
                                 'origin_kecamatan_id' => $storeDistrictId,
                                 'origin_kelurahan_id' => $storeSubdistrictId,
                                 'origin_zipcode'    => $store->postal_code ?? '63271',
                                 'origin_lat'        => $storeLat,
                                 'origin_long'       => $storeLng,
                                 'destination_name'    => $order->shipping_name,
                                 'destination_phone'   => $order->shipping_phone,
                                 'destination_address' => $order->shipping_address,
                                 'destination_kecamatan_id' => $userDistrictId, // Perlu disimpan di order
                                 'destination_kelurahan_id' => $userSubdistrictId, // Perlu disimpan di order
                                 'destination_zipcode' => $order->shipping_postal_code ?? '55598',
                                 'destination_lat'     => $userLat,
                                 'destination_long'    => $userLng,
                                 'destination_address_note' => $order->receiver_note,
                                 'cod'               => 0, // Sudah dibayar via Tripay
                                 'is_insurance'      => $order->insurance_cost > 0 ? 1 : 0,
                                 'insurance_amount'  => $order->subtotal, // Nilai barang untuk asuransi
                                 'service'           => $shipping_courier,
                                 'service_type'      => $shipping_service,
                                 'shipping_cost'     => $order->shipping_cost,
                                 'item_value'        => $order->subtotal,
                                 'items'             => $itemDetailsForKiriminAja,
                                 'package_type_id'   => $isMandatoryInsurance ? ($itemDetailsForKiriminAja[0]['package_type_id'] ?? 7) : 7,
                                 'weight'            => $order->total_weight, // Ambil dari order
                                 'order_id'          => $order->invoice_number, // Gunakan invoice number sbg order ID
                                 'pickup_schedule'   => $kiriminAja->getSchedules()['clock'] ?? null, // Dapatkan jadwal pickup lagi
                                 'category'          => ($order->total_weight >= 30000) ? 'trucking' : 'regular',
                                 'platform_name'     => config('app.name', 'TOKOSANCAKA.COM'),
                                 'package_note'      => $order->sender_note ?? null, // Jika ada catatan pengirim
                             ];

                             $kiriminResponse = null;
                             if ($shipping_type === 'express') {
                                 $kiriminResponse = $kiriminAja->createExpressOrder($packagePayload);
                                 Log::info('KiriminAja Express Create Order Payload (Callback):', $packagePayload);
                             } elseif ($shipping_type === 'instant') {
                                 // Buat payload instant (agak berbeda)
                                 $instantPayload = [ /* ... sesuaikan payload instant ... */ ];
                                 $kiriminResponse = $kiriminAja->createInstantOrder($instantPayload);
                                 Log::info('KiriminAja Instant Create Order Payload (Callback):', $instantPayload);
                             }

                             Log::info('KiriminAja Create Order Response (Callback):', ['response' => $kiriminResponse]);

                             if (!$kiriminResponse || !isset($kiriminResponse['status']) || $kiriminResponse['status'] !== true) {
                                  $errorMessage = 'Gagal membuat pesanan pengiriman setelah pembayaran.';
                                 // Log error tapi JANGAN throw exception agar status order tetap 'paid'
                                 Log::error($errorMessage, ['kirimin_response' => $kiriminResponse, 'order_id' => $order->id]);
                                 // Anda bisa set status order ke 'payment_received_shipping_failed' atau sejenisnya
                                 $order->status = 'payment_received_shipping_failed';
                             } else {
                                 // Simpan AWB/Resi jika ada
                                 $order->shipping_resi = $kiriminResponse['awb'] ?? $kiriminResponse['pickup_number'] ?? null;
                                 $order->kiriminaja_order_id = $kiriminResponse['order_id'] ?? null;
                                 // Status sudah 'processing'
                             }
                          } catch (\Exception $kiriminException) {
                               // Log error tapi JANGAN throw exception
                               Log::error('KiriminAja order creation failed during Tripay callback: ' . $kiriminException->getMessage(), ['order_id' => $order->id]);
                               $order->status = 'payment_received_shipping_failed'; // Tandai order
                          }
                         // --- AKHIR BLOK KIRIM ORDER KIRIMINAJA ---

                     } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
                         $order->status = strtolower($status); // Set status 'expired' or 'failed'
                         // TODO: Kembalikan stok produk jika order expired/failed? (Perlu logika kompleks)
                         // foreach($order->items as $item) { ... }
                     } else {
                         // Status lain seperti UNPAID, abaikan atau log saja
                         Log::info('Tripay Callback: Received status update.', ['invoice' => $merchantRef, 'status' => $status]);
                     }
                     $order->save();

                     // Kirim notifikasi admin
                     event(new AdminNotificationEvent(
                        'Status Pembayaran Order',
                        "Status pembayaran untuk invoice {$order->invoice_number} diubah menjadi {$order->status}.",
                        url('/admin/orders/' . $order->id)
                     ));

                 } else {
                      Log::info('Tripay Callback: Order already processed or in final state.', ['invoice' => $merchantRef, 'current_status' => $order->status, 'received_status' => $status]);
                 }


             } else {
                 // Cari TopUp berdasarkan merchant_ref atau tripay_ref
                 $topUp = TopUp::where('transaction_id', $merchantRef)
                                ->orWhere('tripay_reference', $tripayRef)
                                ->lockForUpdate()
                                ->first();

                 if ($topUp) {
                      // Hanya proses jika status saat ini 'pending'
                     if ($topUp->status === 'pending') {
                         if ($status === 'PAID') {
                             $topUp->status = 'success';
                             $topUp->paid_at = now();
                             $topUp->save();

                             // Tambah saldo user
                             $user = User::find($topUp->customer_id); // Asumsi kolom customer_id di TopUp
                             if ($user) {
                                 $user->increment('saldo', $topUp->amount); // Gunakan increment
                                 event(new SaldoUpdated($user->id_pengguna, $user->saldo));
                                 event(new AdminNotificationEvent('TopUp Saldo Berhasil', "User {$user->name} berhasil top-up Rp " . number_format($topUp->amount), url('/admin/topups'))); // Sesuaikan URL admin
                             } else { Log::warning('Tripay Callback: User not found for successful TopUp.', ['topup_id' => $topUp->id, 'user_id' => $topUp->customer_id]); }

                         } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
                             $topUp->status = strtolower($status);
                             $topUp->save();
                              event(new AdminNotificationEvent('TopUp Saldo Gagal/Expired', "TopUp {$topUp->transaction_id} statusnya: {$topUp->status}.", url('/admin/topups')));
                         } else {
                              Log::info('Tripay Callback: Received TopUp status update.', ['tx_id' => $merchantRef, 'status' => $status]);
                         }
                     } else {
                          Log::info('Tripay Callback: TopUp already processed or in final state.', ['tx_id' => $merchantRef, 'current_status' => $topUp->status, 'received_status' => $status]);
                     }

                 } else {
                      // Cari Pesanan (jika ada model Pesanan terpisah)
                      $pesanan = Pesanan::where('invoice_number', $merchantRef)
                                        ->orWhere('tripay_reference', $tripayRef) // Asumsi ada kolom ini
                                        ->lockForUpdate()
                                        ->first();

                      if ($pesanan) {
                            if ($pesanan->status === 'pending') {
                                if ($status === 'PAID') {
                                    $pesanan->status = 'processing'; // Atau 'paid'
                                    $pesanan->status_pesanan = 'processing';
                                    $pesanan->paid_at = now();
                                    // TODO: Pindahkan logika kirim KiriminAja untuk model Pesanan ke sini
                                } elseif ($status === 'EXPIRED' || $status === 'FAILED') {
                                     $pesanan->status = strtolower($status);
                                     $pesanan->status_pesanan = strtolower($status);
                                     // TODO: Rollback stok untuk model Pesanan
                                }
                                $pesanan->save();
                                 event(new AdminNotificationEvent('Status Pembayaran Pesanan', "Status pembayaran untuk invoice pesanan {$pesanan->invoice_number} diubah menjadi {$pesanan->status}.", url('/admin/pesanan/' . $pesanan->id))); // Sesuaikan URL admin
                            } else {
                                 Log::info('Tripay Callback: Pesanan already processed.', ['invoice' => $merchantRef, 'status' => $pesanan->status]);
                            }
                      } else {
                          Log::warning("Tripay Callback: No Order, TopUp, or Pesanan found for reference.", ['merchant_ref' => $merchantRef, 'tripay_ref' => $tripayRef]);
                      }
                 }
             }

             DB::commit();
             return response()->json(['success' => true]);

         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Tripay Callback Processing Error: '.$e->getMessage() . "\n" . $e->getTraceAsString());
             // Beri tahu Tripay ada error agar bisa coba lagi nanti
             return response()->json(['success' => false, 'message' => 'Internal server error processing callback.'], 500);
         }
     }


} // Akhir Class
