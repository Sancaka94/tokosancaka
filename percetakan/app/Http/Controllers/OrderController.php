<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Logging aktif
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Models
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderAttachment;
use App\Models\Coupon;
use App\Models\Affiliate;

// Services
use App\Services\DokuJokulService;
use App\Services\KiriminAjaService;

class OrderController extends Controller
{
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
     * Fungsi Cerdas: Mencari Koordinat dari Nama Alamat
     */
    private function geocode(string $address): ?array
    {
        Log::info("GEOCODING START: Mencari koordinat untuk -> $address");

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'AplikasiKasir/1.0'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q'            => $address,
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => 'id'
                ]);
            
            if ($response->successful() && isset($response[0]['lat']) && isset($response[0]['lon'])) {
                $lat = (float) $response[0]['lat'];
                $lng = (float) $response[0]['lon'];
                
                Log::info("GEOCODING SUCCESS: Lat: $lat, Lng: $lng");
                return ['lat' => $lat, 'lng' => $lng];
            } else {
                Log::warning("GEOCODING FAILED: Tidak ada hasil dari Nominatim.");
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
            $destSubDistrict = $request->destination_subdistrict_id ?? 0; 
            
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

                if (isset($responseInstant['status']) && $responseInstant['status'] == true) {
                    $instantPrices = $responseInstant['data']['price'] ?? []; 
                    if (isset($instantPrices['price'])) { $instantPrices = [$instantPrices]; } 

                    Log::info("Ongkir INSTANT Sukses: " . count($instantPrices) . " opsi ditemukan.");

                    foreach ($instantPrices as $inst) {
                         if (isset($inst['price']) && $inst['price'] > 0) {
                             $serviceCode = strtolower($inst['service'] ?? 'gojek');
                             $mapData = $courierMap[$serviceCode] ?? null;

                             $formattedRates[] = [
                                'code'    => 'kiriminaja_instant',
                                'name'    => $mapData ? $mapData['name'] : strtoupper($serviceCode),
                                'logo'    => $mapData ? $mapData['logo_url'] : null,
                                'service' => 'Instant (' . ($inst['name'] ?? 'Instant') . ')',
                                'cost'    => (int) $inst['price'],
                                'etd'     => 'Instant',
                             ];
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
     * PROSES ORDER (KIRIM PESANAN)
     */
    public function store(Request $request, DokuJokulService $dokuService, KiriminAjaService $kiriminAja)
    {
        Log::info("STORE ORDER: Memulai proses checkout...");

        // 1. VALIDASI
        $request->validate([
            'items'           => 'required', 
            'total'           => 'required|numeric',
            'payment_method'  => 'required',
            'delivery_type'   => 'required|in:pickup,shipping',
            'shipping_cost'   => 'required_if:delivery_type,shipping|numeric',
            'courier_name'    => 'required_if:delivery_type,shipping|string',
            'destination_text'=> 'nullable|string', 
            'destination_district_id' => 'required_if:delivery_type,shipping',
        ]);

        $cartItems = json_decode($request->items, true);
        if (!is_array($cartItems) || count($cartItems) < 1) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong.'], 400);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $finalCart = []; 
            $totalWeight = 0;

            // 2. STOK & HARGA
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['id']);
                if (!$product) throw new \Exception("Produk ID {$item['id']} tidak ditemukan.");
                if ($product->stock < $item['qty']) throw new \Exception("Stok '{$product->name}' kurang.");

                $lineTotal = $product->sell_price * $item['qty'];
                $subtotal += $lineTotal;
                
                $weight = $product->weight ?? 1000; 
                $totalWeight += ($weight * $item['qty']);

                $finalCart[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'subtotal' => $lineTotal
                ];
            }

            // 3. DISKON KUPON
            $discount = 0;
            $couponId = null;
            if ($request->coupon) {
                $couponDB = Coupon::where('code', $request->coupon)->first();
                if ($couponDB && $couponDB->is_active) {
                    $isValid = true; 
                    if ($isValid) {
                        $couponId = $couponDB->id;
                        $discount = ($couponDB->type == 'percent') ? $subtotal * ($couponDB->value / 100) : $couponDB->value;
                        $couponDB->increment('used_count');
                    }
                }
            }

            $finalPrice = max(0, $subtotal - $discount);

            // 4. DATA CUSTOMER
            $paymentStatus = 'unpaid';
            $paymentUrl    = null;    
            $changeAmount  = 0;     
            $customerName  = $request->customer_name ?? 'Guest';
            $customerPhone = $request->customer_phone ?? '08123456789';
            $customerEmail = 'customer@tokosancaka.com'; 
            $note          = $request->note;
            
             if ($request->customer_id) {
                $affiliateMember = Affiliate::find($request->customer_id);
                if ($affiliateMember) {
                    $customerName  = $affiliateMember->name; 
                    $customerPhone = $affiliateMember->whatsapp;
                    if (!empty($affiliateMember->email)) $customerEmail = $affiliateMember->email;
                }
            }

            // 5. LOGIK BAYAR
             switch ($request->payment_method) {
                case 'cash':
                     $cashReceived = (int) $request->cash_amount;
                    if ($cashReceived < $finalPrice) throw new \Exception("Uang tunai kurang!");
                    $changeAmount = $cashReceived - $finalPrice;
                    $paymentStatus = 'paid'; 
                    $note .= "\n[INFO PEMBAYARAN]\nMetode: Tunai\nDiterima: Rp " . number_format($cashReceived,0,',','.') . "\nKembali: Rp " . number_format($changeAmount,0,',','.');
                    break;
                case 'affiliate_balance':
                     if (!$request->customer_id) throw new \Exception("Wajib pilih Member Afiliasi.");
                    $affiliatePayor = Affiliate::lockForUpdate()->find($request->customer_id);
                    if (!$affiliatePayor || !Hash::check($request->affiliate_pin, $affiliatePayor->pin)) throw new \Exception("PIN Salah!");
                    if ($affiliatePayor->balance < $finalPrice) throw new \Exception("Saldo Kurang.");
                    $affiliatePayor->decrement('balance', $finalPrice);
                    $paymentStatus = 'paid'; 
                    break;
                case 'tripay':
                case 'doku':
                    $paymentStatus = 'unpaid';
                    break;
            }

            // 6. BUAT ORDER KE DB
            $orderNumber = 'INV-' . date('YmdHis') . rand(100, 999);
            $shippingRef = null;

            // =========================================================
            // 7. PROSES KIRIM KE KIRIMINAJA (REGULER & INSTANT)
            // =========================================================
            if ($request->delivery_type === 'shipping') {
                Log::info("DELIVERY: Memulai Request KiriminAja...");
                
                // A. Cari Koordinat Tujuan
                $destLat = null;
                $destLng = null;
                
                if ($request->filled('destination_text')) {
                    $geo = $this->geocode($request->destination_text);
                    if ($geo) {
                        $destLat = (string)$geo['lat'];
                        $destLng = (string)$geo['lng'];
                        Log::info("DELIVERY GEO: Ditemukan $destLat, $destLng");
                    } else {
                        Log::warning("DELIVERY GEO: Gagal menemukan koordinat tujuan.");
                    }
                }

                $serviceCode = strtolower($request->courier_service); 
                $isInstant = (str_contains($serviceCode, 'gosend') || str_contains($serviceCode, 'grab'));
                $kaResponse = null;

                if ($isInstant) {
                    Log::info("TIPE KURIR: INSTANT ($serviceCode)");
                    if (!$destLat || !$destLng) {
                        throw new \Exception("Pengiriman Instan GAGAL: Koordinat alamat tujuan tidak ditemukan.");
                    }

                    $instantPayload = [
                        'order_id' => $orderNumber,
                        'service'  => $serviceCode,
                        'item_price' => $subtotal,
                        'origin' => [
                            'lat' => config('services.kiriminaja.origin_lat'),
                            'long' => config('services.kiriminaja.origin_long'),
                            'address' => config('services.kiriminaja.origin_address'),
                            'phone' => '081234567890',
                            'name' => 'Toko Sancaka'
                        ],
                        'destination' => [
                            'lat' => $destLat,
                            'long' => $destLng,
                            'address' => $request->destination_text,
                            'phone' => $customerPhone,
                            'name' => $customerName
                        ],
                        'weight' => $totalWeight,
                        'vehicle' => 'motor',
                    ];
                    
                    Log::info("PAYLOAD INSTANT:", $instantPayload);
                    $kaResponse = $kiriminAja->createInstantOrder($instantPayload);

                } else {
                    Log::info("TIPE KURIR: REGULER ($serviceCode)");
                    $kaPayload = [
                        'order_id'       => $orderNumber,
                        'service'        => $serviceCode, 
                        'package_type_id'=> 1,
                        'cod'            => 0,
                        'item_value'     => $subtotal,
                        'weight'         => $totalWeight,
                        'origin_name'    => 'Toko Sancaka',
                        'origin_phone'   => '081234567890',
                        'origin_address' => config('services.kiriminaja.origin_address'),
                        'origin_district_id' => config('services.kiriminaja.origin_district_id'),
                        'destination_name'    => $customerName,
                        'destination_phone'   => $customerPhone,
                        'destination_address' => $request->destination_text,
                        'destination_district_id' => $request->destination_district_id,
                        'destination_zip_code' => $request->postal_code ?? '',
                    ];

                    if ($destLat && $destLng) {
                        $kaPayload['destination_latitude']  = $destLat;
                        $kaPayload['destination_longitude'] = $destLng;
                        Log::info("Menyisipkan Koordinat ke Payload Reguler: $destLat, $destLng");
                    }

                    $kaResponse = $kiriminAja->createExpressOrder($kaPayload);
                }

                // Log Response KiriminAja
                if (isset($kaResponse['status']) && $kaResponse['status'] == true) {
                    $shippingRef = $kaResponse['data']['order_id'] ?? $kaResponse['data']['payment_ref'] ?? null;
                    Log::info("KIRIMINAJA SUKSES. Ref: $shippingRef");
                    $note .= "\n[INFO PENGIRIMAN]\nOrder ID KiriminAja: " . $shippingRef;
                } else {
                    $errMsg = $kaResponse['text'] ?? 'Gagal koneksi ke kurir.';
                    Log::error("KIRIMINAJA GAGAL: " . json_encode($kaResponse));
                    throw new \Exception("Gagal Membuat Order Kurir: " . $errMsg);
                }
            }
            // =========================================================

            $order = Order::create([
                'order_number'    => $orderNumber,
                'user_id'         => null,
                'customer_name'   => $customerName,
                'customer_phone'  => $customerPhone,
                'coupon_id'       => $couponId,
                'total_price'     => $subtotal,
                'discount_amount' => $discount,
                'final_price'     => $finalPrice,
                'payment_method'  => $request->payment_method,
                'status'          => ($paymentStatus === 'paid') ? 'processing' : 'pending', 
                'payment_status'  => $paymentStatus,
                'note'            => $note,
                'shipping_cost'   => $request->delivery_type === 'shipping' ? $request->shipping_cost : 0,
                'courier_service' => $request->delivery_type === 'shipping' ? $request->courier_name : null,
                'shipping_ref'    => $shippingRef, 
            ]);

            // 8. PROSES BAYAR
            if ($request->payment_method === 'tripay') {
                if (empty($request->payment_channel)) throw new \Exception("Harap pilih Channel Pembayaran.");
                $orderItems = [];
                foreach ($finalCart as $item) {
                    $orderItems[] = [
                        'sku'      => (string) $item['product']->id,
                        'name'     => $item['product']->name,
                        'price'    => (int) $item['product']->sell_price,
                        'quantity' => (int) $item['qty']
                    ];
                }
                $tripayRes = $this->_createTripayTransaction($order, $request->payment_channel, (int)$finalPrice, $customerName, $customerEmail, $customerPhone, $orderItems);
                if (!$tripayRes['success']) throw new \Exception("Tripay Gagal: " . ($tripayRes['message'] ?? 'Unknown Error'));
                $paymentUrl = $tripayRes['data']['checkout_url'];
                $order->update(['payment_url' => $paymentUrl]);
            }
            elseif ($request->payment_method === 'doku') {
                $dokuData = ['name' => $customerName, 'email' => $customerEmail, 'phone' => $customerPhone];
                $paymentUrl = $dokuService->createPayment($order->order_number, $order->final_price, $dokuData);
                if (empty($paymentUrl)) throw new \Exception("Gagal DOKU.");
                $order->update(['payment_url' => $paymentUrl]);
            }

            // 9. SIMPAN DETAIL & UPLOAD
             foreach ($finalCart as $data) {
                $prod = $data['product'];
                OrderDetail::create([
                    'order_id'            => $order->id,
                    'product_id'          => $prod->id,
                    'product_name'        => $prod->name,    
                    'base_price_at_order' => $prod->base_price, 
                    'price_at_order'      => $prod->sell_price, 
                    'quantity'            => $data['qty'],
                    'subtotal'            => $data['subtotal'],
                ]);
                $prod->decrement('stock', $data['qty']);
                $prod->increment('sold', $data['qty']);
                if ($prod->stock <= 0) $prod->update(['stock_status' => 'unavailable']);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('orders', 'public');
                    OrderAttachment::create([
                        'order_id'  => $order->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                    ]);
                }
            }

            DB::commit();

            if ($request->coupon && $paymentStatus == 'paid') {
                $this->_processAffiliateCommission($request->coupon, $finalPrice);
            }
            $this->_sendWaNotification($order, $finalPrice, $paymentUrl, $paymentStatus);

            return response()->json([
                'status'         => 'success',
                'message'        => 'Transaksi Berhasil!',
                'invoice'        => $order->order_number,
                'order_id'       => $order->id,
                'payment_url'    => $order->payment_url,
                'change_amount'  => $changeAmount,
                'payment_method' => $request->payment_method
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ORDER FAILED (Exception): ' . $e->getMessage());
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
}