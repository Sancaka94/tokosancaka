<?php



namespace App\Http\Controllers\Customer;



use App\Events\AdminNotificationEvent; // Tambahkan di atas

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use App\Models\Pesanan;

use App\Models\Product;

use Illuminate\Support\Str;

use App\Models\Order;

use App\Services\KiriminAjaService;

use Illuminate\Support\Facades\Http;

use App\Models\Kontak;

use Illuminate\Support\Facades\Log; // ✅ 1. Menambahkan 'use' statement untuk Log



class PesananController extends Controller

{

    /**

     * Menampilkan daftar semua pesanan milik pelanggan.

     */

    public function index()

    {

        $pesanans = Auth::user()->pesanans()

                            ->latest('tanggal_pesanan')

                            ->paginate(15);



        return view('customer.pesanan.index', compact('pesanans'));

    }

    

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

     * Menampilkan form untuk membuat pesanan baru.

     */

    public function create()

    {

        $products = Product::all();

        return view('customer.pesanan.create', compact('products'));

    }



    /**

     * Menyimpan pesanan baru dan memotong saldo jika perlu.

     */

     public function store(Request $request, KiriminAjaService $kirimaja) {

        DB::beginTransaction();

        try {

            $validatedData = $request->validate([

                'pengirim_id'        => 'nullable|integer',

                'sender_name'        => 'required|string|max:255',

                'sender_phone'       => 'required|string|max:20',

                'sender_address'     => 'required|string',

                'sender_province'    => 'required|string|max:100',

                'sender_regency'     => 'required|string|max:100',

                'sender_district'    => 'required|string|max:100',

                'sender_village'     => 'required|string|max:100',

                'sender_postal_code' => 'required|string|max:10',

    

                'penerima_id'        => 'nullable|integer',

                'receiver_name'      => 'required|string|max:255',

                'receiver_phone'     => 'required|string|max:20',

                'receiver_address'   => 'required|string',

                'receiver_province'  => 'required|string|max:100',

                'receiver_regency'   => 'required|string|max:100',

                'receiver_district'  => 'required|string|max:100',

                'receiver_village'   => 'required|string|max:100',

                'receiver_postal_code'=> 'required|string|max:10',

    

                'item_description'   => 'required|string|max:255',

                'item_price'         => 'required|numeric|min:1000',

                'weight'             => 'required|numeric|min:1',

                'length'             => 'nullable|numeric|min:0',

                'width'              => 'nullable|numeric|min:0',

                'height'             => 'nullable|numeric|min:0',

                'service_type'       => 'required|string|in:regular,express,sameday,instant,cargo',

                'expedition'         => 'required|string',

                'payment_method'     => 'required|string',

                'kelengkapan'        => 'nullable|array',

                'save_sender'        => 'nullable',

                'save_receiver'      => 'nullable',

            ]);

            

            $subtotal = $validatedData['item_price'];

    

            $pesananData = [

                'id_pengguna_pembeli'  => Auth::user()->id_pengguna,

                'nama_pembeli'         => $validatedData['receiver_name'],

                'telepon_pembeli'      => $validatedData['receiver_phone'],

                'alamat_pengiriman'    => $validatedData['receiver_address'],

    

                'total_harga_barang'   => $subtotal,

                'item_description'     => $validatedData['item_description'],

                'weight'               => $validatedData['weight'],

                'length'               => $validatedData['length'] ?? 0,

                'width'                => $validatedData['width'] ?? 0,

                'height'               => $validatedData['height'] ?? 0,

    

                'status_pesanan'       => 'Menunggu Pembayaran',

                'tanggal_pesanan'      => now(),

                'resi'                 => NULL,

                'status'               => 'Menunggu Pembayaran',

    

                'sender_name'          => $validatedData['sender_name'],

                'sender_phone'         => $validatedData['sender_phone'],

                'sender_address'       => $validatedData['sender_address'],

                'sender_province'      => $validatedData['sender_province'],

                'sender_regency'       => $validatedData['sender_regency'],

                'sender_district'      => $validatedData['sender_district'],

                'sender_village'       => $validatedData['sender_village'],

                'sender_postal_code'   => $validatedData['sender_postal_code'],

    

                'receiver_name'        => $validatedData['receiver_name'],

                'receiver_phone'       => $validatedData['receiver_phone'],

                'receiver_address'     => $validatedData['receiver_address'],

                'receiver_province'    => $validatedData['receiver_province'],

                'receiver_regency'     => $validatedData['receiver_regency'],

                'receiver_district'    => $validatedData['receiver_district'],

                'receiver_village'     => $validatedData['receiver_village'],

                'receiver_postal_code' => $validatedData['receiver_postal_code'],

    

                'tujuan'               => trim(last(explode(',', $validatedData['receiver_address']))),

                'service_type'         => $validatedData['service_type'],

                'expedition'           => $validatedData['expedition'],

                'payment_method'       => $validatedData['payment_method'],

    

                'kelengkapan'          => isset($validatedData['kelengkapan'])

                                            ? json_encode($validatedData['kelengkapan'])

                                            : null,

                'kontak_pengirim_id'   => $request->pengirim_id,

                'kontak_penerima_id'   => $request->penerima_id,

    

                'ip_address'           => $request->ip(),

                'user_agent'           => $request->userAgent(),

                'latitude'             => $request->latitude ?? null,

                'longitude'            => $request->longitude ?? null,

                'item_type'            => $request->item_type,

                

            ];

            

            do {

                $nomorInvoice = 'SCK-' . strtoupper(Str::random(4));

            } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

            

            $pesananData['nomor_invoice'] = $nomorInvoice;

    

            $pesanan = Pesanan::create($pesananData);

    // 👇 Ganti kode lama dengan blok ini 👇
$expeditionParts = explode('-', $validatedData['expedition']);

$type          = $expeditionParts[0] ?? 'unknown';
$service       = $expeditionParts[1] ?? null;
$service_type  = $expeditionParts[2] ?? null;
$cost          = (int)($expeditionParts[3] ?? 0);
$ansuransi_fee = (int)($expeditionParts[4] ?? 0);
$cod_fee       = (int)($expeditionParts[5] ?? 0);

$shipping_cost = $cost;
            

            $type = $parts[0];

            

            if ($type === 'express') {

                $service      = $parts[1] ?? null;

                $service_type = $parts[2] ?? null;

                $cost         = (int)$parts[3] ?? 0;

                $cod_fees      = $parts[4] ?? 0;

                $partsCod = explode('_', $cod_fees);

                $cod_fee = (int)$partsCod[0];

                $cod_fee_percent = (float)$partsCod[1];

                

                $ansuransi_fee      = isset($parts[5]) ? (int)$parts[5] : 0;

                $adminfee      = isset($parts[6]) ? (int)$parts[6] : 0;

            } elseif ($type === 'instant') {

                $service       = $parts[1] ?? null;

                $service_type = $parts[2] ?? null;

                $cost         = (int)$parts[3] ?? 0;

                $cod_fee      = isset($parts[4]) ? (int)$parts[4] : 0;

                $ansuransi_fee      =  0;

                $adminfee      = isset($parts[5]) ? (int)$parts[5] : 0;

            }

            

            if ($request->filled('pengirim_id')) {

                $store = Kontak::findOrFail($request->pengirim_id);

            

                $village  = $store->village  ?: $request->sender_village;

                $district = $store->district ?: $request->sender_district;

                $regency  = $store->regency  ?: $request->sender_regency;

                $province = $store->province ?: $request->sender_province;

            

                $storeSearch = $village . ', ' . $district . ', ' . $regency . ', ' . $province;

            

                $storeLat  = $store->latitude ?: ($request->sender_lat ?? null);

                $storeLng  = $store->longitude ?: ($request->sender_lng ?? null);

            

                $storeAddr = $kirimaja->searchAddress($storeSearch)['data'][0] ?? null;

            } else {

                $storeSearch = $request->sender_village . ', ' . $request->sender_district . ', ' . $request->sender_regency . ', ' . $request->sender_province;

                $storeLat    = $request->sender_lat ?? null;

                $storeLng    = $request->sender_lng ?? null;

                $storeAddr   = $kirimaja->searchAddress($storeSearch)['data'][0] ?? null;

            }

            

            if (!$storeLat || !$storeLng) {

                $geo = $this->geocode($storeSearch);

                if ($geo) {

                    $storeLat = $geo['lat'];

                    $storeLng = $geo['lng'];

                }

            }

            

            if ($request->filled('penerima_id')) {

                $user = Kontak::findOrFail($request->penerima_id);

            

                $village  = $user->village  ?: $request->receiver_village;

                $district = $user->district ?: $request->receiver_district;

                $regency  = $user->regency  ?: $request->receiver_regency;

                $province = $user->province ?: $request->receiver_province;

            

                $userSearch = $village . ', ' . $district . ', ' . $regency . ', ' . $province;

            

                $userLat  = $user->latitude ?: ($request->receiver_lat ?? null);

                $userLng  = $user->longitude ?: ($request->receiver_lng ?? null);

            

                $userAddr = $kirimaja->searchAddress($userSearch)['data'][0] ?? null;

            } else {

                $userSearch = $request->receiver_village . ', ' . $request->receiver_district . ', ' . $request->receiver_regency . ', ' . $request->receiver_province;

                $userLat    = $request->receiver_lat ?? null;

                $userLng    = $request->receiver_lng ?? null;

                $userAddr   = $kirimaja->searchAddress($userSearch)['data'][0] ?? null;

            }

            

            if (!$userLat || !$userLng) {

                $geo = $this->geocode($userSearch);

                if ($geo) {

                    $userLat = $geo['lat'];

                    $userLng = $geo['lng'];

                }

            }



            

            $shipping_type = $type;

            $shipping_cost = $cost;

    

            if (($request->payment_method == 'COD' || $request->payment_method == 'CODBARANG') && $shipping_type !== 'express') {

                return redirect()->back()->with('error', 'COD hanya tersedia untuk pengiriman express.');

            }

            

            $mandatoryTypes = [1, 3, 4, 8];

        

            $itemType = (int) $request->item_type;

            

            $isMandatory = in_array($itemType, $mandatoryTypes) ? 1 : 0;

            

            // if($request->payment_method == 'CODBARANG') {

            //     if($isMandatory) {

            //           $total = $subtotal + $shipping_cost + $cod_fee ?? 0 + $ansuransi_fee ?? 0;

            //     } else {

            //         if($request->ansuransi == 'iya') {

            //             $total = $subtotal + $shipping_cost + $cod_fee ?? 0 + $ansuransi_fee ?? 0;

            //         } else {

            //             $total = $subtotal + $shipping_cost + $cod_fee ?? 0;

            //         }

                     

            //     }

            // } else {

            //     if($isMandatory) {

            //           $total = $shipping_cost + $cod_fee ?? 0 + $ansuransi_fee ?? 0;

            //     } else {

            //         if($request->ansuransi == 'iya') {

            //             $total = $shipping_cost + $cod_fee ?? 0 + $ansuransi_fee ?? 0;

            //         } else {

            //             $total = $shipping_cost + $cod_fee ?? 0;

            //         }

            //     }

            // }

            

            if ($isMandatory) {

                $total = $subtotal + $shipping_cost + ($ansuransi_fee ?? 0);

            } else {

                if ($request->ansuransi == 'iya') {

                    $total = $subtotal + $shipping_cost + ($ansuransi_fee ?? 0);

                } else {

                    $total = $subtotal + $shipping_cost;

                }

            }

            

            if ($request->payment_method === 'COD' || $request->payment_method === 'CODBARANG') {

                $total += ($cod_fee ?? 0); 

            }

            

            $pesanan->price = $total;

            $pesanan->save();

            

            $orderItemsPayload = []; 

            

            $product_id = $pesanan->nomor_invoice;

            

            $orderItemsPayload[] = [

                'sku'      => 'SHIPPING',

                'name'     => 'Ongkos Kirim',

                'price'    => $shipping_cost,

                'quantity' => 1,

            ];

            

            if($cod_fee > 0) {

                $orderItemsPayload[] = [

                    'sku'      => 'CODFEES',

                    'name'     => 'Cod Fee',

                    'price'    => $cod_fee ?? 0,

                    'quantity' => 1,

                ];

            }

            

            if($request->ansuransi == 'iya' && $ansuransi_fee > 0) {

                $orderItemsPayload[] = [

                    'sku'      => 'ASRFEES',

                    'name'     => 'Ansuransi Fee',

                    'price'    => $ansuransi_fee ?? 0,

                    'quantity' => 1,

                ];

            }

            

            

            if ($shipping_type === 'express' && ($request->payment_method == 'COD' || $request->payment_method == 'CODBARANG')) {

                    $schedule = $kirimaja->getSchedules();

        

                    $storeDistrictId = $storeAddr['district_id'] ?? null;

                    $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;

                    $userDistrictId = $userAddr['district_id'] ?? null;

                    $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;

                    

                    $dataRequestCod = [

                        'address' => $request->sender_address,

                        'phone' => $request->sender_phone,

                        'kecamatan_id' => $storeDistrictId,

                        'kelurahan_id' => $storeSubdistrictId,

                        'latitude' => $storeLat,

                        'longitude' => $storeLng,

                        'packages' => [

                            [

                                'order_id' => $pesanan->nomor_invoice,

                                'destination_name' => $request->receiver_name,

                                'destination_phone' => $request->receiver_phone,

                                'destination_address' => $request->receiver_address,

                                'destination_kecamatan_id' => $userDistrictId,

                                'destination_kelurahan_id' => $userSubdistrictId,

                                'destination_zipcode' => $request->receiver_postal_code ?? 55598,

                                'weight' => $request->weight,

                                'width' => $request->width,

                                'height' => $request->height,

                                'length' => $request->length,

                                'item_value' => $subtotal,

                                'shipping_cost' => $shipping_cost,

                                'service' => $service,

                                'insurance_amount' => ($request->ansuransi == 'iya' && $ansuransi_fee > 0) ? $ansuransi_fee : 0,

                                'service_type' => $service_type,

                                'item_name' => 'Pesanan #' . $pesanan->nomor_invoice,

                                'package_type_id' => $itemType,

                                'cod' => ($request->payment_method === 'COD' || $request->payment_method === 'CODBARANG') ? $total : 0

                            ]

                        ],

                        'name' => $request->sender_name,

                        'zipcode' => $request->sender_postal_code,

                        'platform_name' => 'TOKOSANCAKA.COM',

                        'schedule' => $schedule['clock'] ?? null

                    ];

                    

                    $kiriminResponses = $kirimaja->createExpressOrder($dataRequestCod);

                    

                    if ($kiriminResponses['status'] === true) {

                        $pesanan->status = 'Menunggu Pickup';

                        $pesanan->status_pesanan = 'Menunggu Pickup';

                        $pesanan->save();

                    } else {

                        DB::rollBack();

                    

                        if (!empty($kiriminResponses['errors'])) {

                            $errorMessage = collect($kiriminResponses['errors'])

                                ->flatten()

                                ->implode(', ');

                        } else {

                            $errorMessage = $kiriminResponses['text'] ?? 'Gagal membuat order.';

                        }

                    

                        throw new \Exception($errorMessage);

                    }

                }

                

                if ($request->payment_method === 'COD' || $request->payment_method === 'CODBARANG') {

                    

$messageTemplate = <<<TEXT

*Terimakasih Ya Kak Atas Orderannya 🙏*



Berikut adalah Nomor Order ID dan Invoice:

*{NOMOR_INVOICE}*



📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )  

➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )



----------------------------------------

*Rincian Biaya:*  

- Ongkir: Rp {ONGKIR}  

- Nilai Barang: Rp {NILAI_BARANG}  

- Asuransi: Rp {ASURANSI}  

- COD Fee: Rp {COD_FEE}  

----------------------------------------

*Total Bayar: Rp {TOTAL_BAYAR}*



----------------------------------------

*Detail Paket:*  

Deskripsi Barang: {DESKRIPSI}  

Berat: {BERAT} Kg  

Dimensi: {PANJANG} x {LEBAR} x {TINGGI} cm  

Ekspedisi: {EKSPEDISI}  

Layanan: {LAYANAN}  

----------------------------------------



Semoga Paket Kakak  

*{SENDER_NAME} ➡️ {RECEIVER_NAME}*  

aman dan selamat sampai tujuan. ✅



Kak {NAMA_TUJUAN} bisa cek resi dengan klik link berikut:  

https://tokosancaka.com/tracking/{NOMOR_INVOICE}



*Manajemen Sancaka*

TEXT;



$message = str_replace(

    [

        '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',

        '{ONGKIR}', '{NILAI_BARANG}', '{ASURANSI}', '{COD_FEE}', '{TOTAL_BAYAR}',

        '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}', '{NAMA_TUJUAN}'

    ],

    [

        $pesanan->nomor_invoice,

        $validatedData['sender_name'],

        $validatedData['sender_phone'],

        $validatedData['receiver_name'],

        $validatedData['receiver_phone'],

        $shipping_cost,

        $validatedData['item_price'],

        $ansuransi_fee,

        $cod_fee ?? 0,

        $total_paid,

        $validatedData['item_description'],

        $validatedData['weight'],

        $validatedData['length'] ?? 1,

        $validatedData['width'] ?? 1,

        $validatedData['height'] ?? 1,

        $validatedData['expedition'],

        $validatedData['service_type'],

        '' 

    ],

    $messageTemplate

);



$receiverMessage = str_replace('{NAMA_TUJUAN}', $validatedData['receiver_name'], $message);

$receiverWa = preg_replace('/^0/', '62', $validatedData['receiver_phone']);

\App\Services\FonnteService::sendMessage($receiverWa, $receiverMessage);



$senderMessage = str_replace('{NAMA_TUJUAN}', $validatedData['sender_name'], $message);

$senderWa = preg_replace('/^0/', '62', $validatedData['sender_phone']);

\App\Services\FonnteService::sendMessage($senderWa, $senderMessage);

            

                    DB::commit();

                     return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan berhasil dibuat!');

                }

                

                $totalNonCod = $total - $subtotal;

                

                if($request->payment_method === 'Potong Saldo' || $request->payment_method === 'cash') {

                    $customer = Auth::user();

                    if ($customer->saldo < $totalNonCod) {

                         DB::rollBack();

                        throw new \Exception('Saldo Anda tidak mencukupi untuk pembayaran ini.');

                    }

                    $customer->saldo -= $totalNonCod;

                    $customer->save();

                    

                    

                    if($shipping_type === 'express') {

                        $schedule = $kirimaja->getSchedules();

        

                        $storeDistrictId = $storeAddr['district_id'] ?? null;

                        $storeSubdistrictId = $storeAddr['subdistrict_id'] ?? null;

                        $userDistrictId = $userAddr['district_id'] ?? null;

                        $userSubdistrictId = $userAddr['subdistrict_id'] ?? null;

                        

                        $dataRequestExpress = [

                            'address' => $request->sender_address,

                            'phone' => $request->sender_phone,

                            'kecamatan_id' => $storeDistrictId,

                            'kelurahan_id' => $storeSubdistrictId,

                            'latitude' => $storeLat,

                            'longitude' => $storeLng,

                            'packages' => [

                                [

                                    'order_id' => $pesanan->nomor_invoice,

                                    'destination_name' => $request->receiver_name,

                                    'destination_phone' => $request->receiver_phone,

                                    'destination_address' => $request->receiver_address,

                                    'destination_kecamatan_id' => $userDistrictId,

                                    'destination_kelurahan_id' => $userSubdistrictId,

                                    'destination_zipcode' => $request->receiver_postal_code ?? 55598,

                                    'weight' => $request->weight,

                                    'width' => $request->width,

                                    'height' => $request->height,

                                    'length' => $request->length,

                                    'item_value' => $subtotal,

                                    'shipping_cost' => $shipping_cost,

                                    'service' => $service,

                                    'insurance_amount' => $ansuransi_fee > 0 && $isMandatory ? $ansuransi_fee : 0,

                                    'service_type' => $service_type,

                                    'item_name' => 'Pesanan #' . $pesanan->nomor_invoice,

                                    'package_type_id' => 7,

                                    'cod' => 0

                                ]

                            ],

                            'name' => $request->sender_name,

                            'zipcode' => $request->sender_postal_code,

                            'platform_name' => 'TOKOSANCAKA.COM',

                            'schedule' => $schedule['clock'] ?? null

                        ];

                        

                        $kiriminResponse = $kirimaja->createExpressOrder($dataRequestExpress);

                        

                    } else {

                        

                         $kiriminResponse = $kirimaja->createInstantOrder([

                                    'service' => $service,

                                    'service_type' => $service_type,

                                    'vehicle' => 'motor',

                                    'order_prefix' => $pesanan->nomor_invoice,

                                    'packages' => [

                                        [

                                            'destination_name' => $request->receiver_name,

                                            'destination_phone' => $request->receiver_phone,

                                            'destination_lat' => $userLat,

                                            'destination_long' => $userLng,

                                            'destination_address' => $request->receiver_address,

                                            'destination_address_note' => $request->receiver_note ?? '-',

                                            'origin_name' =>  $request->sender_name ?? 'Toko Penjual',

                                            'origin_phone' => $request->sender_phone ?? '-',

                                            'origin_lat' => $storeLat,

                                            'origin_long' => $storeLng,

                                            'origin_address' => $request->sender_address,

                                            'origin_address_note' => $request->sender_note ?? '-',

                                           'shipping_price' => (int)$shipping_cost - (int) $adminfee,

                                            'item' => [

                                                'name' => 'Pesanan #' . $pesanan->invoice_number,

                                                'description' => 'Pesanan dari toko',

                                                'price' => $subtotal,

                                                'weight' => $request->weight,

                                            ]

                                        ]

                                    ]

                                ]);

                    }

                    

                    if ($kiriminResponse['status'] === true) {

                        $pesanan->status = 'Menunggu Pickup';

                        $pesanan->status_pesanan = 'Menunggu Pickup';

                        $pesanan->save();

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

                        

                    DB::commit();

                    return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan berhasil dibuat!');

                    

                }

        

                $apiKey       = config('tripay.api_key');

                $privateKey   = config('tripay.private_key');

                $merchantCode = config('tripay.merchant_code');

                $mode = config('tripay.mode');

        

                $payload = [

                    'method'         => $request->payment_method,

                    'merchant_ref'   => $pesanan->nomor_invoice,

                    'amount'         => $totalNonCod,

                    'customer_name'  => $request->receiver_name,

                    'customer_email' => 'support@tokosancaka.com',

                    'customer_phone' => $request->receiver_phone,

                    'order_items'    => $orderItemsPayload, 

                    'expired_time'   => time() + (24 * 60 * 60),

                    'signature'      => hash_hmac('sha256', $merchantCode.$pesanan->nomor_invoice.$total, $privateKey),

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

                    $pesanan->payment_url = $response['data']['qr_url'] 

                                      ?? $response['data']['pay_url'] 

                                      ?? $response['data']['pay_code'] 

                                      ?? $response['data']['checkout_url'] 

                                      ?? null;

                    $pesanan->save();

                } else {

                     DB::rollBack();

                    throw new \Exception('Gagal membuat transaksi.');

                }

                

                $messageTemplate = <<<TEXT

*Terimakasih Ya Kak Atas Orderannya 🙏*



Berikut adalah Nomor Order ID dan Invoice:

*{NOMOR_INVOICE}*



📦 Dari: *{SENDER_NAME}* ( {SENDER_PHONE} )  

➡️ Ke: *{RECEIVER_NAME}* ( {RECEIVER_PHONE} )



----------------------------------------

*Rincian Biaya:*  

- Ongkir: Rp {ONGKIR}  

- Nilai Barang: Rp {NILAI_BARANG}  

- Asuransi: Rp {ASURANSI}  

- COD Fee: Rp {COD_FEE}  

----------------------------------------

*Total Bayar: Rp {TOTAL_BAYAR}*



----------------------------------------

*Detail Paket:*  

Deskripsi Barang: {DESKRIPSI}  

Berat: {BERAT} Gram  

Dimensi: {PANJANG} x {LEBAR} x {TINGGI} cm  

Ekspedisi: {EKSPEDISI}  

Layanan: {LAYANAN}  

----------------------------------------



Semoga Paket Kakak  

*{SENDER_NAME} ➡️ {RECEIVER_NAME}*  

aman dan selamat sampai tujuan. ✅



Kak {NAMA_TUJUAN} bisa cek resi dengan klik link berikut:  

https://tokosancaka.com/tracking/search?resi={NOMOR_INVOICE}



*Manajemen Sancaka*

TEXT;



$service_display = trim($vendor . ' ' . $service);



$message = str_replace(

    [

        '{NOMOR_INVOICE}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',

        '{ONGKIR}', '{NILAI_BARANG}', '{ASURANSI}', '{COD_FEE}', '{TOTAL_BAYAR}',

        '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}'

    ],

    [

        $pesanan->nomor_invoice,

        $validatedData['sender_name'],

        $validatedData['sender_phone'],

        $validatedData['receiver_name'],

        $validatedData['receiver_phone'],

        $shipping_cost,

        $validatedData['item_price'],

        $ansuransi_fee,

        $cod_fee ?? 0,

        $total_paid,

        $validatedData['item_description'],

        $validatedData['weight'],

        $validatedData['length'] ?? 1,

        $validatedData['width'] ?? 1,

        $validatedData['height'] ?? 1,

        $service_display,

        $validatedData['service_type'],

    ],

    $messageTemplate

);



$receiverMessage = str_replace('{NAMA_TUJUAN}', $validatedData['receiver_name'], $message);

$receiverWa = preg_replace('/^0/', '62', $validatedData['receiver_phone']);

\App\Services\FonnteService::sendMessage($receiverWa, $receiverMessage);



$senderMessage = str_replace('{NAMA_TUJUAN}', $validatedData['sender_name'], $message);

$senderWa = preg_replace('/^0/', '62', $validatedData['sender_phone']);

\App\Services\FonnteService::sendMessage($senderWa, $senderMessage);

            

            DB::commit();

    

             if (!empty($response['data']['checkout_url'])) {

                return redirect()->away($response['data']['checkout_url']);

            }

            

           return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan berhasil dibuat!');

    

        } catch (\Throwable $e) {

            DB::rollBack();

            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());

        }

     }

     

    public function store_backups(Request $request)

    {

        $validatedData = $request->validate([

            'sender_name' => 'required|string|max:255',

            'sender_phone' => 'required|string|max:20',

            'sender_address' => 'required|string',

            'receiver_name' => 'required|string|max:255',

            'receiver_phone' => 'required|string|max:20',

            'receiver_address' => 'required|string',

            'service_type' => 'required|string',

            'expedition' => 'required|string',

            'payment_method' => 'required|string',

            'bank_name' => 'required_if:payment_method,Transfer Bank', // Wajib jika transfer bank

            'item_description' => 'required|string',

            'weight' => 'required|numeric|min:0',

            'total_harga_barang' => 'required|numeric|min:0',

            // ... validasi lainnya

        ]);



        $customer = Auth::user();

        $totalBiaya = $validatedData['total_harga_barang'];



        try {

            DB::transaction(function () use ($validatedData, $customer, $totalBiaya) {

                

                // --- Logika Potong Saldo ---

                if ($validatedData['payment_method'] === 'Potong Saldo') {

                    if ($customer->saldo < $totalBiaya) {

                        throw new \Exception('Saldo Anda tidak mencukupi untuk pembayaran ini.');

                    }

                    $customer->saldo -= $totalBiaya;

                    $customer->save();

                }

                

                // --- Logika Membuat Pesanan ---

                Pesanan::create([

                    'id_pengguna_pembeli' => $customer->id_pengguna,

                    'nomor_invoice' => 'INV/' . now()->format('Ymd') . '/' . strtoupper(Str::random(6)),

                    'resi' => 'SCK' . strtoupper(Str::random(8)),

                    'sender_name' => $validatedData['sender_name'],

                    'sender_phone' => $validatedData['sender_phone'],

                    'sender_address' => $validatedData['sender_address'],

                    'nama_pembeli' => $validatedData['receiver_name'],

                    'telepon_pembeli' => $validatedData['receiver_phone'],

                    'alamat_pengiriman' => $validatedData['receiver_address'],

                    'service_type' => $validatedData['service_type'],

                    'expedition' => $validatedData['expedition'],

                    'payment_method' => $validatedData['payment_method'],

                    'bank_name' => $validatedData['bank_name'] ?? null,

                    'item_description' => $validatedData['item_description'],

                    'weight' => $validatedData['weight'],

                    'total_harga_barang' => $totalBiaya,

                    'status_pesanan' => 'Menunggu Pickup',

                    'tanggal_pesanan' => now(),

                ]);

            });



        } catch (\Exception $e) {

            return redirect()->back()->withInput()->with('error', $e->getMessage());

        }



        return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan berhasil dibuat!');

        

        // ... di dalam metode store() setelah pesanan berhasil dibuat ...

        $message = $customer->nama_lengkap . ' telah membuat pesanan baru.';

        $url = route('admin.pesanan.show', $pesanan->resi);

        event(new AdminNotificationEvent('Pesanan Baru Diterima!', $message, $url));

        

    }



    /**

     * Menampilkan detail satu pesanan.

     */

    public function show($id)

    {

        $pesanan = Auth::user()->pesanans()->findOrFail($id);

        return view('customer.pesanan.show', compact('pesanan'));

    }

    
    /**
     * ✅ DIPERBARUI: Menggunakan kode baru Anda yang lebih canggih.
     * Termasuk logging dan filter yang lebih baik untuk 'tipe' kontak.
     */
    public function searchKontak(Request $request)
    {
        Log::info('API Search Kontak dipanggil dengan:', $request->all());

        $request->validate([
            'search' => 'required|string|min:2',
            'tipe'   => 'nullable|in:Pengirim,Penerima',
        ]);

        $searchTerm = $request->input('search');
        $tipe = $request->input('tipe');

        $query = Kontak::query();

        $query->where(function ($q) use ($searchTerm) {
            $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($searchTerm) . '%')
              ->orWhere('no_hp', 'LIKE', "%{$searchTerm}%");
        });

        if ($tipe) {
            $query->where(function ($q) use ($tipe) {
                $q->where('tipe', $tipe)
                  ->orWhere('tipe', 'Keduanya');
            });
        }

        Log::info('SQL Query untuk Pencarian Kontak:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        $kontaks = $query->limit(10)->get();
        Log::info('Kontak yang ditemukan:', ['count' => $kontaks->count()]);

        return response()->json($kontaks);
    }



    /**

     * Menampilkan halaman cetak untuk resi thermal.

     */

    public function cetakResiThermal($resi)

    {

        $pesanan = Pesanan::where('resi', $resi)

                           ->where('id_pengguna_pembeli', auth()->id())

                           ->firstOrFail();



        return view('admin.pesanan.cetak_thermal', compact('pesanan'));

    }

    

        public function riwayat()

    {

        // ✅ DIPERBAIKI: Menggunakan Model 'Order' yang terhubung ke tabel 'orders'.

        // ✅ DIPERBAIKI: Menggunakan kolom 'user_id' untuk mencari data user yang login.

        $orders = Order::where('user_id', auth()->id())

                      ->latest() // Mengurutkan dari yang terbaru berdasarkan 'created_at'

                      ->paginate(10); 



        // Arahkan ke view riwayat Anda

        // Pastikan view 'riwayat.blade.php' sudah disesuaikan untuk menampilkan data dari $orders

        return view('customer.pesanan.riwayat', compact('orders'));

    }





}

