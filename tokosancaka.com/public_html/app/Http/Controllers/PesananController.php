<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\FonnteService;
use App\Services\KiriminAjaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NotifikasiUmum;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        \App\Models\Pesanan::where('status', 'baru')
                         ->where('telah_dilihat', false)
                         ->update(['telah_dilihat' => true]);
                         
        $query = Pesanan::query()->where('customer_id', Auth::id()); // Hanya pesanan milik user

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('resi', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('sender_phone', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest()->paginate(10); 
        return view('customer.pesanan.index', compact('orders')); // Disesuaikan ke view customer
    }

    public function create()
    {
        return view('customer.pesanan.create'); // Disesuaikan ke view customer
    }

    /**
     * Menyimpan pesanan baru dengan logika API terintegrasi.
     */
    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            // 1. Validasi semua input dari form
            $validatedData = $this->_validateOrderRequest($request);
            
            // 2. Simpan atau perbarui kontak jika dicentang
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            // 3. Kalkulasi semua biaya berdasarkan metode pembayaran yang dipilih
            $calculation = $this->_calculateTotalPaid($validatedData);
            $total_paid_ongkir = $calculation['total_paid_ongkir']; // Biaya ongkir murni
            $cod_value = $calculation['cod_value'];   // Total yang harus ditagih kurir jika COD/CODBARANG
            
            // 4. Siapkan data dan buat entri pesanan awal di database
            $pesananData = $this->_preparePesananData($validatedData, $total_paid_ongkir, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);

            // 5. Proses logika pembayaran spesifik
            if ($validatedData['payment_method'] === 'Potong Saldo') {
                $customer = Auth::user(); // Menggunakan user yang sedang login
                
                if ($customer->saldo < $total_paid_ongkir) {
                    throw new Exception('Saldo Anda tidak mencukupi untuk melakukan transaksi ini.');
                }
                
                $customer->decrement('saldo', $total_paid_ongkir);
                
                $pesanan->customer_id = $customer->id;
            }
            
            // 6. Proses pembuatan order ke API Ekspedisi atau Payment Gateway
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if (($kiriminResponse['status'] ?? false) !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { // Logika untuk pembayaran online via Tripay
                $orderItemsPayload = $this->_prepareOrderItemsPayload($calculation['shipping_cost'], $calculation['ansuransi_fee'], $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $total_paid_ongkir, $orderItemsPayload);

                if (empty($response['success'])) {
                    throw new Exception('Gagal membuat transaksi pembayaran. Pesan: ' . ($response['message'] ?? 'Tidak ada pesan.'));
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            // 7. Simpan finalisasi data pesanan
            if ($cod_value > 0) {
                $pesanan->price = $cod_value;
            } else {
                $pesanan->price = $total_paid_ongkir;
            }
            $pesanan->save();
            DB::commit();
            
              // ==========================================================
            // 👇 BLOK NOTIFIKASI REAL-TIME ADMIN (DILENGKAPI)
            // ==========================================================
            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->count() > 0) {
                    $customerName = Auth::user() ? Auth::user()->nama_lengkap : $validatedData['sender_name'];
                    
                    // Tentukan URL. Rute admin.pesanan.show butuh 'resi'.
                    // Jika resi belum ada (misal Tripay), kita link ke index.
                    $adminUrl = $pesanan->resi 
                                ? route('admin.pesanan.show', $pesanan->resi) 
                                : route('admin.pesanan.index');

                    $dataNotifAdmin = [
                        'tipe'        => 'Pesanan',
                        'judul'       => 'Pesanan Manual Baru!',
                        'pesan_utama' => "Pesanan {$pesanan->nomor_invoice} dibuat oleh {$customerName}.",
                        'url'         => $adminUrl,
                        'icon'        => 'fas fa-shipping-fast',
                    ];
                    Notification::send($admins, new NotifikasiUmum($dataNotifAdmin));
                }
            } catch (Exception $e) {
                Log::error('Gagal mengirim notifikasi pesanan manual ke admin: ' . $e->getMessage());
            }
            // ==========================================================
            // 👆 AKHIR BLOK NOTIFIKASI
            // ==========================================================

            // 8. Kirim notifikasi WhatsApp
            $notification_total = ($cod_value > 0) ? $cod_value : $total_paid_ongkir;
            $this->_sendWhatsappNotification($pesanan, $validatedData, $calculation['shipping_cost'], $calculation['ansuransi_fee'], $calculation['cod_fee'], $notification_total);
            $notifMessage = 'Pesanan baru dengan resi ' . ($pesanan->resi ?? $pesanan->nomor_invoice) . ' berhasil dibuat!';
            
            // 9. Arahkan pengguna ke halaman sukses atau pembayaran
            if (!empty($pesanan->payment_url)) {
                return redirect()->away($pesanan->payment_url);
            }
            
            return redirect()->route('customer.pesanan.index')->with('success', $notifMessage); // Redirect ke halaman customer

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('Order Creation Failed (Customer): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->where('customer_id', Auth::id())->firstOrFail();
        return view('customer.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->where('customer_id', Auth::id())->firstOrFail();
        return view('customer.pesanan.edit', compact('order'));
    }

    public function update(Request $request, $resi)
    {
        $validatedData = $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'item_description' => 'required|string',
            'weight' => 'required|numeric',
        ]);
        $order = Pesanan::where('resi', $resi)->where('customer_id', Auth::id())->firstOrFail();
        $order->update($validatedData);
        return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->where('customer_id', Auth::id())->firstOrFail();
        $order->delete();
        return redirect()->route('customer.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }
    
    // --- Endpoint API Helper ---

    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);
        try {
            $results = $kirimaja->searchAddress($request->input('search'));
            return response()->json($results['data'] ?? []);
        } catch (Exception $e) {
            Log::channel('daily')->error('KiriminAja Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat.'], 500);
        }
    }
    
    public function searchKontak(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'tipe'   => 'nullable|in:Pengirim,Penerima',
        ]);

        $query = Kontak::query()
            ->where(function ($q) use ($request) {
                $q->where(DB::raw('LOWER(nama)'), 'LIKE', '%' . strtolower($request->input('search')) . '%')
                    ->orWhere('no_hp', 'LIKE', "%{$request->input('search')}%");
            });

        if ($request->filled('tipe')) {
            $query->where(fn($q) => $q->where('tipe', $request->input('tipe'))->orWhere('tipe', 'Keduanya'));
        }

        return response()->json($query->limit(10)->get());
    }
    
    public function cek_Ongkir(Request $request, KiriminAjaService $kirimaja)
    {
        try {
            $validated = $request->validate([
                'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
                'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
                'item_price' => 'required|numeric', 'weight' => 'required|numeric',
                'service_type' => 'required|string',
            ]);

            $senderData = $this->_getAddressData($request, 'sender');
            $receiverData = $this->_getAddressData($request, 'receiver');
            
            if (in_array($request->service_type, ['instant', 'sameday']) && (!$senderData['lat'] || !$receiverData['lat'])) {
                return response()->json(['status' => false, 'message' => 'Koordinat alamat tidak ditemukan, tidak dapat menghitung ongkir instan/sameday.'], 422);
            }
    
            $itemValue = $request->item_price; 
            $options = [];
            
            $isMandatory = in_array((int) ($request->item_type ?? 0), [1, 3, 4, 8]) ? 1 : 0;
            
            if($isMandatory && $request->ansuransi == 'tidak') {
                return response()->json(['status' => false, 'message' => 'Wajib ada asuransi.'], 422);
            }

            if (in_array($request->service_type, ['instant', 'sameday'])) {
                $options = $kirimaja->getInstantPricing($senderData['lat'], $senderData['lng'], $request->sender_address, $receiverData['lat'], $receiverData['lng'], $request->receiver_address, $request->weight, $itemValue, 'motor');
            } else { 
                $category = $request->service_type === 'cargo' ? 'trucking' : 'regular';
                $options = $kirimaja->getExpressPricing($validated['sender_district_id'], $validated['sender_subdistrict_id'], $validated['receiver_district_id'], $validated['receiver_subdistrict_id'], $request->weight, $request->length ?? 1, $request->width ?? 1, $request->height ?? 1, $itemValue, null, $category, $request->ansuransi == 'iya' ? 1 : 0);
            }
            
            return response()->json($options);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cetakResiThermal($resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->where('customer_id', Auth::id())->firstOrFail();
        return view('customer.pesanan.cetak_thermal', ['pesanan' => $pesanan]);
    }
    
    // --- PRIVATE HELPER METHODS ---

    private function _validateOrderRequest(Request $request): array
    {
        return $request->validate([
            'sender_name' => 'required|string|max:255', 'sender_phone' => 'required|string|min:9|max:20', 'sender_address' => 'required|string',
            'sender_province' => 'required|string|max:100', 'sender_regency' => 'required|string|max:100', 'sender_district' => 'required|string|max:100',
            'sender_village' => 'required|string|max:100', 'sender_postal_code' => 'required|string|max:10', 'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|min:9|max:20', 'receiver_address' => 'required|string', 'receiver_province' => 'required|string|max:100',
            'receiver_regency' => 'required|string|max:100', 'receiver_district' => 'required|string|max:100', 'receiver_village' => 'required|string|max:100',
            'receiver_postal_code' => 'required|string|max:10', 'item_description' => 'required|string|max:255', 'item_price' => 'required|numeric|min:1000',
            'weight' => 'required|numeric|min:1', 'service_type' => 'required|string|in:regular,express,sameday,instant,cargo', 'expedition' => 'required|string',
            'payment_method' => 'required|string', 'ansuransi' => 'required|string|in:iya,tidak', 'pengirim_id' => 'nullable|integer|exists:kontaks,id',
            'penerima_id' => 'nullable|integer|exists:kontaks,id', 'length' => 'nullable|numeric|min:0', 'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0', 'item_type' => 'required|integer', 'save_sender' => 'nullable', 'save_receiver' => 'nullable',
            'sender_district_id' => 'required|integer', 'sender_subdistrict_id' => 'required|integer',
            'receiver_district_id' => 'required|integer', 'receiver_subdistrict_id' => 'required|integer',
        ]);
    }
    
    private function geocode(string $address): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'SancakaCargo/1.0'])->get("https://nominatim.openstreetmap.org/search", [
                'q' => $address,
                'format' => 'json',
                'limit' => 1
            ])->json();
            return !empty($response[0]) ? ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']] : null;
        } catch (Exception $e) {
            Log::error("Geocoding failed: " . $e->getMessage());
            return null;
        }
    }

    private function _getAddressData(Request $request, string $type): array
    {
        $lat = $request->input("{$type}_lat");
        $lng = $request->input("{$type}_lng");
        
        $kirimajaAddr = [
            'district_id' => $request->input("{$type}_district_id"),
            'subdistrict_id' => $request->input("{$type}_subdistrict_id"),
            'postal_code' => $request->input("{$type}_postal_code")
        ];
        
        if ((!$lat || !$lng) && $request->filled("{$type}_village")) {
             $fullAddress = implode(', ', array_filter([$request->input("{$type}_village"), $request->input("{$type}_district"), $request->input("{$type}_regency"), $request->input("{$type}_province")]));
             if ($geo = $this->geocode($fullAddress)) {
                  $lat = $geo['lat'];
                  $lng = $geo['lng'];
             }
        }
        return ['lat' => $lat, 'lng' => $lng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function _saveOrUpdateKontak(array $data, string $prefix, string $tipe)
    {
        if (!empty($data["save_{$prefix}"])) {
            Kontak::updateOrCreate(
                ['no_hp' => $this->_sanitizePhoneNumber($data["{$prefix}_phone"])],
                [
                    'nama'        => $data["{$prefix}_name"],
                    'alamat'      => $data["{$prefix}_address"],
                    'province'    => $data["{$prefix}_province"],
                    'regency'     => $data["{$prefix}_regency"],
                    'district'    => $data["{$prefix}_district"],
                    'village'     => $data["{$prefix}_village"],
                    'postal_code' => $data["{$prefix}_postal_code"],
                    'tipe'        => $tipe,
                ]
            );
        }
    }
    
    private function _preparePesananData(array $validatedData, int $total_ongkir, string $ip, string $userAgent): array
    {
        do {
            $nomorInvoice = 'SCK-' . date('Ymd') . '-'. strtoupper(Str::random(6));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        return array_merge(
            collect($validatedData)->only([
                'sender_name', 'sender_phone', 'sender_address', 'sender_province', 'sender_regency', 
                'sender_district', 'sender_village', 'sender_postal_code', 'receiver_name', 
                'receiver_phone', 'receiver_address', 'receiver_province', 'receiver_regency', 
                'receiver_district', 'receiver_village', 'receiver_postal_code', 'item_description', 
                'item_price', 'weight', 'length', 'width', 'height', 'service_type', 'expedition', 
                'payment_method', 'ansuransi', 'item_type'
            ])->all(),
            [
                'nomor_invoice' => $nomorInvoice,
                'price' => $total_ongkir,
                'status' => 'Menunggu Pembayaran',
                'status_pesanan' => 'Menunggu Pembayaran',
                'tanggal_pesanan' => now(),
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'customer_id' => Auth::id(), // Menggunakan ID user yang login
                'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null,
                'kontak_penerima_id' => $validatedData['penerima_id'] ?? null,
            ]
        );
    }

    private function _calculateTotalPaid(array $validatedData): array
    {
        $parts = explode('-', $validatedData['expedition']);
        $count = count($parts);

        $cod_fee       = ($count > 2) ? (int)end($parts) : 0;
        $ansuransi_fee = ($count > 3) ? (int)$parts[$count - 2] : 0;
        $shipping_cost = ($count > 4) ? (int)$parts[$count - 3] : 0;
        
        $cod_value = 0;

        $total_paid_ongkir = $shipping_cost;
        if ($validatedData['ansuransi'] == 'iya') {
            $total_paid_ongkir += $ansuransi_fee;
        }

        if ($validatedData['payment_method'] === 'CODBARANG') {
            $cod_value = (int)$validatedData['item_price'] + $total_paid_ongkir + $cod_fee;
        } elseif ($validatedData['payment_method'] === 'COD') {
            $cod_value = $total_paid_ongkir + $cod_fee;
        }
        
        return compact('total_paid_ongkir', 'cod_value', 'shipping_cost', 'ansuransi_fee', 'cod_fee');
    }
    
    private function _createKiriminAjaOrder(array $data, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        $expeditionParts = explode('-', $data['expedition']);
        $count = count($expeditionParts);

        $serviceGroup = $expeditionParts[0] ?? null;
        $courier      = $expeditionParts[1] ?? null;
        $service_type = $expeditionParts[2] ?? null;
        $shipping_cost = ($count > 4) ? (int)$expeditionParts[$count - 3] : 0;

        if (in_array($serviceGroup, ['instant', 'sameday'])) { 
            $payload = [
                'service' => $courier,
                'service_type' => $service_type,
                'vehicle' => 'motor',
                'order_prefix' => $pesanan->nomor_invoice,
                'packages' => [
                    [
                        'destination_name' => $data['receiver_name'],
                        'destination_phone' => $data['receiver_phone'],
                        'destination_lat' => $receiverData['lat'],
                        'destination_long' => $receiverData['lng'],
                        'destination_address' => $data['receiver_address'],
                        'destination_address_note' => $data['receiver_note'] ?? '-',
                        'origin_name' => $data['sender_name'],
                        'origin_phone' => $data['sender_phone'],
                        'origin_lat' => $senderData['lat'],
                        'origin_long' => $senderData['lng'],
                        'origin_address' => $data['sender_address'],
                        'origin_address_note' => $data['sender_note'] ?? '-',
                        'shipping_price' => (int)$shipping_cost,
                        'item' => [
                            'name' => $data['item_description'],
                            'description' => 'Pesanan dari pelanggan',
                            'price' => (int)$data['item_price'],
                            'weight' => (int)$data['weight'],
                        ]
                    ]
                ]
            ];
            return $kirimaja->createInstantOrder($payload);
        } else {
            $schedule = $kirimaja->getSchedules();
            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $schedule['clock'], 'platform_name' => 'tokosancaka.com',
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 'item_name' => $data['item_description'], 'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'], 'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => $data['weight'], 'width' => $data['width'] ?? 1, 'height' => $data['height'] ?? 1, 'length' => $data['length'] ?? 1,
                    'item_value' => (int)$data['item_price'], 'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0, 'cod' => $cod_value,
                    'shipping_cost' => (int)$shipping_cost
                ]]
            ];
            Log::info('KiriminAja Create Order Payload:', $payload);
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1]];
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

    private function _createTripayTransaction(array $data, Pesanan $pesanan, int $total, array $orderItems): array
    {
        $apiKey = config('tripay.api_key');
        $privateKey = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code');
        
        $payload = [
            'method'         => $data['payment_method'],
            'merchant_ref'   => $pesanan->nomor_invoice,
            'amount'         => $total,
            'customer_name'  => $data['receiver_name'],
            'customer_email' => 'customer@sancakacargo.com', 
            'customer_phone' => $data['receiver_phone'],
            'order_items'    => $orderItems,
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];
        
        Log::info('Tripay Create Transaction Payload:', $payload);

        $baseUrl = config('tripay.mode') === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        
        try {
            return Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload)->json();
        } catch (Exception $e) {
            Log::error('Tripay API Connection Failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        }
    }
    
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            return '0' . substr($phone, 2);
        }
        return Str::startsWith($phone, '0') ? $phone : '0' . $phone;
    }

    private function _sendWhatsappNotification(Pesanan $pesanan, array $validatedData, int $shipping_cost, int $ansuransi_fee, int $cod_fee, int $total_paid)
    {
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

Semoga Paket Kakak aman dan selamat sampai tujuan. ✅

Cek resi dengan klik link berikut:
https://tokosancaka.com/tracking/search?resi={RESI}

*Manajemen Sancaka*
TEXT;
        
        $message = str_replace(
            [
                '{NOMOR_INVOICE}', '{RESI}', '{SENDER_NAME}', '{SENDER_PHONE}', '{RECEIVER_NAME}', '{RECEIVER_PHONE}',
                '{ONGKIR}', '{NILAI_BARANG}', '{ASURANSI}', '{COD_FEE}', '{TOTAL_BAYAR}',
                '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}'
            ],
            [
                $pesanan->nomor_invoice, $pesanan->resi ?? $pesanan->nomor_invoice,
                $validatedData['sender_name'], $validatedData['sender_phone'],
                $validatedData['receiver_name'], $validatedData['receiver_phone'],
                number_format($shipping_cost, 0, ',', '.'),
                number_format($validatedData['item_price'], 0, ',', '.'),
                number_format($ansuransi_fee, 0, ',', '.'),
                number_format($cod_fee, 0, ',', '.'),
                number_format($total_paid, 0, ',', '.'),
                $validatedData['item_description'],
                $validatedData['weight'],
                $validatedData['length'] ?? 1,
                $validatedData['width'] ?? 1,
                $validatedData['height'] ?? 1,
                $validatedData['expedition'],
                $validatedData['service_type'],
            ],
            $messageTemplate
        );

        $senderWa = '62' . substr($this->_sanitizePhoneNumber($validatedData['sender_phone']), 1);
        $receiverWa = '62' . substr($this->_sanitizePhoneNumber($validatedData['receiver_phone']), 1);
        
        try {
            FonnteService::sendMessage($senderWa, $message);
            FonnteService::sendMessage($receiverWa, $message);
        } catch (Exception $e) {
            Log::error('Fonnte Service sendMessage failed: ' . $e->getMessage());
        }
    }
}
