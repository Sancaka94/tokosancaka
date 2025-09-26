<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DanaController;
use Illuminate\Support\Facades\Log;
use App\Models\TopUp;
use App\Models\User;
use App\Events\SaldoUpdated;
use App\Events\AdminNotificationEvent;
use App\Services\KiriminAjaService;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use App\Models\Pesanan;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function geocode($address)
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";

        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (support@tokosancaka.com)'
        ])->get($url)->json();

        if (!empty($response[0])) {
            return [
                'lat' => (float) $response[0]['lat'],
                'lng' => (float) $response[0]['lon'],
            ];
        }

        return null;
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

        $user  = Auth::user();
        $firstProduct = Product::find(array_key_first($cart));
        $store = $firstProduct->store;
        
         if (empty($store->village) || empty($store->district) || empty($store->regency) || empty($store->province)) {
             return redirect()->route('cart.index')
                ->with('error', 'Alamat toko tidak lengkap. Mohon lengkapi data lokasi toko terlebih dahulu.');
                
        }
        
        if (empty($user->village) || empty($user->district) || empty($user->regency) || empty($user->province)) {
             return redirect()->route('cart.index')
                ->with('error', 'Alamat penerima tidak lengkap. Mohon lengkapi data lokasi Anda terlebih dahulu.');
                
        }

        $storeSearch = $store->village . ', ' . $store->district . ', ' . $store->regency . ', ' . $store->province;
        $userSearch  = $user->village . ', ' . $user->district . ', ' . $user->regency . ', ' . $user->province;

        $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
        $userAddrRes  = $kiriminAja->searchAddress($userSearch);

        $storeAddr = $storeAddrRes['data'][0] ?? null;
        $userAddr  = $userAddrRes['data'][0] ?? null;

        if (!$storeAddr || !$userAddr) {
            return redirect()->route('cart.index')
                ->with('error', 'Alamat tidak ditemukan. Periksa kembali alamat Anda.');
        }

        $totalWeight = collect($cart)->sum(fn($item) => $item['weight'] * $item['quantity']);
        $finalWeight = max(1000, $totalWeight); // Pastikan berat minimal 1000 gram

        $itemValue   = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        
        $category = $finalWeight >= 30000 ? 'trucking' : 'regular'; 
        
        $expressOptions = $kiriminAja->getExpressPricing(
            $storeAddr['district_id'],
            $storeAddr['subdistrict_id'],
            $userAddr['district_id'],
            $userAddr['subdistrict_id'],
            $finalWeight,
            10,10,10,
            $itemValue,
            null,
            $category 
        );

        $storeLat = $store->latitude;
        $storeLng = $store->longitude;
        $userLat  = $user->latitude;
        $userLng  = $user->longitude;

        if (!$storeLat || !$storeLng) {
            $geo = $this->geocode($storeSearch);
            if ($geo) {
                $storeLat = $geo['lat'];
                $storeLng = $geo['lng'];
            }
        }

        if (!$userLat || !$userLng) {
            $geo = $this->geocode($userSearch);
            if ($geo) {
                $userLat = $geo['lat'];
                $userLng = $geo['lng'];
            }
        }
        
    
        $instantOptions = null;
        if ($storeLat && $storeLng && $userLat && $userLng) {
            $instantOptions = $kiriminAja->getInstantPricing(
                $storeLat,
                $storeLng,
                $store->address_detail,
                $userLat,
                $userLng,
                $user->address_detail,
                $finalWeight,
                $itemValue
            );
            
        }
        
    
        return view('checkout.index', compact('cart', 'expressOptions', 'instantOptions'));
    }



    /**
     * Memproses dan menyimpan pesanan baru.
     */
    public function store(Request $request,KiriminAjaService $kiriminAja)
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
            return redirect()->route('etalase.index')->with('error', 'Silakan lengkapi alamat pengiriman dahulu.');
        }
    
        DB::beginTransaction();
    
        try {
            $subtotal = collect($cart)->sum(fn($details) => $details['price'] * $details['quantity']);
            
            [$type, $courier, $service, $shipCost, $asrCost] = explode('-', $request->shipping_method);
            $shipping_type = $type;
            $shipping_cost = (int) $shipCost;
            $insurance_cost = (int) $asrCost;
    
            $firstProduct = Product::find(array_key_first($cart));
            $isMandatoryInsurance = in_array((int) $firstProduct->jenis_barang, [1, 3, 4, 8]);
            
            // Hitung total dasar (sebelum biaya COD)
            $base_total = $subtotal + $shipping_cost;
            if ($isMandatoryInsurance) {
                $base_total += $insurance_cost;
            }

            $cod_add_cost = 0;
            if ($request->payment_method === 'cod') {
                if ($shipping_type !== 'express') {
                    return redirect()->back()->with('error', 'COD hanya tersedia untuk pengiriman express.');
                }
                // REVISI: Hitung biaya COD di backend sesuai dokumentasi
                $codFeePercentage = 0.03; // 3% - PENTING: Sesuaikan nilai ini atau pindahkan ke config
                $cod_add_cost = ceil($base_total * $codFeePercentage);
            }

            $grand_total = $base_total + $cod_add_cost;
            
            $store = $firstProduct->store;
                  
            do {
                $invoiceNumber = 'SCK-' . strtoupper(Str::random(8));
            } while (Order::where('invoice_number', $invoiceNumber)->exists());
            
            $order = Order::create([
                'store_id'        => $store->id,
                'user_id'         => $user->id_pengguna,
                'invoice_number'  => $invoiceNumber,
                'subtotal'        => $subtotal,
                'shipping_cost'   => $shipping_cost,
                'total_amount'    => $grand_total, // Gunakan grand total yang sudah dihitung
                'shipping_method' => $request->shipping_method,
                'payment_method'  => $request->payment_method,
                'status'          => $request->payment_method === 'cod' ? 'processing' : 'pending',
                'shipping_address'=> $user->address_detail ?? 'Alamat tidak diatur',
                'cod_fee'         => $cod_add_cost, // Simpan biaya tambahan COD yang dihitung
            ]);

    
            $orderItemsPayload = []; 
            
            foreach ($cart as $product_id => $details) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product_id,
                    'quantity'   => $details['quantity'],
                    'price'      => $details['price'],
                ]);
                
                $product = Product::find($product_id);
                if ($product) {
                    $product->stock = max(0, $product->stock - $details['quantity']); 
                    $product->save();
                }
            
                $orderItemsPayload[] = [
                    'sku'        => $product_id,
                    'name'        => $details['name'] ?? 'Produk #' . $product_id,
                    'price'       => $details['price'],
                    'quantity'    => $details['quantity'],
                ];
            }
            
            $orderItemsPayload[] = [
                'sku'     => 'SHIPPING',
                'name'    => 'Ongkos Kirim',
                'price'   => $shipping_cost,
                'quantity' => 1,
            ];
            
            if($cod_add_cost > 0) {
                $orderItemsPayload[] = [
                    'sku'     => 'CODFEES',
                    'name'    => 'Cod Fee',
                    'price'   => $cod_add_cost,
                    'quantity' => 1,
                ];
            }
            
            if($insurance_cost > 0 && $isMandatoryInsurance) {
                $orderItemsPayload[] = [
                    'sku'     => 'INSURANCE',
                    'name'    => 'Asuransi',
                    'price'   => $insurance_cost,
                    'quantity' => 1,
                ];
            }
            
    
            if ($shipping_type === 'express' && $request->payment_method == 'cod') {
    
                $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province;
                $userSearch  = $user->village . ', ' . $user->regency . ', ' . $user->province;
    
                $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                $userAddrRes  = $kiriminAja->searchAddress($userSearch);
    
                $storeAddr = $storeAddrRes['data'][0] ?? null;
                $userAddr  = $userAddrRes['data'][0] ?? null;
    
                $storeDistrictId = $storeAddr['district_id'] ?? null;
                $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                $userDistrictId = $userAddr['district_id'] ?? null;
                $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                
                $storeLat = $store->latitude;
                $storeLng = $store->longitude;

                if (!$storeLat || !$storeLng) {
                    $geo = $this->geocode($storeSearch);
                    if ($geo) {
                        $storeLat = $geo['lat'];
                        $storeLng = $geo['lng'];
                        $pesanan->latitude = $storeLat;
                    $pesanan->longitude = $storeLng;
                       $pesanan->save();
                    }
                }
            
                $schedule = $kiriminAja->getSchedules();

                $totalWeight = collect($cart)->sum(fn($item) => $item['weight'] * $item['quantity']);
                $finalWeight = max(1000, $totalWeight);
                $category = $finalWeight >= 30000 ? 'trucking' : 'regular';
    
                $packages = collect($cart)->map(function ($item, $product_id) use ($order, $user, $userDistrictId, $userSubdistrictId, $subtotal, $shipping_cost, $courier, $isMandatoryInsurance, $insurance_cost, $service, $request, $grand_total) {
                $product = \App\Models\Product::find($product_id);
            
                return [
                    'order_id' => $order->invoice_number,
                    'destination_name' => $user->nama_lengkap,
                    'destination_phone' => $user->no_wa,
                    'destination_address' => $user->address_detail,
                    'destination_kecamatan_id' => $userDistrictId,
                    'destination_kelurahan_id' => $userSubdistrictId,
                    'destination_zipcode' => $user->postal_code ?? 55598,
                    'weight' => $item['weight'] * $item['quantity'],
                    'width' => $product->width ?? 5,
                    'height' => $product->height ?? 5,
                    'length' => $product->length ?? 5,
                    'item_value' => $item['price'] * $item['quantity'],
                    'shipping_cost' => $shipping_cost,
                    'service' => $courier,
                    'insurance_amount' => $isMandatoryInsurance ? $insurance_cost : 0,
                    'service_type' => $service,
                    'item_name' => $product->name ?? 'Produk',
                    'package_type_id' => (int) $product->jenis_barang,
                    'cod' => $request->payment_method === 'cod' ? $grand_total : 0,
                ];
            })->values()->toArray();

                
                $payload = [
                    'address' => $store->address_detail,
                    'phone' => $store->user->no_wa,
                    'kecamatan_id' => $storeDistrictId,
                    'kelurahan_id' => $storeSubdistrictId,
                    'latitude' => $storeLat,
                    'longitude' => $storeLng,
                    'packages' => $packages,
                    'name' => $store->name,
                    'zipcode' => $store->postal_code ?? '63271',
                    'platform_name' => 'TOKOSANCAKA.COM',
                    'schedule' => $schedule['clock'] ?? null,
                    'category' => $category,
                ];
                
                $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                
                if ($kiriminResponse['status'] === true) {
                    // Success
                } else {
                        DB::rollBack();
                    
                        if (!empty($kiriminResponse['errors'])) {
                            $errorMessage = collect($kiriminResponse['errors'])
                                ->flatten()
                                ->implode(', ');
                        } else {
                            $errorMessage = $kiriminResponse['text'] ?? 'Gagal membuat order.';
                        }
                    
                        throw new \Exception($errorMessage);
                    }
            } elseif($request->payment_method == 'cash') {
                if($shipping_type === 'express') {
                    $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province;
                    $userSearch  = $user->village . ', ' . $user->regency . ', ' . $user->province;
    
                    $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                    $userAddrRes  = $kiriminAja->searchAddress($userSearch);
    
                    $storeAddr = $storeAddrRes['data'][0] ?? null;
                    $userAddr  = $userAddrRes['data'][0] ?? null;
    
                    $storeDistrictId = $storeAddr['district_id'] ?? null;
                    $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                    $userDistrictId = $userAddr['district_id'] ?? null;
                    $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                    
                    $storeLat = $store->latitude;
                    $storeLng = $store->longitude;
    
                    if (!$storeLat || !$storeLng) {
                        $geo = $this->geocode($storeSearch);
                        if ($geo) {
                            $storeLat = $geo['lat'];
                            $storeLng = $geo['lng'];
                        }
                    }
                
                    $schedule = $kiriminAja->getSchedules();
    
                    $totalWeight = collect($cart)->sum(fn($item) => $item['weight'] * $item['quantity']);
                    $finalWeight = max(1000, $totalWeight);
                    $category = $finalWeight >= 30000 ? 'trucking' : 'regular';
    
                    $packages = collect($cart)->map(function ($item, $product_id) use ($order, $user, $userDistrictId, $userSubdistrictId, $subtotal, $shipping_cost, $courier, $isMandatoryInsurance, $insurance_cost, $service, $request, $grand_total) {
                    $product = \App\Models\Product::find($product_id);
                
                    return [
                        'order_id' => $order->invoice_number,
                        'destination_name' => $user->nama_lengkap,
                        'destination_phone' => $user->no_wa,
                        'destination_address' => $user->address_detail,
                        'destination_kecamatan_id' => $userDistrictId,
                        'destination_kelurahan_id' => $userSubdistrictId,
                        'destination_zipcode' => $user->postal_code ?? 55598,
                        'weight' => $item['weight'] * $item['quantity'],
                        'width' => $product->width ?? 5,
                        'height' => $product->height ?? 5,
                        'length' => $product->length ?? 5,
                        'item_value' => $item['price'] * $item['quantity'],
                        'shipping_cost' => $shipping_cost,
                        'service' => $courier,
                        'insurance_amount' => $isMandatoryInsurance ? $insurance_cost : 0,
                        'service_type' => $service,
                        'item_name' => $product->name ?? 'Produk',
                        'package_type_id' => (int) $product->jenis_barang,
                        'cod' => 0,
                    ];
                })->values()->toArray();
    
                    
                    $payload = [
                        'address' => $store->address_detail,
                        'phone' => $store->user->no_wa,
                        'kecamatan_id' => $storeDistrictId,
                        'kelurahan_id' => $storeSubdistrictId,
                        'latitude' => $storeLat,
                        'longitude' => $storeLng,
                        'packages' => $packages,
                        'name' => $store->name,
                        'zipcode' => $store->postal_code ?? '63271',
                        'platform_name' => 'TOKOSANCAKA.COM',
                        'schedule' => $schedule['clock'] ?? null,
                        'category' => $category,
                    ];
                    
                    $kiriminResponse = $kiriminAja->createExpressOrder($payload);
                    
                    if ($kiriminResponse['status'] === true) {
                        // Success
                    } else {
                            DB::rollBack();
                        
                            if (!empty($kiriminResponse['errors'])) {
                                $errorMessage = collect($kiriminResponse['errors'])
                                    ->flatten()
                                    ->implode(', ');
                            } else {
                                $errorMessage = $kiriminResponse['text'] ?? 'Gagal membuat order.';
                            }
                        
                            throw new \Exception($errorMessage);
                        }
                } else {
                    $storeSearch = $store->village . ', ' . $store->regency . ', ' . $store->province;
                    $userSearch  = $user->village . ', ' . $user->regency . ', ' . $user->province;
    
                    $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                    $userAddrRes  = $kiriminAja->searchAddress($userSearch);
    
                    $storeAddr = $storeAddrRes['data'][0] ?? null;
                    $userAddr  = $userAddrRes['data'][0] ?? null;
    
                    $storeDistrictId = $storeAddr['district_id'] ?? null;
                    $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                    $userDistrictId = $userAddr['district_id'] ?? null;
                    $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                    
                    $storeLat = $store->latitude;
                    $storeLng = $store->longitude;
    
                    if (!$storeLat || !$storeLng) {
                        $geo = $this->geocode($storeSearch);
                        if ($geo) {
                            $storeLat = $geo['lat'];
                            $storeLng = $geo['lng'];
                        }
                    }
                    
                     if (!$userLat || !$userLng) {
                            $geo = $this->geocode($userSearch);
                            if ($geo) {
                                $userLat = $geo['lat'];
                                $userLng = $geo['lng'];
                            }
                        }
                
                    $schedule = $kiriminAja->getSchedules();
                    
                    
                            
                    $kiriminResponse = $kiriminAja->createInstantOrder([
                                'service' => $courier,
                                'service_type' => $service,
                                'vehicle' => 'motor',
                                'order_prefix' => $order->invoice_number,
                                'packages' => [
                                    [
                                        'destination_name' => $order->user->nama_lengkap,
                                        'destination_phone' => $order->user->no_wa,
                                        'destination_lat' => $userLat,
                                        'destination_long' => $userLng,
                                        'destination_address' => $order->shipping_address,
                                         'destination_address_note' => $request->receiver_note ?? '-',
                                        'origin_name' => $order->store->name ?? 'Toko Penjual',
                                        'origin_phone' => $order->store->user->no_wa ?? '-',
                                        'origin_lat' => $storeLat,
                                        'origin_long' => $storeLng,
                                        'origin_address' => $order->store->address_detail,
                                         'origin_address_note' => $request->sender_note ?? '-',
                                        'shipping_price' => (int) $shipping_cost,
                                        'item' => [
                                            'name' => 'Pesanan ' . $order->invoice_number,
                                            'description' => 'Pesanan dari toko',
                                            'price' => $order->subtotal,
                                            'weight' => $finalWeight,
                                        ]
                                    ]
                                ]
                            ]);
                            
                    if ($kiriminResponse['status'] === true) {
                        // Success
                    } else {
                            DB::rollBack();
                        
                            if (!empty($kiriminResponse['errors'])) {
                                $errorMessage = collect($kiriminResponse['errors'])
                                    ->flatten()
                                    ->implode(', ');
                            } else {
                                $errorMessage = $kiriminResponse['text'] ?? 'Gagal membuat order.';
                            }
                        
                            throw new \Exception($errorMessage);
                        }
                }
            }
    
            DB::commit();
            session()->forget('cart');
    
            if ($request->payment_method === 'cod') {
                return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number]);
            }
    
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
                'expired_time'   => time() + (24 * 60 * 60),
                'signature'      => hash_hmac('sha256', $merchantCode.$order->invoice_number.$grand_total, $privateKey),
            ];
    
            $baseUrl = $mode === 'production' 
                ? 'https://tripay.co.id/api/transaction/create' 
                : 'https://tripay.co.id/api-sandbox/transaction/create';
    
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey
                ],
            ]);
            $result = curl_exec($ch);
            $response = json_decode($result, true);
    
            if (isset($response['success']) && $response['success'] === true) {
                $order->payment_url = $response['data']['qr_url'] 
                                         ?? $response['data']['pay_url'] 
                                         ?? $response['data']['pay_code'] 
                                         ?? $response['data']['checkout_url'] 
                                         ?? null;
                $order->save();
    
                DB::commit();
                session()->forget('cart');
    
                return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number]);
            } else {
                DB::rollBack();
                throw new \Exception('Gagal membuat transaksi di Tripay');
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout Gagal: ' . $e->getMessage());
            return redirect()->route('checkout.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    
    public function invoice($invoice)
    {
        if (!$invoice) {
            return redirect()->route('checkout.index')->with('error', 'Invoice tidak ditemukan.');
        }
        $order = Order::where('invoice_number', $invoice)->firstOrFail();
        
        return view('checkout.invoice', compact('order'));
    }
    
    public function TripayCallback(Request $request,KiriminAjaService $kiriminAja)
    {
        DB::beginTransaction();
        Log::info('Tripay Callback:', $request->all());
    
        $orderId = $request->input('merchant_ref');
        $status  = strtoupper($request->input('status'));
    
        try {
            $order = Order::where('invoice_number', $orderId)->first();
    
            if ($order) {
                    if ($status === 'PAID') {
                        $order->status = 'paid';
                        
                        [$type, $courier, $service, $shipCost, $asrCost] = explode('-', $order->shipping_method);
            
                        $storeSearch = $order->store->village . ', ' . $order->store->regency . ', ' . $order->store->province;
                        $userSearch  = $order->user->village . ', ' . $order->user->regency . ', ' . $order->user->province;
            
                        $storeAddrRes = $kiriminAja->searchAddress($storeSearch);
                        $userAddrRes  = $kiriminAja->searchAddress($userSearch);
            
                        $storeAddr = $storeAddrRes['data'][0] ?? null;
                        $userAddr  = $userAddrRes['data'][0] ?? null;
            
                        $storeDistrictId = $storeAddr['district_id'] ?? null;
                        $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                        $userDistrictId = $userAddr['district_id'] ?? null;
                        $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
            
                        $storeLat = $order->store->latitude;
                        $storeLng = $order->store->longitude;
                        $userLat  = $order->user->latitude;
                        $userLng  = $order->user->longitude;
                    
                        if (!$storeLat || !$storeLng) {
                            $geo = $this->geocode($storeSearch);
                            if ($geo) {
                                $storeLat = $geo['lat'];
                                $storeLng = $geo['lng'];
                            }
                        }
                    
                        if (!$userLat || !$userLng) {
                            $geo = $this->geocode($userSearch);
                            if ($geo) {
                                $userLat = $geo['lat'];
                                $userLng = $geo['lng'];
                            }
                        }
                        
                        $mandatoryTypes = [1, 3, 4, 8];
            
                        $itemType = (int) $order->items->first()->product->jenis_barang;
                        
                        $isMandatory = in_array($itemType, $mandatoryTypes) ? 1 : 0;
                        
            
                        if ($type === 'express' || $type === 'instant') {
                            $schedule = $kiriminAja->getSchedules();

                            $calculatedWeight = $order->items->sum(fn($item) => ($item->product->weight ?? 0) * $item->quantity);
                            $finalWeight = max(1000, $calculatedWeight);
                            $category = $finalWeight >= 30000 ? 'trucking' : 'regular';
                            
                            $packages = $order->items->map(function ($item) use ($order, $userDistrictId, $userSubdistrictId, $shipCost, $courier, $service, $asrCost, $isMandatory) {
                                $product = $item->product;
                            
                                return [
                                    'order_id' => $order->invoice_number,
                                    'destination_name' => $order->user->nama_lengkap,
                                    'destination_phone' => $order->user->no_wa,
                                    'destination_address' => $order->shipping_address,
                                    'destination_kecamatan_id' => $userDistrictId,
                                    'destination_kelurahan_id' => $userSubdistrictId,
                                    'destination_zipcode' => $order->user->postal_code ?? 55598,
                                    'weight' => ($product->weight ?? 0) * $item->quantity,
                                    'width' => $product->width ?? 5,
                                    'height' => $product->height ?? 5,
                                    'length' => $product->length ?? 5,
                                    'item_value' => $item->price * $item->quantity,
                                    'shipping_cost' => (int) $shipCost,
                                    'service' => $courier,
                                    'service_type' => $service,
                                    'insurance_amount' => (int) $asrCost > 0 && $isMandatory ? (int) $asrCost : 0,
                                    'item_name' => $product->name ?? 'Produk',
                                    'package_type_id' => (int) $product->jenis_barang,
                                    'cod' => 0,
                                ];
                            })->toArray();
                            
                            if ($type === 'express') {
                                $kiriminResponse = $kiriminAja->createExpressOrder([
                                    'address' => $order->store->address_detail,
                                    'phone' => $order->store->user->no_wa,
                                    'kecamatan_id' => $storeDistrictId,
                                    'kelurahan_id' => $storeSubdistrictId,
                                    'latitude' => $storeLat,
                                    'longitude' => $storeLng,
                                    'packages' => $packages, 
                                    'name' => $order->store->name,
                                    'zipcode' => $order->store->postal_code ?? '63271',
                                    'platform_name' => 'TOKOSANCAKA.COM',
                                    'schedule' => $schedule['clock'] ?? null,
                                    'category' => $category,
                                ]);
                            } else { // instant
                                $kiriminResponse = $kiriminAja->createInstantOrder([
                                    'service' => $courier,
                                    'service_type' => $service,
                                    'vehicle' => 'motor',
                                    'order_prefix' => $order->invoice_number,
                                    'packages' => [
                                        [
                                            'destination_name' => $order->user->nama_lengkap,
                                            'destination_phone' => $order->user->no_wa,
                                            'destination_lat' => $userLat,
                                            'destination_long' => $userLng,
                                            'destination_address' => $order->shipping_address,
                                             'destination_address_note' => $request->receiver_note ?? '-',
                                            'origin_name' => $order->store->name ?? 'Toko Penjual',
                                            'origin_phone' => $order->store->user->no_wa ?? '-',
                                            'origin_lat' => $storeLat,
                                            'origin_long' => $storeLng,
                                            'origin_address' => $order->store->address_detail,
                                             'origin_address_note' => $request->sender_note ?? '-',
                                            'shipping_price' => (int) $shipCost,
                                            'item' => [
                                                'name' => 'Pesanan ' . $order->invoice_number,
                                                'description' => 'Pesanan dari toko',
                                                'price' => $order->subtotal,
                                                'weight' => $finalWeight,
                                            ]
                                        ]
                                    ]
                                ]);
                            }
            
                            if (!empty($kiriminResponse['status']) && $kiriminResponse['status'] === true) {
                                $order->status = 'processing';
                            }
                        }
            
                        event(new AdminNotificationEvent(
                            'Order Update',
                            "Order dengan invoice {$order->invoice_number} statusnya kini: {$order->status}",
                            '/admin/orders'
                        ));
                    } elseif ($status === 'FAILED') {
                        $order->status = 'failed';
                    } else {
                        $order->status = 'pending';
                    }
                    $order->save();
                    
                    event(new AdminNotificationEvent(
                        'Order Update',
                        "Order dengan invoice {$order->invoice_number} statusnya kini: {$order->status}",
                        '/admin/orders'
                    ));
            } else {
                $topUp = TopUp::where('transaction_id', $orderId)->first();
    
                if ($topUp) {
                    if ($status === 'PAID') {
                        $topUp->status = 'success';
                        $topUp->save();
    
                        $user = User::find($topUp->customer_id);
                        if ($user) {
                            $user->saldo += $topUp->amount;
                            $user->save();
    
                            event(new SaldoUpdated($user->id_pengguna, $user->saldo));
    
                            event(new AdminNotificationEvent(
                                'TopUp Berhasil',
                                "User {$user->name} berhasil top-up Rp " . number_format($topUp->amount, 0, ',', '.'),
                                '/admin/topups'
                            ));
                        }
                    } elseif ($status === 'FAILED') {
                        $topUp->status = 'failed';
                        $topUp->save();
                    } else {
                        $topUp->status = 'pending';
                        $topUp->save();
                    }
                } else {
                    $pesanan = Pesanan::where('invoice_number', $orderId)->first();
                        
                    if($pesanan) {
                        if ($status === 'PAID') {
                            $pesanan->status = 'processing';
                            $pesanan->status_pesanan = 'processing';
                            
                            $expedition = $pesanan->expedition; 
                            $parts = explode('-', $expedition);
                            
                            $type = $parts[0];
                            
                            if ($type === 'express') {
                                $service      = $parts[1] ?? null;
                                $service_type = $parts[2] ?? null;
                                $cost         = (int) ($parts[3] ?? 0);
                                $cod_fee      = (int) ($parts[4] ?? 0);
                                $ansuransi_fee  = (int) ($parts[5] ?? 0);
                            } elseif ($type === 'instant') {
                                $vendor       = $parts[1] ?? null;
                                $service_type = $parts[2] ?? null;
                                $cost         = (int) ($parts[3] ?? 0);
                            }
            
                            // --- Sender (pengirim) ---
                            $storeSearch = $pesanan->sender_village . ', ' .
                                           $pesanan->sender_district . ', ' .
                                           $pesanan->sender_regency . ', ' .
                                           $pesanan->sender_province;
                            
                            $storeLat  = $pesanan->sender_lat ?? null;
                            $storeLng  = $pesanan->sender_lng ?? null;
                            
                            $storeAddr = $kiriminAja->searchAddress($storeSearch)['data'][0] ?? null;
                            
                            if (!$storeLat || !$storeLng) {
                                $geo = $this->geocode($storeSearch);
                                if ($geo) {
                                    $storeLat = $geo['lat'];
                                    $storeLng = $geo['lng'];
                                }
                            }
                            
                            // --- Receiver (penerima) ---
                            $userSearch = $pesanan->receiver_village . ', ' .
                                          $pesanan->receiver_district . ', ' .
                                          $pesanan->receiver_regency . ', ' .
                                          $pesanan->receiver_province;
                            
                            $userLat  = $pesanan->receiver_lat ?? null;
                            $userLng  = $pesanan->receiver_lng ?? null;
                            
                            $userAddr = $kiriminAja->searchAddress($userSearch)['data'][0] ?? null;
                            
                            if (!$userLat || !$userLng) {
                                $geo = $this->geocode($userSearch);
                                if ($geo) {
                                    $userLat = $geo['lat'];
                                    $userLng = $geo['lng'];
                                }
                            }
                            
                            $shipping_type = $type;
                            $shipping_cost = $cost;

                            $mandatoryTypes = [1, 3, 4, 8];
            
                            $itemType = (int) $pesanan->item_type;
                            
                            $isMandatory = in_array($itemType, $mandatoryTypes) ? 1 : 0;
                            $finalWeight = max(1000, $pesanan->weight); // Pastikan berat minimal 1000 gram
                            
                            if (in_array($shipping_type, ['express', 'instant'])) {
                                $schedule = $kiriminAja->getSchedules();
            
                                if ($shipping_type === 'express') {
                                    $storeDistrictId = $storeAddr['district_id'] ?? null;
                                    $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;
                                    $userDistrictId = $userAddr['district_id'] ?? null;
                                    $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;
                            
                                    $kiriminResponse = $kiriminAja->createExpressOrder([
                                        'address' => $pesanan->sender_address,
                                        'phone'   => $pesanan->sender_phone,
                                        'kecamatan_id' => $storeDistrictId,
                                        'kelurahan_id' => $storeSubdistrictId,
                                        'latitude' => $storeLat,
                                        'longitude' => $storeLng,
                                        'packages' => [
                                            [
                                                'order_id' => $pesanan->invoice_number,
                                                'destination_name' => $pesanan->receiver_name,
                                                'destination_phone' => $pesanan->receiver_phone,
                                                'destination_address' => $pesanan->receiver_address,
                                                'destination_kecamatan_id' => $userDistrictId,
                                                'destination_kelurahan_id' => $userSubdistrictId,
                                                'destination_zipcode' => $pesanan->receiver_postal_code ?? 55598,
                                                'weight' => $finalWeight,
                                                'width' => $pesanan->width,
                                                'height' => $pesanan->height,
                                                'length' => $pesanan->length,
                                                'item_value' => $pesanan->price,
                                                'shipping_cost' => $shipping_cost,
                                                'service' => $service,
                                                'insurance_amount' => $ansuransi_fee > 0 && $isMandatory ? $ansuransi_fee : 0,
                                                'service_type' => $service_type,
                                                'item_name' => 'Pesanan ' . $pesanan->invoice_number,
                                                'package_type_id' => $pesanan->item_type,
                                                'cod' => 0,
                                            ]
                                        ],
                                        'name' => $pesanan->sender_name,
                                        'zipcode' => $pesanan->sender_postal_code ?? '63271', // REVISI: Menambahkan fallback zipcode
                                        'platform_name' => 'TOKOSANCAKA.COM',
                                        'schedule' => $schedule['clock'] ?? null,
                                    ]);
                                } else { // instant
                                    $kiriminResponse = $kiriminAja->createInstantOrder([
                                        'service' => $vendor,
                                        'service_type' => $service_type,
                                        'vehicle' => 'motor',
                                        'order_prefix' => $pesanan->invoice_number,
                                        'packages' => [
                                            [
                                                'destination_name' => $pesanan->receiver_name,
                                                'destination_phone' => $pesanan->receiver_phone,
                                                'destination_lat' => $userLat,
                                                'destination_long' => $userLng,
                                                'destination_address' => $pesanan->receiver_address,
                                                'origin_name' => $pesanan->sender_name ?? 'Pengirim',
                                                'origin_phone' => $pesanan->sender_phone ?? '-',
                                                'origin_lat' => $storeLat,
                                                'origin_long' => $storeLng,
                                                'origin_address' => $pesanan->sender_address,
                                                'shipping_price' => $shipping_cost,
                                                'item' => [
                                                    'name' => 'Pesanan ' . $pesanan->invoice_number,
                                                    'description' => 'Pesanan dari toko',
                                                    'price' => $pesanan->price,
                                                    'weight' => $finalWeight,
                                                ]
                                            ]
                                        ]
                                    ]);
                                }
            
                                if (!empty($kiriminResponse['pickup_number'])) {
                                    $pesanan->resi = $kiriminResponse['pickup_number'];
                                }
    
$parts = explode('-', $validatedData['expedition']);

$layanan = $parts[0] ?? null; 
$vendor  = strtoupper($parts[1] ?? '');
$service = strtoupper($parts[2] ?? '');

$service_display = trim($vendor . ' ' . $service);

$messageTemplate = <<<TEXT
*Terimakasih Ya Kak Atas Orderannya 🙏*

Berikut adalah Nomor Order ID dan Invoice:
*{$pesanan->nomor_invoice}*

📦 Dari: *{$pesanan->sender_name}* ( {$pesanan->sender_phone} )  
➡️ Ke: *{$pesanan->receiver_name}* ( {$pesanan->receiver_phone} )

----------------------------------------
*Rincian Biaya:* - Ongkir: Rp {$shipping_cost}  
- Nilai Barang: Rp {$pesanan->total_harga_barang}  
- Asuransi: Rp {$ansuransi_fee}  
- COD Fee: Rp {$cod_fee ?? 0}  
----------------------------------------
*Total Bayar: Rp {$pesanan->price}* ✅ *Telah Terbayar Lunas*

----------------------------------------
*Detail Paket:* Deskripsi Barang: {$pesanan->item_description}  
Berat: {$pesanan->weight} Gram  
Dimensi: {$pesanan->length} x {$pesanan->width} x {$pesanan->height} cm  
Ekspedisi: {$service_display}  
Layanan: {$pesanan->service_type}  
Resi: *{$pesanan->resi ?? '-'}* ----------------------------------------

Semoga Paket Kakak  
*{$pesanan->sender_name} ➡️ {$pesanan->receiver_name}* aman dan selamat sampai tujuan. ✅

Kak {NAMA_TUJUAN} bisa cek resi dengan klik link berikut:  
https://tokosancaka.com/tracking/search?resi={$pesanan->nomor_invoice}

*Manajemen Sancaka*
TEXT;

$receiverMessage = str_replace('{NAMA_TUJUAN}', $pesanan->receiver_name, $messageTemplate);
$receiverWa = preg_replace('/^0/', '62', $pesanan->receiver_phone);
\App\Services\FonnteService::sendMessage($receiverWa, $receiverMessage);

$senderMessage = str_replace('{NAMA_TUJUAN}', $pesanan->sender_name, $messageTemplate);
$senderWa = preg_replace('/^0/', '62', $pesanan->sender_phone);
\App\Services\FonnteService::sendMessage($senderWa, $senderMessage);
                                }
                            } elseif ($status === 'FAILED') {
                                $pesanan->status = 'failed';
                            } else {
                                $pesanan->status = 'pending';
                            }
                            $pesanan->save();
                            
                            event(new AdminNotificationEvent(
                                'Order Update',
                                "Order dengan invoice {$pesanan->invoice_number} statusnya kini: {$pesanan->status}",
                                '/admin/orders'
                            ));
                        } else {
                                Log::warning("Order/TopUp dengan ID {$orderId} tidak ditemukan.");
                        }
                    }
                }
            DB::commit();
            return response()->json(['success' => true]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tripay Callback Error: '.$e->getMessage());
            return response()->json(['error' => 'Failed to update order/topup'], 500);
        }
    }

}