<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Kontak;
use App\Models\User;
use App\Services\KiriminAjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Carbon\Carbon;
use App\Exports\PesanansExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\FonnteService;


class PesananController extends Controller
{
    public function index(Request $request)
    {
        \App\Models\Pesanan::where('status', 'baru')
                         ->where('telah_dilihat', false)
                         ->update(['telah_dilihat' => true]);
                                         
        $query = Pesanan::query();
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
        return view('admin.pesanan.index', compact('orders'));
    }

    public function create()
    {
        $customers = User::orderBy('nama_lengkap', 'asc')->get();
        return view('admin.pesanan.create', compact('customers'));
    }

    public function store(Request $request, KiriminAjaService $kirimaja)
    {
        DB::beginTransaction();
        try {
            $validatedData = $this->_validateOrderRequest($request);
            
            $this->_saveOrUpdateKontak($validatedData, 'sender', 'Pengirim');
            $this->_saveOrUpdateKontak($validatedData, 'receiver', 'Penerima');

            $validatedData['sender_phone'] = $this->_sanitizePhoneNumber($validatedData['sender_phone']);
            $validatedData['receiver_phone'] = $this->_sanitizePhoneNumber($validatedData['receiver_phone']);
            
            list($serviceGroup, $courier, $service, $shipping_cost, $ansuransi_fee, $cod_fee) = array_pad(explode('-', $validatedData['expedition']), 6, 0);
            
            $shipping_cost = (int) $shipping_cost;
            $ansuransi_fee = (int) $ansuransi_fee;
            $cod_fee = (int) $cod_fee;

            $total_paid = 0;
            $cod_value = 0;

            if ($validatedData['payment_method'] === 'CODBARANG') {
                $total_paid = (int)$validatedData['item_price'] + $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } elseif ($validatedData['payment_method'] === 'COD') {
                $total_paid = $shipping_cost + $cod_fee;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
                $cod_value = $total_paid;
            } else { // Termasuk Potong Saldo dan metode Tripay lainnya
                $total_paid = $shipping_cost;
                if ($validatedData['ansuransi'] == 'iya') {
                    $total_paid += $ansuransi_fee;
                }
            }
            
            $pesananData = $this->_preparePesananData($validatedData, $total_paid, $request->ip(), $request->userAgent());
            $pesanan = Pesanan::create($pesananData);
            
            if (in_array($validatedData['payment_method'], ['COD', 'CODBARANG', 'Potong Saldo'])) {
                $senderAddressData = $this->_getAddressData($request, 'sender');
                $receiverAddressData = $this->_getAddressData($request, 'receiver');
                
                $kiriminResponse = $this->_createKiriminAjaOrder($validatedData, $pesanan, $kirimaja, $senderAddressData, $receiverAddressData, $cod_value);
                
                if ($kiriminResponse['status'] !== true) {
                    throw new Exception($kiriminResponse['text'] ?? ($kiriminResponse['errors'][0]['text'] ?? 'Gagal membuat order di sistem ekspedisi.'));
                }
                
                $pesanan->status = 'Menunggu Pickup';
                $pesanan->status_pesanan = 'Menunggu Pickup';
                $pesanan->resi = $kiriminResponse['result']['awb_no'] ?? ($kiriminResponse['results'][0]['awb'] ?? null);
            } else { // Logika untuk pembayaran online via Tripay
                $tripay_amount = $total_paid;
                $orderItemsPayload = $this->_prepareOrderItemsPayload($shipping_cost, $ansuransi_fee, $validatedData['ansuransi']);
                $response = $this->_createTripayTransaction($validatedData, $pesanan, $tripay_amount, $orderItemsPayload);

                Log::channel('daily')->info('Tripay Response: ', $response ?? ['message' => 'No response from Tripay']);
                
                if (empty($response['success'])) {
                    $errorMessage = 'Gagal membuat transaksi pembayaran online. Pesan dari Server: ' . ($response['message'] ?? 'Tidak ada pesan.');
                    throw new Exception($errorMessage);
                }

                $pesanan->payment_url = $response['data']['checkout_url'];
            }
            
            $pesanan->price = $total_paid;
            $pesanan->save();
            DB::commit();

            // Kirim notifikasi WA setelah pesanan berhasil dibuat
            $this->_sendWhatsappNotification($pesanan, $validatedData, $shipping_cost, $ansuransi_fee, $cod_fee, $total_paid);
            
            if (!empty($pesanan->payment_url)) return redirect()->away($pesanan->payment_url);
            
            return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan baru dengan resi ' . $pesanan->resi . ' berhasil dibuat!');
        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('daily')->error('Order Creation Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.show', compact('order'));
    }

    public function edit($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.edit', compact('order'));
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
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'payment_method' => 'required|string',
            'service_type' => 'required|string',
            'expedition' => 'required|string',
            'kelengkapan' => 'nullable|array',
        ]);
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $validatedData['kelengkapan'] = $request->has('kelengkapan') ? $request->input('kelengkapan') : null;
        $order->update($validatedData);
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil diperbarui.');
    }

    public function destroy($resi)
    {
        $order = Pesanan::where('resi', $resi)->firstOrFail();
        $order->delete();
        return redirect()->route('admin.pesanan.index')->with('success', 'Pesanan ' . $resi . ' berhasil dihapus.');
    }

    public function searchAddressApi(Request $request, KiriminAjaService $kirimaja)
    {
        $request->validate(['search' => 'required|string|min:3']);
        $searchQuery = $request->input('search');
        try {
            $results = $kirimaja->searchAddress($searchQuery);
            if (empty($results['status']) || empty($results['data'])) {
                return response()->json([]);
            }
            return response()->json($results['data']);
        } catch (Exception $e) {
            Log::channel('daily')->error('KiriminAja Address Search Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data alamat dari ekspedisi.'], 500);
        }
    }
    
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
            
            $isMandatory = in_array((int) $request->item_type, [1, 3, 4, 8]) ? 1 : 0;
            
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

    public function count()
    {
        $count = \App\Models\Pesanan::where('status', 'baru')
                                   ->where('telah_dilihat', false)
                                   ->count();
        return response()->json(['count' => $count]);
    }

    // --- HELPER METHODS ---

    /**
     * @param string $resi
     * @return \Illuminate\Http\Response
     */
    public function cetakResiThermal(string $resi)
    {
        $pesanan = Pesanan::where('resi', $resi)->firstOrFail();
        return view('admin.pesanan.cetak_thermal', compact('pesanan'));
    }

    /**
     * Mengirim notifikasi WhatsApp kepada pengirim dan penerima.
     * @param Pesanan $pesanan
     * @return void
     */
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
                '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}'
            ],
            [
                $pesanan->nomor_invoice,
                $validatedData['sender_name'],
                $validatedData['sender_phone'],
                $validatedData['receiver_name'],
                $validatedData['receiver_phone'],
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

        $receiverMessage = str_replace('{NAMA_TUJUAN}', $validatedData['receiver_name'], $message);
        $receiverWa = preg_replace('/^0/', '62', $validatedData['receiver_phone']);
        FonnteService::sendMessage($receiverWa, $receiverMessage);

        $senderMessage = str_replace('{NAMA_TUJUAN}', $validatedData['sender_name'], $message);
        $senderWa = preg_replace('/^0/', '62', $validatedData['sender_phone']);
        FonnteService::sendMessage($senderWa, $senderMessage);
    }
    
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
    
    private function _getAddressData(Request $request, string $type): array
    {
        $prefix = $type;
        $village = $request->input("{$prefix}_village");
        $district = $request->input("{$prefix}_district");
        $regency = $request->input("{$prefix}_regency");
        $province = $request->input("{$prefix}_province");
        $lat = $request->input("{$prefix}_lat");
        $lng = $request->input("{$prefix}_lng");
        
        $kirimajaAddr = [
            'district_id' => $request->input("{$prefix}_district_id"),
            'subdistrict_id' => $request->input("{$prefix}_subdistrict_id"),
            'postal_code' => $request->input("{$prefix}_postal_code")
        ];
        
        if ((!$lat || !$lng) && $village) {
             $fullAddressForGeocode = implode(', ', array_filter([$village, $district, $regency, $province]));
            if ($geo = $this->geocode($fullAddressForGeocode)) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        return ['lat' => $lat, 'lng' => $lng, 'kirimaja_data' => $kirimajaAddr];
    }

    private function geocode(string $address): ?array
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
        try {
            $response = Http::withHeaders(['User-Agent' => 'YourAppName/1.0 (your-contact@example.com)'])->get($url)->json();
            if (!empty($response[0])) {
                return ['lat' => (float) $response[0]['lat'], 'lng' => (float) $response[0]['lon']];
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
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
    
    private function _preparePesananData(array $validatedData, int $total, string $ip, string $userAgent): array
    {
        do {
            $tanggal = date('Ymd');
            $nomorInvoice = 'SCK-' . $tanggal . '-' .'SANCAKA'. strtoupper(Str::random(4));
        } while (Pesanan::where('nomor_invoice', $nomorInvoice)->exists());

        return array_merge(
            collect($validatedData)->only([
                'sender_name', 'sender_phone', 'sender_address',
                'sender_province', 'sender_regency', 'sender_district',
                'sender_village', 'sender_postal_code', 'receiver_name',
                'receiver_phone', 'receiver_address', 'receiver_province',
                'receiver_regency', 'receiver_district', 'receiver_village',
                'receiver_postal_code', 'item_description', 'weight',
                'service_type', 'expedition', 'payment_method', 'item_type'
            ])->all(),
            [
                'nama_pembeli' => $validatedData['receiver_name'],
                'telepon_pembeli' => $validatedData['receiver_phone'],
                'alamat_pengiriman' => $validatedData['receiver_address'],
                'total_harga_barang' => $validatedData['item_price'],
                'length' => $validatedData['length'] ?? 1,
                'width' => $validatedData['width'] ?? 1,
                'height' => $validatedData['height'] ?? 1,
                'status_pesanan' => 'Menunggu Pembayaran',
                'tanggal_pesanan' => now(),
                'status' => 'Menunggu Pembayaran',
                'tujuan' => $validatedData['receiver_regency'],
                'kontak_pengirim_id' => $validatedData['pengirim_id'] ?? null,
                'kontak_penerima_id' => $validatedData['penerima_id'] ?? null,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'nomor_invoice' => $nomorInvoice,
                'price' => $total,
            ]
        );
    }
    
    private function _createKiriminAjaOrder(array $data, Pesanan $pesanan, KiriminAjaService $kirimaja, array $senderData, array $receiverData, int $cod_value): array
    {
        list($serviceGroup, $courier, $service_type, $shipping_cost) = array_pad(explode('-', $data['expedition']), 4, null);
        if (in_array($serviceGroup, ['instant', 'sameday'])) { 
            // Logika untuk instant/sameday (jika ada)
            return []; // Placeholder
        } else {
            $schedule = $kirimaja->getSchedules();
            $payload = [
                'address' => $data['sender_address'], 'phone' => $data['sender_phone'], 'name' => $data['sender_name'],
                'kecamatan_id' => $senderData['kirimaja_data']['district_id'], 'kelurahan_id' => $senderData['kirimaja_data']['subdistrict_id'],
                'zipcode' => $senderData['kirimaja_data']['postal_code'], 'schedule' => $schedule['clock'], 'platform_name' => 'YourPlatformName.COM',
                'packages' => [[
                    'order_id' => $pesanan->nomor_invoice, 'item_name' => $data['item_description'], 'package_type_id' => (int)$data['item_type'],
                    'destination_name' => $data['receiver_name'], 'destination_phone' => $data['receiver_phone'], 'destination_address' => $data['receiver_address'],
                    'destination_kecamatan_id' => $receiverData['kirimaja_data']['district_id'], 'destination_kelurahan_id' => $receiverData['kirimaja_data']['subdistrict_id'],
                    'destination_zipcode' => $receiverData['kirimaja_data']['postal_code'],
                    'weight' => $data['weight'], 'width' => $data['width'] ?? 1, 'height' => $data['height'] ?? 1, 'length' => $data['length'] ?? 1,
                    'item_value' => (int)$data['item_price'], 'shipping_cost' => (int)$shipping_cost, 'service' => $courier, 'service_type' => $service_type,
                    'insurance_amount' => ($data['ansuransi'] == 'iya') ? (int)$data['item_price'] : 0, 'cod' => $cod_value
                ]]
            ];
            return $kirimaja->createExpressOrder($payload);
        }
    }

    private function _prepareOrderItemsPayload(int $shipping_cost, int $ansuransi_fee, string $ansuransi_choice): array
    {
        $payload = [];
        $payload[] = ['sku' => 'SHIPPING', 'name' => 'Ongkos Kirim', 'price' => $shipping_cost, 'quantity' => 1];
        
        if ($ansuransi_choice == 'iya' && $ansuransi_fee > 0) {
            $payload[] = ['sku' => 'INSURANCE', 'name' => 'Biaya Asuransi', 'price' => $ansuransi_fee, 'quantity' => 1];
        }
        return $payload;
    }

    private function _createTripayTransaction(array $validatedData, Pesanan $pesanan, int $total, array $orderItemsPayload): array
    {
        $apiKey = config('tripay.api_key'); $privateKey = config('tripay.private_key');
        $merchantCode = config('tripay.merchant_code'); $mode = config('tripay.mode');
        
        $payload = [
            'method'      => $validatedData['payment_method'],
            'merchant_ref'      => $pesanan->nomor_invoice,
            'amount'      => $total,
            'customer_name'  => $validatedData['receiver_name'],
            'customer_email' => 'tokosancaka@gmail.com', // Ganti dengan email valid jika ada
            'customer_phone' => $validatedData['receiver_phone'],
            'order_items'    => $orderItemsPayload,
            'expired_time'   => time() + (24 * 60 * 60),
            'signature'      => hash_hmac('sha256', $merchantCode . $pesanan->nomor_invoice . $total, $privateKey),
        ];

        Log::channel('daily')->info('Tripay Request Payload:', $payload);

        $baseUrl = $mode === 'production' ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
        
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])->post($baseUrl, $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::channel('daily')->error('Tripay API Connection Failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Tidak dapat terhubung ke server pembayaran.'];
        }
    }
    
    private function _sanitizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (Str::startsWith($phone, '62')) {
            return '0' . substr($phone, 2);
        }
        if (!Str::startsWith($phone, '0')) {
            return '0' . $phone;
        }
        return $phone;
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
                '{DESKRIPSI}', '{BERAT}', '{PANJANG}', '{LEBAR}', '{TINGGI}', '{EKSPEDISI}', '{LAYANAN}'
            ],
            [
                $pesanan->nomor_invoice,
                $validatedData['sender_name'],
                $validatedData['sender_phone'],
                $validatedData['receiver_name'],
                $validatedData['receiver_phone'],
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

        $receiverMessage = str_replace('{NAMA_TUJUAN}', $validatedData['receiver_name'], $message);
        $receiverWa = preg_replace('/^0/', '62', $validatedData['receiver_phone']);
        FonnteService::sendMessage($receiverWa, $receiverMessage);

        $senderMessage = str_replace('{NAMA_TUJUAN}', $validatedData['sender_name'], $message);
        $senderWa = preg_replace('/^0/', '62', $validatedData['sender_phone']);
        FonnteService::sendMessage($senderWa, $senderMessage);
    }
}
